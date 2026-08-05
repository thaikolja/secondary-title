<?php
/**
 * Default values for the plugin's settings.
 *
 * Each option is exposed both as a class constant (so call sites can
 * reference the name without typos) and as a key in {@see self::all()}
 * (so the rest of the plugin can iterate over the whole set).
 *
 * @package Thaikolja\SecondaryTitle
 */

declare( strict_types = 1 );

namespace Thaikolja\SecondaryTitle\Settings;

/**
 * Default values for the plugin's options.
 *
 * @since 3.0.0
 */
final class Defaults {

	/**
	 * Option key: post types the secondary title is enabled for.
	 *
	 * @var string
	 */
	public const OPTION_POST_TYPES = 'secondary_title_post_types';

	/**
	 * Option key: categories the secondary title is enabled for.
	 *
	 * @var string
	 */
	public const OPTION_CATEGORIES = 'secondary_title_categories';

	/**
	 * Option key: specific post IDs the secondary title is enabled for.
	 *
	 * @var string
	 */
	public const OPTION_POST_IDS = 'secondary_title_post_ids';

	/**
	 * Option key: whether to merge the secondary title into the
	 * primary one via the `the_title` filter.
	 *
	 * @var string
	 */
	public const OPTION_AUTO_SHOW = 'secondary_title_auto_show';

	/**
	 * Option key: the title format string (with placeholders).
	 *
	 * @var string
	 */
	public const OPTION_TITLE_FORMAT = 'secondary_title_title_format';

	/**
	 * Option key: whether to limit the secondary title display
	 * to the main post (loop) only.
	 *
	 * @var string
	 */
	public const OPTION_ONLY_SHOW_IN_MAIN_POST = 'secondary_title_only_show_in_main_post';

	/**
	 * Database version. Used by the upgrader to detect v2.x.x
	 * installations and run the one-time migration.
	 *
	 * @var string
	 */
	public const OPTION_DB_VERSION = 'secondary_title_db_version';

	/**
	 * Option key: column position on the post list (left/right of
	 * the title column). Preserved from v2 for back-compat.
	 *
	 * @var string
	 */
	public const OPTION_COLUMN_POSITION = 'secondary_title_column_position';

	// "On/Off" sentinel values.
	public const ON  = 'on';
	public const OFF = 'off';

	/**
	 * Default title format.
	 *
	 * Matches the v2.x.x default so existing users see no change.
	 *
	 * @var string
	 */
	public const TITLE_FORMAT = '%secondary_title%: %title%';

	/**
	 * Returns every option key this plugin manages, mapped to its
	 * default value.
	 *
	 * The defaults are intentionally permissive: most options default
	 * to "no restriction" (empty arrays) so the plugin works
	 * out-of-the-box for any post / category / ID.
	 *
	 * The `secondary_title_db_version` key is included so the
	 * upgrader can also be triggered by a missing/invalid value
	 * (since a fresh install does not have a stored value at all).
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		return array(
			self::OPTION_POST_TYPES             => array(),
			self::OPTION_CATEGORIES             => array(),
			self::OPTION_POST_IDS               => array(),
			self::OPTION_AUTO_SHOW              => self::ON,
			self::OPTION_TITLE_FORMAT           => self::TITLE_FORMAT,
			self::OPTION_ONLY_SHOW_IN_MAIN_POST => self::OFF,
			self::OPTION_COLUMN_POSITION        => 'right',
			self::OPTION_DB_VERSION             => 0,
		);
	}

	/**
	 * Returns the default value for a single option, or null when
	 * the key is unknown.
	 *
	 * @param string $key The option key.
	 *
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		return $this->all()[ $key ] ?? null;
	}
}
