<?php
/**
 * Uninstall handler for the Secondary Title plugin.
 *
 * Runs when the user deletes the plugin from the WordPress admin.
 * It removes every option and scheduled event the plugin created.
 *
 * Post meta is intentionally NOT deleted. Existing posts continue
 * to carry their `_secondary_title` value so that re-installing the
 * plugin restores the user's content.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

/**
 * WordPress defines this constant only when the uninstall script is
 * invoked from the admin. Direct access must be blocked.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

/**
 * Snapshot of every option key the plugin manages. We delete them
 * all in a single query for efficiency.
 */
$option_keys = array(
	'secondary_title_post_types',
	'secondary_title_categories',
	'secondary_title_post_ids',
	'secondary_title_auto_show',
	'secondary_title_title_format',
	'secondary_title_only_show_in_main_post',
	'secondary_title_input_field_position',
	'secondary_title_use_in_permalinks',
	'secondary_title_permalinks_position',
	'secondary_title_column_position',
	'secondary_title_feed_auto_show',
	'secondary_title_feed_title_format',
	'secondary_title_include_in_search',
	'secondary_title_show_donation_notice',
	'secondary_title_db_version',
	// Forensic backups of the v2.x.x values, in case the user
	// ever rolls back to the old plugin.
	'v2_secondary_title_post_types',
	'v2_secondary_title_categories',
	'v2_secondary_title_post_ids',
	'v2_secondary_title_auto_show',
	'v2_secondary_title_title_format',
	'v2_secondary_title_only_show_in_main_post',
	'v2_secondary_title_input_field_position',
	'v2_secondary_title_use_in_permalinks',
	'v2_secondary_title_permalinks_position',
	'v2_secondary_title_column_position',
	'v2_secondary_title_feed_auto_show',
	'v2_secondary_title_feed_title_format',
	'v2_secondary_title_include_in_search',
	'v2_secondary_title_show_donation_notice',
);

foreach ( $option_keys as $option_key ) {
	delete_option( $option_key );
}

/**
 * Multisite: per-site options need to be removed on every site of
 * the network, not only the current one.
 */
if ( is_multisite() ) {
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( (int) $blog_id );

		foreach ( $option_keys as $option_key ) {
			delete_option( $option_key );
		}

		restore_current_blog();
	}
}
