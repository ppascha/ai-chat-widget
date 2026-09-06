<?php

/**
 * Purpose: Build the current in-process WordPress MCP app.
 * Highlights:
 * - Composes the existing MCP app with the selected product catalog.
 * - Keeps app construction replaceable for future remote MCP runtimes.
 */

namespace AICW\Services;

use AICW\Contracts\Mcp_App_Factory_Interface;
use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\Mcp_Tool_Executor_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;

class WordPress_Mcp_App_Factory implements Mcp_App_Factory_Interface
{
    public function create(
        Product_Catalog_Interface $catalog,
        Mcp_Provisioning_Result $provisioningResult,
        Mcp_Tool_Executor_Interface $toolExecutor
    ): Mcp_App_Interface {
        return new WordPress_Mcp_App($catalog, $provisioningResult, $toolExecutor);
    }
}
