<?php
/**
 * Registers the `/secondary-title` Gutenberg block.
 *
 * The block is a real, drop-in canvas block: it renders the
 * secondary title wherever the author places it (server-side
 * rendered, so no JavaScript is required on the front end). The
 * block's edit UI is built in assets/js/src/block/edit.js.
 *
 * The block type is registered via a `block.json` manifest living
 * next to the build output, with the server render callback
 * provided programmatically.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Editor\Block;

use Thaikolja\SecondaryTitle\Plugin;

/**
 * Block type registrar.
 *
 * @since 3.0.0
 */
final class Registrar {

	/**
	 * The block name (without the `namespace/` prefix).
	 *
	 * @var string
	 */
	public const NAME = 'secondary-title/secondary-title';

	/**
	 * Hook used to register the block.
	 *
	 * @var string
	 */
	private const HOOK = 'init';

	/**
	 * Server render.
	 *
	 * @var ServerRender
	 */ private readonly ServerRender $server_render;

	/**
	 * Constructor.
	 *
	 * @param ServerRender $server_render The PHP render callback.
	 */
	public function __construct( ServerRender $server_render ) {
		$this->server_render = $server_render;
	}

	/**
	 * Registers the WordPress hook that performs the actual
	 * `register_block_type()` call.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'register_block' ) );
	}

	/**
	 * Performs the registration.
	 *
	 * @return void
	 */
	public function register_block(): void {
		$dir = SECONDARY_TITLE_PATH . 'assets/js/dist/block';

		/**
		 * The block.json lives at assets/js/dist/block/ and the
		 * build pipeline emits both the JSON and the matching
		 * render.js (client side). When the build hasn't run yet
		 * (e.g. during development), `register_block_type` silently
		 * fails — the block is simply unavailable.
		 */
		if ( ! file_exists( $dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $dir, array( 'render_callback' => array( $this->server_render, 'render' ) ) );
	}
}
