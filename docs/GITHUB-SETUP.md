# GitHub Repository Setup

## Recommended repository

```text
lebanon-commerce-toolkit
```

Suggested description:

```text
Free Lebanese checkout, phone, secondary-currency, and district-shipping toolkit for WooCommerce.
```

Topics:

```text
wordpress woocommerce lebanon shipping checkout php open-source
```

## First push

Create an empty repository without generating README/license files, then run from this source directory:

```bash
git init
git branch -M main
git add .
git commit -m "Initial Lebanon Commerce Toolkit public beta"
git remote add origin git@github.com:YOUR-GITHUB-OWNER/lebanon-commerce-toolkit.git
git push -u origin main
```

## Repository settings

### General

- Enable Issues and Discussions when you are prepared to maintain them.
- Disable Wikis unless you intend to keep a second documentation source synchronized.
- Enable **Automatically delete head branches**.
- Enable private vulnerability reporting under Security.

### Branch protection for `main`

Require:

- Pull request before merging.
- One approval for external contributions.
- Dismiss stale approvals after new commits.
- Status checks: `PHP and domain tests`, `WordPress Plugin Check`.
- Conversation resolution.
- Linear history.
- No force pushes and no deletions.

Allow maintainers to bypass only for coordinated security releases.

### Actions permissions

Use read permissions by default. Allow GitHub Actions to create/approve pull requests only when a workflow actually requires it. The PR Preview workflow declares its own narrow permission to update the pull-request description.

## Secrets for WordPress.org deployment

After the plugin is approved and its SVN repository exists, add repository secrets:

```text
SVN_USERNAME
SVN_PASSWORD
```

Use the WordPress.org SVN-specific password configured in the owning account’s profile settings. Never commit these values, put them in workflow files, or send them through issue comments.

## Labels

Recommended labels:

- `bug`
- `security-review`
- `checkout-blocks`
- `classic-checkout`
- `shipping`
- `location-data`
- `accessibility`
- `rtl`
- `good first issue`
- `needs reproduction`
- `breaking-change`

## Release tags

Use numeric semantic-version tags matching the plugin header and WordPress.org stable tag exactly:

```bash
git tag -s 0.1.0 -m "Lebanon Commerce Toolkit 0.1.0"
git push origin 0.1.0
```

Do not prefix WordPress.org release tags with `v`, because the SVN deploy workflow uses the Git tag as the plugin version/tag.
