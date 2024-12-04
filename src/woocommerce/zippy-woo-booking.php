<?php

/**
 * Woocommece Booking Settings
 *
 *
 */

namespace Zippy_Booking_Car\Src\Woocommerce;

defined('ABSPATH') or die();

class Zippy_Woo_Booking
{
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
    add_action('woocommerce_product_options_pricing', array($this, 'add_custom_price_field_to_product'));
    // Save the price hour value when the product is saved
    add_action('woocommerce_process_product_meta',  array($this, 'save_custom_price_field'));
  }


  public function add_custom_price_field_to_product()
  {
    woocommerce_wp_text_input(array(
      'id' => '_price_per_hour',
      'label' => __('Price Per Hour', 'woocommerce'),
      'description' => __('Enter an price by hour for this product.', 'woocommerce'),
      'desc_tip' => 'true',
      'type' => 'number',
      'custom_attributes' => array(
        'step' => '0.1',
        'min' => '0'
      )
    ));
  }


  function save_custom_price_field($post_id)
  {
    $extra_price = isset($_POST['_price_per_hour']) ? sanitize_text_field($_POST['_price_per_hour']) : '';
    if (!empty($extra_price)) {
      update_post_meta($post_id, '_price_per_hour', $extra_price);
    } else {
      delete_post_meta($post_id, '_price_per_hour');
    }
  }
}
