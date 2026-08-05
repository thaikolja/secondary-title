<?php
/**
 * Array helper utilities.
 *
 * Pure functions, no WordPress dependencies, fully testable.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Support;

/**
 * Array helper utilities.
 *
 * @since 3.0.0
 */
final class Arr {

	/**
	 * Returns the value at $key in $items, or $fallback if the key is missing.
	 *
	 * @param array<mixed> $items    The array to read from.
	 * @param string|int   $key      The key to look up.
	 * @param mixed        $fallback Value returned when the key is missing.
	 *
	 * @return mixed
	 */
	public static function get( array $items, string|int $key, mixed $fallback = null ): mixed {
		return array_key_exists( $key, $items ) ? $items[ $key ] : $fallback;
	}

	/**
	 * Returns true when $value is in $items (strict comparison).
	 *
	 * @param array<mixed> $items The haystack.
	 * @param mixed        $value The needle.
	 *
	 * @return bool
	 */
	public static function contains( array $items, mixed $value ): bool {
		return in_array( $value, $items, true );
	}

	/**
	 * Returns a new array that only contains the keys in $keys.
	 *
	 * @param array<mixed>  $items The source array.
	 * @param array<string> $keys  The keys to keep.
	 *
	 * @return array<mixed>
	 */
	public static function only( array $items, array $keys ): array {
		return array_intersect_key( $items, array_flip( $keys ) );
	}

	/**
	 * Returns a new array that excludes the keys in $keys.
	 *
	 * @param array<mixed>  $items The source array.
	 * @param array<string> $keys  The keys to drop.
	 *
	 * @return array<mixed>
	 */
	public static function except( array $items, array $keys ): array {
		return array_diff_key( $items, array_flip( $keys ) );
	}

	/**
	 * Coerces $value into an array of strings.
	 *
	 * - null            -> []
	 * - string          -> [$value] when non-empty, else []
	 * - array<scalar>   -> filtered to scalars, cast to string
	 * - anything else   -> []
	 *
	 * Useful for normalizing $_POST/$_GET input fields like
	 * `name="post_types[]"`, which arrive as either a single string
	 * or an array of strings depending on the form submission.
	 *
	 * @param mixed $value The raw value to coerce.
	 *
	 * @return array<int, string>
	 */
	public static function string_list( mixed $value ): array {
		if ( null === $value ) {
			return array();
		}

		if ( is_string( $value ) ) {
			return '' === $value ? array() : array( $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();

		foreach ( $value as $item ) {
			if ( is_scalar( $item ) ) {
				$out[] = (string) $item;
			}
		}

		return $out;
	}

	/**
	 * Coerces a string of comma-separated IDs into an array of
	 * positive integers.
	 *
	 * Non-numeric characters are stripped before parsing. Empty input
	 * yields an empty array.
	 *
	 * @param string $value The raw CSV string.
	 *
	 * @return array<int, int>
	 */
	public static function positive_int_list( string $value ): array {
		if ( '' === trim( $value ) ) {
			return array();
		}

		$cleaned = (string) preg_replace( '/[^0-9,]/', '', $value );

		if ( '' === $cleaned ) {
			return array();
		}

		$parts = array_map( 'intval', array_filter( explode( ',', $cleaned ), static fn ( string $part ): bool => '' !== $part ) );

		return array_values( array_filter( $parts, static fn ( int $id ): bool => $id > 0 ) );
	}
}
