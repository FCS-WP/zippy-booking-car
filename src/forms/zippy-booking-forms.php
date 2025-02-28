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

    $required_fields = ['emailcustomer', 'phonecustomer', 'pick_up_date', 'pick_up_time', 'pick_up_location', 'drop_off_location', 'no_of_passengers', 'service_type', 'id_product', 'time_use', 'agree_terms'];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
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
    
    $order = wc_create_order();
    $order->add_product(wc_get_product($product_id), $time_use);

    $order->set_address(array(
        'first_name' => $name_customer,
        'email'      => $email_customer,
        'phone'      => $phone_customer,
    ), 'billing');
    
    if (is_user_logged_in()) {
      $user = wp_get_current_user();
      if ($user->ID) { 
          $order->set_customer_id($user->ID);
      }
      $order->update_status('on-hold');
    }else{
      $order->update_status('pending');
    }

    $order->set_payment_method('cod');

    $order->calculate_totals();
    

    $order_id = $order->get_id();

    if (!empty($no_of_passengers)) {
      update_post_meta($order_id, 'no_of_passengers', $no_of_passengers);
    }

    if (!empty($no_of_baggage)) {
      update_post_meta($order_id, 'no_of_baggage', $no_of_baggage);
    }

    if (!empty($service_type)) {
      update_post_meta($order_id, 'service_type', $service_type);
    }
    if (!empty($flight_details)) {
      update_post_meta($order_id, 'flight_details', $flight_details);
    }
    if (!empty($eta_time)) {
      update_post_meta($order_id, 'eta_time', $eta_time);
    }
    if (!empty($key_member)) {
      update_post_meta($order_id, 'key_member', $key_member);
    }
    if (!empty($pick_up_date)) {
      update_post_meta($order_id, 'pick_up_date', $pick_up_date);
    }
    if (!empty($pick_up_time)) {
      update_post_meta($order_id, 'pick_up_time', $pick_up_time);
    }
    if (!empty($pick_up_location)) {
      update_post_meta($order_id, 'pick_up_location', $pick_up_location);
    }
    if (!empty($drop_off_location)) {
      update_post_meta($order_id, 'drop_off_location', $drop_off_location);
    }
    if (!empty($special_requests)) {
      update_post_meta($order_id, 'special_requests', $special_requests);
    }

    $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Imperial <impls@singnet.com.sg>'];
    $subject = 'Thank You for Your Enquiry – Imperial Chauffeur Services Pte. Ltd';
    $message = "<p>Thank you for reaching out to us. We have received your enquiry and will get back to you as soon as possible. Below are the details you submitted:</p>";
    $message .= "<h3>Your Enquiry Details:</h3>";
    $message .= "<p>Service type: " . $service_type . "</p>";
    $message .= "<p>Vehicle Type: $product_name</p>";
    if($service_type == "Hourly/Disposal"){
      $message .= "<p>Usage time: " . $time_use . " Hours</p>";
    }
    $message .= "<p>Pick up Date: $pick_up_date</p>";
    $message .= "<p>Pick up Time: $pick_up_time</p>";
    $message .= "<p>Pick up location: $pick_up_location</p>";
    if($service_type == "Airport Arrival Transfer"){
      $message .= "<p>Flight details: $flight_details</p>";
      $message .= "<p>ETA: $eta_time</p>";
      $message .= "<p>Drop off location: $drop_off_location</p>";
    }elseif($service_type == "Airport Departure Transfer"){
      $message .= "<p>Drop off location: $drop_off_location</p>";
      $message .= "<p>Flight details: $flight_details</p>";
      $message .= "<p>ETD: $eta_time</p>";
    }
    else{
      $message .= "<p>Drop off location: $drop_off_location</p>";
    }
    
    $message .= "<p>No of pax: $no_of_passengers</p>";
    $message .= "<p>No of luggages: $no_of_baggage</p>";
    $message .= "<p>Special requests: $special_requests</p>";


    $message .= "<br>";
    $message .= "<h3>Preferred Contact Method:</h3>";
    $message .= "<p>OFFICE TELEPHONE +65 6734 0428 (24Hours)</p>";
    $message .= "<p>EMAIL: impls@singnet.com.sg</p>";
    $message .= "<br>";
    $message .= "<p>Our team will review your request and respond within  24 hours. If you have any urgent concerns, feel free to contact us.</p>";
    $message .= "<p>We appreciate your patience and look forward to assisting you.</p>";
    $message .= "<br>";
    $message .= "<p>Best regards,</p>";
    $message .= "<p>Imperial Chauffeur Services Pte. Ltd</p>";
    $message .= "<p>Email: impls@singnet.com.sg</p>";
    $message .= "<p>Website: https://imperialchauffeur.sg/</p>";
    

    $send_customer = wp_mail($email_customer, $subject, $message, $headers);

    $subjectAdmin = 'New Enquiry Received';

    $messageAdmin = "<p>A new enquiry has been submitted. Please find the details below:</p>";
    $messageAdmin .= "<h3>Enquiry Details:</h3>";
    $messageAdmin .= "<p>Customer: " . $name_customer . "/" . $email_customer . "/" . $phone_customer . "</p>";
    $messageAdmin .= "<p>Job Type: " . $service_type . "</p>";
    $messageAdmin .= "<p>Vehicle Type: " . $product_name . "</p>";
    if($service_type == "Hourly/Disposal"){
      $messageAdmin .= "<p>Usage time: " . $time_use . " Hours</p>";
    }
    $messageAdmin .= "<p>Pick Up Date: " . $pick_up_date . "</p>";
    $messageAdmin .= "<p>Pick Up Time: " . $pick_up_time . "</p>";

    if($service_type == "Airport Arrival Transfer"){
      $messageAdmin .= "<p>Pick up location: " . $pick_up_location . "</p>";
      $messageAdmin .= "<p>Flight details: " . $flight_details . "</p>";
      $messageAdmin .= "<p>ETA: " . $eta_time . "</p>";
      $messageAdmin .= "<p>Drop off location: " . $drop_off_location . "</p>";
    }elseif($service_type == "Airport Departure Transfer"){
      $messageAdmin .= "<p>Pick up location: " . $pick_up_location . "</p>";
      $messageAdmin .= "<p>Drop off location: " . $drop_off_location . "</p>";
      $messageAdmin .= "<p>Flight details: " . $flight_details . "</p>";
      $messageAdmin .= "<p>ETD: " . $eta_time . "</p>";
    }else{
      $messageAdmin .= "<p>Pick up location: " . $pick_up_location . "</p>";
      $messageAdmin .= "<p>Drop off location: " . $drop_off_location . "</p>";
    }
    
    

    $messageAdmin .= "<p>No of pax: " . $no_of_passengers . "</p>";
    $messageAdmin .= "<p>No of luggages:  " . $no_of_baggage . "</p>";
    $messageAdmin .= "<p>Special requests: " . $special_requests . "</p>";
    $messageAdmin .= "<br>";
    $messageAdmin .= "<p>Please review the enquiry and respond at your earliest convenience.</p>";
    $messageAdmin .= "<br>";
    $messageAdmin .= "<p>Best regards,</p>";
    $messageAdmin .= "<p>Website: <a href='https://imperialchauffeur.sg/' target='_blank'>imperialchauffeur.sg</a></p>";

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
