<?php
/**
 * Plugin activation handler.
 *
 * Called by {@see register_activation_hook()} in secondary-title.php.
 * Seeds the default options on a fresh install.
 *
 * The database version is NOT touched here: the {@see Upgrader}
 * owns the version flag and stamps it only after a migration pass
 * has actually completed. Unconditionally stamping it in the
 * activator would mark v2.x.x sites as "already migrated" without
 * ever running the migration.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Lifecycle;

use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Plugin activator.
 *
 * @since 3.0.0
 */
final class Activator {

	/**
	 * Defaults.
	 *
	 * @var SettingsDefaults
	 */ private readonly SettingsDefaults $defaults;

	/**
	 * Repository.
	 *
	 * @var SettingsRepository
	 */ private readonly SettingsRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param SettingsDefaults   $defaults   Default values.
	 * @param SettingsRepository $repository Options repository.
	 */
	public function __construct( SettingsDefaults $defaults, SettingsRepository $repository ) {
		$this->defaults   = $defaults;
		$this->repository = $repository;
	}

	/**
	 * `register_activation_hook()` callback. Seeds every default
	 * option on a fresh install.
	 *
	 * @param bool $network_wide Whether the activation is happening
	 *                            network-wide (Multisite).
	 *
	 * @return void
	 */
	public function activate( bool $network_wide ): void {
		if ( is_multisite() && $network_wide ) {
			$this->activate_network();
			return;
		}

		$this->activate_site();
	}

	/**
	 * Seeds the defaults on the current site.
	 *
	 * The database version flag is deliberately left alone: the
	 * {@see Upgrader} stamps it after the migration has run, so a
	 * v2.x.x upgrade is never marked as migrated prematurely.
	 *
	 * @return void
	 */
	private function activate_site(): void {
		foreach ( $this->defaults->all() as $key => $default ) {
			if ( false === get_option( $key ) ) {
				$this->repository->set( $key, $default );
			}
		}
	}

	/**
	 * Seeds the defaults on every site of a Multisite network.
	 *
	 * @return void
	 */
	private function activate_network(): void {
		$blog_ids = get_sites( array( 'fields' => 'ids' ) );

		foreach ( (array) $blog_ids as $blog_id ) {
			switch_to_blog( (int) $blog_id );
			$this->activate_site();
			restore_current_blog();
		}
	}
}
