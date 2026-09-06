<?php

/**
 * Purpose: Shared unit-test fakes for the AI Chat Widget service layer.
 * Highlights:
 * - Keeps test setup compact and readable across multiple PHPUnit cases.
 * - Captures tool calls, saved messages, and HTTP requests for assertions.
 */

namespace AICW\Tests\Support;

use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Message_Store_Interface;
use AICW\Contracts\OpenAI_Client_Interface;

final class FakeMessageStore implements Message_Store_Interface
{
    public array $store = [];
    public array $savedConversations = [];

    public function __construct(array $initialStore = [])
    {
        $this->store = $initialStore;
    }

    public function load(string $conversationId): array
    {
        return $this->store[$conversationId] ?? [];
    }

    public function save(string $conversationId, array $messages): void
    {
        $this->store[$conversationId] = $messages;
        $this->savedConversations[] = $conversationId;
    }

    public function clear(string $conversationId): void
    {
        unset($this->store[$conversationId]);
    }
}

final class FakeMcpApp implements Mcp_App_Interface
{
    public array $toolCalls = [];

    public function __construct(
        private readonly array $tools = [],
        private readonly array $toolResults = [],
    ) {
    }

    public function tools(): array
    {
        return $this->tools;
    }

    public function executeTool(string $name, array $arguments = []): array
    {
        $this->toolCalls[] = [
            'name' => $name,
            'arguments' => $arguments,
        ];

        return $this->toolResults[$name] ?? ['message' => 'ok'];
    }

    public function resources(): array
    {
        return [];
    }

    public function productIframeUrl(string $slug): string
    {
        return 'http://localhost:8080/?aicw_product=' . $slug;
    }
}

final class FakeOpenAIClient implements OpenAI_Client_Interface
{
    public array $calls = [];

    public function __construct(private array $responses)
    {
    }

    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        $this->calls[] = [
            'messages' => $messages,
            'tools' => $tools,
            'options' => $options,
        ];

        if ([] === $this->responses) {
            throw new \RuntimeException('No queued OpenAI response was available.');
        }

        return array_shift($this->responses);
    }
}
