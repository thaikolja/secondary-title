<?php
/**
 * Deprecated v2.x.x procedural API.
 *
 * Every function in this file was part of the v2.x.x public API.
 * They are kept alive so existing addons and themes don't break on
 * upgrade, but each one triggers `_deprecated_function()` and
 * delegates to the OOP facade at {@see \Thaikolja\SecondaryTitle\Api}.
 *
 * The functions live forever (per the v3.0.0 plan). New code should
 * call the static methods on the facade instead.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

use Thaikolja\SecondaryTitle\Api;
use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================
// Display: get_secondary_title, the_secondary_title,
// has_secondary_title.
// ============================================================

if ( ! function_exists( 'get_secondary_title' ) ) {
	/**
	 * Returns the secondary title of a post.
	 *
	 * @param int    $post_id      Post ID. Defaults to the current post.
	 * @param string $prefix       Text to prepend to the title.
	 * @param string $suffix       Text to append to the title.
	 * @param bool   $use_settings Whether to apply the display restrictions.
	 * @return string The secondary title, or an empty string.
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::get() instead.
	 */
	function get_secondary_title( $post_id = 0, $prefix = '', $suffix = '', $use_settings = false ): string {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::get()' );

		if ( $use_settings && ! secondary_title_validate( (int) $post_id ) ) {
			return '';
		}

		return Plugin::instance()->api->get( (int) $post_id, (string) $prefix, (string) $suffix );
	}
}

if ( ! function_exists( 'the_secondary_title' ) ) {
	/**
	 * Prints the secondary title of a post.
	 *
	 * @param int    $post_id      Post ID. Defaults to the current post.
	 * @param string $prefix       Text to prepend to the title.
	 * @param string $suffix       Text to append to the title.
	 * @param bool   $use_settings Whether to apply the display restrictions.
	 * @return void
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::get() (and echo) instead.
	 */
	function the_secondary_title( $post_id = 0, $prefix = '', $suffix = '', $use_settings = false ): void {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::get()' );

		echo get_secondary_title( $post_id, $prefix, $suffix, $use_settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'has_secondary_title' ) ) {
	/**
	 * Checks whether a post has a secondary title.
	 *
	 * @param int $post_id Post ID. Defaults to the current post.
	 * @return bool True when a secondary title exists.
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::has() instead.
	 */
	function has_secondary_title( $post_id = 0 ): bool {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::has()' );
		return Plugin::instance()->api->has( (int) $post_id );
	}
}

// ============================================================
// Lists: post types, categories, post IDs.
// ============================================================

if ( ! function_exists( 'get_secondary_title_post_ids' ) ) {
	/**
	 * Returns the post IDs the secondary title is enabled for.
	 *
	 * @return array<int, int>
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::get_enabled_post_ids() instead.
	 */
	function get_secondary_title_post_ids(): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::get_enabled_post_ids()' );
		return Plugin::instance()->api->get_enabled_post_ids();
	}
}

if ( ! function_exists( 'get_secondary_title_post_types' ) ) {
	/**
	 * Returns the post types the secondary title is enabled for.
	 *
	 * @return array<int, string>
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::get_enabled_post_types() instead.
	 */
	function get_secondary_title_post_types(): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::get_enabled_post_types()' );
		return Plugin::instance()->api->get_enabled_post_types();
	}
}

if ( ! function_exists( 'get_secondary_title_post_categories' ) ) {
	/**
	 * Returns the category IDs the secondary title is enabled for.
	 *
	 * @return array<int, int>
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::get_enabled_categories() instead.
	 */
	function get_secondary_title_post_categories(): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::get_enabled_categories()' );
		return Plugin::instance()->api->get_enabled_categories();
	}
}

if ( ! function_exists( 'get_secondary_title_filtered_post_types' ) ) {
	/**
	 * Returns all public post types.
	 *
	 * @return array<string, string>
	 *
	 * @deprecated 3.0.0 No replacement; use get_post_types() directly.
	 */
	function get_secondary_title_filtered_post_types(): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'get_post_types()' );
		return get_post_types( array( 'public' => true ) );
	}
}

// ============================================================
// Post queries.
// ============================================================

if ( ! function_exists( 'get_posts_with_secondary_title' ) ) {
	/**
	 * Returns posts that have a secondary title.
	 *
	 * @param array<string, mixed> $additional_query Extra WP_Query arguments.
	 * @return array<int, \WP_Post>
	 *
	 * @deprecated 3.0.0 Use a WP_Query with meta_key = '_secondary_title' instead.
	 */
	function get_posts_with_secondary_title( array $additional_query = array() ): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'WP_Query with meta_key meta_query' );

		$args = wp_parse_args(
			$additional_query,
			array(
				'post_type'    => 'any',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- v2 back-compat query contract.
				'meta_key'     => Plugin::META_KEY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- v2 back-compat query contract.
				'meta_value'   => ' ',
				'meta_compare' => '!=',
				'post_status'  => 'publish',
			)
		);

		return get_posts( $args );
	}
}

if ( ! function_exists( 'get_random_post_with_secondary_title' ) ) {
	/**
	 * Returns a random post that has a secondary title.
	 *
	 * @return \WP_Post|false
	 *
	 * @deprecated 3.0.0 No direct replacement; use get_posts_with_secondary_title() + shuffle.
	 */
	function get_random_post_with_secondary_title() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'get_posts_with_secondary_title()' );

		$post = get_posts_with_secondary_title(
			array(
				'showposts' => 1,
				'orderby'   => 'rand',
			)
		);

		return $post ? $post[0] : false;
	}
}

// ============================================================
// Settings accessors and mutators.
// ============================================================

if ( ! function_exists( 'secondary_title_get_default_settings' ) ) {
	/**
	 * Returns every setting and its default value.
	 *
	 * @return array<string, mixed>
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Settings\Defaults::all() instead.
	 */
	function secondary_title_get_default_settings(): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Settings\\Defaults::all()' );
		$defaults = new \Thaikolja\SecondaryTitle\Settings\Defaults();
		return $defaults->all();
	}
}

if ( ! function_exists( 'secondary_title_get_settings' ) ) {
	/**
	 * Returns every setting with its current value.
	 *
	 * @param bool $use_prefix Whether to keep the `secondary_title_` prefix.
	 * @return array<string, mixed>
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Settings\Repository::all() instead.
	 */
	function secondary_title_get_settings( bool $use_prefix = true ): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Settings\\Repository::all()' );

		$repository = Plugin::instance()->settings_repository;
		$settings   = $repository->all();

		if ( ! $use_prefix ) {
			$out = array();
			foreach ( $settings as $key => $value ) {
				$out[ str_replace( 'secondary_title_', '', (string) $key ) ] = $value;
			}
			return $out;
		}

		return $settings;
	}
}

if ( ! function_exists( 'secondary_title_get_setting' ) ) {
	/**
	 * Returns a single setting value.
	 *
	 * @param string $setting The setting name without the prefix.
	 * @return mixed
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Api::get_setting() instead.
	 */
	function secondary_title_get_setting( string $setting ) {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Api::get_setting()' );
		return Plugin::instance()->api->get_setting( $setting );
	}
}

if ( ! function_exists( 'secondary_title_update_settings' ) ) {
	/**
	 * Stores a full set of settings.
	 *
	 * @param array<string, mixed> $new_settings The settings to store.
	 * @return bool True when every setting was stored.
	 *
	 * @deprecated 3.0.0 No direct replacement. Use the WordPress Settings API
	 */
	function secondary_title_update_settings( array $new_settings = array() ): bool {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Settings\\Repository::set_many()' );

		$repository = Plugin::instance()->settings_repository;
		$failed     = $repository->set_many( $new_settings );
		return array() === $failed;
	}
}

if ( ! function_exists( 'secondary_title_install' ) ) {
	/**
	 * Re-runs the plugin activation routine.
	 *
	 * @return bool Always true.
	 *
	 * @deprecated 3.0.0 Replaced by Thaikolja\SecondaryTitle\Lifecycle\Activator.
	 */
	function secondary_title_install(): bool {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Lifecycle\\Activator' );
		Plugin::instance()->on_activate( false );
		return true;
	}
}

if ( ! function_exists( 'secondary_title_reset_settings' ) ) {
	/**
	 * Resets every setting to its default value.
	 *
	 * @return bool Always true.
	 *
	 * @deprecated 3.0.0 No direct replacement. Iterate defaults via
	 */
	function secondary_title_reset_settings(): bool {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Settings\\Defaults + Repository::set()' );

		$repository = Plugin::instance()->settings_repository;
		$defaults   = new \Thaikolja\SecondaryTitle\Settings\Defaults();

		foreach ( $defaults->all() as $key => $value ) {
			$repository->set( $key, $value );
		}

		return true;
	}
}

if ( ! function_exists( 'secondary_title_validate' ) ) {
	/**
	 * Validates whether the secondary title may be displayed.
	 *
	 * @param int $post_id Post ID.
	 * @return bool Always true (display rules moved to the renderer).
	 *
	 * @deprecated 3.0.0 No direct replacement. Display rules moved to the
	 */
	function secondary_title_validate( int $post_id ): bool {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Renderer\\DisplayRules::allows()' );
		return Plugin::instance()->display_rules->allows( $post_id );
	}
}

if ( ! function_exists( 'secondary_title_verify_admin_page' ) ) {
	/**
	 * Checks whether the current admin page is the settings page.
	 *
	 * @return bool Always true.
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Editor\MetaBox (it
	 */
	function secondary_title_verify_admin_page(): bool {
		_deprecated_function( __FUNCTION__, '3.0.0' );
		return true;
	}
}

// ============================================================
// Documentation helpers.
// ============================================================

if ( ! function_exists( 'secondary_title_documentation_url' ) ) {
	/**
	 * Builds a documentation URL.
	 *
	 * @param string $path   The documentation page path.
	 * @param string $anchor Optional anchor fragment.
	 * @return string
	 *
	 * @deprecated 3.0.0 No direct replacement; link to the docs site directly.
	 */
	function secondary_title_documentation_url( string $path, string $anchor = '' ): string {
		_deprecated_function( __FUNCTION__, '3.0.0' );
		$anchor = sanitize_title_with_dashes( $anchor );
		return 'https://docs.kolja-nolte.com/secondary-title/' . $path . '.html#' . $anchor;
	}
}

if ( ! function_exists( 'secondary_title_documentation_icon' ) ) {
	/**
	 * Prints a documentation help icon.
	 *
	 * @param string $path   The documentation page path.
	 * @param string $anchor Optional anchor fragment.
	 * @param string $icon   Optional icon name (ignored).
	 * @return void
	 *
	 * @deprecated 3.0.0 No direct replacement. Settings page UI
	 */
	function secondary_title_documentation_icon( string $path, string $anchor = '', string $icon = '' ): void {
		_deprecated_function( __FUNCTION__, '3.0.0' );
		unset( $path, $anchor, $icon );
		// No-op.
	}
}

if ( ! function_exists( 'secondary_title_print_html_info_circle' ) ) {
	/**
	 * Prints the legacy HTML info circle.
	 *
	 * @return void
	 *
	 * @deprecated 2.2.0 Already deprecated in v2.2.0. No replacement.
	 */
	function secondary_title_print_html_info_circle() {
		_deprecated_function( __FUNCTION__, '2.2.0', 'secondary_title_documentation_icon()' );
	}
}

// ============================================================
// Hook callbacks (admin link, deactivation re-arm).
// ============================================================

if ( ! function_exists( 'secondary_title_add_settings_link' ) ) {
	/**
	 * Adds the settings link to the plugins screen.
	 *
	 * @param array<int, string> $links The existing action links.
	 * @return array<int, string>
	 *
	 * @deprecated 3.0.0 Use Thaikolja\SecondaryTitle\Admin\SettingsLink.
	 */
	function secondary_title_add_settings_link( array $links ): array {
		_deprecated_function( __FUNCTION__, '3.0.0', 'Thaikolja\\SecondaryTitle\\Admin\\SettingsLink' );
		Plugin::instance()->admin_settings_link->add_link( $links );
		return $links;
	}
}

if ( ! function_exists( 'secondary_title_reset_donation_notice' ) ) {
	/**
	 * Re-arms the (removed) donation notice.
	 *
	 * @return bool Always true.
	 *
	 * @deprecated 3.0.0 No replacement. The donation notice is gone.
	 */
	function secondary_title_reset_donation_notice(): bool {
		_deprecated_function( __FUNCTION__, '3.0.0' );
		// v3 intentionally does not re-arm the donation notice.
		return true;
	}
}
