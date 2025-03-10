"use strict";
$ = jQuery;

$(document).ready(function() {
    $("#month-tabs").tabs();

    $(".order-accordion").accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });

    $('.edit_order_btn').click(function(e){
        e.stopPropagation();
    })

    $(".create-order-button").on("click", function() {
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
                month_of_order: month_of_order
            },
            success: function(response) {
                if (response.success) {
                    alert("Payment order created successfully!");
                    location.reload();
                } else {
                    alert(response.data.message || "Failed to create payment order.");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
                alert("An error occurred while creating the payment order.");
            }
        });
    });
});