<?php

/**
 * Bookings Admin Settings
 *
 *
 */

namespace Zippy_Booking_Car\Src\Admin;

defined('ABSPATH') or die();

use Zippy_Booking_Car\Utils\Zippy_Utils_Core;

class Zippy_Admin_Settings
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
    add_action('admin_menu',  array($this, 'zippy_booking_car_page'));
  }

  function zippy_action_links($links)
  {
    $links[] = '<a href="' . menu_page_url('zippy-setting', false) . '">Settings</a>';
    return $links;
  }

  public function zippy_booking_car_page()
  {
    add_menu_page('Zippy Bookings', 'Zippy Bookings', 'manage_options', 'zippy-bookings', array($this, 'render'), 'dashicons-admin-generic',6);
  }

  public function render()
  {
    echo Zippy_Utils_Core::get_template('admin-settings.php', [], dirname(__FILE__), '/templates');
  }
}
