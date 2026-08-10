<?php
/**
 * Unit tests for front-end display rules.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Renderer;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Renderer\DisplayRules;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * DisplayRules unit tests.
 *
 * @since 3.0.0
 */
final class DisplayRulesTest extends \PHPUnit\Framework\TestCase {

	/**
	 * In-memory option store.
	 *
	 * @var array<string, mixed>
	 */
	private array $store = array();

	/**
	 * Test method.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->store = array();

		$store =& $this->store;
		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $fallback = false ) use ( &$store ): mixed {
				return array_key_exists( $key, $store ) ? $store[ $key ] : $fallback;
			}
		);
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test method.
	 *
	 * @return DisplayRules
	 */
	private function rules(): DisplayRules {
		return new DisplayRules( new SettingsRepository( new SettingsDefaults() ) );
	}

	/**
	 * Empty restrictions allow any valid post.
	 *
	 * @return void
	 */
	public function test_empty_restrictions_allow_all(): void {
		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'wp_get_post_categories' )->justReturn( array( 1, 2 ) );

		$this->assertTrue( $this->rules()->allows( 42 ) );
	}

	/**
	 * Non-empty post_types whitelist rejects other types.
	 *
	 * @return void
	 */
	public function test_post_type_whitelist(): void {
		$this->store[ SettingsDefaults::OPTION_POST_TYPES ] = array( 'page' );

		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'wp_get_post_categories' )->justReturn( array() );

		$this->assertFalse( $this->rules()->allows( 1 ) );

		Functions\when( 'get_post_type' )->justReturn( 'page' );
		$this->assertTrue( $this->rules()->allows( 1 ) );
	}

	/**
	 * Categories require at least one overlap.
	 *
	 * @return void
	 */
	public function test_category_whitelist_overlap(): void {
		$this->store[ SettingsDefaults::OPTION_CATEGORIES ] = array( '3', '7' );

		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'wp_get_post_categories' )->justReturn( array( 1, 2 ) );
		$this->assertFalse( $this->rules()->allows( 10 ) );

		Functions\when( 'wp_get_post_categories' )->justReturn( array( 7, 99 ) );
		$this->assertTrue( $this->rules()->allows( 10 ) );
	}

	/**
	 * Post IDs are a whitelist when non-empty (not the inverted v2 bug).
	 *
	 * @return void
	 */
	public function test_post_id_whitelist(): void {
		$this->store[ SettingsDefaults::OPTION_POST_IDS ] = array( 12, 34 );

		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'wp_get_post_categories' )->justReturn( array() );

		$this->assertFalse( $this->rules()->allows( 99 ) );
		$this->assertTrue( $this->rules()->allows( 12 ) );
	}

	/**
	 * Invalid post id fails.
	 *
	 * @return void
	 */
	public function test_invalid_post_id(): void {
		$this->assertFalse( $this->rules()->allows( 0 ) );
		$this->assertFalse( $this->rules()->allows( -1 ) );
	}
}
