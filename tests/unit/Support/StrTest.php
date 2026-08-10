<?php
/**
 * Unit tests for Str helpers.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Support;

use Thaikolja\SecondaryTitle\Support\Str;

/**
 * Str unit tests.
 *
 * @since 3.0.0
 */
final class StrTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_after_and_before(): void {
		$this->assertSame( 'world', Str::after( 'hello:world', ':' ) );
		$this->assertSame( 'hello', Str::before( 'hello:world', ':' ) );
		$this->assertSame( 'hello', Str::after( 'hello', 'x' ) );
	}
}
