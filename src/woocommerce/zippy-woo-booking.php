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
  public const ACF_SERVICE_TYPE_HOURL_PRICING = 'hour_pricing';
  public const ACF_SERVICE_TYPE_TRIP_PRICING = 'trip_pricing';

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

    /* Recalculate Price Product by User */
    add_action('woocommerce_order_before_calculate_totals', array($this, 'recalculate_price_product_by_user'), 20, 2);

    /* Save Service Type to Order Meta when add order item in admin */
    add_action('wp_ajax_woocommerce_add_order_item', array($this, 'update_order_meta_service_type'), 0);

    /* Get Order Service Type Ajax */
    add_action('wp_ajax_get_order_service_type', array($this, 'get_order_service_type_ajax'), 1);

    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_add_product_type'));

    /* Custom Order List Columns */
    add_filter('woocommerce_shop_order_list_table_columns', array($this, 'add_booking_date_column'));
    add_action('woocommerce_shop_order_list_table_custom_column', array($this, 'render_booking_date_column'), 10, 2);
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
    $product = $product_id ? wc_get_product($product_id) : null;
    if (!$product) return;

    $roles = get_editable_roles();

    $allowed_roles = [
      'customer'    => 'Customer',
      'customer_v2' => 'Customer V2',
      'customer_v3' => 'Customer V3',
      'customer_v4' => 'Customer V4',
      'customer_v5' => 'Customer V5',
    ];
?>
    <div class="options_group price-per-hour-by-role-box">

      <p class="form-field">
        <label><strong><?php _e('Price Per Hour by Role', 'textdomain'); ?></strong></label>
      </p>

      <div class="price-per-hour-by-role-inner">
        <?php foreach ($roles as $role_key => $role_details):
          if (!array_key_exists($role_key, $allowed_roles)) {
            continue;
          }

          $meta_key = self::PRODUCT_META_KEY_PRICE_PER_HOUR_BY_ROLE . $role_key;
          $price = $product->get_meta($meta_key);
          $role_name = translate_user_role($role_details['name']);
        ?>
          <p class="form-field">
            <label style="width:180px;">
              <?php echo esc_html($role_name); ?>
            </label>

            <input
              type="text"
              name="price_per_hour_by_role[<?php echo esc_attr($role_key); ?>]"
              value="<?php echo esc_attr($price); ?>"
              placeholder="<?php _e('Enter price', 'textdomain'); ?>"
              style="width:160px;" />
            <span style="margin-left:6px;">/ hour</span>
          </p>
        <?php endforeach; ?>
      </div>

    </div>
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

    if (!isset($_POST['price_per_hour_by_role'])) {
      return;
    }

    $prices = $_POST['price_per_hour_by_role'];

    foreach ($prices as $role => $price) {
      $role  = sanitize_text_field($role);
      $price = sanitize_text_field($price);

      $meta_key = self::PRODUCT_META_KEY_PRICE_PER_HOUR_BY_ROLE . $role;

      if ($price !== '') {
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
      if (is_checkout() && get_query_var('order-pay')) {
        foreach ($available_gateways as $gateway_id => $gateway) {
          if ($gateway_id === 'cheque') {
            unset($available_gateways[$gateway_id]);
          }
        }
      } else {
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

  public function add_custom_staff_checkout_fields($checkout)
  {
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

  public function save_custom_staff_checkout_fields($order_id)
  {
    if (!empty($_POST['name_member_staff'])) {
      update_post_meta($order_id, '_name_member_staff', sanitize_text_field($_POST['name_member_staff']));
    }
    if (!empty($_POST['phone_member_staff'])) {
      update_post_meta($order_id, '_phone_member_staff', sanitize_text_field($_POST['phone_member_staff']));
    }
  }

  public function display_custom_fields_in_order_details($order)
  {
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

  public static function get_price_product_by_user($user_id, $product_id, $type_product)
  {
    $product_data = get_field($type_product, 'user_' . $user_id);
    if ($product_data) {
      foreach ($product_data as $data) {
        if ($data['product'] == $product_id) {
          return $data['price'];
        }
      }
    }

    return null;
  }

  function enqueue_scripts_add_product_type($hook)
  {
    if ($hook !== 'woocommerce_page_wc-orders') {
      return;
    }

    wp_enqueue_script(
      'wc-order-add-product-type',
      get_stylesheet_directory_uri() . '/assets/js/wc-order-add-product-type.js',
      ['jquery', 'wc-admin-meta-boxes'],
      '1.0',
      true
    );
  }

  function get_order_service_type_ajax()
  {
    if (empty($_GET['order_id'])) {
      wp_send_json_error();
    }

    $order_id = absint($_GET['order_id']);
    $service  = get_post_meta($order_id, 'service_type', true);

    wp_send_json_success([
      'service_type' => $service ?: ''
    ]);
  }

  function update_order_meta_service_type()
  {

    if (empty($_POST['order_id']) || empty($_POST['order_service_type'])) {
      return;
    }

    $order_id = absint($_POST['order_id']);
    $service  = sanitize_text_field($_POST['order_service_type']);

    if (!$order_id || !$service) return;

    update_post_meta($order_id, 'service_type', $service);
  }

  function recalculate_price_product_by_user($and_taxes, $order)
  {
    if (!is_admin()) {
      return;
    }

    $user_id = $order->get_user_id();
    if (!$user_id) {
      return;
    }

    foreach ($order->get_items('line_item') as $item_id => $item) {
      $product_id = $item->get_product_id();
      $qty        = $item->get_quantity();

      $product = wc_get_product($product_id);
      $type_service = get_post_meta($order->get_id(), 'service_type', true);
      $acf_key_type_service = self::get_acf_key_type_service_by_name($type_service);
      $price_product_by_user = self::get_price_product_by_user($user_id, $product_id, $acf_key_type_service);

      $price = !empty($price_product_by_user) ? floatval($price_product_by_user) : $product->get_price();
      if ($price !== null) {
        $item->set_subtotal($price * $qty);
        $item->set_total($price * $qty);
      }
    }
  }

  function get_acf_key_type_service_by_name($type_service)
  {
    if ($type_service == 'Hourly/Disposal') {
      return 'hour_pricing';
    } else if ($type_service == 'Airport Arrival Transfer') {
      return 'trip_pricing';
    } else {
      return '';
    }
  }

  public function add_booking_date_column($columns)
  {
    if (isset($columns['order_date'])) {
      $columns['order_date'] = __('Booking Created', 'woocommerce');
    }

    $new_columns = [];
    foreach ($columns as $key => $label) {
      $new_columns[$key] = $label;
      if ($key === 'order_date') {
        $new_columns['booking_date'] = __('Booking Date', 'woocommerce');
      }
    }

    return $new_columns;
  }

  public function render_booking_date_column($column_name, $order)
  {
    if ($column_name !== 'booking_date') {
      return;
    }

    $order_id    = $order->get_id();
    $pick_up_date = get_post_meta($order_id, 'pick_up_date', true);
    $pick_up_time = get_post_meta($order_id, 'pick_up_time', true);

    if ($pick_up_date || $pick_up_time) {
      echo '<span>' . esc_html($pick_up_date) . '</span>';
      if ($pick_up_time) {
        echo '<br><small>' . esc_html($pick_up_time) . '</small>';
      }
    } else {
      echo '<span>—</span>';
    }
  }
}
