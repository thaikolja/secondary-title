# Secondary Title — Agent Reference

## Commands

| Task | Command |
|------|---------|
| Build JS/CSS | `bun run build` |
| Watch (dev) | `bun run start` |
| PHPCS lint | `vendor/bin/phpcs` |
| PHPStan | `vendor/bin/phpstan analyse` |
| PHPUnit (unit only) | `vendor/bin/phpunit --testsuite Unit` |
| PHPUnit (integration) | `vendor/bin/phpunit --testsuite Integration` (needs `WP_TESTS_DIR` env) |

`bun run build` runs `@wordpress/scripts` webpack with 3 entrypoints: `settings`, `editor`, `block`. Output lands in `assets/js/dist/` with `<entry>.asset.php` files for auto-dependency loading.

CSS lives in `assets/css/src/admin.scss` and is compiled into the settings entry.

## Architecture

```
secondary-title/           # plugin root slug = 'secondary-title'
├── secondary-title.php    # ABSPATH guard, Composer autoload, constants, Plugin::boot()
├── classes/               # OOP code — Thaikolja\SecondaryTitle\*
│   ├── Plugin.php         # singleton service container, wires all services in boot()
│   ├── Api.php            # static facade (new code calls this)
│   ├── I18n/Loader.php    # load_plugin_textdomain() on init
│   ├── Templating/        # Twig\Environment factory, WP functions/filters
│   ├── Settings/          # Options: Defaults, Repository, Sanitizer, Manager, Page
│   ├── Meta/              # Post meta: Registry, Repository, Sanitizer
│   ├── Editor/            # MetaBox, SidebarPanel, Block\Registrar, Block\ServerRender
│   ├── Renderer/          # Format, Placeholder, Wrapper, TitleRenderer, Shortcode
│   ├── Admin/             # Assets, Columns, SettingsLink, Menu, Notices
│   ├── Lifecycle/         # Activator, Deactivator, Upgrader
│   └── Support/           # Str, Arr helpers
├── includes/
│   └── depreciation/
│       └── functions.php  # v2.x procedural stubs — live forever, delegate to Api facade
├── pages/                 # Twig templates (single FilesystemLoader path)
│   ├── settings/          # page.twig, sections/, partials/
│   ├── fields/            # format-with-preview, text, multi-select-searchable
│   └── components/        # toggle, icon, card, row
└── assets/                # css/src/, js/src/{settings,editor,block}/
```

- `Plugin.php` is a **singleton service container**, not a traditional plugin class. Services are public readonly properties on the instance.
- `Api.php` is the static facade. All v2 deprecated functions in `includes/depreciation/functions.php` delegate to it via `_deprecated_function()`.
- Settings use the WP Settings API for persistence but render via Twig (no `do_settings_fields()`).

## Conventions

| Thing | Value |
|-------|-------|
| Text domain | `secondary-title` (hyphen) |
| Hook prefix | `secondary_title_` (underscore) |
| CSS class prefix | `st-` |
| Namespace | `Thaikolja\SecondaryTitle\` |
| PHP compat | 8.1+, strict_types everywhere |
| WP compat | 6.5+, tested up to 7.0 |
| Indentation | tabs for PHP/SCSS, 2 spaces for JS/JSON/Twig |

## Critical Gotchas

### Twig
- Templates live in `pages/`, loaded by a single `FilesystemLoader`. Include paths are relative to that root (e.g. `fields/text.twig`, `components/toggle.twig`).
- Cache at `wp-content/uploads/secondary-title/twig-cache/`. When `WP_DEBUG` is on, cache is **disabled**. When editing templates with `WP_DEBUG` off, **delete the cache directory** manually.
- `settings_fields()`, `do_settings_sections()`, `settings_errors()` all **echo** output. Must be wrapped in `ob_start()`/`ob_get_clean()` when used inside Twig functions.
- `submit_button()` also echoes — use `get_submit_button()` instead.
- Do NOT define a custom `raw` filter in Twig — it conflicts with Twig's built-in `raw` filter.

### Post Meta
- The meta key is `_secondary_title` (single underscore, starts with `_` so it's hidden in the Custom Fields UI). **Never change this.**
- On uninstall, post meta is **preserved** — only options are deleted.

### Options & Settings
- v2 option keys were kept 1:1 (e.g. `secondary_title_auto_show`). On upgrade, v2 values are backed up to `v2_secondary_title_*` keys, then overwritten with v3 defaults.
- The WP Settings API applies `wp_unslash()` to incoming values. The sanitizer also calls it as defense-in-depth. Do not add extra `wp_unslash()` calls — the double-unslash bug in MetaBox.php was already fixed.

### I18n
- `load_plugin_textdomain()` third argument must be a path **relative to `WP_PLUGIN_DIR`** (derived from `plugin_basename()`), not an absolute path.

### REST / Block Editor
- Meta is registered with `show_in_rest => true` and an `auth_callback`. Sanitization routes through `Meta\Sanitizer` (which uses `wp_kses_post()`).
- The sidebar panel JS uses `useSelect` to determine the post type for the REST path, with a 300ms debounce on meta updates.

### Icons
- All icons render through `components/icon.twig`, which maps icon names (e.g. `merge`, `eye`, `check`) to WordPress dashicons. There is no bundled Phosphor sprite.

### Templates
- When adding a new Twig include, do NOT use `only` unless you truly want to hide the parent context. The format preview depends on `preview.sample_title` and `preview.sample_secondary_title` being accessible from the parent context.
- Example preset buttons use `data-st-preset` attributes. The live-preview JS reads these and replaces the format input value. The placeholder chips use `data-st-placeholder` and insert at the cursor.
