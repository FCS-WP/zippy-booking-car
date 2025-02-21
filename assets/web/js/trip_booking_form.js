import { Calendar, Options } from "vanilla-calendar-pro";

// Option for vanilla calendar JS
const options = {
  selectionTimeMode: 24,
  timeStepMinute: 5,
  disableDatesPast: true,
  layouts: {
    default: `
      <h5 class="heading-custom-vanilla">Pick Up Date</h5>
      <div class="vc-header" data-vc="header" role="toolbar" aria-label="Calendar Navigation">
        <#ArrowPrev />  
        <div class="vc-header__content" data-vc-header="content">
          <#Year /> | <#Month />
        </div>
        <#ArrowNext />
      </div>
      <div class="vc-wrapper" data-vc="wrapper">
        <#WeekNumbers />
        <div class="vc-content" data-vc="content">
          <#Week />
          <#Dates />
          <#DateRangeTooltip />
        </div>
        </div>
        <div class="time-avail">
        <div class="time-avail__item">
          <p>Pick up date</p><p id="get_date_pickup">04-12-2024</p>
        </div>
        <div class="time-avail__item">
          <p>Pick up time</p>
          <div class="pickup_time_row">
            <div class="col_pick_up_time_select">
              <label>Hour:</label>
              <select id="pick_up_hour" class="pick_up_hour"></select>
            </div>
            <div class="col_pick_up_time_select">
              <label>Minutes:</label>
              <select id="pick_up_minute" class="pick_up_minute"></select>
            </div>
          </div>
        </div>
      </div>
      
    `,
  },
  onClickDate(self) {
    var date = self.context.selectedDates;

    if (date[0] !== undefined) {
      const pickupdate = $("#pickupdate");
      pickupdate.val(convertDate(date));
      $("#get_date_pickup").text(convertDate(date));
      // midnightCheck(self.context.selectedTime);
    }
  },
};
const calendar = $("#calendar");
if (calendar.length) {
  const calendar = new Calendar("#calendar", options);
  calendar.init();
}


// Function to convert date format from yyyy-mm-dd to dd-mm-yyyy
function convertDate(inputDate) {
  const date = new Date(inputDate);
  const day = String(date.getDate()).padStart(2, "0"); // Ensure two digits
  const month = String(date.getMonth() + 1).padStart(2, "0"); // Months are 0-indexed
  const year = date.getFullYear();

  return `${day}-${month}-${year}`;
}

// Function to calculate booking total price after changing the date range
const $selectElementTripForm = $("#additional_stop");
const $selectElementHourForm = $("#hbk_pickup_fee");
const $resultPrice = $("#price-total");
const productPrice = $resultPrice.data("product-price");

let total_price = 0;
let additional_stop = 0;
let result_price_number = 0;

const $openPopupButton = $("#openPopup");
const $closePopupButton = $("#closePopup");
const $popup = $("#popup");

// Open popup
$openPopupButton.on("click", () => {
  $popup.css("display", "flex");
  document.body.style.overflow = "hidden";
  $("body").css("overflow", "hidden");
});

// Close popup
$closePopupButton.on("click", () => {
  $popup.css("display", "none");
  $("body").css("overflow", "auto");
});


// Close popup when clicking outside the content
$popup.on("click", (event) => {
  if ($(event.target).is($popup)) {
    $popup.css("display", "none");
    $("body").css("overflow", "auto");
  }
});

$("#servicetype").on("change", function () {
  $("#input-flight").css("display", this.value === "Point-to-point Transfer" ? "none" : "flex");
});

function midnightCheck(time) {
  const [hours, minutes] = time.split(":").map(Number);
  if (hours > 22 || hours < 7) {
    $(".note-trip-midnight").show();
    $("#trip_midnight_fee").val(1);
  } else {
    $(".note-trip-midnight").hide();
    $("#trip_midnight_fee").val(0);
  }
  cacul_midnight_time($("#trip_midnight_fee").val());
  return true;
}

$("#additional_stop, #hbk_pickup_fee").on("change", function () {
  $(".toggleDisplayElements").toggleClass("displayNone");
});

document.addEventListener("DOMContentLoaded", () => {
  $("#additional_stop, #hbk_pickup_fee").on("change", function () {
    $("#additional_stop, #hbk_pickup_fee").val(this.value);
  });
});
function cacul_midnight_time(val) {
  result_price_number = $resultPrice.text();

  if (val == 1) {
    $resultPrice.html(Number(productPrice) + 25);
  }
  if (val == 0) {
    $resultPrice.html(Number(productPrice));
  }
  return;
}


$(document).ready(function () {
  function handleFormSubmission(buttonId, formId, statusMessageId) {
    $(buttonId).click(function (event) {
      event.preventDefault();

      var formData = $(formId).serialize();
      var $btn = $(buttonId);
      var $statusMessage = $(statusMessageId);

      $btn.addClass("displayNone");
      $statusMessage.removeClass("displayNone");

      $.ajax({
        url: "/wp-admin/admin-ajax.php",
        type: "POST",
        data: formData + "&action=enquiry_car_booking",
        dataType: "json",
        success: function (response) {
          if (response.success) {
            alert("Enquiry Sent");
          } else {
            alert("Missing Fields. PLease try again!");
          }
        },
        error: function () {
          alert("System error! Please try again.");
        },
        complete: function () {
          $btn.removeClass("displayNone");
          $statusMessage.addClass("displayNone");
        },
      });
    });
  }

  handleFormSubmission("#btnEnquiryNow", "#car_booking_form", "#message_status_submit");
  handleFormSubmission("#btnEnquiryHourNow", "#car_booking_hour_form", "#message_hours_status_submit");
});

const $hourSelect = $("#ete_hour");
const $minuteSelect = $("#ete_minute");
const $pickUphourSelect = $("#pick_up_hour");
const $pickUpminuteSelect = $("#pick_up_minute");


for (let i = 0; i <= 23; i++) {
  $hourSelect.append(`<option value="${i.toString().padStart(2, "0")}">${i.toString().padStart(2, "0")}</option>`);
  $pickUphourSelect.append(`<option value="${i.toString().padStart(2, "0")}">${i.toString().padStart(2, "0")}</option>`);
}

for (let i = 0; i < 60; i += 5) {
  const value = i.toString().padStart(2, "0");
  $minuteSelect.append(`<option value="${value}">${value}</option>`);
  $pickUpminuteSelect.append(`<option value="${value}">${value}</option>`);
  
}

$('#ete_hour, #ete_minute').on('change', () => {
  const hour = $('#ete_hour').val();
  const minute = $('#ete_minute').val();
  
  if (hour !== null && minute !== null) {
      $('#eta_time').val(`${hour}:${minute}`);
  }
});


$('#pick_up_hour, #pick_up_minute').on('change', () => {
  const hour = $('#pick_up_hour').val();
  const minute = $('#pick_up_minute').val();
  
  if (hour !== null && minute !== null) {
      $('#pickuptime').val(`${hour}:${minute}`);
  }
});


