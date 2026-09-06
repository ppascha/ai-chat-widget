<?php

/**
 * Purpose: Provision the current in-process MCP app for the demo WordPress Storefront Integration.
 * Highlights:
 * - Keeps MCP app construction behind the provisioner contract.
 * - Preserves the existing catalog-to-MCP wiring for the demo WordPress deployment.
 */

namespace AICW\Services;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Mcp_Provisioner_Interface;
use AICW\Contracts\Product_Catalog_Interface;

class WordPress_Mcp_Provisioner implements Mcp_Provisioner_Interface
{
    public function provision(Product_Catalog_Interface $catalog): Mcp_App_Interface
    {
        return new WordPress_Mcp_App($catalog);
    }
}
