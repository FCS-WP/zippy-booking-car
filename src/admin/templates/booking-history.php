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
        <style>
            #month-tabs .wp-list-table a{
                color: #2271b1;
                text-decoration: underline;
            }
        </style>

        <?php
            if (!empty($orders)) {
        ?>

        <div id="month-tabs">
            <ul>
                <?php
                    foreach ($orders as $order) {
                        $order_time_by_moth_year = $order->get_date_created()->format('F Y');
                ?>
                    <li>
                        <a href="#tab-<?php echo sanitize_title($order_time_by_moth_year) ?>">
                            <?php echo "#" . $order->get_meta("_custom_order_number") . " (" . wc_get_order_status_name($order->get_status()) . ")"  ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
            <?php 
                foreach ($orders as $order) { 
                    $order_time_by_moth_year = $order->get_date_created()->format('F Y');
            ?>
            <div id="tab-<?php echo sanitize_title($order_time_by_moth_year) ?>">
                <h3>Orders for <?php echo esc_html($order_time_by_moth_year); ?></h3>
                <div class="order-accordion">
                    <?php
                        if(!empty($child_orders)){ 
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
                    <?php }} ?>
                </div>
                <div style="margin-top: 10px;">
                    <h3>Total for <?php echo $order_time_by_moth_year . ": " . wc_price($order->get_total()) ?></h3>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php } else { echo "No Order Found!"; } ?>
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