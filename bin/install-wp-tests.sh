#!/usr/bin/env bash
#
# Installs the WordPress test suite (test library + test database).
# Canonical script from the wp-cli/scaffold-command package (MIT).
#
# Usage: bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
#   wp-version: 'latest', a version number, or a git ref (default: latest).
#
# Used by .github/workflows/ci.yml to run the integration test suite.
set -euxo pipefail

if [ $# -lt 3 ]; then
    echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]" >&2
    exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

TMPDIR=${TMPDIR-/tmp}
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-$TMPDIR/wordpress}

download() {
    if [ -f "$2" ]; then
        echo "Already downloaded: $2"
    elif command -v curl >/dev/null 2>&1; then
        curl -sS -o "$2" "$1"
    else
        wget -q -O "$2" "$1"
    fi
}

install_wp() {
    if [ -d "$WP_CORE_DIR" ]; then
        return
    fi

    mkdir -p "$WP_CORE_DIR"

    if [ "$WP_VERSION" == 'latest' ]; then
        local ARCHIVE_NAME='latest'
    elif [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        local ARCHIVE_NAME="wordpress-$WP_VERSION"
    else
        local ARCHIVE_NAME="wordpress-$WP_VERSION"
    fi

    download "https://wordpress.org/${ARCHIVE_NAME}.tar.gz" "$TMPDIR/wordpress.tar.gz"
    tar --strip-components=1 -zxmf "$TMPDIR/wordpress.tar.gz" -C "$WP_CORE_DIR"

    if [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
        download "https://raw.githubusercontent.com/WordPress/wordpress-develop/${WP_VERSION}/wp-tests-config-sample.php" "$WP_CORE_DIR/wp-tests-config.php"
    else
        download "https://raw.githubusercontent.com/WordPress/wordpress-develop/trunk/wp-tests-config-sample.php" "$WP_CORE_DIR/wp-tests-config.php"
    fi
}

install_test_suite() {
    if [ -d "$WP_TESTS_DIR" ]; then
        return
    fi

    mkdir -p "$WP_TESTS_DIR"

    if [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]; then
        local WP_DEVELOP_BRANCH="$WP_VERSION"
    else
        local WP_DEVELOP_BRANCH='trunk'
    fi

    for file in index.php functions.php install.php bootstrap.php wp-tests-config-sample.php; do
        download "https://raw.githubusercontent.com/WordPress/wordpress-develop/$WP_DEVELOP_BRANCH/tests/phpunit/includes/$file" "$WP_TESTS_DIR/$file"
    done
}

install_db() {
    if [ "${SKIP_DB_CREATE:-0}" == '1' ]; then
        return 0
    fi

    local PARTS=("${DB_HOST//:/ }")
    local DB_HOSTNAME="${PARTS[0]}"
    local DB_SOCK_OR_PORT="${PARTS[1]-3306}"
    if [[ -n "$DB_SOCK_OR_PORT" ]] && [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]]; then
        DB_HOSTNAME=":$DB_SOCK_OR_PORT"
    fi

    if [ ! -z "$DB_PASS" ]; then
        mysqladmin --host="$DB_HOSTNAME" --user="$DB_USER" --password="$DB_PASS" drop "$DB_NAME" --force >/dev/null 2>&1 || true
        mysqladmin --host="$DB_HOSTNAME" --user="$DB_USER" --password="$DB_PASS" create "$DB_NAME" --force
    else
        mysqladmin --host="$DB_HOSTNAME" --user="$DB_USER" drop "$DB_NAME" --force >/dev/null 2>&1 || true
        mysqladmin --host="$DB_HOSTNAME" --user="$DB_USER" create "$DB_NAME" --force
    fi
}

configure_wp_tests_config() {
    local WP_TESTS_CONFIG="$WP_CORE_DIR/wp-tests-config.php"

    if [ -f "$WP_TESTS_CONFIG" ]; then
        sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_CONFIG"
        sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_CONFIG"
        sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_CONFIG"
        sed -i "s|localhost|${DB_HOST}|" "$WP_TESTS_CONFIG"
    fi
}

install_wp
install_test_suite
install_db
configure_wp_tests_config
