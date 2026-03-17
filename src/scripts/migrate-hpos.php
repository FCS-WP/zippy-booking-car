<?php
/**
 * Standalone Script: Migrate ALL Zippy Booking Meta to Order Metadata (HPOS Ready)
 * 
 * New Location: src/scripts/migrate-hpos.php
 * Usage: your-domain.com/wp-content/plugins/zippy-booking-car/src/scripts/migrate-hpos.php
 */

// 1. Load WordPress (Deep link: 6 levels up from src/scripts/ to WP Root)
$wp_load_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('wp-load.php not found. Current path: ' . __FILE__);
}
require_once($wp_load_path);

// 2. Security Check
if (!current_user_can('manage_options')) {
    die('Unauthorized access.');
}

if (!function_exists('wc_get_orders')) {
    die('WooCommerce is not active.');
}

echo "<h1>🚀 Starting COMPREHENSIVE Zippy HPOS Migration</h1>";
echo "<p>Moving all booking details to Official Order Metadata...</p>";

// 3. Full list of keys to migrate
$keys_to_migrate = [
    'no_of_passengers',
    'no_of_baggage',
    'service_type',
    'flight_details',
    'eta_time',
    'key_member',
    'pick_up_date',
    'pick_up_time',
    'pick_up_location',
    'drop_off_location',
    'special_requests',
    'staff_name',
    'car_id',
    'is_monthly_payment_order',
    'member_type'
];

$orders = wc_get_orders(['limit' => -1]);
$total_orders = 0;
$total_meta_moved = 0;

foreach ($orders as $order) {
    $order_id = $order->get_id();
    $has_changes = false;

    foreach ($keys_to_migrate as $key) {
        $old_val = get_post_meta($order_id, $key, true);
        
        if ($old_val !== '') {
            if ($key === 'pick_up_date') {
                if (strpos($old_val, '-') !== false && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $old_val)) {
                    $date_obj = \DateTime::createFromFormat('d-m-Y', $old_val);
                    if ($date_obj) {
                        $old_val = $date_obj->format('Y-m-d');
                    }
                }
            }

            $order->update_meta_data($key, $old_val);
            delete_post_meta($order_id, $key);
            
            $total_meta_moved++;
            $has_changes = true;
        }
    }

    if ($has_changes) {
        $order->save();
        $total_orders++;
        echo "Order #$order_id: Data migrated successfully.<br>";
    }
}

echo "<h2>✅ Complete!</h2>";
echo "<ul>";
echo "<li>Orders processed: <strong>$total_orders</strong></li>";
echo "<li>Total entries moved: <strong>$total_meta_moved</strong></li>";
echo "</ul>";
