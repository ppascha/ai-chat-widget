<?php

/**
 * Purpose: Thin OpenAI Chat Completions transport adapter.
 * Highlights:
 * - Accepts pre-built messages/tools from chat loop without adding orchestration rules.
 * - Converts WordPress HTTP responses into decoded payloads or explicit runtime errors.
 */

namespace AICW\Services;

use AICW\Contracts\OpenAI_Client_Interface;

class LLM_Service implements OpenAI_Client_Interface
{
    public function chat(array $messages, array $tools = [], array $options = []): array
    {
        // API key is mandatory because this adapter intentionally avoids local fallback logic.
        $apiKey = trim((string) getenv('OPENAI_API_KEY'));

        if ('' === $apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $baseUrl = rtrim((string) getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1', '/');
        $model = trim((string) getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini');

        // Payload stays close to OpenAI schema; loop owns conversation/tool semantics.
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.4,
        ];

        if ([] !== $tools) {
            // Include tool declarations only when available to avoid unnecessary payload noise.
            $payload['tools'] = $tools;
            $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';
        }

        if (array_key_exists('max_tokens', $options)) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        if (array_key_exists('stream', $options)) {
            $payload['stream'] = (bool) $options['stream'];
        }

        $response = wp_remote_post(
            $baseUrl . '/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode($payload),
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response)) {
            // Preserve low-level network/client details for easier debugging in the REST layer.
            throw new \RuntimeException($response->get_error_message());
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($statusCode >= 400) {
            $errorMessage = $body;
            $decodedBody = json_decode($body, true);

            // Prefer provider-native message when available; it is usually clearer than raw JSON output.
            if (is_array($decodedBody) && isset($decodedBody['error']['message'])) {
                $errorMessage = (string) $decodedBody['error']['message'];
            }

            throw new \RuntimeException(sprintf('OpenAI request failed (%d): %s', $statusCode, $errorMessage));
        }

        $data = json_decode($body, true);

        if (!is_array($data)) {
            // Strict decode guard protects loop from operating on malformed provider responses.
            throw new \RuntimeException('OpenAI response could not be decoded.');
        }

        return $data;
    }
}