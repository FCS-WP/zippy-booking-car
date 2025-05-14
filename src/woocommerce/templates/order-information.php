<?php
$cart = WC()->cart;
$total_quantity = $cart->get_cart_contents_count();
$applied_coupons = $cart->get_applied_coupons();

$total_price = $cart->get_total();

foreach ($cart->get_cart() as $cart_item) {
  $product_name = $cart_item['data']->get_name();
  $product_price = $cart_item['data']->get_price();
?>
  <div class="box-order-booking">
    <h4 class="box-header">Your Booking</h4>
    <div class="row-checkout-form">
      <div class="dropdown-form">
        <label>Car Booking</label>
        <div class="wrap-form">
          <span class="value"><?php echo $product_name; ?></span>
        </div>
      </div>
      <?php if (!empty($cart_item['booking_hour']['time_use'])) { ?>
        <div class="dropdown-form">
          <label><span class="value"><?php echo $cart_item['booking_information']['service_type']; ?></span></label>
          <div class="wrap-form">
            <label>Time Use: </label><span class="value"> <?php echo $cart_item['booking_hour']['time_use']; ?> Hour</span>
          </div>
        </div>
      <?php } else { ?>
        <div class="dropdown-form">
          <label>Type</label>
          <div class="wrap-form">
            <span class="value"><?php echo $cart_item['booking_information']['service_type']; ?></span>
          </div>
        </div>
      <?php } ?>
    </div>
    <div class="row-checkout-form">
      <div class="dropdown-form">
        <label>Pick Up Date</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['pick_up_date']; ?></span>
        </div>
      </div>
      <div class="dropdown-form">
        <label>Pick Up Time</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['pick_up_time']; ?></span>
        </div>
      </div>
    </div>
    <div class="row-checkout-form">
      <div class="dropdown-form location_checkout">
        <label>Pick Up Location</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['pick_up_location']; ?></span>
        </div>
      </div>
      <div class="dropdown-form location_checkout">
        <label>Drop Off Location</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['drop_off_location']; ?></span>
        </div>
      </div>
    </div>
    <div class="row-checkout-form">
      <div class="dropdown-form">
        <label>No. of Passengers</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['no_of_passengers']; ?></span>
        </div>
      </div>
      <div class="dropdown-form">
        <label>No. of Baggage</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['no_of_baggage']; ?></span>
        </div>
      </div>
    </div>
    <?php
    if ($cart_item['booking_trip']['flight_details'] != NULL) {
    ?>
      <div class="row-checkout-form">
        <div class="dropdown-form">
          <label>Flight Details</label>
          <div class="wrap-form">
            <span class="value"><?php echo $cart_item['booking_trip']['flight_details']; ?></span>
          </div>
        </div>
        <div class="dropdown-form">
          <label>ETE/ETA Time</label>
          <div class="wrap-form">
            <span class="value"><?php echo $cart_item['booking_trip']['eta_time']; ?></span>
          </div>
        </div>
      </div>
    <?php
    }
    ?>
    <div class="row-checkout-form">
      <div class="dropdown-form">
        <label>Additional Stop</label>
        <div class="wrap-form">
          <span class="value"><?php
                              if ($cart_item['booking_information']['additional_stop'] == 1) {
                                echo "Outside Singapore";
                              } else {
                                echo "Inside Singapore";
                              }
                              ?></span>
        </div>
      </div>

      <div class="dropdown-form">
        <label>Special Reuest</label>
        <div class="wrap-form">
          <span class="value"><?php echo $cart_item['booking_information']['special_requests']; ?></span>
        </div>
      </div>
    </div>
    <h4 class="box-header">Your Billing</h4>
    <div class="box-billing">
      <div class="box-billing-flex">
        <div class="box-billing-flex_col">
          <span>Price Car</span>
        </div>
        <div class="box-billing-flex_col">
          <span>$<?php echo $product_price; ?></span>
        </div>
      </div>
      <?php
      if ($cart_item['booking_information']['midnight_fee'] == 1) {
      ?>
        <div class="box-billing-flex">
          <div class="box-billing-flex_col">
            <span>Midnight Fee</span>
          </div>
          <div class="box-billing-flex_col">
            <span>$25</span>
          </div>
        </div>
      <?php
      }
      if ($cart_item['booking_information']['additional_stop'] == 1) {
      ?>
        <div class="box-billing-flex">
          <div class="box-billing-flex_col">
            <span>Additional Stop Fee</span>
          </div>
          <div class="box-billing-flex_col">
            <span>$25</span>
          </div>
        </div>
      <?php
      }
      ?>
      <?php
      if (!empty($applied_coupons)) { ?>
        <?php
        foreach ($applied_coupons as $coupon_code) {
          $coupon = new WC_Coupon($coupon_code);
          $discount_total = WC()->cart->get_coupon_discount_amount($coupon_code); ?>
          <div class="box-billing-flex">
            <div class="box-billing-flex_col">
              <span>Promotion (<?php echo $coupon_code; ?>)</span>
            </div>
            <div class="box-billing-flex_col">
              <span>- <?php
                      echo "$" . $discount_total;
                    }
                      ?>
              </span>
            </div>
          </div>
        <?php
      }
        ?>
    </div>
    <div class="dropdown-form-checkout-total">
      <div class="dropdown-form-checkout-total_title">
        <label>Total Price</label>
      </div>
      <div class="dropdown-form-checkout-total_value">
        <span class="value"><?php echo $total_price; ?></span>
      </div>
    </div>
  </div>
  <?php do_action('woocommerce_checkout_order_review'); ?>
<?php
}
