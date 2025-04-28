<?php
namespace OrderNotificationTelegram\Classes;

class TemplateManager {
    public static function get_default_template() {
        return <<<TEMPLATE
New order at {order_date_created}, ORDER ID: <b>#{order_id}</b>
--
Address:
{billing_first_name} 
{billing_address_1}, {billing_city}.
{billing_phone}

--
Products: {products}
Total: <b>{total}</b> 
- {payment_method}

---
(<a href="admin_url/post.php?post={order_id}&action=edit">Check Order</a>) | 
(Message on <a href="https://wa.me/88{billing_phone}">WhatsApp</a>) | 
Copy: <code>{billing_phone}</code>
TEMPLATE;
    }

    public static function get_template($key = 'default') {
        // Add logic for different templates if needed
        if ($key === 'default') {
            return self::get_default_template();
        }

        // Placeholder for future templates
        return '';
    }
}