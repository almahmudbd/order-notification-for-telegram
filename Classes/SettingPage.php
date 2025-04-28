<?php
namespace OrderNotificationTelegram\Classes;

class SettingPage extends \WC_Settings_Page {
    public function __construct() {
        $this->id = 'ontg_settings';
        $this->label = __('Telegram Notifications', 'order-notification-for-telegram');
        
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
        
        parent::__construct();
    }
    
    public function output() {
        global $current_section;
        
        // Header
        echo $this->render_header();
        
        // Settings Fields
        $settings = $this->get_settings($current_section);
        \WC_Admin_Settings::output_fields($settings);
        
        // Template Guide & Test Section
        echo $this->render_template_guide();
        echo $this->render_test_section();
    }
    
    private function render_header() {
        return sprintf(
            '<div class="ontg-header">
                <h1><span class="dashicons dashicons-admin-plugins"></span>%s</h1>
                <p>%s | %s</p>
            </div>',
            esc_html__('Telegram Notifications for WooCommerce', 'order-notification-for-telegram'),
            sprintf(__('Version %s', 'order-notification-for-telegram'), ONTG_VERSION),
            sprintf(
                __('By %s', 'order-notification-for-telegram'),
                '<a href="https://github.com/almahmudbd" target="_blank">Al Mahmud</a>'
            )
        );
    }
    
    public function get_settings($section = '') {
        $settings = array(
            // Basic Settings Section
            array(
                'title' => __('Basic Settings', 'order-notification-for-telegram'),
                'type'  => 'title',
                'desc'  => $this->get_basic_help_text(),
                'id'    => 'ontg_basic_section'
            ),
            array(
                'title'    => __('Bot Token', 'order-notification-for-telegram'),
                'type'     => 'text',
                'desc'     => __('Enter your Telegram bot token from @BotFather', 'order-notification-for-telegram'),
                'id'       => 'ontg_bot_token',
                'default'  => '',
                'class'    => 'regular-text',
                'css'      => 'min-width: 400px;',
                'desc_tip' => true,
            ),
            array(
                'title'    => __('Chat ID', 'order-notification-for-telegram'),
                'type'     => 'text',
                'desc'     => __('Enter your Telegram chat ID from @userinfobot', 'order-notification-for-telegram'),
                'id'       => 'ontg_chat_id',
                'default'  => '',
                'class'    => 'regular-text',
                'css'      => 'min-width: 400px;',
                'desc_tip' => true,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ontg_basic_section_end'
            ),
            
            // Notification Settings Section
            array(
                'title' => __('Notification Settings', 'order-notification-for-telegram'),
                'type'  => 'title',
                'id'    => 'ontg_notification_section'
            ),
            // Notification Settings Section
			array(
				'title'   => __('Send Notifications On', 'order-notification-for-telegram'),
				'type'    => 'radio',
				'id'      => 'ontg_send_on_status_change',
				'options' => array(
				'no'  => __('New Order Only', 'order-notification-for-telegram'),
				'yes' => __('Order Status Change', 'order-notification-for-telegram')
			),
				'default' => 'no',
				'desc'    => __('Choose when to send notifications', 'order-notification-for-telegram'),
				'desc_tip' => true,
			),
            array(
                'title'    => __('Order Statuses', 'order-notification-for-telegram'),
                'type'     => 'multiselect',
                'desc'     => __('Select order statuses that trigger notifications (Only applicable if "Order Status Change" is selected above)', 'order-notification-for-telegram'),
                'id'       => 'ontg_order_statuses',
                'class'    => 'wc-enhanced-select',
                'options'  => wc_get_order_statuses(),
                'default'  => array('wc-processing'),
                'css'      => 'min-width: 350px;',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ontg_notification_section_end'
            ),
            
            // Message Template Section
            array(
                'title' => __('Message Template', 'order-notification-for-telegram'),
                'type'  => 'title',
                'desc'  => __('Customize your notification message using the placeholders below.', 'order-notification-for-telegram'),
                'id'    => 'ontg_template_section'
            ),
            array(
                'title'    => __('Message Template', 'order-notification-for-telegram'),
                'type'     => 'textarea',
                'id'       => 'ontg_message_template',
                'default'  => $this->get_default_template(),
                'css'      => 'min-width: 500px; min-height: 200px; font-family: monospace;',
                'class'    => 'code'
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ontg_template_section_end'
            ),
        );
        
        return apply_filters('ontg_settings', $settings, $section);
    }
    
    private function get_basic_help_text() {
        $help = '<div class="ontg-help-text">';
        $help .= '<p><strong>' . __('Quick Setup Guide:', 'order-notification-for-telegram') . '</strong></p>';
        $help .= '<ol>';
        
        // Step 1: Bot Token
        $help .= '<li>' . sprintf(
            __('Create a Telegram bot: Message %s and send:', 'order-notification-for-telegram'),
            '<a href="https://t.me/BotFather" target="_blank">@BotFather</a>'
        ) . '
            <ul>
                <li><code>/start</code></li>
                <li><code>/newbot</code></li>
                <li>' . __('Follow the instructions and copy the bot token', 'order-notification-for-telegram') . '</li>
            </ul>
        </li>';
        
        // Step 2: Chat ID
        $help .= '<li>' . sprintf(
            __('Get your Chat ID: Message %s and send %s', 'order-notification-for-telegram'),
            '<a href="https://t.me/userinfobot" target="_blank">@userinfobot</a>',
            '<code>/start</code>'
        ) . '</li>';
        
        // Step 3: Configuration
        $help .= '<li>' . __('Enter both the Bot Token and Chat ID above', 'order-notification-for-telegram') . '</li>';
        
        $help .= '</ol>';
        $help .= '</div>';
        
        return $help;
    }
    
    private function render_template_guide() {
        ob_start();
        ?>
        <div class="ontg-template-guide">
            <h2><?php _e('Template Guide', 'order-notification-for-telegram'); ?></h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Available Placeholders -->
                <div class="ontg-placeholders">
                    <h3><?php _e('Available Placeholders', 'order-notification-for-telegram'); ?></h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Placeholder', 'order-notification-for-telegram'); ?></th>
                                <th><?php _e('Description', 'order-notification-for-telegram'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $placeholders = array(
                                '{order_id}' => __('Order ID', 'order-notification-for-telegram'),
                                '{order_date_created}' => __('Order Date and Time', 'order-notification-for-telegram'),
                                '{total}' => __('Order Total Amount', 'order-notification-for-telegram'),
                                '{products}' => __('Product List', 'order-notification-for-telegram'),
                                '{billing_first_name}' => __('Customer\'s First Name', 'order-notification-for-telegram'),
                                '{billing_phone}' => __('Customer\'s Phone', 'order-notification-for-telegram'),
                                '{billing_address_1}' => __('Address Line 1', 'order-notification-for-telegram'),
                                '{billing_city}' => __('City', 'order-notification-for-telegram'),
                                '{payment_method}' => __('Payment Method', 'order-notification-for-telegram'),
                            );
                            
                            foreach ($placeholders as $placeholder => $description) {
                                printf(
                                    '<tr><td><code>%s</code></td><td>%s</td></tr>',
                                    esc_html($placeholder),
                                    esc_html($description)
                                );
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- HTML Tags -->
                <div class="ontg-html-tags">
                    <h3><?php _e('Supported HTML Tags', 'order-notification-for-telegram'); ?></h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Tag', 'order-notification-for-telegram'); ?></th>
                                <th><?php _e('Example', 'order-notification-for-telegram'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tags = array(
                                '<b>' => __('Bold text', 'order-notification-for-telegram'),
                                '<i>' => __('Italic text', 'order-notification-for-telegram'),
                                '<u>' => __('Underlined text', 'order-notification-for-telegram'),
                                '<s>' => __('Strikethrough text', 'order-notification-for-telegram'),
                                '<code>' => __('Monospace text', 'order-notification-for-telegram'),
                                '<a href="">' => __('Link', 'order-notification-for-telegram'),
                            );
                            
                            foreach ($tags as $tag => $example) {
                                printf(
                                    '<tr><td><code>%s</code></td><td>%s</td></tr>',
                                    esc_html($tag),
                                    esc_html($example)
                                );
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Example Template -->
            <div class="ontg-example-template" style="margin-top: 20px;">
                <h3><?php _e('Example Template', 'order-notification-for-telegram'); ?></h3>
                <pre style="background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap;">
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
                </pre>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_test_section() {
        ob_start();
        ?>
        <div class="ontg-test-section">
            <h2><?php _e('Test Your Settings', 'order-notification-for-telegram'); ?></h2>
            <p><?php _e('Send a test message to verify your Telegram notification settings.', 'order-notification-for-telegram'); ?></p>
            
            <button type="button" class="button button-primary" id="ontg-test-button">
                <?php _e('Send Test Message', 'order-notification-for-telegram'); ?>
            </button>
            <span class="spinner" style="float: none; margin-top: 0;"></span>
            <p class="ontg-test-result" style="display: none; margin-top: 10px;"></p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ontg-test-button').click(function() {
                var $button = $(this);
                var $spinner = $button.next('.spinner');
                var $result = $('.ontg-test-result');
                
                $button.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $result.hide();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ontg_test_notification',
                        nonce: '<?php echo wp_create_nonce('ontg_test_notification'); ?>'
                    },
                    success: function(response) {
                        $result.html(response.data)
                              .removeClass('notice-error notice-success')
                              .addClass(response.success ? 'notice-success' : 'notice-error')
                              .show();
                    },
                    error: function() {
                        $result.html('<?php _e('Error: Could not send test message.', 'order-notification-for-telegram'); ?>')
                              .removeClass('notice-success')
                              .addClass('notice-error')
                              .show();
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $spinner.css('visibility', 'hidden');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
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
(<a href="' . admin_url("post.php?post={order_id}&action=edit") . '">check order</a>)) | (mgs <a href="https://wa.me/88{billing_phone}">whatsapp</a>) | copy: <code>{billing_phone}</code>
TEMPLATE;
    }
    
    public function save() {
        global $current_section;
        \WC_Admin_Settings::save_fields($this->get_settings($current_section));
    }
}