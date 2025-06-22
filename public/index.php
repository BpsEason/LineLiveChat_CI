<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP.
 *
 * @package     CodeIgniter
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright   Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license     https://opensource.org/licenses/MIT MIT License
 * @link        https://codeigniter.com
 * @since       Version 1.0.0
 * @filesource
 */

// Define APPPATH (Application Path) and BASEPATH (System Path)
define('APPPATH', __DIR__ . '/../application/');
define('BASEPATH', __DIR__ . '/../system/');
define('VIEWPATH', APPPATH . 'views/'); // Correct VIEWPATH definition

/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default CodeIgniter will display all errors.
 *
 * For a live environment, you'll want to enable error logging and perhaps
 * set error_reporting to 0
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 *---------------------------------------------------------------
 * SYSTEM FOLDER NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" folder.
 * Include the path if the folder is not one level above the "application" folder.
 */
$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder than the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server.
 * For more info please see the user guide:
 *
 * https://codeigniter.com/user_guide/general/managing_your_application.html
 *
 *
 * DO NOT CHANGE THIS UNLESS YOU KNOW WHAT YOU ARE DOING
 *
 */
$application_folder = 'application';

/*
 *---------------------------------------------------------------
 * ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 * development
 * testing
 * production
 *
 */
define('ENVIRONMENT', 'development');
// Define CI_VERSION (optional, for compatibility)
define('CI_VERSION', '3.1.13');


/*
 *---------------------------------------------------------------
 * SET THE INCLUDE PATH
 *---------------------------------------------------------------
 *
 * This would go in your front controller, but in an environment
 * where you have no front controller, such as a CLI script, you
 * would typically include this file explicitly.
 *
 */
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(BASEPATH));

/*
 *---------------------------------------------------------------
 * LAUNCH THE BOOTSTRAP FILE
 *---------------------------------------------------------------
 *
 * And away we go...
 */
require_once BASEPATH . 'core/CodeIgniter.php';
