# AI Chat Widget

The **AI Chat Widjet** is a `Wordpress plguin` kind of project.

## Local development

### Demo 1: Toy 5-product WP Storefront (default)

1. Copy `.env.example` to `.env`
2. Run `docker-compose --env-file .env up -d`

Visit [`http:localhost:8080`](`http://localhost:8080)

### Demo 2: WooTestSite1 (WooCommerce Storefront)

Boots the same stack against a WooCommerce-backed fixture so the WooCommerce Storefront Integration can be demoed end-to-end.

1. Copy `.env.example` to `.env`
2. Run `WP_FIXTURE=woo-test-site-1 AICW_STOREFRONT_INTEGRATION=woocommerce docker-compose --env-file .env up -d`
3. Wait for the `wp-init` container to finish (`docker-compose logs -f wp-init`), then visit [`http:localhost:8080`](`http://localhost:8080)

To switch back to Demo 1, run `docker-compose --env-file .env down -v` before starting again without the `WP_FIXTURE`/`AICW_STOREFRONT_INTEGRATION` overrides (volumes must be reset because the fixture and integration are selected at bootstrap time).

---

The plugin source is mounted live from this repo into `wp-content/plugins/ai-chat-widget`.

If you are on Windows and the bootstrap script fails with `line 2: illegal option -`, check that Git did not convert `scripts/bootstrap-site.sh` to CRLF. This repo pins shell scripts to LF via `.gitattributes`, so a fresh clone with normal Git settings should avoid the issue.

## Testing direction

* Use one toy WordPress site for runtime e2e coverage.
* Add a separate packaging/installability e2e path later that validates the built artifact is a valid, installable WordPress plugin.

## End-User - Installation

1. Download / Activate plugin.
2. Configure
3. Visit your website

Click the **AI Chat Widjet**, which should be visible on bottom (right) corner.

Ask for a product you are sercing for, by describing it's specs and see the lieve results !
