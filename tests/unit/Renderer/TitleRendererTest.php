<?php
/**
 * Unit tests for the_title auto-merge.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Renderer;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Meta\Sanitizer as MetaSanitizer;
use Thaikolja\SecondaryTitle\Renderer\DisplayRules;
use Thaikolja\SecondaryTitle\Renderer\Format;
use Thaikolja\SecondaryTitle\Renderer\TitleRenderer;
use Thaikolja\SecondaryTitle\Renderer\Wrapper;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * TitleRenderer unit tests.
 *
 * @since 3.0.0
 */
final class TitleRendererTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Property.
	 *
	 * @var array<string, mixed>
	 */
	private array $store = array();

	/**
	 * Property.
	 *
	 * @var array<int, string>
	 */
	private array $meta = array();

	/**
	 * Test method.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->store = array(
			SettingsDefaults::OPTION_AUTO_SHOW    => SettingsDefaults::ON,
			SettingsDefaults::OPTION_ONLY_SHOW_IN_MAIN_POST => SettingsDefaults::OFF,
			SettingsDefaults::OPTION_TITLE_FORMAT => '%secondary_title%: %title%',
			SettingsDefaults::OPTION_POST_TYPES   => array(),
			SettingsDefaults::OPTION_CATEGORIES   => array(),
			SettingsDefaults::OPTION_POST_IDS     => array(),
			SettingsDefaults::OPTION_STRIP_HTML   => SettingsDefaults::OFF,
		);
		$this->meta  = array();

		$store =& $this->store;
		$meta  =& $this->meta;

		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $fallback = false ) use ( &$store ): mixed {
				return array_key_exists( $key, $store ) ? $store[ $key ] : $fallback;
			}
		);
		Functions\when( 'get_post_meta' )->alias(
			static function ( int $post_id, string $key, bool $single = false ) use ( &$meta ): mixed {
				unset( $key, $single );
				return $meta[ $post_id ] ?? '';
			}
		);
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'get_post_type' )->justReturn( 'post' );
		Functions\when( 'wp_get_post_categories' )->justReturn( array() );
		Functions\when( 'apply_filters' )->alias(
			static function ( string $tag, mixed $value ): mixed {
				return $value;
			}
		);
		Functions\when( 'esc_attr' )->alias( static fn ( string $v ): string => $v );
		Functions\when( 'tag_escape' )->alias( static fn ( string $v ): string => $v );
		Functions\when( 'wp_kses' )->alias(
			static function ( string $v, array $allowed_tags ): string {
				unset( $allowed_tags );
				return $v;
			}
		);
		Functions\when( 'wp_strip_all_tags' )->alias( static fn ( string $v ): string => preg_replace( '/<[^>]*>/', '', $v ) ?? '' );
		Functions\when( 'wpautop' )->alias( static fn ( string $v ): string => $v );
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
	 * @return TitleRenderer
	 */
	private function renderer(): TitleRenderer {
		$settings = new SettingsRepository( new SettingsDefaults() );
		$wrapper  = new Wrapper( $settings );
		$format   = new Format( $settings );
		$meta     = new MetaRepository( new MetaSanitizer(), $wrapper );
		$rules    = new DisplayRules( $settings );

		return new TitleRenderer( $settings, $format, $wrapper, $meta, $rules );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_merges_when_enabled(): void {
		$post             = new \WP_Post();
		$post->ID         = 5;
		$post->post_title = 'Hello';
		Functions\when( 'get_post' )->justReturn( $post );

		$this->meta[5] = 'Sub';

		$out = $this->renderer()->filter_the_title( 'Hello', 5 );
		$this->assertStringContainsString( 'Sub', $out );
		$this->assertStringContainsString( 'Hello', $out );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_skips_when_auto_show_off(): void {
		$this->store[ SettingsDefaults::OPTION_AUTO_SHOW ] = SettingsDefaults::OFF;
		$post     = new \WP_Post();
		$post->ID = 5;
		Functions\when( 'get_post' )->justReturn( $post );
		$this->meta[5] = 'Sub';

		$this->assertSame( 'Hello', $this->renderer()->filter_the_title( 'Hello', 5 ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_skips_when_display_rules_fail(): void {
		$this->store[ SettingsDefaults::OPTION_POST_TYPES ] = array( 'page' );
		$post     = new \WP_Post();
		$post->ID = 5;
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_post_type' )->justReturn( 'post' );
		$this->meta[5] = 'Sub';

		$this->assertSame( 'Hello', $this->renderer()->filter_the_title( 'Hello', 5 ) );
	}

	/**
	 * Test method.
	 *
	 * @return void
	 */
	public function test_skips_in_admin(): void {
		Functions\when( 'is_admin' )->justReturn( true );
		$this->assertSame( 'Hello', $this->renderer()->filter_the_title( 'Hello', 5 ) );
	}
}
