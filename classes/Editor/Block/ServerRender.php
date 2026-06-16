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
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Renderer\Format as Format;
use Thaikolja\SecondaryTitle\Renderer\Wrapper as Wrapper;
use Thaikolja\SecondaryTitle\Plugin;

/**
 * Block server-side renderer.
 *
 * @since 3.0.0
 */
final class ServerRender {

	/**
	 * @var MetaRepository
	 */
	private readonly MetaRepository $meta_repository;

	/**
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

	/**
	 * @var Format
	 */
	private readonly Format $format;

	/**
	 * @var Wrapper
	 */
	private readonly Wrapper $wrapper;

	/**
	 * @param MetaRepository     $meta_repository    Meta read/write.
	 * @param SettingsRepository  $settings_repository Settings repository.
	 * @param Format              $format              Title format.
	 * @param Wrapper             $wrapper             Output wrapper.
	 */
	public function __construct(
		MetaRepository $meta_repository,
		SettingsRepository $settings_repository,
		Format $format,
		Wrapper $wrapper
	) {
		$this->meta_repository    = $meta_repository;
		$this->settings_repository = $settings_repository;
		$this->format              = $format;
		$this->wrapper             = $wrapper;
	}

	/**
	 * The render callback registered with `register_block_type()`.
	 *
	 * @param array          $attributes Block attributes. May contain
	 *                                   `postId` (int) and
	 *                                   `wrapperTag` (string).
	 * @param string         $content    Block inner content (unused).
	 * @param WP_Block|null  $block      Block instance, may be null.
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
			return $this->empty_state();
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
	 * Renders the empty-state placeholder shown when the post has
	 * no secondary title.
	 *
	 * @return string
	 */
	private function empty_state(): string {
		return sprintf(
			'<p class="st-block-empty">%s</p>',
			esc_html__( '(no secondary title set)', 'secondary-title' )
		);
	}
}
