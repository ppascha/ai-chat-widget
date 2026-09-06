<?php

/**
 * Purpose: Provide typed MCP capabilities for a product/catalog source.
 * Highlights:
 * - Keeps capability definition separate from MCP app construction.
 * - Allows concrete Storefront Integrations to compose their own providers.
 */

namespace AICW\Contracts;

use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;

interface Mcp_Capability_Provider_Interface
{
    public function provide(Product_Catalog_Interface $catalog): Mcp_Provisioning_Result;
}
