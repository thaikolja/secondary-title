<?php
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
