<?php

/**
 * Purpose: Define the shared Template Method for MCP provisioning.
 * Highlights:
 * - Standardizes capability preparation before MCP app construction.
 * - Keeps integration-specific tool/resource decisions in concrete provisioners.
 * - Preserves one public provisioning entrypoint for all Storefront Integrations.
 */

namespace AICW\Services;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Mcp_Provisioner_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;

abstract class Abstract_Mcp_Provisioner implements Mcp_Provisioner_Interface
{
    final public function provision(Product_Catalog_Interface $catalog): Mcp_App_Interface
    {
        // Template flow: concrete provisioners define capabilities, then app construction consumes them.
        $provisioningResult = $this->provisionCapabilities($catalog);

        return $this->buildMcpApp($catalog, $provisioningResult);
    }

    abstract protected function provisionCapabilities(Product_Catalog_Interface $catalog): Mcp_Provisioning_Result;

    abstract protected function buildMcpApp(
        Product_Catalog_Interface $catalog,
        Mcp_Provisioning_Result $provisioningResult
    ): Mcp_App_Interface;
}
