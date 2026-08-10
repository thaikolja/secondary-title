<?php
/**
 * Integration tests for Classic Editor meta box save path.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Editor;

use Thaikolja\SecondaryTitle\Editor\MetaBox;
use Thaikolja\SecondaryTitle\Plugin;

/**
 * MetaBox save integration tests.
 *
 * @since 3.0.0
 */
final class MetaBoxSaveTest extends \WP_UnitTestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_save_keeps_limited_html_without_sanitize_text_field(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );
		$post    = get_post( $post_id );

		$nonce                          = wp_create_nonce( MetaBox::NONCE_ACTION );
		$_POST[ MetaBox::FIELD_NAME ]   = '<em>Secondary</em>';
		$_POST[ MetaBox::NONCE_ACTION ] = $nonce;

		Plugin::instance()->classic_meta_box->save( $post_id, $post );

		$raw = get_post_meta( $post_id, Plugin::META_KEY, true );
		$this->assertStringContainsString( '<em>', (string) $raw );
		$this->assertStringContainsString( 'Secondary', (string) $raw );

		unset( $_POST[ MetaBox::FIELD_NAME ], $_POST[ MetaBox::NONCE_ACTION ] );
	}
}
