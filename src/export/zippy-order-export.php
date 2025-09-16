<?php

namespace Zippy_Booking_Car\Src\Export;

defined('ABSPATH') || exit;

use Dompdf\Dompdf;

class Zippy_Order_Export
{
    protected static $_instance = null;

    public static function get_instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        add_action('woocommerce_before_account_orders', [$this, 'add_export_filter_controls']);
        add_action('template_redirect', [$this, 'handle_order_export']);
        add_filter('woocommerce_my_account_my_orders_query', [$this, 'filter_account_orders_by_date']);
    }

    public function add_export_filter_controls()
    {
        $current_date = isset($_GET['fulfilment_date']) ? esc_attr($_GET['fulfilment_date']) : '';
?>
        <form method="get" class="download-form">
            <?php foreach ($_GET as $key => $value) : ?>
                <?php if ($key !== 'fulfilment_date' && $key !== 'export') : ?>
                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <label>
                <input type="date" name="fulfilment_date" value="<?php echo $current_date; ?>" />
            </label>
            <select name="export" onchange="this.form.submit()" class="button select-download">
                <option value="">Download</option>
                <option value="csv">Export CSV</option>
                <option value="pdf">Export PDF</option>
            </select>

            <button type="submit" class="button filter-btn ">Filter</button>
        </form>
<?php
    }

    private function get_filtered_orders()
    {
        $user_id = get_current_user_id();
        $args = [
            'customer_id' => $user_id,
            'status'      => ['pending', 'on-hold', 'completed'],
            'limit'       => -1,
        ];

        if (!empty($_GET['fulfilment_date'])) {
            $date = sanitize_text_field($_GET['fulfilment_date']);
            $args['date_created'] = $date . ' 00:00:00...' . $date . ' 23:59:59';
        }

        return wc_get_orders($args);
    }

    public function handle_order_export()
    {
        if (!isset($_GET['export']) || !is_account_page()) {
            return;
        }

        $customer_orders = $this->get_filtered_orders();

        // ===== CSV EXPORT =====
        if ($_GET['export'] === 'csv') {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Expires: 0');

            $filename = 'orders-' . date('Y-m-d-H-i-s') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$filename}");

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Order Number', 'Booking Date', 'Type of Service', 'Status', 'Type of Vehicle', 'Total']);

            foreach ($customer_orders as $order) {
                $is_monthly   = $order->get_meta('is_monthly_payment_order');
                $service_type = get_post_meta($order->get_id(), 'service_type', true);

                $product_names = [];
                foreach ($order->get_items() as $item) {
                    $product_names[] = $item->get_name();
                }
                $product_name = implode(', ', $product_names);

                // Debug log
                error_log('===== ORDER DEBUG =====');
                error_log('Order ID: ' . $order->get_id());
                error_log('is_monthly: ' . $is_monthly);
                error_log('service_type: ' . $service_type);
                error_log('product_name: ' . $product_name);

                fputcsv($output, [
                    $order->get_order_number(),
                    $order->get_date_created()->date('d-m-Y'),
                    $service_type,
                    wc_get_order_status_name($order->get_status()),
                    ($is_monthly) ? '' : $product_name,
                    ($is_monthly) ? $order->get_total() : ''
                ]);
            }
            exit;
        }


        // ===== PDF EXPORT =====
        if ($_GET['export'] === 'pdf') {
            if (!class_exists(Dompdf::class)) {
                require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            }
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Expires: 0');

            $dompdf = new Dompdf();

            $html = '<h2>My Orders</h2><table border="1" cellpadding="5" cellspacing="0" width="100%">
    <thead><tr>
        <th>Order Number</th><th>Booking Date</th><th>Type of Service</th>
        <th>Status</th><th>Type of Vehicle</th><th>Total</th>
    </tr></thead><tbody>';

            foreach ($customer_orders as $order) {
                $service_type = get_post_meta($order->get_id(), 'service_type', true);

                $product_names = [];
                foreach ($order->get_items() as $item) {
                    $product_names[] = $item->get_name();
                }
                $product_name = implode(', ', $product_names);

                $html .= '<tr>
    <td>' . $order->get_order_number() . '</td>
    <td>' . $order->get_date_created()->date('d-m-Y') . '</td>
    <td>' . esc_html($service_type) . '</td>
    <td>' . wc_get_order_status_name($order->get_status()) . '</td>
    <td>' . ($order->get_meta('is_monthly_payment_order') ? '' : esc_html($product_name)) . '</td>
    <td>' . ($order->get_meta('is_monthly_payment_order') ? $order->get_total() : '') . '</td>
</tr>';
            }

            $html .= '</tbody></table>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $filename = 'orders-' . date('Y-m-d-H-i-s') . '.pdf';
            $dompdf->stream($filename, ["Attachment" => 1]);
            exit;
        }
    }

    public function filter_account_orders_by_date($args)
    {
        if (!empty($_GET['fulfilment_date'])) {
            $date = sanitize_text_field($_GET['fulfilment_date']);
            $args['date_created'] = $date . ' 00:00:00...' . $date . ' 23:59:59';
        }
        return $args;
    }
}
