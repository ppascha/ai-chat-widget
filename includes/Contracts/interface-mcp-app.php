<?php

/**
 * Purpose: Stable capability boundary for MCP-style tools/resources.
 * Highlights:
 * - Declares tools/resources consumable by the chat loop.
 * - Executes tool calls behind a single contract for future remote extraction.
 */

namespace AICW\Contracts;

interface Mcp_App_Interface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function tools(): array;

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function executeTool(string $toolName, array $arguments): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resources(): array;

    public function productIframeUrl(string $slug): string;
}