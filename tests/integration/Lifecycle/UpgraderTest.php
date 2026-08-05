<?php
/**
 * Integration tests for the v2.x.x -> v3.0.0 upgrader.
 *
 * Runs against a real WordPress database and asserts the core
 * promise of the upgrade: v2 users lose NO content. Post meta and
 * options are preserved, forensic backups are created, legacy
 * escaped meta values are normalized, and the migration is
 * idempotent.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Integration\Lifecycle;

use Thaikolja\SecondaryTitle\Lifecycle\Upgrader;
use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Upgrader integration tests.
 *
 * @since 3.0.0
 */
final class UpgraderTest extends \WP_UnitTestCase {

	/**
	 * Returns the plugin's upgrader service.
	 *
	 * @return Upgrader
	 */
	private function upgrader(): Upgrader {
		return Plugin::instance()->upgrader;
	}

	/**
	 * Returns a realistic set of v2.x.x options.
	 *
	 * @return array<string, mixed>
	 */
	private function v2_options(): array {
		return array(
			'secondary_title_post_types'             => array( 'post', 'page' ),
			'secondary_title_categories'             => array( '3', '7' ),
			'secondary_title_post_ids'               => array( '12', '34' ),
			'secondary_title_auto_show'              => 'on',
			'secondary_title_title_format'           => '%secondary_title%: %title%',
			'secondary_title_only_show_in_main_post' => 'off',
		);
	}

	/**
	 * Tests that a v2.x.x upgrade preserves every option 1:1, keeps
	 * a forensic backup, sanitizes unsanitized legacy markup, and is
	 * idempotent.
	 *
	 * @return void
	 */
	public function test_migrates_v2_data_without_data_loss(): void {
		// Simulate a v2.x.x installation: no db_version, options and
		// post meta as v2 left them in the database.
		foreach ( $this->v2_options() as $key => $value ) {
			update_option( $key, $value );
		}

		// v2 stored `_secondary_title` WITHOUT going through any
		// sanitizer on the block-editor path, and pre-escaped from
		// the Classic Editor. The plugin's own sanitize filter must
		// be bypassed here (raw inserts), exactly like v2 wrote the
		// database.
		global $wpdb;

		$escaped = $this->factory()->post->create();
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $escaped,
				'meta_key'   => '_secondary_title',
				'meta_value' => 'Tom &amp; Jerry',
			)
		);

		$raw = $this->factory()->post->create();
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $raw,
				'meta_key'   => '_secondary_title',
				'meta_value' => 'Plain: John & Mary',
			)
		);

		$markup = $this->factory()->post->create();
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $markup,
				'meta_key'   => '_secondary_title',
				'meta_value' => '<script>alert(1)</script>Bad title',
			)
		);

		// Run the migration.
		$this->upgrader()->maybe_upgrade();

		// Every live option keeps its v2 value.
		foreach ( $this->v2_options() as $key => $value ) {
			$this->assertSame( $value, get_option( $key ), "Option {$key} must keep its v2 value." );
		}

		// Forensic backups exist for every migrated key.
		foreach ( array_keys( $this->v2_options() ) as $key ) {
			$this->assertSame(
				$this->v2_options()[ $key ],
				get_option( 'v2_' . $key ),
				"Backup v2_{$key} must exist."
			);
		}

		// DB version is stamped.
		$this->assertSame( 3, (int) get_option( SettingsDefaults::OPTION_DB_VERSION ) );

		// Escaped and plain legacy values are preserved byte-for-byte
		// (escaped values are decoded at render time).
		$this->assertSame( 'Tom &amp; Jerry', get_post_meta( $escaped, '_secondary_title', true ) );
		$this->assertSame( 'Plain: John & Mary', get_post_meta( $raw, '_secondary_title', true ) );

		// Unsanitized legacy markup is cleaned exactly once: the
		// script tags are gone, only the text remains.
		$this->assertSame( 'alert(1)Bad title', get_post_meta( $markup, '_secondary_title', true ) );

		// Re-running the migration is a no-op.
		$this->upgrader()->maybe_upgrade();
		$this->assertSame( 'Tom &amp; Jerry', get_post_meta( $escaped, '_secondary_title', true ) );
		$this->assertSame( array( 'post', 'page' ), get_option( 'secondary_title_post_types' ) );
		$this->assertSame( 3, (int) get_option( SettingsDefaults::OPTION_DB_VERSION ) );
	}

	/**
	 * Tests that a fresh install is seeded with defaults and marked
	 * as migrated without ever touching (nonexistent) v2 data.
	 *
	 * @return void
	 */
	public function test_fresh_install_seeds_defaults_and_marks_migrated(): void {
		delete_option( SettingsDefaults::OPTION_DB_VERSION );

		$this->upgrader()->maybe_upgrade();

		$this->assertSame( 3, (int) get_option( SettingsDefaults::OPTION_DB_VERSION ) );
		$this->assertSame( array(), get_option( SettingsDefaults::OPTION_POST_TYPES ) );
		$this->assertSame( array(), get_option( SettingsDefaults::OPTION_CATEGORIES ) );
		$this->assertSame( array(), get_option( SettingsDefaults::OPTION_POST_IDS ) );
		$this->assertSame( 'on', get_option( SettingsDefaults::OPTION_AUTO_SHOW ) );
		$this->assertSame( 'right', get_option( SettingsDefaults::OPTION_COLUMN_POSITION ) );
	}
}
