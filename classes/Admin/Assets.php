<?php
/**
 * Enqueues admin assets (CSS/JS) for the plugin's admin screens.
 *
 * Loads:
 *   - The settings page CSS + JS on the plugin's settings page.
 *   - The stylesheet on the Classic Editor post-edit and post-list
 *     screens (for the meta box and the post-list column).
 *
 * The block-editor assets are enqueued by
 * {@see \Thaikolja\SecondaryTitle\Editor\SidebarPanel}.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Admin;

use Thaikolja\SecondaryTitle\Plugin;

/**
 * Admin assets manager.
 *
 * @since 3.0.0
 */
final class Assets {

	/**
	 * The admin hook used to enqueue.
	 *
	 * @var string
	 */
	private const HOOK = 'admin_enqueue_scripts';

	/**
	 * The settings-page hook suffix.
	 *
	 * @var string
	 */
	private const SETTINGS_HOOK = 'settings_page_' . Plugin::PAGE_SLUG;

	/**
	 * CSS handle.
	 *
	 * @var string
	 */
	public const CSS_HANDLE = 'secondary-title-admin';

	/**
	 * JS handle for the settings page.
	 *
	 * @var string
	 */
	public const JS_SETTINGS_HANDLE = 'secondary-title-settings';

	/**
	 * Path to the built settings JS bundle (relative to plugin root).
	 *
	 * @var string
	 */
	private const SETTINGS_JS_PATH = 'assets/js/dist/settings/settings.js';

	/**
	 * Path to the built settings asset file.
	 *
	 * @var string
	 */
	private const SETTINGS_ASSET_PATH = 'assets/js/dist/settings/settings.asset.php';

	/**
	 * Path to the built settings CSS (compiled from admin.scss).
	 *
	 * @var string
	 */
	private const SETTINGS_CSS_PATH = 'assets/js/dist/settings/settings.css';

	/**
	 * Registers the admin_enqueue_scripts hook.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, [ $this, 'enqueue' ] );
	}

	/**
	 * `admin_enqueue_scripts` callback. Loads the right assets on
	 * the right screens.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( self::SETTINGS_HOOK === $hook_suffix ) {
			$this->enqueue_settings();
			return;
		}

		global $current_screen;

		if ( ! $current_screen instanceof \WP_Screen ) {
			return;
		}

		$base = $current_screen->base ?? '';

		if ( 'post' === $base || 'edit' === $base ) {
			$this->enqueue_editor_styles();
		}
	}

	/**
	 * Loads JS + CSS for the settings page. Version and dependencies
	 * are read from the auto-generated .asset.php file.
	 *
	 * @return void
	 */
	private function enqueue_settings(): void {
		// CSS.
		wp_enqueue_style(
			self::CSS_HANDLE,
			SECONDARY_TITLE_URL . self::SETTINGS_CSS_PATH,
			[ 'wp-components' ],
			Plugin::VERSION
		);

		// JS (with asset file).
		$asset = $this->asset_data( self::SETTINGS_ASSET_PATH );

		wp_enqueue_script(
			self::JS_SETTINGS_HANDLE,
			SECONDARY_TITLE_URL . self::SETTINGS_JS_PATH,
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations(
			self::JS_SETTINGS_HANDLE,
			Plugin::TEXT_DOMAIN,
			SECONDARY_TITLE_PATH . 'languages'
		);
	}

	/**
	 * Loads the stylesheet on the Classic Editor post-edit screen
	 * and on the post list screen. No JavaScript is loaded — the
	 * meta box is fully server-rendered, and the list-table column
	 * only needs the basic styling.
	 *
	 * @return void
	 */
	private function enqueue_editor_styles(): void {
		wp_enqueue_style(
			self::CSS_HANDLE,
			SECONDARY_TITLE_URL . self::SETTINGS_CSS_PATH,
			[],
			Plugin::VERSION
		);
	}

	/**
	 * Reads a .asset.php file and returns its `dependencies` and
	 * `version` arrays with safe fallbacks.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 *
	 * @return array{dependencies: array<int, string>, version: string}
	 */
	private function asset_data( string $relative_path ): array {
		$file = SECONDARY_TITLE_PATH . ltrim( $relative_path, '/' );

		$data = [];

		if ( file_exists( $file ) ) {
			$data = (array) include $file;
		}

		return [
			'dependencies' => $data['dependencies'] ?? [],
			'version'      => $data['version'] ?? Plugin::VERSION,
		];
	}
}
