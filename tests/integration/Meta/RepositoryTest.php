<?php
/**
 * Integration tests for meta repository save/load.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Meta;

use Thaikolja\SecondaryTitle\Plugin;

/**
 * Meta repository integration tests.
 *
 * @since 3.0.0
 */
final class RepositoryTest extends \WP_UnitTestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_save_preserves_limited_html(): void {
		$post_id = self::factory()->post->create();
		$repo    = Plugin::instance()->meta_repository;

		$repo->save( $post_id, '<em>Hello</em> &amp; world' );
		$raw = get_post_meta( $post_id, Plugin::META_KEY, true );

		$this->assertStringContainsString( '<em>', (string) $raw );
		$this->assertStringNotContainsString( '<script>', (string) $raw );

		$repo->save( $post_id, '<script>x</script>Safe' );
		$raw = get_post_meta( $post_id, Plugin::META_KEY, true );
		$this->assertStringNotContainsString( '<script>', (string) $raw );
		$this->assertStringContainsString( 'Safe', (string) $raw );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_empty_deletes_meta(): void {
		$post_id = self::factory()->post->create();
		$repo    = Plugin::instance()->meta_repository;

		$repo->save( $post_id, 'Temporary' );
		$this->assertTrue( $repo->has( $post_id ) );

		$repo->save( $post_id, '' );
		$this->assertFalse( $repo->has( $post_id ) );
		$this->assertSame( '', (string) get_post_meta( $post_id, Plugin::META_KEY, true ) );
	}
}
