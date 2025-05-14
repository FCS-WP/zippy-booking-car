<?php
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
?>
    <div class="wrap">
        <h1>Details for Customer ID: <?php echo $customer_id; ?> </h1>
        <?php
        if (!empty($orders)) {
        ?>
            <div id="month-tabs">
                <ul>
                    <?php
                    $orders_by_month = [];
                    foreach ($orders as $order) {
                        $order_time_by_month_year = $order->get_meta('month_of_order', true);;
                        if (!isset($orders_by_month[$order_time_by_month_year])) {
                            $orders_by_month[$order_time_by_month_year] = [];
                        }
                        $orders_by_month[$order_time_by_month_year][] = $order;
                    }

                    foreach ($orders_by_month as $month => $month_orders) {
                        foreach ($month_orders as $order) {
                            $tab_status = $order->get_status();
                        }
                    ?>
                        <li class="<?php echo $tab_status; ?>">
                            <a href="#tab-<?php echo esc_attr(sanitize_title($month)); ?>">
                                <?php
                                echo esc_html($month);

                                if (!empty($tab_status)) {
                                    echo ' (' . wc_get_order_status_name($tab_status) . ')';
                                }
                                ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>

                <?php
                foreach ($orders_by_month as $month => $month_orders) {
                ?>
                    <div id="tab-<?php echo esc_attr(sanitize_title($month)); ?>" class="tab-content">
                        <h3>Orders for <?php echo esc_html($month); ?></h3>
                        <div class="order-accordion">
                            <?php
                            foreach ($month_orders as $order) {
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
                                <h4 class="<?php echo $tab_status; ?>">Order #<?php echo $order->get_meta('_custom_order_number') ?> (<?php echo wc_get_order_status_name($order->get_status()); ?>)</h4>
                                <div>
                                    <table class="wp-list-table widefat fixed striped">
                                        <tr>
                                            <th>Order ID</th>
                                            <td>
                                                <a href="admin.php?page=wc-orders&action=edit&id=<?php echo $order->get_id(); ?>" target="_blank">
                                                    <?php echo "#" . $order->get_id(); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo $order->get_date_created()->date('Y-m-d H:i:s'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Total</th>
                                            <td><?php echo wc_price($order->get_total()); ?></td>
                                        </tr>

                                    </table>

                                    <?php if (!empty($child_orders)) { ?>
                                        <h4>Items</h4>
                                        <div class="order-child-items">
                                        <?php foreach ($child_orders as $child_order) { ?>
                                            <div class="order-child-item">
                                                <h5><a href="admin.php?page=wc-orders&action=edit&id=<?php echo $child_order->get_id(); ?>" target="_blank">Order #<?php echo $child_order->get_id(); ?></a> (<?php echo wc_get_order_status_name($child_order->get_status()); ?>)</h5>
                                                <p>Total: <?php echo wc_price($child_order->get_total()); ?> </p>
                                            </div>
                                        <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
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