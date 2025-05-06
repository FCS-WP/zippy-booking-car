<?php
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
?>
    <div class="wrap">
    <h1>Details for Customer: <span class="text-capitalize"> <?php echo $orders[0]->get_billing_first_name() . ' ' . $orders[0]->get_billing_last_name(); ?> </span> </h1>
        <?php
        if (!empty($orders)) {
        ?>
            <div id="orders-table">
                <table class="wp-list-table widefat fixed striped booking-table history">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Child Orders</th>
                            <th>Total</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $orders_by_month = [];
                        foreach ($orders as $order) {
                            $order_time_by_month_year = $order->get_meta('month_of_order', true);
                            if (!isset($orders_by_month[$order_time_by_month_year])) {
                                $orders_by_month[$order_time_by_month_year] = [];
                            }
                            $orders_by_month[$order_time_by_month_year][] = $order;
                        }

                        foreach ($orders_by_month as $month => $month_orders) {
                            foreach ($month_orders as $order) {
                                $tab_status = $order->get_status();
                                $child_orders = [];
                                $child_order_ids = unserialize($order->get_meta("list_of_child_orders"));
                                if (!empty($child_order_ids)) {
                                    $child_order_args = [
                                        'limit'   => -1,
                                        'post__in' => $child_order_ids,
                                    ];
                                    $child_orders = wc_get_orders($child_order_args);
                                    usort($child_orders, function ($a, $b) {
                                        return $a->get_id() - $b->get_id();
                                    });
                                }
                        ?>
                                <tr class="<?php echo $tab_status; ?>">
                                    <td class="booking-name history"><a href="admin.php?page=wc-orders&action=edit&id=<?php echo $order->get_id(); ?>" target="_blank">Order #<?php echo $order->get_meta('_custom_order_number'); ?></a></td>
                                    <td class="bookings_status column-order_status">
                                        <span class="booking-status status-<?php echo esc_attr($order->get_status()); ?> tips">
                                            <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                        </span>
                                    </td>
                                    <td class="booking-date"><?php echo esc_html($order->get_date_created()->date('F j, Y')); ?></td>
                                    <td>
                                        <?php if (!empty($child_orders)) { ?>
                                            <ul>
                                                <?php foreach ($child_orders as $child_order) { ?>
                                                    <li>
                                                        <a href="admin.php?page=wc-orders&action=edit&id=<?php echo $child_order->get_id(); ?>" target="_blank">
                                                            Order #<?php echo $child_order->get_id(); ?>
                                                        </a> (<?php echo wc_get_order_status_name($child_order->get_status()); ?>) -
                                                        <?php echo wc_price($child_order->get_total()); ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        <?php } else { ?>
                                            No child orders
                                        <?php } ?>
                                    </td>
                                    <td><?php echo wc_price($order->get_total()); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>


        <?php } else {
            echo "<h3>No orders have been paid yet!</h3>";
        } ?>
    </div>
    <a class="button back-to-history-bookings" href="admin.php?page=booking-history" style="margin-top: 20px;">
        Back to History Bookings
    </a>
<?php
} else {
?>
    <div class="wrap">
        <h1>History</h1>
        <?php if (!empty($order_infos)) { ?>
            <table class="wp-list-table widefat fixed striped table-customers">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th style="width: 15%; text-align: center;">Number of paid orders</th>
                        <th style="width: 10%; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($order_infos as $customer_id => $data) {
                    ?>
                        <tr>
                            <td class="customer-name"><a href="admin.php?page=booking-history&customer_id=<?php echo $customer_id ?>&action=view">#<?php echo esc_html($customer_id) . ' ' . esc_html($data["customer_name"]); ?></a></td>
                            <td class="order-count" style="text-align: center;">
                                <?php
                                $filtered_orders = array_filter($data['orders'], function ($order) {
                                    return $order->get_status() === 'completed';
                                });

                                $order_count = count($filtered_orders);
                                echo $order_count;
                                ?>

                            </td>
                            <td class="action" style="text-align: center;">
                                <a href="admin.php?page=booking-history&customer_id=<?php echo $customer_id ?>&action=view">View</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
    </div>
<?php
        } else {
            echo "No data found";
        }
    } ?>