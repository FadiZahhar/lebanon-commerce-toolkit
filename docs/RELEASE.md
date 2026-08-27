# Release Procedure

## 1. Prepare

- Finish the release milestone.
- Update `CHANGELOG.md` and `readme.txt`.
- Confirm no secrets, customer data, test accounts, or private URLs exist.
- Confirm any location-data change has a reliable source and migration note.

## 2. Set one version everywhere

Update:

- Main plugin header `Version`.
- `LCT_VERSION` constant.
- each `blocks/*/block.json` `version`.
- `readme.txt` `Stable tag`.
- `CHANGELOG.md` release heading.
- translation template project-version header.

Then regenerate and verify the translation template:

```bash
python3 bin/make-pot.py
bash bin/check-version.sh
```

## 3. Validate source

```bash
composer install
composer test
bash bin/audit-source.sh
node --check assets/js/location-selector.js
node --check assets/js/classic-checkout.js
node --check assets/js/block-checkout.js
node --check blocks/editor.js
```

Complete `docs/QA-CHECKLIST.md` against the release matrix.

## 4. Build and inspect

```bash
bash bin/build-all.sh
unzip -t dist/lebanon-commerce-toolkit-<version>.zip
unzip -t dist/lebanon-commerce-toolkit-source-<version>.zip
unzip -l dist/lebanon-commerce-toolkit-<version>.zip
cat dist/SHA256SUMS.txt
```

The installable ZIP must extract to:

```text
lebanon-commerce-toolkit/lebanon-commerce-toolkit.php
```

It must not contain `.git`, `.github`, tests, Composer vendor packages, development docs, or credentials.

## 5. Commit and tag

```bash
git add .
git commit -m "Release <version>"
git tag -s <version> -m "Lebanon Commerce Toolkit <version>"
git push origin main <version>
```

The tag triggers the GitHub release package workflow. Inspect the generated release and checksum before public announcement.

## 6. Deploy to WordPress.org

Only after WordPress.org approval and SVN secrets are configured:

1. Open **Actions → Deploy to WordPress.org**.
2. Run the workflow for the exact numeric tag, or publish the matching GitHub release when automatic deployment is enabled.
3. Inspect `trunk`, `tags/<version>`, and the plugin page assets.
4. Install the WordPress.org ZIP on a clean staging site and repeat the critical checkout smoke test.

## 7. Post-release

- Create the next `Unreleased` changelog section.
- Announce the release with a concise upgrade note.
- Monitor support for fatal errors, checkout blocks, shipping calculation, and location-data reports.
- Patch security issues through a coordinated private advisory.
