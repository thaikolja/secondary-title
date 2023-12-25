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
 * @see        https://wordpress.org/plugins/secondary-title/
 * @author     Kolja Nolte <kolja.nolte@gmail.com>
 *
 * This file contains the main functions that can be used to return,
 * display or modify every information that is related to the plugin.
 *
 * @package    Secondary Title
 * @subpackage Global
 */

/**
 * Stop script when the file is called directly.
 *
 * @since 0.1.0
 */
if ( ! function_exists( "add_action" ) ) {
	die( "403 - You are not authorized to view this page." );
}

/**
 * Sets the default settings when plugin is activated (and no settings exist).
 *
 * @return bool
 *
 * @since 1.0.0
 *
 */
function secondary_title_install(): bool {
	$installed = true;

	/** Use update_option() to create the default options  */
	foreach ( secondary_title_get_default_settings() as $setting => $value ) {
		if ( get_option( $setting ) ) {
			continue;
		}

		if ( ! update_option( $setting, $value ) ) {
			$installed = false;
		}
	}

	return $installed;
}

/**
 * Resets all settings back to the default values.
 *
 * @return bool
 *
 * @since 1.6.0
 *
 */
function secondary_title_reset_settings(): bool {
	$default_settings = secondary_title_get_default_settings();

	foreach ( $default_settings as $setting => $default_value ) {
		update_option( $setting, $default_value );
	}

	return true;
}

/**
 * Returns all settings and their default values used by Secondary Title.
 *
 * @return array
 *
 * @since 0.1.0
 */
function secondary_title_get_default_settings(): array {
	/** Define the default settings and their values */
	$default_settings = [
		"secondary_title_post_types"             => [],
		"secondary_title_categories"             => [],
		"secondary_title_post_ids"               => [],
		"secondary_title_auto_show"              => "on",
		"secondary_title_title_format"           => "%secondary_title%: %title%",
		"secondary_title_input_field_position"   => "above",
		"secondary_title_only_show_in_main_post" => "off",
		"secondary_title_use_in_permalinks"      => "off",
		"secondary_title_permalinks_position"    => "prepend",
		"secondary_title_column_position"        => "right",
		"secondary_title_feed_auto_show"         => "off",
		"secondary_title_feed_title_format"      => "%title%",
		"secondary_title_include_in_search"      => "on",
		"secondary_title_show_donation_notice"   => "on",
		"secondary_title_strip_html"             => "on"
	];

	$default_settings = apply_filters( "secondary_title_get_default_settings", $default_settings );

	return (array) $default_settings;
}

/**
 * Returns all settings generated by Secondary Title and their current values.
 *
 * @param bool $use_prefix
 *
 * @return array
 *
 * @since 0.1.0
 *
 */
function secondary_title_get_settings( bool $use_prefix = true ): array {
	$settings = [];

	foreach ( secondary_title_get_default_settings() as $setting => $default_value ) {
		$option = get_option( $setting );
		$value  = $default_value;

		if ( $option ) {
			$value = $option;
		}

		if ( ! $use_prefix ) {
			$setting = str_replace( "secondary_title_", "", $setting );
		}

		$settings[ $setting ] = $value;
	}

	return $settings;
}

/**
 * Returns a specific setting for the plugin. If the selected
 * option is unset, the default value will be returned.
 *
 * @param string $setting
 *
 * @return mixed
 *
 * @since 0.1.0
 *
 */
function secondary_title_get_setting( string $setting ) {
	$settings = secondary_title_get_settings();

	if ( isset( $settings["secondary_title_$setting"] ) ) {
		$setting = $settings["secondary_title_$setting"];
	}

	return $setting;
}

/**
 * Returns the IDs of the posts for which secondary title is activated.
 *
 * @return array Post IDs
 *
 * @since 0.1.0
 *
 */
function get_secondary_title_post_ids(): array {
	return (array) secondary_title_get_setting( "post_ids" );
}

/**
 * Returns the post types for which secondary title is activated.
 *
 * @return array Post types
 *
 * @since 0.1.0
 *
 */
function get_secondary_title_post_types(): array {
	return (array) secondary_title_get_setting( "post_types" );
}

/**
 * Returns the categories for which secondary title is activated.
 *
 * @return array Selected categories
 *
 * @since 0.1.0
 *
 */
function get_secondary_title_post_categories(): array {
	return secondary_title_get_setting( "categories" );
}

/**
 * Get the secondary title from post ID $post_id
 *
 * @param int $post_id ID of target post.
 * @param string $prefix To be added in front of the secondary title.
 * @param string $suffix To be added after the secondary title.
 * @param bool $use_settings Use filters set on Secondary Title settings page.
 *
 * @return string The secondary title
 *
 * @since 0.1.0
 *
 */
require_once __DIR__ . '/../vendor/autoload.php';
function get_secondary_title( $post_id = 0, $prefix = "", $suffix = "", $use_settings = false ): string {
	/** If $post_id not set, use current post ID */
	if ( ! $post_id ) {
		$post_id = (int) get_the_ID();
	}

	/** Get the secondary title and return false if it's empty actually empty */
	$secondary_title = get_post_meta( $post_id, "_secondary_title", true );

	if ( ! $secondary_title ) {
		return "";
	}

	/** Use filters set on Secondary Title settings page */
	if ( $use_settings && ! secondary_title_validate( $post_id ) ) {
		return "";
	}

	$secondary_title = $prefix . $secondary_title . $suffix;

	/** Apply filters to secondary title if used with Word Filter Plus plugin */
	if ( class_exists( "WordFilter" ) ) {
		$word_filter     = new WordFilter;
		$secondary_title = $word_filter->filter_title( $secondary_title );
	}

	$secondary_title = apply_filters(
		"get_secondary_title",
		$secondary_title,
		$post_id,
		$prefix,
		$suffix
	);

	return (string) $secondary_title;
}

/**
 * Prints the secondary title and adds an optional suffix.
 *
 * @param int $post_id ID of target post.
 * @param string $prefix To be added in front of the secondary title.
 * @param string $suffix To be added after the secondary title.
 * @param bool $use_settings Use filters set on Secondary Title settings page.
 *
 * @since 0.1.0
 *
 */

use voku\helper\AntiXSS;

$antiXss = new AntiXSS();

function the_secondary_title( $post_id = 0, $prefix = "", $suffix = "", $use_settings = false ) {
	$secondary_title = get_secondary_title(
		$post_id,
		$prefix,
		$suffix,
		$use_settings
	);

	$secondary_title = apply_filters(
		"the_secondary_title",
		$secondary_title,
		$post_id,
		$prefix,
		$suffix
	);

	echo $secondary_title;
}

/**
 * Returns whether the specified post has a
 * secondary title or not.
 *
 * @param int $post_id Post ID of the post in question.
 *
 * @return bool
 *
 * @since 0.5.1
 *
 */
function has_secondary_title( int $post_id = 0 ): bool {
	return (bool) get_secondary_title( $post_id );
}

/**
 * Returns all available post types except pages, attachments,
 * revision ans nav_menu_items.
 *
 * @return array
 *
 * @since 0.1.0
 *
 */
function get_secondary_title_filtered_post_types(): array {
	/** Returns all registered post types */
	return get_post_types(
		[
			"public" => true, // Only show post types that are publicly accessible in the front end
		]
	);
}

/**
 * Returns all posts that have a valid
 * secondary title.
 *
 * @param array $additional_query
 *
 * @return array
 *
 * @since    0.9.2
 *
 * @internal param int $count
 *
 */
function get_posts_with_secondary_title( array $additional_query = [] ): array {
	$query_arguments = [
		"post_type"    => "any",
		"meta_key"     => "_secondary_title",
		"meta_value"   => " ",
		"meta_compare" => "!=",
		"post_status"  => "publish"
	];

	$query_arguments = wp_parse_args( $query_arguments, $additional_query );

	return get_posts( $query_arguments );
}

/**
 * Returns a random post that has a valid
 * secondary title.
 *
 * @return bool|WP_Post
 *
 * @since 0.9.2
 *
 */
function get_random_post_with_secondary_title() {
	$post = get_posts_with_secondary_title(
		[
			"showposts" => 1,
			"orderby"   => "rand"
		]
	);

	if ( ! $post ) {
		return false;
	}

	return $post[0];
}

/**
 * @param array $new_settings
 *
 * @return bool
 *
 * @since 1.4.0
 *
 */
function secondary_title_update_settings( array $new_settings = [] ): bool {
	$saved  = false;
	$arrays = [
		"post_types",
		"categories"
	];

	foreach ( secondary_title_get_default_settings() as $full_setting_name => $default_value ) {
		$setting_name = str_replace( "secondary_title_", "", $full_setting_name );
		$value        = "";

		if ( $setting_name === "show_donation_notice" ) {
			continue;
		}

		if ( isset( $new_settings[ $setting_name ] ) ) {
			$value = $new_settings[ $setting_name ];

			if ( $setting_name === "post_ids" ) {
				$value = preg_replace( "'[^0-9,]'", "", $value );

				if ( ! is_array( $value ) ) {
					$value = explode( ",", $value );
				}
			}

			if ( $setting_name === "post_ids" && ( ! $new_settings[ $setting_name ] || $value[0] === "" ) ) {
				$value = [];
			}
		} elseif ( in_array( $setting_name, $arrays, true ) ) {
			$value = [];
		}
		if ( update_option( $full_setting_name, $value ) ) {
			$saved = true;
		}
	}

	return $saved;
}

/**
 * Checks whether the secondary title is allowed
 * to be displayed according to the settings set
 * on Secondary Title's settings page.
 *
 * @param int $post_id
 *
 * @return bool
 *
 * @since 1.4.0
 *
 */
function secondary_title_validate( int $post_id ): bool {
	$allowed_post_types = get_secondary_title_post_types();
	$allowed_categories = get_secondary_title_post_categories();
	$allowed_post_ids   = get_secondary_title_post_ids();
	$post_categories    = wp_get_post_categories( $post_id );

	/** Check if post type is among the allowed post types */
	if ( count( $allowed_post_types ) && ! in_array( get_post_type( $post_id ), $allowed_post_types, false ) ) {
		return false;
	}

	/** Check if post's categories are among the allowed categories */
	$in_categories = false;
	foreach ( $post_categories as $category_id ) {
		if ( in_array( $category_id, $allowed_categories, false ) ) {
			$in_categories = true;
		}
	}
	if ( ! $in_categories && count( $allowed_categories ) ) {
		return false;
	}

	return ! in_array( $post_id, $allowed_post_ids, false );
}

/**
 * Verifies whether plugin settings allow secondary title
 * input box to be displayed.
 *
 * @return bool
 *
 * @since 1.7.0
 *
 */
function secondary_title_verify_admin_page(): bool {
	global $post;

	$category_taxonomy  = get_taxonomy( "category" );
	$allowed_post_ids   = secondary_title_get_setting( "post_ids" );
	$allowed_post_types = secondary_title_get_setting( "post_types" );
	$allowed_categories = secondary_title_get_setting( "categories" );

	/** Check if post is not among allowed post types */
	if ( isset( $post->post_type ) && count( $allowed_post_types ) && ! in_array( $post->post_type, $allowed_post_types, false ) ) {
		return false;
	}

	if ( ! isset( $_GET["post"] ) ) {
		return true;
	}

	/** Don't do anything if the post is not a valid, well, post */
	if ( ! $post->ID ) {
		return false;
	}

	if ( ! isset( $post->ID ) || ! get_the_title( $post->ID ) ) {
		return true;
	}

	/** Check if post is not among allowed post IDs */
	if ( count( $allowed_post_ids ) && ! in_array( $post->ID, $allowed_post_ids, false ) ) {
		return false;
	}

	/** Check if post is not among allowed post categories */
	if ( count( $allowed_categories ) && in_array( $post->post_type, $category_taxonomy->object_type, false ) ) {
		$in_category = false;
		foreach ( (array) wp_get_post_categories( $post->ID ) as $category ) {
			if ( ! $in_category && in_array( $category, $allowed_categories, false ) ) {
				$in_category = true;
			}
		}

		if ( ! $in_category ) {
			return false;
		}
	}

	/** Yup, we're good */
	return true;
}

/**
 * Turns the "show donation notification" setting back on
 * when the plugin is deactivated. Please don't kill me!
 *
 * @return bool
 * @since 1.9.7
 *
 */
function secondary_title_reset_donation_notice(): bool {
	return update_option( "secondary_title_show_donation_notice", "on" );
}

/**
 * Displays a Font Awesome info icon with a link
 * pointing to the relevant section in Secondary Title's
 * documentation on gitbooks.io.
 *
 * @param string $anchor
 *
 * @since 2.0.0
 */
function secondary_title_print_html_info_circle( string $anchor ): void {
	$info_url = "https://thaikolja.gitbooks.io/secondary-title/quick-start/settings.html";
	?>
    <a href="<?php echo $info_url . "#" . $anchor; ?>" target="_blank"
       title="<?php _e( "Click here to learn more about this setting", "secondary-title" ); ?>"
       class="info-circle right">
        <i class="fa fa-info-circle"></i>
    </a>
	<?php
}