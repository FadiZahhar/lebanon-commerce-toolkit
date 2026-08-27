# Architecture

## Design goals

1. **Correctness before feature volume.** Administrative data, shipping decisions, and checkout validation are deterministic.
2. **WooCommerce-native integration.** Orders use CRUD, shipping uses `WC_Shipping_Method`, Checkout Blocks use official additional-field and Store API APIs.
3. **Theme independence.** No WooCommerce template overrides and no assumptions about theme colors, typography, or DOM outside Woo-owned checkout fields.
4. **Low operational risk.** No remote services, no scheduled jobs, and no automatic exchange-rate assertions.
5. **Extensibility.** Reference data and validation are filterable; stable identifiers are separated from localized labels.
6. **Testability.** Domain rules are pure PHP; hooks are adapters.

## Bootstrap flow

```text
lebanon-commerce-toolkit.php
  ├─ defines constants and lightweight autoloader
  ├─ registers activation
  ├─ declares HPOS and Checkout Blocks compatibility
  ├─ validates WooCommerce >= 9.9
  ├─ constructs shared domain dependencies
  └─ registers independent services
```

The distributable plugin does not require Composer at runtime. Composer is used only for PHPCS and PHPUnit.

## Domain layer

### `LocationRepository`

Reads `data/lebanon-locations.php`, localizes labels, generates stable options, validates composite district keys, and exposes a script-safe map.

District identity is stored as:

```text
<governorate-slug>:<district-slug>
```

Example:

```text
mount-lebanon:metn
```

The composite key makes block checkout's flattened selector unambiguous and allows shipping rules to remain stable across languages.

### `PhoneNormalizer`

Converts Arabic/Persian numerals, removes presentation punctuation, recognizes `00`/`+` international prefixes, normalizes common Lebanese local numbers, and preserves explicit foreign numbers.

It deliberately validates structure rather than telecom-operator allocation. Integrators can replace the result through `lct_is_valid_lebanon_phone`.

### `CurrencyConverter`

Performs multiplication, optional nearest-increment rounding, and localized formatting. It never reads an external rate or mutates WooCommerce prices.

### `ShippingRateTable`

Parses a small merchant DSL and resolves it in this order:

```text
exact district -> governorate -> global -> method fallback
```

Invalid/negative rules are ignored during parsing and normalized when the shipping method is saved.

## WooCommerce integration layer

### Location fields

Classic checkout:

```text
woocommerce_states
  -> LB governorate options
woocommerce_billing_fields / woocommerce_shipping_fields
  -> district selects
classic-checkout.js
  -> dependent options + update_checkout
woocommerce_after_checkout_validation
  -> country/state/district consistency
woocommerce_checkout_create_order
  -> CRUD order meta
```

Checkout Block:

```text
woocommerce_register_additional_checkout_field
  -> address field with LB-only JSON Schema conditions
block-checkout.js
  -> official extensionCartUpdate call
Store API update callback
  -> session district + shipping recalculation
checkout validation
  -> state/district consistency before payment
order-meta hook
  -> stable plugin-owned order meta
```

WooCommerce also persists its official additional field. The plugin copies the value to `_lct_billing_district` / `_lct_shipping_district` so future internal Woo metadata changes do not become the plugin's public contract.

### Phone lifecycle

Classic payload is normalized before order creation. Checkout Block customer and order objects are normalized through Store API lifecycle hooks. Lebanon-specific validation runs server-side before payment.

### Secondary currency

Filters append display-only markup to PHP-rendered product/cart prices. The same converter is available through a shortcode and dynamic block for block-based templates that do not execute classic price filters.

### Shipping method

WooCommerce instantiates shipping methods itself, so `ShippingMethodRegistrar` holds the shared repository/parser references and exposes them to the method. The method is zone-scoped, checks destination country `LB`, reads the validated checkout district from session, and uses package state as governorate fallback.

## Storage contract

| Storage | Key | Purpose |
|---|---|---|
| Option | `lct_settings` | Plugin settings |
| Option | `lct_version` | Installed plugin version |
| Session | `lct_checkout_district` | Active delivery-rate calculation |
| User meta | `billing_lct_district` | Classic saved billing district |
| User meta | `shipping_lct_district` | Classic saved shipping district |
| Order meta | `_lct_billing_district` | Stable billing district |
| Order meta | `_lct_shipping_district` | Stable shipping district |

Official Checkout Block additional-field storage is also retained by WooCommerce.

## Public contracts

- Filter: `lct_location_data`
- Filter: `lct_is_valid_lebanon_phone`
- Filter: `lct_secondary_currency_html`
- REST: `GET /wp-json/lct/v1/locations`
- Shortcode: `lct_location_selector`
- Shortcode: `lct_secondary_price`
- Block: `lebanon-commerce-toolkit/location-selector`
- Block: `lebanon-commerce-toolkit/secondary-price`
- Shipping method: `lct_district_delivery`

Changes to these contracts require a changelog entry and backward-compatibility review.
