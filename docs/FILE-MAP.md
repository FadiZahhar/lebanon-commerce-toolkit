# Repository File Map

This repository deliberately separates the installable runtime from development and release tooling.

## Runtime entry points

| Path | Responsibility |
|---|---|
| `lebanon-commerce-toolkit.php` | Plugin header, constants, lightweight PSR-4-style autoloader, compatibility declarations, and service composition root. |
| `uninstall.php` | Privacy-conscious uninstall routine. Settings are deleted only after the merchant explicitly enables deletion. Historical order/customer address metadata is retained. |
| `src/Core/` | Requirements, activation, upgrades, options, and service registration. |
| `src/Contracts/Service.php` | Small service-registration contract used by the composition root. |
| `src/Domain/` | Framework-light business rules for locations, phone normalization, currency conversion, and shipping-rate parsing. |
| `src/Integration/WooCommerce/` | WooCommerce checkout, customer, order, price, and shipping integrations. |
| `src/Admin/` | WooCommerce submenu and merchant settings. |
| `src/Frontend/` | Public assets, shortcodes, and dynamic Gutenberg blocks. |
| `src/Api/` | Read-only REST API for packaged Lebanon location data. |
| `data/lebanon-locations.php` | Versioned nine-governorate/26-district reference dataset plus legacy key aliases. |
| `assets/` | Dependency-free front-end/admin CSS and JavaScript. |
| `blocks/` | Dynamic block metadata and editor registration. No front-end JavaScript build is required. |
| `languages/` | Translation template for GlotPress, PO/MO workflows, or local translation tools. |
| `readme.txt` | WordPress.org-facing plugin description and release metadata. |
| `LICENSE` | GPL-2.0-or-later license. |

## Development and quality tooling

| Path | Responsibility |
|---|---|
| `composer.json` | Development-only linting and unit-test dependencies. The runtime never loads Composer `vendor/`. |
| `phpcs.xml.dist` | WordPress Coding Standards and PHP 7.4+ compatibility configuration. |
| `phpunit.xml.dist` | Pure-domain PHPUnit configuration. |
| `tests/` | Dependency-free smoke suite and PHPUnit domain tests. |
| `.wp-env.json` | Repeatable local WordPress/WooCommerce environment configuration. |
| `blueprint.json` | WordPress Playground setup for previews and manual evaluation. |
| `bin/check-version.sh` | Ensures plugin, blocks, readme, and translation versions remain aligned. |
| `bin/check-json.php` | Validates repository JSON files. |
| `bin/audit-source.sh` | Scans tracked source for unsafe/debug patterns and probable secrets. |
| `bin/build-zip.sh` | Produces the WordPress-installable ZIP using `.distignore`. |
| `bin/build-source-zip.sh` | Produces a contributor/source ZIP without generated dependencies. |
| `bin/build-all.sh` | Rebuilds both packages and one deterministic checksum manifest. |
| `.github/workflows/ci.yml` | PHP matrix, domain tests, JavaScript syntax checks, source audit, and WordPress Plugin Check. |
| `.github/workflows/pr-preview.yml` | WordPress Playground preview link for pull requests. |
| `.github/workflows/release.yml` | Tag-driven GitHub release package generation. |
| `.github/workflows/deploy-wordpress-org.yml` | Manual WordPress.org SVN deployment after directory approval and secret setup. |
| `.wordpress-org/` | WordPress.org icon/banner artwork. |

## Documentation and governance

| Path | Responsibility |
|---|---|
| `README.md` | Contributor-facing project overview and quick start. |
| `docs/A-TO-Z-PLAN.md` | End-to-end implementation and publication plan. |
| `docs/ARCHITECTURE.md` | Design boundaries, data flow, compatibility strategy, and extension principles. |
| `docs/DATA-GOVERNANCE.md` | Administrative-data sourcing, aliasing, change control, and versioning. |
| `docs/GITHUB-SETUP.md` | Repository setup, protection, secrets, and release automation. |
| `docs/TESTING.md` | Test pyramid and compatibility matrix. |
| `docs/QA-CHECKLIST.md` | Manual release acceptance checklist. |
| `docs/VALIDATION.md` | Automated validation commands, release gates, and known local limitations. |
| `docs/RELEASE.md` | Versioning, build, tag, GitHub, and post-release procedure. |
| `docs/WORDPRESS-ORG.md` | WordPress.org submission and SVN deployment process. |
| `CONTRIBUTING.md` | Contribution workflow and coding expectations. |
| `SECURITY.md` | Private vulnerability-reporting policy. |
| `SUPPORT.md` | Community-support boundaries. |
| `CODE_OF_CONDUCT.md` | Contributor conduct. |

## Package boundaries

The installable ZIP contains only runtime files required by WordPress. It intentionally excludes tests, GitHub workflows, local-environment files, development dependencies, contributor documentation, and source-only build scripts.

The source ZIP contains the complete repository except generated dependencies, VCS internals, existing distribution packages, and operating-system artifacts.
