<?php

namespace AICW\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purpose: Provide helper methods for admin configuration values.
 *
 * This class is a thin layer for reading saved admin settings.
 */
class Admin_Config
{
    /**
     * Get the current style settings for the chat widget.
     *
     * @return array<string, string>
     */
    public static function get_style_settings(): array
    {
        $settings = Admin_Settings::get_settings();

        return [
            'chat_icon_size' => $settings['chat_icon_size'],
            'chat_icon_color' => $settings['chat_icon_color'],
            'chat_size_mode' => $settings['chat_size_mode'],
            'chat_title' => $settings['chat_title'],
        ];
    }

    /**
     * Get the current training-related settings for the AI.
     *
     * @return array<string, string>
     */
    public static function get_training_settings(): array
    {
        $settings = Admin_Settings::get_settings();

        return [
            'training_prompt' => $settings['training_prompt'],
            'training_resources' => $settings['training_resources'],
        ];
    }
}
