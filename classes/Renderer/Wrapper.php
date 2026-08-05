<?php
/**
 * Output wrapper for the secondary title.
 *
 * Wraps the secondary title in a stable, themeable HTML element so
 * users can target it with CSS (e.g. `.st-title`).
 *
 * The default markup is `<span class="st-title">{title}</span>`. The
 * tag name and the class are filterable through the
 * `secondary_title_wrapper_tag` and `secondary_title_wrapper_class`
 * filters.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

/**
 * Output wrapper.
 *
 * @since 3.0.0
 */
final class Wrapper {

	/**
	 * The default HTML tag used to wrap the secondary title.
	 *
	 * @var string
	 */
	public const DEFAULT_TAG = 'span';

	/**
	 * The default CSS class applied to the wrapping element.
	 *
	 * The class intentionally uses the `st-` prefix (short for
	 * "Secondary Title") to keep style selectors concise.
	 *
	 * @var string
	 */
	public const DEFAULT_CLASS = 'st-title';

	/**
	 * Filters for the wrapper.
	 *
	 * Addons can use these to change the wrapping tag or class.
	 *
	 * @var string
	 */
	public const FILTER_TAG = 'secondary_title_wrapper_tag';

	/**
	 * Filter: the CSS class applied to the wrapper element.
	 *
	 * @var string
	 */
	public const FILTER_CLASS = 'secondary_title_wrapper_class';

	/**
	 * Wraps $title in the configured tag + class and returns the
	 * resulting HTML.
	 *
	 * If $title is empty, an empty string is returned (no empty
	 * wrappers are emitted).
	 *
	 * Stored values are entity-encoded: v2's Classic Editor saved
	 * `esc_html()` output and v3's sanitizer runs `wp_kses_post()`,
	 * which also encodes `&`. The title is therefore decoded once
	 * here, at the output boundary, so existing and new content
	 * display correctly. A single decode is safe: kses encodes
	 * ampersands on save, so encoded markup like `&lt;script&gt;`
	 * decodes to inert text, never to live tags.
	 *
	 * @param string $title The secondary title to wrap.
	 *
	 * @return string The wrapped HTML, or '' if $title is empty.
	 */
	public function wrap( string $title ): string {
		if ( '' === $title ) {
			return '';
		}

		$title = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$tag   = $this->resolve_tag();
		$class = $this->resolve_class();

		$attributes = '';

		if ( '' !== $class ) {
			$attributes = ' class="' . esc_attr( $class ) . '"';
		}

		return '<' . tag_escape( $tag ) . $attributes . '>' . $title . '</' . tag_escape( $tag ) . '>';
	}

	/**
	 * Resolves the tag name to use, applying the wrapper filter.
	 *
	 * @return string
	 */
	private function resolve_tag(): string {
		$tag = (string) apply_filters( self::FILTER_TAG, self::DEFAULT_TAG );

		return '' === $tag ? self::DEFAULT_TAG : $tag;
	}

	/**
	 * Resolves the CSS class to use, applying the wrapper filter.
	 *
	 * @return string
	 */
	private function resolve_class(): string {
		return (string) apply_filters( self::FILTER_CLASS, self::DEFAULT_CLASS );
	}
}
