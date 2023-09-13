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
 * This file contains the hooks used for Secondary Title.
 * Hooks are functions that modify WordPress core functions
 * and thus allow to change their output.
 *
 * @package    Secondary Title
 * @subpackage Global
 */

/**
 * Stop script when the file is called directly.
 *
 * @since 0.0.1
 */

use voku\helper\AntiXSS;

if ( ! function_exists( "add_action" ) ) {
	die( "403 - You are not authorized to view this page." );
}

/**
 * Add a secondary title meta box to Gutenberg editor
 *
 * @param $post
 *
 * @since 2.0.0
 */
function secondary_title_gutenberg_meta_box_content( $post ) {
	$secondary_title = get_secondary_title( $post->ID );
	$secondary_title = esc_html( $secondary_title );
	$title           = __( "Enter secondary title here", "secondary-title" );
	$placeholder     = $title . "...";
	?>
    <input type="text" value="<?php echo $secondary_title; ?>" id="secondary-title"
           class="components-text-control__input" name="secondary_post_title" title="<?php echo $title; ?>"
           placeholder="<?php echo $placeholder; ?>"/>
	<?php
}

/**
 * @since 2.0.0
 */
function secondary_title_gutenberg_add_meta_box() {
	global $current_screen;

	if ( method_exists( $current_screen, "is_block_editor" ) && $current_screen->is_block_editor() ) {
		add_meta_box(
			"secondary_title_gutenberg_meta_box",
			__( "Secondary Title", "secondary-title" ),
			"secondary_title_gutenberg_meta_box_content",
			secondary_title_get_setting( "post_types" ),
			"side"
		);
	}
}

add_action( "add_meta_boxes", "secondary_title_gutenberg_add_meta_box" );

/**
 * @since 2.0.0
 */
function secondary_title_gutenberg_register_meta() {
	register_meta(
		"any",
		"_secondary_title",
		[
			"type"         => "string",
			"single"       => true,
			"show_in_rest" => true
		]
	);
}

add_action( "init", "secondary_title_gutenberg_register_meta" );

/**
 * Updates the secondary title when "Edit post" screen
 * is being saved.
 *
 * @param $post_id
 *
 * @return bool|int
 *
 * @since 0.0.1
 */
function secondary_title_edit_post( $post_id ) {
	global $antiXss;

	if ( ! isset( $_POST["secondary_post_title"] ) ) {
		return false;
	}

	/** Don't save on autosave */
	if ( defined( "DOING_AUTOSAVE" ) && DOING_AUTOSAVE ) {
		return false;
	}

	/** Don't save if user doesn't have necessary permissions */
	if ( isset( $_POST["post_type"] ) && "page" === $_POST["post_type"] ) {
		if ( ! current_user_can( "edit_page", $post_id ) ) {
			return false;
		}
	} elseif ( ! current_user_can( "edit_post", $post_id ) ) {
		return false;
	}


	return update_post_meta(
		$post_id,
		"_secondary_title",
		esc_html( $antiXss->xss_clean( wp_strip_all_tags( filter_input( INPUT_POST, 'secondary_post_title' ) ) ) )
	);
}

add_action( "save_post", "secondary_title_edit_post" );

/**
 * Adds a "Secondary title" column to the posts/pages
 * overview (edit.php).
 *
 * @param $columns
 *
 * @return array
 *
 * @since 0.7
 */
function secondary_title_overview_columns( $columns ): array {
	$new_columns = [];

	foreach ( $columns as $column_slug => $column_title ) {
		/** Insert the secondary title before the "author" column */

		if ( $column_slug === "author" ) {
			$new_columns["secondary_title"] = __( "Secondary title", "secondary-title" );
		}

		$new_columns[ $column_slug ] = $column_title;
	}

	return $new_columns;
}

/**
 * Displays the extra column for the post types for which
 * the secondary title has been activated.
 *
 * @since 0.7
 */
function secondary_title_init_columns() {
	$allowed_post_types = (array) secondary_title_get_setting( "post_types" );
	$post_types         = get_post_types(
		[
			"public" => true
		]
	);

	foreach ( $post_types as $post_type ) {
		/** Add "Secondary title" column to activated post types */
		if ( ! isset( $allowed_post_types[0] ) || in_array( $post_type, $allowed_post_types, true ) ) {

			/** Adding columns */
			add_filter( "manage_{$post_type}_posts_columns", "secondary_title_overview_columns" );

			/** Adding columns content */
			add_filter(
				"manage_{$post_type}_posts_custom_column",
				"secondary_title_overview_column_content",
				10,
				2
			);
		}
	}
}

/**
 * Displays the secondary title and lets
 * jQuery move it into the column.
 *
 * @param $column
 * @param $post_id
 *
 * @since 0.7
 */
function secondary_title_overview_column_content( $column, $post_id ) {
	if ( $column === "secondary_title" ) {
		the_secondary_title( $post_id );
	}
}

/**
 * If auto show function is set, replace the post titles
 * with custom title format.
 *
 * @param $title string The default title
 *
 * @return string The changed or default title
 *
 * @since 0.0.1
 */
function secondary_title_auto_show( string $title ): string {
	global $post;

	/** Keep the standard title */
	$standard_title = $title;

	/** Don't do "auto show" when on admin area or if the post is not a valid post */
	if ( ! isset( $post->ID ) || is_admin() ) {
		return $standard_title;
	}

	/** The unaltered secondary title $secondary_title */
	$secondary_title = get_secondary_title(
		$post->ID,
		"",
		"",
		true
	);

	/** Validate secondary title */
	if ( ! $secondary_title || get_option( "secondary_title_auto_show" ) === "off" || $title !== wptexturize( $post->post_title ) ) {
		return $standard_title;
	}

	/** Apply title format */
	$format = str_replace(
		'"',
		"'",
		stripslashes( get_option( "secondary_title_title_format" ) )
	);

	$title = str_replace(
		"%title%",
		$title,
		$format
	);

	$title = str_replace(
		"%secondary_title%",
		html_entity_decode( $secondary_title ),
		$title
	);

	/** Only display if title is within the main loop */
	if ( secondary_title_get_setting( "only_show_in_main_post" ) === "on" ) {
		global $wp_query;

		if ( ! $wp_query->in_the_loop ) {
			return $standard_title;
		}
	}

	return (string) $title;
}

add_filter( "the_title", "secondary_title_auto_show" );

/**
 * Loads scripts and styles.
 *
 * @since 0.0.1
 */
function secondary_title_scripts_and_styles() {
	global $current_screen;

	$page_base     = $current_screen->base;
	$plugin_folder = plugin_dir_url( __DIR__ );

	/** Don't load anything if we're not on the right page */
	if ( $page_base !== "edit" && $page_base !== "post" && $page_base !== "settings_page_secondary-title" ) {
		return;
	}

	/** Scripts */

	wp_enqueue_script(
		"secondary-title-scripts-admin",
		"{$plugin_folder}scripts/js/admin.min.js",
		[ "jquery" ],
		SECONDARY_TITLE_VERSION
	);

	/** Styles */

	wp_enqueue_style(
		"secondary-title-styles-admin",
		"{$plugin_folder}styles/css/admin.min.css",
		[],
		SECONDARY_TITLE_VERSION
	);

	wp_enqueue_style(
		"secondary-font-awesome",
		"{$plugin_folder}styles/css/font-awesome.min.css",
		[ "secondary-title-styles-admin" ],
		"5.2.0"
	);
}

add_action( "admin_enqueue_scripts", "secondary_title_scripts_and_styles" );

/**
 * Initialize setting on admin interface.
 *
 * @since 0.0.1
 */
function secondary_title_init_admin_settings() {
	/** Creates a new page on the admin interface */
	add_options_page(
		__( "Settings", "secondary-title" ),
		"Secondary Title",
		"manage_options",
		"secondary-title",
		"secondary_title_settings_page"
	);
}

add_action( "admin_menu", "secondary_title_init_admin_settings" );

/**
 * Registers the %secondary_title% tag as a
 * permalink tag.
 *
 * @since 0.8
 */
function secondary_title_permalinks_init() {
	add_rewrite_tag( "%secondary_title%", "([^&]+)" );
}

add_action( "init", "secondary_title_permalinks_init" );

/**
 * @param $permalink
 * @param $post
 *
 * @return mixed
 **@since 1.5.4
 *
 */
function secondary_title_modify_permalink( $permalink, $post ) {
	$secondary_title = get_secondary_title( $post->ID );
	$secondary_title = sanitize_title_for_query( $secondary_title );
	$placeholder     = "%secondary_title%";

	if ( $secondary_title ) {
		$permalink = str_replace( $placeholder, $secondary_title, $permalink );
	} else {
		/** Remove placeholder from permalink if no secondary title exists */
		$permalink = str_replace( $placeholder, "", $permalink );
	}

	return $permalink;
}

add_filter( "post_link", "secondary_title_modify_permalink", 10, 2 );

/**
 * Modifies the post titles in RSS feeds.
 *
 * @param $original_title
 *
 * @return string
 * @since 1.7
 *
 */
function secondary_title_modify_feed_title( $original_title ): string {
	global $post;

	/** Gather necessary settings */
	$auto_show            = secondary_title_get_setting( "auto_show" );
	$feed_title_auto_show = secondary_title_get_setting( "feed_auto_show" );
	$title                = $original_title;

	/** Only modify title if setting is set and auto show setting is off */
	if ( $feed_title_auto_show === "on" && $auto_show === "off" ) {
		$feed_title_format = secondary_title_get_setting( "feed_title_format" );
		$secondary_title   = get_secondary_title( $post->ID );

		/** Only modify title if post actually has a secondary title */
		if ( $secondary_title ) {
			$title        = $feed_title_format;
			$replacements = [
				"%title%"           => $post->post_title,
				"%secondary_title%" => $secondary_title
			];

			/** Replace placeholders with replacements */
			foreach ( $replacements as $placeholder => $replacement ) {
				$title = str_replace( $placeholder, $replacement, $title );
			}
		}
	}

	return (string) $title;
}

add_filter( "the_title_rss", "secondary_title_modify_feed_title" );

/**
 * @param $pieces
 *
 * @return mixed
 * @since 1.8.0
 *
 */
function secondary_title_extend_search( $pieces ) {
	if ( is_search() && ! is_admin() ) {
		global $wpdb;

		$custom_field = "_secondary_title";
		$keywords     = explode( " ", get_query_var( "s" ) );
		$query        = "((meta_key = '$custom_field')";

		foreach ( $keywords as $word ) {
			$query .= " AND (wptest.meta_value  LIKE '%{$word}%')) OR ";
		}

		$pieces["where"] = str_replace(
			"((({$wpdb->posts}.post_title LIKE '%",
			"( {$query} (({$wpdb->posts}.post_title LIKE '%",
			$pieces["where"]
		);

		$pieces["join"]    .= " INNER JOIN {$wpdb->postmeta} AS wptest ON ({$wpdb->posts}.ID = wptest.post_id)";
		$pieces["groupby"] = "{$wpdb->posts}.ID";
	}

	return $pieces;
}

/**
 * Joins posts and postmeta tables
 *
 * @param $join
 *
 * @return string
 * @since 1.9.0
 *
 */
function secondary_title_search_join( $join ): string {
	global $wpdb;

	if ( is_search() ) {
		$join .= " LEFT JOIN " . $wpdb->postmeta . " AS stmt1 ON " . $wpdb->posts . ".ID = stmt1.post_id ";
	}

	return $join;
}

/**
 * Modifies the search query with posts_where.
 *
 * @param $where
 *
 * @return mixed
 *
 * @since 1.9.0
 *
 */
function secondary_title_search_where( $where ) {
	global $wpdb;

	if ( is_search() ) {
		$where = preg_replace(
			"/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			"(" . $wpdb->posts . ".post_title LIKE $1) OR (stmt1.meta_value LIKE $1)",
			$where
		);
	}

	return $where;
}

/**
 * Prevents duplicates.
 *
 * @param $where
 *
 * @return string
 *
 * @since 1.9.0
 *
 */
function secondary_title_search_distinct( $where ): string {
	if ( is_search() ) {
		return "DISTINCT";
	}

	return $where;
}

if ( secondary_title_get_setting( "include_in_search" ) === "on" ) {
	add_filter( "posts_join", "secondary_title_search_join" );
	add_filter( "posts_where", "secondary_title_search_where" );
	add_filter( "posts_distinct", "secondary_title_search_distinct" );
}

/**
 * Displays the secondary title when shortcode is used.
 *
 * @param $attributes
 *
 * @return string
 *
 * @since 1.9.2
 */
function secondary_title_add_shortcode_function( $attributes ): string {
	$post_id = get_the_ID();

	/** Use secondary title of a different post if set */
	if ( isset( $attributes["post_id"] ) && $attributes["post_id"] ) {
		$post_id = $attributes["post_id"];
	}

	/** Retrieve the secondary title */
	$secondary_title = get_secondary_title( $post_id );

	/** Allow the usage of HTML if set */
	if ( isset( $attributes["allow_html"] ) && $attributes["allow_html"] === "true" ) {
		$secondary_title = html_entity_decode( $secondary_title );
	}

	$secondary_title = apply_filters( "secondary_title_shortcode", $secondary_title );

	return (string) $secondary_title;
}

/**
 * Adds the shortcode [secondary_title allow_html="false" post_id=""]
 *
 * @since 1.9.1
 */
function secondary_title_add_shortcode_function_init(): void {
	add_shortcode( "secondary_title", "secondary_title_add_shortcode_function" );
}

add_action( "init", "secondary_title_add_shortcode_function_init" );

/**
 * Displays a dismissable donation notice in the admin area.
 *
 * @since 1.9.6
 */
function secondary_title_donation_notice(): void {
	if ( secondary_title_get_setting( "show_donation_notice" ) === "off" ) {
		return;
	}

	$disable_notice_url = wp_nonce_url(
		get_admin_url( get_current_blog_id(), "options-general.php?page=secondary-title&secondary_title_notice=off" ),
		"secondary_title_nonce_disable_donation_notice",
		"nonce"
	);
	?>
    <div id="donation-notice" class="notice notice-info">
        <h1>
			<?php _e( "Feeling generous?", "secondary-title" ); ?>
        </h1>
        <p>
			<?php
			echo sprintf(
				__(
					"<p>" . "Ah, look at that, you are using my plugin %s. Excellent choice 😉" . "</p>",
					"secondary-title"
				),
				"<strong>Secondary Title</strong> (version " . SECONDARY_TITLE_VERSION . ")"
			);

			_e(
				"<p>" . "If you want to make sure that the plugin continues to be updated to guarantee compatibility with future versions your plugins or themes, you can help by making a small donation. This will benefit not only yourself but the whole WordPress community!" . "</p>",
				"secondary-title"
			);
			?>
        </p>
        <div class="action-buttons">
            <a href="https://www.paypal.me/thaikolja/10/" target="_blank" class="button button-primary link-button"
               style="margin-right:10px;">
                <i class="fab fa-paypal" style="margin-right:5px;"></i>
				<?php _e( "Keep Secondary Title alive by Donating via PayPal", "secondary-title" ); ?>
            </a>
            <a href="<?php echo $disable_notice_url; ?>" class="button button-secondary dismiss-button">
                <i class="fa fa-times"></i>
				<?php _e( "Stop displaying this annoying notice", "secondary-title" ); ?>
            </a>
        </div>
        <br>
    </div>
	<?php
}

/**
 * Adds the action that triggers the display of
 * the donation notice.
 *
 * @since 2.0.5
 */
function secondary_title_display_donation_notice(): void {
	add_action( 'admin_notices', 'secondary_title_donation_notice' );
}

/** Call the donation notice action when on Secondary Title settings page */
add_action( 'admin_head-settings_page_secondary-title', 'secondary_title_display_donation_notice' );

/**
 * Omits the donation notice on Secondary Title's settings page
 * if all parameters are correct and the nonce could be verified.
 *
 * @since 2.0.5
 *
 */
function secondary_title_deactivate_donation_notice(): void {
	/** Don't do anything if both necessary URL parameters aren't given */
	if ( ! isset( $_GET["secondary_title_notice"], $_GET["nonce"] ) ) {
		return;
	}

	/** Don't do anything if parameters are given but the nonce couldn't be verified */
	if ( isset( $_GET["nonce"] ) && ! wp_verify_nonce( $_GET["nonce"], "secondary_title_nonce_disable_donation_notice" ) ) {
		return;
	}

	/** Check if the notice parameter is "off" */
	if ( $_GET["secondary_title_notice"] === "off" ) {
		/** Update Secondary Title's settings and omit the donation message */
		update_option(
			"secondary_title_show_donation_notice",
			"off"
		);

		/** Redirect back to the settings page */
		wp_redirect(
			get_admin_url(
				get_current_blog_id(),
				"options-general.php?page=secondary-title"
			)
		);
	}
}

add_action( "admin_init", "secondary_title_deactivate_donation_notice" );

/**
 * Adds a link to the plugin's settings page on WP's
 * "Plugins" section in the admin area.
 *
 * @param array $links The already existing links ("Disable", "Activate", ...)
 *
 * @return array
 *
 * @since 1.9.7
 */
function secondary_title_add_settings_link( array $links ): array {
	$settings_link      = "";
	$settings_page_link = admin_url( "options-general.php?page=secondary-title" );
	$link_title         = __( "Go to Secondary Title's options page", "secondary-title" );

	$settings_link .= "<a href=\"$settings_page_link\" title=\"$link_title\">";
	$settings_link .= __( "Settings", "secondary-title" );
	$settings_link .= "</a>";

	$links = array_merge(
		$links,
		[
			$settings_link
		]
	);

	return $links;
}

/**
 * Support for All in One SEO Pack plugin.
 *
 * @since 2.0.2
 */
function secondary_title_support_aioseop() {
	/** Exit if the plugin is not active */
	if ( ! defined( "AIOSEOP_VERSION" ) ) {
		return;
	}

	/** Compare the plugin verison */
	if ( version_compare( AIOSEOP_VERSION, "3.0-dev", ">=" ) ) {
		/**
		 * For version >= 3.0. Replaces the tag %secondary_title%
		 * with the secondary title of the post (if exists).
		 *
		 * @param $title_format
		 *
		 * @return string
		 *
		 * @since 2.0.2
		 */
		function secondary_title_support_aioseop_title_format( $title_format ): string {
			/** Replace the tag with the actual secondary title */
			$new_title_format = str_replace(
				"%secondary_title%",
				get_secondary_title( get_the_ID() ),
				$title_format
			);

			/** Et voilà! */
			return (string) $new_title_format;
		}

		add_action( "aioseop_title_format", "secondary_title_support_aioseop_title_format" );
	} else {
		/**
		 * For version < 3.0. Replaces the tag %secondary_title%
		 * with the secondary title of the post (if exists).
		 *
		 * @param $title
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		function secondary_title_support_aioseop_title( $title ): string {
			/** Exit this filter if All in One SEO Pack plugin is not active */
			if ( ! function_exists( "aioseop_get_options" ) || ! class_exists( "All_in_One_SEO_Pack" ) ) {
				return (string) $title;
			}

			/** Get the plugin's options */
			$aioseop_options      = aioseop_get_options();
			$rewrite_option_value = $aioseop_options["aiosp_rewrite_titles"];

			/** Exit this filter if rewrite title option is not activated */
			if ( ! $rewrite_option_value ) {
				return (string) $title;
			}

			/** Replace the tag with the actual secondary title */
			$new_title = str_replace(
				"%secondary_title%",
				get_secondary_title( get_the_ID() ),
				$title
			);

			/** Et voilà! */
			return (string) $new_title;
		}

		add_filter( "aioseop_title", "secondary_title_support_aioseop_title", 10, 1 );
	}
}

add_action( "init", "secondary_title_support_aioseop" );