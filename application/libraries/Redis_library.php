<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ensure Composer's autoloader for Predis is loaded
// This is typically handled by `composer_autoload` in CI config or manual include in controller.
// For this demo, it's assumed to be available.
// If you run this file directly (not via CI), you might need: require_once 'vendor/autoload.php';
require_once APPPATH . 'vendor/autoload.php';

use Predis\Client;
use Predis\ClientException;

class Redis_library {

    protected $CI;
    protected $redis_client;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('redis'); // Load Redis configuration

        $host = $this->CI->config->item('redis_host');
        $port = $this->CI->config->item('redis_port');
        $password = $this->CI->config->item('redis_password');
        $database = $this->CI->config->item('redis_database');
        $timeout = $this->CI->config->item('redis_timeout');

        try {
            // Predis connection parameters
            $parameters = [
                'scheme' => 'tcp', // Use 'unix' for socket connection
                'host'   => $host,
                'port'   => $port,
                'timeout'=> $timeout,
            ];

            if ($password) {
                $parameters['password'] = $password;
            }
            // Database is a separate command (SELECT) which Predis handles if 'database' parameter is set.
            // Note: Redis database selection is often deprecated in favor of separate Redis instances.
            if (!is_null($database)) {
                 $parameters['database'] = $database;
            }

            $this->redis_client = new Client($parameters);
            $this->redis_client->connect(); // Explicitly connect to check connection at construct

            log_message('info', 'Redis_library: Successfully connected to Redis.');

        } catch (ClientException $e) {
            // Log the error and show a user-friendly message
            log_message('error', 'Redis connection failed: ' . $e->getMessage());
            // In a production environment, you might want a more graceful failure,
            // but for a demo, showing an error is acceptable.
            show_error('無法連接到 Redis 伺服器，請檢查配置。錯誤: ' . $e->getMessage());
        }
    }

    /**
     * Get the Predis client instance.
     * This allows models or other libraries to interact directly with Redis.
     *
     * @return \Predis\Client
     */
    public function get_instance() {
        return $this->redis_client;
    }
}
