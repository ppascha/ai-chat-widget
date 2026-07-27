<?php

namespace AICW;

if (!defined('ABSPATH')) {
    exit;
}

class Assets
{
    public static function init()
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function enqueue()
    {
        wp_enqueue_style(
            'aicw-chat',
            AICW_URL . 'assets/css/chat.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'aicw-chat',
            AICW_URL . 'assets/js/chat.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'aicw-chat',
            'AICW_Config',
            [
                'restUrl' => rest_url('ai-chatbot/v1/message'),
                'nonce' => wp_create_nonce('wp_rest'),
            ]
        );
    }
}