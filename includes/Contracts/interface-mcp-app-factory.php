<?php

/**
 * Purpose: Construct an MCP app from a catalog and provisioned capabilities.
 * Highlights:
 * - Keeps app construction composable instead of inheritance-driven.
 * - Provides a seam for local or remote MCP app implementations.
 */

namespace AICW\Contracts;

use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;

interface Mcp_App_Factory_Interface
{
    public function create(
        Product_Catalog_Interface $catalog,
        Mcp_Provisioning_Result $provisioningResult,
        Mcp_Tool_Executor_Interface $toolExecutor
    ): Mcp_App_Interface;
}
