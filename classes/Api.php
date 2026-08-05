<?php
/**
 * Static API facade.
 *
 * New code should call the static methods on this class instead
 * of the v2.x.x procedural functions in `includes/`. The procedural
 * functions are kept as thin wrappers that trigger
 * `_deprecated_function()` and delegate to the facade.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle;

use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Renderer\Wrapper;

/**
 * Static API facade.
 *
 * @since 3.0.0
 */
final class Api {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */ private readonly SettingsRepository $settings_repository;

	/**
	 * Meta repository.
	 *
	 * @var MetaRepository
	 */ private readonly MetaRepository $meta_repository;

	/**
	 * Wrapper.
	 *
	 * @var Wrapper
	 */ private readonly Wrapper $wrapper;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings_repository Settings repository.
	 * @param MetaRepository     $meta_repository     Meta repository.
	 * @param Wrapper            $wrapper             Output wrapper.
	 */
	public function __construct(
		SettingsRepository $settings_repository,
		MetaRepository $meta_repository,
		Wrapper $wrapper
	) {
		$this->settings_repository = $settings_repository;
		$this->meta_repository     = $meta_repository;
		$this->wrapper             = $wrapper;
	}

	/**
	 * Returns the secondary title for $post_id (auto-merge context).
	 *
	 * Applies the configured "auto-show" + "only in main post"
	 * settings. When the merge is disabled or the post fails the
	 * validation, returns an empty string.
	 *
	 * @param int    $post_id      Post ID. Default: current post.
	 * @param string $prefix       HTML prefix (e.g. '<strong>').
	 * @param string $suffix       HTML suffix (e.g. '</strong>').
	 *
	 * @return string
	 */
	public function get( int $post_id = 0, string $prefix = '', string $suffix = '' ): string {
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return '';
		}

		$raw = $this->meta_repository->get_raw( $post_id );
		if ( '' === $raw ) {
			return '';
		}

		$wrapped = $this->wrapper->wrap( $raw );
		return $prefix . $wrapped . $suffix;
	}

	/**
	 * Returns true when $post_id has a non-empty secondary title.
	 *
	 * @param int $post_id Post ID. Default: current post.
	 *
	 * @return bool
	 */
	public function has( int $post_id = 0 ): bool {
		return '' !== $this->meta_repository->get_raw( $post_id );
	}

	/**
	 * Renders the merged title (the_title context). When auto-show
	 * is on and the post qualifies, returns the format-substituted
	 * title. Otherwise, returns the original $title.
	 *
	 * Delegates to the title-renderer service to keep the rendering
	 * logic in a single place.
	 *
	 * @param string $title    Original title.
	 * @param int    $post_id  Post ID.
	 *
	 * @return string
	 */
	public function render_title( string $title, int $post_id = 0 ): string {
		return Plugin::instance()->title_renderer->filter_the_title( $title, $post_id );
	}

	/**
	 * Returns the list of post types the secondary title is enabled for.
	 *
	 * @return array<int, string>
	 */
	public function get_enabled_post_types(): array {
		return (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_TYPES, array() );
	}

	/**
	 * Returns the list of category IDs the secondary title is enabled for.
	 *
	 * @return array<int, int>
	 */
	public function get_enabled_categories(): array {
		return array_map( 'intval', (array) $this->settings_repository->get( SettingsDefaults::OPTION_CATEGORIES, array() ) );
	}

	/**
	 * Returns the list of post IDs the secondary title is enabled for.
	 *
	 * @return array<int, int>
	 */
	public function get_enabled_post_ids(): array {
		return array_map( 'intval', (array) $this->settings_repository->get( SettingsDefaults::OPTION_POST_IDS, array() ) );
	}

	/**
	 * Returns the configured title format.
	 *
	 * @return string
	 */
	public function get_title_format(): string {
		return (string) $this->settings_repository->get( SettingsDefaults::OPTION_TITLE_FORMAT, SettingsDefaults::TITLE_FORMAT );
	}

	/**
	 * Returns whether auto-merge is on.
	 *
	 * @return bool
	 */
	public function is_auto_show(): bool {
		return SettingsDefaults::ON === $this->settings_repository->get( SettingsDefaults::OPTION_AUTO_SHOW );
	}

	/**
	 * Returns a single setting value.
	 *
	 * @param string $key     The option key (with or without `secondary_title_` prefix).
	 * @param mixed  $fallback Default if the key is missing.
	 *
	 * @return mixed
	 */
	public function get_setting( string $key, mixed $fallback = null ): mixed {
		$full = str_starts_with( $key, 'secondary_title_' ) ? $key : 'secondary_title_' . $key;
		return $this->settings_repository->get( $full, $fallback );
	}
}
