<?php
namespace OrderNotificationTelegram\Classes;

class Core {
    private static $instance = null;
    private $telegram;
    
    // Constants
    const DEFAULT_TIMEOUT = 15;
    const DEFAULT_STATUS = 'wc-processing';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->check_version();
        $this->init_hooks();
        $this->setup_telegram();
    }
    
    private function check_version() {
        $installed_version = get_option('ontg_version', '0');
        if (version_compare($installed_version, ONTG_VERSION, '<')) {
            $this->upgrade($installed_version);
            update_option('ontg_version', ONTG_VERSION);
        }
    }
    
    private function upgrade($from_version) {
        // Add upgrade routines here if needed in future
    }
    
    public function init_hooks() {
        // Admin hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('woocommerce_get_settings_pages', [$this, 'add_settings_page']);
        add_action('wp_ajax_ontg_test_notification', [$this, 'handle_test_notification']);
        
        // Order notification hooks
        if (get_option('ontg_send_on_status_change', 'no') === 'yes') {
            add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change'], 10, 4);
        } else {
            add_action('woocommerce_new_order', [$this, 'handle_new_order']);
            add_action('woocommerce_checkout_order_processed', [$this, 'handle_new_order']);
        }
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style(
            'ontg-admin',
            ONTG_URL . 'assets/css/admin.css',
            [],
            ONTG_VERSION
        );
    }
    
    public function add_settings_page($settings) {
        $settings[] = new SettingPage();
        return $settings;
    }
    
    private function setup_telegram() {
        $token = get_option('ontg_bot_token');
        $chat_id = get_option('ontg_chat_id');
        
        $this->telegram = new Sender();
        $this->telegram->set_credentials($token, $chat_id);
    }
    
    public function handle_new_order($order_id) {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                error_log('ONTG Error - Order not found: ' . $order_id);
                return;
            }
            
            // Check if notification was already sent
            $notification_sent = $order->get_meta('_ontg_notification_sent');
            if ($notification_sent) {
                return;
            }
            
            $this->send_notification($order);
            
            // Mark notification as sent
            $order->update_meta_data('_ontg_notification_sent', 'yes');
            $order->save();
            
        } catch (\Exception $e) {
            error_log('ONTG Error - Failed to handle new order: ' . $e->getMessage());
        }
    }
    
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        try {
            if (!$order) {
                $order = wc_get_order($order_id);
                if (!$order) {
                    error_log('ONTG Error - Order not found: ' . $order_id);
                    return;
                }
            }
            
            $allowed_statuses = get_option('ontg_order_statuses', []);
            
            if (in_array('wc-' . $new_status, $allowed_statuses, true)) {
                $this->send_notification($order);
            }
        } catch (\Exception $e) {
            error_log('ONTG Error - Failed to handle status change: ' . $e->getMessage());
        }
    }
    
    public function handle_test_notification() {
        check_ajax_referer('ontg_test_notification', 'nonce');

        try {
            $token = get_option('ontg_bot_token');
            $chat_id = get_option('ontg_chat_id');

            if (empty($token) || empty($chat_id)) {
                wp_send_json_error(__('Please configure your Bot Token and Chat ID first.', 'order-notification-for-telegram'));
                return;
            }

            $test_message = "🔔 *Test Notification*\n\n";
            $test_message .= "This is a test message from your WooCommerce store.\n";
            $test_message .= "• Store URL: " . home_url() . "\n";
            $test_message .= "• Sent by: " . wp_get_current_user()->user_login . "\n";
            $test_message .= "• Time: " . current_time('mysql') . "\n\n";
            $test_message .= "If you're seeing this message, your Telegram notifications are working correctly! 👍";

            $this->telegram->set_credentials($token, $chat_id);
            $result = $this->telegram->send_message($test_message);

            if ($result) {
                wp_send_json_success(__('Test message sent successfully! Check your Telegram.', 'order-notification-for-telegram'));
            } else {
                wp_send_json_error(__('Failed to send test message. Please check your settings.', 'order-notification-for-telegram'));
            }
        } catch (\Exception $e) {
            wp_send_json_error(sprintf(
                __('Error: %s', 'order-notification-for-telegram'),
                $e->getMessage()
            ));
        }
    }
    
    private function send_notification($order) {
        try {
            $wc = new WooCommerce($order);
            $template = get_option('ontg_message_template');
            
            if (empty($template)) {
                $template = $this->get_default_template();
            }
            
            $message = $wc->get_formatted_message($template);
            return $this->telegram->send_message($message);
            
        } catch (\Exception $e) {
            error_log('ONTG Error - Failed to send notification: ' . $e->getMessage());
            return false;
        }
    }
    
    private function get_default_template() {
        return <<<TEMPLATE
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
    }
}