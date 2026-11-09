<?php
/*
 * Copyright (C) 2026-2026 by Kolja Nolte
 * https://www.kolja-nolte.com
 * kolja.nolte@gmail.com
 *
 * All Rights Reserved.
 *
 * This code is proprietary software. You may not use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of
 * the Software without express written permission from the author.
 *
 * @author Kolja Nolte <kolja.nolte@gmail.com>
 * @date 2024-2026
 */

/**
 * Registers the secondary title post meta with WordPress.
 *
 * Called on the `init` action (via {@see self::register()}) so the
 * meta is exposed to:
 *   - The Classic Editor's "Custom Fields" meta box (hidden by the
 *     leading underscore, but available via the Custom Fields panel).
 *   - The block editor's REST API (show_in_rest => true).
 *   - The WP REST API for headless front-ends.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Meta;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Post meta registry.
 *
 * @since 3.0.0
 */
final class Registry {

	/**
	 * Hook used to register the meta.
	 *
	 * @var string
	 */
	private const HOOK = 'init';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings_repository Settings access.
	 */
	public function __construct( SettingsRepository $settings_repository ) {
		$this->settings_repository = $settings_repository;
	}

	/**
	 * Registers the WordPress hook that performs the actual
	 * `register_meta()` call.
	 *
	 * @return void
	 */
	public function register(): void {
		// After post types are registered (they use init priority 10
		// or lower) so the public-REST hide filters can be attached
		// per type.
		add_action( self::HOOK, array( $this, 'register_meta' ), 20 );
	}

	/**
	 * Performs the `register_meta()` call.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		/**
		 * `auth_callback` returns true when the current user is
		 * allowed to edit the post the meta is attached to. The
		 * single-underscore prefix on the key hides the meta from
		 * the standard "Custom Fields" UI in the post editor.
		 *
		 * `show_in_rest` stays on even when "Expose via REST API"
		 * is off. The block editor can only persist this meta
		 * through the post REST endpoint. Public responses are
		 * stripped in {@see self::hide_from_public_rest()} instead.
		 */
		register_meta(
			'post',
			Plugin::META_KEY,
			array(
				'object_subtype'    => '', // Applies to every post type.
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type' => 'string',
					),
				),
				'sanitize_callback' => static function ( $value ): string {
					// Route through the full MetaSanitizer which applies
					// wp_kses_post() — same path as the Classic Editor save.
					return ( new Sanitizer() )->sanitize( $value );
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
					unset( $allowed, $meta_key );
					return current_user_can( 'edit_post', (int) $post_id );
				},
			)
		);

		if ( SettingsDefaults::ON === $this->settings_repository->get( SettingsDefaults::OPTION_SHOW_IN_REST ) ) {
			return;
		}

		foreach ( get_post_types() as $post_type ) {
			add_filter( 'rest_prepare_' . $post_type, array( $this, 'hide_from_public_rest' ), 10, 3 );
		}
	}

	/**
	 * Removes the secondary title from public REST responses when
	 * "Expose via REST API" is off.
	 *
	 * Edit context is left intact so the block editor can read and
	 * save the value.
	 *
	 * @param mixed $response REST response.
	 * @param mixed $post     Prepared object. Unused.
	 * @param mixed $request  REST request.
	 *
	 * @return mixed
	 */
	public function hide_from_public_rest( $response, $post, $request ) {
		unset( $post );

		if ( ! $response instanceof \WP_REST_Response || ! $request instanceof \WP_REST_Request ) {
			return $response;
		}

		if ( 'edit' === $request->get_param( 'context' ) ) {
			return $response;
		}

		$data = $response->get_data();

		if ( is_array( $data ) && isset( $data['meta'][ Plugin::META_KEY ] ) ) {
			unset( $data['meta'][ Plugin::META_KEY ] );
			$response->set_data( $data );
		}

		return $response;
	}
}
