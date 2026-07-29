<?php

/**
 * Purpose: Conversation history persistence abstraction.
 * Highlights:
 * - Allows storage backend swaps without changing chat-loop logic.
 * - Encapsulates load/save/clear operations per conversation ID.
 */

namespace AICW\Contracts;

interface Message_Store_Interface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function load(string $conversationId): array;

    /**
     * @param array<int, array<string, mixed>> $messages
     */
    public function save(string $conversationId, array $messages): void;

    public function clear(string $conversationId): void;
}