<?php
    if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
?>
    <div class="wrap">
        <h1>Details for Customer ID: <?php echo $customer_id; ?> </h1>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script>
            jQuery(document).ready(function ($) {
                $("#month-tabs").tabs(); // Initialize tabs
                $(".order-accordion").accordion({
                    collapsible: true,
                    active: false, // Start with all orders collapsed
                    heightStyle: "content"
                });
            });
        </script>


        <?php
            $grouped_by_month = array();
            $monthly_payment_orders = array();
            foreach ($orders as $order) {
                $order_date = $order->get_date_created();
                $month_year = $order_date->format('F Y');

                $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);
                $order_status = $order->get_status();
                if ($is_monthly_payment_order && $order_status == "completed") {
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
        ?>

        <div id="month-tabs">
            <ul>
                <?php 
                foreach (array_keys($grouped_by_month) as $month_year) {
                    $tab_status = isset($monthly_payment_orders[$month_year])
                    ? ' (' . esc_html(wc_get_order_status_name($monthly_payment_orders[$month_year]->get_status())) . ')'
                    : '';
                    echo '<li><a href="#tab-' . sanitize_title($month_year) . '">' . esc_html($month_year) . $tab_status . '</a></li>';
                }
                ?>
            </ul>
            <?php foreach ($grouped_by_month as $month_year => $data) { ?>
                <div id="tab-<?php echo sanitize_title($month_year); ?>">
                    <h3>Orders for <?php echo $month_year; ?></h3>
                    <div class="order-accordion">
                        <?php foreach ($data['orders'] as $order) { ?>
                            <h4>Order #<?php echo esc_html($order->get_id()); ?></h4>
                            <div>
                                <table class="wp-list-table widefat fixed striped">
                                    <tr>
                                        <th>Order ID</th>
                                        <td><a href="admin.php?page=wc-orders&action=edit&id=<?php echo esc_html($order->get_id()); ?>" target="_blank"><?php echo esc_html($order->get_id());?></a></td>
                                    </tr>
                                    <tr>
                                        <th>Date</th>
                                        <td><?php echo esc_html($order->get_date_created()->date('Y-m-d H:i:s')); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Customer</th>
                                        <td><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo esc_html($order->get_billing_email()); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone</th>
                                        <td><?php esc_html($order->get_billing_phone()) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total</th>
                                        <td><?php echo esc_html($order->get_total()) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td><?php echo esc_html(wc_get_order_status_name($order->get_status())) ?></td>
                                    </tr>
                                </table>
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
                                            <td><?php echo esc_html($item->get_name()); ?></td>
                                            <td><?php echo esc_html($item->get_quantity()); ?></td>
                                            <td><?php echo esc_html(wc_price($item->get_total())) ?></td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                    <div style="margin-top: 10px;">
                        <h3>Total for <?php echo $month_year; ?>: <?php echo wc_price($data['total']); ?></h3>
                    </div>
                </div>
            <?php } ?>
        </div>
        </div><a href="admin.php?page=booking-history" class="button" style="margin-top: 20px;">Back to History Bookings</a>
    </div>
<?php 
    } else {
?>
    <h1>Bookings</h1>
    <?php if (!empty($order_infos)) { ?>
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
                foreach ($order_infos as $customer_id => $data) {
            ?>
            <tr>
                <td><?php echo esc_html($data["customer_name"]) . ' # ' . esc_html($customer_id); ?></td>
                <td>
                <?php
                    $filtered_orders = array_filter($data['orders'], function ($order) {
                        return !$order->get_meta('is_monthly_payment_order');
                    });
    
                    $order_count = count($filtered_orders);
                    echo $order_count;
                ?>
                </td>
                <td><a href="admin.php?page=booking-history&customer_id=<?php echo $customer_id ?>&action=view">View</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <?php } else { echo "No data found"; } ?>
<?php } ?>