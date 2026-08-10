<?php
/**
 * Integration tests for uninstall behaviour.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Uninstall integration tests.
 *
 * @since 3.0.0
 */
final class UninstallTest extends \WP_UnitTestCase {

	/**
	 * Options are deleted; post meta is preserved.
	 *
	 * @return void
	 */
	public function test_uninstall_preserves_meta_deletes_options(): void {
		$post_id = self::factory()->post->create();
		update_post_meta( $post_id, Plugin::META_KEY, 'Keep me' );
		update_option( SettingsDefaults::OPTION_AUTO_SHOW, SettingsDefaults::ON );
		update_option( SettingsDefaults::OPTION_SHOW_IN_SEARCH, SettingsDefaults::ON );
		update_option( SettingsDefaults::OPTION_DB_VERSION, 3 );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress uninstall contract.
			define( 'WP_UNINSTALL_PLUGIN', 'secondary-title/secondary-title.php' );
		}

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		$this->assertFalse( get_option( SettingsDefaults::OPTION_AUTO_SHOW ) );
		$this->assertFalse( get_option( SettingsDefaults::OPTION_SHOW_IN_SEARCH ) );
		$this->assertFalse( get_option( SettingsDefaults::OPTION_DB_VERSION ) );
		$this->assertSame( 'Keep me', get_post_meta( $post_id, Plugin::META_KEY, true ) );
	}
}
