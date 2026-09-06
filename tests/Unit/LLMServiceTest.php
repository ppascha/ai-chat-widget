<?php

/**
 * Purpose: Unit coverage for the OpenAI transport adapter.
 * Highlights:
 * - Verifies request payload assembly, tool forwarding, and response decoding.
 * - Verifies configuration errors fail fast when the API key is absent.
 */

namespace AICW\Tests\Unit;

use AICW\Services\LLM_Service;
use PHPUnit\Framework\TestCase;

final class LLMServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the HTTP shim between tests so each case controls its own Given state.
        $GLOBALS['aicw_test_http_handler'] = null;
        putenv('OPENAI_API_KEY');
        putenv('OPENAI_BASE_URL');
        putenv('OPENAI_MODEL');
    }

    public function testItBuildsTheExpectedPayloadAndReturnsDecodedData(): void
    {
        // Given: a configured provider endpoint and a fake transport that records outbound payloads.
        putenv('OPENAI_API_KEY=test-key');
        putenv('OPENAI_BASE_URL=https://example.com/v1');
        putenv('OPENAI_MODEL=gpt-4.1-mini');

        $capturedRequest = [];
        $GLOBALS['aicw_test_http_handler'] = static function (string $url, array $args) use (&$capturedRequest): array {
            $capturedRequest = [
                'url' => $url,
                'args' => $args,
            ];

            return [
                'response' => ['code' => 200],
                'body' => wp_json_encode([
                    'choices' => [[
                        'message' => [
                            'content' => 'hello from provider',
                        ],
                    ]],
                ]),
            ];
        };

        $service = new LLM_Service();

        // When: the adapter sends a tool-enabled chat completion request.
        $response = $service->chat(
            [['role' => 'user', 'content' => 'hello']],
            [['type' => 'function', 'function' => ['name' => 'search_products']]],
            ['tool_choice' => 'required', 'temperature' => 0.2, 'max_tokens' => 128]
        );

        // Then: the provider payload is correct and the decoded body is returned intact.
        self::assertSame('hello from provider', $response['choices'][0]['message']['content']);
        self::assertSame('https://example.com/v1/chat/completions', $capturedRequest['url']);
        self::assertSame('Bearer test-key', $capturedRequest['args']['headers']['Authorization']);

        $payload = json_decode((string) $capturedRequest['args']['body'], true);

        self::assertSame('gpt-4.1-mini', $payload['model']);
        self::assertSame('required', $payload['tool_choice']);
        self::assertSame(0.2, $payload['temperature']);
        self::assertSame(128, $payload['max_tokens']);
        self::assertCount(1, $payload['tools']);
    }

    public function testItFailsFastWhenTheApiKeyIsMissing(): void
    {
        // Given: the transport is configured without a provider key.
        putenv('OPENAI_API_KEY=');
        putenv('OPENAI_BASE_URL=https://example.com/v1');
        putenv('OPENAI_MODEL=gpt-4.1-mini');

        $service = new LLM_Service();

        // When: the adapter is asked to send a request.
        // Then: the test expects a hard failure instead of an empty-provider fallback.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY is not configured.');

        $service->chat([['role' => 'user', 'content' => 'hello']]);
    }
}
