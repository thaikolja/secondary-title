<?php
/**
 * Unit tests for meta sanitization.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Meta;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Meta\Sanitizer;

/**
 * Meta sanitizer unit tests.
 *
 * @since 3.0.0
 */
final class SanitizerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'wp_unslash' )->alias(
			static function ( mixed $v ): mixed {
				return is_string( $v ) ? stripslashes( $v ) : $v;
			}
		);
		Functions\when( 'wp_kses_post' )->alias(
			static function ( string $v ): string {
				return (string) preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $v );
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
	public function test_empty_and_non_scalar(): void {
		$s = new Sanitizer();
		$this->assertSame( '', $s->sanitize( '' ) );
		$this->assertSame( '', $s->sanitize( '   ' ) );
		$this->assertSame( '', $s->sanitize( array( 'x' ) ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_strips_script_keeps_text(): void {
		$s = new Sanitizer();
		$this->assertSame( 'Safe', $s->sanitize( '<script>alert(1)</script>Safe' ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_unslashes_once(): void {
		$s = new Sanitizer();
		$this->assertSame( "O'Reilly", $s->sanitize( "O\\'Reilly" ) );
	}
}
