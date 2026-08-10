<?php
/**
 * Integration tests for the deprecated v2 procedural API.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Api;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Deprecated API integration tests.
 *
 * @since 3.0.0
 */
final class DeprecatedApiTest extends \WP_UnitTestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_get_has_and_validate(): void {
		$this->setExpectedDeprecated( 'has_secondary_title' );
		$this->setExpectedDeprecated( 'get_secondary_title' );
		$this->setExpectedDeprecated( 'secondary_title_validate' );

		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		update_post_meta( $post_id, Plugin::META_KEY, 'Side title' );

		$this->assertTrue( has_secondary_title( $post_id ) );
		$this->assertStringContainsString( 'Side title', get_secondary_title( $post_id ) );

		update_option( SettingsDefaults::OPTION_POST_TYPES, array( 'page' ) );
		$this->assertFalse( secondary_title_validate( $post_id ) );
		$this->assertSame( '', get_secondary_title( $post_id, '', '', true ) );

		update_option( SettingsDefaults::OPTION_POST_TYPES, array() );
		$this->assertTrue( secondary_title_validate( $post_id ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_settings_accessors(): void {
		$this->setExpectedDeprecated( 'secondary_title_get_setting' );
		$this->setExpectedDeprecated( 'secondary_title_get_settings' );
		$this->setExpectedDeprecated( 'get_secondary_title_post_types' );

		update_option( SettingsDefaults::OPTION_AUTO_SHOW, SettingsDefaults::ON );
		$this->assertSame( SettingsDefaults::ON, secondary_title_get_setting( 'auto_show' ) );
		$this->assertIsArray( secondary_title_get_settings() );
		$this->assertIsArray( get_secondary_title_post_types() );
	}
}
