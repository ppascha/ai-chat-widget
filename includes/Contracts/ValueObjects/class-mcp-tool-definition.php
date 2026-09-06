<?php

/**
 * Purpose: Typed description of an MCP tool exposed to a model provider.
 * Highlights:
 * - Keeps tool identity and JSON-schema parameters together.
 * - Serializes to the current OpenAI-compatible tool payload shape.
 */

namespace AICW\Contracts\ValueObjects;

final readonly class Mcp_Tool_Definition
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters,
        public string $type = 'function',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => $this->parameters,
            ],
        ];
    }
}
