<?php
/**
 * Typed read/write of plugin options.
 *
 * The Repository is the single point of access for the rest of the
 * plugin. It never calls `get_option()` / `update_option()` directly
 * elsewhere; everything goes through here so we can:
 *   - always return the registered default for missing keys,
 *   - provide a typed `set()` that respects the default schema,
 *   - expose a single `all()` for templates and the API facade.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Settings;

/**
 * Typed options repository.
 *
 * @since 3.0.0
 */
final class Repository {

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
	 * Returns the value of an option.
	 *
	 * When the option is missing in the database, the default value
	 * (from {@see Defaults::all()}) is returned. This way callers
	 * never have to deal with `false` or null "missing" values.
	 *
	 * @param string $key     The option key.
	 * @param mixed  $default Optional override for the default value.
	 *                        If null, the {@see Defaults} value is used.
	 *
	 * @return mixed The stored value, the explicit default, or the
	 *               {@see Defaults} value (in that order).
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$value = get_option( $key, null );

		if ( null !== $value ) {
			return $value;
		}

		if ( null !== $default ) {
			return $default;
		}

		return $this->defaults->get( $key );
	}

	/**
	 * Stores a value in the database.
	 *
	 * @param string $key   The option key.
	 * @param mixed  $value The value to store.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, mixed $value ): bool {
		return update_option( $key, $value );
	}

	/**
	 * Deletes an option.
	 *
	 * @param string $key The option key.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key ): bool {
		return delete_option( $key );
	}

	/**
	 * Returns every option managed by the plugin, mapped to its
	 * current value (or the default if not set).
	 *
	 * Used by the settings page template and the {@see \Thaikolja\SecondaryTitle\Api}
	 * facade.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$out = [];

		foreach ( $this->defaults->all() as $key => $default ) {
			$out[ $key ] = $this->get( $key, $default );
		}

		return $out;
	}

	/**
	 * Persists a full set of options in a single batch.
	 *
	 * Only keys that exist in {@see Defaults::all()} are accepted.
	 * Any unknown key is silently dropped.
	 *
	 * @param array<string, mixed> $values The new values.
	 *
	 * @return array<int, string> The list of keys that failed to persist.
	 */
	public function set_many( array $values ): array {
		$failed = [];

		foreach ( $values as $key => $value ) {
			if ( ! array_key_exists( $key, $this->defaults->all() ) ) {
				continue;
			}

			if ( ! $this->set( $key, $value ) ) {
				$failed[] = $key;
			}
		}

		return $failed;
	}
}
