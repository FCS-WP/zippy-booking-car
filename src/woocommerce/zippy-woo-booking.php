<?php

/**
 * Woocommece Booking Settings
 *
 *
 */

namespace Zippy_Booking_Car\Src\Woocommerce;

defined('ABSPATH') or die();

use Zippy_Booking_Car\Utils\Zippy_Utils_Core;

class Zippy_Woo_Booking
{
  public const PRODUCT_META_KEY_PRICE_PER_HOUR_BY_ROLE  = '_price_per_hour_by_role_';

  protected static $_instance = null;

  /**
   * @return Zippy_Woo_Booking
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
    if (!function_exists('is_plugin_active')) {

      include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    if (!is_plugin_active('woocommerce/woocommerce.php')) return;

    $this->set_hooks();

    add_action('woocommerce_product_options_pricing', array($this, 'add_custom_price_field_to_product'));

    /* Handle Save Custom Woo Order Fields */
    add_action('woocommerce_process_product_meta',  array($this, 'save_custom_price_field'));

    /* Handle Remove Order Fields */
    add_filter('woocommerce_checkout_fields',  array($this, 'remove_billing_details'));

    /* Custom Order Fields */
    add_filter('woocommerce_checkout_fields',  array($this, 'add_multiple_custom_checkout_fields'));

    /* Custom Staff Order Fields */
    add_action('woocommerce_before_order_notes',  array($this, 'add_custom_staff_checkout_fields'));

    /* Handle Save Custom Order Fields */
    add_action('woocommerce_checkout_update_order_meta', array($this, 'save_multiple_custom_checkout_fields'));

    /* Handle Save Custom Staff Order Fields */
    add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_staff_checkout_fields'));

    /* Handle Display Custom Order Fields */
    add_action('woocommerce_admin_order_data_after_billing_address',  array($this, 'display_multiple_custom_checkout_fields_in_admin'));

    /* Handle Display Custom Staff Order Fields */
    // add_action('woocommerce_admin_order_data_after_billing_address',  array($this, 'display_custom_fields_in_order_details'));

    /* Handle Payment By User Type*/
    add_filter('woocommerce_available_payment_gateways', array($this, 'restrict_payment_methods_for_logged_in_users'));

    /* Booking Infomation  */
    add_shortcode('zippy_information_order', array($this, 'render_information_order'));

    /* Update Checkout After Applied Coupon */
    add_action('woocommerce_applied_coupon', array($this, 'after_apply_coupon_action'));
  }

  function after_apply_coupon_action($coupon_code)
  {
    echo '<script>jQuery( "body" ).trigger( "update_checkout" ); </script>';
  }

  protected function set_hooks()
  {
    add_filter('wc_get_template_part', array($this, 'override_woocommerce_template_part'), 1, 3);
    add_filter('woocommerce_locate_template', array($this, 'override_woocommerce_template'), 1, 3);
  }

  /**
   * Template Part's
   *
   * @param  string $template Default template file path.
   * @param  string $slug     Template file slug.
   * @param  string $name     Template file name.
   * @return string           Return the template part from plugin.
   */
  public function override_woocommerce_template_part($template, $slug, $name)
  {

    $template_directory = untrailingslashit(plugin_dir_path(__FILE__)) . "/templates/";
    if ($name) {
      $path = $template_directory . "{$slug}-{$name}.php";
    } else {
      $path = $template_directory . "{$slug}.php";
    }
    return file_exists($path) ? $path : $template;
  }
  /**
   * Template File
   *
   * @param  string $template      Default template file  path.
   * @param  string $template_name Template file name.
   * @param  string $template_path Template file directory file path.
   * @return string                Return the template file from plugin.
   */
  public function override_woocommerce_template($template, $template_name, $template_path)
  {

    $template_directory = untrailingslashit(plugin_dir_path(__FILE__)) . "/templates/";

    $path = $template_directory . $template_name;
    // echo 'template: ' . $path . '<br/>';

    return file_exists($path) ? $path : $template;
  }

  public function render_information_order()
  {
    echo Zippy_Utils_Core::get_template('order-information.php', [], dirname(__FILE__), '/templates');
  }

  public function add_custom_price_field_to_product()
  {
    $this->add_price_per_hour_field();
    $this->add_price_per_hour_by_role_field();
  }

  public function add_price_per_hour_field()
  {
    woocommerce_wp_text_input(array(
      'id' => '_price_per_hour',
      'label' => __('Price Per Hour', 'woocommerce'),
      'description' => __('Enter a price by hour for this product.', 'woocommerce'),
      'desc_tip' => 'true',
      'type' => 'number',
      'custom_attributes' => array(
        'step' => '0.1',
        'min' => '0'
      )
    ));
  }

  public function add_price_per_hour_by_role_field()
  {
    $product_id = get_the_ID();
    if ($product_id) {
      $product = wc_get_product($product_id);
    }
    $roles = get_editable_roles();
    $role_prices = [];

    if ($product) {
      foreach ($roles as $role_key => $role_details) {
        $meta_key = self::PRODUCT_META_KEY_PRICE_PER_HOUR_BY_ROLE . $role_key;
        $price = $product->get_meta($meta_key);
        if (!empty($price)) {
          $role_prices[$role_key] = $price;
        }
      }
    }

    $saved_role = !empty(array_key_first($role_prices)) ? array_key_first($role_prices) : '';
    $saved_price = $saved_role ? $role_prices[$saved_role] : '';
?>
    <div class="options_group">
      <p class="form-field custom_price_group">
        <label for="custom_text_input"><?php _e('Price Per Hour by Role', 'textdomain'); ?></label>

        <input
          type="text"
          id="custom_text_input"
          name="custom_text_input"
          style="width:45%; margin-right:10px;"
          placeholder="<?php _e('Enter price', 'textdomain'); ?>"
          value="<?php echo esc_attr($saved_price); ?>">

        <select id="custom_select_input" name="custom_select_input" style="width:30%;">
          <option value=""><?php _e('Select role', 'textdomain'); ?></option>
          <?php
          foreach ($roles as $role_key => $role_details) {
            $role_name = translate_user_role($role_details['name']);
            $selected = selected($saved_role, $role_key, false);
            echo '<option value="' . esc_attr($role_key) . '" ' . $selected . '>' . esc_html($role_name) . '</option>';
          }
          ?>
        </select>
      </p>
    </div>

    <script>
      jQuery(document).ready(function($) {
        var rolePrices = <?php echo json_encode($role_prices); ?>;
        $('#custom_select_input').on('change', function() {
          var role = $(this).val();
          var price = rolePrices[role] || '';
          $('#custom_text_input').val(price);
        });
      });
    </script>
<?php
  }


  public function save_custom_price_field($post_id)
  {
    $extra_price = isset($_POST['_price_per_hour']) ? sanitize_text_field($_POST['_price_per_hour']) : '';
    if (!empty($extra_price)) {
      update_post_meta($post_id, '_price_per_hour', $extra_price);
    } else {
      delete_post_meta($post_id, '_price_per_hour');
    }

    if (isset($_POST['custom_select_input'])) {
      $price = sanitize_text_field($_POST['custom_text_input']);
      $role  = sanitize_text_field($_POST['custom_select_input']);

      if (empty($role)) {
        return;
      }

      $meta_key = self::PRODUCT_META_KEY_PRICE_PER_HOUR_BY_ROLE . $role;
      if (!empty($price) && !empty($role)) {
        update_post_meta($post_id, $meta_key, $price);
      } else {
        delete_post_meta($post_id, $meta_key);
      }
    }
  }

  public function remove_billing_details($fields)
  {
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);


    return $fields;
  }

  public function add_multiple_custom_checkout_fields($fields)
  {
    $cart = WC()->cart;

    foreach ($cart->get_cart() as $cart_item) {

      $fields['billing']['no_of_passengers'] = array(
        'type'        => 'hidden',
        'placeholder' => __('Enter no. of Passengers', 'woocommerce'),
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['no_of_passengers']
      );

      $fields['billing']['no_of_baggage'] = array(
        'type'        => 'hidden',
        'placeholder' => __('Enter no. of Baggage', 'woocommerce'),
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['no_of_baggage']
      );

      $fields['billing']['service_type'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['service_type']
      );

      $fields['billing']['eta_time'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_trip']['eta_time']
      );

      $fields['billing']['flight_details'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_trip']['flight_details']
      );

      $fields['billing']['key_member'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['key_member']
      );

      $fields['billing']['pick_up_date'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['pick_up_date']
      );

      $fields['billing']['pick_up_time'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['pick_up_time']
      );

      $fields['billing']['pick_up_location'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['pick_up_location']
      );

      $fields['billing']['drop_off_location'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['drop_off_location']
      );

      $fields['billing']['special_requests'] = array(
        'type'        => 'hidden',
        'class'       => array('form-row-wide hidden-field'),
        'clear'       => true,
        'default'     => $cart_item['booking_information']['special_requests']
      );
    }
    return $fields;
  }

  public function save_multiple_custom_checkout_fields($order_id)
  {

    if (!empty($_POST['no_of_passengers'])) {
      update_post_meta($order_id, 'no_of_passengers', sanitize_text_field($_POST['no_of_passengers']));
    }

    if (!empty($_POST['no_of_baggage'])) {
      update_post_meta($order_id, 'no_of_baggage', sanitize_text_field($_POST['no_of_baggage']));
    }

    if (!empty($_POST['service_type'])) {
      update_post_meta($order_id, 'service_type', sanitize_text_field($_POST['service_type']));
    }
    if (!empty($_POST['flight_details'])) {
      update_post_meta($order_id, 'flight_details', sanitize_text_field($_POST['flight_details']));
    }
    if (!empty($_POST['eta_time'])) {
      update_post_meta($order_id, 'eta_time', sanitize_text_field($_POST['eta_time']));
    }
    if (!empty($_POST['key_member'])) {
      update_post_meta($order_id, 'key_member', sanitize_text_field($_POST['key_member']));
    }
    if (!empty($_POST['pick_up_date'])) {
      update_post_meta($order_id, 'pick_up_date', sanitize_text_field($_POST['pick_up_date']));
    }
    if (!empty($_POST['pick_up_time'])) {
      update_post_meta($order_id, 'pick_up_time', sanitize_text_field($_POST['pick_up_time']));
    }
    if (!empty($_POST['pick_up_location'])) {
      update_post_meta($order_id, 'pick_up_location', sanitize_text_field($_POST['pick_up_location']));
    }
    if (!empty($_POST['drop_off_location'])) {
      update_post_meta($order_id, 'drop_off_location', sanitize_text_field($_POST['drop_off_location']));
    }
    if (!empty($_POST['special_requests'])) {
      update_post_meta($order_id, 'special_requests', sanitize_text_field($_POST['special_requests']));
    }
  }

  public function display_multiple_custom_checkout_fields_in_admin($order)
  {


    $service_type = get_post_meta($order->get_id(), 'service_type', true);
    if ($service_type) {
      echo '<p><strong>' . __('Service Type: ', 'woocommerce') . ':</strong> ' . esc_html($service_type) . '</p>';
    }

    $flight_details = get_post_meta($order->get_id(), 'flight_details', true);
    if ($flight_details) {
      echo '<p><strong>' . __('Flight Details: ', 'woocommerce') . ':</strong> ' . esc_html($flight_details) . '</p>';
    }

    $eta_time = get_post_meta($order->get_id(), 'eta_time', true);
    if ($eta_time) {
      echo '<p><strong>' . __('ETD/ETA Time: ', 'woocommerce') . ':</strong> ' . esc_html($eta_time) . '</p>';
    }


    $no_of_passengers = get_post_meta($order->get_id(), 'no_of_passengers', true);
    if ($no_of_passengers) {
      echo '<p><strong>' . __('No Of Passengers: ', 'woocommerce') . ':</strong> ' . esc_html($no_of_passengers) . '</p>';
    }

    $no_of_baggage = get_post_meta($order->get_id(), 'no_of_baggage', true);
    if ($no_of_baggage) {
      echo '<p><strong>' . __('No Of Baggage: ', 'woocommerce') . ':</strong> ' . esc_html($no_of_baggage) . '</p>';
    }

    $key_member = get_post_meta($order->get_id(), 'key_member', true);
    if ($key_member) {
      echo '<p><strong>' . __('Key Member: ', 'woocommerce') . ':</strong> ' . esc_html($key_member) . '</p>';
    }

    $pick_up_date = get_post_meta($order->get_id(), 'pick_up_date', true);
    if ($pick_up_date) {
      echo '<p><strong>' . __('Pick Up Date: ', 'woocommerce') . ':</strong> ' . esc_html($pick_up_date) . '</p>';
    }

    $pick_up_time = get_post_meta($order->get_id(), 'pick_up_time', true);
    if ($pick_up_time) {
      echo '<p><strong>' . __('Pick Up Time: ', 'woocommerce') . ':</strong> ' . esc_html($pick_up_time) . '</p>';
    }

    $pick_up_location = get_post_meta($order->get_id(), 'pick_up_location', true);
    if ($pick_up_location) {
      echo '<p><strong>' . __('Pick Up Location: ', 'woocommerce') . ':</strong> ' . esc_html($pick_up_location) . '</p>';
    }

    $drop_off_location = get_post_meta($order->get_id(), 'drop_off_location', true);
    if ($drop_off_location) {
      echo '<p><strong>' . __('Drop Off Location: ', 'woocommerce') . ':</strong> ' . esc_html($drop_off_location) . '</p>';
    }

    $special_requests = get_post_meta($order->get_id(), 'special_requests', true);
    if ($special_requests) {
      echo '<p><strong>' . __('Special Reuest: ', 'woocommerce') . ':</strong> ' . esc_html($special_requests) . '</p>';
    }

    $special_requests = get_post_meta($order->get_id(), 'staff_name', true);
    if ($special_requests) {
      echo '<p><strong>' . __('Staff Name: ', 'woocommerce') . ':</strong> ' . esc_html($special_requests) . '</p>';
    }
  }

  function restrict_payment_methods_for_logged_in_users($available_gateways)
  {
    
    if (is_user_logged_in()) {
      if(is_checkout() && get_query_var('order-pay')){
        foreach ($available_gateways as $gateway_id => $gateway) {
          if ($gateway_id === 'cheque') {
            unset($available_gateways[$gateway_id]);
          }
        }
      }else{
        foreach ($available_gateways as $gateway_id => $gateway) {
          if ($gateway_id !== 'cheque') {
            unset($available_gateways[$gateway_id]);
          }
        }
      }
      
    } else {
      foreach ($available_gateways as $gateway_id => $gateway) {
        if ($gateway_id === 'cheque') {
          unset($available_gateways[$gateway_id]);
        }
      }
    }

    return $available_gateways;
  }

  public function add_custom_staff_checkout_fields($checkout) {
    echo '<div id="custom_checkout_fields"><h3>' . __('Member Staff Details') . '</h3>';
    
    // Name field
    woocommerce_form_field('name_member_staff', array(
        'type'        => 'text',
        'class'       => array('form-row-first'),
        'label'       => __('Name'),
        'required'    => false,
    ), $checkout->get_value('name_member_staff'));
    
    // Phone field
    woocommerce_form_field('phone_member_staff', array(
        'type'        => 'text',
        'class'       => array('form-row-last'),
        'label'       => __('Phone'),
        'required'    => false,
    ), $checkout->get_value('phone_member_staff'));
    
    echo '</div>';
  }
  
  public function save_custom_staff_checkout_fields($order_id) {
    if (!empty($_POST['name_member_staff'])) {
        update_post_meta($order_id, '_name_member_staff', sanitize_text_field($_POST['name_member_staff']));
    }
    if (!empty($_POST['phone_member_staff'])) {
        update_post_meta($order_id, '_phone_member_staff', sanitize_text_field($_POST['phone_member_staff']));
    }
  }
  
  public function display_custom_fields_in_order_details($order) {
    $name_member_staff = get_post_meta($order->get_id(), '_name_member_staff', true);
    $phone_member_staff = get_post_meta($order->get_id(), '_phone_member_staff', true);
  
    echo '<section class="woocommerce-customer-details">';
    echo '<h2>' . __('Member Staff Details') . '</h2>';
    echo '<ul class="woocommerce-order-details">';
    
    if ($name_member_staff) {
        echo '<li><strong>' . __('Name: ') . ':</strong> ' . esc_html($name_member_staff) . '</li>';
    }
    if ($phone_member_staff) {
        echo '<li><strong>' . __('Phone: ') . ':</strong> ' . esc_html($phone_member_staff) . '</li>';
    }
  
    echo '</ul>';
    echo '</section>';
  }

}
