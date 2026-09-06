<?php

/**
 * Purpose: Unit coverage for the transient-backed conversation store.
 * Highlights:
 * - Verifies save/load/clear behavior through the WordPress storage contract.
 * - Verifies transient keys are normalized so client IDs stay safe.
 */

namespace AICW\Tests\Unit;

use AICW\Services\WordPress_Message_Store;
use PHPUnit\Framework\TestCase;

final class WordPressMessageStoreTest extends TestCase
{
    protected function setUp(): void
    {
        // Start from a clean in-memory transient map so each case is isolated.
        $GLOBALS['aicw_test_transients'] = [];
    }

    public function testItPersistsLoadsAndClearsConversationState(): void
    {
        // Given: a store instance and a client-supplied conversation identifier.
        $store = new WordPress_Message_Store();
        $conversationId = 'Conversation 123';
        $messages = [
            ['role' => 'user', 'content' => 'hello'],
            ['role' => 'assistant', 'content' => 'hi'],
        ];

        // When: messages are saved and reloaded, then the conversation is cleared.
        $store->save($conversationId, $messages);
        $loaded = $store->load($conversationId);

        // Then: the saved transient exists before the clear operation.
        self::assertArrayHasKey('aicw_chat_history_conversation-123', $GLOBALS['aicw_test_transients']);

        $store->clear($conversationId);

        // Then: the transcript round-trips and the underlying transient entry disappears after clear.
        self::assertSame($messages, $loaded);
        self::assertArrayNotHasKey('aicw_chat_history_conversation-123', $GLOBALS['aicw_test_transients']);
        self::assertSame([], $store->load($conversationId));
    }
}
