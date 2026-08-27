#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_PATHS=(
  "$ROOT/lebanon-commerce-toolkit.php"
  "$ROOT/uninstall.php"
  "$ROOT/src"
  "$ROOT/data"
  "$ROOT/assets"
  "$ROOT/blocks"
)

fail_if_match() {
  local pattern="$1"
  local message="$2"
  if grep -RInE --include='*.php' --include='*.js' "$pattern" "${RUNTIME_PATHS[@]}"; then
    echo "$message" >&2
    exit 1
  fi
}

fail_if_match '\b(eval|shell_exec|passthru|proc_open|popen|system)\s*\(' 'Forbidden dynamic/system execution found.'
fail_if_match '\bbase64_decode\s*\(' 'Unexpected base64 decoder found.'
fail_if_match '\b(wp_remote_get|wp_remote_post|wp_remote_request|curl_exec|curl_init)\s*\(' 'Unexpected external-network code found.'
fail_if_match '\$wpdb\b' 'Direct database access found; use WordPress/WooCommerce APIs.'
fail_if_match '(BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|AKIA[0-9A-Z]{16})' 'Credential-like material found.'

if grep -RIl --include='*.php' --include='*.js' 'lebanon-commerce-toolkit' "${RUNTIME_PATHS[@]}" >/dev/null; then
  :
else
  echo 'Expected text domain/namespace marker was not found.' >&2
  exit 1
fi

echo 'Source policy audit passed.'
