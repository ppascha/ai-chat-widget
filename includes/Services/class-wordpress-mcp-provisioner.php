<?php

/**
 * Purpose: Provision the current in-process MCP app for the demo WordPress Storefront Integration.
 * Highlights:
 * - Keeps MCP app construction behind the provisioner contract.
 * - Preserves the existing catalog-to-MCP wiring for the demo WordPress deployment.
 */

namespace AICW\Services;

use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;

class WordPress_Mcp_Provisioner extends Abstract_Mcp_Provisioner
{
    protected function provisionCapabilities(Product_Catalog_Interface $catalog): Mcp_Provisioning_Result
    {
        // The demo app retains its existing inline tool/resource compatibility behavior during this migration.
        return new Mcp_Provisioning_Result();
    }

    protected function buildMcpApp(
        Product_Catalog_Interface $catalog,
        Mcp_Provisioning_Result $provisioningResult
    ): \AICW\Contracts\Mcp_App_Interface {
        return new WordPress_Mcp_App($catalog);
    }
}
