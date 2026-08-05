<?php
/**
 * Placeholder tokens used in the title format.
 *
 * The format string can contain two placeholders that are substituted
 * with the primary and secondary titles at render time.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

/**
 * Placeholder token constants.
 *
 * Centralizes the placeholder syntax so it can be changed in one
 * place if needed (and to avoid magic strings scattered through the
 * renderer code).
 *
 * @since 3.0.0
 */
final class Placeholder {

	/**
	 * The placeholder that resolves to the post's primary title.
	 *
	 * @var string
	 */
	public const TITLE = '%title%';

	/**
	 * The placeholder that resolves to the post's secondary title.
	 *
	 * @var string
	 */
	public const SECONDARY_TITLE = '%secondary_title%';

	/**
	 * Returns every placeholder recognized by the format.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array( self::TITLE, self::SECONDARY_TITLE );
	}

	/**
	 * Replaces every placeholder in $format with the matching value
	 * from $values.
	 *
	 * @param string               $format The format string with placeholders.
	 * @param array<string,string> $values Map of placeholder (with percent signs) to value.
	 *
	 * @return string The format with all known placeholders replaced.
	 *                Unknown placeholders are left untouched.
	 */
	public static function replace( string $format, array $values ): string {
		$search  = array_keys( $values );
		$replace = array_values( $values );

		if ( array() === $search ) {
			return $format;
		}

		return str_replace( $search, $replace, $format );
	}
}
