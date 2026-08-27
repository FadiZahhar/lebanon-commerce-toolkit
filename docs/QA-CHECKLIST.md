# Release QA Checklist

## Installation and requirements

- [ ] Fresh install activates with WooCommerce 9.9+.
- [ ] Missing WooCommerce shows an actionable notice without a fatal error.
- [ ] Unsupported WooCommerce shows an actionable version notice.
- [ ] Activation does not overwrite existing settings.
- [ ] Deactivation preserves settings and historical data.
- [ ] Uninstall removes settings only when the explicit option is enabled.

## Admin settings

- [ ] Only users with `manage_woocommerce` can open settings.
- [ ] All checkboxes save and restore correctly.
- [ ] Invalid exchange rates and negative rounding values are rejected/sanitized.
- [ ] No settings screen performs an external request.
- [ ] Shipping-zone link opens WooCommerce shipping settings.

## Classic checkout

- [ ] Lebanon shows Governorate, District, and City / Area.
- [ ] Non-Lebanon countries hide/disable District.
- [ ] Changing governorate refreshes district options.
- [ ] A district from a different governorate is rejected server-side.
- [ ] Required district is enforced only for Lebanon.
- [ ] Separate shipping address uses shipping district for rates.
- [ ] Billing-only shipping uses billing district for rates.
- [ ] Saved My Account district preselects correctly.
- [ ] Order admin and formatted addresses show the district.

## Checkout Block

- [ ] Official district field appears only for Lebanon.
- [ ] District is conditionally required only for Lebanon.
- [ ] English and Arabic labels render correctly.
- [ ] Selecting district calls the Store API extension endpoint without console errors.
- [ ] Shipping rates refresh after district changes.
- [ ] State/district mismatch is rejected before payment.
- [ ] Billing and shipping field values persist on the order.
- [ ] Reloading checkout preserves the selected field.

## Shipping

- [ ] Exact district rule wins.
- [ ] Governorate fallback wins when no district rule exists.
- [ ] `*` fallback wins when neither exact nor governorate exists.
- [ ] Method fallback works only when configured.
- [ ] Method is unavailable outside Lebanon.
- [ ] Empty unmatched configuration hides the method.
- [ ] Free-shipping threshold changes cost to zero.
- [ ] Tax status is respected.
- [ ] Multiple shipping-zone instances remain isolated.

## Phone

- [ ] `03 123 456` normalizes to `+9613123456`.
- [ ] `00961...` and `+961...` normalize consistently.
- [ ] Arabic numerals normalize.
- [ ] Explicit foreign country code is preserved.
- [ ] Invalid Lebanese structure is rejected in relaxed validation mode.
- [ ] Normalize-only mode does not reject.
- [ ] Classic, My Account, and Checkout Block persist normalized values.

## Secondary currency

- [ ] Disabled mode produces no secondary markup.
- [ ] Empty/zero exchange rate produces no secondary markup.
- [ ] Product/shop output is correct.
- [ ] Variable-product variation output is correct.
- [ ] Classic cart line output is correct when enabled.
- [ ] Rounding increment works at boundaries.
- [ ] Base WooCommerce price and order total remain unchanged.
- [ ] Shortcode and dynamic block render correctly.
- [ ] Markup inherits theme colors and remains readable in dark mode.

## REST, accessibility, and RTL

- [ ] REST endpoint returns 9 governorates and 26 districts.
- [ ] `locale=ar` returns Arabic labels.
- [ ] No personal data is exposed.
- [ ] Every public input has a label.
- [ ] Keyboard-only selection works.
- [ ] Focus remains visible in tested themes.
- [ ] Arabic checkout and selector flow correctly in RTL.
- [ ] Screen-reader labels distinguish approximate secondary price.

## Regression and release

- [ ] HPOS on and off/compatibility mode pass.
- [ ] Classic and block checkout pass.
- [ ] PHP lint, JS syntax, PHPCS, PHPUnit, smoke tests pass.
- [ ] Plugin Check has no unresolved errors.
- [ ] Version headers, stable tag, changelog, and Git tag match.
- [ ] Installable ZIP contains one root plugin folder and no dev dependencies/secrets.
- [ ] SHA-256 checksum is published.
- [ ] Backup/restore exists for the staging test site.
