<?php
/**
 * The "title format" value object.
 *
 * The format is a string with two placeholders:
 *
 *   %title%            -> the post's primary title
 *   %secondary_title%  -> the post's secondary title (already HTML-wrapped)
 *
 * The format is stored in the database as a single option. It is
 * sanitized on save (via the Settings Sanitizer) using
 * `wp_kses_post()`, which is more permissive than
 * `sanitize_text_field()` but still strips script tags and the
 * most dangerous attributes. The set of allowed tags and
 * attributes is filterable via the `secondary_title_allowed_tags`
 * filter.
 *
 * The `render()` method applies the placeholders, runs a series of
 * `secondary_title_*` filters documented in the developer guide,
 * and returns the final HTML. The title passed in is assumed to
 * already be safe for output (WordPress's `the_title` filter has
 * already escaped it). The secondary title is assumed to already
 * be wrapped in the configured HTML element (see {@see Wrapper}).
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Title format value object.
 *
 * @since 3.0.0
 */
final class Format {

	/**
	 * Filter: returns the array of allowed HTML tags / attributes
	 * for the format string. Defaults to whatever `wp_kses_post()`
	 * accepts, but addons can tighten or loosen it.
	 *
	 * @var string
	 */
	public const FILTER_ALLOWED_TAGS = 'secondary_title_allowed_tags';

	/**
	 * Filter: limit the secondary title's visible length. Receives
	 * the limit in characters (default 0 = unlimited) and the post
	 * ID, returns the new limit.
	 *
	 * @var string
	 */
	public const FILTER_MAX_LENGTH = 'secondary_title_max_length';

	/**
	 * Filter: trim excess characters from the secondary title.
	 * Receives the suffix appended after truncation (default '…'),
	 * returns the new suffix. Set to '' to disable.
	 *
	 * @var string
	 */
	public const FILTER_TRIM_EXCESS = 'secondary_title_trim_excess';

	/**
	 * Filter: whether to run `wpautop()` on the formatted output.
	 *
	 * @var string
	 */
	public const FILTER_AUTOP = 'secondary_title_autop';

	/**
	 * Filter: a final escape hatch to modify the rendered output.
	 *
	 * Receives the rendered HTML, the format string, the title, the
	 * secondary title, and the post ID. Returns the final HTML.
	 *
	 * @var string
	 */
	public const FILTER_DISPLAY = 'secondary_title_display';

	/**
	 * The Settings repository. Used to read the stored format.
	 *
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings;

	/**
	 * @param SettingsRepository $settings The settings repository.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns the configured format template, already sanitized.
	 *
	 * Reads from the Settings repository, then re-applies
	 * `wp_kses_post()` so even a manually-corrupted option row
	 * cannot inject dangerous markup at render time. Falls back to
	 * the default if no format has been stored.
	 *
	 * @return string
	 */
	public function template(): string {
		$raw = $this->settings->get( SettingsDefaults::OPTION_TITLE_FORMAT, SettingsDefaults::TITLE_FORMAT );

		/**
		 * Apply the stored format through `wp_kses_post()`. The
		 * allowed tags/attributes are filterable so addons can
		 * tighten the allow-list.
		 */
		$allowed = (array) apply_filters( self::FILTER_ALLOWED_TAGS, $this->default_allowed_tags() );

		return wp_kses( (string) $raw, $allowed );
	}

	/**
	 * Renders the format with the given values.
	 *
	 * Pipeline (each step is filterable):
	 *   1. Read & sanitize the format template.
	 *   2. Apply max-length and trim-excess to the secondary title.
	 *   3. Substitute placeholders.
	 *   4. Optionally run `wpautop()`.
	 *   5. Apply the `secondary_title_display` filter as a final
	 *      escape hatch.
	 *
	 * @param string        $title           The primary title (already escaped by WP).
	 * @param string        $secondary_title The secondary title (already wrapped, already safe).
	 * @param int           $post_id         The post ID, for context in filters.
	 *
	 * @return string
	 */
	public function render( string $title, string $secondary_title, int $post_id = 0 ): string {
		$template        = $this->template();
		$secondary_title = $this->truncate_secondary( $secondary_title, $post_id );

		$rendered = Placeholder::replace(
			$template,
			[
				Placeholder::TITLE           => $title,
				Placeholder::SECONDARY_TITLE => $secondary_title,
			]
		);

		if ( (bool) apply_filters( self::FILTER_AUTOP, false, $rendered, $post_id ) ) {
			$rendered = wpautop( $rendered );
		}

		/**
		 * Fires after the format has been rendered but before it is
		 * returned. Addons can use this to wrap the output further,
		 * add microdata, or rewrite the markup.
		 */
		return (string) apply_filters(
			self::FILTER_DISPLAY,
			$rendered,
			$template,
			$title,
			$secondary_title,
			$post_id
		);
	}

	/**
	 * Returns the default set of tags/attributes for the format
	 * sanitization. Equivalent to `wp_kses_post()`'s allow-list.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private function default_allowed_tags(): array {
		/**
		 * `wp_kses_post()` builds a complex default allow-list. We
		 * rely on it being available via the post-kses globals.
		 */
		return (array) apply_filters(
			'wp_kses_allowed_html',
			[],
			'post'
		);
	}

	/**
	 * Applies the max-length and trim-excess filters to the secondary
	 * title before it is substituted into the format.
	 *
	 * @param string $secondary_title The wrapped secondary title.
	 * @param int    $post_id         The post ID, for context.
	 *
	 * @return string
	 */
	private function truncate_secondary( string $secondary_title, int $post_id ): string {
		if ( '' === $secondary_title ) {
			return $secondary_title;
		}

		$limit = (int) apply_filters( self::FILTER_MAX_LENGTH, 0, $post_id );

		if ( $limit <= 0 ) {
			return $secondary_title;
		}

		$end = (string) apply_filters( self::FILTER_TRIM_EXCESS, '…' );

		$plain = wp_strip_all_tags( $secondary_title );
		$truncated = mb_strimwidth( $plain, 0, $limit, $end );

		// Preserve the wrapper by re-wrapping the truncated plain text.
		// We lose inline tags inside the secondary title, but that's
		// the documented behavior of the max-length filter.
		if ( preg_match( '/^<([a-z][a-z0-9]*)\b[^>]*>(.*?)<\/\\1>$/is', $secondary_title, $m ) ) {
			$tag   = $m[1];
			$attrs = '';
			if ( preg_match( '/\bclass\s*=\s*"([^"]*)"/i', $m[0], $cm ) ) {
				$attrs = ' class="' . esc_attr( $cm[1] ) . '"';
			}
			return '<' . tag_escape( $tag ) . $attrs . '>' . $truncated . '</' . tag_escape( $tag ) . '>';
		}

		return $truncated;
	}
}
