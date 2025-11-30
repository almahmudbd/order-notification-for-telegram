<?php
namespace OrderNotificationTelegram\Classes;

class WooCommerce {
    private $order;
    private $placeholders = [];

    public function __construct($order) {
        $this->order = $order;
        $this->setup_placeholders();
    }

    public function get_formatted_message($template) {
        // Replace admin_url placeholder
        $template = str_replace('admin_url', admin_url(), $template);
        return strtr($template, $this->placeholders);
    }

    private function setup_placeholders() {
        // Order details
        $this->placeholders['{order_id}'] = $this->order->get_id();
        $this->placeholders['{order_date_created}'] = $this->order->get_date_created()->date_i18n('F j, Y g:i a');
        $this->placeholders['{order_status}'] = wc_get_order_status_name($this->order->get_status());
        $this->placeholders['{total}'] = $this->order->get_formatted_order_total();
        $this->placeholders['{order_total}'] = $this->order->get_formatted_order_total();

        // Payment method - support both variants
        $this->placeholders['{payment_method}'] = $this->order->get_payment_method_title();
        $this->placeholders['{payment_method_title}'] = $this->order->get_payment_method_title();

        // Support both - and _ in billing fields for backward compatibility
        $billing_fields = [
            'first_name', 'last_name', 'company',
            'address_1', 'address_2', 'city',
            'state', 'postcode', 'country',
            'email', 'phone'
        ];

        foreach ($billing_fields as $field) {
            $method = 'get_billing_' . $field;
            $value = $this->order->$method();

            // Support both formats
            $this->placeholders['{billing_' . $field . '}'] = $value;
            $this->placeholders['{billing-' . $field . '}'] = $value;
        }

        // Products
        $this->placeholders['{products}'] = $this->get_products_list();

        // Shipping / delivery
        $this->placeholders['{delivery_charge}'] = $this->get_delivery_charge();
        $this->placeholders['{delivery_method}'] = $this->get_delivery_method();

        // Fees
        $this->placeholders['{fees}'] = $this->get_fees_list();
        $this->placeholders['{fees_total}'] = $this->get_fees_total();

        // Additional placeholders
        $this->placeholders['{order_date}'] = $this->order->get_date_created()->date_i18n('F j, Y g:i a');
    }

    /**
     * Build product list with:
     * - optional numbering
     * - qty before name
     * - optional Bangla qty
     * - optional hide single qty
     * - variation text shown once (no duplicates)
     */
    
    /**
     * Build product list with:
     * - optional numbering
     * - qty before name
     * - optional Bangla qty
     * - optional hide single qty
     * - variation text shown once
     */
    private function get_products_list() {
        $items = $this->order->get_items();
        if (empty($items)) {
            return '';
        }

        $qty_bangla      = get_option('ontg_qty_bangla', 'no') === 'yes';
        $hide_single_qty = get_option('ontg_hide_single_qty', 'yes') === 'yes';
        $numbered_list   = get_option('ontg_numbered_products', 'no') === 'yes';

        $list = "\n";
        $i = 1;

        foreach ($items as $item) {
            $name = $item->get_name();

            // Get variation attributes string (if any)
            $variation_string = '';
            if ($item->get_variation_id()) {
                $variation_string = $this->get_variation_attribute_string($item);
            }

            // If variation already appears in name, don't append again
            $variation_text = '';
            if ($variation_string && mb_strpos($name, $variation_string) === false) {
                $variation_text = ' - ' . $variation_string;
            }

            $qty = (int) $item->get_quantity();

            // Quantity label logic
            $qty_label = '';
            if (!($hide_single_qty && $qty === 1)) {
                if ($qty_bangla) {
                    $qty_label = $this->to_bangla_number($qty) . 'টি';
                } else {
                    $qty_label = 'x' . $qty;
                }
            }

            // Price formatted with WooCommerce currency settings (strip tags for Telegram)
            $price = strip_tags(wc_price((float) $item->get_total()));

            // Prefix based on numbering on/off
            $prefix = $numbered_list ? ($i . '. ') : ' - ';

            // Format:
            // With qty: "1. ২টি - Product - Variation ৳price"
            // Without qty (hidden single): "1. Product - Variation ৳price"
            if ($qty_label) {
                $list .= sprintf(
                    "%s%s - %s%s  %s\n",
                    $prefix,
                    $qty_label,
                    $name,
                    $variation_text,
                    $price
                );
            } else {
                $list .= sprintf(
                    "%s%s%s  %s\n",
                    $prefix,
                    $name,
                    $variation_text,
                    $price
                );
            }

            $i++;
        }

        return $list;
    }

private function get_variation_attribute_string($item) {
        $variation_data = $item->get_meta_data();
        $attributes = [];

        foreach ($variation_data as $meta) {
            // Exclude taxonomy attributes (pa_) and empty values
            if (strpos($meta->key, 'pa_') === false && !empty($meta->value)) {
                $attributes[] = $meta->value;
            }
        }

        // Remove duplicates
        $attributes = array_values(array_unique($attributes));

        return implode(', ', $attributes);
    }

    /**
     * Convert English digits to Bangla digits.
     */
    private function to_bangla_number($number) {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, (string) $number);
    }

    /**
     * Delivery / shipping charge
     */
    private function get_delivery_charge() {
        $shipping_total = (float) $this->order->get_shipping_total();
        if ($shipping_total <= 0) return '';
        return wc_price($shipping_total);
    }

    /**
     * Delivery / shipping method title
     */
    private function get_delivery_method() {
        $methods = $this->order->get_shipping_methods();
        if (empty($methods)) return '';
        $first = array_shift($methods);
        return $first ? $first->get_name() : '';
    }

    /**
     * Fee list (each fee with name + amount)
     */
    private function get_fees_list() {
        $fees = $this->order->get_fees();
        if (empty($fees)) return '';

        $out = [];
        foreach ($fees as $fee) {
            $name = $fee->get_name();
            $total = wc_price((float) $fee->get_total());
            $out[] = $name . ': ' . $total;
        }
        return implode(', ', $out);
    }

    /**
     * Total fees amount (sum)
     */
    private function get_fees_total() {
        $fees = $this->order->get_fees();
        if (empty($fees)) return '';

        $sum = 0;
        foreach ($fees as $fee) {
            $sum += (float) $fee->get_total();
        }

        if ($sum <= 0) return '';
        return wc_price($sum);
    }
}
