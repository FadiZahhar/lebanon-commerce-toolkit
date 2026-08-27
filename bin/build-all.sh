#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

rm -f "$ROOT/dist/SHA256SUMS.txt"
"$ROOT/bin/build-zip.sh"
"$ROOT/bin/build-source-zip.sh"

echo "Built installable and source packages with SHA-256 checksums."
