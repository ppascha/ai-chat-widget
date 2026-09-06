<?php

/**
 * Purpose: Provision the current in-process MCP app for the demo WordPress Storefront Integration.
 * Highlights:
 * - Composes capability provision and MCP app construction behind one entrypoint.
 * - Preserves the existing catalog-to-MCP wiring for the demo WordPress deployment.
 */

namespace AICW\Services;

use AICW\Contracts\Mcp_App_Factory_Interface;
use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Mcp_Capability_Provider_Interface;
use AICW\Contracts\Mcp_Provisioner_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\Mcp_Tool_Executor_Interface;

class WordPress_Mcp_Provisioner implements Mcp_Provisioner_Interface
{
    public function __construct(
        private readonly Mcp_Capability_Provider_Interface $capabilityProvider,
        private readonly Mcp_App_Factory_Interface $appFactory,
        private readonly Mcp_Tool_Executor_Interface $toolExecutor,
    ) {
    }

    public function provision(Product_Catalog_Interface $catalog): Mcp_App_Interface
    {
        // Compose capability preparation with app construction instead of inheriting a provisioning algorithm.
        $provisioningResult = $this->capabilityProvider->provide($catalog);

        return $this->appFactory->create($catalog, $provisioningResult, $this->toolExecutor);
    }
}
