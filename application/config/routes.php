<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'customer_service';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

# Custom routes for Line webhook
$route['line_webhook'] = 'Line_webhook/index';
$route['customer_service/poll_for_messages'] = 'Customer_service/poll_for_messages';
$route['customer_service/send_reply'] = 'Customer_service/send_reply';
