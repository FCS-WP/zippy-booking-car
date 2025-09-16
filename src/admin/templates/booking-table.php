<?php
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['customer_id'])) {
    $customer_id = sanitize_text_field($_GET['customer_id']);
    $args = array(
        'limit' => -1,
        'customer_id' => $customer_id,
    );
    $orders = wc_get_orders($args);

    if (!empty($orders)) { ?>
        <div class="wrap">
            <h1>Details for Customer: <span class="text-capitalize"> <?php echo $orders[0]->get_billing_first_name() . ' ' . $orders[0]->get_billing_last_name(); ?> </span> </h1>
            <?php
            $grouped_by_month = array();
            $monthly_payment_orders = array();
            $summary_orders = [];

            foreach ($orders as $order) {
                $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);
                $order_date = $order->get_date_created();
                $month_of_order = $is_monthly_payment_order
                    ? $order->get_meta('month_of_order', true)
                    : $order_date->format('F Y');

                if ($is_monthly_payment_order) {
                    $summary_order_number = $order->get_meta('summary_order_number', true);

                    if ($summary_order_number) {
                        $summary_orders[$month_of_order][] = intval($summary_order_number);
                    }
                    $monthly_payment_orders[$month_of_order][] = $order;
                    continue;
                }

                if (!isset($grouped_by_month[$month_of_order])) {
                    $grouped_by_month[$month_of_order] = array(
                        'orders' => array(),
                        'summary_orders' => array(),
                        'total' => 0,
                    );
                }


                $grouped_by_month[$month_of_order]['orders'][] = $order;

                $summary_order_number = $order->get_meta('summary_order_number', true);
                if ($summary_order_number) {
                    $grouped_by_month[$month_of_order]['summary_orders'][] = intval($summary_order_number);
                }

                if ($order->get_status() === 'confirmed') {
                    $grouped_by_month[$month_of_order]['total'] += $order->get_total();
                }

                usort($grouped_by_month[$month_of_order]['orders'], function ($a, $b) {
                    return $a->get_id() - $b->get_id();
                });
            }

            $GLOBALS['summary_orders'] = $summary_orders;

            ?>
            <div id="order-filter-container">
                <label for="month-filter">Filter by Month:</label>
                <select id="month-filter">
                    <?php
                    $months = array_keys($grouped_by_month);
                    $latest_month_slug = sanitize_title($months[0]);

                    foreach ($grouped_by_month as $month => $data):
                        $selected = (sanitize_title($month) === $latest_month_slug) ? 'selected' : '';
                    ?>
                        <option value="<?php echo esc_attr(sanitize_title($month)); ?>" <?php echo $selected; ?>>
                            <?php echo esc_html($month); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="order-number-filter" style="margin-left: 10px;">Order ID:</label>
                <input type="text" id="order-number-filter" placeholder="e.g. 1234">

                <label for="booking-date-filter" style="margin-left: 10px;">Booking Date:</label>
                <input type="date" id="booking-date-filter">

                <label for="vehicle-type-filter" style="margin-left: 10px;">Vehicle Type:</label>
                <select id="vehicle-type-filter">
                    <option value="">All</option>
                    <?php
                    $vehicle_products = wc_get_products([
                        'limit' => -1,
                        'status' => 'publish',
                    ]);

                    if (!empty($vehicle_products)) {
                        foreach ($vehicle_products as $product) {
                            echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                        }
                    }
                    ?>
                </select>



                <label for="status-filter" style="margin-left: 10px;">Order Status:</label>
                <select id="status-filter">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="on-hold">On Hold</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>

                </select>
                <button id="apply-filters-button" class="button" style="margin-left: 10px;">Filter</button>

            </div>



            <table class="wp-list-table widefat fixed striped booking-table" id="orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                        <th>Total</th>

                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grouped_by_month as $month => $data):?>
                        <?php foreach ($data['orders'] as $order):
                        ?>
                            <?php if ($order->get_status() === 'completed') continue; ?>
                            <?php
                            $product_ids = [];
                            $billing_full_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();

                            foreach ($order->get_items() as $item) {
                                $product = $item->get_product();
                                if ($product) {
                                    $product_ids = $product->get_id();
                                    $product_name = $product->get_name();
                                }
                            }
                            ?>

                            <tr
                                data-month="<?php echo esc_attr(sanitize_title($month)); ?>"
                                data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                data-booking-date="<?php echo esc_attr($order->get_date_created()->format('Y-m-d')); ?>"
                                data-vehicle-type="<?php echo esc_attr($product_ids); ?>"
                                data-status="<?php echo esc_attr($order->get_status()); ?>">


                                <td class="booking-name"><a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>"><?php echo  'Order #' . esc_html($order->get_id()) . ' - ' . esc_html($billing_full_name); ?></a></td>
                                <td class="booking-date"><?php echo esc_html($order->get_date_created()->date('F j, Y')); ?></td>
                                <td class="bookings_status column-order_status">
                                    <span class="booking-status status-<?php echo esc_attr($order->get_status()); ?> tips">
                                        <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                    </span>
                                </td>
                                <td class="booking-total"><?php echo wc_price($order->get_total()); ?></td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                </tbody>
            </table>
            <?php foreach ($grouped_by_month as $month_of_order => $data): ?>
                <?php
                $sanitized_month = sanitize_title($month_of_order);

                $all_pending = true;
                foreach ($data['orders'] as $order) {
                    if (!in_array($order->get_status(), ['pending', 'processing'])) {
                        $all_pending = false;
                        break;
                    }
                }
                $button_disabled = $all_pending ? 'disabled' : '';

                $order_of_months = [];
                $summary_order_urls = [];

                if (!empty($monthly_payment_orders[$month_of_order])) {
                    foreach ($monthly_payment_orders[$month_of_order] as $order) {
                        $month_of_order_key = $order->get_meta('month_of_order', true);
                        if ($month_of_order === $month_of_order_key) {
                            $status = $order->get_status();
                            if (in_array($status, ['pending', 'processing'])) {
                                $order_id = $order->get_id();
                                $order_of_months[$status][] = $order;
                                $summary_order_urls[$order_id] = admin_url('post.php?post=' . $order_id . '&action=edit');
                            }
                        }
                    }
                }
                ?>

                <div class="create-order-container" data-month="<?php echo esc_attr($sanitized_month); ?>" style="display: none; margin-top: 30px;">
                    <h3>Total for <?php echo esc_html($month_of_order); ?>: <?php echo wc_price($data['total']); ?></h3>

                    <button class="button create-order-button"
                        data-customer-id="<?php echo esc_attr($customer_id); ?>"
                        data-month-of-order="<?php echo esc_attr($month_of_order); ?>"
                        <?php echo $button_disabled; ?>>
                        Create order for <?php echo esc_html($month_of_order); ?>
                    </button>

                    <?php if (!empty($order_of_months)): ?>
                        <?php if (!empty($order_of_months['pending'])): ?>
                            <?php foreach ($order_of_months['pending'] as $pending_order): ?>
                                <a href="<?php echo esc_url($summary_order_urls[$pending_order->get_id()]); ?>"
                                    class="button view-order-detail-button"
                                    data-month="<?php echo esc_attr($sanitized_month); ?>"
                                    style="display: none;">
                                    View order of month (Pending)
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($order_of_months['processing'])): ?>
                            <?php foreach ($order_of_months['processing'] as $processing_order): ?>
                                <a href="<?php echo esc_url($summary_order_urls[$processing_order->get_id()]); ?>"
                                    class="button view-order-detail-button"
                                    data-month="<?php echo esc_attr($sanitized_month); ?>"
                                    style="display: none;">
                                    View order of month (Processing)
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>



            <div> <a href="<?php echo esc_url(admin_url('admin.php?page=zippy-bookings')) ?>" class="button back-to-bookings" style="margin-top: 20px;">Back to Bookings</a></div>
            <?php if ($all_completed) { ?>
                <a class="button go-to-history" style="margin-top: 20px;" href='<?php echo esc_url(admin_url('admin.php?page=booking-history')) ?>'>Go to History</a>
            <?php } ?>
        </div>
    <?php
    } else {
    ?>
        <div class="wrap">
            <h1>No Orders Found for Customer ID: <?php echo esc_html($customer_id) ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=zippy-bookings')) ?>" class="button back-to-bookings" style="margin-top: 20px;">Back to Bookings</a>
        </div>
    <?php
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
        $customer_id = $order->get_user_id();

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

    ksort($grouped_orders);
    ?>
    <div class="wrap">
        <h1>Bookings</h1>
        <table class="wp-list-table widefat fixed striped table-customers">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th style="width: 15%; text-align: center;">Number of orders per month</th>
                    <th style="width: 15%; text-align: center;">Number of orders to be paid</th>
                    <th style="width: 10%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($grouped_orders)) {
                    foreach ($grouped_orders as $customer_id => $data) {
                        $customer_name = $data['customer_name'];
                        $months_grouped = array();

                        foreach ($data['orders'] as $order) {
                            if ($order->get_status() === 'completed') {
                                continue;
                            }
                            $is_monthly_payment_order = $order->get_meta('is_monthly_payment_order', true);
                            $month_of_order = $is_monthly_payment_order
                                ? $order->get_meta('month_of_order', true)
                                : $order->get_date_created()->format('F Y');

                            if (!in_array($month_of_order, $months_grouped)) {
                                $months_grouped[] = $month_of_order;
                            }
                        }
                        $months_count = count($months_grouped);

                        $filtered_orders = array_filter($data['orders'], function ($order) {
                            return $order->get_meta('is_monthly_payment_order') && $order->get_status() !== 'completed';
                        });
                        $order_count = count($filtered_orders);
                ?>
                        <tr>
                            <td class="customer-name"><a href="<?php echo esc_url(admin_url('admin.php?page=zippy-bookings&customer_id=' . $customer_id . '&action=view')); ?>">#<?php echo esc_html($customer_id) . " " . esc_html($customer_name); ?></a></td>
                            <td class="months-grouped" style="text-align: center;"><?php echo esc_html($months_count); ?></td>
                            <td class="order-count" style="text-align: center;"><?php echo esc_html($order_count); ?></td>
                            <td class="action" style="text-align: center;"><a href="<?php echo esc_url(admin_url('admin.php?page=zippy-bookings&customer_id=' . $customer_id . '&action=view')); ?>">View</a></td>
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
?>