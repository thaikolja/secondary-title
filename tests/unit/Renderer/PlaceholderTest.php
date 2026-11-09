<?php
/*
 * Copyright (C) 2026-2026 by Kolja Nolte
 * https://www.kolja-nolte.com
 * kolja.nolte@gmail.com
 *
 * All Rights Reserved.
 *
 * This code is proprietary software. You may not use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of
 * the Software without express written permission from the author.
 *
 * @author Kolja Nolte <kolja.nolte@gmail.com>
 * @date 2024-2026
 */

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
