<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads the Composer autoloader and conditionally includes the
 * WordPress test suite functions when running integration tests.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

/**
 * Locate Composer's autoloader. If it's missing, the developer
 * probably forgot to run `composer install`; surface a clear error.
 */
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	fwrite( STDERR, "Composer autoloader not found. Run `composer install` first.\n" );
	exit( 1 );
}

require_once $autoloader;

/**
 * Minimal stubs for unit tests that reference WP core classes without
 * loading the full WordPress test suite.
 */
if ( ! class_exists( 'WP_Post', false ) && false === getenv( 'WP_TESTS_DIR' ) ) {
	/**
	 * Lightweight stand-in for {@see WP_Post}.
	 */
	class WP_Post {
		/**
		 * Post ID.
		 *
		 * @var int
		 */
		public $ID = 0;

		/**
		 * Post title.
		 *
		 * @var string
		 */
		public $post_title = '';

		/**
		 * Post type.
		 *
		 * @var string
		 */
		public $post_type = 'post';
	}
}

/**
 * Only load the WordPress test framework for the integration test
 * suite. Unit tests should be runnable without a WordPress
 * installation.
 */
if ( getenv( 'WP_TESTS_DIR' ) !== false ) {
	$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

	if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
		fwrite( STDERR, "Could not find {$wp_tests_dir}/includes/functions.php. Did you run `wp-cli scaffold plugin-tests`?\n" );
		exit( 1 );
	}

	require_once $wp_tests_dir . '/includes/functions.php';

	/**
	 * Loads the plugin under test into the WordPress test environment.
	 * WordPress looks for this constant to find the plugin file.
	 */
	tests_add_filter(
		'muplugins_loaded',
		static function (): void {
			require dirname( __DIR__ ) . '/secondary-title.php';
		}
	);

	require_once $wp_tests_dir . '/includes/bootstrap.php';
}
