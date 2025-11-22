
## Order Notification for Telegram

Send WooCommerce order notifications to Telegram.

[![GitHub Downloads (all releases)](https://img.shields.io/github/downloads/almahmudbd/order-notification-for-telegram/total)](https://github.com/almahmudbd/order-notification-for-telegram/releases/latest)



## Description

**Order Notification for Telegram** lets you receive WooCommerce order updates instantly in your Telegram chat or group.
You can customize the message using placeholders, include product variations, delivery charge, gateway fees, and choose whether notifications are sent only for new orders or also on order status changes.



## Features

* Instant Telegram notifications for WooCommerce orders
* Send notifications on:

  * **New Order only**, or
  * **Order status change**
* Fully customizable message template
* Product list with variation support (no duplicate variation text)
* Smart quantity display:

  * Quantity hidden for **1 item**
  * Shows **2pcs / ২পিস** for multiple quantities
  * Optional toggle for Bangla quantity digits
* Supports delivery/shipping charge and method placeholders
* Supports gateway/extra fees placeholders
* HTML formatting support (Telegram compatible)
* Easy setup with test message button
* HPOS (High-Performance Order Storage) compatible


## Quick Setup Guide

1. Download and install the plugin, then activate it.
2. Create a Telegram bot:

   * Message [@BotFather](https://t.me/BotFather)
   * Send:

     * `/start`
     * `/newbot`
   * Follow instructions and copy the **Bot Token**.
3. Get your **Chat ID**:

   * Message [@userinfobot](https://t.me/userinfobot) and send `/start`.
4. For group chat notifications:

   * Add [@chatIDrobot](https://t.me/chatIDrobot) to your group and get the ID.
   * Then add your newly created bot to the same group.
5. Go to **WooCommerce → Settings → Telegram Notifications** and paste:

   * Bot Token
   * Chat ID
6. Customize message template using placeholders. then Click **Save settings**
7. then **Send Test Message** or create a new order to send a verify your setup.



## Installation

1. Upload the plugin files to:
   `/wp-content/plugins/order-notification-for-telegram`
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Go to:
   **WooCommerce → Settings → Telegram Notifications**
   or click **Settings** under the plugin name from the Plugins page.



## Support

For support, please [create an issue](https://github.com/almahmudbd/order-notification-for-telegram/issues) in the GitHub repository.


## Credits

* Based on choplugins' plugin:
  [https://choplugins.com/en/product/order-notification-for-telegram](https://choplugins.com/en/product/order-notification-for-telegram)
