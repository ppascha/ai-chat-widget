<?php

/**
 * Purpose: Provide the demo WordPress Storefront Integration.
 * Highlights:
 * - Owns the current custom-post product catalog implementation for the demo site.
 * - Builds the in-process MCP app without exposing demo Storefront construction to the plugin bootstrap.
 */

namespace AICW\Services\Integrations;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\Storefront_Integration_Interface;
use AICW\Services\Integrations\Demo_WP_Mcp_Capability_Provider;
use AICW\Services\Integrations\Demo_WP_Mcp_Tool_Executor;
use AICW\Services\WordPress_Mcp_App_Factory;
use AICW\Services\WordPress_Mcp_Provisioner;
use AICW\Services\WordPress_Product_Catalog;

class Demo_WP_Storefront_Integration implements Storefront_Integration_Interface
{
    private Product_Catalog_Interface $catalog;

    private Mcp_App_Interface $mcpApp;

    public function __construct()
    {
        $this->catalog = new WordPress_Product_Catalog();
        $this->mcpApp = (new WordPress_Mcp_Provisioner(
            new Demo_WP_Mcp_Capability_Provider(),
            new WordPress_Mcp_App_Factory(),
            new Demo_WP_Mcp_Tool_Executor($this->catalog),
        ))->provision($this->catalog);
    }

    public function key(): string
    {
        return 'demo-wp';
    }

    public function isAvailable(): bool
    {
        return function_exists('get_posts');
    }

    public function productCatalog(): Product_Catalog_Interface
    {
        return $this->catalog;
    }

    public function mcpApp(): Mcp_App_Interface
    {
        return $this->mcpApp;
    }
}