<?php
/**
 * Read/write of the secondary title post meta.
 *
 * The Repository is the only place in the plugin that calls
 * `get_post_meta()` / `update_post_meta()` / `delete_post_meta()`.
 * Other services go through here so the meta key, sanitization
 * behavior, and the wrap-or-not decisions are all in one place.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Meta;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Renderer\Wrapper;

/**
 * Post meta repository.
 *
 * @since 3.0.0
 */
final class Repository {

	/**
	 * Sanitizer.
	 *
	 * @var Sanitizer
	 */ private readonly Sanitizer $sanitizer;

	/**
	 * Constructor.
	 *
	 * @param Sanitizer $sanitizer The sanitizer for incoming values.
	 */
	public function __construct( Sanitizer $sanitizer ) {
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Returns the secondary title for $post_id.
	 *
	 * - When $wrap is true (default), the value is wrapped via
	 *   {@see Wrapper} so it is safe to echo directly.
	 * - When $wrap is false, the raw stored value is returned
	 *   (still already sanitized on save).
	 * - Empty values return an empty string.
	 *
	 * @param int  $post_id Post ID. Defaults to the current post.
	 * @param bool $wrap    Whether to wrap the value in the output
	 *                     element. Default: true.
	 *
	 * @return string
	 */
	public function get( int $post_id = 0, bool $wrap = true ): string {
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return '';
		}

		$raw = (string) get_post_meta( $post_id, Plugin::META_KEY, true );

		if ( '' === $raw ) {
			return '';
		}

		return $wrap ? ( new Wrapper() )->wrap( $raw ) : $raw;
	}

	/**
	 * Returns the raw (unwrapped) secondary title.
	 *
	 * @param int $post_id Post ID. Defaults to the current post.
	 *
	 * @return string
	 */
	public function get_raw( int $post_id = 0 ): string {
		return $this->get( $post_id, false );
	}

	/**
	 * Returns true when $post_id has a non-empty secondary title.
	 *
	 * @param int $post_id Post ID. Defaults to the current post.
	 *
	 * @return bool
	 */
	public function has( int $post_id = 0 ): bool {
		return '' !== $this->get_raw( $post_id );
	}

	/**
	 * Persists a secondary title for $post_id.
	 *
	 * The value is sanitized via {@see Sanitizer} before storage.
	 * Empty values delete the meta row entirely (so the post meta
	 * box on the edit screen reflects an empty state).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $value   Raw value (already unslashed by WP).
	 *
	 * @return bool|int True on insert, false on failure. Returns
	 *                 meta_id on update, or false on failure.
	 */
	public function save( int $post_id, string $value ): bool|int {
		$cleaned = $this->sanitizer->sanitize( $value );

		if ( '' === $cleaned ) {
			return delete_post_meta( $post_id, Plugin::META_KEY );
		}

		return update_post_meta( $post_id, Plugin::META_KEY, $cleaned );
	}

	/**
	 * Deletes the secondary title meta for $post_id.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public function delete( int $post_id ): bool {
		return delete_post_meta( $post_id, Plugin::META_KEY );
	}
}
