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
                    foreach ($orders as $order) {
                        $order_time_by_month_year = $order->get_date_created()->format('F Y');
                    ?>
                        <li class="<?php echo sanitize_title($order->get_status()); ?>">
                            <a href="#tab-<?php echo sanitize_title("order-" . $order->get_id()) ?>">
                                <?php echo "#" . $order->get_meta("_custom_order_number") . " (" . wc_get_order_status_name($order->get_status()) . ")"  ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <?php
                foreach ($orders as $order) {
                    $order_time_by_month_year = $order->get_date_created()->format('F Y');
                    $child_orders = [];
                    $child_order_ids = unserialize($order->get_meta("list_of_child_orders"));
                    if (!empty($child_order_ids)) {
                        $child_order_args = [
                            'limit'   => -1,
                            'post__in' => $child_order_ids,
                        ];
                        $child_orders = wc_get_orders($child_order_args);
                    }
                ?>
                    <div id="tab-<?php echo sanitize_title("order-" . $order->get_id()) ?>" class="tab-content">
                        <h3>Orders for <?php echo esc_html($order_time_by_month_year); ?></h3>
                        <div class="order-accordion">
                            <?php
                            if (!empty($child_orders)) {
                                foreach ($child_orders as $child_order) {
                            ?>
                                    <h4>Order #<?php echo $child_order->get_id(); ?></h4>
                                    <div>
                                        <table class="wp-list-table widefat fixed striped">
                                            <tr>
                                                <th>Order ID</th>
                                                <td>
                                                    <a href="admin.php?page=wc-orders&action=edit&id=<?php echo sanitize_title($child_order->get_id()); ?>" target="_blank">
                                                        <?php echo "#" . $child_order->get_id(); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Date</th>
                                                <td><?php echo $child_order->get_date_created()->date('Y-m-d H:i:s'); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Customer</th>
                                                <td><a href="user-edit.php?user_id=<?php echo $customer_id ?>" target="_blank"><?php echo $child_order->get_billing_first_name() . " " . $child_order->get_billing_last_name(); ?></a></td>
                                            </tr>
                                            <tr>
                                                <th>Email</th>
                                                <td><a href="mailto:<?php echo $child_order->get_billing_email(); ?>"><?php echo $child_order->get_billing_email(); ?></a></td>
                                            </tr>
                                            <tr>
                                                <th>Phone</th>
                                                <td>
                                                    <a href="tel:<?php echo $child_order->get_billing_phone(); ?>">
                                                        <?php echo $child_order->get_billing_phone(); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total</th>
                                                <td><?php echo wc_price($child_order->get_total()); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Status</th>
                                                <td><?php echo wc_get_order_status_name($child_order->get_status()); ?></td>
                                            </tr>
                                        </table>
                                        <h5>Products</h5>
                                        <table class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity</th>
                                                    <th>Total Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ($child_order->get_items() as $item) {
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <a href="post.php?action=edit&post=<?php echo $item["product_id"]; ?>" target="_blank">
                                                                <?php echo esc_html($item->get_name()); ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo esc_html($item->get_quantity()); ?></td>
                                                        <td><?php echo wc_price($item->get_total()) ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                            <?php }
                            } ?>
                        </div>
                        <div style="margin-top: 10px;">
                            <h3>Total for <?php echo $order_time_by_month_year . ": " . wc_price($order->get_total()) ?></h3>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>" class="button view-order-detail-button">View Order</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else {
            echo "<h3>No orders have been paid yet!</h3>";
        } ?>
    </div><a class="button back-to-history-bookings" href="admin.php?page=booking-history" style="margin-top: 20px;">Back to History Bookings</a>
    </div>
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
                            <td class="action" style="text-align: center;"><a href="admin.php?page=booking-history&customer_id=<?php echo $customer_id ?>&action=view">View</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
    </div>
<?php } else {
            echo "No data found";
        } ?>
<?php } ?>