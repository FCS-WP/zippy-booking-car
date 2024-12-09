<?php
// function add_detail_page_menu_item()
// {
//     add_submenu_page(
//         null,
//         'View Detail',
//         'View Detail',
//         'manage_woocommerce',
//         'view-detail',
//         'view_detail_page'
//     );
// }
// add_action('admin_menu', 'add_detail_page_menu_item');

function view_bookings_page()
{
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
        $customer_id = sanitize_text_field($_GET['customer_id']);
        $args = array(
            'limit' => -1,
            'customer_id' => $customer_id,
        );
        $orders = wc_get_orders($args);

        if (!empty($orders)) {
            echo '<div class="wrap"><h1>Details for Customer ID: ' . esc_html($customer_id) . '</h1>';

            // Include jQuery UI for tabs and accordion
            echo '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';
            echo '<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';

            echo '<script>
            jQuery(document).ready(function($){ 
                $("#month-tabs").tabs(); // Initialize tabs
                $(".order-accordion").accordion({
                    collapsible: true,
                    active: false, // Start with all orders collapsed
                    heightStyle: "content"
                });

                $(".payment-button").on("click", function() {
                    var customer_id = $(this).data("customer-id");
                    var month_year = $(this).data("month-year");
                    
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: "create_payment_order",
                            customer_id: customer_id,
                            month_year: month_year
                        },
                        success: function(response) {
                            if (response.success) {
                                alert("Order created successfully: Order #" + response.data.order_id);
                                location.reload(); 
                            } else {
                                alert("Failed to create order: " + response.data.message);
                            }
                        },
                        error: function() {
                            alert("An error occurred while creating the order.");
                        }
                    });
                });
            });
            </script>';

            $grouped_by_month = array();
            $monthly_payment_orders = array();

            foreach ($orders as $order) {
                $order_date = $order->get_date_created();
                $month_year = $order_date->format('F Y');

                $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);
                $total_for_month = $order->get_meta('total_for_month', true);
                var_dump( $order->get_id(),$total_for_month ,);


                if ($is_monthly_payment_order) {
                    $monthly_payment_orders[$month_year] = $order;
                } else {
                    if (!isset($grouped_by_month[$month_year])) {
                        $grouped_by_month[$month_year] = array(
                            'orders' => array(),
                            'total' => 0
                        );
                    }
                    $grouped_by_month[$month_year]['orders'][] = $order;
                    $grouped_by_month[$month_year]['total'] += $order->get_total();
                }
            }

            // Tabs container
            echo '<div id="month-tabs">';
            echo '<ul>';
            foreach (array_keys($grouped_by_month) as $month_year) {
                $tab_status = isset($monthly_payment_orders[$month_year])
                    ? ' (' . esc_html(wc_get_order_status_name($monthly_payment_orders[$month_year]->get_status())) . ')'
                    : '';

                echo '<li><a href="#tab-' . sanitize_title($month_year) . '">' . esc_html($month_year) . $tab_status . '</a></li>';
            }
            echo '</ul>';
            

            // Tab content
            foreach ($grouped_by_month as $month_year => $data) {
                echo '<div id="tab-' . sanitize_title($month_year) . '">';
                echo '<h3>Orders for ' . esc_html($month_year) . '</h3>';
                echo '<div class="order-accordion">';

                foreach ($data['orders'] as $order) {
                    echo '<h4>Order #' . esc_html($order->get_id()) . '</h4>';
                    echo '<div>';

                    // Display order details
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<tr><th>Order ID</th><td>' . esc_html($order->get_id()) . '</td></tr>';
                    echo '<tr><th>Date</th><td>' . esc_html($order->get_date_created()->date('Y-m-d H:i:s')) . '</td></tr>';
                    echo '<tr><th>Customer</th><td>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td></tr>';
                    echo '<tr><th>Email</th><td>' . esc_html($order->get_billing_email()) . '</td></tr>';
                    echo '<tr><th>Phone</th><td>' . esc_html($order->get_billing_phone()) . '</td></tr>';
                    echo '<tr><th>Total</th><td>' . wc_price($order->get_total()) . '</td></tr>';
                    echo '<tr><th>Status</th><td>' . esc_html(wc_get_order_status_name($order->get_status())) . '</td></tr>';
                    echo '</table>';

                    // Products table
                    echo '<h5>Products</h5>';
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Product</th><th>Quantity</th><th>Price</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($order->get_items() as $item) {
                        echo '<tr>';
                        echo '<td>' . esc_html($item->get_name()) . '</td>';
                        echo '<td>' . esc_html($item->get_quantity()) . '</td>';
                        echo '<td>' . wc_price($item->get_total()) . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>'; // End order details
                }

                echo '</div>'; // End order accordion

                if (isset($monthly_payment_orders[$month_year])) {
                    $payment_order = $monthly_payment_orders[$month_year];
                    echo '<div style="margin-top: 10px;">';
                    echo '<p><strong>Monthly Payment Order Status:</strong> '. '<span class="order-status"> ' . esc_html(wc_get_order_status_name($payment_order->get_status())) .'</span>'. '</p>';
                    echo '<h3>Total for ' . esc_html($month_year) . ': ' . wc_price($data['total']);
                    echo '</div>';
                    echo '<button class="button" disabled>Payment</button>';
                } else {
                    echo '<div style="margin-top: 10px;">';
                    echo '<h3>Total for ' . esc_html($month_year) . ': ' . wc_price($data['total']);
                    echo '</div>';
                    echo '<button class="button payment-button" data-customer-id="' . esc_attr($customer_id) . '" data-month-year="' . esc_attr($month_year) . '">Payment</button>';
                }

                echo '</div>'; // End tab content for the current month
            }

            echo '</div>'; // End tabs container
            echo '<a href="' . esc_url(admin_url('admin.php?page=view-bookings')) . '" class="button" style="margin-top: 20px;">Back to Bookings</a>';
            echo '</div>';
        } else {
            echo '<div class="wrap"><h1>No Orders Found for Customer ID: ' . esc_html($customer_id) . '</h1>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=view-bookings')) . '" class="button" style="margin-top: 20px;">Back to Bookings</a>';
            echo '</div>';
        }
    } else {
        $args = array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'ASC',
        );
        $orders = wc_get_orders($args);

        $grouped_orders = array();
        foreach ($orders as $order) {
            $customer_id = $order->get_customer_id();

            if (!$customer_id) {
                continue;
            }

            if (!isset($grouped_orders[$customer_id])) {
                $billing_first_name = get_user_meta($customer_id, 'billing_first_name', true);
                $billing_last_name = get_user_meta($customer_id, 'billing_last_name', true);
                $user_info = get_userdata($customer_id);
                $display_name = sanitize_text_field($user_info->display_name);

                if (!empty($billing_first_name) && !empty($billing_last_name)) {
                    $customer_name = $billing_first_name . ' ' . $billing_last_name;
                } else {
                    $customer_name = $display_name;
                }
                $grouped_orders[$customer_id] = array(
                    'customer_name' =>  $customer_name,
                    'orders' => array(),
                );
            }
            $grouped_orders[$customer_id]['orders'][] = $order;
        }

        echo '<div class="wrap"><h1>Bookings</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Customer Name</th><th>Number of Orders</th><th>Action</th></tr></thead>';
        echo '<tbody>';

        if (!empty($grouped_orders)) {
            foreach ($grouped_orders as $customer_id => $data) {
                $customer_name = $data['customer_name'];

                $filtered_orders = array_filter($data['orders'], function ($order) {
                    return !$order->get_meta('is_monthly_payment_order');
                });

                $order_count = count($filtered_orders);

                echo '<tr>';
                echo '<td>' . esc_html($customer_name) . ' # ' . esc_html($customer_id) . '</td>';
                echo '<td>' . esc_html($order_count) . '</td>';
                echo '<td><a href="' . esc_url(admin_url('admin.php?page=view-bookings&customer_id=' . $customer_id . '&action=view')) . '">View</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">No bookings found.</td></tr>';
        }


        echo '</tbody></table>';
        echo '</div>';
    }
}


add_action('wp_ajax_create_payment_order', 'create_payment_order');

function create_payment_order()
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Unauthorized']);
        return;
    }

    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $month_year = isset($_POST['month_year']) ? sanitize_text_field($_POST['month_year']) : '';

    if (!$customer_id || !$month_year) {
        wp_send_json_error(['message' => 'Invalid data provided']);
        return;
    }

    $user_info = get_userdata($customer_id);
    if (!$user_info) {
        wp_send_json_error(['message' => 'Customer not found']);
        return;
    }

    $billing_first_name = get_user_meta($customer_id, 'billing_first_name', true);
    $billing_last_name = get_user_meta($customer_id, 'billing_last_name', true);
    $customer_name = trim($billing_first_name . ' ' . $billing_last_name);

    $args = array(
        'customer_id' => $customer_id,
        'limit' => -1,
    );
    $orders = wc_get_orders($args);

    $total_for_month = 0;
    $selected_orders = [];

    foreach ($orders as $order) {
        $order_date = $order->get_date_created();
        $order_month_year = $order_date->format('F Y');

        $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);

        if ($order_month_year === $month_year && !$is_monthly_payment_order) {
            $total_for_month += $order->get_total();
            $selected_orders[] = $order;
        }
    }

    if ($total_for_month <= 0) {
        wp_send_json_error(['message' => 'No orders found for the specified month']);
        return;
    }

    $order = wc_create_order();
    $order->set_customer_id($customer_id);
    $order->set_billing_first_name($billing_first_name);
    $order->set_billing_last_name($billing_last_name);
    $order->set_billing_email($user_info->user_email);
    $order->set_billing_phone(get_user_meta($customer_id, 'billing_phone', true));
    $order->set_status('pending');

    foreach ($selected_orders as $selected_order) {
        $product_name = 'Order #' . $selected_order->get_id() . ' (' . $customer_name . ' - ' . $month_year . ')';
        $item = new WC_Order_Item_Product();
        $item->set_name($product_name);
        $item->set_quantity(1);
        $item->set_total($selected_order->get_total());
        $order->add_item($item);
    }

    $order->add_order_note('Included Orders: ' . implode(', ', array_map(function ($o) {
        return $o->get_id();
    }, $selected_orders)));

    $order->update_meta_data('is_monthly_payment_order', true);
    $order->update_meta_data('total_for_month', $month_year);

    $custom_order_number = $order->get_id() . ' ' . $month_year . '-';
    $order->update_meta_data('_custom_order_number', $custom_order_number);

    $order->calculate_totals();

    $order_id = $order->save();

    if ($order_id) {
        wp_send_json_success(['order_id' => $order_id, 'total' => wc_price($total_for_month)]);
    } else {
        wp_send_json_error(['message' => 'Failed to create order']);
    }
}

add_filter('woocommerce_order_number', 'custom_order_number_display', 10, 2);

function custom_order_number_display($order_number, $order)
{
    $custom_order_number = $order->get_meta('_custom_order_number');
    if ($custom_order_number) {
        return $custom_order_number;
    }

    return $order_number;
}
