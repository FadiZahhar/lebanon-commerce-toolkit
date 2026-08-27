# Security Policy

## Supported versions

Until 1.0.0, only the latest tagged public beta receives security fixes. After 1.0.0, the current major release and the immediately preceding maintained branch will receive fixes when practical.

## Reporting a vulnerability

Do not publish exploitable details in a public issue.

Use GitHub's **Security → Report a vulnerability** private advisory flow for the repository. Include:

- Affected version and environment.
- Reproduction steps or proof of concept.
- Impact assessment.
- Suggested mitigation when known.
- Whether customer/order data is exposed.

The project will acknowledge, triage, patch, test, and coordinate disclosure through the private advisory. No bounty is promised.

## Security design

- No telemetry or external requests.
- No credentials stored by the plugin.
- Settings require `manage_woocommerce`.
- Public REST data is non-personal packaged reference data.
- Checkout inputs are sanitized and validated server-side.
- Order changes use WooCommerce CRUD APIs.
- WordPress/WooCommerce nonce handling is reused for their native admin and account forms.
