#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="lebanon-commerce-toolkit"
VERSION="$(sed -nE 's/^ \* Version:[[:space:]]+([^[:space:]]+).*/\1/p' "$ROOT/lebanon-commerce-toolkit.php" | head -1)"
DIST="$ROOT/dist"
BUILD="${TMPDIR:-/tmp}/lct-build-$$"

"$ROOT/bin/check-version.sh"
rm -rf "$BUILD"
mkdir -p "$BUILD/$SLUG" "$DIST"

rsync -a --delete --exclude-from="$ROOT/.distignore" "$ROOT/" "$BUILD/$SLUG/"

find "$BUILD/$SLUG" -type f \( -name '*.log' -o -name '*.tmp' -o -name '.DS_Store' \) -delete

ZIP="$DIST/$SLUG-$VERSION.zip"
rm -f "$ZIP"
(
  cd "$BUILD"
  zip -qr "$ZIP" "$SLUG"
)

(
  cd "$DIST"
  sha256sum "$(basename "$ZIP")" > SHA256SUMS.txt
)

rm -rf "$BUILD"
echo "Built $ZIP"
