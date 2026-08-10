<?php
/**
 * Front-end display rules for the secondary title.
 *
 * Empty restriction lists mean "no restriction". Non-empty lists are
 * whitelists: the post must match every configured dimension.
 *
 * Used by {@see TitleRenderer} (auto-merge) and the deprecated
 * {@see secondary_title_validate()} API.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Display-rules validator.
 *
 * @since 3.0.0
 */
final class DisplayRules {

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
	 * Returns true when the secondary title may be displayed for $post_id
	 * according to the configured post types, categories, and post IDs.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public function allows( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}

		$post_type = get_post_type( $post_id );
		if ( ! is_string( $post_type ) || '' === $post_type ) {
			return false;
		}

		$allowed_types = array_values(
			array_filter(
				array_map( 'strval', (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_TYPES, array() ) )
			)
		);

		if ( array() !== $allowed_types && ! in_array( $post_type, $allowed_types, true ) ) {
			return false;
		}

		$allowed_categories = array_values(
			array_filter(
				array_map( 'intval', (array) $this->settings_repository->get( SettingsDefaults::OPTION_CATEGORIES, array() ) ),
				static fn ( int $id ): bool => $id > 0
			)
		);

		if ( array() !== $allowed_categories ) {
			$post_categories = array_map( 'intval', (array) wp_get_post_categories( $post_id ) );
			$overlap         = array_intersect( $allowed_categories, $post_categories );
			if ( array() === $overlap ) {
				return false;
			}
		}

		$allowed_ids = array_values(
			array_filter(
				array_map( 'intval', (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_IDS, array() ) ),
				static fn ( int $id ): bool => $id > 0
			)
		);

		// Whitelist: when IDs are configured, the post must be in the list.
		if ( array() !== $allowed_ids && ! in_array( $post_id, $allowed_ids, true ) ) {
			return false;
		}

		return true;
	}
}
