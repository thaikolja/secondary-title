<?php
/**
 * Unit tests for settings sanitization.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Settings;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Settings\Defaults;
use Thaikolja\SecondaryTitle\Settings\Sanitizer;

/**
 * Settings sanitizer unit tests.
 *
 * @since 3.0.0
 */
final class SanitizerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Property.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Test method.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->sanitizer = new Sanitizer();

		Functions\when( 'sanitize_key' )->alias(
			static function ( string $key ): string {
				$key = strtolower( $key );
				return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( mixed $v ): int {
				return abs( (int) $v );
			}
		);
		Functions\when( 'wp_unslash' )->alias(
			static function ( mixed $v ): mixed {
				return is_string( $v ) ? stripslashes( $v ) : $v;
			}
		);
		Functions\when( 'wp_kses_post' )->alias(
			static function ( string $v ): string {
				return preg_replace( '/<(?!\/?(?:span|em|strong|b|i)\b)[^>]*>/i', '', $v ) ?? $v;
			}
		);
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'remove_filter' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, mixed $value ): mixed {
				return $value;
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
	 * @return void
	 */
	public function test_sanitize_post_types(): void {
		$this->assertSame(
			array( 'post', 'page' ),
			$this->sanitizer->sanitize_post_types( array( 'Post', 'page', '!!!' ) )
		);
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_sanitize_categories_and_post_ids(): void {
		$this->assertSame( array( 3, 7 ), $this->sanitizer->sanitize_categories( array( '3', '0', '7' ) ) );
		$this->assertSame( array( 13, 71 ), $this->sanitizer->sanitize_post_ids( '13, 71, abc' ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_on_off_and_empty_behaviour(): void {
		$this->assertSame( 'on', $this->sanitizer->sanitize_on_off( 'on' ) );
		$this->assertSame( 'off', $this->sanitizer->sanitize_on_off( 'yes' ) );
		$this->assertSame( 'hide', $this->sanitizer->sanitize_empty_behaviour( 'nope' ) );
		$this->assertSame( 'primary', $this->sanitizer->sanitize_empty_behaviour( 'primary' ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_dispatch_by_key(): void {
		$this->assertSame( 'right', $this->sanitizer->sanitize( Defaults::OPTION_COLUMN_POSITION, 'right' ) );
		$this->assertSame( 'left', $this->sanitizer->sanitize( Defaults::OPTION_COLUMN_POSITION, 'left' ) );
		$this->assertSame( 'off', $this->sanitizer->sanitize( Defaults::OPTION_STRIP_HTML, 'off' ) );
		$this->assertSame( 'on', $this->sanitizer->sanitize( Defaults::OPTION_SHOW_IN_REST, 'on' ) );
	}
}
