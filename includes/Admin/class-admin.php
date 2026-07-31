<?php

namespace AICW\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purpose: Add an admin menu and render the plugin settings page.
 *
 * This class handles admin page tabs and the page layout.
 */
class Admin
{
    private const MENU_SLUG = 'aicw_settings_page';

    /**
     * Boot the admin interface.
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    /**
     * Register the top-level admin menu page.
     */
    public static function register_menu(): void
    {
        add_menu_page(
            'AI Chat Widget',
            'AI Chat Widget',
            'manage_options',
            self::MENU_SLUG,
            [self::class, 'render_settings_page'],
            'dashicons-format-chat',
            80
        );
    }

    /**
     * Register settings and page rendering support.
     */
    public static function register_settings(): void
    {
        Admin_Settings::init();
    }

    /**
     * Render the full admin settings page with tabs.
     */
    public static function render_settings_page(): void
    {
        $current_tab = self::get_current_tab();
        $style_class = 'style' === $current_tab ? 'nav-tab-active' : '';
        $resources_class = 'resources' === $current_tab ? 'nav-tab-active' : '';

        echo '<div class="wrap">';
        echo '<h1>AI Chat Widget Settings</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo sprintf(
            '<a href="%s" class="nav-tab %s">Style</a>',
            esc_url(self::get_tab_url('style')),
            esc_attr($style_class)
        );
        echo sprintf(
            '<a href="%s" class="nav-tab %s">Resources</a>',
            esc_url(self::get_tab_url('resources')),
            esc_attr($resources_class)
        );
        echo '</h2>';

        echo '<form method="post" action="options.php">';
        settings_fields('aicw_settings');
        do_settings_sections('aicw_settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Determine the currently selected admin tab.
     *
     * @return string The active tab slug.
     */
    public static function get_current_tab(): string
    {
        if (isset($_GET['tab']) && 'resources' === $_GET['tab']) {
            return 'resources';
        }

        return 'style';
    }

    /**
     * Build the URL for a named tab.
     *
     * @param string $tab Tab slug.
     * @return string URL for the tab.
     */
    public static function get_tab_url(string $tab): string
    {
        return add_query_arg(
            [
                'page' => self::MENU_SLUG,
                'tab' => $tab,
            ],
            admin_url('admin.php')
        );
    }
}
