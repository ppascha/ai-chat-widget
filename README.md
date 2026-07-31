# AI Chat Widget

The **AI Chat Widjet** is a `Wordpress plguin` kind of project.

## Local development

1. Copy `.env.example` to `.env`
2. Run `docker-compose --env-file .env up -d`

The plugin source is mounted live from this repo into `wp-content/plugins/ai-chat-widget`.

## Testing direction

* Use one toy WordPress site for runtime e2e coverage.
* Add a separate packaging/installability e2e path later that validates the built artifact is a valid, installable WordPress plugin.

## End-User - Installation

1. Download / Activate plugin.
2. Configure
3. Visit your website

Click the **AI Chat Widjet**, which should be visible on bottom (right) corner.

Ask for a product you are sercing for, by describing it's specs and see the lieve results !
