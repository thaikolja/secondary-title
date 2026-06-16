<?php
/**
 * Configures a Twig\Environment for the plugin's admin templates.
 *
 * The environment:
 *   - Looks up templates in the plugin's `pages/` directory.
 *   - Caches compiled templates in `wp-content/uploads/secondary-title/twig-cache`
 *     to keep admin page loads fast. The cache is invalidated whenever
 *     the plugin version changes.
 *   - Registers WordPress-aware functions and filters
 *     (translation, escaping, admin URLs, settings helpers, etc.).
 *   - Auto-escapes everything by default; use the `raw` filter
 *     to mark a value as already-safe HTML.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Templating;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Thaikolja\SecondaryTitle\Plugin;

/**
 * Builds a configured Twig environment for the plugin.
 *
 * @since 3.0.0
 */
final class Twig {

	/**
	 * The root directory containing `.twig` templates.
	 *
	 * @var string
	 */
	private readonly string $templates_path;

	/**
	 * @param string $templates_path Absolute filesystem path to the
	 *                                `pages/` directory.
	 */
	public function __construct( string $templates_path ) {
		$this->templates_path = $templates_path;
	}

	/**
	 * Builds and returns the Twig environment.
	 *
	 * @return Environment
	 */
	public function create(): Environment {
		$loader = new FilesystemLoader( $this->templates_path );

		$twig = new Environment(
			$loader,
			[
				'cache'            => $this->resolve_cache(),
				'auto_reload'      => $this->should_auto_reload(),
				'strict_variables' => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'autoescape'       => 'html',
			]
		);

		( new Functions( $twig ) )->register();
		( new Filters( $twig ) )->register();

		/**
		 * Fires after the plugin's Twig environment is set up. Addons
		 * can hook this to register their own functions/filters or
		 * load extensions without forking the factory.
		 *
		 * @param Environment $twig The configured Twig environment.
		 */
		do_action( 'secondary_title_twig_init', $twig );

		return $twig;
	}

	/**
	 * Resolves the Twig cache setting.
	 *
	 * When WP_DEBUG is true, cache is disabled entirely so template
	 * changes are immediately visible. In production, compiled
	 * templates are written to the filesystem and reused.
	 *
	 * @return string|false
	 */
	private function resolve_cache(): string|false {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return false;
		}

		return $this->cache_path();
	}

	/**
	 * Resolves the path to the Twig cache directory, creating it if
	 * necessary.
	 *
	 * Falls back to the system temp directory when WordPress's
	 * uploads directory is not writable (e.g. on locked-down hosts).
	 *
	 * @return string
	 */
	private function cache_path(): string {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['basedir'] ) && is_writable( $uploads['basedir'] ) ) {
			$cache = $uploads['basedir'] . '/secondary-title/twig-cache';
			wp_mkdir_p( $cache );
			return $cache;
		}

		return sys_get_temp_dir() . '/secondary-title/twig-cache';
	}

	/**
	 * Returns whether Twig should auto-reload templates on every call.
	 *
	 * Active when WP_DEBUG is on or when the plugin's version constant
	 * is "dev" / "0.0.0". In production, auto-reload is disabled and
	 * the compiled cache is reused for performance.
	 *
	 * @return bool
	 */
	private function should_auto_reload(): bool {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		return Plugin::VERSION === 'dev' || Plugin::VERSION === '0.0.0';
	}
}
