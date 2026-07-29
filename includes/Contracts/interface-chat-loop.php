<?php

/**
 * Purpose: Chat loop orchestration contract for one user turn.
 * Highlights:
 * - Accepts user message and optional conversation ID.
 * - Returns normalized payload for REST and widget rendering.
 */

namespace AICW\Contracts;

interface Chat_Loop_Interface
{
    /**
     * @return array<string, mixed>
     */
    public function handle(string $message, string $conversationId = ''): array;
}