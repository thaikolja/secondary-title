<?php
/**
 * Sanitizes a raw secondary-title value before storage.
 *
 * Wraps `wp_kses_post()` so the secondary title may include the
 * same kind of limited HTML (e.g. `<span style="...">...</span>`)
 * that the title format allows. The single-underscore meta key
 * hides the value from the standard Custom Fields UI, but the
 * value itself may still be rendered to logged-in users.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Meta;

/**
 * Post meta sanitizer.
 *
 * @since 3.0.0
 */
final class Sanitizer {

	/**
	 * Filter: change the allowed HTML allow-list for the
	 * secondary title value. Defaults to whatever `wp_kses_post`
	 * accepts.
	 *
	 * @var string
	 */
	public const FILTER_ALLOWED_TAGS = 'secondary_title_allowed_tags';

	/**
	 * Returns a sanitized secondary-title value.
	 *
	 * @param mixed $value The raw value (string, or anything that
	 *                     can be cast to string).
	 *
	 * @return string Empty string if input is empty; sanitized
	 *                HTML otherwise.
	 */
	public function sanitize( mixed $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$raw = (string) $value;

		if ( '' === trim( $raw ) ) {
			return '';
		}

		// Use wp_unslash to undo WordPress's automatic slashes on
		// $_POST data; then sanitize for storage.
		$unslashed = wp_unslash( $raw );

		/**
		 * Filters the allow-list of HTML tags/attributes for the
		 * secondary title. Same hook as the title format, for
		 * consistency.
		 */
		$filter = static function ( array $allowed ): array {
			return (array) apply_filters( self::FILTER_ALLOWED_TAGS, $allowed );
		};
		add_filter( 'wp_kses_allowed_html', $filter, 10, 1 );

		$cleaned = wp_kses_post( $unslashed );

		remove_filter( 'wp_kses_allowed_html', $filter, 10 );

		return (string) $cleaned;
	}
}
