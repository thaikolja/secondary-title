=== Secondary Title ===

Contributors:        thaikolja
Tags:                title, secondary title, subheading, heading
Tested up to:        7.2
Stable tag:          3.0.0.rc.1
Requires at least:   6.5
Requires PHP:        8.1
License:             GPLv2 or later
License URI:         https://www.gnu.org/licenses/gpl-2.0.en.html

Secondary Title adds a secondary title to posts, pages, and custom post types. Display it automatically, with a shortcode, or via a real Gutenberg block.

== Description ==

Secondary Title is a modern, lightweight plugin that adds a second title field to your WordPress posts, pages, and custom post types. The secondary title can be merged into the primary title automatically (with a configurable format), rendered via a `[secondary_title]` shortcode, or dropped onto the page as a native Gutenberg block.

The redesigned v3.0.0 settings page gives you clean toggles, a live format preview, and searchable multi-selects for post types and categories. The plugin uses the WordPress Settings API, the Twig templating engine for the admin UI, and PHP 8.1 features throughout.

**Display Options:**
* **Auto merge** – the secondary title is merged into the post title using a user-defined format (`%secondary_title%: %title%`). Supports limited HTML (`<span style="...">`).
* **Shortcode** – drop `[secondary_title]` anywhere in your content. Use the `post_id` and `allow_html` attributes to customize the output.
* **Gutenberg block** – a native `/secondary-title` canvas block renders the secondary title wherever you place it. Server-side rendered (no JavaScript required on the front end).
* **Manual PHP** – call `the_secondary_title()` or use the new `Thaikolja\SecondaryTitle\Api` facade from your theme or plugin.

== Installation ==

1. Upload the `secondary-title` folder to `/wp-content/plugins/` or install via the WordPress plugin installer.
2. Activate the plugin.
3. Go to **Settings → Secondary Title** to configure post types, categories, the title format, and more.

== Frequently Asked Questions ==

= How do I make the secondary title searchable? =
The stored value is plain text (or optionally HTML). Standard WordPress search already indexes post meta when a search plugin or a custom search query is active.

= How do I show the secondary title in my theme? =
When **Auto merge** is on, the secondary title is automatically merged into the post title. When it is off, use `the_secondary_title()` in your theme template files or insert the `[secondary_title]` shortcode.

= Is the v2.x.x API still supported? =
Yes. Every v2.x.x function (`get_secondary_title`, `the_secondary_title`, etc.) is kept as a compact wrapper in
`includes/depreciation/`. They trigger a deprecation notice and delegate to the new `Thaikolja\SecondaryTitle\Api` facade.

= Can I use HTML in the secondary title? =
Yes. The value is sanitized on save via `wp_kses_post()` (which allows limited, safe HTML). The exact allow-list is filterable via the `secondary_title_allowed_tags` hook.

= Does this plugin work with the Classic Editor? =
Yes. A proper meta box is added to the Classic Editor. The Gutenberg block editor is supported via a sidebar panel and a real canvas block.

== Changelog ==

= 3.0.0 =
* Complete rewrite. PHP 8.1 minimum, WP 6.5 minimum.
* OOP architecture with a `Thaikolja\SecondaryTitle\Api` facade.
* Settings page rebuilt with Twig templating and the WordPress Settings API.
* Live title-format preview.
* Searchable multi-selects for post types and categories on the settings page.
* Native Gutenberg sidebar panel and a real `/secondary-title` canvas block (server-side rendered).
* Classic Editor gets a proper meta box (no more jQuery injection).
* All v2.x.x functions preserved as deprecated stubs.
* Post meta key unchanged (`_secondary_title`). Zero data loss on upgrade.
* Dropped: donation notice, AIOSEO/Yoast/Rank Math integration, search rewrite, permalink tag, feed-title formatting, bundled Font Awesome, voku/anti-xss dependency.

= 2.2.0 =
* See the full changelog at https://docs.kolja-nolte.com/secondary-title/

== Upgrade Notice ==

= 3.0.0 =
Major rewrite. Post meta (`_secondary_title`) is preserved. Settings are migrated via a one-time upgrader. The old v2.x.x API functions continue to work via deprecation stubs.
