# WooTestSite1

Plugin-agnostic WordPress fixture for a small WooCommerce storefront with demo products.

Select it with `WP_FIXTURE=woo-test-site-1`. The fixture is bootstrapped before plugin activation, so the AI Chat Widget can auto-detect WooCommerce at runtime. The fixture owns only WordPress/WooCommerce state and does not activate or configure the AI Chat Widget.

## Contents

- 5 WooCommerce products across 2 categories (PC and PlayStation).
- Site URL set to `http://localhost:8080` for local development.
- Idempotent seeding by SKU so repeated bootstraps recreate the same products.
