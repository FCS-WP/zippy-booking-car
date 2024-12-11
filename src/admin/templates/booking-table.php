<?php
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
?>
    <?php
    $customer_id = sanitize_text_field($_GET['customer_id']);
    $args = array(
        'limit' => -1,
        'customer_id' => $customer_id,
    );
    $orders = wc_get_orders($args);

    if (!empty($orders)) { ?>
        <div class="wrap">
            <h1>Details for Customer ID: <?php echo esc_html($customer_id) ?> </h1>
            <?php
            $grouped_by_month = array();
            $monthly_payment_orders = array();

            foreach ($orders as $order) {
                $order_date = $order->get_date_created();
                $month_of_order = $order_date->format('F Y');


                $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);


                if ($is_monthly_payment_order) {
                    $monthly_payment_orders[$month_of_order] = $order;
                } else {
                    if (!isset($grouped_by_month[$month_of_order])) {
                        $grouped_by_month[$month_of_order] = array(
                            'orders' => array(),
                            'total' => 0
                        );
                    }
                    $grouped_by_month[$month_of_order]['orders'][] = $order;
                    $grouped_by_month[$month_of_order]['total'] += $order->get_total();
                }
            }
            ?>


            <div id="month-tabs">
                <ul>
                    <?php
                    foreach (array_keys($grouped_by_month) as $month_of_order) {
                        $tab_status = isset($monthly_payment_orders[$month_of_order])
                            ? ' (' . esc_html(wc_get_order_status_name($monthly_payment_orders[$month_of_order]->get_status())) . ')'
                            : '';
                    ?>
                        <li><a href="#tab-<?php echo sanitize_title($month_of_order) ?>"><?php echo esc_html($month_of_order) . $tab_status ?></a></li>
                    <?php
                    } ?>
                </ul>

                <?php
                // Tab content
                foreach ($grouped_by_month as $month_of_order => $data) {
                ?>
                    <div id="tab-<?php echo sanitize_title($month_of_order) ?>">
                        <h3>Orders for <?php echo  esc_html($month_of_order)  ?></h3>
                        <div class="order-accordion">
                            <?php
                            foreach ($data['orders'] as $order) {
                            ?>
                                <h4>Order #<?php echo esc_html($order->get_id()) ?></h4>
                                <div>

                                    <!-- Display order details -->
                                    <table class="wp-list-table widefat fixed striped">
                                        <tr>
                                            <th>Order ID</th>
                                            <td> <?php echo esc_html($order->get_id()) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo esc_html($order->get_date_created()->date('Y-m-d H:i:s')) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Customer</th>
                                            <td><?php echo esc_html($order->get_billing_first_name() .  $order->get_billing_last_name()) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo esc_html($order->get_billing_email()) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Phone</th>
                                            <td><?php echo esc_html($order->get_billing_phone()) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Total</th>
                                            <td><?php echo wc_price($order->get_total()) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td><?php echo esc_html(wc_get_order_status_name($order->get_status())) ?></td>
                                        </tr>
                                    </table>

                                    <!-- Products table -->
                                    <h5>Products</h5>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($order->get_items() as $item) {
                                            ?>
                                                <tr>
                                                    <td><?php echo esc_html($item->get_name()) ?></td>
                                                    <td><?php echo esc_html($item->get_quantity()) ?></td>
                                                    <td><?php echo wc_price($item->get_total()) ?></td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div> <!-- End order details -->
                            <?php
                            }
                            ?>

                        </div> <!-- End order accordion -->
                        <?php
                        if (isset($monthly_payment_orders[$month_of_order])) {
                            $payment_order = $monthly_payment_orders[$month_of_order];
                        ?>
                            <div style="margin-top: 10px;">
                                <p><strong>Monthly Payment Order Status:</strong><span class="order-status"> <?php echo esc_html(wc_get_order_status_name($payment_order->get_status())) ?></span></p>
                                <h3>Total for <?php echo esc_html($month_of_order) ?>: <?php echo wc_price($data['total']); ?>
                            </div>
                            <button class="button" disabled>Payment</button>
                        <?php
                        } else {
                        ?>
                            <div style="margin-top: 10px;">
                                <h3>Total for <?php echo esc_html($month_of_order) ?>: <?php echo wc_price($data['total']); ?>
                            </div>
                            <button class="button payment-button" data-customer-id="<?php echo esc_attr($customer_id) ?>" data-month-of-order="<?php echo esc_attr($month_of_order) ?>">Payment</button>
                        <?php
                        }
                        ?>

                    </div> <!-- End tab content for the current month -->
                <?php
                }
                ?>

            </div> <!--End tabs container -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=booking-table')) ?>" class="button" style="margin-top: 20px;">Back to Bookings</a>

        </div>
    <?php
    } else {
    ?>
        <div class="wrap">
            <h1>No Orders Found for Customer ID: <?php echo esc_html($customer_id) ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=booking-table')) ?>" class="button" style="margin-top: 20px;">Back to Bookings</a>

        </div>
    <?php
    }
} else {
    ?>
    <?php
    $args = array(
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'ASC',
    );
    $orders = wc_get_orders($args);

    $grouped_orders = array();
    ?>
    <?php
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
    ?>

    <div class="wrap">
        <h1>Bookings</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Number of Orders</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($grouped_orders)) {
                    foreach ($grouped_orders as $customer_id => $data) {
                        $customer_name = $data['customer_name'];

                        $filtered_orders = array_filter($data['orders'], function ($order) {
                            return !$order->get_meta('is_monthly_payment_order');
                        });
                        $order_count = count($filtered_orders);
                ?>

                        <tr>
                            <td> #<?php echo esc_html($customer_id) ?> <?php echo esc_html($customer_name) ?></td>
                            <td><?php echo esc_html($order_count) ?></td>
                            <td><a href="<?php echo esc_url(admin_url('admin.php?page=booking-table&customer_id=' . $customer_id . '&action=view')) ?>">View</a></td>
                        </tr>
                    <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="4">No bookings found.</td>
                    </tr>
                <?php
                }

                ?>
            </tbody>
        </table>

    </div>
<?php
}
