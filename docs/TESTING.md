# Testing Strategy

No single test type can verify a WooCommerce checkout extension. This project uses four layers.

## 1. Dependency-free smoke tests

```bash
php tests/smoke.php
```

Covers:

- Dataset counts and stable keys.
- English/Arabic labels.
- Lebanese/foreign phone normalization.
- Structural phone validation.
- Currency conversion and rounding.
- Shipping parser and fallback precedence.

## 2. Unit tests

```bash
composer install
composer test:unit
```

The PHPUnit suite tests pure domain behavior without booting WordPress. New domain rules require unit tests.

## 3. Static checks

```bash
composer lint:php
composer lint:phpcs
bash bin/audit-source.sh
php bin/check-json.php
node --check assets/js/location-selector.js
node --check assets/js/classic-checkout.js
node --check assets/js/block-checkout.js
node --check blocks/editor.js
bash bin/check-version.sh
```

GitHub CI additionally runs WordPress Plugin Check against the built distributable directory, not the development repository.

## 4. WordPress/WooCommerce integration tests

Start the supplied `wp-env` environment:

```bash
npx @wordpress/env start
npx @wordpress/env run cli wp plugin activate woocommerce lebanon-commerce-toolkit
```

Create:

- A Lebanon shipping zone.
- One Lebanon District Delivery instance.
- A simple product.
- A taxable and a non-taxable scenario.
- A free-shipping threshold scenario.

Run every relevant item in `docs/QA-CHECKLIST.md`.

## Compatibility matrix before 1.0.0

The following matrix is a release gate, not a claim that every combination has already passed:

| Dimension | Required coverage |
|---|---|
| WordPress | 6.9, 7.0, 7.1 |
| WooCommerce | 9.9 latest patch, 10.x latest patch, 11.0.1+ |
| PHP | 7.4, 8.1, 8.3, 8.4 |
| Checkout | Classic, Checkout Block |
| HPOS | Enabled, compatibility mode/off |
| Theme | Storefront, current default block theme, one custom commercial theme |
| Locale | English LTR, Arabic RTL |
| Address | Billing-only shipping, separate shipping address |
| Customer | Guest, logged-in, saved My Account address |
| Products | Physical, virtual, mixed cart |
| Taxes | Disabled, enabled |
| Payments | COD/manual gateway, one redirect gateway, one tokenized gateway in sandbox |

Pairwise coverage is acceptable for minor releases; all critical checkout dimensions must be represented.

## Failure diagnostics

Enable:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'SCRIPT_DEBUG', true );
```

Inspect:

- Browser console/network for `/wc/store/v1/cart/extensions` and `/wc/store/v1/checkout`.
- WooCommerce logs.
- `wp-content/debug.log`.
- Order billing/shipping district meta through WooCommerce CRUD or WP-CLI.
- Shipping package destination country/state and `lct_checkout_district` session value.

Never capture production credentials or personal customer data in test evidence.
