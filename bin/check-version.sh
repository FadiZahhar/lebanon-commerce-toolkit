#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MAIN="$ROOT/lebanon-commerce-toolkit.php"
README="$ROOT/readme.txt"
CHANGELOG="$ROOT/CHANGELOG.md"

HEADER_VERSION="$(sed -nE 's/^ \* Version:[[:space:]]+([^[:space:]]+).*/\1/p' "$MAIN" | head -1)"
CONSTANT_VERSION="$(sed -nE "s/^define\( 'LCT_VERSION', '([^']+)' \);/\1/p" "$MAIN" | head -1)"
STABLE_TAG="$(sed -nE 's/^Stable tag:[[:space:]]+([^[:space:]]+).*/\1/p' "$README" | head -1)"

if [[ -z "$HEADER_VERSION" || -z "$CONSTANT_VERSION" || -z "$STABLE_TAG" ]]; then
  echo "Could not read all version declarations." >&2
  exit 1
fi

if [[ "$HEADER_VERSION" != "$CONSTANT_VERSION" || "$HEADER_VERSION" != "$STABLE_TAG" ]]; then
  printf 'Version mismatch: header=%s constant=%s stable-tag=%s\n' "$HEADER_VERSION" "$CONSTANT_VERSION" "$STABLE_TAG" >&2
  exit 1
fi

if ! grep -Fq "## [$HEADER_VERSION]" "$CHANGELOG"; then
  echo "CHANGELOG.md does not contain ## [$HEADER_VERSION]." >&2
  exit 1
fi

for BLOCK_FILE in "$ROOT"/blocks/*/block.json; do
  BLOCK_VERSION="$(php -r '$d=json_decode(file_get_contents($argv[1]), true); echo isset($d["version"]) ? $d["version"] : "";' "$BLOCK_FILE")"
  if [[ "$BLOCK_VERSION" != "$HEADER_VERSION" ]]; then
    printf 'Version mismatch: %s=%s plugin=%s\n' "$BLOCK_FILE" "$BLOCK_VERSION" "$HEADER_VERSION" >&2
    exit 1
  fi
done

echo "Version declarations match: $HEADER_VERSION"
