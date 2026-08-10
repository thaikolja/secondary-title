<?php
/**
 * Includes secondary titles in the main WordPress search query.
 *
 * When {@see SettingsDefaults::OPTION_SHOW_IN_SEARCH} is on, posts
 * whose `_secondary_title` meta matches the search terms are returned
 * alongside normal title/content matches.
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Renderer;

use Thaikolja\SecondaryTitle\Plugin;
use Thaikolja\SecondaryTitle\Settings\Defaults as SettingsDefaults;
use Thaikolja\SecondaryTitle\Settings\Repository as SettingsRepository;

/**
 * Front-end search integration.
 *
 * @since 3.0.0
 */
final class Search {

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
	 * Registers the search filters when the setting is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( SettingsDefaults::ON !== $this->settings_repository->get( SettingsDefaults::OPTION_SHOW_IN_SEARCH ) ) {
			return;
		}

		add_filter( 'posts_join', array( $this, 'join' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'where' ), 10, 2 );
		add_filter( 'posts_distinct', array( $this, 'distinct' ), 10, 2 );
	}

	/**
	 * LEFT JOINs postmeta for secondary-title search.
	 *
	 * @param string    $join  Existing JOIN clause.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function join( string $join, \WP_Query $query ): string {
		if ( ! $this->is_front_search( $query ) ) {
			return $join;
		}

		global $wpdb;

		$alias = $this->alias();
		$join .= " LEFT JOIN {$wpdb->postmeta} AS {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = '" . esc_sql( Plugin::META_KEY ) . "') ";

		return $join;
	}

	/**
	 * Extends the search WHERE clause to match meta values.
	 *
	 * @param string    $where Existing WHERE clause.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function where( string $where, \WP_Query $query ): string {
		if ( ! $this->is_front_search( $query ) ) {
			return $where;
		}

		global $wpdb;

		$alias = $this->alias();

		// Mirror WP's title LIKE clause by also matching the meta value.
		$where = preg_replace(
			"/\(\s*{$wpdb->posts}\.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			"({$wpdb->posts}.post_title LIKE $1) OR ({$alias}.meta_value LIKE $1)",
			$where
		);

		return is_string( $where ) ? $where : '';
	}

	/**
	 * Forces DISTINCT to avoid duplicate rows from the meta join.
	 *
	 * @param string    $distinct Existing DISTINCT clause.
	 * @param \WP_Query $query    Query object.
	 *
	 * @return string
	 */
	public function distinct( string $distinct, \WP_Query $query ): string {
		if ( ! $this->is_front_search( $query ) ) {
			return $distinct;
		}

		return 'DISTINCT';
	}

	/**
	 * Whether this is a front-end main search query.
	 *
	 * @param \WP_Query $query Query object.
	 *
	 * @return bool
	 */
	private function is_front_search( \WP_Query $query ): bool {
		return ! is_admin() && $query->is_main_query() && $query->is_search();
	}

	/**
	 * Stable postmeta table alias for this join.
	 *
	 * @return string
	 */
	private function alias(): string {
		return 'st_secondary_title_search';
	}
}
