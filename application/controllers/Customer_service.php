<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_service extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('message_model');
        $this->load->helper('html');
        // Manually include Composer autoload if not using CI's built-in Composer autoload
        require_once APPPATH . 'vendor/autoload.php';
    }

    public function index() {
        $this->load->view('customer_service/index');
    }

    public function poll_for_messages() {
        set_time_limit(0); // Allow script to run indefinitely for long polling
        header('Content-Type: application/json');

        $polling_timeout = 25; // seconds

        // Blocking pop from Redis queue for new incoming messages
        $new_message = $this->message_model->get_new_incoming_message_from_redis($polling_timeout);

        if ($new_message) {
            log_message('info', 'Long Polling: New message found for display.');
            // Escape HTML for safe display
            $new_message['content'] = html_escape($new_message['content']);
            echo json_encode(['status' => 'success', 'message' => $new_message]);
        } else {
            log_message('info', 'Long Polling: No new messages within timeout.');
            echo json_encode(['status' => 'no_new_messages']);
        }
        exit(); // Important to exit after sending JSON
    }

    public function send_reply() {
        header('Content-Type: application/json');

        // Sanitize input
        $user_id = $this->input->post('user_id', TRUE);
        $reply_text = $this->input->post('reply_text', TRUE);

        if (empty($user_id) || empty($reply_text)) {
            log_message('warning', 'Customer Service: Incomplete parameters for sending reply.');
            echo json_encode(['status' => 'error', 'message' => '用戶ID或回覆內容不可為空。']);
            exit();
        }

        // Add the customer's reply to an outgoing queue in Redis
        $this->message_model->add_customer_reply_to_redis($user_id, $reply_text);
        log_message('info', 'Customer Service: Reply added to outgoing queue for user: ' . $user_id);

        // For this demo, we assume a background worker will pick it up.
        // In a real system, you might send back the new CSRF token here.
        echo json_encode(['status' => 'success', 'message' => '回覆已加入佇列，將在後台發送。', 'csrf_token_name' => $this->security->get_csrf_token_name(), 'csrf_hash' => $this->security->get_csrf_hash()]);
        exit();
    }
}
