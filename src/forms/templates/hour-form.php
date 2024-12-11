<?php

global $product;
if (!is_product()) return;
$today = date('d-m-Y');
$key_member = 0;
if (is_user_logged_in()) {
  $key_member = 1;
}
?>
<div id="popupHour" class="popup">
  <div class="popup-content">
    <div class="calendar-box-custom">
      <div class="calendar-box">
        <div id="tab_hour_picker"></div>
      </div>
      <button class="close-btn close-popup-btn" id="closePopupHour">Continute Booking</button>
    </div>
  </div>
</div>
<form method="POST">
  <div class="box-pickup-information">
    <div class="input-text-pickup-information">
      <div class="row-form-custom">
        <input name="id_product" type="hidden" value="<?php echo $product->get_id(); ?>">
        <input name="key_member" type="hidden" value="<?php echo $key_member; ?>">
        <input name="service_type" type="hidden" value="Hourly/Disposal">
        <input name="midnight_fee" id="hbk_midnight_fee" type="hidden" value="0">
      </div>
      <!-- Get product categories & check min hour -->
      <?php
      $category_ids = $product->get_category_ids();
      $isMin3h = true;
      if (empty($category_ids)) {
        return;
      }
      foreach ($category_ids as $category_id) {
        $category = get_term($category_id, 'product_cat');
        if ($category->slug === 'min-4-hours') {
          $isMin3h = false;
        }
      }
      ?>
      <div class="row-form-custom col-2">
        <div class="col-form-custom position-relative">
          <div class="d-flex flex-wrap mb-1">
            <label for="pick_up_date">Pick Up Date & Time <span style="color:red;">*</span></label>
            <span class="note-midnight-fee" id="note_midnight_fee" style="display: none;">(Midnight fee has been applied.)</span>
          </div>
          <div class="d-flex" id="openPopupHour">
            <input type="text" id="hbk_pickup_date" name="pick_up_date" value="<?php echo $today;?>" placeholder="Select date" autocomplete="off" required />
            <input type="text" id="hbk_pickup_time" name="pick_up_time" value="<?php echo date("H:i"); ?>" autocomplete="off" required />
          </div>
        </div>
        <div class="col-form-custom pickup-type">
          <label for="additional_stop">Pick Up type <span style="color:red;">*</span></label>
          <select class="" id="hbk_pickup_fee" name="additional_stop">
            <option value="0" data-price="0" selected>Inside Singapore</option>
            <option value="1" data-price="25">Outside Singapore</option>
          </select>
        </div>
      </div>
      <div class="row-form-custom col-1">
        <div class="col-form-custom">
          <label for="time_use">Time <span style="color:red;">*</span></label>
          <select class="" id="hbk_time_value" name="time_use" required>
            <option value="" selected>Please choose an option</option>
            <?php
            if ($isMin3h) {
              echo ('<option value="3-hours">3 hours</option>');
            }
            ?>
            <option value="4">4 hours</option>
            <option value="5">5 hours</option>
            <option value="6">6 hours</option>
            <option value="7">7 hours</option>
            <option value="8">8 hours</option>
            <option value="9">9 hours</option>
            <option value="10">10 hours</option>
            <option value="11">11 hours</option>
            <option value="12">12 hours</option>
          </select>
        </div>
      </div>
      <div class="row-form-custom col-2">
        <div class="col-form-custom">
          <label for="pick_up_location">Pick Up Location <span style="color:red;">*</span></label>
          <input size="40" maxlength="60" class="" id="hbk_pickup_location" aria-required="true" aria-invalid="false" placeholder="Enter location" value="" type="text" name="pick_up_location" required>
        </div>
        <div class="col-form-custom">
          <label for="drop_off_location">Drop Off Location <span style="color:red;">*</span></label>
          <input size="40" maxlength="50" class="" id="hbk_dropoff_location" aria-required="true" aria-invalid="false" placeholder="Enter location" value="" type="text" name="drop_off_location" required>
        </div>
      </div>
      <div class="row-form-custom col-2">
        <div class="col-form-custom">
          <label for="no_of_passengers">No. of Passengers <span style="color:red;">*</span></label>
          <input size="40" class="" id="hbk_number_of_passengers" aria-required="true" aria-invalid="false" placeholder="Enter number" value="" type="number" max="100" min="1" name="no_of_passengers" required>
        </div>
        <div class="col-form-custom">
          <label for="no_of_baggage">No. of Baggage <span style="color:red;">*</span></label>
          <input size="40" class="" id="hbk_number_of_baggages" aria-required="true" aria-invalid="false" placeholder="Enter number" value="" type="number" max="100" min="1" name="no_of_baggage" required>
        </div>

      </div>
      <div class="row-form-custom col-1">
        <div class="col-form-custom col-1">
          <label for="special_requests">Special Requests</label>
          <input size="40" maxlength="400" class="" id="hbk_special_requests" aria-invalid="false" placeholder="Enter your request" value="" type="text" name="special_requests">
        </div>
      </div>
    </div>
    <div class="confirm-terms">
      <input class="terms-checkbox" type="checkbox" name="agree_terms" value="1" id="agree_terms_booing_hours" required>
      <label for="agree_terms_booing_hours">
        <ul class="list-terms">
          <li class="show-title">I submit this form to request for the services listed above. I understand that my booking will only be confirmed after I have received an email confirmation.</li>
          <li class="show-title">I have read and understood the terms and conditions</li>
        </ul>
      </label>
    </div>
    <div class="col-total-price-information">
      <!-- <label>Total Price: </label><span > $<span id="price-total"><?php echo $current_price = $product->get_price(); ?></span><span id="default-price" style="display:none"><?php echo $current_price = $product->get_price(); ?></span></span> -->
      <label>Total Price: </label>
      <span> $
        <span id="hbk_total_price" data-product-price="<?php echo $_price_per_hour = get_post_meta($product->get_id(), '_price_per_hour', true); ?>">
          <?php
          $_price_per_hour = get_post_meta($product->get_id(), '_price_per_hour', true);
          if (!empty($_price_per_hour)) {
            echo $_price_per_hour;
          } else {
            echo "0";
          }
          ?>
        </span>
      </span>
    </div>
    <div class="row-form-custom col-1">
      <div class="col-form-custom">
        <input class="" id="btnReserve" name="submit_hour_booking_form" type="submit" value="Payment Booking">
      </div>
    </div>
  </div>
</form>
