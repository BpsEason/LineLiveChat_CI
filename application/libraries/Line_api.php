<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ensure Composer's autoloader is loaded
// This is typically handled by `composer_autoload` in CI config or manual include in controller.
// For this demo, it's assumed to be available.
// If you run this file directly (not via CI), you might need: require_once 'vendor/autoload.php';

use Line\Clients\MessagingApi\Api\MessagingApiApi;
use Line\Clients\MessagingApi\Configuration;
use Line\Clients\MessagingApi\Model\ReplyMessageRequest;
use Line\Clients\MessagingApi\Model\PushMessageRequest;
use Line\Clients\MessagingApi\Model\TextMessage;
use GuzzleHttp\Client; // Required by Line SDK

class Line_api {

    protected $CI;
    protected $messaging_api_client;
    protected $channel_secret;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('line'); // Load Line API configuration

        $channel_access_token = $this->CI->config->item('line_channel_access_token');
        $this->channel_secret = $this->CI->config->item('line_channel_secret');

        // Create Line API client configuration
        $config = new Configuration();
        $config->setAccessToken($channel_access_token);

        // Initialize the Messaging API client with Guzzle HTTP client
        $this->messaging_api_client = new MessagingApiApi(
            client: new Client(),
            config: $config
        );
        log_message('info', 'Line_api library initialized.');
    }

    /**
     * Validates the X-Line-Signature header.
     * This is crucial for security to ensure requests come from Line.
     *
     * @param string $body The raw request body
     * @param string $signature The X-Line-Signature header value
     * @param string $channel_secret Your Line Channel Secret
     * @return bool True if signature is valid, false otherwise.
     */
    public function validate_signature($body, $signature, $channel_secret) {
        $hash = hash_hmac('sha256', $body, $channel_secret, true);
        $calculated_signature = base64_encode($hash);
        log_message('debug', 'Calculated Signature: ' . $calculated_signature . ' | Received Signature: ' . $signature);
        return hash_equals($calculated_signature, $signature);
    }

    /**
     * Replies to a specific Line message using its replyToken.
     * Messages must be replied to within a certain timeframe.
     *
     * @param string $replyToken The reply token obtained from a Line webhook event.
     * @param string $messageText The text message to send.
     * @return bool True on success, false on failure.
     */
    public function reply_message($replyToken, $messageText) {
        try {
            $textMessage = new TextMessage(['text' => $messageText]);
            $replyMessageRequest = new ReplyMessageRequest([
                'replyToken' => $replyToken,
                'messages' => [$textMessage]
            ]);

            $this->messaging_api_client->replyMessage($replyMessageRequest);
            log_message('info', 'Replied to Line message with token: ' . $replyToken . ', text: ' . $messageText);
            return true;
        } catch (Throwable $e) {
            log_message('error', 'Failed to reply to Line message (' . $replyToken . '): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pushes a message to a specific Line user ID.
     * This can be used for proactive messages or replies not tied to a replyToken.
     *
     * @param string $toUserId The Line user ID to send the message to.
     * @param string $messageText The text message to send.
     * @return bool True on success, false on failure.
     */
    public function push_message($toUserId, $messageText) {
        try {
            $textMessage = new TextMessage(['text' => $messageText]);
            $pushMessageRequest = new PushMessageRequest([
                'to' => $toUserId,
                'messages' => [$textMessage]
            ]);

            $this->messaging_api_client->pushMessage($pushMessageRequest);
            log_message('info', 'Pushed message to Line user ' . $toUserId . ': ' . $messageText);
            return true;
        } catch (Throwable $e) {
            log_message('error', 'Failed to push message to Line user ' . $toUserId . ': ' . $e->getMessage());
            return false;
        }
    }
}
