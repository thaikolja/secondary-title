<?php
/**
 * (C) Copyright 2021 by Kolja Nolte
 * kolja.nolte@gmail.com
 * https://www.kolja-nolte.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * @see    https://wordpress.org/plugins/secondary-title/
 * @author Kolja Nolte <kolja.nolte@gmail.com>
 */

/**
 * Plugin Name:   Secondary Title
 * Plugin URI:    https://www.kolja-nolte.com/wordpress/plugins/secondary-title/
 * Description:   Adds a secondary title to posts, pages and custom post types.
 * Version:       2.1.0
 * Author:        Kolja Nolte
 * Author URI:    https://www.kolja-nolte.com
 * License:       GPLv2 or later
 * License URI:   http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:   secondary-title
 */

/**
 * Stop script when the file is called directly.
 */
if ( ! function_exists( "add_action" ) ) {
	die( "403 - You are not authorized to view this page." );
}

define( "SECONDARY_TITLE_PATH", plugin_dir_path( __FILE__ ) );
define( "SECONDARY_TITLE_URL", plugin_dir_url( __FILE__ ) );

const SECONDARY_TITLE_VERSION = "2.1.0";


require_once __DIR__ . '/vendor/autoload.php'; // example path

/** Install default settings (if not set yet) */
register_activation_hook( __FILE__, "secondary_title_install" );

/** Handles the donation notification display settings */
register_deactivation_hook( __FILE__, "secondary_title_reset_donation_notice" );

/** Calls function which adds a link to the settings page on "Plugins" section in the admin area */
add_action( "plugin_action_links_" . plugin_basename( __FILE__ ), "secondary_title_add_settings_link" );

/** Find all .php files in the "includes" directory */
$include_files = glob( plugin_dir_path( __FILE__ ) . "/includes/*.php" );

/** Loop through all .php files in the "includes" directory */
foreach ( $include_files as $include_file ) {
	/** Skip file if file is not valid */
	if ( ! is_file( $include_file ) || is_dir( $include_file ) ) {
		continue;
	}

	/** Include current file */
	require_once $include_file;
}