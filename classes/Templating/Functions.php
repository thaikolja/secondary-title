<?php
/**
 * Registers WordPress-aware Twig functions.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Templating;

use Twig\Environment;
use Twig\TwigFunction;
use Thaikolja\SecondaryTitle\Plugin;

/**
 * Adds WordPress helpers to a Twig environment.
 *
 * @since 3.0.0
 */
final class Functions {

	/**
	 * Twig.
	 *
	 * @var Environment
	 */ private readonly Environment $twig;

	/**
	 * Constructor.
	 *
	 * @param Environment $twig The Twig environment to register the functions on.
	 */
	public function __construct( Environment $twig ) {
		$this->twig = $twig;
	}

	/**
	 * Registers every function on the Twig environment.
	 *
	 * @return void
	 */
	public function register(): void {
		// URLs.
		$this->add( 'admin_url', array( $this, 'admin_url' ) );
		$this->add( 'home_url', array( $this, 'home_url' ) );
		$this->add( 'plugin_url', array( $this, 'plugin_url' ) );

		// Translation.
		$this->add( '__', fn ( string $text ): string => __( $text, 'secondary-title' ) );
		$this->add(
			'_e',
			function ( string $text ): void {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo esc_html( __( $text, 'secondary-title' ) );
			},
			array( 'is_safe' => array( 'html' ) )
		);
		$this->add( 'esc_html__', fn ( string $text ): string => esc_html__( $text, 'secondary-title' ) );
		$this->add(
			'esc_html_e',
			function ( string $text ): void {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo esc_html( __( $text, 'secondary-title' ) );
			},
			array( 'is_safe' => array( 'html' ) )
		);
		$this->add( 'esc_attr__', fn ( string $text ): string => esc_attr__( $text, 'secondary-title' ) );

		// Escaping.
		$this->add( 'esc_html', 'esc_html' );
		$this->add( 'esc_attr', 'esc_attr' );
		$this->add( 'esc_url', 'esc_url' );
		$this->add( 'esc_textarea', 'esc_textarea' );

		// Settings API helpers. These output their HTML directly and
		// are therefore marked is_safe => html.
		$this->add(
			'wp_nonce_field',
			static function ( string $action, string $name = '_wpnonce' ): string {
				return wp_nonce_field( $action, $name, true, false );
			},
			array( 'is_safe' => array( 'html' ) )
		);

		$this->add(
			'settings_fields',
			static function ( string $group ): string {
				ob_start();
				settings_fields( $group );
				return (string) ob_get_clean();
			},
			array( 'is_safe' => array( 'html' ) )
		);

		$this->add(
			'do_settings_sections',
			static function ( string $page ): string {
				ob_start();
				do_settings_sections( $page );
				return (string) ob_get_clean();
			},
			array( 'is_safe' => array( 'html' ) )
		);

		$this->add(
			'submit_button',
			static function ( ?string $text = null, ?string $type = 'primary' ): string {
				return get_submit_button( $text, $type );
			},
			array( 'is_safe' => array( 'html' ) )
		);

		$this->add(
			'settings_errors',
			static function ( string $group ): string {
				ob_start();
				settings_errors( $group );
				return (string) ob_get_clean();
			},
			array( 'is_safe' => array( 'html' ) )
		);

		$this->add(
			'selected',
			static fn ( mixed $selected, mixed $current, bool $is_echo = false ): string => selected( $selected, $current, $is_echo ),
			array( 'is_safe' => array( 'html' ) )
		);

		$this->add(
			'checked',
			static fn ( mixed $checked, mixed $current = true ): string => checked( $checked, $current, false ),
			array( 'is_safe' => array( 'html' ) )
		);

		// Misc.
		$this->add( 'post_type_archive_title', static fn (): string => post_type_archive_title( '', false ) );
		$this->add( 'wp_create_nonce', 'wp_create_nonce' );

		// Plugin-specific helpers.
		$this->add( 'plugin_page_slug', static fn (): string => Plugin::PAGE_SLUG );
	}

	/**
	 * Registers a single Twig function.
	 *
	 * @param string                                     $name     The function name (Twig side).
	 * @param callable|array{0:object,1:string}|string   $callback The PHP callable.
	 * @param array{is_safe?: array<int,string>}|array{} $options  Optional flags. Recognized:
	 *                                                                - is_safe: array of output types
	 *                                                                  the function is considered safe for.
	 *
	 * @return void
	 */
	private function add( string $name, callable|array|string $callback, array $options = array() ): void {
		$function = new TwigFunction( $name, $callback, $options );
		$this->twig->addFunction( $function );
	}

	/**
	 * Wraps `admin_url()` so it can be used as a callable.
	 *
	 * @param string $path Optional path appended to the admin URL.
	 * @param string $scheme Optional scheme.
	 *
	 * @return string
	 */
	public function admin_url( string $path = '', string $scheme = 'admin' ): string {
		return admin_url( $path, $scheme );
	}

	/**
	 * Wraps `home_url()` so it can be used as a callable.
	 *
	 * @param string $path    Optional path.
	 * @param string $scheme  Optional scheme.
	 *
	 * @return string
	 */
	public function home_url( string $path = '', ?string $scheme = null ): string {
		return home_url( $path, $scheme );
	}

	/**
	 * Wraps `plugin_dir_url()` for the current plugin.
	 *
	 * @param string $path Optional path relative to the plugin root.
	 *
	 * @return string
	 */
	public function plugin_url( string $path = '' ): string {
		return plugin_dir_url( SECONDARY_TITLE_PATH . 'secondary-title.php' ) . ltrim( $path, '/' );
	}
}
