# Validation and Release Gates

Version `0.1.0` is a production-oriented public beta. The repository contains the engineering controls needed for release, but a plugin that changes checkout and shipping behavior must still pass a real WordPress/WooCommerce/browser matrix before `1.0.0`.

## Fast local validation

Run without installing Composer dependencies:

```bash
find . -path './vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l
php bin/check-json.php
bash bin/check-version.sh
bash bin/audit-source.sh
php tests/smoke.php
node --check assets/js/location-selector.js
node --check assets/js/classic-checkout.js
node --check assets/js/block-checkout.js
node --check blocks/editor.js
bash bin/build-all.sh
unzip -t dist/lebanon-commerce-toolkit-0.1.0.zip
unzip -t dist/lebanon-commerce-toolkit-source-0.1.0.zip
sha256sum -c dist/SHA256SUMS.txt
```

## Full development validation

Requires Composer and its development dependencies:

```bash
composer validate --strict
composer install
composer test
```

This adds:

- WordPress Coding Standards.
- PHPCompatibilityWP for PHP 7.4 and newer.
- PHPUnit tests for pure domain services.
- The same syntax, JSON, version, source-audit, and smoke checks used locally.

## GitHub CI release gates

A pull request or push to `main` must pass:

1. PHP 7.4, 8.1, 8.3, and 8.4 matrices.
2. Composer validation and dependency installation.
3. PHP syntax and WordPress Coding Standards.
4. PHPCompatibilityWP.
5. Dependency-free domain smoke tests.
6. PHPUnit domain tests.
7. JavaScript syntax validation.
8. JSON, source-policy, and version-consistency checks.
9. WordPress Plugin Check against the actual installable build directory.

A numeric tag must match every version declaration before the GitHub release workflow produces packages.

## Required runtime acceptance matrix

Before promoting `0.1.0` to `1.0.0`, complete `docs/QA-CHECKLIST.md` with evidence that covers:

- WordPress 6.9, 7.0, and 7.1.
- WooCommerce 9.9 latest patch, a supported 10.x patch, and 11.0.1 or newer.
- PHP 7.4, 8.1, 8.3, and 8.4.
- Classic checkout and Checkout Block.
- HPOS enabled and compatibility mode/off.
- Storefront, a current default block theme, and one representative commercial/custom theme.
- English LTR and Arabic RTL.
- Guest, logged-in, billing-only, and separate-shipping-address flows.
- Physical, virtual, and mixed carts.
- Taxes disabled/enabled, coupons, free-shipping threshold, and district/governorate/global fallbacks.
- COD/manual, redirect, and sandbox tokenized payment gateways.
- My Account billing/shipping edits and historical order address rendering.

## Security and privacy gates

- No secrets, credentials, customer data, staging URLs, or license keys in source/history.
- All write operations use WordPress/WooCommerce capabilities and nonce mechanisms supplied by their owning forms/APIs.
- No direct SQL and no direct writes to WooCommerce order tables.
- No telemetry, tracking, or external requests.
- No executable code loaded from remote sources.
- REST data is packaged public administrative reference data only.
- Uninstall deletes settings only after explicit merchant opt-in.

## Package inspection gates

The installable ZIP must:

- Extract under one `lebanon-commerce-toolkit/` directory.
- Contain `lebanon-commerce-toolkit/lebanon-commerce-toolkit.php`.
- Exclude `.git`, `.github`, tests, docs, Composer packages, source maps, credentials, and local-environment files.
- Pass `unzip -t` and WordPress Plugin Check.

The source ZIP must contain all code, tests, workflows, docs, artwork, and build tools needed by a contributor, but no `vendor/`, `node_modules/`, `.git/`, or prior `dist/` output.

## Local validation limitation

The generated release report records exactly what ran in the packaging environment. Composer/PHPUnit/PHPCS, WordPress Plugin Check, WordPress itself, WooCommerce itself, browsers, payment gateways, and the full checkout matrix require the prepared GitHub/local integration environments and are not inferred from syntax tests.
