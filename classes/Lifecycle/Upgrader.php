<?php
/**
 * One-time data migration from v2.x.x to v3.0.0.
 *
 * Algorithm:
 *   1. Read the v2 option `secondary_title_db_version` (added in
 *      v3.0.0; v2.x.x never sets it).
 *   2. If the value is already at least {@see self::TARGET_VERSION},
 *      the migration has already run; do nothing.
 *   3. For every v2 option key:
 *        a. Read its current value.
 *        b. Save the value to `v2_secondary_title_<key>` as a
 *           forensic backup.
 *        c. Migrate the value to the v3 schema (currently a 1:1
 *           copy with light normalization; future versions may
 *           transform more aggressively).
 *        d. Overwrite the live `secondary_title_<key>` with the
 *           migrated value.
 *   4. Sanitize legacy post meta: v2 saved `_secondary_title`
 *      HTML-escaped from the Classic Editor but raw (unsanitized)
 *      from the block editor. Escaped values are decoded once at
 *      render time and must not be rewritten here; raw values
 *      containing actual markup are run through `wp_kses_post()`
 *      once.
 *   5. Seed any v3-only option that does not exist yet.
 *   6. Set `secondary_title_db_version` to {@see self::TARGET_VERSION}.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Lifecycle;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Upgrades v2.x.x data to v3.0.0.
 *
 * @since 3.0.0
 */
final class Upgrader {

	/**
	 * Database version that signals "already migrated".
	 *
	 * @var int
	 */
	public const TARGET_VERSION = 3;

	/**
	 * The v2.x.x option keys that we know about and migrate. New
	 * v3-only options are seeded by the {@see Activator}.
	 *
	 * Options intentionally NOT migrated (v2 stored them but v3
	 * drops them as no longer needed): `secondary_title_input_field_position`,
	 * `secondary_title_use_in_permalinks`, `secondary_title_permalinks_position`,
	 * `secondary_title_column_position`, `secondary_title_feed_auto_show`,
	 * `secondary_title_feed_title_format`, `secondary_title_include_in_search`,
	 * `secondary_title_show_donation_notice`.
	 *
	 * @var array<int, string>
	 */
	private const V2_OPTIONS = array(
		'secondary_title_post_types',
		'secondary_title_categories',
		'secondary_title_post_ids',
		'secondary_title_auto_show',
		'secondary_title_title_format',
		'secondary_title_only_show_in_main_post',
	);

	/**
	 * Repository.
	 *
	 * @var SettingsRepository
	 */ private readonly SettingsRepository $repository;

	/**
	 * Defaults.
	 *
	 * @var SettingsDefaults
	 */ private readonly SettingsDefaults $defaults;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $repository Options repository.
	 * @param SettingsDefaults   $defaults   Default values.
	 */
	public function __construct( SettingsRepository $repository, SettingsDefaults $defaults ) {
		$this->repository = $repository;
		$this->defaults   = $defaults;
	}

	/**
	 * Registers the upgrader as a `plugins_loaded` listener.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ), 5 );
	}

	/**
	 * Runs the migration if needed.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		$current = (int) $this->repository->get( SettingsDefaults::OPTION_DB_VERSION );

		if ( $current >= self::TARGET_VERSION ) {
			return;
		}

		if ( is_multisite() ) {
			$this->migrate_network();
		} else {
			$this->migrate_site();
		}
	}

	/**
	 * Migrates the current site.
	 *
	 * @return void
	 */
	private function migrate_site(): void {
		foreach ( self::V2_OPTIONS as $key ) {
			$this->migrate_option( $key );
		}

		// Map dropped/renamed v2 options onto their v3 successors.
		$this->migrate_renamed_options();

		// Sanitize unsanitized legacy post meta (v2 block editor
		// saved raw values; escaped values are decoded at render).
		$this->sanitize_legacy_meta();

		// Seed any v3-only option that does not exist yet.
		$this->seed_defaults();
		$this->repository->set( SettingsDefaults::OPTION_DB_VERSION, self::TARGET_VERSION );
	}

	/**
	 * Copies known v2-only option keys onto their v3 equivalents when
	 * the v3 key has not been stored yet.
	 *
	 * @return void
	 */
	private function migrate_renamed_options(): void {
		$map = array(
			// v2 include_in_search → v3 show_in_search.
			'secondary_title_include_in_search' => SettingsDefaults::OPTION_SHOW_IN_SEARCH,
			// v2 feed_auto_show → v3 show_in_rss.
			'secondary_title_feed_auto_show'    => SettingsDefaults::OPTION_SHOW_IN_RSS,
		);

		foreach ( $map as $v2_key => $v3_key ) {
			$v2_value = get_option( $v2_key, null );
			if ( null === $v2_value || '' === $v2_value ) {
				continue;
			}

			$backup_key = 'v2_' . $v2_key;
			if ( false === get_option( $backup_key ) ) {
				update_option( $backup_key, $v2_value, false );
			}

			// Only seed the v3 key when it has never been set.
			if ( false === get_option( $v3_key ) ) {
				$normalized = ( 'on' === $v2_value ) ? SettingsDefaults::ON : SettingsDefaults::OFF;
				$this->repository->set( $v3_key, $normalized );
			}
		}

		// Preserve v2 column position when present (not in V2_OPTIONS).
		$column = get_option( SettingsDefaults::OPTION_COLUMN_POSITION, null );
		if ( null !== $column && '' !== $column && false === get_option( 'v2_' . SettingsDefaults::OPTION_COLUMN_POSITION ) ) {
			update_option( 'v2_' . SettingsDefaults::OPTION_COLUMN_POSITION, $column, false );
		}
	}

	/**
	 * Migrates every site of a Multisite network.
	 *
	 * @return void
	 */
	private function migrate_network(): void {
		$blog_ids = get_sites( array( 'fields' => 'ids' ) );

		foreach ( (array) $blog_ids as $blog_id ) {
			switch_to_blog( (int) $blog_id );
			$this->migrate_site();
			restore_current_blog();
		}
	}

	/**
	 * Migrates a single v2 option: backup the value, migrate it,
	 * overwrite the live key.
	 *
	 * @param string $key The v2 option key (with `secondary_title_` prefix).
	 *
	 * @return void
	 */
	private function migrate_option( string $key ): void {
		// Skip options that are not v2 keys.
		if ( ! in_array( $key, self::V2_OPTIONS, true ) ) {
			return;
		}

		$value = get_option( $key, null );

		// No value stored -> nothing to migrate.
		if ( null === $value || '' === $value ) {
			return;
		}

		// Backup to v2_<key> (forensic snapshot of the original value).
		$backup_key = 'v2_' . $key;
		if ( false === get_option( $backup_key ) ) {
			update_option( $backup_key, $value, false );
		}

		// Migrate the value. Currently a 1:1 copy; transform per-key here
		// when future migrations need it.
		$migrated = $this->transform_value( $key, $value );

		// Overwrite the live key with the migrated value.
		update_option( $key, $migrated, false );
	}

	/**
	 * Per-option transformation hook. Override in subclasses for
	 * more aggressive migrations.
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The current value.
	 *
	 * @return mixed The migrated value.
	 */
	private function transform_value( string $key, mixed $value ): mixed {
		unset( $key );
		return $value;
	}

	/**
	 * Sanitizes unsanitized legacy post meta in place.
	 *
	 * Legacy v2 values were saved HTML-escaped from the Classic
	 * Editor, but raw from the block editor (its `register_meta()`
	 * call had no `sanitize_callback`), so legacy values may contain
	 * actual markup that never went through any sanitizer.
	 *
	 * Values that contain HTML entities are skipped entirely: they
	 * are v2-escaped text and are decoded once at render time by
	 * the {@see \Thaikolja\SecondaryTitle\Renderer\Wrapper} — rewriting
	 * them here would double-encode them. Only values that contain
	 * a literal `<` (and no `&`) can be raw markup; those are run
	 * through `wp_kses_post()` once.
	 *
	 * @return void
	 */
	private function sanitize_legacy_meta(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration sweep; runs a single time before the db_version flag is stamped.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				Plugin::META_KEY
			)
		);

		if ( ! is_array( $rows ) || array() === $rows ) {
			return;
		}

		foreach ( $rows as $row ) {
			$value = (string) $row->meta_value;

			if ( '' === $value ) {
				continue;
			}

			// Escaped legacy values must never be rewritten: kses
			// would double-encode them. The renderer decodes them.
			if ( str_contains( $value, '&' ) ) {
				continue;
			}

			// Only values with literal markup can be unsanitized.
			if ( ! str_contains( $value, '<' ) ) {
				continue;
			}

			$cleaned = wp_kses_post( $value );

			if ( $cleaned !== $value ) {
				update_metadata_by_mid( 'post', (int) $row->meta_id, $cleaned );
			}
		}
	}

	/**
	 * Seeds any v3-only option that does not exist yet.
	 *
	 * @return void
	 */
	private function seed_defaults(): void {
		foreach ( $this->defaults->all() as $key => $default ) {
			if ( false === get_option( $key ) ) {
				$this->repository->set( $key, $default );
			}
		}
	}
}
