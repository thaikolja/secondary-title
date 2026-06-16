<?php
/**
 * `[secondary_title]` shortcode handler.
 *
 * Renders the secondary title for the current post (or a
 * `post_id` attribute) inside any post/page content. Two
 * attributes are recognized for back-compat with v2.x.x:
 *
 *   - post_id:    ID of the post to read from.
 *   - allow_html: whether to render the stored value as HTML
 *                 (true) or plain text (false, default).
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;

/**
 * Shortcode handler.
 *
 * @since 3.0.0
 */
final class Shortcode {

	/**
	 * The shortcode tag (stable across versions).
	 *
	 * @var string
	 */
	public const TAG = 'secondary_title';

	/**
	 * Filter applied to the shortcode output before it is returned.
	 *
	 * Back-compat: addon plugins hook this to rewrite the output.
	 *
	 * @var string
	 */
	public const FILTER = 'secondary_title_shortcode';

	/**
	 * @var MetaRepository
	 */
	private readonly MetaRepository $meta_repository;

	/**
	 * @param MetaRepository $meta_repository Meta read access.
	 */
	public function __construct( MetaRepository $meta_repository ) {
		$this->meta_repository = $meta_repository;
	}

	/**
	 * Registers the shortcode.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::TAG, [ $this, 'render' ] );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML (or empty string).
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'post_id'    => 0,
				'allow_html' => 'false',
			],
			(array) $atts,
			self::TAG
		);

		$post_id = (int) $atts['post_id'];
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return '';
		}

		$raw = $this->meta_repository->get_raw( $post_id );

		if ( '' === $raw ) {
			return '';
		}

		/**
		 * For HTML rendering: the value is already sanitized via
		 * {@see \Thaikolja\SecondaryTitle\Meta\Sanitizer} on
		 * save, so we can emit it as-is when `allow_html` is true.
		 * For plain-text: strip tags, then re-escape.
		 */
		if ( 'true' === (string) $atts['allow_html'] ) {
			$output = $raw;
		} else {
			$output = esc_html( wp_strip_all_tags( $raw ) );
		}

		/**
		 * Filters the shortcode output. Addons can use this to
		 * rewrite the rendered HTML.
		 *
		 * @param string $output The current output.
		 */
		return (string) apply_filters( self::FILTER, $output );
	}
}
