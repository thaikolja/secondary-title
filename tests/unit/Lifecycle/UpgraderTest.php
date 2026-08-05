<?php
/**
 * Unit tests for the v2.x.x -> v3.0.0 upgrader.
 *
 * Covers the option migration (1:1 copy + forensic backups), the
 * seeding of v3-only defaults, the database-version flag ownership,
 * the Multisite loop, and the legacy post-meta normalization.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Lifecycle;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Lifecycle\Upgrader;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Upgrader unit tests.
 *
 * @since 3.0.0
 */
final class UpgraderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * In-memory option store used by the mocked option functions.
	 *
	 * @var array<string, mixed>
	 */
	private array $store = array();

	/**
	 * Every update_option() call made during a test, keyed by option.
	 *
	 * @var array<string, array<int, mixed>>
	 */
	private array $updates = array();

	/**
	 * The upgrader under test.
	 *
	 * @var Upgrader
	 */
	private Upgrader $upgrader;

	/**
	 * Prepares Brain Monkey and the option-function mocks.
	 *
	 * NOTE: The alias closures must NOT capture `$this` — Brain
	 * Monkey/Patchwork redefinitions persist across tests in the
	 * same process, so a closure bound to a previous test instance
	 * would keep reading that instance's (empty) store. Local
	 * variables captured by reference survive independently.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->store   = array();
		$this->updates = array();

		$store   =& $this->store;
		$updates =& $this->updates;

		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $default = false ) use ( &$store ): mixed {
				return array_key_exists( $key, $store ) ? $store[ $key ] : $default;
			}
		);

		Functions\when( 'update_option' )->alias(
			static function ( string $key, mixed $value, bool $autoload = true ) use ( &$store, &$updates ): bool {
				unset( $autoload );

				$store[ $key ]     = $value;
				$updates[ $key ][] = $value;

				return true;
			}
		);

		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'get_sites' )->justReturn( array() );
		Functions\when( 'switch_to_blog' )->justReturn( true );
		Functions\when( 'restore_current_blog' )->justReturn( true );

		// By default: no postmeta rows, so the meta pass is a no-op.
		$this->set_wpdb_rows( array() );

		$this->upgrader = new Upgrader(
			new SettingsRepository( new SettingsDefaults() ),
			new SettingsDefaults()
		);
	}

	/**
	 * Tears down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
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
	 * Replaces the global $wpdb with a test double.
	 *
	 * @param array<int, object> $rows Rows returned by get_results().
	 *
	 * @return void
	 */
	private function set_wpdb_rows( array $rows ): void {
		$GLOBALS['wpdb'] = new class( $rows ) {
			/**
			 * @var array<int, object>
			 */
			public array $rows;

			/**
			 * @var string
			 */
			public string $postmeta = 'wp_postmeta';

		/**
		 * Constructor.
		 *
		 * @param array<int, object> $rows Rows returned by get_results().
		 */
		public function __construct( array $rows ) {
			$this->rows = $rows;
		}

		/**
		 * Prepares the SQL string (identity, for unit tests).
		 *
		 * @param string $query The query template.
		 * @param mixed  ...$args Query arguments (ignored).
		 *
		 * @return string
		 */
		public function prepare( string $query, ...$args ): string {
			return $query;
		}

		/**
		 * Returns the fixture rows.
		 *
		 * @param string|null $query Ignored.
		 *
		 * @return array<int, object>
		 */
		public function get_results( $query = null ): array {
			return $this->rows;
		}
		};
	}

	/**
	 * Tests that v2 options are migrated 1:1 with forensic backups
	 * and that the database version is stamped.
	 *
	 * @return void
	 */
	public function test_migrates_v2_options_with_backups_and_stamps_db_version(): void {
		$this->store = array( 'secondary_title_db_version' => 0 ) + $this->v2_options();

		$this->upgrader->maybe_upgrade();

		foreach ( $this->v2_options() as $key => $value ) {
			$this->assertSame( $value, $this->store[ $key ], "Live {$key} must keep the v2 value." );
			$this->assertSame( $value, $this->store[ 'v2_' . $key ], "Backup v2_{$key} must exist." );
		}

		$this->assertSame( 3, $this->store[ SettingsDefaults::OPTION_DB_VERSION ] );
	}

	/**
	 * Tests that v3-only options are seeded when missing.
	 *
	 * @return void
	 */
	public function test_seeds_v3_only_defaults_when_missing(): void {
		$this->store = array(
			'secondary_title_db_version'   => 0,
			'secondary_title_post_types'   => array( 'post' ),
			'secondary_title_title_format' => '%secondary_title%: %title%',
		);

		$this->upgrader->maybe_upgrade();

		$this->assertSame( 'right', $this->store['secondary_title_column_position'] );
		$this->assertSame( array(), $this->store['secondary_title_categories'] );
		$this->assertSame( array(), $this->store['secondary_title_post_ids'] );
		$this->assertSame( 'on', $this->store['secondary_title_auto_show'] );
		$this->assertSame( 'off', $this->store['secondary_title_only_show_in_main_post'] );
	}

	/**
	 * Tests that a v2 value of '' or a missing option is not touched.
	 *
	 * @return void
	 */
	public function test_skips_missing_and_empty_options(): void {
		$this->store = array(
			'secondary_title_db_version' => 0,
			'secondary_title_auto_show'  => '',
		);

		$this->upgrader->maybe_upgrade();

		$this->assertArrayNotHasKey( 'v2_secondary_title_auto_show', $this->store );
		$this->assertArrayNotHasKey( 'v2_secondary_title_post_types', $this->store );
		$this->assertSame( '', $this->store['secondary_title_auto_show'] );
	}

	/**
	 * Tests that a second run is a no-op once the DB version is current.
	 *
	 * @return void
	 */
	public function test_is_idempotent_when_db_version_is_current(): void {
		$this->store = array( 'secondary_title_db_version' => 3 ) + $this->v2_options();

		$this->upgrader->maybe_upgrade();

		$this->assertSame( array(), $this->updates );
	}

	/**
	 * Tests that every site of a Multisite network is migrated.
	 *
	 * @return void
	 */
	public function test_migrates_every_site_of_a_network(): void {
		$current_blog = 1;
		$blog_stores  = array(
			1 => array( 'secondary_title_db_version' => 0 ),
			2 => array( 'secondary_title_db_version' => 0 ) + $this->v2_options(),
		);

		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_sites' )->justReturn( array( 1, 2 ) );
		Functions\when( 'switch_to_blog' )->alias(
			static function ( int $blog_id ) use ( &$current_blog ): bool {
				$current_blog = $blog_id;

				return true;
			}
		);
		Functions\when( 'restore_current_blog' )->alias(
			static function () use ( &$current_blog ): bool {
				$current_blog = 1;

				return true;
			}
		);
		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $default = false ) use ( &$current_blog, &$blog_stores ): mixed {
				$store = $blog_stores[ $current_blog ] ?? array();

				return array_key_exists( $key, $store ) ? $store[ $key ] : $default;
			}
		);
		Functions\when( 'update_option' )->alias(
			static function ( string $key, mixed $value ) use ( &$current_blog, &$blog_stores ): bool {
				$blog_stores[ $current_blog ][ $key ] = $value;

				return true;
			}
		);

		$this->upgrader->maybe_upgrade();

		$this->assertSame( 3, $blog_stores[1][ SettingsDefaults::OPTION_DB_VERSION ] );
		$this->assertSame( 3, $blog_stores[2][ SettingsDefaults::OPTION_DB_VERSION ] );
		$this->assertSame(
			$this->v2_options()['secondary_title_post_types'],
			$blog_stores[2]['secondary_title_post_types']
		);
		$this->assertSame(
			$this->v2_options()['secondary_title_post_types'],
			$blog_stores[2]['v2_secondary_title_post_types']
		);
	}

	/**
	 * Tests that only unsanitized legacy meta (literal markup, no
	 * entities) is sanitized, while entity-encoded and plain values
	 * are left untouched.
	 *
	 * @return void
	 */
	public function test_sanitizes_unsanitized_legacy_meta_only(): void {
		$this->store = array( 'secondary_title_db_version' => 0 );

		$this->set_wpdb_rows(
			array(
				(object) array(
					'meta_id'    => 1,
					'meta_value' => 'Tom &amp; Jerry',
				),
				(object) array(
					'meta_id'    => 2,
					'meta_value' => 'Plain: John & Mary',
				),
				(object) array(
					'meta_id'    => 3,
					'meta_value' => '<script>alert(1)</script>Title',
				),
			)
		);

		$meta_updates = array();
		$kses_calls   = array();

		Functions\when( 'wp_kses_post' )->alias(
			static function ( string $content ) use ( &$kses_calls ): string {
				$kses_calls[] = $content;

				// WP's kses strips scripts; approximate it here.
				return (string) preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $content );
			}
		);

		Functions\when( 'update_metadata_by_mid' )->alias(
			static function ( string $meta_type, int $object_id, mixed $meta_value ) use ( &$meta_updates ): bool {
				$meta_updates[ $object_id ] = $meta_value;

				return true;
			}
		);

		$this->upgrader->maybe_upgrade();

		// Escaped and plain values must never be rewritten.
		$this->assertSame( array( 3 => 'Title' ), $meta_updates );
		$this->assertSame( array( '<script>alert(1)</script>Title' ), $kses_calls );
	}
}
