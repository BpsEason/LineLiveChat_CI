<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message_model extends CI_Model {

    private $redis;
    // Redis Queue Names and Channel
    const LINE_IN_QUEUE = 'line_incoming_messages';    // Queue for messages coming FROM Line (to be displayed to CS)
    const CUSTOMER_OUT_QUEUE = 'customer_outgoing_messages'; // Queue for messages FROM CS (to be sent to Line)
    const NEW_MESSAGE_CHANNEL = 'new_message_alert'; // Redis Pub/Sub channel for new message alerts

    public function __construct() {
        parent::__construct();
        // Load the Redis_library to get a Predis client instance
        $this->redis = $this->redis_library->get_instance();
    }

    /**
     * Adds an incoming Line message to the Redis queue.
     * Also publishes an alert on a Redis channel for long polling.
     *
     * @param string $user_id Line user ID
     * @param string $message_type Type of message (text, image, sticker, etc.)
     * @param string $message_content Content of the message
     */
    public function add_line_message_to_redis($user_id, $message_type, $message_content) {
        $message_data = [
            'direction' => 'in', // Direction: incoming from Line
            'user_id' => $user_id,
            'type' => $message_type,
            'content' => $message_content,
            'timestamp' => time()
        ];
        // RPUSH: Add to the right (end) of the list
        $this->redis->rpush(self::LINE_IN_QUEUE, json_encode($message_data));
        // PUBLISH: Send an alert to listeners (e.g., long polling script)
        $this->redis->publish(self::NEW_MESSAGE_CHANNEL, 'new_line_message');
        log_message('info', 'Line message added to Redis queue for user: ' . $user_id);
    }

    /**
     * Adds a customer service reply to the Redis outgoing queue.
     * This will be picked up by a background worker for sending to Line.
     *
     * @param string $user_id Line user ID to reply to
     * @param string $reply_text The text content of the reply
     */
    public function add_customer_reply_to_redis($user_id, $reply_text) {
        $reply_data = [
            'direction' => 'out', // Direction: outgoing from CS
            'user_id' => $user_id,
            'message' => $reply_text,
            'timestamp' => time()
        ];
        // RPUSH: Add to the right (end) of the list
        $this->redis->rpush(self::CUSTOMER_OUT_QUEUE, json_encode($reply_data));
        log_message('info', 'Customer reply added to Redis queue for user: ' . $user_id);
    }

    /**
     * Attempts to get a new incoming message from the Redis queue using blocking pop.
     * Used by the customer service interface for long polling.
     *
     * @param int $timeout Maximum time to wait for a message (in seconds). 0 for indefinite.
     * @return array|null Decoded message data or null if timeout occurs.
     */
    public function get_new_incoming_message_from_redis($timeout = 25) {
        // BLPOP: Blocking Left POP. Waits for a message in the queue.
        $result = $this->redis->blpop([self::LINE_IN_QUEUE], $timeout);
        if ($result && isset($result[1])) {
            log_message('info', 'New incoming message retrieved from Redis.');
            return json_decode($result[1], true);
        }
        return null;
    }

    /**
     * Attempts to get an outgoing message from the Redis queue using blocking pop.
     * Used by the background worker to send replies to Line.
     *
     * @param int $timeout Maximum time to wait for a message (in seconds). 0 for indefinite.
     * @return array|null Decoded message data or null if timeout occurs.
     */
    public function get_customer_outgoing_message_from_redis($timeout = 0) {
        // BLPOP: Blocking Left POP. Waits for a message in the queue.
        $result = $this->redis->blpop([self::CUSTOMER_OUT_QUEUE], $timeout);
        if ($result && isset($result[1])) {
            log_message('info', 'Outgoing message retrieved from Redis for worker.');
            return json_decode($result[1], true);
        }
        return null;
    }
}
