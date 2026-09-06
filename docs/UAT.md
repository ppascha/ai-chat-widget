# User Acceptance Testing

## Demo WP Storefront

1. Start the default fixture with `docker compose --env-file .env up -d`.
2. Open `http://localhost:8080`.
3. Open the chat widget and ask for a product such as `show me alpha pc`.
4. Confirm a text response and product iframe card appear.
5. Reload the page and confirm the conversation identifier remains available.

## WooTestSite1

1. Set `WP_FIXTURE=woo-test-site-1` and `AICW_STOREFRONT_INTEGRATION=woocommerce` in `.env`.
2. Run `docker compose --env-file .env down -v && docker compose --env-file .env up -d`.
3. Wait for `wp-init` to report fixture bootstrap completion.
4. Open `http://localhost:8080`.
5. Ask for a WooCommerce product and confirm product cards, links, and iframe previews.
6. Repeat the bootstrap after `down -v` and confirm the same seeded products are recreated.

## Expected failures

- If WooCommerce cannot be downloaded, `wp-init` should report the installation failure instead of silently claiming a seeded storefront.
- If an unavailable Storefront Integration is selected, the plugin should fail with an explicit integration-availability error.
- PHPUnit may report a coverage-driver warning when Xdebug or PCOV is not installed; test failures remain authoritative.
