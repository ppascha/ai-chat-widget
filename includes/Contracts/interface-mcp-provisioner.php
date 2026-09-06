<?php

/**
 * Purpose: Provision an MCP capability boundary from a site data source.
 * Highlights:
 * - Keeps tool/resource construction outside the MCP app facade.
 * - Accepts a catalog abstraction so Storefront Integrations remain replaceable.
 */

namespace AICW\Contracts;

interface Mcp_Provisioner_Interface
{
    /**
     * Build an MCP app for the supplied product/catalog data source.
     */
    public function provision(Product_Catalog_Interface $catalog): Mcp_App_Interface;
}
