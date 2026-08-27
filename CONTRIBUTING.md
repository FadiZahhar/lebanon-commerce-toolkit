# Contributing

Thank you for helping improve commerce tooling for Lebanon.

## Ground rules

- Keep the plugin free of telemetry, affiliate links, dashboard advertising, and paid-feature lockouts.
- Do not add external API calls without an explicit privacy/security design and opt-in.
- Preserve backward-compatible district slugs after a public release.
- Use WooCommerce CRUD APIs for orders and customers.
- Support classic checkout and Checkout Blocks for every checkout-facing feature.
- Keep public markup theme-agnostic and accessible.
- Add tests for domain behavior and manual QA steps for integrations.

## Development setup

```bash
composer install
composer test
npx @wordpress/env start
```

For a dependency-free first check:

```bash
php tests/smoke.php
bash bin/check-version.sh
bash bin/build-zip.sh
```

## Branch and commit model

1. Create a branch from `main`: `feature/short-description` or `fix/short-description`.
2. Keep changes focused.
3. Use imperative commits, for example `Add district shipping fallback validation`.
4. Update tests and documentation.
5. Open a pull request and complete the template.
6. Do not merge while CI, Plugin Check, or the required manual matrix is failing.

## Location-data changes

A location-data pull request must include:

- A reliable governmental or intergovernmental source.
- A migration note when a stable slug changes.
- English and Arabic labels.
- Updated dataset version.
- Updated dataset-count tests.

Avoid turning operational delivery neighborhoods into administrative facts. Merchant-specific areas belong in a future configurable layer.

## Coding standards

- WordPress Coding Standards.
- PHP 7.4 compatible syntax.
- Escape output at render time and sanitize input at the trust boundary.
- Prefer dependency injection and pure domain services.
- Avoid direct SQL unless there is no supported WordPress/WooCommerce API.
- Prefix global handles, hooks, options, shortcodes, REST namespaces, and metadata with `lct` or the plugin slug.

## Pull-request acceptance

A change is ready only when:

- PHP lint passes.
- JavaScript syntax passes.
- PHPCS passes.
- Unit and smoke tests pass.
- Plugin Check passes against the distributable folder.
- The relevant classic and block checkout scenarios pass manually.
- Documentation and changelog are updated.
