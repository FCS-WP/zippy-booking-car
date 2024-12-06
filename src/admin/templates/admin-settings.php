<?php

function create_combined_order($order_ids) {
    $new_order = wc_create_order();

    foreach ($order_ids as $order_id) {
        $old_order = wc_get_order($order_id);

        if ($old_order) {
            foreach ($old_order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();

                $new_order->add_product(wc_get_product($product_id), $quantity);
            }
        }
    }

    $new_order->set_address($old_order->get_address('billing'), 'billing');
    $new_order->set_address($old_order->get_address('shipping'), 'shipping');
    $new_order->add_order_note('Đơn hàng tổng hợp từ các đơn hàng: ' . implode(', ', $order_ids));

    $new_order->calculate_totals();
    $new_order->save();

    return $new_order->get_id(); // Trả về ID của đơn hàng mới
}

// Sử dụng hàm
$order_ids = [125, 129, 253];
$new_order_id = create_combined_order($order_ids);



// function get_orders_by_customer_role() {
//     $args = [
//         'role'    => 'administrator',
//         'fields'  => 'ID',       
//         'orderby' => 'ID',
//         'order'   => 'ASC',
//     ];

//     $customer_ids = get_users($args);
//     if (empty($customer_ids)) {
//         echo 'No customers with role "customer" found.';
//         return;
//     }

    
//     $args_orders = [
//         'status'     => 'any',        
//         'limit'      => -1,           
//         'customer'   => $customer_ids 
//     ];

//     $orders = wc_get_orders($args_orders);

//     if (empty($orders)) {
//         echo 'No orders found for customers with role "customer".';
//         return;
//     }

//     foreach ($orders as $order) {
//         echo '<h3>Order ID: ' . $order->get_id() . '</h3>';
//         echo '<p>Customer ID: ' . $order->get_customer_id() . '</p>';
//         echo '<p>Order Total: ' . $order->get_total() . ' ' . get_woocommerce_currency_symbol() . '</p>';
//         echo '<p>Order Status: ' . wc_get_order_status_name($order->get_status()) . '</p>';
//         echo '<hr>';
//     }
// }

// get_orders_by_customer_role();


