<?php

namespace AICW\Content;

if (!defined('ABSPATH')) {
    exit;
}

class Product_Post_Type
{
    public const POST_TYPE = 'aicw_product';

    /**
     * Hook the post type registration into WordPress init.
     */
    public static function register(): void
    {
        add_action('init', [self::class, 'register_post_type']);
    }

    /**
     * Register a lightweight product-like post type owned by the site.
     */
    public static function register_post_type(): void
    {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name' => 'AI Products',
                    'singular_name' => 'AI Product',
                ],
                'public' => true,
                'show_in_rest' => true,
                'supports' => ['title', 'editor', 'excerpt'],
                'has_archive' => false,
                'rewrite' => ['slug' => 'ai-products'],
                'menu_icon' => 'dashicons-products',
            ]
        );
    }

}