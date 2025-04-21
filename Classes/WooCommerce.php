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
        
        // Additional placeholders
        $this->placeholders['{order_date}'] = $this->order->get_date_created()->date_i18n('F j, Y g:i a');
    }
    
    private function get_products_list() {
        $items = $this->order->get_items();
        if (empty($items)) {
            return '';
        }
        
        $list = "\n";
        foreach ($items as $item) {
            $list .= sprintf(
                " -%s%s   x%d  ৳%s\n",
                $item->get_name(),
                ($item->get_variation_id() ? ' - ' . $this->get_variation_attribute_string($item) : ''),
                $item->get_quantity(),
                number_format($item->get_total(), 2)
            );
        }
        
        return $list;
    }
    
    private function get_variation_attribute_string($item) {
        $variation_data = $item->get_meta_data();
        $attributes = [];
        
        foreach ($variation_data as $meta) {
            if (strpos($meta->key, 'pa_') === false && !empty($meta->value)) {
                $attributes[] = $meta->value;
            }
        }
        
        return implode(', ', $attributes);
    }
}