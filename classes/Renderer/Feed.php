<?php
/**
 * Merges the secondary title into feed (RSS/Atom) titles.
 *
 * When {@see SettingsDefaults::OPTION_SHOW_IN_RSS} is on, feed item
 * titles use the same format template as front-end auto-merge.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Feed title integration.
 *
 * @since 3.0.0
 */
final class Feed {

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private readonly SettingsRepository $settings_repository;

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
	 * Meta repository.
	 *
	 * @var MetaRepository
	 */
	private readonly MetaRepository $meta_repository;

	/**
	 * Display rules.
	 *
	 * @var DisplayRules
	 */
	private readonly DisplayRules $display_rules;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings_repository Settings access.
	 * @param Format             $format              Title format.
	 * @param Wrapper            $wrapper             Output wrapper.
	 * @param MetaRepository     $meta_repository     Meta access.
	 * @param DisplayRules       $display_rules       Display restrictions.
	 */
	public function __construct(
		SettingsRepository $settings_repository,
		Format $format,
		Wrapper $wrapper,
		MetaRepository $meta_repository,
		DisplayRules $display_rules
	) {
		$this->settings_repository = $settings_repository;
		$this->format              = $format;
		$this->wrapper             = $wrapper;
		$this->meta_repository     = $meta_repository;
		$this->display_rules       = $display_rules;
	}

	/**
	 * Registers the feed title filter when the setting is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( SettingsDefaults::ON !== $this->settings_repository->get( SettingsDefaults::OPTION_SHOW_IN_RSS ) ) {
			return;
		}

		add_filter( 'the_title_rss', array( $this, 'filter_title' ), 10, 1 );
	}

	/**
	 * `the_title_rss` callback.
	 *
	 * @param string $title Current feed title.
	 *
	 * @return string
	 */
	public function filter_title( string $title ): string {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return $title;
		}

		if ( ! $this->display_rules->allows( (int) $post->ID ) ) {
			return $title;
		}

		$secondary = $this->meta_repository->get_raw( (int) $post->ID );
		if ( '' === $secondary ) {
			return $title;
		}

		// Feeds should not emit HTML wrappers; strip tags after format render.
		$wrapped = $this->wrapper->wrap( $secondary );
		$merged  = $this->format->render( $title, $wrapped, (int) $post->ID );

		return wp_strip_all_tags( $merged );
	}
}
