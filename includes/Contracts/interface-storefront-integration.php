<?php

/**
 * Purpose: Define a plug-in Storefront Integration for MCP provisioning.
 * Highlights:
 * - Lets the plugin detect available Storefront systems without hardcoding one vendor.
 * - Connects each Storefront Integration to its catalog and MCP provisioner.
 */

namespace AICW\Contracts;

interface Storefront_Integration_Interface
{
    /**
    * Stable Storefront Integration key used by configuration and factory selection.
     */
    public function key(): string;

    /**
    * Report whether this Storefront Integration can be used on the current WordPress site.
     */
    public function isAvailable(): bool;

    public function productCatalog(): Product_Catalog_Interface;

    public function mcpApp(): Mcp_App_Interface;
}
