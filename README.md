# Lebanon Commerce Toolkit for WooCommerce

A free, open-source WooCommerce extension by **Pro-Solutions.net** for Lebanese checkout data, phone normalization, informational secondary-currency display, and district-based local delivery.

The runtime package has no Composer dependency, no JavaScript build step, no telemetry, no external API calls, and no theme-specific templates.

## Release status

Version `0.1.0` is a production-oriented public beta. The source is structured for WordPress.org review and includes automated static checks, domain tests, an installable ZIP builder, GitHub Actions, and a manual compatibility matrix. Complete checkout testing on representative production stacks remains mandatory before submitting version `1.0.0`.

## Compatibility target

| Component | Target |
|---|---:|
| WordPress | Requires 6.9; tested target 7.1 |
| WooCommerce | Requires 9.9; tested target 11.0.1 |
| PHP | 7.4–8.4 |
| Checkout | Classic shortcode and Checkout Block |
| Orders | HPOS compatible; WooCommerce CRUD only |
| Themes | Theme-agnostic markup and minimal logical-property CSS |

WooCommerce 9.9 is the minimum because conditional visibility for official additional Checkout Block fields is used to show the district selector only for Lebanon.

## Included features

### Lebanese checkout locations

- Nine governorates and 26 districts.
- Governorate replaces the generic state/province concept for country `LB`.
- A dependent district field for classic checkout and My Account addresses.
- An official additional address field for Checkout Blocks.
- City / Area remains editable for neighborhood, village, street, or delivery-zone detail.
- District data is available through a public read-only REST endpoint.

### Lebanese phone normalization

Common local formats are normalized to a consistent `+961` representation. Explicit foreign country codes are preserved. Validation is intentionally structural and filterable instead of embedding a brittle operator-prefix list.

Examples:

```text
03 123 456       -> +9613123456
00961 71 234 567 -> +96171234567
٠٣ ١٢٣ ٤٥٦       -> +9613123456
```

### Informational secondary currency

- Manual merchant-controlled conversion rate.
- Optional rounding increment.
- Product/shop and classic-cart display controls.
- Dynamic Gutenberg block and shortcode.
- Does **not** alter the store currency, payment amount, tax calculation, shipping cost, or persisted order total.

No exchange-rate provider is bundled. This avoids silently representing a changing market rate as authoritative.

### District-based delivery

Add **Lebanon District Delivery** to one or more WooCommerce shipping zones. Rules are configured in the store currency:

```text
mount-lebanon:metn=4.00
@mount-lebanon=5.00
*=7.00
```

Resolution order:

1. Exact district
2. Governorate fallback using `@governorate`
3. Lebanon-wide fallback using `*`
4. Optional method fallback cost

An optional free-shipping threshold is supported per shipping-method instance.

### Theme-independent public tools

```text
[lct_location_selector]
[lct_location_selector required="yes" show_city="yes"]
[lct_secondary_price product_id="123"]
[lct_secondary_price amount="25"]
```

Dynamic blocks:

- **Lebanon Location Selector**
- **Lebanon Secondary Price**

REST endpoint:

```text
GET /wp-json/lct/v1/locations?locale=en
GET /wp-json/lct/v1/locations?locale=ar
```

## Installation

### Installable ZIP

1. Build or download `lebanon-commerce-toolkit-0.1.0.zip`.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP and activate it.
4. Confirm WooCommerce 9.9 or newer is active.
5. Open **WooCommerce → Lebanon Toolkit**.
6. Configure the checkout, phone, and secondary-currency options.
7. Open **WooCommerce → Settings → Shipping → Shipping zones**.
8. Add **Lebanon District Delivery** to the relevant Lebanon zone and enter rates.

### Development checkout

```bash
git clone https://github.com/YOUR-GITHUB-OWNER/lebanon-commerce-toolkit.git
cd lebanon-commerce-toolkit
composer install
composer test
bash bin/build-all.sh
```

The plugin itself does not load `vendor/`; Composer packages are development-only.

## Architecture

The bootstrap creates small, separately testable services:

```text
Core
├── Requirements / activation / options
Domain
├── Locations repository
├── Phone normalizer
├── Currency converter
└── Shipping rate parser
Integration/WooCommerce
├── Classic + Block location fields
├── Phone lifecycle integration
├── Secondary price display
└── Zone shipping method
Frontend
├── Shortcodes
├── Dynamic blocks
└── Registered public assets
Admin
└── Merchant settings
Api
└── Read-only locations endpoint
```

Domain objects contain no WooCommerce dependencies. WordPress and WooCommerce hooks are isolated in integration services. See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) and [`docs/FILE-MAP.md`](docs/FILE-MAP.md).

## Extension points

### Replace or extend location data

```php
add_filter(
    'lct_location_data',
    function ( $data ) {
        // Preserve the documented schema and stable slugs.
        return $data;
    }
);
```

### Customize phone validation

```php
add_filter(
    'lct_is_valid_lebanon_phone',
    function ( $is_valid, $number ) {
        return $is_valid;
    },
    10,
    2
);
```

### Customize secondary price markup

```php
add_filter(
    'lct_secondary_currency_html',
    function ( $html, $converted, $base_amount ) {
        return $html;
    },
    10,
    3
);
```

## Data and privacy

- No analytics or telemetry.
- No external network requests.
- No customer data is sent outside WordPress.
- The REST endpoint exposes only packaged administrative reference data.
- Settings can be removed during uninstall if explicitly enabled.
- Historical order/customer address metadata is retained to preserve commerce records.

## Development commands

```bash
php tests/smoke.php
composer lint:php
composer lint:phpcs
composer test:unit
composer test
python3 bin/make-pot.py
bash bin/check-version.sh
bash bin/build-all.sh
```

Local WooCommerce testing can use `wp-env`:

```bash
npx @wordpress/env start
npx @wordpress/env run cli wp plugin list
```

See [`docs/TESTING.md`](docs/TESTING.md), [`docs/VALIDATION.md`](docs/VALIDATION.md), and [`docs/QA-CHECKLIST.md`](docs/QA-CHECKLIST.md).

## GitHub and WordPress.org release

The repository includes:

- Pull-request CI across supported PHP versions.
- WordPress Plugin Check against the built package.
- WordPress Playground PR previews.
- Tagged GitHub release ZIP generation.
- Optional WordPress.org SVN deployment using encrypted secrets.
- `.wordpress-org` icons and banners.

Follow [`docs/GITHUB-SETUP.md`](docs/GITHUB-SETUP.md), [`docs/RELEASE.md`](docs/RELEASE.md), and [`docs/WORDPRESS-ORG.md`](docs/WORDPRESS-ORG.md).

## Roadmap

Potential post-1.0 features are intentionally kept outside the first release until checkout/shipping behavior is field-tested:

- Merchant-maintained city/area delivery groups.
- Optional Cash on Delivery constraints/surcharge.
- WhatsApp product/order inquiry block.
- CSV location/rate import and export.
- Optional exchange-rate provider adapters with explicit consent and caching.
- Arabic WordPress.org translation packs.

## Contributing and security

Read [`CONTRIBUTING.md`](CONTRIBUTING.md) before opening a pull request. Security issues should follow [`SECURITY.md`](SECURITY.md), not a public issue.

## License and trademarks

GPL-2.0-or-later. See [`LICENSE`](LICENSE).

WooCommerce and WordPress are trademarks of their respective owners. This community plugin is not affiliated with or endorsed by Automattic or WooCommerce.
