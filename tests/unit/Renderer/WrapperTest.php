<?php
/**
 * Unit tests for the output wrapper.
 *
 * Verifies that stored (entity-encoded) secondary titles are
 * decoded exactly once at the render boundary.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Renderer;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Renderer\Wrapper;

/**
 * Wrapper unit tests.
 *
 * @since 3.0.0
 */
final class WrapperTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Prepares Brain Monkey and the WP function mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, mixed $value ): mixed {
				return $value;
			}
		);
		Functions\when( 'esc_attr' )->alias(
			static function ( string $value ): string {
				return $value;
			}
		);
		Functions\when( 'tag_escape' )->alias(
			static function ( string $value ): string {
				return $value;
			}
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
	 * Tests that entity-encoded stored values are decoded once.
	 *
	 * @return void
	 */
	public function test_decodes_stored_entities_once(): void {
		$wrapper = new Wrapper();

		$this->assertSame(
			'<span class="st-title">Tom & Jerry</span>',
			$wrapper->wrap( 'Tom &amp; Jerry' )
		);

		$this->assertSame(
			'<span class="st-title">Düsseldorf \'says\' "hi"</span>',
			$wrapper->wrap( 'D&#252;sseldorf &#039;says&#039; &quot;hi&quot;' )
		);

		// Entity-free values pass through unchanged.
		$this->assertSame(
			'<span class="st-title">Plain: John & Mary</span>',
			$wrapper->wrap( 'Plain: John & Mary' )
		);

		// Empty values produce no wrapper.
		$this->assertSame( '', $wrapper->wrap( '' ) );
	}
}
