<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$autoload['packages'] = array();
$autoload['libraries'] = array('database', 'session', 'form_validation', 'redis_library', 'line_api');
$autoload['helper'] = array('url', 'form', 'security');
$autoload['config'] = array('line', 'redis');
$autoload['language'] = array();
$autoload['model'] = array('message_model', 'user_model');
