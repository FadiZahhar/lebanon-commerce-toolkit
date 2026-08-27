# A-to-Z Product, Development, and Publishing Plan

This document is the operating plan for taking Lebanon Commerce Toolkit from the supplied `0.1.0` public beta to a dependable `1.0.0` WordPress.org release.

## 1. Product contract

### Audience

- Lebanese WooCommerce merchants
- agencies building Lebanese stores
- developers who need stable Lebanese administrative identifiers
- stores that display an informational second currency or calculate local delivery by district

### Free-core promise

The public plugin remains useful on its own. Core checkout locations, phone normalization, secondary price display, district shipping, shortcodes, blocks, and REST reference data must not be crippled behind an upgrade prompt.

### Non-goals for 1.0

- no payment gateway;
- no tax/legal declaration engine;
- no automatic or “official” exchange-rate claim;
- no exhaustive city/neighborhood database;
- no remote telemetry;
- no hosted SaaS dependency;
- no theme template overrides.

## 2. Version 0.1.0 scope

Included:

- 9 governorates and 26 districts with stable IDs and EN/AR labels;
- classic checkout and Checkout Block address handling;
- My Account saved-address support;
- Lebanese phone normalization and structural validation;
- informational manual secondary-currency display;
- district/governorate/global shipping rules;
- HPOS declaration and WooCommerce CRUD usage;
- shortcodes, dynamic blocks, and public reference-data REST endpoint;
- settings, unit/smoke tests, static checks, CI, packaging, and WordPress.org assets.

Release classification: **public beta** until the compatibility matrix is completed on real WordPress/WooCommerce instances.

## 3. Repository bootstrap

Recommended GitHub repository:

```text
lebanon-commerce-toolkit
```

From the source package:

```bash
git init
git branch -M main
git add .
git commit -m "Initial Lebanon Commerce Toolkit public beta"
git remote add origin git@github.com:YOUR-GITHUB-OWNER/lebanon-commerce-toolkit.git
git push -u origin main
```

Then configure branch protection, private vulnerability reporting, Actions permissions, labels, and WordPress.org SVN secrets as described in `docs/GITHUB-SETUP.md`.

## 4. Local development

Requirements:

- PHP 7.4+ for runtime compatibility checks;
- PHP 8.1+ recommended for development tools;
- Composer 2;
- Node.js 20+ only for `wp-env` and JavaScript syntax checks;
- Docker when using `wp-env`;
- Git and ZIP/rsync utilities.

Commands:

```bash
composer install
composer test
npx @wordpress/env start
bash bin/build-all.sh
```

The runtime plugin does not load Composer packages and does not require a JavaScript build step.

## 5. Development workflow

Use short-lived branches:

```text
feature/<topic>
fix/<topic>
docs/<topic>
release/<version>
```

Every functional pull request should contain:

- problem statement and acceptance criteria;
- tests for pure domain behavior;
- classic checkout impact;
- Checkout Block impact;
- HPOS/storage impact;
- accessibility and RTL impact;
- screenshots or reproducible test evidence when UI changes;
- changelog entry for user-visible behavior.

Do not merge when Plugin Check, PHPCS, unit tests, smoke tests, or the affected checkout path fails.

## 6. Architecture rules

- Keep business rules in `src/Domain` without WordPress/WooCommerce dependencies.
- Keep hooks and lifecycle adapters in `src/Integration`.
- Use WooCommerce CRUD for orders and customers; do not query order tables directly.
- Use official additional checkout fields and Store API extension APIs for blocks.
- Keep stable data IDs separate from translated labels.
- Sanitize at input, validate against domain rules, and escape at output.
- Never add telemetry or remote calls without an explicit privacy/product decision and WordPress.org review.
- Avoid global state except the small WooCommerce-instantiated shipping-method bridge.
- Introduce migrations through `Core/Upgrader.php`; migrations must be idempotent.

## 7. Test gates

### On every change

```bash
php tests/smoke.php
composer test
node --check assets/js/location-selector.js
node --check assets/js/classic-checkout.js
node --check assets/js/block-checkout.js
node --check blocks/editor.js
bash bin/check-version.sh
bash bin/audit-source.sh
```

### Before a beta release

- complete all affected sections of `docs/QA-CHECKLIST.md`;
- test Storefront and the current default block theme;
- test English LTR and Arabic RTL;
- test guest and logged-in checkout;
- test exact, governorate, global, and no-match delivery rules;
- test one manual/COD gateway and one sandbox redirect/tokenized gateway;
- test HPOS enabled and compatibility mode.

### Before 1.0.0

Complete the full matrix in `docs/TESTING.md`, resolve all Plugin Check errors, and retain evidence for each critical checkout dimension.

## 8. Security and privacy review

For each release:

- verify permissions/capabilities on every admin action;
- verify Settings API/WordPress or WooCommerce nonce ownership;
- verify REST permissions and response scope;
- search for secrets, private URLs, customer data, debug output, and test credentials;
- verify no direct database writes to WooCommerce order tables;
- verify no dynamic execution or unreviewed deserialization;
- confirm uninstall does not remove historical order/customer records;
- publish security fixes through a private advisory before disclosure.

## 9. Packaging

Build both deliverables:

```bash
bash bin/build-all.sh
```

Outputs:

```text
dist/lebanon-commerce-toolkit-<version>.zip
dist/lebanon-commerce-toolkit-source-<version>.zip
dist/SHA256SUMS.txt
```

The first ZIP is installable and suitable for WordPress.org review. The source ZIP includes tests, docs, workflows, and WordPress.org artwork.

## 10. GitHub release

1. Update header version, `LCT_VERSION`, block metadata versions, stable tag, changelog, and POT header.
2. Run all automated and manual gates.
3. Build and inspect the ZIP.
4. Commit the release.
5. Create a signed numeric tag such as `0.2.0` (no `v` prefix).
6. Push the tag.
7. Inspect the generated GitHub Release assets and checksums.
8. Install the attached ZIP on a fresh staging site before announcing it.

## 11. WordPress.org submission

1. Confirm the exact WordPress.org owner/contributor username in `readme.txt`.
2. Submit only the installable ZIP.
3. Respond precisely to review feedback and fix root causes.
4. After approval, configure the SVN-specific password as `SVN_PASSWORD` and the exact case-sensitive username as `SVN_USERNAME`.
5. Run the manual deployment workflow for an existing numeric Git tag.
6. Inspect SVN `trunk`, `tags/<version>`, and `assets`.
7. Download the generated WordPress.org ZIP and repeat the critical smoke test.

See `docs/WORDPRESS-ORG.md` and `docs/RELEASE.md`.

## 12. Support model

Community support covers:

- reproducible plugin defects;
- compatibility issues on supported versions;
- location-label corrections with evidence;
- documentation gaps;
- security reports through the private channel.

Not included in free community support:

- store-specific configuration;
- custom delivery pricing design;
- theme/plugin conflicts without a reproducible minimal case;
- data migration or production incident response;
- custom feature development.

Keep the plugin dashboard free of ads. Mention professional Pro-Solutions implementation only in documentation/support context, not as a blocker or recurring admin notice.

## 13. Suggested roadmap

### 0.2.x — stabilization

- compatibility fixes from real stores;
- improved block checkout persistence and automated integration coverage;
- accessibility/RTL refinements;
- community-verified location label corrections.

### 0.3.x — merchant operations

- CSV import/export for district rates;
- reusable rate profiles;
- clearer validation feedback for ignored shipping rules.

### 0.4.x — optional commerce helpers

- opt-in COD constraints and surcharge rules;
- WhatsApp product inquiry block using merchant-configured number;
- merchant-maintained city/area groups.

### 1.0.0 — stable contract

- completed compatibility matrix;
- documented migration/backward-compatibility policy;
- stable public filters, shortcodes, blocks, REST shape, metadata, and shipping-rule grammar;
- no unresolved critical/high defects.

## 14. Maintenance cadence

- Test against every major WooCommerce release before raising `WC tested up to`.
- Test against every major WordPress release before raising `Tested up to`.
- Review PHP support annually and announce removals at least one minor release ahead.
- Review administrative data at least twice yearly and on credible correction reports.
- Publish security patches independently of the normal feature cadence.
- Keep release notes merchant-focused and migration notes developer-focused.
