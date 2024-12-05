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

    //add Booking History submenu
    add_submenu_page(
      'zippy-bookings',   
      'Booking History',
      'Booking History',
      'manage_options',
      'booking-history',
      array($this, 'booking_history_render')
  );
  }

  public function render()
  {
    echo Zippy_Utils_Core::get_template('admin-settings.php', [], dirname(__FILE__), '/templates');
  }
  public function booking_history_render()
  {
    $data = [];
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
      $customer_id = sanitize_text_field($_GET['customer_id']);
      $args = array(
          'limit' => -1,
          'customer_id' => $customer_id,
          // 'status' => 'completed',
      );
      $orders = wc_get_orders($args);
      
      $data["customer_id"] = $customer_id;
      $data["orders"] = $orders;
    } else {
      $args = array(
          'limit' => -1,
          'orderby' => 'date',
          'order' => 'ASC',
      );
      $orders = wc_get_orders($args);

      $data = [
        "order_infos" => [],
      ];
      foreach ($orders as $order) {
          $customer_id = $order->get_customer_id();

          if (!$customer_id) {
              continue;
          }

          if (!isset($data["order_infos"][$customer_id])) {
              $billing_first_name = get_user_meta($customer_id, 'billing_first_name', true);
              $billing_last_name = get_user_meta($customer_id, 'billing_last_name', true);
              $user_info = get_userdata($customer_id);
              $display_name = sanitize_text_field($user_info->display_name);

              if (!empty($billing_first_name) && !empty($billing_last_name)) {
                  $customer_name = $billing_first_name . ' ' . $billing_last_name;
              } else {
                  $customer_name = $display_name;
              }
              $data["order_infos"][$customer_id] = array(
                  'customer_name' =>  $customer_name,
                  'orders' => array(),
              );
          }
          $data["order_infos"][$customer_id]['orders'][] = $order;
      }
    }
    
    echo Zippy_Utils_Core::get_template('booking-history.php', $data, dirname(__FILE__), '/templates');
  }
}
