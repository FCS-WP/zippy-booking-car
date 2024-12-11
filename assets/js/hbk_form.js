// import { Calendar, Options } from "vanilla-calendar-pro";
$(document).ready(function () {
  // Init date picker for hourly booking
  const options = {
    disableDatesPast: true,
    selectionTimeMode: 24,
    timeStepMinute: 5,
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
        <#ControlTime />
        <div class="time-avail">
          <div class="time-avail__item">
            <p>Pick up time</p><p id="get_hbk_time_pickup">00:00</p>
          </div>
          <div class="time-avail__item">
            <p>Pick up date</p><p id="get_hbk_date_pickup">04-12-2024</p>
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

      if (isToday(selectedDate)) {
        let today = new Date();
        self.set({
          selectedDates: self.context.selectedDates,
          timeMinHour: today.getHours() + 1,
          timeMaxHour: 23,
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
              <#ControlTime />
              <div class="time-avail">
                <div class="time-avail__item">
                  <p>Pick up time</p><p id="get_hbk_time_pickup">${selectedTime}</p>
                </div>
                <div class="time-avail__item">
                  <p>Pick up date</p><p id="get_hbk_date_pickup">${dateConverted}</p>
                </div>
              </div>
              
            `,
          },
        });
        let newHours = today.getHours() + ":0";
        hbkMidnightCheck(newHours);
      } else {
        self.set({
          selectedDates: self.context.selectedDates,
          timeMinHour: 0,
          timeMaxHour: 23,
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
              <#ControlTime />
              <div class="time-avail">
                <div class="time-avail__item">
                  <p>Pick up time</p><p id="get_hbk_time_pickup">00:00</p>
                </div>
                <div class="time-avail__item">
                  <p>Pick up date</p><p id="get_hbk_date_pickup">${dateConverted}</p>
                </div>
              </div>
              
            `,
          },
        });
        hbkMidnightCheck("0:0");
      }
    },
    onChangeTime(self) {
      hbkMidnightCheck(self.context.selectedTime);
      $("#hbk_pickup_time").val(self.context.selectedTime);
      $("#get_hbk_time_pickup").html(self.context.selectedTime);
    },
  };

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
    $('body').css('overflow', 'hidden');
  });

  closePopupButtonHour.on("click", function () {
    popupHour.css("display", "none");
    $('body').css('overflow', 'auto');
  });

  popupHour.on("click", function (event) {
    if (event.target === $(this)[0]) {
      popupHour.css("display", "none");
      $('body').css('overflow', 'auto');
    }
  });
});

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

function isToday(compareDate) {
  let date1 = new Date();
  let date2 = new Date(compareDate);

  date1.setHours(0, 0, 0, 0);
  date2.setHours(0, 0, 0, 0);

  return date1.getTime() === date2.getTime();
}

function hbkMidnightCheck(time) {
  const [hours, minutes] = time.split(":").map(Number);
  if (hours > 22 || hours < 7) {
    $("#hbk_midnight_fee").val("1");
    $("#note_midnight_fee").show();
  } else {
    $("#hbk_midnight_fee").val("0");
    $("#note_midnight_fee").hide();
  }
  calcHbkPrices();
  return true;
}

function convertDate(inputDate = new Date()) {
  const date = new Date(inputDate);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();

  return `${day}-${month}-${year}`;
}
