<?php
/**
 * Core Class for Telegram WooCommerce Notifications
 * 
 * @since 2.0.0
 * @package NineKolor\TelegramWC
 */

namespace NineKolor\TelegramWC\Classes;
use NineKolor\TelegramWC\Classes\WooCommerce as Woo;

class Core
{
    /**
     * @var Sender
     */
    public $telegram;

    /**
     * @var Core
     */
    protected static $_instance = null;

    /**
     * Singleton instance
     *
     * @return Core
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->setTelegram();
        $this->hooks();
        $this->language();
    }

    /**
     * Load plugin text domain
     */
    public function language() {
        load_plugin_textdomain(
            'nktgnfw', 
            false, 
            trailingslashit(dirname(plugin_basename(__DIR__))) . 'languages'
        );
    }

    /**
     * Initialize hooks
     */
    public function hooks() {
        // Add WooCommerce settings
        add_filter('woocommerce_get_settings_pages', array($this, 'addWooSettingSection'));
        
        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script'));

        // HPOS compatibility declaration
        add_action('before_woocommerce_init', function() {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });

        // Order notifications
        $order_status_changed_enabled = get_option('nktgnfw_send_after_order_status_changed', false);
        
        if ($order_status_changed_enabled == 'yes') {
            add_action(
                'woocommerce_order_status_changed', 
                array($this, 'woocommerce_order_status_changed'), 
                20, 
                4
            );
        } else {
            add_action(
                'woocommerce_checkout_order_processed', 
                array($this, 'woocommerce_new_order')
            );
        }
    }

    /**
     * Initialize Telegram sender
     */
    public function setTelegram() {
        $this->telegram = new Sender();
        $this->telegram->chatID = get_option('nktgnfw_setting_chatid');
        $this->telegram->token = get_option('nktgnfw_setting_token');
    }

    /**
     * Send new order notification to Telegram
     *
     * @param int $orderID
     * @return void
     */
    public function sendNewOrderToTelegram($orderID) {
        try {
            $order = wc_get_order($orderID);
            if (!$order) {
                return;
            }

            $wc = new Woo($orderID);
            $template = get_option('nktgnfw_setting_template');
            $message = $wc->getBillingDetails($template);
            $this->telegram->sendMessage($message);
        } catch (\Exception $e) {
            // Log error if needed
            error_log('Telegram notification error: ' . $e->getMessage());
        }
    }

    /**
     * Add WooCommerce settings page
     *
     * @param array $settings
     * @return array
     */
    public function addWooSettingSection($settings) {
        $settings[] = new SettingPage();
        return $settings;
    }

    /**
     * Handle new order notification
     *
     * @param int $order_id
     * @return void
     */
    public function woocommerce_new_order($order_id) {
        try {
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            // Check if notification was already sent using order meta
            $was_sent = $order->get_meta('telegramWasSent', true);
            if (!$was_sent) {
                $order->update_meta_data('telegramWasSent', 1);
                $order->save();
                $this->sendNewOrderToTelegram($order_id);
            }
        } catch (\Exception $e) {
            error_log('New order notification error: ' . $e->getMessage());
        }
    }

    /**
     * Handle order status change notification
     *
     * @param int $order_id
     * @param string $status_transition_from
     * @param string $status_transition_to
     * @param WC_Order $order
     * @return void
     */
    public function woocommerce_order_status_changed($order_id, $status_transition_from, $status_transition_to, $order) {
        try {
            if (!$order || !($order instanceof \WC_Order)) {
                $order = wc_get_order($order_id);
                if (!$order) {
                    return;
                }
            }

            $statuses = get_option('nktgnfw_order_statuses', array());
            if (empty($statuses)) {
                return;
            }

            // Check if current status is in selected statuses
            $current_status = 'wc-' . $order->get_status();
            if (in_array($current_status, $statuses, true)) {
                $this->sendNewOrderToTelegram($order->get_id());
            }
        } catch (\Exception $e) {
            error_log('Order status change notification error: ' . $e->getMessage());
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_script() {
        $plugin_url = plugin_dir_url(__FILE__);
        
        wp_enqueue_style(
            'nktgnfw', 
            $plugin_url . '../assets/css/admin.css', 
            array(), 
            NKTGNFW_VERSION,
            'all'
        );

        wp_enqueue_script(
            'nktgnfw', 
            $plugin_url . '../assets/js/admin.js', 
            array('jquery'), 
            NKTGNFW_VERSION,
            true
        );
    }
}