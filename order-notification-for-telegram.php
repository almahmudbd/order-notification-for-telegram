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

// Load translations
add_action('init', function() {
    load_plugin_textdomain(
        'order-notification-for-telegram',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

// Plugin activation/deactivation
register_activation_hook(__FILE__, 'ontg_activate');
register_deactivation_hook(__FILE__, 'ontg_deactivate');

function ontg_activate() {
    add_option('ontg_version', ONTG_VERSION);
    
    // Set default template if not exists
    if (!get_option('ontg_message_template')) {
        $default_template = <<<TEMPLATE
New order at {order_date_created}, ORDER ID: <b>#{order_id}</b>
--
address: 
{billing_first_name} 
{billing_address_1}, {billing_city}.
{billing_phone}

--
Products: {products}
Total: <b>{total}</b> 
- {payment_method}

---
(<a href="admin_url/post.php?post={order_id}&action=edit">check order</a>) | (mgs <a href="https://wa.me/88{billing_phone}">whatsapp</a>) | copy: <code>{billing_phone}</code>
TEMPLATE;
        
        add_option('ontg_message_template', $default_template);
    }
}

function ontg_deactivate() {
    delete_option('ontg_version');
    // Settings are preserved by default
}