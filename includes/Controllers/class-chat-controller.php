<?php

/**
 * Purpose: Translate REST chat requests into chat-loop calls and API-safe responses.
 * Highlights:
 * - Validates input and guarantees a stable conversation ID per request.
 * - Delegates orchestration to chat loop and maps returned products for the widget.
 */

namespace AICW\Controllers;

use AICW\Contracts\Chat_Loop_Interface;
use AICW\Contracts\Mcp_App_Interface;

class Chat_Controller
{
    public function __construct(
        private readonly Chat_Loop_Interface $chatLoop,
        private readonly Mcp_App_Interface $mcpApp
    ) {
    }

    public function process($request)
    {
        // Pull user payload from REST params; conversation_id keeps multi-turn history stable.
        $message = trim((string) $request->get_param('message'));
        $conversationId = trim((string) $request->get_param('conversation_id'));

        // Generate an ID server-side when the client is new or does not send one.
        if ('' === $conversationId) {
            $conversationId = wp_generate_uuid4();
        }

        // Fail fast on empty messages to avoid spending LLM/tool calls on invalid input.
        if ($message === '') {
            return new \WP_Error(
                'aicw_empty_message',
                'Message cannot be empty.',
                ['status' => 400]
            );
        }

        try {
            $response = $this->chatLoop->handle($message, $conversationId);
        } catch (\Throwable $error) {
            // Return transport/model failures as structured WP errors so JS can display the exact reason.
            return new \WP_Error(
                'aicw_llm_error',
                $error->getMessage(),
                [
                    'status' => 500,
                    'error' => $error->getMessage(),
                    'conversationId' => $conversationId,
                ]
            );
        }

        return [
            'success' => true,
            'kind' => $response['kind'] ?? 'text',
            'message' => (string) ($response['message'] ?? ''),
            // Echo resolved ID back to client so frontend can persist the exact server conversation key.
            'conversationId' => (string) ($response['conversationId'] ?? $conversationId),
            'products' => array_map(
                fn (array $product): array => $this->presentProduct($product),
                $response['products'] ?? []
            ),
        ];
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function presentProduct(array $product): array
    {
        return [
            'slug' => $product['slug'],
            'title' => $product['title'],
            'summary' => $product['summary'],
            'productUrl' => $product['productUrl'] ?? '',
            // Keep iframe URL generation behind MCP app so route strategy can change without controller changes.
            'iframeUrl' => $this->mcpApp->productIframeUrl($product['slug']),
        ];
    }
}