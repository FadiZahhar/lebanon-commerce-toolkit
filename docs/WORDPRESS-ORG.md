# WordPress.org Submission Guide

## Final naming

Recommended directory name and requested slug:

```text
lebanon-commerce-toolkit
```

Public name:

```text
Lebanon Commerce Toolkit for WooCommerce
```

The slug does not begin with the WooCommerce trademark; the public name describes compatibility using “for WooCommerce.” Approval remains at the directory team's discretion.

## Before submission

1. Create/sign in to the WordPress.org account that will own the plugin.
2. Replace `Contributors: prosolutionsnet` in `readme.txt` with the exact approved WordPress.org username when different.
3. Confirm the author name/URI and support destination are correct.
4. Run all automated checks and the release QA matrix.
5. Build the installable ZIP.
6. Open the ZIP and manually inspect every file.
7. Confirm the plugin contains no minified code without source, telemetry, upsells, remote assets, credentials, or test customer data.

## Submission package

Submit:

```text
dist/lebanon-commerce-toolkit-<version>.zip
```

Do not submit the full source/development ZIP.

## Suggested submission description

> Lebanon Commerce Toolkit is a free WooCommerce localization extension for Lebanese merchants. It provides governorate/district checkout fields for classic and block checkout, Lebanese phone normalization, a manual informational secondary-currency display, and a shipping-zone method with district/governorate rates. It makes no external requests, collects no telemetry, and uses WooCommerce CRUD and official Checkout Block APIs.

## Review response discipline

- Answer review emails directly and technically.
- Fix the underlying issue instead of suppressing checks.
- Do not rename the plugin/slug without updating text domain, constants, paths, blocks, REST namespace decisions, tests, and release automation.
- Keep all distributed code human-readable.

## SVN layout after approval

```text
assets/
  banner-772x250.png
  banner-1544x500.png
  icon-128x128.png
  icon-256x256.png
trunk/
tags/0.1.0/
```

The GitHub deploy action maps `.wordpress-org/` to the SVN `assets/` directory.

## Publishing assets

- Keep essential text inside the safe central area.
- Do not put version numbers in evergreen banners.
- Use real plugin UI screenshots only after the staging flow is final.
- Avoid trademark confusion or implying official WooCommerce endorsement.

## Support expectations

A free public plugin creates a maintenance obligation. Publish a clear support policy covering:

- Supported WordPress/WooCommerce/PHP versions.
- Security reporting.
- Whether custom delivery datasets/configuration are in scope.
- Response expectations without promising 24/7 support.
- The distinction between community plugin support and paid Pro-Solutions implementation work.
