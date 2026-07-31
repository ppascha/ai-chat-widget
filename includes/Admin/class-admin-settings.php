<?php

namespace AICW\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purpose: Register plugin settings and render settings fields.
 *
 * This class contains the admin setting definitions and validation logic.
 */
class Admin_Settings
{
    /**
     * Register the settings, sections, and fields for the admin page.
     */
    public static function init(): void
    {
        register_setting(
            'aicw_settings',
            'aicw_settings',
            [self::class, 'sanitize_settings']
        );

        add_settings_section(
            'aicw_style_section',
            'Floating Chat Style',
            [self::class, 'render_style_section'],
            'aicw_settings'
        );

        add_settings_field(
            'chat_icon_size',
            'Chat icon size',
            [self::class, 'render_chat_icon_size_field'],
            'aicw_settings',
            'aicw_style_section'
        );

        add_settings_field(
            'chat_icon_color',
            'Chat icon color',
            [self::class, 'render_chat_icon_color_field'],
            'aicw_settings',
            'aicw_style_section'
        );

        add_settings_field(
            'chat_size_mode',
            'Open chat size',
            [self::class, 'render_chat_size_mode_field'],
            'aicw_settings',
            'aicw_style_section'
        );

        add_settings_field(
            'chat_title',
            'Chat title',
            [self::class, 'render_chat_title_field'],
            'aicw_settings',
            'aicw_style_section'
        );

        add_settings_section(
            'aicw_training_section',
            'AI Training Resources',
            [self::class, 'render_training_section'],
            'aicw_settings'
        );

        add_settings_field(
            'training_prompt',
            'Training prompt text',
            [self::class, 'render_training_prompt_field'],
            'aicw_settings',
            'aicw_training_section'
        );

        add_settings_field(
            'training_resources',
            'Supporting resources',
            [self::class, 'render_training_resources_field'],
            'aicw_settings',
            'aicw_training_section'
        );
    }

    /**
     * Sanitize settings before saving them to the database.
     *
     * @param array<mixed> $input Raw options from the settings form.
     * @return array<mixed> Sanitized options.
     */
    public static function sanitize_settings(array $input): array
    {
        $sanitized = self::get_default_settings();

        if (isset($input['chat_icon_size'])) {
            $sanitized['chat_icon_size'] = sanitize_text_field($input['chat_icon_size']);
        }

        if (isset($input['chat_icon_color'])) {
            $sanitized['chat_icon_color'] = sanitize_hex_color($input['chat_icon_color']);
        }

        if (isset($input['chat_size_mode'])) {
            $sanitized['chat_size_mode'] = sanitize_text_field($input['chat_size_mode']);
        }

        if (isset($input['chat_title'])) {
            $sanitized['chat_title'] = sanitize_text_field($input['chat_title']);
        }

        if (isset($input['training_prompt'])) {
            $sanitized['training_prompt'] = sanitize_textarea_field($input['training_prompt']);
        }

        if (isset($input['training_resources'])) {
            $sanitized['training_resources'] = sanitize_textarea_field($input['training_resources']);
        }

        // TODO: Extend validation and normalization for each setting when training logic is implemented.
        return $sanitized;
    }

    /**
     * Render the style section description.
     */
    public static function render_style_section(): void
    {
        $current_tab = Admin::get_current_tab();
        if ('style' !== $current_tab) {
            return;
        }
        echo '<p>Configure the floating chat icon size and color. Styling is saved now; later this will update the live chat widget appearance.</p>';
    }

    /**
     * Render the training section description.
     */
    public static function render_training_section(): void
    {
        $current_tab = Admin::get_current_tab();
        if ('resources' !== $current_tab) {
            return;
        }
        echo '<p>Provide text and resource hints to shape the AI responses. Minimal logic is stored today; later this data can be used for fine-tuning or prompt enrichment.</p>';
    }

    /**
     * Render the chat icon size field.
     */
    public static function render_chat_icon_size_field(): void
    {
        if ('style' !== Admin::get_current_tab()) {
            return;
        }

        $settings = self::get_settings();
        $value = esc_attr($settings['chat_icon_size']);

        echo sprintf(
            '<input type="text" name="aicw_settings[chat_icon_size]" value="%s" class="regular-text" placeholder="e.g. 56px">',
            $value
        );
        echo '<p class="description">Specify the floating icon size in CSS units.</p>';
    }

    /**
     * Render the chat icon color field.
     */
    public static function render_chat_icon_color_field(): void
    {
        if ('style' !== Admin::get_current_tab()) {
            return;
        }

        $settings = self::get_settings();
        $value = esc_attr($settings['chat_icon_color']);

        echo sprintf(
            '<input type="text" name="aicw_settings[chat_icon_color]" value="%s" class="regular-text" placeholder="#00aaff">',
            $value
        );
        echo '<p class="description">Use a hex color code for the floating chat icon.</p>';
    }

    /**
     * Render the open chat size field.
     */
    public static function render_chat_size_mode_field(): void
    {
        if ('style' !== Admin::get_current_tab()) {
            return;
        }

        $settings = self::get_settings();
        $value = $settings['chat_size_mode'];

        echo '<select name="aicw_settings[chat_size_mode]">';
        echo sprintf(
            '<option value="default" %s>Default</option>',
            selected($value, 'default', false)
        );
        echo sprintf(
            '<option value="large" %s>Bigger</option>',
            selected($value, 'large', false)
        );
        echo '</select>';
        echo '<p class="description">Choose between the standard widget size and a larger layout for better iframe visibility.</p>';
    }

    /**
     * Render the chat title field.
     */
    public static function render_chat_title_field(): void
    {
        if ('style' !== Admin::get_current_tab()) {
            return;
        }

        $settings = self::get_settings();
        $value = esc_attr($settings['chat_title']);

        echo sprintf(
            '<input type="text" name="aicw_settings[chat_title]" value="%s" class="regular-text" placeholder="AI Assistant">',
            $value
        );
        echo '<p class="description">Set the chat window name displayed when the user opens the chat.</p>';
    }

    /**
     * Render the training prompt field.
     */
    public static function render_training_prompt_field(): void
    {
        if ('resources' !== Admin::get_current_tab()) {
            return;
        }

        $settings = self::get_settings();
        $value = esc_textarea($settings['training_prompt']);

        echo sprintf(
            '<textarea name="aicw_settings[training_prompt]" rows="6" cols="50" class="large-text">%s</textarea>',
            $value
        );
        echo '<p class="description">Enter a short text prompt that can later be used to influence AI responses.</p>';
    }

    /**
     * Render the training resources field.
     */
    public static function render_training_resources_field(): void
    {
        if ('resources' !== Admin::get_current_tab()) {
            return;
        }

        $settings = self::get_settings();
        $value = esc_textarea($settings['training_resources']);

        echo sprintf(
            '<textarea name="aicw_settings[training_resources]" rows="6" cols="50" class="large-text">%s</textarea>',
            $value
        );
        echo '<p class="description">Provide URLs, notes, or other resource text for future AI training logic.</p>';
    }

    /**
     * Get the current saved plugin settings merged with defaults.
     *
     * @return array<string, string> Settings values.
     */
    public static function get_settings(): array
    {
        $saved = get_option('aicw_settings', []);
        if (!is_array($saved)) {
            return self::get_default_settings();
        }

        return array_merge(self::get_default_settings(), $saved);
    }

    /**
     * Return the default settings used by the plugin.
     *
     * @return array<string, string> Default settings.
     */
    public static function get_default_settings(): array
    {
        return [
            'chat_icon_size' => '56px',
            'chat_icon_color' => '#00aaff',
            'chat_size_mode' => 'default',
            'chat_title' => 'AI Assistant',
            'training_prompt' => '',
            'training_resources' => '',
        ];
    }
}
