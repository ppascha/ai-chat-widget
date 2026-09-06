<?php

/**
 * Purpose: WordPress-native conversation history store backed by transients.
 * Highlights:
 * - Keeps message storage behind an interface so backend can be swapped later.
 * - Applies TTL to avoid unbounded growth for demo/spike environments.
 */

namespace AICW\Services;

use AICW\Contracts\Message_Store_Interface;

class WordPress_Message_Store implements Message_Store_Interface
{
    // Prefix namespaces chat history records to avoid collisions with unrelated transients.
    private const TRANSIENT_PREFIX = 'aicw_chat_history_';
    // 24h retention is enough for session-resume demos without long-lived storage coupling.
    private const TTL_SECONDS = 86400;

    public function load(string $conversationId): array
    {
        // Missing/expired transients resolve to an empty conversation transcript.
        $messages = get_transient($this->transientKey($conversationId));

        return is_array($messages) ? $messages : [];
    }

    public function save(string $conversationId, array $messages): void
    {
        // Persist the full transcript so subsequent turns can continue with prior context.
        set_transient($this->transientKey($conversationId), $messages, self::TTL_SECONDS);
    }

    public function clear(string $conversationId): void
    {
        // Explicit clear is useful for future "new chat" actions.
        delete_transient($this->transientKey($conversationId));
    }

    private function transientKey(string $conversationId): string
    {
        // sanitize_key keeps transient names valid and stable across client-provided IDs.
        return self::TRANSIENT_PREFIX . sanitize_key($conversationId);
    }
}