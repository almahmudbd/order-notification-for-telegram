<?php
namespace OrderNotificationTelegram\Classes;

class Core {
    private static $instance = null;
    private $telegram;
    
    const DEFAULT_TIMEOUT = 15;
    const DEFAULT_STATUS = 'wc-processing';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->setup_telegram();
    }
    
    public function init_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('woocommerce_get_settings_pages', [$this, 'add_settings_page']);
        add_action('wp_ajax_ontg_test_notification', [$this, 'handle_test_notification']);
        
        if (get_option('ontg_send_on_status_change', 'no') === 'yes') {
            add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change'], 10, 4);
        } else {
            add_action('woocommerce_new_order', [$this, 'handle_new_order'], 10, 1);
        }
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style('ontg-admin', ONTG_URL . 'assets/css/admin.css', [], ONTG_VERSION);
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
                return;
            }

            $template = get_option('ontg_message_template', TemplateManager::get_default_template());
            $wc = new WooCommerce($order);
            $message = $wc->get_formatted_message($template);

            if ($this->telegram->send_message($message)) {
                $order->update_meta_data('_ontg_notification_sent', 'yes');
                $order->save();
            }
        } catch (\Exception $e) {
            error_log('ONTG Error - Exception in handle_new_order: ' . $e->getMessage());
        }
    }
    
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        if (!$order) {
            $order = wc_get_order($order_id);
        }

        $allowed_statuses = get_option('ontg_order_statuses', [self::DEFAULT_STATUS]);

        if (in_array('wc-' . $new_status, $allowed_statuses, true)) {
            $this->handle_new_order($order_id);
        }
    }
    
    public function handle_test_notification() {
        check_ajax_referer('ontg_test_notification', 'nonce');

        try {
            $token = get_option('ontg_bot_token');
            $chat_id = get_option('ontg_chat_id');

            $test_message = TemplateManager::get_default_template();
            $this->telegram->set_credentials($token, $chat_id);

            if ($this->telegram->send_message($test_message)) {
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
}