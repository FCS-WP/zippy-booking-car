<?php

/**
 * Bookings FontEnd Form
 *
 *
 */

namespace Zippy_Booking_Car\Src\Forms;

defined('ABSPATH') or die();

use Zippy_Booking_Car\Utils\Zippy_Utils_Core;
use WC_Order_Item_Fee;
use WC_Settings_Page;
use Zippy_Booking_Car\Utils\Zippy_Pricing_Rule;

class Zippy_Booking_Forms
{
  protected static $_instance = null;

  /**
   * @return Zippy_Booking_Forms
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
    /* Set timezone SG */
    // date_default_timezone_set('Asia/Singapore');

    /* Booking Assets  */
    add_action('wp_enqueue_scripts', array($this, 'booking_assets'));

    /* Booking Enquiry q  */
    add_action('wp_ajax_enquiry_car_booking', array($this, 'enquiry_car_booking'));
    add_action('wp_ajax_nopriv_enquiry_car_booking', array($this, 'enquiry_car_booking'));

    /* Booking List Items  */
    add_shortcode('booking_car_list', array($this, 'render_booking_car_list'));

    /* Trip Booking Form */
    add_shortcode('trip_booking_form', array($this, 'render_trip_booking_form'));

    /* Hour Booking Form */
    add_shortcode('hour_booking_form', array($this, 'render_hour_booking_form'));

    /* Handle Booking */
    add_action('init', array($this, 'handle_booking_process'));

    /* Handle Cart Booking */
    add_filter('woocommerce_add_cart_item_data', array($this, 'handle_add_booking_cart'));

    /* Handle Add Extra Fee Booking */
    add_action('woocommerce_before_calculate_totals',  array($this, 'handle_add_extra_fee'));
  }

  public function booking_assets()
  {
    if (!is_archive() && !is_single() && !is_checkout() && ! is_account_page()) return;
    $version = time();

    $current_user_id = get_current_user_id();

    // Form Assets
    wp_enqueue_script('booking-js', ZIPPY_BOOKING_URL . '/assets/dist/js/web.min.js', [], $version, true);
    wp_enqueue_style('booking-css', ZIPPY_BOOKING_URL . '/assets/dist/css/web.min.css', [], $version);

    wp_localize_script('booking-js-current-id', 'admin_id', array(
      'userID' => $current_user_id,
    ));
  }

  //function send email to customer when website has new order
  public function send_enquiry_email($name_customer, $staff_name, $email_customer, $service_type, $product_name, $pick_up_date, $pick_up_time, $pick_up_location, $drop_off_location, $flight_details = '', $eta_time = '', $time_use = '', $no_of_passengers, $no_of_baggage, $special_requests)
  {
    $headers = [
      'Content-Type: text/html; charset=UTF-8',
      'From: Imperial Chauffeur Services <impls@singnet.com.sg>'
    ];

    $subject = 'Thank You for Your Enquiry – Imperial Chauffeur Services Pte. Ltd';

    $message = "<p style='font-size:13px;color:#000'>Thank you for reaching out to us. We have received your enquiry and will get back to you as soon as possible. Below are the details you submitted:</p>";
    $message .= "<h3 style='font-size:15px;color:#000'>Your Enquiry Details:</h3>";
    $message .= "<p style='font-size:13px;color:#000'>Passenger name: $name_customer</p>";
    $message .= "<p style='font-size:13px;color:#000'>Service type: $service_type</p>";
    $message .= "<p style='font-size:13px;color:#000'>Vehicle type: $product_name</p>";

    if ($service_type == "Hourly/Disposal") {
      $message .= "<p style='font-size:13px;color:#000'>Usage time: $time_use Hours</p>";
    }

    $message .= "<p style='font-size:13px;color:#000'>Pick up date: $pick_up_date</p>";
    $message .= "<p style='font-size:13px;color:#000'>Pick up time: $pick_up_time</p>";
    $message .= "<p style='font-size:13px;color:#000'>Pick up location: $pick_up_location</p>";

    if ($service_type == "Airport Arrival Transfer") {
      $message .= "<p style='font-size:13px;color:#000'>Flight details: $flight_details</p>";
      $message .= "<p style='font-size:13px;color:#000'>ETA: $eta_time</p>";
      $message .= "<p style='font-size:13px;color:#000'>Drop off location: $drop_off_location</p>";
    } elseif ($service_type == "Airport Departure Transfer") {
      $message .= "<p style='font-size:13px;color:#000'>Drop off location: $drop_off_location</p>";
      $message .= "<p style='font-size:13px;color:#000'>Flight details: $flight_details</p>";
      $message .= "<p style='font-size:13px;color:#000'>ETD: $eta_time</p>";
    } else {
      $message .= "<p style='font-size:13px;color:#000'>Drop off location: $drop_off_location</p>";
    }

    $message .= "<p style='font-size:13px;color:#000'>No of pax: $no_of_passengers</p>";
    $message .= "<p style='font-size:13px;color:#000'>No of luggages: $no_of_baggage</p>";
    $message .= "<p style='font-size:13px;color:#000'>Special requests: $special_requests</p>";
    $message .= "<p style='font-size:13px;color:#000'>Staff name: $staff_name</p>";

    $message .= get_email_signature();

    return wp_mail($email_customer, $subject, $message, $headers);
  }

  //function send email to admin when website has new order
  public function send_enquiry_admin_email($staff_name, $order_id, $admin_email, $key_member, $name_customer, $email_customer, $phone_customer, $service_type, $product_name, $time_use, $pick_up_date, $pick_up_time, $pick_up_location, $drop_off_location, $flight_details, $eta_time, $no_of_passengers, $no_of_baggage, $special_requests)
  {
    $headers = [
      'Content-Type: text/html; charset=UTF-8',
      'From: Imperial Chauffeur Services <impls@singnet.com.sg>'
    ];

    $subjectAdmin = 'New Enquiry Received';
    $messageAdmin = "<p style='font-size:13px;color:#000'>A new enquiry has been submitted. Please find the details below:</p>";
    $messageAdmin .= "<h3 style='font-size:15px;color:#000'>Enquiry Details:</h3>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Order no: #$order_id</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Customer type: " . ($key_member == 0 ? "Visitor" : "Member") . "</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Customer: $name_customer / $email_customer & $phone_customer</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Service type: $service_type</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Vehicle type: $product_name</p>";

    if ($service_type == "Hourly/Disposal") {
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Usage time: $time_use Hours</p>";
    }

    $messageAdmin .= "<p style='font-size:13px;color:#000'>Pick up date: $pick_up_date</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Pick up time: $pick_up_time</p>";

    if ($service_type == "Airport Arrival Transfer") {
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Pick up location: $pick_up_location</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Flight details: $flight_details</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>ETA: $eta_time</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Drop off location: $drop_off_location</p>";
    } elseif ($service_type == "Airport Departure Transfer") {
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Pick up location: $pick_up_location</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Drop off location: $drop_off_location</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Flight details: $flight_details</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>ETD: $eta_time</p>";
    } else {
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Pick up location: $pick_up_location</p>";
      $messageAdmin .= "<p style='font-size:13px;color:#000'>Drop off location: $drop_off_location</p>";
    }

    $messageAdmin .= "<p style='font-size:13px;color:#000'>No of pax: $no_of_passengers</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>No of luggages: $no_of_baggage</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Special requests: $special_requests</p>";
    $messageAdmin .= "<p style='font-size:13px;color:#000'>Staff name: $staff_name</p>";
    $messageAdmin .= "<br><p style='font-size:13px;color:#000'>Please review the enquiry and respond at your earliest convenience.</p>";

    $messageAdmin .= get_email_signature();

    return wp_mail($admin_email, $subjectAdmin, $messageAdmin, $headers);
  }

  //function create order when website has new enquiry
  public function create_enquiry_order($key_member, $product_id, $time_use, $name_customer, $email_customer, $phone_customer, $service_type)
  {


    $order = wc_create_order();

    $product = wc_get_product($product_id);

    $order->set_address([
      'first_name' => $name_customer,
      'email'      => $email_customer,
      'phone'      => $phone_customer,
    ], 'billing');

    $is_role_customer_v2 = false;
    if (is_user_logged_in()) {
      $user = wp_get_current_user();
      if ($user->ID) {
        $order->set_customer_id($user->ID);

        if (in_array('customer_v2', (array) $user->roles)) {
          $is_role_customer_v2 = true;
        }
      }
      $order->update_status('on-hold');
    } else {
      $order->update_status('pending');
    }

    $order->update_meta_data('member_type', $key_member);

    $order->set_payment_method('cod');

    $regular_price = $product ? $product->get_price() : 0;

    //Get discount price
    $discounted_price = get_product_pricing_rules($product, 1);
    $regular_price = !empty($discounted_price) ? $discounted_price : $regular_price;

    //Tmp hardcode price per hour for customer v2
    $price_per_hour_for_v2 = get_config_price_for_customer_v2();

    if ($service_type == "Hourly/Disposal") {
      if ($is_role_customer_v2 && array_key_exists($product_id, $price_per_hour_for_v2)) {
        $price_per_hour = $price_per_hour_for_v2[$product_id];
      } else {
        $price_per_hour = get_post_meta($product_id, '_price_per_hour', true);
        $price_per_hour = (!empty($price_per_hour) && is_numeric($price_per_hour)) ? (float) $price_per_hour : $regular_price;
      }

      $total_price = $price_per_hour * $time_use;
    } else {
      $total_price = $regular_price * $time_use;
    }

    // Add product to order
    if ($product) {
      $item = new \WC_Order_Item_Product();
      $item->set_product($product);
      $item->set_quantity($time_use);
      $item->set_subtotal($total_price);
      $item->set_total($total_price);
      $order->add_item($item);
    }

    // // CC fee 
    // $is_enable = get_option('enable_cc_fee');
    // if (!empty($is_enable) && $is_enable == 'yes') {
    //   $cc_fee_name = get_option('zippy_cc_fee_name');
    //   $cc_fee_value = get_option('zippy_cc_fee_amount');
    //   $cc_tax = floor($order_total * ($cc_fee_value / 100) * 100) / 100;

    //   $fee_CC = new WC_Order_Item_Fee();
    //   $fee_CC->set_name($cc_fee_name);
    //   $fee_CC->set_total($cc_tax);
    //   $fee_CC->set_tax_class('');
    //   $fee_CC->set_tax_status('taxable');

    //   $order->add_item($fee_CC);
    // }

    $order->calculate_taxes();

    $order->calculate_totals();


    $order->save();

    return $order->get_id();
  }

  //handle enquiry button ajax
  function enquiry_car_booking()
  {

    if (empty($_POST)) {
      wp_send_json_error(array('message' => 'Invalid request.'));
    }

    $required_fields = [
      'emailcustomer' => "Customer Email*",
      'phonecustomer' => "Customer Phone*",
      'pick_up_date' => "Pick Up Date",
      'pick_up_time' => "Pick Up Time",
      'pick_up_location' => "Pick Up",
      'drop_off_location' => "Drop Off",
      'no_of_passengers' => "No. of Passengers",
      'service_type' => "Types of Transfers",
      'id_product' => "Product ID",
      'time_use' => "Time",
      'agree_terms' => "Agree Term"
    ];

    foreach ($required_fields as $key => $field) {
      if (empty($_POST[$key])) {
        wp_send_json_error(array('message' => "Missing information: $field"));
      }
    }

    $name_customer = sanitize_text_field($_POST['namecustomer']);
    $email_customer = sanitize_email($_POST['emailcustomer']);
    $phone_customer = sanitize_text_field($_POST['phonecustomer']);
    $pick_up_date = sanitize_text_field($_POST['pick_up_date']);
    $pick_up_time = sanitize_text_field($_POST['pick_up_time']);
    $pick_up_location = sanitize_text_field($_POST['pick_up_location']);
    $drop_off_location = sanitize_text_field($_POST['drop_off_location']);
    $no_of_passengers = sanitize_text_field($_POST['no_of_passengers']);
    $no_of_baggage = sanitize_text_field($_POST['no_of_baggage'] ?? '');
    $service_type = sanitize_text_field($_POST['service_type']);
    $special_requests = sanitize_text_field($_POST['special_requests'] ?? '');
    $flight_details = sanitize_text_field($_POST['flight_details'] ?? '');
    $eta_time = sanitize_text_field($_POST['eta_time'] ?? '');
    $time_use = intval($_POST['time_use']);
    $product_id = intval($_POST['id_product']);
    $key_member = intval($_POST['key_member']);
    $staff_name = sanitize_text_field($_POST['staffname'] ?? '');

    $admin_email = get_option('admin_email');
    $product = wc_get_product($product_id);
    $product_name = $product ? $product->get_name() : 'Unknown';

    $order_id = self::create_enquiry_order($key_member, $product_id, $time_use, $name_customer, $email_customer, $phone_customer, $service_type);

    $customer_infors = [
      'no_of_passengers' => $no_of_passengers,
      'no_of_baggage' => $no_of_baggage,
      'service_type' => $service_type,
      'flight_details' => $flight_details,
      'eta_time' => $eta_time,
      'key_member' => $key_member,
      'pick_up_date' => $pick_up_date,
      'pick_up_time' => $pick_up_time,
      'pick_up_location' => $pick_up_location,
      'drop_off_location' => $drop_off_location,
      'special_requests' => $special_requests,
    ];

    if ($key_member == 1) {
      $customer_infors["staff_name"] = $staff_name;
    }

    foreach ($customer_infors as $customer_infor => $value) {
      update_post_meta($order_id, $customer_infor, $value);
    }

    $status_customer_email = self::send_enquiry_email($name_customer, $staff_name, $email_customer, $service_type, $product_name, $pick_up_date, $pick_up_time, $pick_up_location, $drop_off_location, $flight_details, $eta_time, $time_use, $no_of_passengers, $no_of_baggage, $special_requests);

    $status_admin_email = self::send_enquiry_admin_email($staff_name, $order_id, $admin_email, $key_member, $name_customer, $email_customer, $phone_customer, $service_type, $product_name, $time_use, $pick_up_date, $pick_up_time, $pick_up_location, $drop_off_location, $flight_details, $eta_time, $no_of_passengers, $no_of_baggage, $special_requests);


    if ($status_customer_email && $status_admin_email) {
      wp_send_json_success(array('message' => 'sucess.'));
    } else {
      wp_send_json_error(array('message' => 'fails'));
    }

    wp_die();
  }

  public function handle_booking_process()
  {

    $status_redirect = false;
    if (isset($_POST['submit_car_booking_time']) || isset($_POST['submit_hour_booking_form'])) {
      global $woocommerce;

      $woocommerce->session->set_customer_session_cookie(true);
      $time_use = 1;
      $id_product = sanitize_text_field($_POST['id_product']);
      $time_use = sanitize_text_field($_POST['time_use']);
      $service_type = sanitize_text_field($_POST['service_type']);

      $cart = WC()->cart;

      $cart->empty_cart();
      $cart->add_to_cart($id_product, $time_use);


      $status_redirect = true;
    }

    if ($status_redirect == true) {
      wp_redirect(wc_get_checkout_url());
      exit;
    }
  }

  public function handle_add_booking_cart($cart_item_data)
  {

    if (!isset($_POST['submit_car_booking_time']) && !isset($_POST['submit_hour_booking_form'])) return;


    $key_member = sanitize_text_field($_POST['key_member']);
    $pick_up_date = sanitize_text_field($_POST['pick_up_date']);
    $pick_up_time = sanitize_text_field($_POST['pick_up_time']);
    $pick_up_location = sanitize_text_field($_POST['pick_up_location']);
    $drop_off_location = sanitize_text_field($_POST['drop_off_location']);
    $no_of_passengers = sanitize_text_field($_POST['no_of_passengers']);
    $no_of_baggage = sanitize_text_field($_POST['no_of_baggage']);
    $additional_stop = sanitize_text_field($_POST['additional_stop']);
    $midnight_fee = sanitize_text_field($_POST['midnight_fee']);
    $agree_terms = sanitize_text_field($_POST['agree_terms']);
    $service_type = sanitize_text_field($_POST['service_type']);
    $special_requests = sanitize_text_field($_POST['special_requests']);


    $cart_item_data['booking_information'] = array(
      'key_member' => $key_member,
      'pick_up_date' => $pick_up_date,
      'pick_up_time' => $pick_up_time,
      'pick_up_location' => $pick_up_location,
      'drop_off_location' => $drop_off_location,
      'no_of_passengers' => $no_of_passengers,
      'no_of_baggage' => $no_of_baggage,
      'additional_stop' => $additional_stop,
      'midnight_fee' => $midnight_fee,
      'agree_terms' => $agree_terms,
      'service_type' => $service_type,
      'special_requests' => $special_requests,

    );

    if (isset($_POST['submit_car_booking_time'])) {
      $cart_item_data['booking_trip'] = array(
        'flight_details' => sanitize_text_field($_POST['flight_details']),
        'eta_time' => sanitize_text_field($_POST['eta_time']),
      );
    }

    if (isset($_POST['submit_hour_booking_form'])) {
      $cart_item_data['booking_hour'] = array(
        'time_use' => sanitize_text_field($_POST['time_use']),
      );
    }

    return $cart_item_data;
  }

  public function handle_add_extra_fee($cart)
  {


    $cart = WC()->cart;

    foreach ($cart->get_cart() as $cart_item) {
      if ($cart_item['booking_information']['additional_stop'] == 1) {
        $cart->add_fee('Additional Stop Fee Purchase', 25);
      }
      if ($cart_item['booking_information']['midnight_fee'] == 1) {
        $cart->add_fee('Additional Midnight Fee Purchase', 25);
      }
      if ($cart_item['booking_information']['service_type'] ==  "Hourly/Disposal") {
        foreach ($cart->get_cart() as $cart_item) {

          $product = $cart_item['data'];
          $_price_per_hour = get_post_meta($product->get_id(), '_price_per_hour', true);
          $product->set_price($_price_per_hour);
        }
      }
    }
  }

  public function render_trip_booking_form()
  {
    echo Zippy_Utils_Core::get_template('trip-form.php', [], dirname(__FILE__), '/templates');
  }

  public function render_booking_car_list()
  {
    echo Zippy_Utils_Core::get_template('booking-list-items.php', [], dirname(__FILE__), '/templates');
  }

  public function render_hour_booking_form()
  {
    echo Zippy_Utils_Core::get_template('hour-form.php', [], dirname(__FILE__), '/templates');
  }
}
