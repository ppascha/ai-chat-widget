<?php

/**
 * Purpose: Transport contract for model chat requests.
 * Highlights:
 * - Accepts model-ready messages and tool declarations.
 * - Keeps chat loop independent from any specific HTTP client/provider SDK.
 */

namespace AICW\Contracts;

interface OpenAI_Client_Interface
{
    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $tools = [], array $options = []): array;
}