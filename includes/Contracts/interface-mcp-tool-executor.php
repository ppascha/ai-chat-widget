<?php

/**
 * Purpose: Execute a provisioned MCP tool without embedding Storefront behavior in the MCP app.
 * Highlights:
 * - Keeps tool dispatch behind a small composable contract.
 * - Allows each Storefront Integration to own its execution semantics.
 */

namespace AICW\Contracts;

interface Mcp_Tool_Executor_Interface
{
    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public function execute(string $toolName, array $arguments): array;
}
