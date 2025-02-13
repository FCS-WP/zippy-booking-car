<?php
global $product;
if (!is_product()) return;
$today = date('d-m-Y');
$key_member = 0;
if (is_user_logged_in()) {
  $key_member = 1;
}
?>
<div id="popup" class="popup">
  <div class="popup-content">
    <div class="calendar-box-custom">
      <div class="calendar-box">
        <div id="calendar"></div>
      </div>
      <button class="close-popup-btn" id="closePopup">Continute Booking</button>
    </div>
  </div>
</div>
<form method="POST" id="car_booking_form">
  <div class="box-pickup-information">

    <div class="input-text-pickup-information">
      <div class="row-form-custom">
        <input name="id_product" type="hidden" value="<?php echo $product->get_id(); ?>">
        <input name="key_member" type="hidden" value="<?php echo $key_member; ?>">
        <input name="midnight_fee" id="trip_midnight_fee" type="hidden" value="0">
        <input name="time_use" id="time_use" type="hidden" value="1">
      </div>
      <div class="row-form-custom col-2">
        <div class="col-form-custom position-relative" id="openPopup">
          <div class="d-flex flex-wrap mb-1">
            <label for="hbk_pickup_date">Pick Up Date & Time <span style="color:red;">*</span></label>
            <span class="note-midnight-fee note-trip-midnight" style="display: none;">(Midnight fee has been applied.)</span>
          </div>
          <div class="d-flex">
            <input class="pickupdate" id="pickupdate" value="<?php echo $today; ?>" type="text" name="pick_up_date" required>
            <input type="text" id="pickuptime" name="pick_up_time" min="00:00" max="24:00" value="<?php echo date("H:i"); ?>" required>
          </div>
        </div>
        <div class="col-form-custom pickup-type">
          <label for="hbk_pickup_fee">Pick Up type <span style="color:red;">*</span></label>
          <select class="" id="additional_stop" name="additional_stop">
            <option id="inside_additional_stop" value="0" data-price="0" selected>Incity</option>
            <option id="outside_additional_stop" value="1" data-price="25">Outcity</option>
          </select>
        </div>
      </div>

      <div class="row-form-custom col-1">
        <div class="col-form-custom ">
          <label for="servicetype">Type Services <span style="color:red;">*</span></label>
          <select class="" id="servicetype" name="service_type" required>
            <option value="">Please choose an option</option>
            <option value="Airport Arrival Transfer">Airport Arrival Transfer</option>
            <option value="Airport Departure Transfer">Airport Departure Transfer</option>
            <option value="Point-to-point Transfer">Point-to-point Transfer</option>
          </select>
        </div>
      </div>
      <div class="row-form-custom col-2">
        <div class="col-form-custom">
          <label for="pickuplocation">Pick Up <span style="color:red;">*</span></label>
          <input size="40" maxlength="60" class="" id="pickuplocation" aria-required="true" aria-invalid="false" placeholder="Enter location" value="" type="text" name="pick_up_location" required>
        </div>
        <div class="col-form-custom">
          <label for="doaddress">Drop Off <span style="color:red;">*</span></label>
          <input size="40" maxlength="50" class="" id="dolocation" aria-required="true" aria-invalid="false" placeholder="Enter location" value="" type="text" name="drop_off_location" required>
        </div>
      </div>
      <div class="row-form-custom col-2" id="input-flight">
        <div class="col-form-custom">
          <label for="flight">Flight Details<span style="color:red;">*</span></label>
          <input size="40" maxlength="400" class="" id="flight" aria-required="true" aria-invalid="false" placeholder="Enter your flight details" value="" type="text" name="flight_details">
        </div>
        <div class="col-form-custom">
          <label for="eta_time">ETE/ETA Time</label>
          <input type="time" name="eta_time" id="eta_time" placeholder="Enter time">
        </div>
      </div>
      <div class="row-form-custom col-2">
        <div class="col-form-custom">
          <label for="noofpassengers">No. of Passengers <span style="color:red;">*</span></label>
          <input class="" id="noofpassengers" aria-required="true" aria-invalid="false" placeholder="Enter No. of Passengers" value="" type="number" name="no_of_passengers" min="1" max="100" required>
        </div>
        <div class="col-form-custom">
          <label for="noofbaggage">No. of Baggage <span style="color:red;">*</span></label>
          <input class="" id="noofbaggage" aria-required="true" aria-invalid="false" placeholder="Enter No. of Baggage" value="" type="number" name="no_of_baggage" min="1" max="100" required>
        </div>
      </div>
      <div class="row-form-custom col-2 displayNone toggleDisplayElements">
        <div class="col-form-custom">
          <label for="emailcustomer">Customer Email<span style="color:red;">*</span></label>
          <input class="" id="emailcustomer" aria-required="true" aria-invalid="false" placeholder="Enter Your Email" value="" type="email" name="emailcustomer">
        </div>
        <div class="col-form-custom">
          <label for="phonecustomer">Customer Phone<span style="color:red;">*</span></label>
          <input class="" id="phonecustomer" aria-required="true" aria-invalid="false" placeholder="Enter Your Phone Number" value="" type="text" name="phonecustomer">
        </div>
      </div>
      <div class="row-form-custom col-1">
        <div class="col-form-custom">
          <label for="special_requests">Special Requests</label>
          <input size="40" maxlength="400" class="" id="hbk_special_requests" aria-invalid="false" placeholder="Enter your request" value="" type="text" name="special_requests">
        </div>
      </div>
      <div class="row-form-custom col-1">
        <div class="extra_text_noti">
          <p>Baby Seats (Subject to Availability)</p>
          <p>Contact us for more enquiry</p>
        </div>
      </div>
    </div>
    <div class="confirm-terms">
      <input class="terms-checkbox" type="checkbox" name="agree_terms" value="1" id="agree_terms" required>
      <label for="agree_terms">
        <ul class="list-terms">
          <li class="show-title">I submit this form to request for the services listed above. I understand that my booking will only be confirmed after I have received an email confirmation.</li>
          <li class="show-title">I have read and understood the terms and conditions</li>
        </ul>
      </label>
    </div>
    <div class="col-total-price-information toggleDisplayElements" >
      <label>Total Price: </label><span> $<span id="price-total" data-product-price="<?php echo $current_price = $product->get_price(); ?>"><?php echo $current_price = $product->get_price(); ?></span></span>
      <input type="hidden" name="price_product_default" value="<?php echo $current_price = $product->get_price(); ?>">
    </div>
    <div class="row-form-custom col-1 displayNone toggleDisplayElements">
      <div class="col-form-custom ">
        <input class="zippy_btn_submit" id="btnEnquiryNow" name="enquiry_car_booking_time" type="submit" value="Enquire Now">
        <div id="message_status_submit" class="displayNone"><div class="loader"></div><p> Wait! Processing Send Enquire</p></div>
      </div>
    </div>
    <div class="row-form-custom col-1 toggleDisplayElements">
      <div class="col-form-custom ">
        <input class="" id="btnReserve" name="submit_car_booking_time" type="submit" value="Payment Booking">
      </div>
    </div>
  </div>
</form>
