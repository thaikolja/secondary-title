<?php
/**
 * Loads the plugin's text domain on `init`.
 *
 * Modern WordPress (4.6+) automatically loads translations for
 * plugins hosted on translate.wordpress.org whose text domain
 * matches the plugin slug. For non-.org installs or bundled
 * language files, this loader provides the fallback.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\I18n;

use Thaikolja\SecondaryTitle\Plugin;

/**
 * i18n loader.
 *
 * @since 3.0.0
 */
final class Loader {

	/**
	 * The action hook used to load the text domain.
	 *
	 * @var string
	 */
	private const HOOK = 'init';

	/**
	 * Registers the `init` listener.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, [ $this, 'load' ] );
	}

	/**
	 * Loads the text domain.
	 *
	 * The third argument to `load_plugin_textdomain()` must be
	 * a path *relative* to `WP_PLUGIN_DIR`. `plugin_basename()` on
	 * the plugin's path produces e.g. `secondary-title`, and we
	 * append `/languages` to that.
	 *
	 * @return void
	 */
	public function load(): void {
		$plugin_rel_path = dirname( plugin_basename( SECONDARY_TITLE_PATH . 'secondary-title.php' ) ) . '/languages';

		load_plugin_textdomain(
			Plugin::TEXT_DOMAIN,
			false,
			$plugin_rel_path
		);
	}
}
