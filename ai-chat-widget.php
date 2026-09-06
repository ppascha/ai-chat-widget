<?php
/**
 * Plugin Name: AI Chat Widget
 * Plugin URI: https://example.com
 * Description: Simple floating chat widget.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2+
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AICW_PATH', plugin_dir_path(__FILE__));
define('AICW_URL', plugin_dir_url(__FILE__));

require_once AICW_PATH . 'includes/class-plugin.php';

AICW\Plugin::register_activation_hooks();
// Defer init until all plugins have loaded so integrations like WooCommerce are detectable
// regardless of alphabetical plugin load order.
add_action('plugins_loaded', ['AICW\\Plugin', 'init']);