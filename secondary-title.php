<?php
/**
 * Plugin Name:       Secondary Title
 * Plugin URI:        https://docs.kolja-nolte.com/secondary-title
 * Description:       Add a secondary title to posts, pages, and custom post types. Display it automatically, with a shortcode, or via a real Gutenberg block.
 * Version:           3.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Kolja Nolte
 * Author URI:        https://www.kolja-nolte.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       secondary-title
 * Domain Path:       /languages
 * Update URI:        https://docs.kolja-nolte.com/secondary-title
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types=1 );

/**
 * Stop script execution when the file is called directly.
 *
 * Using the WordPress-standard ABSPATH constant rather than the older
 * `function_exists( 'add_action' )` idiom. This must be checked before
 * the Composer autoloader is required, since the autoloader's side
 * effects would otherwise leak into a direct-access context.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Defines the current version of the plugin. Used as the version
 * argument for enqueued assets and as the source of truth for
 * upgrade routines.
 */
const SECONDARY_TITLE_VERSION = '3.0.0.rc.1';

/**
 * Defines the text domain used for all translatable strings.
 */
const SECONDARY_TITLE_TEXT_DOMAIN = 'secondary-title';

/**
 * Defines the absolute path to the plugin's root directory.
 */
define( 'SECONDARY_TITLE_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Defines the URL to the plugin's root directory.
 */
define( 'SECONDARY_TITLE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Defines the public documentation URL.
 */
const SECONDARY_TITLE_DOCS_URL = 'https://docs.kolja-nolte.com/secondary-title';

/**
 * Bootstraps the Composer autoloader.
 *
 * The autoloader is the only file we require manually. Every other
 * file in the plugin is reached through the PSR-4 autoloader
 * (`Thaikolja\SecondaryTitle\` -> `classes/`).
 *
 * NOTE: The deprecated v2.x.x API lives in
 * `includes/depreciation/functions.php` and must NOT be loaded via
 * Composer's `autoload.files` — its `ABSPATH` guard makes it exit
 * silently in CLI contexts (PHPUnit, PHPStan, WP-CLI). It is
 * required explicitly below, after the guard in this file has run.
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Loads the deprecated v2.x.x procedural API (see note above).
 */
require_once __DIR__ . '/includes/depreciation/functions.php';

/**
 * Boots the plugin.
 *
 * The Plugin class is the central coordinator: it constructs the
 * service container and wires the lifecycle hooks (activation,
 * deactivation, plugins_loaded). Instantiating it triggers every
 * required add_action() / add_filter() call, so we don't need
 * any global side effects at this point.
 */
Thaikolja\SecondaryTitle\Plugin::instance()->boot();
