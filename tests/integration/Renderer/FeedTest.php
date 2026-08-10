<?php
/**
 * Integration tests for RSS title merge.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Renderer;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Feed integration tests.
 *
 * @since 3.0.0
 */
final class FeedTest extends \WP_UnitTestCase {

	/**
	 * Feed titles merge when the setting is on.
	 *
	 * @return void
	 */
	public function test_rss_title_merge_when_enabled(): void {
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello' ) );
		update_post_meta( $post_id, Plugin::META_KEY, 'World' );
		update_option( SettingsDefaults::OPTION_SHOW_IN_RSS, SettingsDefaults::ON );
		update_option( SettingsDefaults::OPTION_TITLE_FORMAT, '%secondary_title%: %title%' );
		update_option( SettingsDefaults::OPTION_POST_TYPES, array() );
		update_option( SettingsDefaults::OPTION_CATEGORIES, array() );
		update_option( SettingsDefaults::OPTION_POST_IDS, array() );

		// Feed service only registers on boot when the option is already on.
		// Call the filter callback directly so the test is deterministic.
		$GLOBALS['post'] = get_post( $post_id );
		$feed            = Plugin::instance()->feed_renderer;
		$out             = $feed->filter_title( 'Hello' );

		$this->assertStringContainsString( 'World', $out );
		$this->assertStringContainsString( 'Hello', $out );
		$this->assertStringNotContainsString( '<', $out );
	}
}
