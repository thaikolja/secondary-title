<?php
/**
 * Per-option sanitization callbacks.
 *
 * Each public method in this class corresponds to one option. The
 * Settings Manager wires these to `register_setting()` so the
 * WordPress Settings API calls the right callback when the form
 * is submitted.
 *
 * Every callback:
 *   - Receives the raw $_POST value (mixed).
 *   - Returns a value safe to store in the database.
 *   - Never throws — invalid input is coerced to the option's
 *     default.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Settings;

use Thaikolja\SecondaryTitle\Support\Arr;

/**
 * Settings sanitizer.
 *
 * @since 3.0.0
 */
final class Sanitizer {

	/**
	 * @var Defaults
	 */
	private readonly Defaults $defaults;

	/**
	 * @param Defaults $defaults The defaults source.
	 */
	public function __construct( Defaults $defaults ) {
		$this->defaults = $defaults;
	}

	/**
	 * Dispatches sanitization to the right callback based on the
	 * option key.
	 *
	 * Used by the Settings Manager when only one callback is
	 * available. Prefer registering per-option callbacks via
	 * `register_setting()`.
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The raw value to sanitize.
	 *
	 * @return mixed
	 */
	public function sanitize( string $key, mixed $value ): mixed {
		$method = $this->method_for( $key );

		if ( null === $method ) {
			return $value;
		}

		return $this->{$method}( $value );
	}

	/**
	 * Maps an option key to the method that sanitizes it.
	 *
	 * @param string $key The option key.
	 *
	 * @return string|null The method name, or null for unknown keys.
	 */
	private function method_for( string $key ): ?string {
		return match ( $key ) {
			Defaults::OPTION_POST_TYPES             => 'sanitize_post_types',
			Defaults::OPTION_CATEGORIES             => 'sanitize_categories',
			Defaults::OPTION_POST_IDS               => 'sanitize_post_ids',
			Defaults::OPTION_AUTO_SHOW              => 'sanitize_on_off',
			Defaults::OPTION_TITLE_FORMAT           => 'sanitize_title_format',
			Defaults::OPTION_ONLY_SHOW_IN_MAIN_POST => 'sanitize_on_off',
			Defaults::OPTION_COLUMN_POSITION       => 'sanitize_column_position',
			Defaults::OPTION_DB_VERSION             => 'sanitize_int',
			default                                  => null,
		};
	}

	/**
	 * Sanitizes the post-types option. Each value must be a valid
	 * post-type slug (lowercase letters, digits, underscores, hyphens).
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_post_types( mixed $value ): array {
		$list = Arr::string_list( $value );

		return array_values( array_filter( array_map( 'sanitize_key', $list ) ) );
	}

	/**
	 * Sanitizes the categories option. Each value must be a positive
	 * integer (term ID).
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return array<int, int>
	 */
	public function sanitize_categories( mixed $value ): array {
		$list = Arr::string_list( $value );

		$ids = array_map( 'absint', $list );

		return array_values( array_filter( $ids, static fn ( int $id ): bool => $id > 0 ) );
	}

	/**
	 * Sanitizes the post-IDs option. Accepts a CSV string or an
	 * array of strings. Non-numeric characters are stripped.
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return array<int, int>
	 */
	public function sanitize_post_ids( mixed $value ): array {
		if ( is_array( $value ) ) {
			$value = implode( ',', Arr::string_list( $value ) );
		}

		return Arr::positive_int_list( (string) $value );
	}

	/**
	 * Sanitizes an on/off toggle. Anything that is not exactly 'on'
	 * is coerced to 'off'.
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return string Either 'on' or 'off'.
	 */
	public function sanitize_on_off( mixed $value ): string {
		return ( is_string( $value ) && 'on' === $value ) ? 'on' : 'off';
	}

	/**
	 * Sanitizes the title format. Uses `wp_kses_post()` to allow
	 * styling tags (`<span style="...">`, `<strong>`, `<em>`, ...)
	 * while stripping scripts, iframes, and event handlers.
	 *
	 * The set of allowed tags/attributes is filterable via
	 * `secondary_title_allowed_tags`.
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return string
	 */
	public function sanitize_title_format( mixed $value ): string {
		$raw    = (string) $value;
		$filter = static function ( array $allowed ): array {
			/**
			 * Filters the HTML tags/attributes allowed in the title format.
			 *
			 * @param array $allowed The default allow-list (whatever `wp_kses_post` accepts).
			 *
			 * @return array
			 */
			return (array) apply_filters( 'secondary_title_allowed_tags', $allowed );
		};
		add_filter( 'wp_kses_allowed_html', $filter, 10, 1 );

		// Unslash + sanitize through wp_kses_post.
		$cleaned = wp_kses_post( wp_unslash( $raw ) );

		remove_filter( 'wp_kses_allowed_html', $filter, 10 );

		return (string) $cleaned;
	}

	/**
	 * Sanitizes an integer option.
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return int
	 */
	public function sanitize_int( mixed $value ): int {
		return (int) $value;
	}

	/**
	 * Sanitizes the column position option.
	 *
	 * @param mixed $value Raw input.
	 *
	 * @return string Either 'left' or 'right'.
	 */
	public function sanitize_column_position( mixed $value ): string {
		return in_array( $value, [ 'left', 'right' ], true ) ? $value : 'right';
	}
}
