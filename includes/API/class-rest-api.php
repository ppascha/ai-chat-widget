<?php

/**
 * Purpose: Register REST entrypoints used by the chat widget and product rendering.
 * Highlights:
 * - Exposes the chat route used by the browser widget.
 * - Exposes product routes used for catalog reads and iframe HTML content.
 */

namespace AICW\API;

use AICW\Controllers\Chat_Controller;
use AICW\Controllers\Product_Controller;
use AICW\Contracts\Chat_Loop_Interface;
use AICW\Contracts\Mcp_App_Interface;
use AICW\Contracts\Product_Catalog_Interface;

class Rest_API
{
    public static function init(
        Chat_Loop_Interface $chatLoop,
        Product_Catalog_Interface $catalog,
        Mcp_App_Interface $mcpApp
    )
    {
        // Register routes at the WordPress REST bootstrap stage so callbacks are available for all requests.
        add_action(
            'rest_api_init',
            static fn () => self::register_routes($chatLoop, $catalog, $mcpApp)
        );
    }

    public static function register_routes(
        Chat_Loop_Interface $chatLoop,
        Product_Catalog_Interface $catalog,
        Mcp_App_Interface $mcpApp
    )
    {
        // Main widget route: browser -> chat loop. This is the only route the widget needs for chat turns.
        register_rest_route(
            'ai-chatbot/v1',
            '/message',
            [
                'methods' => 'POST',
                'callback' => [
                    new Chat_Controller($chatLoop, $mcpApp),
                    'process'
                ],
                'permission_callback' => '__return_true'
            ]
        );

        // Support route: lets clients inspect product cards directly (debugging or non-chat consumers).
        register_rest_route(
            'ai-chatbot/v1',
            '/products',
            [
                'methods' => 'GET',
                'callback' => [
                    new Product_Controller($catalog, $mcpApp),
                    'index'
                ],
                'permission_callback' => '__return_true'
            ]
        );

        // Support route: returns iframe HTML by slug for embedded product previews.
        register_rest_route(
            'ai-chatbot/v1',
            '/products/(?P<slug>[a-z0-9-]+)/iframe',
            [
                'methods' => 'GET',
                'callback' => [
                    new Product_Controller($catalog, $mcpApp),
                    'iframe'
                ],
                'permission_callback' => '__return_true'
            ]
        );
    }
}