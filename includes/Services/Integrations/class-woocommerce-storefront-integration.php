<?php

/**
 * Purpose: Provide the WooCommerce Storefront Integration.
 * Highlights:
 * - Detects WooCommerce through its runtime API.
 * - Reuses the composed MCP capability boundary with a WooCommerce catalog.
 */

namespace AICW\Services\Integrations;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\Storefront_Integration_Interface;
use AICW\Services\WordPress_Mcp_App_Factory;
use AICW\Services\WordPress_Mcp_Provisioner;

class WooCommerce_Storefront_Integration implements Storefront_Integration_Interface
{
    private Product_Catalog_Interface $catalog;
    private Mcp_App_Interface $mcpApp;

    public function __construct()
    {
        $this->catalog = new WooCommerce_Product_Catalog();
        $this->mcpApp = (new WordPress_Mcp_Provisioner(
            new Catalog_Mcp_Capability_Provider(),
            new WordPress_Mcp_App_Factory(),
            new Catalog_Mcp_Tool_Executor($this->catalog),
        ))->provision($this->catalog);
    }

    public function key(): string { return 'woocommerce'; }

    public function isAvailable(): bool { return function_exists('wc_get_product'); }

    public function productCatalog(): Product_Catalog_Interface { return $this->catalog; }

    public function mcpApp(): Mcp_App_Interface { return $this->mcpApp; }
}
