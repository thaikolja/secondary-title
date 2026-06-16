<?php
/**
 * Adds a "Settings" link to the plugin row in the Plugins admin page.
 *
 * Back-compat: kept the same `plugin_action_links_` filter and the
 * same label/style as v2.x.x so users upgrading see no change.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Admin;

use Thaikolja\SecondaryTitle\Plugin;

/**
 * Plugin row "Settings" link.
 *
 * @since 3.0.0
 */
final class SettingsLink {

	/**
	 * The hook for the row actions filter.
	 *
	 * `plugin_basename()` produces the stable key regardless of
	 * the directory name, so the link survives a plugin rename.
	 *
	 * @var string
	 */
	private const HOOK = ''; // Assigned in register().

	/**
	 * @return void
	 */
	public function register(): void {
		$hook = 'plugin_action_links_' . plugin_basename( SECONDARY_TITLE_PATH . 'secondary-title.php' );
		add_filter( $hook, [ $this, 'add_link' ] );
	}

	/**
	 * `plugin_action_links_*` callback.
	 *
	 * @param array<int, string> $links The current action links.
	 *
	 * @return array<int, string> The augmented links.
	 */
	public function add_link( array $links ): array {
		$url   = admin_url( 'options-general.php?page=' . Plugin::PAGE_SLUG );
		$label = __( 'Settings', 'secondary-title' );
		$title = __( "Go to Secondary Title's options page", 'secondary-title' );

		$link = sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_html( $label )
		);

		array_unshift( $links, $link );

		return $links;
	}
}
