<?php

/**
 * Plugin Updates
 *
 * @package Shin
 */

namespace Zippy_Booking_Car\Utils;

require ZIPPY_BOOKING_DIR_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';


use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined('ABSPATH') or die();

class Zippy_Updates
{

  protected static $_instance = null;

  /**
   * @return Zippy_Admin_Settings
   */

  public static function get_instance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __construct()
  {
    if (!is_admin()) return;
    $this->checkUpdatesPlugin();
    add_action('in_plugin_update_message-' . ZIPPY_BOOKING_NAME . '/' . ZIPPY_BOOKING_NAME . '.php', array($this, 'plugin_name_show_upgrade_notification'), 10, 2);
  }
  public function checkUpdatesPlugin()
  {

    $zippyUpdateChecker = PucFactory::buildUpdateChecker(
      'https://github.com/FCS-WP/zippy_booking_car/',
      __FILE__,
      'zippy-booking-car'
    );

    $zippyUpdateChecker->setBranch('master');

    // $zippyUpdateChecker->setAuthentication('your-token-here');
  }

  public function plugin_name_show_upgrade_notification($current_plugin_metadata, $new_plugin_metadata)
  {

    if (isset($new_plugin_metadata->upgrade_notice) && strlen(trim($new_plugin_metadata->upgrade_notice)) > 0) {

      echo sprintf('<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'exopite-multifilter'), esc_html(rtrim($new_plugin_metadata->upgrade_notice)));
    }
  }
}
