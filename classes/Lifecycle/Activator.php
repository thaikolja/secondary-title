<?php
/**
 * Plugin activation handler.
 *
 * Called by {@see register_activation_hook()} in secondary-title.php.
 * Seeds the default options and bumps the database version to mark
 * the install as up-to-date.
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
	 * Target database version after a fresh install.
	 *
	 * @var int
	 */
	public const TARGET_DB_VERSION = 3;

	/**
	 * @var SettingsDefaults
	 */
	private readonly SettingsDefaults $defaults;

	/**
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $repository;

	/**
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
	 * @return void
	 */
	private function activate_site(): void {
		foreach ( $this->defaults->all() as $key => $default ) {
			if ( false === get_option( $key ) ) {
				$this->repository->set( $key, $default );
			}
		}

		// Always mark the current DB version on fresh installs.
		$this->repository->set( SettingsDefaults::OPTION_DB_VERSION, self::TARGET_DB_VERSION );
	}

	/**
	 * Seeds the defaults on every site of a Multisite network.
	 *
	 * @return void
	 */
	private function activate_network(): void {
		$blog_ids = get_sites( [ 'fields' => 'ids' ] );

		foreach ( (array) $blog_ids as $blog_id ) {
			switch_to_blog( (int) $blog_id );
			$this->activate_site();
			restore_current_blog();
		}
	}
}
