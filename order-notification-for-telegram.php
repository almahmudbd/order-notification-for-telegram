<?php
/**
 * Plugin Name: Order Notification For Telegram
 * Plugin URI: https://wordpress.org/plugins/order-notification-for-telegram/
 * Description: Send WooCommerce order notifications to Telegram
 * Version: 2.0.0
 * Author: Al Mahmud
 * Author URI: https://profiles.wordpress.org/almahmudbd
 * Text Domain: order-notification-for-telegram
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
define('ONTG_VERSION', '2.0.0');
define('ONTG_FILE', __FILE__);
define('ONTG_PATH', plugin_dir_path(ONTG_FILE));
define('ONTG_URL', plugin_dir_url(ONTG_FILE));

// Autoloader
spl_autoload_register(function($className) {
    $namespace = 'OrderNotificationTelegram\\';
    
    if (strpos($className, $namespace) !== 0) {
        return;
    }

    $className = str_replace($namespace, '', $className);
    $filePath = ONTG_PATH . str_replace('\\', '/', $className) . '.php';
    
    if (file_exists($filePath)) {
        require_once $filePath;
    }
});

// HPOS Compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize plugin
if (!function_exists('ONTGInit')) {
    function ONTGInit() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('Order Notification For Telegram requires WooCommerce to be installed and active.', 'order-notification-for-telegram') . 
                     '</p></div>';
            });
            return;
        }
        
        \OrderNotificationTelegram\Classes\Core::instance();
    }
}

add_action('plugins_loaded', 'ONTGInit');