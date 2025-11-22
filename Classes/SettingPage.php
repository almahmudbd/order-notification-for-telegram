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

        echo $this->render_header();

        $settings = $this->get_settings($current_section);
        \WC_Admin_Settings::output_fields($settings);

        // ✅ Save button just ABOVE Template Guide
        echo $this->render_save_button();

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
                '<a href="https://github.com/almahmudbd" target="_blank">almahmud</a>'
            )
        );
    }

    // ✅ Top save button + hide default WC save button at bottom to avoid duplicate
    private function render_save_button() {
        ob_start();
        ?>
        <style>
            /* hide default WC save button row(s) */
            .wrap.woocommerce form p.submit:not(.ontg-submit-top),
            .wrap.woocommerce form .woocommerce-save-button:not(.ontg-save-btn){
                display:none !important;
            }
        </style>

        <p class="submit ontg-submit-top" style="margin: 10px 0 18px;">
            <button name="save"
                    class="button-primary woocommerce-save-button ontg-save-btn"
                    type="submit"
                    value="<?php echo esc_attr__('Save changes', 'woocommerce'); ?>">
                <?php echo esc_html__('Save changes', 'woocommerce'); ?>
            </button>
        </p>
        <?php
        return ob_get_clean();
    }

    public function get_settings($section = '') {
        $settings = array(
            // Basic Settings
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

            // Notification Settings
            array(
                'title' => __('Notification Settings', 'order-notification-for-telegram'),
                'type'  => 'title',
                'id'    => 'ontg_notification_section'
            ),
            array(
                'title'   => __('Send Notifications On', 'order-notification-for-telegram'),
                'type'    => 'radio',
                'id'      => 'ontg_send_on_status_change',
                'options' => array(
                    'no'  => __('New Order Only', 'order-notification-for-telegram'),
                    'yes' => __('Order Status Change', 'order-notification-for-telegram')
                ),
                'default'  => 'no',
                'desc'     => __('Choose when to send notifications', 'order-notification-for-telegram'),
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

            // Quantity Bangla checkbox
            array(
                'title'    => __('Quantity in Bangla', 'order-notification-for-telegram'),
                'type'     => 'checkbox',
                'id'       => 'ontg_qty_bangla',
                'default'  => 'no',
                'desc'     => __('Show quantity using Bangla digits (e.g., ২পিস). If unchecked, uses English digits (e.g., 2pcs).', 'order-notification-for-telegram'),
                'desc_tip' => true,
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'ontg_notification_section_end'
            ),

            // Message Template
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
                'css'      => 'min-width: 500px; min-height: 220px; font-family: monospace;',
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
        $help  = '<div class="ontg-help-text">';
        $help .= '<p><strong>' . __('Quick Setup Guide:', 'order-notification-for-telegram') . '</strong></p>';
        $help .= '<ol>';

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

        $help .= '<li>' . sprintf(
            __('Get your Chat ID: Message %s and send %s', 'order-notification-for-telegram'),
            '<a href="https://t.me/userinfobot" target="_blank">@userinfobot</a>',
            '<code>/start</code>'
        ) . '</li>';

        $help .= '<li>' . __('Enter both the Bot Token and Chat ID above', 'order-notification-for-telegram') . '</li>';

        $help .= '</ol></div>';
        return $help;
    }

    private function render_template_guide() {
        ob_start();

        // ✅ ALL supported placeholders
        $placeholders_text = <<<TXT
Order:
{order_id}
{order_date_created}
{order_date}
{order_status}
{total}
{order_total}

Payment:
{payment_method}
{payment_method_title}

Products:
{products}

Delivery/Shipping:
{delivery_charge}
{delivery_method}

Fees:
{fees}
{fees_total}

Billing (underscore or dash both supported):
{billing_first_name} / {billing-first_name}
{billing_last_name}  / {billing-last_name}
{billing_company}    / {billing-company}
{billing_address_1}  / {billing-address_1}
{billing_address_2}  / {billing-address_2}
{billing_city}       / {billing-city}
{billing_state}      / {billing-state}
{billing_postcode}   / {billing-postcode}
{billing_country}    / {billing-country}
{billing_email}      / {billing-email}
{billing_phone}      / {billing-phone}
TXT;

        // ✅ ALL supported tags
        $tags_text = <<<TXT
<b>bold</b>
<i>italic</i>
<u>underline</u>
<s>strikethrough</s>
<code>monospace</code>
<a href="">link</a>
TXT;

        $codebox_style = 'width:100%;min-height:180px;background:#0b0b0b !important;color:#f5f5f5 !important;border:1px solid #2b2b2b;border-radius:8px;padding:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;font-size:13px;line-height:1.5;white-space:pre;resize:vertical;';
        ?>
        <div class="ontg-template-guide">
            <style>
                .ontg-codegrid{
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:16px;
                    margin-top:10px;
                }
                @media (max-width:768px){
                    .ontg-codegrid{ grid-template-columns:1fr; }
                }
                .ontg-example-wrap{ max-width:700px; }
            </style>

            <h2><?php _e('Template Guide', 'order-notification-for-telegram'); ?></h2>

            <div class="ontg-codegrid">
                <div>
                    <h3 style="margin:0 0 6px;"><?php _e('Available Placeholders', 'order-notification-for-telegram'); ?></h3>
                    <textarea readonly style="<?php echo esc_attr($codebox_style); ?>"><?php echo esc_textarea($placeholders_text); ?></textarea>
                </div>

                <div>
                    <h3 style="margin:0 0 6px;"><?php _e('Supported HTML Tags', 'order-notification-for-telegram'); ?></h3>
                    <textarea readonly style="<?php echo esc_attr($codebox_style); ?>"><?php echo esc_textarea($tags_text); ?></textarea>
                </div>
            </div>

            <div class="ontg-example-template ontg-example-wrap" style="margin-top:18px;">
                <h3 style="margin:0 0 6px;"><?php _e('Example Template', 'order-notification-for-telegram'); ?></h3>

                <textarea readonly style="<?php echo esc_attr($codebox_style); ?>min-height:220px;">New order at {order_date_created}, ORDER ID: <b>#{order_id}</b>
------
{billing_first_name} {billing_last_name}
{billing_address_1}, {billing_city}.
<code>{billing_phone}</code>

---{products}

Delivery: {delivery_method} - {delivery_charge}
Fees: {fees}
Total: <b>{total}</b>
- {payment_method_title}

-----
(<a href="admin_url/post.php?post={order_id}&action=edit">check order</a> | mgs <a href="https://wa.me/88{billing_phone}">whatsapp</a>)</textarea>
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
            <p class="ontg-test-result" style="display:none; margin-top:10px;"></p>
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
------
{billing_first_name} {billing_last_name}
{billing_address_1}, {billing_city}.
<code>{billing_phone}</code>

---{products}

Delivery: {delivery_method} - {delivery_charge}
Fees: {fees}
Total: <b>{total}</b>
- {payment_method_title}

-----
(<a href="admin_url/post.php?post={order_id}&action=edit">check order</a> | mgs <a href="https://wa.me/88{billing_phone}">whatsapp</a>)
TEMPLATE;
    }

    public function save() {
        global $current_section;
        \WC_Admin_Settings::save_fields($this->get_settings($current_section));
    }
}
