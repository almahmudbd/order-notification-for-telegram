<?php
namespace OrderNotificationTelegram\Classes;

class Sender {
    private $chatID;
    private $token;
    private $parseMode;
    private $accessTags;

    public function __construct() {
        $this->chatID = '';
        $this->token = '';
        $this->parseMode = 'HTML';
        $this->accessTags = '<b><strong><i><u><em><ins><s><strike><del><a><code><pre>';
    }

    public function set_credentials($token, $chat_id) {
        $this->token = $token;
        $this->chatID = $chat_id;
    }

    public function send_message($message) {
        if (empty($this->token) || empty($this->chatID)) {
            error_log('ONTG Error - Token or Chat ID missing');
            return false;
        }

        try {
            // Clean and prepare the message
            $message = strip_tags($message, $this->accessTags);
            $message = stripcslashes(html_entity_decode($message));

            $data = [
                'chat_id' => $this->chatID,
                'text' => $message,
                'parse_mode' => $this->parseMode,
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
                return false;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!isset($body['ok']) || $body['ok'] !== true) {
                error_log('ONTG Error - API Error: ' . ($body['description'] ?? 'Unknown error'));
                return false;
            }

            return true;
            
        } catch (\Exception $e) {
            error_log('ONTG Error - Failed to send message: ' . $e->getMessage());
            return false;
        }
    }
}