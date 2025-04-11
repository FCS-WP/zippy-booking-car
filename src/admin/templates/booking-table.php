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
            <h1>Details for Customer ID: <?php echo esc_html($customer_id) ?> </h1>
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

            <div id="month-tabs">
                <?php
                $completed_months = [];
                $all_completed = true;

                foreach ($grouped_by_month as $month_of_order => $data) {
                    $all_completed_for_month = true;

                    foreach ($data['orders'] as $order) {
                        if ($order->get_status() !== 'completed') {
                            $all_completed_for_month = false;
                            break;
                        }
                    }

                    if ($all_completed_for_month) {
                        $completed_months[] = $month_of_order;
                    } else {
                        $all_completed = false;
                    }
                }

                if (!$all_completed) {
                ?>
                    <div class="not-all-completed">
                        <ul>
                            <?php
                            foreach ($grouped_by_month as $month_of_order => $data) {

                                if (in_array($month_of_order, $completed_months)) {
                                    continue;
                                }
                                $tab_status = '';

                                foreach ($monthly_payment_orders[$month_of_order] as $order) {

                                    $month_of_order_key = $order->get_meta('month_of_order', true);

                                    if ($month_of_order === $month_of_order_key) {
                                        $tab_status = $order->get_status();
                                        break;
                                    }
                                }

                            ?>
                                <li class="<?php echo $tab_status; ?>">
                                    <a href="#tab-<?php echo esc_attr(sanitize_title($month_of_order)); ?>">
                                        <?php
                                        echo esc_html($month_of_order);

                                        if (!empty($tab_status)) {
                                            echo ' (' . wc_get_order_status_name($tab_status) . ')';
                                        }
                                        ?>
                                    </a>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>

                    </div>

                <?php
                } else {
                ?>
                    <div class="all-completed">
                        <h3>All monthly orders have been paid.</h3>
                    </div>
                <?php
                }
                ?>

                <?php
                foreach ($grouped_by_month as $month_of_order => $data) {
                    if (in_array($month_of_order, $completed_months)) {
                        continue;
                    }
                ?>
                    <div id="tab-<?php echo sanitize_title($month_of_order) ?>" class="tab-content">
                        <h3>Orders for <?php echo esc_html($month_of_order) ?></h3>
                        <div class="order-accordion">
                            <?php
                            foreach ($data['orders'] as $order) {
                                $order_id = $order->get_id();
                                $order_link = admin_url("post.php?post=$order_id&action=edit");
                                if ($order->get_status() !== "completed") {;
                            ?>
                                    <h4 class="<?php echo $order->get_status() ?>">
                                        <p class="space_center_title">Order #<?php echo esc_html($order->get_id()) ?> (<?php echo esc_html(wc_get_order_status_name($order->get_status())) ?>)
                                            <a href="<?php echo $order_link; ?>" class='edit_order_btn orange_background_color button view-order-detail-button border-radius-tab'>Edit Order</a>
                                        </p>
                                    </h4>
                                    <div>

                                        <!-- Display order details -->

                                        <table class="wp-list-table widefat fixed striped">
                                            <tr>
                                                <th>Order ID</th>
                                                <td><?php echo esc_html($order->get_id()) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Date</th>
                                                <td><?php echo esc_html($order->get_date_created()->date('Y-m-d H:i:s')) ?></td>
                                            </tr>
                                            <tr>
                                                <th>Customer</th>
                                                <td><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?></td>
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
                                                <th>Pickup Date</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "pick_up_date", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Pickup Time</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "pick_up_time", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Pickup Location</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "pick_up_location", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Drop Off Location</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "drop_off_location", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>No. of Passengers</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "no_of_passengers", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>No. of Baggage</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "no_of_passengers", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Additional Stop</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "additional_stop", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Midnight Fee</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "midnight_fee", true); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Special Requests</th>
                                                <td>
                                                    <?php echo get_post_meta($order->get_id(), "special_requests", true); ?>
                                                </td>
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
                                                    <th>Car</th>
                                                    <th>Time</th>
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
                            }

                            ?>
                        </div> <!-- End order accordion -->

                        <div style="margin-top: 10px;">
                            <h3>Total for <?php echo esc_html($month_of_order); ?>: <?php echo wc_price($data['total']); ?></h3>
                        </div>


                        <?php
                        $all_pending = true;

                        foreach ($data['orders'] as $order) {
                            if ($order->get_status() !== 'pending' && $order->get_status() !== 'processing' && $order->get_status() !== 'on-hold') {
                                $all_pending = false;
                                break;
                            }
                        }
                        $button_disabled = $all_pending ? 'disabled' : '';
                        ?>

                        <button class="button create-order-button"
                            data-customer-id="<?php echo esc_attr($customer_id); ?>"
                            data-month-of-order="<?php echo esc_attr($month_of_order); ?>"
                            <?php echo $button_disabled; ?>>Create order for this month</button>

                        <?php
                        if (!empty($monthly_payment_orders[$month_of_order])) {
                            foreach ($monthly_payment_orders[$month_of_order] as $order) {
                                $month_of_order_key = $order->get_meta('month_of_order', true);

                                if ($month_of_order === $month_of_order_key) {
                                    $status = $order->get_status();

                                    if ($status === 'pending' || $status === 'processing') {
                                        $order_id = $order->get_id();
                                        $order_of_months[$status][] = $order;
                                        $summary_order_urls[$order_id] = admin_url('post.php?post=' . $order_id . '&action=edit');
                                    }
                                }
                            }
                        }
                        ?>

                        <?php if (!empty($order_of_months)): ?>
                            <?php if (!empty($order_of_months['pending'])): ?>
                                <?php foreach ($order_of_months['pending'] as $pending_order): ?>
                                    <a href="<?php echo esc_url($summary_order_urls[$pending_order->get_id()]); ?>" class="button view-order-detail-button">View order of month (Pending)</a>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($order_of_months['processing'])): ?>
                                <?php foreach ($order_of_months['processing'] as $processing_order): ?>
                                    <a href="<?php echo esc_url($summary_order_urls[$processing_order->get_id()]); ?>" class="button view-order-detail-button">View order of month (Processing)</a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>


                    </div> <!-- End tab content for the current month -->
                <?php
                }
                ?>
            </div> <!-- End tabs container -->

            <a href="<?php echo esc_url(admin_url('admin.php?page=zippy-bookings')) ?>" class="button back-to-bookings" style="margin-top: 20px;">Back to Bookings</a>
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