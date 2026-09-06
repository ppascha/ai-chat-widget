<?php

/**
 * Purpose: Typed description of an MCP resource exposed by a Storefront Integration.
 * Highlights:
 * - Keeps resource identity and presentation metadata together.
 * - Provides a stable serializable shape for local or remote MCP transport.
 */

namespace AICW\Contracts\ValueObjects;

final readonly class Mcp_Resource_Definition
{
    public function __construct(
        public string $uri,
        public string $name,
        public string $title,
        public string $description,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'uri' => $this->uri,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
