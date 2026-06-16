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
 *   4. Set `secondary_title_db_version` to {@see self::TARGET_VERSION}.
 *
 * Post meta is NOT touched. `_secondary_title` post meta is
 * forward-compatible and is read/written by both v2 and v3.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Lifecycle;

use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * v2.x.x -> v3.0.0 data upgrader.
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
	private const V2_OPTIONS = [
		'secondary_title_post_types',
		'secondary_title_categories',
		'secondary_title_post_ids',
		'secondary_title_auto_show',
		'secondary_title_title_format',
		'secondary_title_only_show_in_main_post',
	];

	/**
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $repository;

	/**
	 * @var SettingsDefaults
	 */
	private readonly SettingsDefaults $defaults;

	/**
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
		add_action( 'plugins_loaded', [ $this, 'maybe_upgrade' ], 5 );
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

		// Seed any v3-only option that does not exist yet.
		$this->seed_defaults();
		$this->repository->set( SettingsDefaults::OPTION_DB_VERSION, self::TARGET_VERSION );
	}

	/**
	 * Migrates every site of a Multisite network.
	 *
	 * @return void
	 */
	private function migrate_network(): void {
		$blog_ids = get_sites( [ 'fields' => 'ids' ] );

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
