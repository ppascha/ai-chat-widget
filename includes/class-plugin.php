<?php

/**
 * Purpose: WordPress deployment composition root that wires the selected Storefront Integration to plugin services.
 * Highlights:
 * - Registers activation/init hooks and bootstrap order.
 * - Builds chat loop, MCP app, catalog, and REST controllers for the WordPress deployment runtime.
 */

namespace AICW;

if (!defined('ABSPATH')) {
    exit;
}


require_once AICW_PATH . 'includes/class-assets.php';
require_once AICW_PATH . 'includes/class-widget.php';

require_once AICW_PATH . 'includes/Content/class-product-post-type.php';

require_once AICW_PATH . 'includes/Contracts/interface-chat-loop.php';
require_once AICW_PATH . 'includes/Contracts/interface-openai-client.php';
require_once AICW_PATH . 'includes/Contracts/interface-message-store.php';
require_once AICW_PATH . 'includes/Contracts/interface-mcp-app.php';
require_once AICW_PATH . 'includes/Contracts/interface-mcp-provisioner.php';
require_once AICW_PATH . 'includes/Contracts/interface-product-catalog.php';
require_once AICW_PATH . 'includes/Contracts/interface-storefront-integration.php';
require_once AICW_PATH . 'includes/Contracts/interface-storefront-integration-factory.php';

require_once AICW_PATH . 'includes/API/class-rest-api.php';

require_once AICW_PATH . 'includes/Controllers/class-product-controller.php';
require_once AICW_PATH . 'includes/Controllers/class-product-iframe-controller.php';
require_once AICW_PATH . 'includes/Controllers/class-chat-controller.php';

require_once AICW_PATH . 'includes/Services/class-wordpress-mcp-app.php';
require_once AICW_PATH . 'includes/Services/Abstract_Mcp_Provisioner.php';
require_once AICW_PATH . 'includes/Services/class-wordpress-mcp-provisioner.php';
require_once AICW_PATH . 'includes/Services/class-wordpress-message-store.php';
require_once AICW_PATH . 'includes/Services/class-wordpress-product-catalog.php';
require_once AICW_PATH . 'includes/Services/class-chat-loop.php';
require_once AICW_PATH . 'includes/Services/class-llm-service.php';
require_once AICW_PATH . 'includes/Services/class-storefront-integration-factory.php';
require_once AICW_PATH . 'includes/Services/Integrations/class-demo-wp-storefront-integration.php';


use AICW\API\Rest_API;
use AICW\Controllers\Chat_Controller;
use AICW\Controllers\Product_Iframe_Controller;
use AICW\Controllers\Product_Controller;
use AICW\Contracts\Chat_Loop_Interface;
use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Message_Store_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Content\Product_Post_Type;
use AICW\Services\LLM_Service;
use AICW\Services\Chat_Loop;
use AICW\Services\Integrations\Demo_WP_Storefront_Integration;
use AICW\Services\Storefront_Integration_Factory;
use AICW\Services\WordPress_Message_Store;


class Plugin
{
    private static ?Chat_Loop_Interface $chatLoop = null;

    private static ?Message_Store_Interface $messageStore = null;

    private static ?Product_Catalog_Interface $catalog = null;

    private static ?Mcp_App_Interface $mcpApp = null;

    public static function register_activation_hooks(): void
    {
        // Register activation so WordPress can flush rewrites for the site-owned product routes.
        register_activation_hook(
            AICW_PATH . 'ai-chat-widget.php',
            [self::class, 'activate']
        );
    }

    public static function activate(): void
    {
        // Register the content type before flushing rewrites so the site-owned product routes resolve.
        Product_Post_Type::register_post_type();
        flush_rewrite_rules();
    }

    public static function init(?string $storefrontIntegrationKey = null)
    {
        // Register the product content type on every request so the site owns the product records.
        Product_Post_Type::register();

        $storefrontIntegrationKey = self::resolveStorefrontIntegrationKey($storefrontIntegrationKey);
        $integrationFactory = new Storefront_Integration_Factory();

        // Extensions can add Storefront Integrations without changing this deployment composition root.
        $storefrontIntegrations = apply_filters(
            'aicw_storefront_integrations',
            [new Demo_WP_Storefront_Integration()]
        );

        foreach ($storefrontIntegrations as $storefrontIntegration) {
            $integrationFactory->register($storefrontIntegration);
        }

        $storefrontIntegration = $integrationFactory->create($storefrontIntegrationKey);

        // Message store is a swap point: transients now, DB/remote store later.
        self::$messageStore = new WordPress_Message_Store();
        // The selected Storefront Integration owns its catalog and MCP capability construction.
        self::$catalog = $storefrontIntegration->productCatalog();
        self::$mcpApp = $storefrontIntegration->mcpApp();
        // Chat loop owns message state and tool recursion for each conversation turn.
        self::$chatLoop = new Chat_Loop(
            self::$messageStore,
            self::$mcpApp,
            new LLM_Service()
        );

        Assets::init();

        Widget::init();

        // Frontend iframe rendering stays in WordPress so product cards resolve via same-origin routes.
        Product_Iframe_Controller::init(self::$catalog);

        // REST API exposes entrypoints for chat and product resources.
        Rest_API::init(
            self::$chatLoop,
            self::$catalog,
            self::$mcpApp
        );
    }

    private static function resolveStorefrontIntegrationKey(?string $storefrontIntegrationKey): string
    {
        $configuredKey = $storefrontIntegrationKey;

        if (null === $configuredKey && defined('AICW_STOREFRONT_INTEGRATION')) {
            $configuredKey = (string) AICW_STOREFRONT_INTEGRATION;
        }

        // Filter/constant configuration keeps Storefront Integration choice outside service classes.
        return (string) apply_filters(
            'aicw_storefront_integration_key',
            '' !== trim((string) $configuredKey) ? trim((string) $configuredKey) : 'demo-wp'
        );
    }

    public static function chat_loop(): Chat_Loop_Interface
    {
        // Lazy init keeps direct static usage safe during plugin bootstrap order.
        if (null === self::$chatLoop) {
            self::init();
        }

        return self::$chatLoop;
    }

    public static function product_catalog(): Product_Catalog_Interface
    {
        // Lazy init keeps direct static usage safe during plugin bootstrap order.
        if (null === self::$catalog) {
            self::init();
        }

        return self::$catalog;
    }

    public static function mcp_app(): Mcp_App_Interface
    {
        // Lazy init keeps direct static usage safe during plugin bootstrap order.
        if (null === self::$mcpApp) {
            self::init();
        }

        return self::$mcpApp;
    }

}