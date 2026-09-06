<?php

/**
 * Purpose: Carry the typed capabilities produced by MCP provisioning.
 * Highlights:
 * - Groups provisioned tools and resources into one result value.
 * - Serializes capabilities without coupling provisioners to a transport.
 */

namespace AICW\Contracts\ValueObjects;

final readonly class Mcp_Provisioning_Result
{
    /**
     * @param array<int, Mcp_Tool_Definition> $tools
     * @param array<int, Mcp_Resource_Definition> $resources
     */
    public function __construct(
        public array $tools = [],
        public array $resources = [],
    ) {
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function toArray(): array
    {
        return [
            'tools' => array_map(
                static fn (Mcp_Tool_Definition $tool): array => $tool->toArray(),
                $this->tools
            ),
            'resources' => array_map(
                static fn (Mcp_Resource_Definition $resource): array => $resource->toArray(),
                $this->resources
            ),
        ];
    }
}
