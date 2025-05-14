<?php

/**
 * Bookings Admin Settings
 *
 *
 */

namespace Zippy_Booking_Car\Src\Admin;

defined('ABSPATH') or die();

use WC_Settings_Page;

class Zippy_Woo_Settings extends WC_Settings_Page
{
  public function __construct()
  {
    $this->id    = 'extra_fee_settings';
    $this->label = 'Extra Fee Settings';
    parent::__construct();
  }

  public function get_settings()
  {
    $settings = array(
      array(
        'title' => __('Extra Fee Settings', 'zippy-booking-car'),
        'type'  => 'title',
        'id'    => 'zippy_extra_fee_settings_title',
      ),
      array(
        'name' => 'Enable/Disable',
        'desc'     => __('Enable CC fee.', 'zippy-booking-car'),
        'type' => 'checkbox',
        'id' => 'enable_cc_fee',
        'autoload' => true,
      ),
      array(
        'title'    => __('CC Fee Amount', 'zippy-booking-car'),
        'desc'     => __('Enter the default extra fee amount.', 'zippy-booking-car'),
        'id'       => 'zippy_cc_fee_amount',
        'default'  => '5',
        'type'     => 'text',
        'desc_tip' => true,
      ),

      array(
        'title'    => __('Title', 'zippy-booking-car'),
        'desc'     => __('Fee name that the customer will see on your website ', 'zippy-booking-car'),
        'id'       => 'zippy_cc_fee_name',
        'default'  => '5% CC fee',
        'type'     => 'text',
        'desc_tip' => true,
      ),

      array(
        'type' => 'sectionend',
        'id'   => 'zippy_extra_fee_settings_title',
      ),
    );

    return apply_filters('woocommerce_get_settings_' . $this->id, $settings);
  }
}
