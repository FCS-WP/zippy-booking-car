<?php

/**
 * Bookings FontEnd Form
 *
 *
 */

namespace Zippy_Booking_Car\Src\Forms;

defined('ABSPATH') or die();

use Zippy_Booking_Car\Utils\Zippy_Utils_Core;

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
    date_default_timezone_set('Asia/Singapore');

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
    if (!is_archive() && !is_single() && !is_checkout()) return;
    $version = time();

    $current_user_id = get_current_user_id();

    // Form Assets
    wp_enqueue_script('booking-js', ZIPPY_BOOKING_URL . '/assets/dist/js/web.min.js', [], $version, true);
    wp_enqueue_style('booking-css', ZIPPY_BOOKING_URL . '/assets/dist/css/web.min.css', [], $version);

    wp_localize_script('booking-js-current-id', 'admin_id', array(
      'userID' => $current_user_id,
    ));
  }

  function enquiry_car_booking() {
    if (empty($_POST)) {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }

    $required_fields = ['emailcustomer', 'phonecustomer', 'pick_up_date', 'pick_up_time', 'pick_up_location', 'drop_off_location', 'no_of_passengers', 'service_type', 'id_product', 'price_product_default', 'time_use'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(array('message' => "Missing information: $field"));
        }
    }

    $email_customer = sanitize_email($_POST['emailcustomer']);
    $phone_customer = sanitize_text_field($_POST['phonecustomer']);
    $pick_up_date = sanitize_text_field($_POST['pick_up_date']);
    $pick_up_time = sanitize_text_field($_POST['pick_up_time']);
    $pick_up_location = sanitize_text_field($_POST['pick_up_location']);
    $drop_off_location = sanitize_text_field($_POST['drop_off_location']);
    $no_of_passengers = sanitize_text_field($_POST['no_of_passengers']);
    $no_of_baggage = sanitize_text_field($_POST['no_of_baggage'] ?? '');
    $additional_stop = sanitize_text_field($_POST['additional_stop'] ?? '');
    $midnight_fee = intval($_POST['midnight_fee'] ?? 0);
    $agree_terms = sanitize_text_field($_POST['agree_terms'] ?? '');
    $service_type = sanitize_text_field($_POST['service_type']);
    $special_requests = sanitize_text_field($_POST['special_requests'] ?? '');
    $flight_details = sanitize_text_field($_POST['flight_details'] ?? '');
    $eta_time = sanitize_text_field($_POST['eta_time'] ?? '');
    $price_product_default = floatval($_POST['price_product_default']);
    $time_use = intval($_POST['time_use']);
    $product_id = intval($_POST['id_product']);

    $admin_email = get_option('admin_email');
    $product = wc_get_product($product_id);
    $product_name = $product ? $product->get_name() : 'Unknown';

    $total_booking = $price_product_default * $time_use;
    if ($midnight_fee == 1) {
        $total_booking += 25;
    }
    $total_booking += 25; 

    $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Imperial <impls@singnet.com.sg>'];
    $subject = 'Enquiry Booking Car';
    $message = "<p>Thank you for your interest in our services.</p>";
    $message .= "<p>Service type: $service_type</p>";
    $message .= "<p>Car: $product_name</p>";
    $message .= "<p>Usage time: " . (($time_use == 1) ? "1 Trip" : "$time_use Hours") . "</p>";
    $message .= "<p>Pick up: $pick_up_location at $pick_up_time Date $pick_up_date</p>";
    $message .= "<p>Drop off location: $drop_off_location</p>";
    $message .= "<p>No of passengers: $no_of_passengers</p>";
    $message .= "<p>Flight details: $flight_details</p>";
    $message .= "<p>ETA: $eta_time</p>";
    $message .= "<p>Special requests: $special_requests</p>";
    $message .= "<p>Total Price: <strong>$$total_booking</strong></p>";
    $message .= "<p>Thank You</p>";

    $send_customer = wp_mail($email_customer, $subject, $message, $headers);

    $subjectAdmin = 'New Enquiry Booking Car';
    $messageAdmin = "<p>There is an order for a new car:</p>";
    $messageAdmin .= "<p>Customer information: $email_customer - $phone_customer</p>" . $message;

    $send_admin = wp_mail($admin_email, $subjectAdmin, $messageAdmin, $headers);

    if ($send_customer && $send_admin) {
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
