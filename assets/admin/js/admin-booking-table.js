"use strict";
$ = jQuery;

$(document).ready(function () {
  $("#month-tabs").tabs();

  $(".order-accordion").accordion({
    collapsible: true,
    active: false,
    heightStyle: "content",
  });

  $(".edit_order_btn").click(function (e) {
    e.stopPropagation();
  });

  $(".create-order-button").on("click", function () {
    var customer_id = $(this).data("customer-id");
    var month_of_order = $(this).data("month-of-order");

    if (!customer_id || !month_of_order) {
      alert("Invalid customer ID or month.");
      return;
    }

    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "create_payment_order",
        customer_id: customer_id,
        month_of_order: month_of_order,
      },
      success: function (response) {
        if (response.success) {
          alert("Payment order created successfully!");
          location.reload();
        } else {
          alert(response.data.message || "Failed to create payment order.");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error:", error);
        alert("An error occurred while creating the payment order.");
      },
    });
  });

  // Filter by month
  const $monthFilter = $("#month-filter");
  const $orderNumberFilter = $("#order-number-filter");
  const $bookingDateFilter = $("#booking-date-filter");
  const $vehicleTypeFilter = $("#vehicle-type-filter");
  const $statusFilter = $("#status-filter");
  const $applyFiltersButton = $("#apply-filters-button");

  const initialMonth = $monthFilter.val();
  filterByMonth(initialMonth);

  $monthFilter.on("change", function () {
    const selectedMonth = $(this).val();
    filterByMonth(selectedMonth);
  });

  $applyFiltersButton.on("click", function () {
    applyAllFilters();
  });

  function filterByMonth(selectedMonth) {
    $(".create-order-container, .view-order-detail-button").each(function () {
      const itemMonth = $(this).data("month");
      $(this).toggle(itemMonth === selectedMonth);
    });

    $("#orders-table tbody tr").each(function () {
      const rowMonth = $(this).data("month");
      $(this).toggle(rowMonth === selectedMonth);
    });
  }

  function applyAllFilters() {
    const selectedMonth = $monthFilter.val();
    const orderIdInput = $orderNumberFilter.val().toLowerCase().trim();
    const bookingDateInput = $bookingDateFilter.val();
    const vehicleTypeInput = $vehicleTypeFilter.val().toLowerCase().trim();
    const statusInput = $statusFilter.val();

    $("#orders-table tbody tr").each(function () {
      const $row = $(this);
      const rowMonth = $row.data("month");
      const rowOrderId = String($row.data("order-id")).toLowerCase();
      const rowBookingDate = $row.data("booking-date");
      const rowVehicleType = String($row.data("vehicle-type")).toLowerCase();
      const rowStatus = $row.data("status");

      const matchMonth = rowMonth === selectedMonth;
      const matchOrderId = !orderIdInput || rowOrderId.includes(orderIdInput);
      const matchBookingDate =
        !bookingDateInput || rowBookingDate === bookingDateInput;
      const matchVehicleType =
        !vehicleTypeInput || rowVehicleType.includes(vehicleTypeInput);
      const matchStatus = !statusInput || rowStatus === statusInput;

      const shouldShow =
        matchMonth &&
        matchOrderId &&
        matchBookingDate &&
        matchVehicleType &&
        matchStatus;
      $row.toggle(shouldShow);
    });
  }
});
