<?php

/**
 * Purpose: Conversation loop that manages message state, tool recursion, and final response shaping.
 * Highlights:
 * - Loads/saves conversation history through a storage-agnostic message store.
 * - Iterates assistant tool calls until a final natural-language answer is produced.
 */

namespace AICW\Services;

use AICW\Contracts\Chat_Loop_Interface;
use AICW\Contracts\Message_Store_Interface;
use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\OpenAI_Client_Interface;

class Chat_Loop implements Chat_Loop_Interface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $cachedTools = [];

    public function __construct(
        private readonly Message_Store_Interface $messageStore,
        private readonly Mcp_App_Interface $mcpApp,
        private readonly OpenAI_Client_Interface $openAI,
    ) {
        // Tool declarations are cached per service instance to avoid rebuilding schemas each turn.
        $this->cachedTools = $this->mcpApp->tools();
    }

    public function handle(string $message, string $conversationId = ''): array
    {
        // Resolve/load conversation state first so a request can continue an earlier multi-turn session.
        $conversationId = $this->normalizeConversationId($conversationId);
        $messages = $this->messageStore->load($conversationId);
        $startingMessageCount = count($messages);

        // Seed system prompt once per new conversation history.
        if ([] === $messages) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->systemPrompt(),
            ];
        }

        // Append the latest user turn before calling the model.
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $products = [];

        // Hard-stop loop guard protects against malformed endless tool-call cycles.
        for ($iteration = 0; $iteration < 8; $iteration++) {
            $response = $this->openAI->chat($messages, $this->cachedTools, [
                'tool_choice' => 'auto',
                'temperature' => 0.4,
            ]);

            $assistantMessage = $response['choices'][0]['message'] ?? null;

            if (!is_array($assistantMessage)) {
                throw new \RuntimeException('OpenAI response missing assistant message.');
            }

            // Keep the assistant output in history even if it requests tools.
            $messages[] = $assistantMessage;

            // No tool calls means we reached a terminal assistant answer for this turn.
            if ([] === ($assistantMessage['tool_calls'] ?? [])) {
                $kind = [] === $products ? 'text' : 'products';
                // Trust the model's own phrasing always; cards render additively from $products
                // without ever overriding the assistant's natural-language answer.
                $messageText = (string) ($assistantMessage['content'] ?? '');

                // Persist full conversation after each successful turn so future turns can resume context.
                $this->messageStore->save($conversationId, $messages);

                // Debug summary for verifying if product reply and iframe render originate from one or many stored entries.
                error_log('AICW chat_loop summary ' . wp_json_encode([
                    'conversationId' => $conversationId,
                    'kind' => $kind,
                    'startingMessageCount' => $startingMessageCount,
                    'endingMessageCount' => count($messages),
                    'assistantText' => $messageText,
                    'productsCount' => count($products),
                ]));

                // Debug detail for the last role entries to confirm assistant/tool boundaries in message history.
                $tailMessages = array_slice($messages, -4);
                error_log('AICW chat_loop tail ' . wp_json_encode(array_map(
                    static function (array $entry): array {
                        return [
                            'role' => (string) ($entry['role'] ?? ''),
                            'hasToolCalls' => !empty($entry['tool_calls']),
                            'contentPreview' => substr((string) ($entry['content'] ?? ''), 0, 160),
                        ];
                    },
                    $tailMessages
                )));

                return [
                    'kind' => $kind,
                    'message' => $messageText,
                    'products' => $products,
                    'messages' => $messages,
                    'conversationId' => $conversationId,
                ];
            }

            foreach ($assistantMessage['tool_calls'] as $toolCall) {
                // Tool arguments arrive as JSON strings from model output; decode before execution.
                $arguments = json_decode((string) ($toolCall['function']['arguments'] ?? '{}'), true);

                if (!is_array($arguments)) {
                    // Defensive fallback keeps tool execution stable even if model emitted invalid JSON.
                    $arguments = [];
                }

                // Delegate tool execution to MCP app boundary so loop is storage/transport agnostic.
                $toolResult = $this->mcpApp->executeTool((string) $toolCall['function']['name'], $arguments);

                if (isset($toolResult['products']) && is_array($toolResult['products'])) {
                    // Track last product payload for widget card rendering convenience.
                    $products = $toolResult['products'];
                }

                // Feed tool output back into the transcript so model can continue reasoning with fresh data.
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) $toolCall['id'],
                    'content' => wp_json_encode($toolResult),
                ];
            }
        }

        throw new \RuntimeException('Tool loop did not converge.');
    }

    private function normalizeConversationId(string $conversationId): string
    {
        // Preserve caller-provided IDs so frontend/browser can own conversation continuity.
        if ('' !== trim($conversationId)) {
            return $conversationId;
        }

        // Fallback server-generated UUID keeps API usable for non-browser clients.
        return wp_generate_uuid4();
    }

    private function systemPrompt(): string
    {
        return 'You are a WordPress storefront assistant. Use product tools (list_products/find_products) whenever the user wants to browse or see specific items; the UI renders their results as cards automatically, so keep your own text brief in that case. For aggregate or descriptive questions about the catalog (e.g. counts, categories, general availability), answer directly and naturally in your own words instead of listing every item. Do not output markdown links or raw URLs for products.';
    }
}