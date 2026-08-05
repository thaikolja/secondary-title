<?php
/**
 * Wires the plugin's options into the WordPress Settings API.
 *
 * Registers every default-managed option via `register_setting()`,
 * pointing each at the matching sanitize callback in
 * {@see \Thaikolja\SecondaryTitle\Settings\Sanitizer}.
 *
 * It also registers the settings section that the page template
 * will render.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Settings;

use Thaikolja\SecondaryTitle\Plugin;

/**
 * Settings manager.
 *
 * @since 3.0.0
 */
final class Manager {

	/**
	 * Sanitizer.
	 *
	 * @var Sanitizer
	 */ private readonly Sanitizer $sanitizer;

	/**
	 * Defaults.
	 *
	 * @var Defaults
	 */ private readonly Defaults $defaults;

	/**
	 * Constructor.
	 *
	 * @param Sanitizer $sanitizer The per-option sanitizer.
	 */
	public function __construct( Sanitizer $sanitizer ) {
		$this->sanitizer = $sanitizer;
		$this->defaults  = new Defaults();
	}

	/**
	 * Registers every option + the section on `admin_init`.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * `admin_init` callback. Iterates the defaults, registers each
	 * option with its matching sanitize callback, then adds the
	 * single settings section the page template references.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		foreach ( $this->defaults->all() as $key => $fallback ) {
			$this->register_one( $key, $fallback );
		}
	}

	/**
	 * Registers a single option with the Settings API.
	 *
	 * @param string $key     The option key.
	 * @param mixed  $fallback The default value to register.
	 *
	 * @return void
	 */
	private function register_one( string $key, mixed $fallback ): void {
		/**
		 * `wp_unslash()` is applied by the Settings API itself, but
		 * we also call it inside the sanitizer as a defense in depth
		 * (the function is filterable and addons may change the
		 * behavior).
		 */
		register_setting(
			Plugin::OPTION_GROUP,
			$key,
			array(
				'type'              => $this->type_for( $key ),
				'sanitize_callback' => function ( mixed $value ) use ( $key ): mixed {
					return $this->sanitizer->sanitize( $key, $value );
				},
				'default'           => $fallback,
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Maps an option key to its Settings API `type` argument.
	 *
	 * The Settings API uses these strings to coerce values and to
	 * display the field in the form (when `do_settings_fields`
	 * would be used). We use string for everything; type-specific
	 * coercion is done in the sanitizer.
	 *
	 * @param string $key The option key.
	 *
	 * @return string 'string' | 'array' | 'integer' | 'boolean'
	 */
	private function type_for( string $key ): string {
		return match ( $key ) {
			Defaults::OPTION_POST_TYPES,
			Defaults::OPTION_CATEGORIES,
			Defaults::OPTION_POST_IDS => 'array',
			Defaults::OPTION_DB_VERSION => 'integer',
			default                   => 'string',
		};
	}
}
