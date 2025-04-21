<?php
namespace OrderNotificationTelegram\Classes;

class Sender {
    public $chatID;
    public $token;
    public $parseMode;
    public $accessTags;
    private $api_url = 'https://api.telegram.org/bot%s/sendMessage';

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

        // Clean and prepare the message
        $message = strip_tags($message, $this->accessTags);
        return $this->postTelegramAPI($message);
    }

    private function postTelegramAPI($text) {
        error_log('ONTG Debug - Sending message to Telegram');
        
        $data = array(
            'chat_id' => $this->chatID,
            'text' => stripcslashes(html_entity_decode($text)),
            'parse_mode' => $this->parseMode,
        );

        $args = array(
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => $data,
            'cookies' => array(),
            'sslverify' => false
        );

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

        error_log('ONTG Debug - Message sent successfully');
        return true;
    }
}