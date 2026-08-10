<?php
/**
 * Unit tests for placeholder replacement.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Renderer;

use Thaikolja\SecondaryTitle\Renderer\Placeholder;

/**
 * Placeholder unit tests.
 *
 * @since 3.0.0
 */
final class PlaceholderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_replace_title_and_secondary(): void {
		$out = Placeholder::replace(
			'%secondary_title%: %title%',
			array(
				Placeholder::TITLE           => 'Hello',
				Placeholder::SECONDARY_TITLE => 'Sub',
			)
		);

		$this->assertSame( 'Sub: Hello', $out );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_empty_map_returns_format(): void {
		$this->assertSame( '%title%', Placeholder::replace( '%title%', array() ) );
	}
}
