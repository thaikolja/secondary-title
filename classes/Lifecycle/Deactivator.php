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
 * Plugin deactivation handler.
 *
 * The v2.x.x code re-armed the donation nag on deactivation, which
 * violates the WordPress.org plugin guidelines. This implementation
 * does NOT re-arm anything.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Lifecycle;

/**
 * Plugin deactivator.
 *
 * @since 3.0.0
 */
final class Deactivator {

	/**
	 * `register_deactivation_hook()` callback.
	 *
	 * Intentionally a no-op: we don't touch options, don't schedule
	 * anything, don't display anything. A clean deactivation.
	 *
	 * @param bool $network_wide Whether the deactivation is happening
	 *                            network-wide (Multisite).
	 *
	 * @return void
	 */
	public function deactivate( bool $network_wide ): void {
		unset( $network_wide );
		// No-op.
	}
}
