<?php

namespace AICW\API;

use AICW\Controllers\Chat_Controller;

class Rest_API
{
    public static function init()
    {
        add_action(
            'rest_api_init',
            [self::class, 'register_routes']
        );
    }

    public static function register_routes()
    {
        register_rest_route(
            'ai-chatbot/v1',
            '/message',
            [
                'methods' => 'POST',
                'callback' => [
                    new Chat_Controller(),
                    'process'
                ],
                'permission_callback' => '__return_true'
            ]
        );
    }
}