<?php
/**
 * Registers WordPress-aware Twig filters.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Templating;

use Twig\Environment;
use Twig\TwigFilter;
use Thaikolja\SecondaryTitle\Renderer\Placeholder;

/**
 * Adds WordPress escaping/translation filters to a Twig environment.
 *
 * @since 3.0.0
 */
final class Filters {

	/**
	 * Twig.
	 *
	 * @var Environment
	 */ private readonly Environment $twig;

	/**
	 * Constructor.
	 *
	 * @param Environment $twig The Twig environment to register the filters on.
	 */
	public function __construct( Environment $twig ) {
		$this->twig = $twig;
	}

	/**
	 * Registers every filter on the Twig environment.
	 *
	 * @return void
	 */
	public function register(): void {
		// Translation.
		$this->add( 'translate', fn ( string $text ): string => __( $text, 'secondary-title' ), array( 'is_safe' => array( 'html' ) ) );
		$this->add( 'translate_eschtml', fn ( string $text ): string => esc_html( __( $text, 'secondary-title' ) ) );
		$this->add( 'translate_escattr', fn ( string $text ): string => esc_attr( __( $text, 'secondary-title' ) ) );

		// Escaping (sanity aliases; twig already auto-escapes {{ }}).
		$this->add( 'esc_html', 'esc_html' );
		$this->add( 'esc_attr', 'esc_attr' );
		$this->add( 'esc_url', 'esc_url' );
		$this->add( 'esc_textarea', 'esc_textarea' );
		$this->add( 'esc_js', 'esc_js' );

		// Format the placeholders for the title format.
		$this->add(
			'format_with_placeholders',
			static function ( string $format, string $title, string $secondary_title ): string {
				return Placeholder::replace(
					$format,
					array(
						Placeholder::TITLE           => $title,
						Placeholder::SECONDARY_TITLE => $secondary_title,
					)
				);
			},
			array( 'is_safe' => array( 'html' ) )
		);
	}

	/**
	 * Registers a single Twig filter.
	 *
	 * @param string                              $name     The filter name (Twig side).
	 * @param callable|array<int|string, mixed>   $callback The PHP callable.
	 * @param array{is_safe?: array<int, string>} $options Optional flags. Recognized:
	 *                                                     - is_safe: array of output types the filter is considered safe for.
	 *
	 * @return void
	 */
	private function add( string $name, callable|array $callback, array $options = array() ): void {
		$filter = new TwigFilter( $name, $callback, $options );
		$this->twig->addFilter( $filter );
	}
}
