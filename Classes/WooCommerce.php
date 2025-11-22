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

        // Billing fields (support both _ and -)
        $billing_fields = [
            'first_name','last_name','company',
            'address_1','address_2','city',
            'state','postcode','country',
            'email','phone'
        ];

        foreach ($billing_fields as $field) {
            $method = 'get_billing_' . $field;
            $value  = $this->order->$method();

            $this->placeholders['{billing_' . $field . '}'] = $value;
            $this->placeholders['{billing-' . $field . '}'] = $value;
        }

        // Products list
        $this->placeholders['{products}'] = $this->get_products_list();

        // Fees inline (gateway/COD/etc)
        $this->placeholders['{fees}'] = $this->get_fees_inline();
        $this->placeholders['{fees_total}'] = wc_price($this->order->get_total_fees());

        // Shipping / Delivery charge separate (tax inclusive)
        $shipping_total = (float) $this->order->get_shipping_total();
        $shipping_tax   = (float) $this->order->get_shipping_tax();
        $shipping_grand = $shipping_total + $shipping_tax;

        $this->placeholders['{delivery_charge}'] = wc_price($shipping_grand);
        $this->placeholders['{delivery_method}'] = $this->order->get_shipping_method();

        // Extra alias
        $this->placeholders['{order_date}'] = $this->order->get_date_created()->date_i18n('F j, Y g:i a');
    }

    private function get_products_list() {
        $items = $this->order->get_items();
        if (empty($items)) {
            return '';
        }

        $list = "\n";
        foreach ($items as $item) {
            $name = $item->get_name();

            // Clean, unique variation string
            $variation = '';
            if ($item->get_variation_id()) {
                $variation = $this->get_variation_attribute_string($item);

                // If variation already in name, don't repeat
                if ($variation && stripos($name, $variation) !== false) {
                    $variation = '';
                }
            }

            $qty = (int) $item->get_quantity();
            $qty_part = '';

            // quantity will show only when more than 1, like: 2pcs/২পিস
            if ($qty > 1) {
                $qty_part = ' - ' . $this->format_qty($qty);
            }

            $list .= sprintf(
                " -%s%s%s  ৳%s\n",
                $name,
                ($variation ? ' - ' . $variation : ''),
                $qty_part,
                number_format($item->get_total(), 2)
            );
        }

        return $list;
    }

    private function get_variation_attribute_string($item) {
        $variation_data = $item->get_meta_data();
        $attributes = [];

        foreach ($variation_data as $meta) {
            // Skip taxonomy keys like pa_* and ignore empty values
            if (strpos($meta->key, 'pa_') === false && !empty($meta->value)) {
                $attributes[] = trim((string) $meta->value);
            }
        }

        // Remove duplicates + empties
        $attributes = array_values(array_filter(array_unique($attributes)));

        return implode(', ', $attributes);
    }

    private function get_fees_inline() {
        $fees = $this->order->get_fees();
        if (empty($fees)) return '';

        $parts = [];
        foreach ($fees as $fee) {
            $parts[] = sprintf(
                "%s: %s",
                $fee->get_name(),
                wc_price($fee->get_total())
            );
        }

        return implode(', ', $parts);
    }

    // Format quantity based on settings
    private function format_qty($qty) {
        $use_bangla = (get_option('ontg_qty_bangla', 'no') === 'yes');

        if ($use_bangla) {
            return $this->to_bangla_number($qty) . 'পিস';  // ২পিস
        }

        return $qty . 'pcs'; // 2pcs
    }

    // English digits -> Bangla digits
    private function to_bangla_number($number) {
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return str_replace($en, $bn, (string) $number);
    }
}
