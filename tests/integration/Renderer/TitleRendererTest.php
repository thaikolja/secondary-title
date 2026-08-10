<?php
/**
 * Integration tests for auto-merge + display rules.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Renderer;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * TitleRenderer integration tests.
 *
 * @since 3.0.0
 */
final class TitleRendererTest extends \WP_UnitTestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_auto_merge_respects_post_type_rule(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Primary',
				'post_type'  => 'post',
			)
		);
		update_post_meta( $post_id, Plugin::META_KEY, 'Secondary' );
		update_option( SettingsDefaults::OPTION_AUTO_SHOW, SettingsDefaults::ON );
		update_option( SettingsDefaults::OPTION_POST_TYPES, array( 'page' ) );
		update_option( SettingsDefaults::OPTION_TITLE_FORMAT, '%secondary_title%: %title%' );

		// Simulate front end.
		set_current_screen( 'front' );
		// is_admin() is false outside admin; WP_UnitTestCase runs as front by default for filters.

		$merged = apply_filters( 'the_title', 'Primary', $post_id );
		$this->assertSame( 'Primary', $merged, 'Restricted post type must not auto-merge.' );

		update_option( SettingsDefaults::OPTION_POST_TYPES, array( 'post' ) );
		$merged = apply_filters( 'the_title', 'Primary', $post_id );
		$this->assertStringContainsString( 'Secondary', $merged );
		$this->assertStringContainsString( 'Primary', $merged );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_post_id_whitelist(): void {
		$allowed = self::factory()->post->create( array( 'post_title' => 'Allowed' ) );
		$blocked = self::factory()->post->create( array( 'post_title' => 'Blocked' ) );
		update_post_meta( $allowed, Plugin::META_KEY, 'A' );
		update_post_meta( $blocked, Plugin::META_KEY, 'B' );
		update_option( SettingsDefaults::OPTION_AUTO_SHOW, SettingsDefaults::ON );
		update_option( SettingsDefaults::OPTION_POST_IDS, array( $allowed ) );
		update_option( SettingsDefaults::OPTION_POST_TYPES, array() );
		update_option( SettingsDefaults::OPTION_CATEGORIES, array() );

		$this->assertStringContainsString( 'A', apply_filters( 'the_title', 'Allowed', $allowed ) );
		$this->assertSame( 'Blocked', apply_filters( 'the_title', 'Blocked', $blocked ) );
	}
}
