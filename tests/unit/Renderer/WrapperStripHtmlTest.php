<?php
/**
 * Unit tests for Wrapper strip-HTML setting.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Renderer;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Renderer\Wrapper;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Wrapper strip-HTML tests.
 *
 * @since 3.0.0
 */
final class WrapperStripHtmlTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Property.
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
		$this->store = array(
			SettingsDefaults::OPTION_STRIP_HTML => SettingsDefaults::ON,
		);

		$store =& $this->store;
		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $fallback = false ) use ( &$store ): mixed {
				return array_key_exists( $key, $store ) ? $store[ $key ] : $fallback;
			}
		);
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, mixed $value ): mixed {
				return $value;
			}
		);
		Functions\when( 'esc_attr' )->alias( static fn ( string $v ): string => $v );
		Functions\when( 'tag_escape' )->alias( static fn ( string $v ): string => $v );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn ( string $v ): string => preg_replace( '/<[^>]*>/', '', $v ) ?? '' );
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
	public function test_strips_html_when_setting_on(): void {
		$wrapper = new Wrapper( new SettingsRepository( new SettingsDefaults() ) );
		$this->assertSame(
			'<span class="st-title">Bold</span>',
			$wrapper->wrap( '<strong>Bold</strong>' )
		);
	}
}
