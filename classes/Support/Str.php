<?php
/**
 * String helper utilities.
 *
 * Pure functions, no WordPress dependencies, fully testable.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Support;

use InvalidArgumentException;

/**
 * String helper utilities.
 *
 * @since 3.0.0
 */
final class Str {

	/**
	 * Returns the substring of $haystack that follows the first occurrence
	 * of $needle. If $needle is not found, the full $haystack is returned.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The string to search for.
	 *
	 * @return string The substring after the first occurrence of $needle,
	 *                or the full $haystack if not found.
	 */
	public static function after( string $haystack, string $needle ): string {
		if ( '' === $needle ) {
			return $haystack;
		}

		$position = strpos( $haystack, $needle );

		if ( false === $position ) {
			return $haystack;
		}

		return substr( $haystack, $position + strlen( $needle ) );
	}

	/**
	 * Returns the substring of $haystack that precedes the first occurrence
	 * of $needle. If $needle is not found, the full $haystack is returned.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The string to search for.
	 *
	 * @return string The substring before the first occurrence of $needle,
	 *                or the full $haystack if not found.
	 */
	public static function before( string $haystack, string $needle ): string {
		if ( '' === $needle ) {
			return $haystack;
		}

		$position = strpos( $haystack, $needle );

		if ( false === $position ) {
			return $haystack;
		}

		return substr( $haystack, 0, $position );
	}

	/**
	 * Truncates $value to $limit characters, appending $end if truncation
	 * occurred. If $value is shorter than $limit, the original is returned
	 * unchanged.
	 *
	 * Multibyte-safe: uses `mb_strimwidth` so accented and non-ASCII
	 * characters are not split in half.
	 *
	 * @param string      $value The string to truncate.
	 * @param int<0, max> $limit Maximum number of characters to keep.
	 * @param string      $end   The suffix appended after truncation. Default: ''.
	 *
	 * @return string The truncated string.
	 *
	 * @throws InvalidArgumentException If $limit is negative.
	 */
	public static function limit( string $value, int $limit, string $end = '' ): string {
		if ( 0 > $limit ) {
			throw new InvalidArgumentException( 'Limit must be non-negative.' );
		}

		if ( mb_strwidth( $value ) <= $limit ) {
			return $value;
		}

		return mb_strimwidth( $value, 0, $limit, $end );
	}

	/**
	 * Returns true when $value is null or an empty string.
	 *
	 * @param string|null $value The value to test.
	 *
	 * @return bool
	 */
	public static function is_blank( ?string $value ): bool {
		return null === $value || '' === $value;
	}

	/**
	 * Returns true when $value contains the $needle substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to look for.
	 *
	 * @return bool
	 */
	public static function contains( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		return false !== strpos( $haystack, $needle );
	}
}
