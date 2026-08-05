<?php
/**
 * Adds a "Secondary title" column to the post-list table.
 *
 * Back-compat:
 *   - Column slug: `secondary_title` (matches v2.x.x).
 *   - Position is configurable (left/right of the title column).
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Admin;

use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;

/**
 * Post-list column.
 *
 * @since 3.0.0
 */
final class Columns {

	/**
	 * The column slug. Stable across versions.
	 *
	 * @var string
	 */
	public const COLUMN_SLUG = 'secondary_title';

	/**
	 * Column position options. Match v2.x.x strings.
	 *
	 * @var string
	 */
	public const POSITION_LEFT  = 'left';
	public const POSITION_RIGHT = 'right';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */ private readonly SettingsRepository $settings_repository;

	/**
	 * Meta repository.
	 *
	 * @var MetaRepository
	 */ private readonly MetaRepository $meta_repository;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings_repository Settings repository.
	 * @param MetaRepository     $meta_repository     Meta read access.
	 */
	public function __construct(
		SettingsRepository $settings_repository,
		MetaRepository $meta_repository
	) {
		$this->settings_repository = $settings_repository;
		$this->meta_repository     = $meta_repository;
	}

	/**
	 * Registers the WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_columns' ) );
	}

	/**
	 * `admin_init` callback. Registers the column on every public
	 * post type the plugin is enabled for.
	 *
	 * @return void
	 */
	public function register_columns(): void {
		$enabled = $this->enabled_post_types();

		if ( array() === $enabled ) {
			return;
		}

		foreach ( $enabled as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
		}
	}

	/**
	 * `manage_{$post_type}_posts_columns` callback. Inserts the
	 * secondary title column at the configured position.
	 *
	 * @param array<string, string> $columns Existing columns.
	 *
	 * @return array<string, string> The augmented columns.
	 */
	public function add_column( array $columns ): array {
		$position = (string) $this->settings_repository->get( SettingsDefaults::OPTION_COLUMN_POSITION, 'right' );
		// Fall back to "right" if an unknown value is stored.
		$position = self::POSITION_LEFT === $position ? self::POSITION_LEFT : self::POSITION_RIGHT;

		$out = array();

		$inserted = false;
		foreach ( $columns as $slug => $label ) {
			if ( 'title' === $slug ) {
				if ( self::POSITION_LEFT === $position ) {
					$out[ self::COLUMN_SLUG ] = __( 'Secondary title', 'secondary-title' );
					$inserted                 = true;
				}
				$out[ $slug ] = $label;
				if ( self::POSITION_RIGHT === $position ) {
					$out[ self::COLUMN_SLUG ] = __( 'Secondary title', 'secondary-title' );
					$inserted                 = true;
				}
				continue;
			}
			$out[ $slug ] = $label;
		}

		if ( ! $inserted ) {
			$out[ self::COLUMN_SLUG ] = __( 'Secondary title', 'secondary-title' );
		}

		return $out;
	}

	/**
	 * `manage_{$post_type}_posts_custom_column` callback. Renders
	 * the column content for the current row.
	 *
	 * @param string $column  The current column slug.
	 * @param int    $post_id The current post ID.
	 *
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( self::COLUMN_SLUG !== $column ) {
			return;
		}

		$value = $this->meta_repository->get_raw( $post_id );

		if ( '' === $value ) {
			echo '&mdash;';
			return;
		}

		// Output the sanitized value. Stored values are
		// entity-encoded, so decode once before escaping; tags are
		// stripped to keep the column safe at-a-glance.
		echo esc_html( wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}

	/**
	 * Returns the post types the secondary title is enabled for.
	 *
	 * @return array<int, string>
	 */
	private function enabled_post_types(): array {
		$enabled = (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_TYPES, array() );

		if ( array() !== $enabled ) {
			return array_values( array_filter( $enabled, 'post_type_exists' ) );
		}

		$public = get_post_types( array( 'public' => true ) );
		return array_values( array_filter( $public, static fn ( string $t ): bool => 'attachment' !== $t ) );
	}
}
