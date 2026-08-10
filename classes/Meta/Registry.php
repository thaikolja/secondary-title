<?php
/**
 * Registers the secondary title post meta with WordPress.
 *
 * Called on the `init` action (via {@see self::register()}) so the
 * meta is exposed to:
 *   - The Classic Editor's "Custom Fields" meta box (hidden by the
 *     leading underscore, but available via the Custom Fields panel).
 *   - The block editor's REST API (show_in_rest => true).
 *   - The WP REST API for headless front-ends.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Meta;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Post meta registry.
 *
 * @since 3.0.0
 */
final class Registry {

	/**
	 * Hook used to register the meta.
	 *
	 * @var string
	 */
	private const HOOK = 'init';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings_repository Settings access.
	 */
	public function __construct( SettingsRepository $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	/**
	 * Registers the WordPress hook that performs the actual
	 * `register_meta()` call.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'register_meta' ) );
	}

	/**
	 * Performs the `register_meta()` call.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$show_in_rest = SettingsDefaults::ON === $this->settings_repository->get( SettingsDefaults::OPTION_SHOW_IN_REST );

		/**
		 * `auth_callback` returns true when the current user is
		 * allowed to edit the post the meta is attached to. The
		 * single-underscore prefix on the key hides the meta from
		 * the standard "Custom Fields" UI in the post editor.
		 */
		register_meta(
			'post',
			Plugin::META_KEY,
			array(
				'object_subtype'    => '', // Applies to every post type.
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => $show_in_rest
					? array(
						'schema' => array(
							'type' => 'string',
						),
					)
					: false,
				'sanitize_callback' => static function ( $value ): string {
					// Route through the full MetaSanitizer which applies
					// wp_kses_post() — same path as the Classic Editor save.
					return ( new Sanitizer() )->sanitize( $value );
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
					unset( $allowed, $meta_key );
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);
	}
}
