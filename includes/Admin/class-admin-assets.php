<?php

namespace AICW\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purpose: Enqueue admin page assets for the plugin settings screen.
 */
class Admin_Assets
{
    /**
     * Hook into admin asset loading.
     */
    public static function init(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Enqueue CSS for the plugin admin page.
     *
     * @param string $hook_suffix Current admin page hook suffix.
     */
    public static function enqueue_assets(string $hook_suffix): void
    {
        if (strpos($hook_suffix, 'aicw_settings_page') === false) {
            return;
        }

        wp_enqueue_style(
            'aicw-admin-style',
            AICW_URL . 'assets/css/chat.css',
            [],
            '1.0.0'
        );
    }
}
