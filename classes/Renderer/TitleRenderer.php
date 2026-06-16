<?php
/**
 * `the_title` filter integration.
 *
 * When the "Auto merge" setting is on, replaces the post's title
 * with the rendered format (which contains the secondary title
 * at the configured position).
 *
 * Skips:
 *   - The admin (so admin lists and the editor are unaffected).
 *   - Feeds, where the dedicated feed filter handles the merge.
 *   - When the current post has no secondary title.
 *   - When the "Only show in main post" setting is on and the
 *     filter is called outside the main loop.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Plugin;

/**
 * Title renderer.
 *
 * @since 3.0.0
 */
final class TitleRenderer {

	/**
	 * The WordPress filter priority. Picked so that the merge
	 * happens late, after other plugins (Yoast etc.) have had
	 * their say, but before `the_title` is echoed.
	 *
	 * @var int
	 */
	public const PRIORITY = 10;

	/**
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

	/**
	 * @var Format
	 */
	private readonly Format $format;

	/**
	 * @var Placeholder
	 */
	private readonly Placeholder $placeholder;

	/**
	 * @var Wrapper
	 */
	private readonly Wrapper $wrapper;

	/**
	 * @var MetaRepository
	 */
	private readonly MetaRepository $meta_repository;

	/**
	 * @param SettingsRepository $settings_repository Settings repository.
	 * @param Format             $format              Title format.
	 * @param Placeholder        $placeholder         Placeholder tokens.
	 * @param Wrapper            $wrapper             Output wrapper.
	 * @param MetaRepository     $meta_repository     Meta repository.
	 */
	public function __construct(
		SettingsRepository $settings_repository,
		Format $format,
		Placeholder $placeholder,
		Wrapper $wrapper,
		MetaRepository $meta_repository
	) {
		$this->settings_repository = $settings_repository;
		$this->format              = $format;
		$this->placeholder         = $placeholder;
		$this->wrapper             = $wrapper;
		$this->meta_repository    = $meta_repository;
	}

	/**
	 * Registers the `the_title` filter.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'the_title', [ $this, 'filter_the_title' ], self::PRIORITY, 2 );
	}

	/**
	 * `the_title` filter callback.
	 *
	 * @param string $title    The current title.
	 * @param int    $post_id  The post ID (optional, may be 0).
	 *
	 * @return string The original or the merged title.
	 */
	public function filter_the_title( string $title, int $post_id = 0 ): string {
		// In the admin, do nothing.
		if ( is_admin() ) {
			return $title;
		}

		// Auto-show must be on.
		if ( SettingsDefaults::ON !== $this->settings_repository->get( SettingsDefaults::OPTION_AUTO_SHOW ) ) {
			return $title;
		}

		// Resolve post id.
		$post = $post_id > 0 ? get_post( $post_id ) : get_post();
		if ( ! $post instanceof \WP_Post ) {
			return $title;
		}

		// Skip if no secondary title.
		$secondary_raw = $this->meta_repository->get_raw( $post->ID );
		if ( '' === $secondary_raw ) {
			return $title;
		}

		// Only-in-main-post restriction.
		if ( SettingsDefaults::ON === $this->settings_repository->get( SettingsDefaults::OPTION_ONLY_SHOW_IN_MAIN_POST ) ) {
			global $wp_query;
			if ( ! ( $wp_query instanceof \WP_Query ) || ! $wp_query->in_the_loop ) {
				return $title;
			}
		}

		// Wrap + render.
		$secondary_wrapped = $this->wrapper->wrap( $secondary_raw );
		return $this->format->render( $title, $secondary_wrapped, $post->ID );
	}
}
