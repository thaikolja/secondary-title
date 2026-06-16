<?php
/**
 * Central coordinator for the Secondary Title plugin.
 *
 * The Plugin class is a singleton service container. It owns every
 * service in the plugin (settings repository, meta repository, renderers,
 * editor components, lifecycle handlers) and wires them together.
 *
 * It is instantiated by secondary-title.php. The single instance is
 * reachable via {@see self::instance()} from anywhere in the plugin
 * (notably from the deprecated v2 function wrappers in includes/depreciation/).
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle;

use Thaikolja\SecondaryTitle\Templating\Twig as TwigEnv;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;
use Thaikolja\SecondaryTitle\Settings\Sanitizer as SettingsSanitizer;
use Thaikolja\SecondaryTitle\Settings\Manager as SettingsManager;
use Thaikolja\SecondaryTitle\Settings\Page as SettingsPage;
use Thaikolja\SecondaryTitle\Meta\Registry as MetaRegistry;
use Thaikolja\SecondaryTitle\Meta\Repository as MetaRepository;
use Thaikolja\SecondaryTitle\Meta\Sanitizer as MetaSanitizer;
use Thaikolja\SecondaryTitle\Editor\MetaBox as ClassicMetaBox;
use Thaikolja\SecondaryTitle\Editor\SidebarPanel as GutenbergSidebar;
use Thaikolja\SecondaryTitle\Editor\Block\Registrar as BlockRegistrar;
use Thaikolja\SecondaryTitle\Editor\Block\ServerRender as BlockServerRender;
use Thaikolja\SecondaryTitle\Admin\Assets as AdminAssets;
use Thaikolja\SecondaryTitle\Admin\Menu as AdminMenu;
use Thaikolja\SecondaryTitle\Admin\SettingsLink as AdminSettingsLink;
use Thaikolja\SecondaryTitle\Admin\Columns as AdminColumns;
use Thaikolja\SecondaryTitle\Admin\Notices as AdminNotices;
use Thaikolja\SecondaryTitle\I18n\Loader as I18nLoader;
use Thaikolja\SecondaryTitle\Lifecycle\Activator as Activator;
use Thaikolja\SecondaryTitle\Lifecycle\Deactivator as Deactivator;
use Thaikolja\SecondaryTitle\Lifecycle\Upgrader as Upgrader;
use Thaikolja\SecondaryTitle\Renderer\Format as Format;
use Thaikolja\SecondaryTitle\Renderer\Placeholder as Placeholder;
use Thaikolja\SecondaryTitle\Renderer\Wrapper as Wrapper;
use Thaikolja\SecondaryTitle\Renderer\TitleRenderer;
use Thaikolja\SecondaryTitle\Renderer\Shortcode as ShortcodeRenderer;

/**
 * The Plugin class.
 *
 * Wires together every service the plugin needs and registers the
 * WordPress hooks that bring them to life.
 *
 * @since 3.0.0
 */
final class Plugin {

	/**
	 * The single shared instance of the Plugin.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Loaded text domain.
	 *
	 * @var string
	 */
	public const TEXT_DOMAIN = 'secondary-title';

	/**
	 * The post meta key used to persist the secondary title.
	 *
	 * Single underscore is intentional: it hides the meta from the
	 * standard Custom Fields UI in the post editor.
	 *
	 * @var string
	 */
	public const META_KEY = '_secondary_title';

	/**
	 * The settings option group used by the Settings API.
	 *
	 * @var string
	 */
	public const OPTION_GROUP = 'secondary_title';

	/**
	 * The settings page slug (used in admin URLs and Settings API callbacks).
	 *
	 * @var string
	 */
	public const PAGE_SLUG = 'secondary-title';

	/**
	 * Current plugin version, mirrored from the SECONDARY_TITLE_VERSION
	 * constant in the main plugin file. Stored as a class constant for
	 * convenient access from any service without using the global
	 * constant directly.
	 *
	 * @var string
	 */
	public const VERSION = SECONDARY_TITLE_VERSION;

	// ============================================================
	// Services. New services are added as their phases land.
	// ============================================================

	public readonly TwigEnv $twig;
	public readonly Placeholder $placeholder;
	public readonly Wrapper $wrapper;
	public readonly SettingsDefaults $settings_defaults;
	public readonly SettingsRepository $settings_repository;
	public readonly SettingsSanitizer $settings_sanitizer;
	public readonly SettingsManager $settings_manager;
	public readonly SettingsPage $settings_page;
	public readonly Format $format;
	public readonly MetaSanitizer $meta_sanitizer;
	public readonly MetaRepository $meta_repository;
	public readonly MetaRegistry $meta_registry;
	public readonly ClassicMetaBox $classic_meta_box;
	public readonly GutenbergSidebar $gutenberg_sidebar;
	public readonly BlockServerRender $block_server_render;
	public readonly BlockRegistrar $block_registrar;
	public readonly TitleRenderer $title_renderer;
	public readonly ShortcodeRenderer $shortcode_renderer;
	public readonly AdminAssets $admin_assets;
	public readonly AdminMenu $admin_menu;
	public readonly AdminSettingsLink $admin_settings_link;
	public readonly AdminColumns $admin_columns;
	public readonly AdminNotices $admin_notices;
	public readonly I18nLoader $i18n;
	public readonly Activator $activator;
	public readonly Deactivator $deactivator;
	public readonly Upgrader $upgrader;
	public readonly Api $api;

	/**
	 * Private constructor. Use {@see self::instance()} instead.
	 *
	 * Services are constructed bottom-up so each one only depends on
	 * services that have already been built. The order of the
	 * assignments below encodes the dependency graph.
	 */
	private function __construct() {
		// 1. No-dep infrastructure
		$this->twig              = new TwigEnv( SECONDARY_TITLE_PATH . 'pages' );
		$this->placeholder       = new Placeholder();
		$this->wrapper           = new Wrapper();

		// 2. Settings
		$this->settings_defaults  = new SettingsDefaults();
		$this->settings_repository = new SettingsRepository( $this->settings_defaults );
		$this->settings_sanitizer = new SettingsSanitizer( $this->settings_defaults );
		$this->settings_manager   = new SettingsManager( $this->settings_repository, $this->settings_sanitizer );
		$this->settings_page      = new SettingsPage( $this->settings_repository, $this->twig );

		// 3. Meta
		$this->meta_sanitizer     = new MetaSanitizer();
		$this->meta_repository    = new MetaRepository( $this->meta_sanitizer );

		// 4. Renderer value objects
		$this->format             = new Format( $this->settings_repository );

		// 5. Meta registry (depends on WP's register_meta, called on init)
		$this->meta_registry      = new MetaRegistry();

		// 6. Editor
		$this->classic_meta_box   = new ClassicMetaBox( $this->meta_repository, $this->meta_sanitizer, $this->settings_repository, $this->format, $this->wrapper );
		$this->gutenberg_sidebar  = new GutenbergSidebar( $this->meta_repository, $this->format, $this->wrapper, $this->settings_repository );
		$this->block_server_render = new BlockServerRender( $this->meta_repository, $this->settings_repository, $this->format, $this->wrapper );
		$this->block_registrar    = new BlockRegistrar( $this->block_server_render );

		// 7. Renderer
		$this->title_renderer     = new TitleRenderer( $this->settings_repository, $this->format, $this->placeholder, $this->wrapper, $this->meta_repository );
		$this->shortcode_renderer = new ShortcodeRenderer( $this->meta_repository );

		// 8. Admin
		$this->admin_assets        = new AdminAssets();
		$this->admin_menu          = new AdminMenu();
		$this->admin_settings_link = new AdminSettingsLink();
		$this->admin_columns       = new AdminColumns( $this->settings_repository, $this->meta_repository, $this->wrapper );
		$this->admin_notices       = new AdminNotices();

		// 9. i18n
		$this->i18n                = new I18nLoader();

		// 10. Lifecycle
		$this->activator          = new Activator( $this->settings_defaults, $this->settings_repository );
		$this->deactivator        = new Deactivator();
		$this->upgrader           = new Upgrader( $this->settings_repository, $this->settings_defaults );

		// 11. Public API facade
		$this->api                = new Api( $this->settings_repository, $this->meta_repository, $this->format, $this->wrapper );
	}

	/**
	 * Returns the shared instance, creating it on first call.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		return self::$instance ??= new self();
	}

	/**
	 * Boots the plugin: registers activation/deactivation hooks and
	 * schedules the main runtime wiring for the `plugins_loaded` action.
	 *
	 * Called once by secondary-title.php.
	 *
	 * @return void
	 */
	public function boot(): void {
		$plugin_file = SECONDARY_TITLE_PATH . 'secondary-title.php';

		register_activation_hook( $plugin_file, [ $this, 'on_activate' ] );
		register_deactivation_hook( $plugin_file, [ $this, 'on_deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
	}

	/**
	 * Stub for the activation handler. Filled in during the lifecycle phase.
	 *
	 * @param bool $network_wide Whether this is a network-wide activation (Multisite).
	 *
	 * @return void
	 */
	public function on_activate( bool $network_wide = false ): void {
		$this->activator->activate( $network_wide );
	}

	/**
	 * Stub for the deactivation handler. Filled in during the lifecycle phase.
	 *
	 * @param bool $network_wide Whether this is a network-wide deactivation (Multisite).
	 *
	 * @return void
	 */
	public function on_deactivate( bool $network_wide = false ): void {
		$this->deactivator->deactivate( $network_wide );
	}

	/**
	 * The main wiring point. Runs on the `plugins_loaded` action.
	 *
	 * Services register their WordPress hooks here. As more services
	 * are added in subsequent phases, their `register()` calls are
	 * added to this method.
	 *
	 * @return void
	 */
	public function on_plugins_loaded(): void {
		// Settings: must run first so the options exist before the
		// rest of the plugin reads them.
		$this->settings_manager->register();
		$this->settings_page->register();

		// Meta: the registry registers `_secondary_title` so the
		// block editor and REST API see it.
		$this->meta_registry->register();

		// Editor: Classic Editor meta box, block-editor sidebar,
		// and the /secondary-title canvas block.
		$this->classic_meta_box->register();
		$this->gutenberg_sidebar->register();
		$this->block_registrar->register();

		// Renderer: the_title filter and the [secondary_title] shortcode.
		$this->title_renderer->register();
		$this->shortcode_renderer->register();

		// Admin: assets, settings link, columns. Menu is reserved.
		$this->admin_assets->register();
		$this->admin_menu->register();
		$this->admin_settings_link->register();
		$this->admin_columns->register();
		$this->admin_notices->register();

		// i18n: load the text domain.
		$this->i18n->register();

		// Lifecycle: run the upgrader before anything else reads options.
		$this->upgrader->register();
	}
}
