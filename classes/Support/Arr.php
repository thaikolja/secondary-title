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
	 * Returns the value at $key in $array, or $default if the key is missing.
	 *
	 * @param array<mixed> $array   The array to read from.
	 * @param string|int   $key     The key to look up.
	 * @param mixed        $default Value returned when the key is missing.
	 *
	 * @return mixed
	 */
	public static function get( array $array, string|int $key, mixed $default = null ): mixed {
		return array_key_exists( $key, $array ) ? $array[ $key ] : $default;
	}

	/**
	 * Returns true when $value is in $array (strict comparison).
	 *
	 * @param array<mixed> $array The haystack.
	 * @param mixed        $value The needle.
	 *
	 * @return bool
	 */
	public static function contains( array $array, mixed $value ): bool {
		return in_array( $value, $array, true );
	}

	/**
	 * Returns a new array that only contains the keys in $keys.
	 *
	 * @param array<mixed>  $array The source array.
	 * @param array<string> $keys  The keys to keep.
	 *
	 * @return array<mixed>
	 */
	public static function only( array $array, array $keys ): array {
		return array_intersect_key( $array, array_flip( $keys ) );
	}

	/**
	 * Returns a new array that excludes the keys in $keys.
	 *
	 * @param array<mixed>  $array The source array.
	 * @param array<string> $keys  The keys to drop.
	 *
	 * @return array<mixed>
	 */
	public static function except( array $array, array $keys ): array {
		return array_diff_key( $array, array_flip( $keys ) );
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
			return [];
		}

		if ( is_string( $value ) ) {
			return '' === $value ? [] : [ $value ];
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$out = [];

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
			return [];
		}

		$cleaned = (string) preg_replace( '/[^0-9,]/', '', $value );

		if ( '' === $cleaned ) {
			return [];
		}

		$parts = array_map( 'intval', array_filter( explode( ',', $cleaned ), 'strlen' ) );

		return array_values( array_filter( $parts, static fn ( int $id ): bool => $id > 0 ) );
	}
}
