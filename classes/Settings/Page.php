<?php
/**
 * Settings page: menu registration + Twig-based rendering.
 *
 * The actual HTML is composed by a Twig template (see
 * `pages/settings/page.twig`). The page relies on the standard
 * WordPress Settings API for persistence, nonces, and
 * capability checks, but the visual layout is fully under our
 * control via Twig.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Settings;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Templating\Twig as TwigEnv;
use Thaikolja\SecondaryTitle\Support\Arr;

/**
 * Settings page.
 *
 * @since 3.0.0
 */
final class Page {

	/**
	 * The menu hook used to register the page.
	 *
	 * @var string
	 */
	private const ADMIN_MENU_HOOK = 'admin_menu';

	/**
	 * The capability required to view/change the settings.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * The slug of the main settings section registered by Manager.
	 *
	 * @var string
	 */
	public const SECTION_GENERAL = 'secondary_title_section_general';

	/**
	 * The slug of the display rules settings section.
	 *
	 * @var string
	 */
	public const SECTION_DISPLAY_RULES = 'secondary_title_section_display_rules';

	/**
	 * Repository.
	 *
	 * @var Repository
	 */ private readonly Repository $repository;

	/**
	 * Twig factory.
	 *
	 * @var TwigEnv
	 */ private readonly TwigEnv $twig_factory;

	/**
	 * Constructor.
	 *
	 * @param Repository $repository  The options repository.
	 * @param TwigEnv    $twig_factory The Twig environment factory.
	 */
	public function __construct( Repository $repository, TwigEnv $twig_factory ) {
		$this->repository   = $repository;
		$this->twig_factory = $twig_factory;
	}

	/**
	 * Registers the page hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::ADMIN_MENU_HOOK, array( $this, 'add_menu' ) );
	}

	/**
	 * `admin_menu` callback. Adds the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_options_page(
			__( 'Secondary Title Settings', 'secondary-title' ),
			__( 'Secondary Title', 'secondary-title' ),
			self::CAPABILITY,
			Plugin::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * Called by WordPress as the page's render callback. Performs
	 * the capability check, then echoes the Twig-rendered HTML.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not authorized to view this page.', 'secondary-title' ) );
		}

		$context = $this->build_context();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Twig output is trusted.
		echo $this->twig_factory->create()->render( 'settings/page.twig', $context );
	}

	/**
	 * Builds the context array passed to the Twig template.
	 *
	 * @return array<string, mixed>
	 */
	private function build_context(): array {
		$all = $this->repository->all();

		return array(
			'page'               => array(
				'slug'         => Plugin::PAGE_SLUG,
				'url'          => menu_page_url( Plugin::PAGE_SLUG, false ),
				'capability'   => self::CAPABILITY,
				'heading'      => __( 'Secondary Title', 'secondary-title' ),
				'option_group' => Plugin::OPTION_GROUP,
			),
			'settings'           => $all,
			'labels'             => array(
				'save'          => __( 'Save Changes', 'secondary-title' ),
				'reset'         => __( 'Reset to defaults', 'secondary-title' ),
				'reset_confirm' => __( 'Are you sure you want to reset all settings to their defaults? This cannot be undone.', 'secondary-title' ),
				'saved'         => __( 'Settings saved.', 'secondary-title' ),
				'general'       => __( 'General', 'secondary-title' ),
				'display_rules' => __( 'Display rules', 'secondary-title' ),
				'help'          => __( 'Help', 'secondary-title' ),
			),
			// Expose option keys to the template as constants so the
			// template doesn't need to hard-code the strings.
			'keys'               => array(
				'auto_show'              => Defaults::OPTION_AUTO_SHOW,
				'title_format'           => Defaults::OPTION_TITLE_FORMAT,
				'only_show_in_main_post' => Defaults::OPTION_ONLY_SHOW_IN_MAIN_POST,
				'post_types'             => Defaults::OPTION_POST_TYPES,
				'categories'             => Defaults::OPTION_CATEGORIES,
				'post_ids'               => Defaults::OPTION_POST_IDS,
				'db_version'             => Defaults::OPTION_DB_VERSION,
			),
			// Things the template needs to render choices.
			'choices'            => array(
				'on'  => Defaults::ON,
				'off' => Defaults::OFF,
			),
			// Convenience: sample title + secondary title used by the
			// live preview when the user is not on a post page.
			'preview'            => array(
				'sample_title'           => __( 'Hello world', 'secondary-title' ),
				'sample_secondary_title' => __( 'Breaking news', 'secondary-title' ),
			),
			// Option lists for the searchable multi-selects.
			'post_types_options' => $this->post_type_options(),
			'categories_options' => $this->category_options(),
			'helpers'            => array(
				'esc_html' => 'esc_html',
				'esc_attr' => 'esc_attr',
				'esc_url'  => 'esc_url',
			),
		);
	}

	/**
	 * Returns the list of public post types as a list of
	 * { value, label, count } options, excluding `attachment`.
	 *
	 * @return array<int, array{value: string, label: string, count: int}>
	 */
	private function post_type_options(): array {
		$out    = array();
		$types  = get_post_types( array( 'public' => true ), 'objects' );
		$counts = (array) wp_count_posts();

		foreach ( $types as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}

			$count = 0;
			if ( isset( $counts[ $type->name ] ) ) {
				$count = (int) $counts[ $type->name ];
			}

			$out[] = array(
				'value' => $type->name,
				'label' => $type->labels->singular_name,
				'count' => $count,
			);
		}

		return $out;
	}

	/**
	 * Returns the list of categories as { value, label, count } options.
	 *
	 * @return array<int, array{value: int, label: string, count: int}>
	 */
	private function category_options(): array {
		$out  = array();
		$cats = get_categories( array( 'hide_empty' => false ) );

		foreach ( $cats as $cat ) {
			$out[] = array(
				'value' => (int) $cat->term_id,
				'label' => $cat->name,
				'count' => (int) $cat->count,
			);
		}

		return $out;
	}

	/**
	 * Helper for the template: returns the value of $key from the
	 * $context['settings'] array (or $fallback).
	 *
	 * Not used by the page itself but exposed as a Twig function
	 * alias. Kept here as a class method so unit tests can call it
	 * directly.
	 *
	 * @param array<string, mixed> $settings The settings array.
	 * @param string               $key      The key.
	 * @param mixed                $fallback  Default value.
	 *
	 * @return mixed
	 */
	public static function setting_value( array $settings, string $key, mixed $fallback = null ): mixed {
		return Arr::get( $settings, $key, $fallback );
	}
}
