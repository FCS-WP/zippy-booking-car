<?php
/*
Plugin Name: Zippy Booking Car
Plugin URI: https://zippy.sg/
Description: Booking System, Manage Oder, Monthly Payment...
Version: 6.0 Author: Zippy SG
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
  define('ZIPPY_BOOKING_VERSION', '4.0');
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
require ZIPPY_BOOKING_DIR_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

// require ZIPPY_BOOKING_DIR_PATH . '/vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

use  Zippy_Booking_Car\Src\Admin\Zippy_Admin_Settings;

use  Zippy_Booking_Car\Src\Woocommerce\Zippy_Woo_Booking;

use  Zippy_Booking_Car\Src\Forms\Zippy_Booking_Forms;


/**
 * Zippy Plugin update
 *
 */
if (is_admin()) {


  $zippyUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/FCS-WP/zippy-booking-car/',
    __FILE__,
    'zippy-booking-car'
  );

  $zippyUpdateChecker->setBranch('production');

  // $zippyUpdateChecker->setAuthentication('your-token-here');

  add_action('in_plugin_update_message-' . ZIPPY_BOOKING_NAME . '/' . ZIPPY_BOOKING_NAME . '.php', 'plugin_name_show_upgrade_notification', 10, 2);
  function plugin_name_show_upgrade_notification($current_plugin_metadata, $new_plugin_metadata)
  {

    if (isset($new_plugin_metadata->upgrade_notice) && strlen(trim($new_plugin_metadata->upgrade_notice)) > 0) {

      echo sprintf('<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'exopite-multifilter'), esc_html(rtrim($new_plugin_metadata->upgrade_notice)));
    }
  }
}

/**
 *
 * Init Zippy Core
 */

Zippy_Admin_Settings::get_instance();

Zippy_Woo_Booking::get_instance();

Zippy_Booking_Forms::get_instance();
