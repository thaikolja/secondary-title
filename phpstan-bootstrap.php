<?php
/**
 * PHPStan bootstrap.
 *
 * Registers the plugin's top-level constants for static analysis.
 *
 * The constants are normally defined in `secondary-title.php`, but
 * its `ABSPATH` guard makes the `define()` calls appear unreachable
 * to PHPStan, so the symbols would be reported as "not found".
 *
 * @package Thaikolja\SecondaryTitle
 */

if ( ! defined( 'SECONDARY_TITLE_VERSION' ) ) {
	define( 'SECONDARY_TITLE_VERSION', '3.0.0' );
}

if ( ! defined( 'SECONDARY_TITLE_PATH' ) ) {
	define( 'SECONDARY_TITLE_PATH', '/srv/www/wp-content/plugins/secondary-title/' );
}

if ( ! defined( 'SECONDARY_TITLE_URL' ) ) {
	define( 'SECONDARY_TITLE_URL', 'https://example.test/wp-content/plugins/secondary-title/' );
}

if ( ! defined( 'SECONDARY_TITLE_DOCS_URL' ) ) {
	define( 'SECONDARY_TITLE_DOCS_URL', 'https://docs.kolja-nolte.com/secondary-title' );
}
