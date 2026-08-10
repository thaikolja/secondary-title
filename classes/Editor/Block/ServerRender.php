<?php
/**
 * Server-side render callback for the `/secondary-title` block.
 *
 * Looks up the secondary title for the current post (or the
 * explicit `postId` attribute), wraps it via {@see Wrapper}, and
 * runs it through {@see Format::render()} using the configured
 * title format.
 *
 * This class is the render callback declared in `block.json`. It
 * is called by WordPress when the block is rendered on the
 * front end (PHP) and inside `ServerSideRender` in the editor.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Editor\Block;

use WP_Block;
use WP_Post;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Renderer\Format;
use Thaikolja\SecondaryTitle\Renderer\Wrapper;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Block server-side renderer.
 *
 * @since 3.0.0
 */
final class ServerRender {

	/**
	 * Meta repository.
	 *
	 * @var MetaRepository
	 */
	private readonly MetaRepository $meta_repository;

	/**
	 * Format.
	 *
	 * @var Format
	 */
	private readonly Format $format;

	/**
	 * Wrapper.
	 *
	 * @var Wrapper
	 */
	private readonly Wrapper $wrapper;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param MetaRepository     $meta_repository     Meta read/write.
	 * @param Format             $format              Title format.
	 * @param Wrapper            $wrapper             Output wrapper.
	 * @param SettingsRepository $settings_repository Settings access.
	 */
	public function __construct(
		MetaRepository $meta_repository,
		Format $format,
		Wrapper $wrapper,
		SettingsRepository $settings_repository
	) {
		$this->meta_repository     = $meta_repository;
		$this->format              = $format;
		$this->wrapper             = $wrapper;
		$this->settings_repository = $settings_repository;
	}

	/**
	 * The render callback registered with `register_block_type()`.
	 *
	 * @param array<int|string, mixed> $attributes Block attributes. May contain
	 *                                  `postId` (int) and
	 *                                  `wrapperTag` (string).
	 * @param string                   $content    Block inner content (unused).
	 * @param WP_Block|null            $block      Block instance, may be null.
	 *
	 * @return string Rendered HTML.
	 */
	public function render( array $attributes, string $content, ?WP_Block $block = null ): string {
		unset( $content, $block );

		$post_id = (int) ( $attributes['postId'] ?? 0 );
		if ( $post_id <= 0 ) {
			$post_id = (int) get_the_ID();
		}

		if ( $post_id <= 0 ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$secondary = $this->meta_repository->get_raw( $post_id );

		if ( '' === $secondary ) {
			return $this->empty_state( $post );
		}

		/**
		 * Filters the secondary title before it is substituted into
		 * the format on block render. Addons can use this to
		 * rewrite or translate the value.
		 *
		 * @param string $secondary The raw secondary title.
		 * @param int    $post_id   The post ID.
		 */
		$secondary = (string) apply_filters( 'secondary_title_source', $secondary, $post_id );

		$wrapped = $this->wrapper->wrap( $secondary );

		return $this->format->render( $post->post_title, $wrapped, $post_id );
	}

	/**
	 * Renders the empty-state output when the post has no secondary title.
	 *
	 * @param WP_Post $post The post.
	 *
	 * @return string
	 */
	private function empty_state( WP_Post $post ): string {
		$behaviour = (string) $this->settings_repository->get( SettingsDefaults::OPTION_EMPTY_BEHAVIOUR );

		if ( SettingsDefaults::EMPTY_PRIMARY === $behaviour ) {
			return esc_html( $post->post_title );
		}

		// hide (default): nothing on the front end; editor still benefits from a hint via empty string.
		return '';
	}
}
