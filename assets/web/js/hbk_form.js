import { Calendar, Options } from "vanilla-calendar-pro";

// Init date picker for hourly booking
const options = {
  disableDatesPast: true,
  selectionTimeMode: 24,
  timeStepMinute: 5,
  layouts: {
    default: `
        <h5 class="heading-custom-vanilla">Pick Up Date and Time</h5>
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
        <p>Pick up date</p><p id="get_hbk_date_pickup"></p>
      </div>  
        <div class="time-avail__item">
            <p>Pick up time</p>
            <div class="pickup_time_row">
              <div class="col_pick_up_time_select">
                <label>Hour:</label>
                <select id="pick_up_hour_disposal" ></select>
              </div>
              <div class="col_pick_up_time_select">
                <label>Minutes:</label>
                <select id="pick_up_minute_disposal"></select>
              </div>
            </div>
          </div>
          
        </div>
        
      `,
  },
  onClickDate(self) {
    const selectedDate = self.context.selectedDates[0];
    const selectedTime = self.context.selectedTime;
    const dateConverted = convertDate(selectedDate);

    $("#hbk_pickup_date").val(dateConverted);
    $("#hbk_pickup_time").val(selectedTime);
    $("#get_hbk_date_pickup").text(dateConverted);
    parsePickupHourDisposal(selectedDate);
  },
};

function setDefaultPickupDateDisposal() {
  const today = new Date();
  const formattedDate = convertDate(today);
  $("#get_hbk_date_pickup").text(formattedDate);

  const current_time = $("#hbk_pickup_time");
  const select_hour = $("#pick_up_hour_disposal");
  const select_minutes = $("#pick_up_minute_disposal");
  var timeParts = current_time.val().split(":");
  var hour = timeParts[0];
  var minute = timeParts[1];

  select_hour.val(hour);
  select_minutes.val(roundUpToNearestFive(minute));
}

const formatDate = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};

$(document).ready(setDefaultPickupDateDisposal);

function roundUpToNearestFive(num) {
  return Math.ceil(num / 5) * 5;
}

if ($("#tab_hour_picker").length > 0) {
  const tabHourPicker = new Calendar("#tab_hour_picker", options);
  tabHourPicker.init();
}

// Function display price with domestic:
if ($("#hbk_pickup_fee").length > 0) {
  $("#hbk_pickup_fee").on("change", function () {
    calcHbkPrices();
  });
}
if ($("#hbk_time_value").length > 0) {
  $("#hbk_time_value").on("change", function () {
    calcHbkPrices();
  });
}
if ($("#hbk_midnight_fee").length > 0) {
  $("#hbk_midnight_fee").on("change", function () {
    calcHbkPrices();
  });
}

// Open/close popup handling
const openPopupButtonHour = $("#openPopupHour");
const closePopupButtonHour = $("#closePopupHour");
const popupHour = $("#popupHour");

openPopupButtonHour.on("click", function () {
  popupHour.css("display", "flex");
  $("body").css("overflow", "hidden");
});

closePopupButtonHour.on("click", function () {
  popupHour.css("display", "none");
  $("body").css("overflow", "auto");
});

popupHour.on("click", function (event) {
  if (event.target === $(this)[0]) {
    popupHour.css("display", "none");
    $("body").css("overflow", "auto");
  }
});


$(document).ready(function () {
  const d = new Date(
    new Date().toLocaleString("en-US", { timeZone: "Asia/Singapore" })
  );

  const todayStr = formatDate(d);

  const $hourSelect = $("#ete_hour");
  // const $pick_up_hour_disposal = $("#pick_up_hour_disposal");
  const $pick_up_minute_disposal = $("#pick_up_minute_disposal");

  for (let i = 0; i <= 23; i++) {
    $hourSelect.append(
      `<option value="${i.toString().padStart(2, "0")}">${i
        .toString()
        .padStart(2, "0")}</option>`
    );
  }
  for (let i = 0; i < 60; i += 5) {
    const value = i.toString().padStart(2, "0");
    $pick_up_minute_disposal.append(
      `<option value="${value}">${value}</option>`
    );
  }

  // Init default
  parsePickupHourDisposal(todayStr);
  handlerMinuteChangeDPS();
});

function handlerMinuteChangeDPS() {
  $("#pick_up_hour_disposal, #pick_up_minute_disposal").on("change", () => {
    const hour = $("#pick_up_hour_disposal").val();
    const minute = $("#pick_up_minute_disposal").val();

    if (hour !== null && minute !== null) {
      $("#hbk_pickup_time").val(`${hour}:${minute}`);
    }
  });
}

function calcHbkPrices() {
  let productPrice = $("#hbk_total_price").data("product-price");
  let timeValue =
    $("#hbk_time_value").val() !== "" ? $("#hbk_time_value").val() : 1;
  let pickupFee = $("#hbk_pickup_fee").val() == 1 ? 25 : 0;
  let midnightFee = $("#hbk_midnight_fee").val() == 1 ? 25 : 0;
  let totalPrice =
    parseFloat(productPrice) * parseInt(timeValue) +
    parseFloat(pickupFee) +
    parseFloat(midnightFee);
  $("#hbk_total_price").text(totalPrice);
}

function convertDate(inputDate = new Date()) {
  const date = new Date(inputDate);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();

  return `${day}-${month}-${year}`;
}


function parsePickupHourDisposal(activeDate) {
  const $targetSelect = $("#pick_up_hour_disposal");
  const $minuteSelect = $("#pick_up_minute_disposal");

  const now = new Date(
    new Date().toLocaleString("en-US", { timeZone: "Asia/Singapore" })
  );
  const currentHour = now.getHours();

  const todayStr = formatDate(now);
  const startHour = activeDate == todayStr ? currentHour + 1 : 0;

  if (!$targetSelect || typeof $targetSelect.append !== "function") return;

  let optionsHtml = "";

  for (let i = startHour; i <= 23; i++) {
    const hour = i.toString().padStart(2, "0");
    const selected = i === startHour ? " selected" : "";
    optionsHtml += `<option value="${hour}"${selected}>${hour}</option>`;
  }

  $targetSelect.html(optionsHtml);
  $minuteSelect.empty();
  for (let i = 0; i < 60; i += 5) {
    const value = i.toString().padStart(2, "0");
    $minuteSelect.append(`<option value="${value}">${value}</option>`);
  }

  handlerMinuteChangeDPS();
}
