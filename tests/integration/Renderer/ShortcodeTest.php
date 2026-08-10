<?php
/**
 * Integration tests for the [secondary_title] shortcode.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Renderer;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Shortcode integration tests.
 *
 * @since 3.0.0
 */
final class ShortcodeTest extends \WP_UnitTestCase {

	/**
	 * Renders the secondary title for the current post.
	 *
	 * @return void
	 */
	public function test_shortcode_renders_and_empty_primary(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'Primary Title',
				'post_content' => '[secondary_title]',
			)
		);
		update_post_meta( $post_id, Plugin::META_KEY, 'Side' );

		$this->go_to( get_permalink( $post_id ) );
		$content = apply_filters( 'the_content', get_post( $post_id )->post_content );
		$this->assertStringContainsString( 'Side', $content );

		// Empty meta + primary fallback.
		delete_post_meta( $post_id, Plugin::META_KEY );
		update_option( SettingsDefaults::OPTION_EMPTY_BEHAVIOUR, SettingsDefaults::EMPTY_PRIMARY );
		$output = do_shortcode( '[secondary_title post_id="' . $post_id . '"]' );
		$this->assertSame( 'Primary Title', $output );

		update_option( SettingsDefaults::OPTION_EMPTY_BEHAVIOUR, SettingsDefaults::EMPTY_HIDE );
		$this->assertSame( '', do_shortcode( '[secondary_title post_id="' . $post_id . '"]' ) );
	}
}
