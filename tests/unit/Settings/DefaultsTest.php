<?php
/**
 * Unit tests for settings defaults.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Tests\Unit\Settings;

use Thaikolja\SecondaryTitle\Settings\Defaults;

/**
 * Defaults unit tests.
 *
 * @since 3.0.0
 */
final class DefaultsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Every UI-backed option must have a default.
	 *
	 * @return void
	 */
	public function test_all_expected_keys_present(): void {
		$all = ( new Defaults() )->all();

		$expected = array(
			Defaults::OPTION_POST_TYPES,
			Defaults::OPTION_CATEGORIES,
			Defaults::OPTION_POST_IDS,
			Defaults::OPTION_AUTO_SHOW,
			Defaults::OPTION_TITLE_FORMAT,
			Defaults::OPTION_ONLY_SHOW_IN_MAIN_POST,
			Defaults::OPTION_COLUMN_POSITION,
			Defaults::OPTION_EMPTY_BEHAVIOUR,
			Defaults::OPTION_STRIP_HTML,
			Defaults::OPTION_SHOW_IN_SEARCH,
			Defaults::OPTION_SHOW_IN_RSS,
			Defaults::OPTION_SHOW_IN_REST,
			Defaults::OPTION_DB_VERSION,
		);

		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, $all, "Missing default for {$key}" );
		}
	}

	/**
	 * Product defaults for new 3.0.0 options.
	 *
	 * @return void
	 */
	public function test_new_option_defaults(): void {
		$all = ( new Defaults() )->all();

		$this->assertSame( Defaults::EMPTY_HIDE, $all[ Defaults::OPTION_EMPTY_BEHAVIOUR ] );
		$this->assertSame( Defaults::OFF, $all[ Defaults::OPTION_STRIP_HTML ] );
		$this->assertSame( Defaults::OFF, $all[ Defaults::OPTION_SHOW_IN_SEARCH ] );
		$this->assertSame( Defaults::OFF, $all[ Defaults::OPTION_SHOW_IN_RSS ] );
		$this->assertSame( Defaults::ON, $all[ Defaults::OPTION_SHOW_IN_REST ] );
		$this->assertSame( Defaults::ON, $all[ Defaults::OPTION_AUTO_SHOW ] );
		$this->assertSame( Defaults::TITLE_FORMAT, $all[ Defaults::OPTION_TITLE_FORMAT ] );
	}
}
