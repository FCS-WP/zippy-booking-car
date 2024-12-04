<?php
/*
Plugin Name: Zippy Booking Car
Plugin URI: https://zippy.sg/
Description: Booking System, Manage Oder, Monthly Payment...
Version: 5.0 Author: Zippy SG
Author URI: https://zippy.sg/
License: GNU General Public
License v3.0 License
URI: https://zippy.sg/
Domain Path: /languages

Copyright 2024

*/

namespace Zippy_Booking_Car;


defined('ABSPATH') or die('°_°’');

/* ------------------------------------------
 // Constants
 ------------------------------------------------------------------------ */
/* Set plugin version constant. */

if (!defined('ZIPPY_BOOKING_VERSION')) {
  define('ZIPPY_BOOKING_VERSION', '5.0');
}

/* Set plugin name. */

if (!defined('ZIPPY_BOOKING_NAME')) {
  define('ZIPPY_BOOKING_NAME', 'Zippy Booking Car');
}

if (!defined('ZIPPY_BOOKING_PREFIX')) {
  define('ZIPPY_BOOKING_PREFIX', 'zippy_booking_car');
}

if (!defined('ZIPPY_BOOKING_BASENAME')) {
  define('ZIPPY_BOOKING_BASENAME', plugin_basename(__FILE__));
}

/* Set constant path to the plugin directory. */

if (!defined('ZIPPY_BOOKING_DIR_PATH')) {
  define('ZIPPY_BOOKING_DIR_PATH', plugin_dir_path(__FILE__));
}

/* Set constant url to the plugin directory. */

if (!defined('ZIPPY_BOOKING_URL')) {
  define('ZIPPY_BOOKING_URL', plugin_dir_url(__FILE__));
}


/* ------------------------------------------
// i18n
---------------------------- --------------------------------------------- */

// load_plugin_textdomain('zippy-booking-car', false, basename(dirname(__FILE__)) . '/languages');


// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

/* ------------------------------------------
// Includes
 --------------------------- --------------------------------------------- */
require ZIPPY_BOOKING_DIR_PATH . '/includes/autoload.php';

// require ZIPPY_BOOKING_DIR_PATH . '/vendor/autoload.php';

use  Zippy_Booking_Car\Src\Admin\Zippy_Admin_Settings;
use Zippy_Booking_Car\Utils\Zippy_Updates;

/**
 *
 * Check plugin version
 */

Zippy_Updates::get_instance();

/**
 *
 * Init Zippy Plugin
 */

Zippy_Admin_Settings::get_instance();
