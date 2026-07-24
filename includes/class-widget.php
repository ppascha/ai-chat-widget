<?php

namespace AICW;

if (!defined('ABSPATH')) {
    exit;
}

class Widget
{
    public static function init()
    {
        add_action('wp_footer', [self::class, 'render']);
    }

    public static function render()
    {
        include AICW_PATH . 'templates/chat.php';
    }
}