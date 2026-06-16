<?php
/**
 * Admin notices. Reserved for future notices (e.g. WP.org compliance
 * warnings, upgrade notices). The v2.x.x donation nag is intentionally
 * NOT ported.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Admin;

/**
 * Admin notices.
 *
 * @since 3.0.0
 */
final class Notices {

	/**
	 * Reserved hook. No-op.
	 *
	 * @return void
	 */
	public function register(): void {
		// Intentionally empty. Addons can add their own notices via
		// the standard `admin_notices` action.
	}
}
