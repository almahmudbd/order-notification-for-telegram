<?php
/**
 * Plugin Name: Order Notification For Telegram
 * Plugin URI: https://wordpress.org/plugins/order-notification-for-telegram/
 * Description: Send WooCommerce order notifications to Telegram.
 * Version: 2.4.0
 * Author: almahmud & ChoPlugins
 * Author URI: https://github.com/almahmudbd
 * Text Domain: order-notification-for-telegram
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

define('ONTG_VERSION', '2.4.0');
define('ONTG_FILE', __FILE__);
define('ONTG_PATH', plugin_dir_path(ONTG_FILE));
define('ONTG_URL', plugin_dir_url(ONTG_FILE));

// Autoloader
spl_autoload_register(function ($className) {
    $namespace = 'OrderNotificationTelegram\\';
    if (strpos($className, $namespace) !== 0) {
        return;
    }
    $relativeClass = str_replace($namespace, '', $className);
    $filePath = ONTG_PATH . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// WooCommerce HPOS Compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize plugin
add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                esc_html__('Order Notification For Telegram requires WooCommerce to be installed and active.', 'order-notification-for-telegram') .
                '</p></div>';
        });
        return;
    }

    // Load Core plugin class
    \OrderNotificationTelegram\Classes\Core::instance();
});

// ✅ Add "Settings" link under plugin name on Plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=ontg_settings');
    $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'order-notification-for-telegram') . '</a>';

    array_unshift($links, $settings_link);
    return $links;
});

// Load translations
add_action('init', function () {
    load_plugin_textdomain(
        'order-notification-for-telegram',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// Plugin activation and deactivation hooks
register_activation_hook(__FILE__, 'ontg_activate');
register_deactivation_hook(__FILE__, 'ontg_deactivate');

function ontg_activate() {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    // Add version and template defaults
    add_option('ontg_version', ONTG_VERSION);

    if (!get_option('ontg_message_template')) {
        add_option('ontg_message_template', \OrderNotificationTelegram\Classes\TemplateManager::get_default_template());
    }
}

function ontg_deactivate() {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    delete_option('ontg_version');
    // Preserve user settings for future reactivation
}
