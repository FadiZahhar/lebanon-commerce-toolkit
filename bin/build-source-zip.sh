#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="lebanon-commerce-toolkit"
VERSION="$(sed -nE 's/^ \* Version:[[:space:]]+([^[:space:]]+).*/\1/p' "$ROOT/lebanon-commerce-toolkit.php" | head -1)"
DIST="$ROOT/dist"
BUILD="${TMPDIR:-/tmp}/lct-source-$$"

"$ROOT/bin/check-version.sh"
rm -rf "$BUILD"
mkdir -p "$BUILD/$SLUG" "$DIST"

rsync -a --delete \
  --exclude '/.git' \
  --exclude '/vendor' \
  --exclude '/node_modules' \
  --exclude '/dist' \
  --exclude '/.DS_Store' \
  "$ROOT/" "$BUILD/$SLUG/"

ZIP="$DIST/$SLUG-source-$VERSION.zip"
rm -f "$ZIP"
(
  cd "$BUILD"
  zip -qr "$ZIP" "$SLUG"
)

(
  cd "$DIST"
  ZIP_BASENAME="$(basename "$ZIP")"
  if [[ -f SHA256SUMS.txt ]]; then
    awk -v filename="$ZIP_BASENAME" '$2 != filename' SHA256SUMS.txt > SHA256SUMS.txt.tmp
    mv SHA256SUMS.txt.tmp SHA256SUMS.txt
  fi
  sha256sum "$ZIP_BASENAME" >> SHA256SUMS.txt
  sort -u SHA256SUMS.txt -o SHA256SUMS.txt
)

rm -rf "$BUILD"
echo "Built $ZIP"
