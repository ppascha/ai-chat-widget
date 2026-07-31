<?php
use AICW\Admin\Admin_Config;

if (!defined('ABSPATH')) {
    exit;
}

$style_settings = Admin_Config::get_style_settings();
$chat_icon_size = esc_attr($style_settings['chat_icon_size'] ?: '56px');
$chat_icon_color = esc_attr($style_settings['chat_icon_color'] ?: '#00aaff');
$chat_size_mode = $style_settings['chat_size_mode'] === 'large' ? 'large' : 'default';
$chat_title = esc_html($style_settings['chat_title'] ?: 'AI Assistant');

$chat_style = '';
if ('large' === $chat_size_mode) {
    $chat_style = 'width: calc(min(90vw, 920px)); height: 90vh; top: 5%; left: 50%; transform: translateX(-50%);';
}
?>

<style>
    /* Apply saved admin style settings to the floating chat icon and open chat header. */
    #aicw-toggle {
        width: <?php echo $chat_icon_size; ?>;
        height: <?php echo $chat_icon_size; ?>;
        background: <?php echo $chat_icon_color; ?>;
    }

    #aicw-header,
    #aicw-send {
        background: <?php echo $chat_icon_color; ?>;
    }
</style>

<div id="aicw-toggle">
    💬
</div>

<div id="aicw-chat" class="closed" style="<?php echo esc_attr($chat_style); ?>">

    <div id="aicw-header">
        <span><?php echo $chat_title; ?></span>

        <button id="aicw-close">
            −
        </button>
    </div>


    <div id="aicw-messages">

    </div>


    <div id="aicw-input">

        <input
            type="text"
            id="aicw-message"
            placeholder="Ask something..."
        >

        <button id="aicw-send">
            Send
        </button>

    </div>

</div>