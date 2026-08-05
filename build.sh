#!/usr/bin/env bash
#
# Builds a production-ready, installable ZIP of the Secondary Title
# plugin.
#
# Steps:
#   1. Rebuild the JS/CSS bundles via wp-scripts (`bun run build`).
#   2. Stage a clean copy of the plugin (no git, node_modules, tests,
#      build config, or asset sources).
#   3. Install the PRODUCTION composer dependencies (twig only) into
#      the staged vendor/ directory.
#   4. Verify: PHP syntax check, unit tests, PHPStan/PHPCS (warnings),
#      and a full plugin boot under the WordPress test harness.
#   5. Zip everything into dist/secondary-title-<version>.zip.
#
# Env overrides:
#   SKIP_TESTS=1       Skip the PHPUnit unit-test gate.
#   SKIP_LINT=1        Skip PHPStan/PHPCS (they only warn anyway).
#   SKIP_SMOKE=1       Skip the WP-harness boot smoke test.
#
# Requires: php, composer, zip, bun, rsync.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PLUGIN_DIR"

# ----------------------------------------------------------------------
# Resolve the plugin version from the plugin header (source of truth).
# ----------------------------------------------------------------------
VERSION="$(php -r '
$lines = file( "secondary-title.php" );
foreach ( $lines as $line ) {
    if ( preg_match( "/\*\s*Version:\s*(\S+)/", $line, $m ) ) {
        echo $m[1];
        exit;
    }
}
exit( 1 );
')"
if [ -z "$VERSION" ]; then
    echo "ERROR: could not parse the plugin version from secondary-title.php" >&2
    exit 1
fi

OUT_DIR="$PLUGIN_DIR/dist"
OUT_FILE="$OUT_DIR/secondary-title-$VERSION.zip"

echo "==> Building secondary-title $VERSION"

# ----------------------------------------------------------------------
# Tool checks
# ----------------------------------------------------------------------
for tool in php composer zip bun rsync; do
    if ! command -v "$tool" >/dev/null 2>&1; then
        echo "ERROR: required tool '$tool' not found" >&2
        exit 1
    fi
done

# ----------------------------------------------------------------------
# Warn when the working tree is dirty (you might ship uncommitted code).
# ----------------------------------------------------------------------
if [ -d .git ] && [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    echo "NOTE: working tree has uncommitted changes; the ZIP will contain them."
fi

# ----------------------------------------------------------------------
# 1. Build the JS/CSS bundles in place (committed assets regenerate).
# ----------------------------------------------------------------------
if [ ! -d node_modules ]; then
    echo "==> Installing JS dependencies (bun install)"
    bun install
fi
echo "==> Building assets (wp-scripts build)"
bun run build

# ----------------------------------------------------------------------
# 2. Stage a clean copy of the plugin.
# ----------------------------------------------------------------------
WORK="$(mktemp -d /tmp/secondary-title-build.XXXXXX)"
STAGE="$WORK/secondary-title"
mkdir -p "$STAGE"

cleanup() {
    if [ -n "${WORK:-}" ] && [ -d "$WORK" ]; then
        rm -rf "$WORK"
    fi
}
trap cleanup EXIT

echo "==> Staging plugin files"
rsync -a \
    --exclude '.git/' \
    --exclude '.github/' \
    --exclude '.DS_Store' \
    --exclude '.distignore' \
    --exclude '.editorconfig' \
    --exclude '.gitignore' \
    --exclude '.phpunit.cache/' \
    --exclude '.phpunit.result.cache' \
    --exclude 'AGENTS.md' \
    --exclude 'README.md' \
    --exclude 'bun.lock' \
    --exclude 'coverage/' \
    --exclude 'dist/' \
    --exclude 'node_modules/' \
    --exclude 'package.json' \
    --exclude 'package-lock.json' \
    --exclude 'phpcs.xml.dist' \
    --exclude 'phpstan.neon.dist' \
    --exclude 'phpunit.xml.dist' \
    --exclude 'tests/' \
    --exclude 'vendor/' \
    --exclude 'webpack.config.js' \
    --exclude 'assets/css/src/' \
    --exclude 'assets/js/src/' \
    --exclude 'build.sh' \
    ./ "$STAGE/"

# ----------------------------------------------------------------------
# 3. Production composer dependencies (twig only, no dev tooling).
# ----------------------------------------------------------------------
echo "==> Installing production composer dependencies"
(
    cd "$STAGE"
    # Give the production autoloader a unique class name so the boot
    # smoke test can load the dev autoloader (PHPUnit) side by side
    # without "Cannot redeclare class ComposerAutoloaderInit...".
    # composer.json is dev metadata and is removed from the ZIP below.
    composer config autoloader-suffix secondary_title_prod
    composer install --no-dev --no-interaction --no-progress --optimize-autoloader
)

# The staged composer files are dev metadata; keep them out of the ZIP.
rm -f "$STAGE/composer.json" "$STAGE/composer.lock"

# ----------------------------------------------------------------------
# 4. Verification gates.
# ----------------------------------------------------------------------
if [ "${SKIP_TESTS:-0}" != "1" ]; then
    echo "==> Running unit tests"
    if [ -x "$PLUGIN_DIR/vendor/bin/phpunit" ]; then
        "$PLUGIN_DIR/vendor/bin/phpunit" --testsuite unit
    else
        echo "WARNING: phpunit not installed; skipping unit-test gate"
    fi
fi

if [ "${SKIP_LINT:-0}" != "1" ]; then
    if [ -x "$PLUGIN_DIR/vendor/bin/phpstan" ]; then
        echo "==> PHPStan (warnings only; pre-existing issues do not fail the build)"
        "$PLUGIN_DIR/vendor/bin/phpstan" analyse --no-progress --memory-limit=1G || true
    fi
    if [ -x "$PLUGIN_DIR/vendor/bin/phpcs" ]; then
        echo "==> PHPCS (warnings only; pre-existing issues do not fail the build)"
        "$PLUGIN_DIR/vendor/bin/phpcs" --report=summary || true
    fi
fi

echo "==> Syntax-checking staged PHP files"
LINT_ERR="$(find "$STAGE" -name '*.php' -print0 | xargs -0 -n1 php -l 2>&1 | grep -v 'No syntax errors' || true)"
if [ -n "$LINT_ERR" ]; then
    echo "ERROR: staged plugin contains PHP files with syntax errors:" >&2
    echo "$LINT_ERR" >&2
    exit 1
fi

if [ "${SKIP_SMOKE:-0}" != "1" ]; then
    if [ -f /tmp/wordpress-tests-lib/includes/bootstrap.php ]; then
        echo "==> Booting the staged plugin under the WordPress test harness"
        SMOKE="$(mktemp /tmp/secondary-title-smoke.XXXXXX.php)"
        cat > "$SMOKE" <<PHP
<?php
// The WP test harness requires PHPUnit and the PHPUnit Polyfills at
// bootstrap. Load the source (dev) autoloader for those, then let
// the staged plugin load its own production autoloader. Composer
// autoloaders register with prepend, so the staged autoloader (loaded
// later) wins for plugin classes, while PHPUnit classes fall back to
// the dev autoloader. No redeclaration: the staged vendor was built
// with a unique autoloaders-suffix.
require '$PLUGIN_DIR/vendor/autoload.php';

define(
    'WP_TESTS_PHPUNIT_POLYFILLS_PATH',
    '$PLUGIN_DIR/vendor/yoast/phpunit-polyfills'
);

require '/tmp/wordpress-tests-lib/includes/functions.php';
tests_add_filter( 'muplugins_loaded', static function (): void {
    require '$STAGE/secondary-title.php';
} );
require '/tmp/wordpress-tests-lib/includes/bootstrap.php';
\$plugin = \Thaikolja\SecondaryTitle\Plugin::instance();
echo 'boot ok; twig=' . get_class( \$plugin->twig ) . PHP_EOL;
PHP
        SMOKE_OUT="$(php -d error_reporting=-1 "$SMOKE" 2>&1 || true)"
        rm -f "$SMOKE"
        echo "$SMOKE_OUT"
        if ! echo "$SMOKE_OUT" | grep -q 'boot ok'; then
            echo "ERROR: the staged plugin failed to boot under WordPress" >&2
            exit 1
        fi
    else
        echo "NOTE: WP test harness not found at /tmp/wordpress-tests-lib; skipping boot smoke test"
    fi
fi

# ----------------------------------------------------------------------
# 5. Zip.
# ----------------------------------------------------------------------
mkdir -p "$OUT_DIR"
rm -f "$OUT_FILE"

echo "==> Creating $OUT_FILE"
(
    cd "$WORK"
    zip -rq "$OUT_FILE" secondary-title
)

echo "==> Done: $OUT_FILE ($(du -h "$OUT_FILE" | cut -f1))"
