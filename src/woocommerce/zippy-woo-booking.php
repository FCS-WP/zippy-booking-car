<?php

/**
 * Woocommece Booking Settings
 *
 *
 */

namespace Zippy_Booking_Car\Src\Woocommerce;

defined('ABSPATH') or die();

use Zippy_Booking_Car\Src\Forms\Zippy_Booking_Forms;
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

    /* Handle Save Custom Order Fields In Admin */
    add_action('woocommerce_process_shop_order_meta', array($this, 'save_custom_order_fields_admin'));

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
    add_action('wp_ajax_get_order_service_type', array($this, 'get_order_service_type_ajax'));
    add_action('wp_ajax_update_order_meta_service_type', array($this, 'update_order_meta_service_type'));
    add_action('wp_ajax_get_all_vehicles', array($this, 'get_all_vehicles_ajax'));

    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_add_product_type'));

    /* Filter Order by Booking Date */
    add_action('woocommerce_order_list_table_restrict_manage_orders', array($this, 'add_booking_date_filter_to_order_list'), 10);

    /* HPOS Support for Columns */
    add_filter('manage_edit-shop_order_columns', array($this, 'add_booking_date_column'), 20);
    add_action('manage_shop_order_posts_custom_column', array($this, 'render_booking_date_column'), 20, 2);
    add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_booking_date_column'), 20);
    add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_booking_date_column'), 20, 2);

    /* Sortable Columns */
    add_filter('manage_edit-shop_order_sortable_columns', array($this, 'make_booking_date_column_sortable'));
    add_filter('manage_woocommerce_page_wc-orders_sortable_columns', array($this, 'make_booking_date_column_sortable'));
    
    add_action('pre_get_posts', array($this, 'handle_legacy_sorting_booking_date'), 999);
    add_filter('woocommerce_order_query_args', array($this, 'filter_orders_by_booking_date_query_args'), 999);
    add_filter('woocommerce_order_list_table_prepare_items_query_args', array($this, 'filter_orders_by_booking_date_query_args'), 999);
  }

  public function handle_legacy_sorting_booking_date($query)
  {
    if (!is_admin() || !$query->is_main_query() || 'shop_order' !== $query->get('post_type')) {
      return;
    }

    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : $query->get('orderby');

    if (in_array($orderby, ['booking_date', 'pick_up_date'])) {
      $meta_query = $query->get('meta_query') ?: [];
      $meta_query['booking_date_clause'] = [
        'key'  => 'pick_up_date',
        'type' => 'DATE',
      ];
      $query->set('meta_query', $meta_query);
      $query->set('orderby', 'booking_date_clause');

      if (isset($_GET['order'])) {
        $query->set('order', strtoupper(sanitize_text_field($_GET['order'])));
      }
    }
  }

  public function make_booking_date_column_sortable($columns)
  {
    $columns['booking_date'] = 'pick_up_date';
    return $columns;
  }

  /**
   * AJAX handler to get all vehicle products
   */
  public function get_all_vehicles_ajax()
  {
    if (!current_user_can('edit_shop_orders')) {
      wp_send_json_error(['message' => 'Permission denied']);
    }

    $products = wc_get_products([
      'status' => ['publish', 'private'],
      'limit'  => -1,
      'orderby' => 'name',
      'order'   => 'ASC',
    ]);

    $data = [];
    foreach ($products as $product) {
      $data[] = [
        'id'   => $product->get_id(),
        'name' => $product->get_name(),
        'is_vehicle' => has_term('vehicle', 'product_cat', $product->get_id()),
      ];
    }

    wp_send_json_success($data);
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
    // $this->add_price_per_hour_by_role_field();
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
    $order = wc_get_order($order_id);
    if (!$order) return;

    if (!empty($_POST['no_of_passengers'])) {
      $order->update_meta_data('no_of_passengers', sanitize_text_field($_POST['no_of_passengers']));
    }

    if (!empty($_POST['no_of_baggage'])) {
      $order->update_meta_data('no_of_baggage', sanitize_text_field($_POST['no_of_baggage']));
    }

    if (!empty($_POST['service_type'])) {
      $order->update_meta_data('service_type', sanitize_text_field($_POST['service_type']));
    }
    if (!empty($_POST['flight_details'])) {
      $order->update_meta_data('flight_details', sanitize_text_field($_POST['flight_details']));
    }
    if (!empty($_POST['eta_time'])) {
      $order->update_meta_data('eta_time', sanitize_text_field($_POST['eta_time']));
    }
    if (!empty($_POST['key_member'])) {
      $order->update_meta_data('key_member', sanitize_text_field($_POST['key_member']));
    }
    if (!empty($_POST['pick_up_date'])) {
      $pick_up_date = sanitize_text_field($_POST['pick_up_date']);
      // Standardize date to YYYY-MM-DD for better filtering
      if (strpos($pick_up_date, '-') !== false && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $pick_up_date)) {
        $date_obj = \DateTime::createFromFormat('d-m-Y', $pick_up_date);
        if ($date_obj) {
          $pick_up_date = $date_obj->format('Y-m-d');
        }
      }
      $order->update_meta_data('pick_up_date', $pick_up_date);
    }
    if (!empty($_POST['pick_up_time'])) {
      $order->update_meta_data('pick_up_time', sanitize_text_field($_POST['pick_up_time']));
    }
    if (!empty($_POST['pick_up_location'])) {
      $order->update_meta_data('pick_up_location', sanitize_text_field($_POST['pick_up_location']));
    }
    if (!empty($_POST['drop_off_location'])) {
      $order->update_meta_data('drop_off_location', sanitize_text_field($_POST['drop_off_location']));
    }
    if (!empty($_POST['special_requests'])) {
      $order->update_meta_data('special_requests', sanitize_text_field($_POST['special_requests']));
    }
    $order->save();
  }

  public function display_multiple_custom_checkout_fields_in_admin($order)
  {
    // Service Type (Read-only)
    $service_type = $order->get_meta('service_type');

    // Editable Fields
    $fields = [
      'booking_details' => [
        'title' => __('Booking Details', 'woocommerce'),
        'items' => [
          'pick_up_date'      => ['label' => __('Pick Up Date', 'woocommerce'), 'type' => 'text', 'class' => 'zippy-admin-datepicker', 'full_width' => true],
          'pick_up_time'      => ['label' => __('Pick Up Time', 'woocommerce'), 'type' => 'time_picker', 'class' => 'zippy-admin-timepicker', 'full_width' => true],
          'pick_up_location'  => ['label' => __('Pick Up Location', 'woocommerce'), 'type' => 'text'],
          'drop_off_location' => ['label' => __('Drop Off Location', 'woocommerce'), 'type' => 'text'],
          'no_of_passengers'  => ['label' => __('No Of Passengers', 'woocommerce'), 'type' => 'number'],
          'no_of_baggage'     => ['label' => __('No Of Baggage', 'woocommerce'), 'type' => 'number'],
        ]
      ],
      'transfer_info' => [
        'title' => __('Transfer Information', 'woocommerce'),
        'items' => [
          'flight_details'    => ['label' => __('Flight Details', 'woocommerce'), 'type' => 'textarea', 'full_width' => true],
          'eta_time'          => ['label' => __('ETD/ETA Time', 'woocommerce'), 'type' => 'time_picker', 'class' => 'zippy-admin-timepicker', 'full_width' => true],
          'special_requests'  => ['label' => __('Special Request', 'woocommerce'), 'type' => 'textarea', 'full_width' => true],
        ]
      ],
      'additional_info' => [
        'title' => __('Internal Information', 'woocommerce'),
        'items' => [
          'key_member'        => ['label' => __('Key Member', 'woocommerce'), 'type' => 'text'],
          'staff_name'        => ['label' => __('Staff Name', 'woocommerce'), 'type' => 'text'],
        ]
      ]
    ];

    echo '<div class="zippy-custom-order-fields" style="clear:both; margin-top:10px; padding-top:10px; border-top:1px solid #eee;">';

    if ($service_type) {
      echo '<p class="form-field form-field-wide" style="margin-bottom:10px;"><strong>' . __('Service Type', 'woocommerce') . ':</strong> <span style="display:inline-block; padding: 4px 8px; background:#f0f0f0; border-radius:3px;">' . esc_html($service_type) . '</span></p>';
    }

    foreach ($fields as $group_key => $group) {
      echo '<h3 style="margin: 15px 0 8px; font-size:14px; border-bottom:1px solid #eee; padding-bottom:5px;">' . esc_html($group['title']) . '</h3>';
      echo '<div style="display:grid; grid-template-columns: 1fr; gap: 8px;">';

      foreach ($group['items'] as $meta_key => $config) {
        $value = $order->get_meta($meta_key);
        $class = isset($config['class']) ? $config['class'] : '';

        echo '<p class="form-field" style="margin-bottom:8px;">';
        echo '<label style="display:block; margin-bottom:3px; font-weight:600;">' . esc_html($config['label']) . '</label>';

        if ($config['type'] === 'textarea') {
          echo '<textarea name="' . esc_attr($meta_key) . '" class="' . esc_attr($class) . '" style="width:100%;" rows="2">' . esc_textarea($value) . '</textarea>';
        } elseif ($config['type'] === 'time_picker') {
          $time_parts = explode(':', $value);
          $hour = isset($time_parts[0]) ? $time_parts[0] : '00';
          $minute = isset($time_parts[1]) ? $time_parts[1] : '00';

          echo '<div class="zippy-admin-time-wrapper" style="display:flex; gap:5px;">';
          echo '<select class="zippy-hour-select" style="width:48%;">';
          for ($i = 0; $i <= 23; $i++) {
            $val = str_pad($i, 2, '0', STR_PAD_LEFT);
            echo '<option value="' . $val . '" ' . selected($hour, $val, false) . '>' . $val . '</option>';
          }
          echo '</select>';
          echo '<select class="zippy-minute-select" style="width:48%;">';
          for ($i = 0; $i < 60; $i += 5) {
            $val = str_pad($i, 2, '0', STR_PAD_LEFT);
            echo '<option value="' . $val . '" ' . selected($minute, $val, false) . '>' . $val . '</option>';
          }
          echo '</select>';
          echo '<input type="hidden" name="' . esc_attr($meta_key) . '" class="' . esc_attr($class) . '" value="' . esc_attr($value) . '" />';
          echo '</div>';
        } else {
          $input_value = $value;
          if ($meta_key === 'pick_up_date' && !empty($value)) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
              $input_value = date('d-m-Y', strtotime($value));
            }
          }
          echo '<input type="' . esc_attr($config['type']) . '" name="' . esc_attr($meta_key) . '" class="' . esc_attr($class) . '" value="' . esc_attr($input_value) . '" style="width:100%;" />';
        }
        echo '</p>';
      }

      echo '</div>';
    }
    echo '</div>';
  ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        // Datepicker init
        if (typeof $.fn.datepicker !== 'undefined') {
          $('.zippy-admin-datepicker').datepicker({
            dateFormat: 'dd-mm-yy',
            minDate: 0
          });
        }

        // Time picker sync logic
        $('.zippy-admin-time-wrapper').on('change', 'select', function() {
          var $wrapper = $(this).closest('.zippy-admin-time-wrapper');
          var h = $wrapper.find('.zippy-hour-select').val();
          var m = $wrapper.find('.zippy-minute-select').val();
          $wrapper.find('input[type="hidden"]').val(h + ':' + m);
        });
      });
    </script>
  <?php
  }

  /**
   * Save custom order fields from the admin order page.
   *
   * @param int $order_id
   */
  public function save_custom_order_fields_admin($order_id)
  {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $fields_to_save = [
      'pick_up_date',
      'pick_up_time',
      'pick_up_location',
      'drop_off_location',
      'no_of_passengers',
      'no_of_baggage',
      'flight_details',
      'eta_time',
      'special_requests',
      'key_member',
      'staff_name'
    ];

    foreach ($fields_to_save as $meta_key) {
      if (isset($_POST[$meta_key])) {
        $value = sanitize_text_field($_POST[$meta_key]);
        if ($meta_key === 'pick_up_date' && !empty($value)) {
          if (strpos($value, '-') !== false && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $date_obj = \DateTime::createFromFormat('d-m-Y', $value);
            if ($date_obj) {
              $value = $date_obj->format('Y-m-d');
            }
          }
        }
        $order->update_meta_data($meta_key, $value);
      }
    }
    $order->save();
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
    $order = wc_get_order($order_id);
    if (!$order) return;

    if (!empty($_POST['name_member_staff'])) {
      $order->update_meta_data('_name_member_staff', sanitize_text_field($_POST['name_member_staff']));
    }
    if (!empty($_POST['phone_member_staff'])) {
      $order->update_meta_data('_phone_member_staff', sanitize_text_field($_POST['phone_member_staff']));
    }
    $order->save();
  }

  public function display_custom_fields_in_order_details($order)
  {
    $name_member_staff = $order->get_meta('_name_member_staff');
    $phone_member_staff = $order->get_meta('_phone_member_staff');

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

    $js_path = get_stylesheet_directory() . '/assets/js/wc-order-add-product-type.js';
    $version = file_exists($js_path) ? filemtime($js_path) : '1.0';

    wp_enqueue_script(
      'wc-order-add-product-type',
      get_stylesheet_directory_uri() . '/assets/js/wc-order-add-product-type.js',
      ['jquery', 'wc-admin-meta-boxes'],
      $version,
      true
    );
  }

  function get_order_service_type_ajax()
  {
    if (empty($_GET['order_id'])) {
      wp_send_json_error();
    }

    $order_id = absint($_GET['order_id']);
    $order    = wc_get_order($order_id);
    $service  = $order ? $order->get_meta('service_type') : '';

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

    $order = wc_get_order($order_id);
    if (!$order) return;

    $order->update_meta_data('service_type', $service);
    $order->save();
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
      $type_service = $order->get_meta('service_type');
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
    if ($type_service == Zippy_Booking_Forms::HOURLY_DISPOSAL) {
      return 'hour_pricing';
    }

    if (
      $type_service == Zippy_Booking_Forms::AIRPORT_ARRIVAL_TRANSFER
      || $type_service == Zippy_Booking_Forms::AIRPORT_DEPARTURE_TRANSFER
      || $type_service == Zippy_Booking_Forms::POINT_TO_POINT_TRANSFER
    ) {
      return 'trip_pricing';
    }

    return 'trip_pricing';
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

  public function render_booking_date_column($column, $order_or_id)
  {
    if ('booking_date' === $column) {
      $order = ($order_or_id instanceof \WC_Order) ? $order_or_id : wc_get_order($order_or_id);
      
      if (!$order) return;

      $pick_up_date = $order->get_meta('pick_up_date');
      $pick_up_time = $order->get_meta('pick_up_time');

      if ($pick_up_date || $pick_up_time) {
        $display_date = $pick_up_date;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pick_up_date)) {
          $display_date = date('d-m-Y', strtotime($pick_up_date));
        }
        echo '<span>' . esc_html($display_date) . '</span>';
        if ($pick_up_time) {
          echo '<br><small>' . esc_html($pick_up_time) . '</small>';
        }
      } else {
        echo '<span>—</span>';
      }
    }
  }

  public function add_booking_date_filter_to_order_list()
  {
    $from = isset($_GET['filter_pick_up_date_from']) ? sanitize_text_field($_GET['filter_pick_up_date_from']) : '';
    $to   = isset($_GET['filter_pick_up_date_to']) ? sanitize_text_field($_GET['filter_pick_up_date_to']) : '';

    echo '<div class="zippy-booking-date-filter" style="display:inline-flex; gap:5px; align-items:center; margin-right:5px; vertical-align: middle;">';
    echo '<input type="text" name="filter_pick_up_date_from" value="' . esc_attr($from) . '" placeholder="' . __('From Date', 'woocommerce') . '" class="zippy-admin-datepicker" style="width:110px; height: 32px; font-size: 12px;"/>';
    echo '<input type="text" name="filter_pick_up_date_to" value="' . esc_attr($to) . '" placeholder="' . __('To Date', 'woocommerce') . '" class="zippy-admin-datepicker" style="width:110px; height: 32px; font-size: 12px;"/>';
    echo '</div>';
  ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        if (typeof $.fn.datepicker !== 'undefined') {
          $('.zippy-admin-datepicker').datepicker({
            dateFormat: 'dd-mm-yy',
            changeMonth: true,
            changeYear: true
          });
        }
      });
    </script>
<?php
  }

  public function filter_orders_by_booking_date_query_args($query_args)
  {
    if (!is_admin()) return $query_args;

    $from    = isset($_GET['filter_pick_up_date_from']) ? sanitize_text_field($_GET['filter_pick_up_date_from']) : '';
    $to      = isset($_GET['filter_pick_up_date_to']) ? sanitize_text_field($_GET['filter_pick_up_date_to']) : '';
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : ($query_args['orderby'] ?? '');

    if (empty($from) && empty($to) && !in_array($orderby, ['booking_date', 'pick_up_date'])) {
      return $query_args;
    }

    $meta_query = $query_args['meta_query'] ?? [];

    // Mệnh đề Named Meta Query để dùng cho cả lọc và sắp xếp
    $meta_query['booking_date_clause'] = [
      'key'  => 'pick_up_date',
      'type' => 'DATE',
    ];

    if (!empty($from) && !empty($to)) {
      $meta_query['booking_date_clause']['value']   = [date('Y-m-d', strtotime($from)), date('Y-m-d', strtotime($to))];
      $meta_query['booking_date_clause']['compare'] = 'BETWEEN';
    } elseif (!empty($from)) {
      $meta_query['booking_date_clause']['value']   = date('Y-m-d', strtotime($from));
      $meta_query['booking_date_clause']['compare'] = '>=';
    } elseif (!empty($to)) {
      $meta_query['booking_date_clause']['value']   = date('Y-m-d', strtotime($to));
      $meta_query['booking_date_clause']['compare'] = '<=';
    }

    if (in_array($orderby, ['booking_date', 'pick_up_date'])) {
      $query_args['orderby'] = 'booking_date_clause';
      if (isset($_GET['order'])) {
        $query_args['order'] = strtoupper(sanitize_text_field($_GET['order']));
      }
    }

    $query_args['meta_query'] = $meta_query;
    return $query_args;
  }

  /**
   * Migrate ALL Zippy Booking post_meta to Order Metadata (HPOS Compatible)
   */
}
