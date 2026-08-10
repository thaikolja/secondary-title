<?php
/**
 * Unit tests for Arr helpers.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Support;

use Thaikolja\SecondaryTitle\Support\Arr;

/**
 * Arr unit tests.
 *
 * @since 3.0.0
 */
final class ArrTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_string_list(): void {
		$this->assertSame( array(), Arr::string_list( null ) );
		$this->assertSame( array(), Arr::string_list( '' ) );
		$this->assertSame( array( 'post' ), Arr::string_list( 'post' ) );
		$this->assertSame( array( 'a', 'b' ), Arr::string_list( array( 'a', 'b', array() ) ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_positive_int_list(): void {
		$this->assertSame( array(), Arr::positive_int_list( '' ) );
		$this->assertSame( array( 13, 71, 33 ), Arr::positive_int_list( '13, 71, abc, 33' ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_get_contains_only_except(): void {
		$items = array(
			'a' => 1,
			'b' => 2,
			'c' => 3,
		);
		$this->assertSame( 2, Arr::get( $items, 'b' ) );
		$this->assertSame( 9, Arr::get( $items, 'z', 9 ) );
		$this->assertTrue( Arr::contains( array( 1, 2 ), 2 ) );
		$this->assertSame( array( 'a' => 1 ), Arr::only( $items, array( 'a' ) ) );
		$this->assertSame(
			array(
				'b' => 2,
				'c' => 3,
			),
			Arr::except( $items, array( 'a' ) )
		);
	}
}
