<?php

/**
 * Bookings Admin Settings
 *
 *
 */

namespace Zippy_Booking_Car\Src\Admin;

defined('ABSPATH') or die();

use Zippy_Booking_Car\Utils\Zippy_Utils_Core;
use  WC_Order_Item_Product;
use Zippy_Booking_Car\Src\Admin\Zippy_Woo_Settings;

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
    add_action('admin_enqueue_scripts', array($this, 'admin_booking_assets'));
    add_action('woocommerce_order_status_changed', array($this, 'handle_monthly_payment_orders'));
    add_action('wp_ajax_create_payment_order', array($this, 'create_payment_order'));
    add_action('wp_ajax_nopriv_create_payment_order', array($this, 'create_payment_order'));
    add_filter('woocommerce_order_number', array($this, 'custom_order_number_display'), 10, 2);
    add_filter('woocommerce_get_settings_pages', array($this, 'custom_woocommerce_settings_tab'));
  }

  public function admin_booking_assets()
  {
    $version = time();
    $current_user_id = get_current_user_id();
    // lib
    wp_enqueue_style('admin-jquery-ui-css', ZIPPY_BOOKING_URL . 'assets/libs/jquery-ui/jquery-ui.min.css', [], $version);
    // Pass the user ID to the script
    wp_enqueue_script('admin-booking-js', ZIPPY_BOOKING_URL . '/assets/dist/js/admin.min.js', [], $version, true);
    wp_enqueue_style('booking-css', ZIPPY_BOOKING_URL . '/assets/dist/css/admin.min.css', [], $version);





    wp_localize_script('booking-js-current-id', 'admin_id', array(
      'userID' => $current_user_id,
    ));
  }

  function custom_woocommerce_settings_tab($links)
  {
    $settings[] = new Zippy_Woo_Settings();
    return $settings;
  }

  function zippy_action_links($links)
  {
    $links[] = '<a href="' . menu_page_url('zippy-setting', false) . '">Settings</a>';
    return $links;
  }

  public function zippy_booking_car_page()
  {
    add_menu_page('Zippy Bookings', 'Zippy Bookings', 'manage_options', 'zippy-bookings', array($this, 'render'), 'dashicons-list-view', 6);

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
    echo Zippy_Utils_Core::get_template('booking-table.php', [], dirname(__FILE__), '/templates');
  }
  public function booking_history_render()
  {
    $data = [];
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {

      $customer_id = sanitize_text_field($_GET['customer_id']);

      // Post per page
      $orders_per_page = 7;

      // Current Page
      $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

      // Offset
      $offset = ($current_page - 1) * $orders_per_page;

      // Query array to count total orders

      $args = array(
        'limit' => -1,
        'customer_id' => $customer_id,
        'status' => 'completed',
        'meta_key' => 'is_monthly_payment_order',
        'meta_value' => 1,
        'meta_compare' => '=',
        'return' => "ids"
      );
      $total_orders = count(wc_get_orders($args));


      // New Query Array for Orders List

      $args["limit"] = $orders_per_page;
      $args["offset"] = $offset;
      $args["orderby"] = "date";
      $args["order"] = "DESC";

      unset($args["return"]);

      $orders = wc_get_orders($args);

      $data["customer_id"] = $customer_id;
      $data["orders"] = $orders;
      $data["total_orders"] = $total_orders;
      $data["current_page"] = $current_page;
      $data["orders_per_page"] = $orders_per_page;
    } else {

      $args = array(
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
      );
      $orders = wc_get_orders($args);

      $data = [
        "order_infos" => [],
      ];
      foreach ($orders as $order) {
        if (!$order->get_meta('is_monthly_payment_order')) {
          continue;
        }

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

  public function create_payment_order()
  {
    if (!current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized']);
      return;
    }

    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $month_of_order = isset($_POST['month_of_order']) ? sanitize_text_field($_POST['month_of_order']) : '';

    if (!$customer_id || !$month_of_order) {
      wp_send_json_error(['message' => 'Invalid data provided']);
      return;
    }

    $user_info = get_userdata($customer_id);
    if (!$user_info) {
      wp_send_json_error(['message' => 'Customer not found']);
      return;
    }

    $billing_first_name = get_user_meta($customer_id, 'billing_first_name', true);
    $billing_last_name = get_user_meta($customer_id, 'billing_last_name', true);
    $customer_name = trim($billing_first_name . ' ' . $billing_last_name);

    $args = array(
      'customer_id' => $customer_id,
      'limit' => -1,
    );
    $orders = wc_get_orders($args);

    $total_for_month = 0;
    $selected_orders = [];
    $selected_orders_ids = [];

    foreach ($orders as $order) {
      $order_date = $order->get_date_created();
      $order_month_year = $order_date->format('F Y');

      $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);
      if ($order_month_year === $month_of_order && !$is_monthly_payment_order && $order->get_status() === 'confirmed') {
        $total_for_month += $order->get_total();

        $selected_orders[] = $order;
        $selected_orders_ids[] = $order->get_id();
      }
    }

    if ($total_for_month <= 0) {
      wp_send_json_error(['message' => 'No on-hold orders found for the specified month']);
      return;
    }

    $existing_summary_orders = wc_get_orders([
      'meta_key' => 'is_monthly_payment_order',
      'meta_value' => true,
      'limit' => -1,
    ]);

    $summary_order_count = 0;
    foreach ($existing_summary_orders as $existing_order) {
      $order_month = $existing_order->get_meta('month_of_order', true);
      if ($order_month === $month_of_order) {
        $summary_order_count++;
      }
    }

    // Increment summary_order_count to ensure unique order numbers
    $current_order_number = $summary_order_count + 1;

    // Create the new order
    $order = wc_create_order();
    $order->set_customer_id($customer_id);
    $order->set_billing_first_name($billing_first_name);
    $order->set_billing_last_name($billing_last_name);
    $order->set_billing_email($user_info->user_email);
    $order->set_billing_phone(get_user_meta($customer_id, 'billing_phone', true));
    $order->set_status('pending');

    foreach ($selected_orders as $selected_order) {
      $selected_order->update_status('pending', 'Updated to pending by parent monthly order creation.');

      $product_name = 'Order #' . $selected_order->get_id() . ' (' . $customer_name . ' - ' . $month_of_order . ')';
      $item = new WC_Order_Item_Product();
      $item->set_name($product_name);
      $item->set_quantity(1);
      $item->set_tax_class('');
      $item->set_total($selected_order->get_total(), true);
      $item->set_subtotal($selected_order->get_total(), true);
      $item->set_taxes(['total' => [], 'subtotal' => []]);
      $order->add_item($item);
    }

    $order->add_order_note('Included Orders: ' . implode(', ', array_map(function ($o) {
      return $o->get_id();
    }, $selected_orders)));

    $order->update_meta_data('is_monthly_payment_order', true);
    $order->update_meta_data('month_of_order', $month_of_order);
    $order->update_meta_data('list_of_child_orders', serialize($selected_orders_ids));
    $order->update_meta_data('summary_order_number', $current_order_number);

    $custom_order_number = $order->get_id() . ' ' . $month_of_order . ' #' . $current_order_number;
    $order->update_meta_data('_custom_order_number', $custom_order_number);

    $order->calculate_totals(false);

    $order_id = $order->save();

    if ($order_id) {
      wp_send_json_success([
        'order_id' => $order_id,
        'total' => wc_price($total_for_month),
        'summary_order_number' => $current_order_number
      ]);
    } else {
      wp_send_json_error(['message' => 'Failed to create order']);
    }
  }

  public function custom_order_number_display($order_number, $order)
  {
    $custom_order_number = $order->get_meta('_custom_order_number');
    if ($custom_order_number) {
      return $custom_order_number;
    }

    return $order_number;
  }
  public function handle_monthly_payment_orders($order_id)
  {
    $order = wc_get_order($order_id);

    if (!$order->get_meta('is_monthly_payment_order', true)) {
      return;
    }

    $child_orders_ids = $order->get_meta('list_of_child_orders', true);
    $child_orders_ids = !empty($child_orders_ids) ? unserialize($child_orders_ids) : [];

    if (empty($child_orders_ids)) {
      return;
    }
    
    $parent_status = $order->get_status();

    foreach ($child_orders_ids as $child_order_id) {
      $child_order = wc_get_order($child_order_id);
      if ($child_order) {
        $child_order->update_status($parent_status, 'Status updated to match parent monthly order.');
      }
    }
  }
}
