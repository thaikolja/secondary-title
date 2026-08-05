<?php
/**
 * Block editor sidebar panel for the secondary title.
 *
 * Adds a `PluginDocumentSettingPanel` to the post editor's sidebar
 * with the secondary title input + a live preview of the formatted
 * output.
 *
 * The actual JavaScript lives in assets/js/src/editor/ and is built
 * by @wordpress/scripts. PHP only registers the panel and renders
 * a server-side stub (the JS re-renders the panel with up-to-date
 * post data on every change).
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Editor;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;

/**
 * Gutenberg sidebar panel for the secondary title.
 *
 * @since 3.0.0
 */
final class SidebarPanel {

	/**
	 * The block-editor plugin script handle used to register the
	 * panel. JS reads/writes the meta from the same field name
	 * used by the Classic Editor for consistency.
	 *
	 * @var string
	 */
	public const HANDLE = 'secondary-title-sidebar';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */ private readonly SettingsRepository $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings_repository Settings repository.
	 */
	public function __construct( SettingsRepository $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	/**
	 * Registers the editor script and the inline bootstrap data.
	 *
	 * Both run on `enqueue_block_editor_assets`, which only fires
	 * on block-editor screens — `edit_form_after_title` does not.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'print_bootstrap_data' ) );
	}

	/**
	 * Enqueues the editor JS bundle built by @wordpress/scripts.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->enabled_post_types(), true ) ) {
			return;
		}

		$asset_file = SECONDARY_TITLE_PATH . 'assets/js/dist/editor/editor.asset.php';
		$asset      = file_exists( $asset_file )
			? (array) include $asset_file
			: array(
				'dependencies' => array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ),
				'version'      => Plugin::VERSION,
			);

		wp_enqueue_script(
			self::HANDLE,
			SECONDARY_TITLE_URL . 'assets/js/dist/editor/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( self::HANDLE, Plugin::TEXT_DOMAIN, SECONDARY_TITLE_PATH . 'languages' );
	}

	/**
	 * Prints an inline script that pushes the current secondary
	 * title, title, and the format template into a global the JS
	 * reads to bootstrap the sidebar panel.
	 *
	 * Only runs when the block editor is active, avoiding a
	 * wasted query on Classic Editor post screens.
	 *
	 * @return void
	 */
	public function print_bootstrap_data(): void {
		if ( ! $this->is_block_editor() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->enabled_post_types(), true ) ) {
			return;
		}

		$secondary = (string) get_post_meta( $post->ID, Plugin::META_KEY, true );
		$format    = (string) $this->settings_repository->get( SettingsDefaults::OPTION_TITLE_FORMAT, SettingsDefaults::TITLE_FORMAT );

		$data = array(
			'secondaryTitle' => $secondary,
			'title'          => $post->post_title,
			'format'         => $format,
			'fieldName'      => MetaBox::FIELD_NAME,
			'metaKey'        => Plugin::META_KEY,
		);

		wp_add_inline_script(
			self::HANDLE,
			'window.SecondaryTitleBootstrap = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}

	/**
	 * Returns the post types the secondary title is enabled for.
	 *
	 * @return array<int, string>
	 */
	private function enabled_post_types(): array {
		$enabled = (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_TYPES, array() );

		if ( array() !== $enabled ) {
			return array_values( array_filter( $enabled, 'post_type_exists' ) );
		}

		$public = get_post_types( array( 'public' => true ) );
		return array_values( array_filter( $public, static fn ( string $t ): bool => 'attachment' !== $t ) );
	}

	/**
	 * Returns true when the current screen is the block editor.
	 *
	 * @return bool
	 */
	private function is_block_editor(): bool {
		global $current_screen;

		return $current_screen instanceof \WP_Screen
			&& method_exists( $current_screen, 'is_block_editor' )
			&& $current_screen->is_block_editor();
	}
}
