<?php

/**
 * Purpose: Expose provisioned MCP capabilities through the WordPress runtime.
 * Highlights:
 * - Serializes tool and resource definitions for the chat loop.
 * - Delegates Storefront-specific tool execution to a composed executor.
 */

namespace AICW\Services;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Mcp_Tool_Executor_Interface;
use AICW\Contracts\Product_Catalog_Interface;
use AICW\Contracts\ValueObjects\Mcp_Provisioning_Result;

class WordPress_Mcp_App implements Mcp_App_Interface
{
    public function __construct(
        private readonly Product_Catalog_Interface $catalog,
        private readonly Mcp_Provisioning_Result $provisioningResult,
        private readonly Mcp_Tool_Executor_Interface $toolExecutor,
    ) {
    }

    public function tools(): array
    {
        return array_map(
            static fn ($tool): array => $tool->toArray(),
            $this->provisioningResult->tools
        );
    }

    public function executeTool(string $toolName, array $arguments): array
    {
        return $this->toolExecutor->execute($toolName, $arguments);
    }

    public function resources(): array
    {
        return array_map(
            static fn ($resource): array => $resource->toArray(),
            $this->provisioningResult->resources
        );
    }

    public function productIframeUrl(string $slug): string
    {
        return add_query_arg(
            'aicw_product_iframe',
            sanitize_title($slug),
            home_url('/')
        );
    }
}
