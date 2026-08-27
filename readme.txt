=== Lebanon Commerce Toolkit for WooCommerce ===
Contributors: prosolutionsnet
Tags: woocommerce, lebanon, checkout, shipping, currency
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lebanese governorates and districts, phone normalization, secondary currency display, and district delivery for WooCommerce.

== Description ==

Lebanon Commerce Toolkit localizes WooCommerce for Lebanese stores without adding telemetry, external APIs, or theme-specific templates.

Features:

* Nine Lebanese governorates and 26 districts.
* Governorate, District, and City / Area checkout structure.
* Classic checkout, My Account addresses, and Checkout Block support.
* Lebanese phone normalization to a consistent +961 format.
* Optional structural phone validation with a developer filter.
* Merchant-controlled informational LBP or other secondary-currency display.
* District, governorate, and Lebanon-wide shipping-rate rules.
* WooCommerce shipping-zone and HPOS compatibility.
* Theme-independent shortcodes and dynamic Gutenberg blocks.
* Read-only locations REST endpoint.
* English and Arabic location labels.
* No tracking and no external network requests.

The secondary amount is informational only. It does not alter the store currency, payment amount, taxes, shipping, or order totals. The merchant controls the manual rate and remains responsible for its accuracy.

Shortcodes:

`[lct_location_selector]`

`[lct_location_selector required="yes" show_city="yes"]`

`[lct_secondary_price product_id="123"]`

`[lct_secondary_price amount="25"]`

REST endpoint:

`/wp-json/lct/v1/locations?locale=en`

`/wp-json/lct/v1/locations?locale=ar`

== Installation ==

1. Install and activate WooCommerce 9.9 or newer.
2. Upload the plugin ZIP through Plugins > Add New Plugin > Upload Plugin.
3. Activate Lebanon Commerce Toolkit.
4. Open WooCommerce > Lebanon Toolkit and configure the desired features.
5. Open WooCommerce > Settings > Shipping > Shipping zones.
6. Add Lebanon District Delivery to a zone that includes Lebanon.
7. Enter one shipping rule per line in the store currency.

District rule:

`mount-lebanon:metn=4.00`

Governorate fallback:

`@mount-lebanon=5.00`

Lebanon-wide fallback:

`*=7.00`

Before using on a live store, test checkout, payment, shipping, emails, and refunds on staging with HPOS and the Checkout Block configuration used in production.

== Frequently Asked Questions ==

= Does this plugin change my WooCommerce transaction currency? =

No. Secondary currency is display-only and clearly approximate. Checkout and payment continue using the store currency.

= Does it automatically fetch an exchange rate? =

No. Automatic financial data is deliberately not bundled. The merchant enters and maintains the informational rate.

= Does it support Checkout Blocks? =

Yes. The district uses WooCommerce's official additional checkout field API and is conditionally shown for Lebanon.

= Does it support classic checkout? =

Yes. Classic checkout receives a governorate-dependent district field and server-side validation.

= Is it HPOS compatible? =

The plugin declares HPOS compatibility and uses WooCommerce order CRUD APIs rather than direct order-table writes.

= Why is City / Area not a fixed list? =

Districts are stable administrative data. Neighborhoods, villages, delivery zones, and merchant terminology are more operational and change frequently, so City / Area remains editable.

= Can developers replace the location data? =

Yes. Use the `lct_location_data` filter while preserving the documented data schema and stable keys.

= Does the plugin track users? =

No. It includes no telemetry, advertising, or external service calls.

== Changelog ==

= 0.1.0 =
* Initial public beta.
* Added nine governorates and 26 districts with English and Arabic labels.
* Added classic and block checkout district handling.
* Added +961 phone normalization and structural validation.
* Added informational secondary-currency display.
* Added district/governorate/fallback shipping method.
* Added shortcodes, dynamic blocks, REST endpoint, tests, and release automation.

== Upgrade Notice ==

= 0.1.0 =
Initial public beta. Validate the full checkout flow on staging before production use.
