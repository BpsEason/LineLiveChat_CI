<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Line_webhook extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('line_api');
        $this->load->model('message_model');
        $this->load->config('line');
        // Manually include Composer autoload if not using CI's built-in Composer autoload
        require_once APPPATH . 'vendor/autoload.php';
    }

    public function index() {
        $signature = $this->input->get_request_header('X-Line-Signature');
        // Use file_get_contents('php://input') for raw POST body
        $http_body = file_get_contents('php://input');
        $channel_secret = $this->config->item('line_channel_secret');

        if (!$this->line_api->validate_signature($http_body, $signature, $channel_secret)) {
            log_message('error', 'Line Webhook: Signature validation failed. Signature: ' . $signature);
            http_response_code(400);
            echo 'Signature validation failed';
            exit();
        }

        $decoded_body = json_decode($http_body, true);
        if (!isset($decoded_body['events']) || !is_array($decoded_body['events'])) {
            log_message('warning', 'Line Webhook: Invalid event format received.');
            http_response_code(400);
            echo 'Invalid event format';
            exit();
        }

        $events = $decoded_body['events'];

        foreach ($events as $event) {
            switch ($event['type']) {
                case 'message':
                    $this->handleMessageEvent($event);
                    break;
                case 'follow':
                    $this->handleFollowEvent($event);
                    break;
                case 'unfollow':
                    $this->handleUnfollowEvent($event);
                    break;
                default:
                    log_message('info', 'Line Webhook: Unhandled event type: ' . $event['type']);
                    break;
            }
        }
        echo "OK";
    }

    private function handleMessageEvent($event) {
        $user_id = $event['source']['userId'] ?? 'unknown_user';
        $reply_token = $event['replyToken'] ?? null;

        switch ($event['message']['type']) {
            case 'text':
                $message_text = $event['message']['text'];
                log_message('info', 'Line Webhook: Received text message from ' . $user_id . ': ' . $message_text);
                $this->message_model->add_line_message_to_redis($user_id, 'text', $message_text);
                // Optionally reply directly here if not using worker for all replies
                // $this->line_api->reply_message($reply_token, "已收到您的訊息：".$message_text);
                break;
            case 'sticker':
                $sticker_id = $event['message']['stickerId'];
                log_message('info', 'Line Webhook: Received sticker from ' . $user_id . ': ' . $sticker_id);
                $this->message_model->add_line_message_to_redis($user_id, 'sticker', '貼圖ID: ' . $sticker_id);
                break;
            case 'image':
                $image_id = $event['message']['id'];
                log_message('info', 'Line Webhook: Received image from ' . $user_id . ': ' . $image_id);
                $this->message_model->add_line_message_to_redis($user_id, 'image', '圖片ID: ' . $image_id);
                break;
            default:
                log_message('info', 'Line Webhook: Unhandled message type: ' . $event['message']['type'] . ' from ' . $user_id);
                $this->message_model->add_line_message_to_redis($user_id, 'unknown', '未處理的訊息類型: ' . $event['message']['type']);
                break;
        }
    }

    private function handleFollowEvent($event) {
        $user_id = $event['source']['userId'] ?? 'unknown_user';
        log_message('info', 'Line Webhook: User followed: ' . $user_id);
        $this->message_model->add_line_message_to_redis($user_id, 'system', '新用戶追蹤了帳號。');
        // Send a welcome message to the new follower
        $this->line_api->push_message($user_id, '感謝您的追蹤！客服人員將盡快為您服務。');
    }

    private function handleUnfollowEvent($event) {
        $user_id = $event['source']['userId'] ?? 'unknown_user';
        log_message('info', 'Line Webhook: User unfollowed: ' . $user_id);
        $this->message_model->add_line_message_to_redis($user_id, 'system', '用戶取消追蹤了帳號。');
    }
}
