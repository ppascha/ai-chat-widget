<?php

/**
 * Purpose: Unit coverage for the chat loop orchestration boundary.
 * Highlights:
 * - Verifies tool-choice behavior for product and non-product prompts.
 * - Verifies tool call fan-out and deterministic product copy.
 * - Verifies conversation state is persisted through the message store contract.
 */

namespace AICW\Tests\Unit;

use AICW\Services\Chat_Loop;
use AICW\Tests\Support\FakeMcpApp;
use AICW\Tests\Support\FakeMessageStore;
use AICW\Tests\Support\FakeOpenAIClient;
use PHPUnit\Framework\TestCase;

final class ChatLoopTest extends TestCase
{
    public function testItForcesToolUseAndReturnsDeterministicProductCopy(): void
    {
        // Given: a product-intent prompt, one product result, and a model that first calls a tool.
        $messageStore = new FakeMessageStore();
        $mcpApp = new FakeMcpApp(
            tools: [[
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Search products by keyword.',
                ],
            ]],
            toolResults: [
                'search_products' => [
                    'products' => [[
                        'title' => 'Alpha PC',
                        'slug' => 'alpha-pc',
                    ]],
                ],
            ]
        );
        $openAI = new FakeOpenAIClient([
            [
                'choices' => [[
                    'message' => [
                        'tool_calls' => [[
                            'id' => 'call-1',
                            'function' => [
                                'name' => 'search_products',
                                'arguments' => wp_json_encode(['query' => 'alpha pc']),
                            ],
                        ]],
                    ],
                ]],
            ],
            [
                'choices' => [[
                    'message' => [
                        'content' => 'assistant fallback text',
                    ],
                ]],
            ],
        ]);
        $loop = new Chat_Loop($messageStore, $mcpApp, $openAI);

        // When: the loop handles a product request with an explicit conversation ID.
        $result = $loop->handle('show me alpha pc', 'conversation-123');

        // Then: the reply is product mode, the message is deterministic, and the transcript is persisted.
        self::assertSame('products', $result['kind']);
        self::assertSame('Here is Alpha PC.', $result['message']);
        self::assertCount(1, $result['products']);
        self::assertSame('conversation-123', $result['conversationId']);
        self::assertCount(1, $mcpApp->toolCalls);
        self::assertSame('search_products', $mcpApp->toolCalls[0]['name']);
        self::assertSame(['query' => 'alpha pc'], $mcpApp->toolCalls[0]['arguments']);
        self::assertSame('required', $openAI->calls[0]['options']['tool_choice']);
        self::assertSame('auto', $openAI->calls[1]['options']['tool_choice']);
        self::assertCount(5, $messageStore->store['conversation-123']);
    }

    public function testItReturnsTextWhenAssistantDoesNotRequestTools(): void
    {
        // Given: a conversational prompt that does not look like a product search.
        $messageStore = new FakeMessageStore([
            'conversation-456' => [[
                'role' => 'system',
                'content' => 'existing context',
            ]],
        ]);
        $mcpApp = new FakeMcpApp(tools: [[
            'type' => 'function',
            'function' => [
                'name' => 'search_products',
                'description' => 'Search products by keyword.',
            ],
        ]]);
        $openAI = new FakeOpenAIClient([
            [
                'choices' => [[
                    'message' => [
                        'content' => 'Plain answer from the assistant.',
                    ],
                ]],
            ],
        ]);
        $loop = new Chat_Loop($messageStore, $mcpApp, $openAI);

        // When: the loop handles a non-product request.
        $result = $loop->handle('hello there', 'conversation-456');

        // Then: the response stays in text mode and the transcript records the assistant answer.
        self::assertSame('text', $result['kind']);
        self::assertSame('Plain answer from the assistant.', $result['message']);
        self::assertSame([], $result['products']);
        self::assertSame('conversation-456', $result['conversationId']);
        self::assertSame('auto', $openAI->calls[0]['options']['tool_choice']);
        self::assertCount(3, $messageStore->store['conversation-456']);
    }
}
