#!/usr/bin/env bash
#
# Regenerates languages/secondary-title.pot.
#
# WP-CLI's `make-pot` only parses .php and .js files. The settings UI is
# rendered with Twig, whose translatable strings are `__()` calls inside
# .twig templates. This script extracts those strings into a temporary
# PHP stub inside the project, runs `make-pot` over the real sources plus
# the stub, then deletes the stub. Run it after adding or changing any
# translatable string (PHP, JS, or Twig).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PLUGIN_DIR"

STUB=".twig-strings.php"
trap 'rm -f "$STUB"' EXIT

# Extract `__( '...', 'secondary-title' )` calls from all Twig templates.
# Handles single and double quotes, plus optional whitespace. The result
# must be a syntactically valid PHP file for WP-CLI's parser.
{
    echo "<?php"
    echo "/** Twig template strings, extracted for the POT file. */"
    grep -rhoE "__\(\s*['\"][^'\"]+['\"]\s*,\s*['\"]secondary-title['\"]\s*\)" pages/ \
        | sort -u
    echo "return [];"
} > "$STUB"
if [ ! -s "$STUB" ]; then
    echo "ERROR: no translatable strings found in pages/" >&2
    exit 1
fi

wp i18n make-pot . languages/secondary-title.pot \
    --slug=secondary-title \
    --domain=secondary-title \
    --ignore-domain \
    --include="classes/,includes/,pages/,assets/js/src/,secondary-title.php,$STUB" \
    2>/dev/null

# The extracted Twig strings carry the stub's file name as their source
# reference. Point them at the templates directory instead.
sed -i '' 's|\.twig-strings\.php:[0-9]*|pages/*.twig|g' languages/secondary-title.pot

echo "==> POT regenerated: $(grep -c '^msgid ' languages/secondary-title.pot) strings"
