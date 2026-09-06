<?php

/**
 * Purpose: Minimal WordPress compatibility layer for Dockerized unit tests.
 * Highlights:
 * - Defines the WordPress functions and classes required by the service layer.
 * - Keeps unit tests isolated from a full WordPress bootstrap.
 * - Provides deterministic transient, UUID, and HTTP stubs for repeatable tests.
 */

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value, int $flags = 0, int $depth = 512): string
    {
        return json_encode($value, $flags, $depth) ?: '';
    }
}

if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        $sanitized = strtolower($key);
        $sanitized = preg_replace('/[^a-z0-9_-]+/', '-', $sanitized) ?: '';

        return trim($sanitized, '-');
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key)
    {
        return $GLOBALS['aicw_test_transients'][$key] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, $value, int $expiration = 0): bool
    {
        $GLOBALS['aicw_test_transients'][$key] = $value;

        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        unset($GLOBALS['aicw_test_transients'][$key]);

        return true;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(private readonly string $message = '', private readonly string $code = '')
        {
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post(string $url, array $args = [])
    {
        $handler = $GLOBALS['aicw_test_http_handler'] ?? null;

        if (is_callable($handler)) {
            return $handler($url, $args);
        }

        return [
            'response' => ['code' => 200],
            'body' => '{}',
        ];
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response): int
    {
        return (int) ($response['response']['code'] ?? 0);
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response): string
    {
        return (string) ($response['body'] ?? '');
    }
}

require_once __DIR__ . '/../includes/Contracts/interface-chat-loop.php';
require_once __DIR__ . '/../includes/Contracts/interface-message-store.php';
require_once __DIR__ . '/../includes/Contracts/interface-mcp-app.php';
require_once __DIR__ . '/../includes/Contracts/interface-openai-client.php';
require_once __DIR__ . '/../includes/Contracts/interface-product-catalog.php';
require_once __DIR__ . '/../includes/Content/class-product-post-type.php';
require_once __DIR__ . '/../includes/Services/class-chat-loop.php';
require_once __DIR__ . '/../includes/Services/class-llm-service.php';
require_once __DIR__ . '/../includes/Services/class-wordpress-message-store.php';
