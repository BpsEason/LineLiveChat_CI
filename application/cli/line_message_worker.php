<?php
// application/cli/line_message_worker.php

// Define FCPATH to point to the public/index.php directory
// This script assumes it's located in application/cli/
define('FCPATH', realpath(__DIR__ . '/../../public') . DIRECTORY_SEPARATOR);

// Load the CodeIgniter application's entry point
// This will bootstrap CI and make its functions/libraries/models available.
require_once FCPATH . 'index.php';

// Get the CI instance
$CI =& get_instance();

// Load necessary models and libraries
$CI->load->model('message_model');
$CI->load->library('line_api');

// Set no time limit for the worker script
// This allows it to run continuously, waiting for messages.
set_time_limit(0);

echo "Line Message Worker Started...\n";
echo "This worker listens for outgoing messages from the customer service panel ";
echo "and sends them to Line users via the Line Messaging API.\n";

while (true) {
    echo "Worker: Waiting for outgoing messages...\n";
    // Blocking pop (BLPOP) from Redis queue for outgoing messages
    // The `0` timeout means it will wait indefinitely until a message appears.
    $reply_data = $CI->message_model->get_customer_outgoing_message_from_redis(0);

    if ($reply_data) {
        echo "Worker: Processing reply for user: " . $reply_data['user_id'] . "\n";
        echo "Worker: Message: " . $reply_data['message'] . "\n";
        
        // Call the Line API to push the message to the user
        $result = $CI->line_api->push_message($reply_data['user_id'], $reply_data['message']);

        if ($result) {
            echo "Worker: Reply sent successfully to " . $reply_data['user_id'] . "\n";
            log_message('info', 'Worker: Successfully pushed message to Line user: ' . $reply_data['user_id']);
        } else {
            echo "Worker: Failed to send reply to " . $reply_data['user_id'] . "\n";
            log_message('error', 'Worker: Failed to push message to Line user: ' . $reply_data['user_id']);
            // Implement error handling here: e.g., re-queue the message, log to a dead-letter queue, or send notification.
        }
    }
    // No need for a sleep() call here because BLPOP is a blocking operation;
    // it only returns when a message is available or the timeout is reached.
}
