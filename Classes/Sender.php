<?php
namespace OrderNotificationTelegram\Classes;

class Sender {
    private $chatIDs = [];
    private $token;
    private $parseMode;
    private $accessTags;

    public function __construct() {
        $this->chatIDs   = [];
        $this->token     = '';
        $this->parseMode = 'HTML';
        $this->accessTags = '<b><strong><i><u><em><ins><s><strike><del><a><code><pre>';
    }

    /**
     * Accept single or comma-separated chat IDs.
     * Example: "-10012345, 12345678, -10099999"
     */
    public function set_credentials($token, $chat_id) {
        $this->token = trim((string) $token);

        // Split by comma, trim spaces, remove empties
        $ids = array_map('trim', explode(',', (string) $chat_id));
        $ids = array_filter($ids, function($v){ return $v !== ''; });

        $this->chatIDs = array_values($ids);
    }

    /**
     * Send message to ALL chat IDs.
     */
    public function send_message($message) {
        if (empty($this->token) || empty($this->chatIDs)) {
            error_log('ONTG Error - Token or Chat ID missing');
            return false;
        }

        try {
            // Clean and prepare the message (keep allowed HTML)
            $message = strip_tags($message, $this->accessTags);
            $message = stripcslashes(html_entity_decode($message));

            $success_any = false;

            foreach ($this->chatIDs as $chatID) {
                $data = [
                    'chat_id' => $chatID,
                    'text' => $message,
                    'parse_mode' => $this->parseMode,
                    'disable_web_page_preview' => true,
                ];

                $args = [
                    'timeout' => 15,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => [],
                    'body' => $data,
                    'cookies' => [],
                    'sslverify' => false
                ];

                $response = wp_remote_post(
                    'https://api.telegram.org/bot' . $this->token . '/sendMessage',
                    $args
                );

                if (is_wp_error($response)) {
                    error_log('ONTG Error - ' . $response->get_error_message());
                    continue;
                }

                $body = json_decode(wp_remote_retrieve_body($response), true);

                if (!isset($body['ok']) || $body['ok'] !== true) {
                    error_log('ONTG Error - API Error for chat ' . $chatID . ': ' . ($body['description'] ?? 'Unknown error'));
                    continue;
                }

                $success_any = true;
            }

            return $success_any;

        } catch (\Exception $e) {
            error_log('ONTG Error - Failed to send message: ' . $e->getMessage());
            return false;
        }
    }
}
