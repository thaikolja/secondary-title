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
use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Renderer\Placeholder;

/**
 * Adds WordPress escaping/translation filters to a Twig environment.
 *
 * @since 3.0.0
 */
final class Filters {

	/**
	 * @var Environment
	 */
	private readonly Environment $twig;

	/**
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
		$domain = Plugin::TEXT_DOMAIN;

		// Translation
		$this->add( 'translate',     fn ( string $text ): string => __( $text, $domain ), [ 'is_safe' => [ 'html' ] ] );
		$this->add( 'translate_eschtml', fn ( string $text ): string => esc_html( __( $text, $domain ) ) );
		$this->add( 'translate_escattr', fn ( string $text ): string => esc_attr( __( $text, $domain ) ) );

		// Escaping (sanity aliases; twig already auto-escapes {{ }})
		$this->add( 'esc_html',    'esc_html' );
		$this->add( 'esc_attr',    'esc_attr' );
		$this->add( 'esc_url',     'esc_url' );
		$this->add( 'esc_textarea', 'esc_textarea' );
		$this->add( 'esc_js',      'esc_js' );

		// Format the placeholders for the title format.
		$this->add(
			'format_with_placeholders',
			static function ( string $format, string $title, string $secondary_title ): string {
				return Placeholder::replace(
					$format,
					[
						Placeholder::TITLE           => $title,
						Placeholder::SECONDARY_TITLE => $secondary_title,
					]
				);
			},
			[ 'is_safe' => [ 'html' ] ]
		);
	}

	/**
	 * Registers a single Twig filter.
	 *
	 * @param string         $name     The filter name (Twig side).
	 * @param callable|array $callable The PHP callable.
	 * @param array          $options  Optional flags. Recognized:
	 *                                 - is_safe: array of output types the filter is considered safe for.
	 *
	 * @return void
	 */
	private function add( string $name, callable|array $callable, array $options = [] ): void {
		$filter = new TwigFilter( $name, $callable, $options );
		$this->twig->addFilter( $filter );
	}
}
