#!/usr/bin/env bash
#
# Installs the WordPress test suite (test library + test database).
# Adapted from the wp-cli/scaffold-command package (MIT).
#
# Usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
#   wp-version: 'latest', a version number (e.g. 6.5), or 'trunk' (default: latest).
#
# Env overrides:
#   WP_TESTS_DIR  Test library path (default: $TMPDIR/wordpress-tests-lib)
#   WP_CORE_DIR   WordPress core path (default: $TMPDIR/wordpress)
#   SKIP_DB_CREATE=1  Skip creating the test database
#
# Used by .github/workflows/ci.yml and local integration runs.
set -euo pipefail

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]" >&2
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

TMPDIR=${TMPDIR:-/tmp}
# Normalize trailing slash quirks.
TMPDIR="${TMPDIR%/}"
WP_TESTS_DIR=${WP_TESTS_DIR:-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-$TMPDIR/wordpress}

download() {
	if command -v curl >/dev/null 2>&1; then
		curl -sS -o "$2" "$1"
	else
		wget -q -O "$2" "$1"
	fi
}

sed_inplace() {
	# Portable in-place sed (GNU vs BSD/macOS).
	if sed --version >/dev/null 2>&1; then
		sed -i "$@"
	else
		sed -i '' "$@"
	fi
}

install_wp() {
	if [ -f "$WP_CORE_DIR/wp-settings.php" ]; then
		echo "WordPress core already present at $WP_CORE_DIR"
		return
	fi

	mkdir -p "$WP_CORE_DIR"

	if [ "$WP_VERSION" = 'latest' ] || [ "$WP_VERSION" = 'trunk' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	echo "Downloading WordPress ($ARCHIVE_NAME)..."
	download "https://wordpress.org/${ARCHIVE_NAME}.tar.gz" "$TMPDIR/wordpress.tar.gz"
	tar --strip-components=1 -zxmf "$TMPDIR/wordpress.tar.gz" -C "$WP_CORE_DIR"
	rm -f "$TMPDIR/wordpress.tar.gz"
}

install_test_suite() {
	# Full suite layout:
	#   $WP_TESTS_DIR/includes/*
	#   $WP_TESTS_DIR/data/*
	#   $WP_TESTS_DIR/wp-tests-config.php
	if [ -f "$WP_TESTS_DIR/includes/bootstrap.php" ] && [ -d "$WP_TESTS_DIR/data" ]; then
		echo "Test suite already present at $WP_TESTS_DIR"
	else
		rm -rf "$WP_TESTS_DIR"
		mkdir -p "$WP_TESTS_DIR"

		if [ "$WP_VERSION" = 'trunk' ]; then
			local BRANCH='trunk'
		elif [ "$WP_VERSION" = 'latest' ]; then
			local BRANCH='trunk'
		elif [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+ ]]; then
			# Tag style: 6.5 or 6.5.3 → branches/6.5 when possible, else trunk.
			local BRANCH="branches/${WP_VERSION%.*}"
			if [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
				BRANCH="branches/$WP_VERSION"
			fi
		else
			local BRANCH='trunk'
		fi

		echo "Checking out test suite from WordPress develop ($BRANCH)..."
		if command -v svn >/dev/null 2>&1; then
			svn export --quiet --force "https://develop.svn.wordpress.org/${BRANCH}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
			svn export --quiet --force "https://develop.svn.wordpress.org/${BRANCH}/tests/phpunit/data/" "$WP_TESTS_DIR/data"
		else
			# Fallback: sparse git clone of wordpress-develop.
			local CLONE="$TMPDIR/wordpress-develop-clone"
			rm -rf "$CLONE"
			git clone --depth 1 --filter=blob:none --sparse \
				https://github.com/WordPress/wordpress-develop.git "$CLONE"
			(
				cd "$CLONE"
				git sparse-checkout set tests/phpunit/includes tests/phpunit/data
			)
			cp -R "$CLONE/tests/phpunit/includes" "$WP_TESTS_DIR/includes"
			cp -R "$CLONE/tests/phpunit/data" "$WP_TESTS_DIR/data"
			rm -rf "$CLONE"
		fi
	fi

	# Config sample → live config next to the suite (bootstrap looks here first).
	if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
		download "https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php" \
			"$WP_TESTS_DIR/wp-tests-config.php" || \
			download "https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/wp-tests-config-sample.php" \
				"$WP_TESTS_DIR/wp-tests-config.php"
	fi

	# Write a clean, known-good config (avoids sed/quote portability traps).
	cat > "$WP_TESTS_DIR/wp-tests-config.php" <<PHP
<?php
define( 'ABSPATH', '${WP_CORE_DIR%/}/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );
define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
\$table_prefix = 'wptests_';
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
PHP

	# Mirror config into core dir for tools that look there.
	cp "$WP_TESTS_DIR/wp-tests-config.php" "$WP_CORE_DIR/wp-tests-config.php"
}

install_db() {
	if [ "${SKIP_DB_CREATE:-0}" = '1' ]; then
		return 0
	fi

	local EXTRA=()
	if [[ "$DB_HOST" == *:* ]]; then
		local DB_HOSTNAME="${DB_HOST%%:*}"
		local DB_SOCK_OR_PORT="${DB_HOST#*:}"
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]]; then
			EXTRA=(--host="$DB_HOSTNAME" --port="$DB_SOCK_OR_PORT" --protocol=tcp)
		elif [ -n "$DB_SOCK_OR_PORT" ]; then
			EXTRA=(--socket="$DB_SOCK_OR_PORT")
		fi
	else
		EXTRA=(--host="$DB_HOST")
	fi

	echo "Creating database $DB_NAME..."
	if [ -n "$DB_PASS" ]; then
		mysqladmin "${EXTRA[@]}" --user="$DB_USER" --password="$DB_PASS" drop "$DB_NAME" --force >/dev/null 2>&1 || true
		mysqladmin "${EXTRA[@]}" --user="$DB_USER" --password="$DB_PASS" create "$DB_NAME" --force
	else
		mysqladmin "${EXTRA[@]}" --user="$DB_USER" drop "$DB_NAME" --force >/dev/null 2>&1 || true
		mysqladmin "${EXTRA[@]}" --user="$DB_USER" create "$DB_NAME" --force
	fi
}

echo "==> Installing WordPress test suite"
echo "    WP_CORE_DIR=$WP_CORE_DIR"
echo "    WP_TESTS_DIR=$WP_TESTS_DIR"

install_wp
install_test_suite
install_db

echo "==> Done. Run integration tests with:"
echo "    WP_TESTS_DIR=$WP_TESTS_DIR WP_CORE_DIR=$WP_CORE_DIR vendor/bin/phpunit --testsuite integration"
