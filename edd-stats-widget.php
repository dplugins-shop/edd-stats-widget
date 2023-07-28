<?php
/**
 * Plugin Name: EDD Stats per Download per Months
 * Description: Adds custom dashboard widgets to display earnings per download with dates.
 * Version: 1.0
 * Author: Marko Krstic
 * Author URI: DPlugins
 * Text Domain: custom-edd-dashboard-widget
 */

function display_earnings_and_refunds_dashboard_widgets() {
    // Check if the current user has the 'manage_options' capability (Administrator)
    if (current_user_can('manage_options')) {
        global $wpdb;

        // Get the current date
        $current_date = current_time('Y-m-d');

        // Calculate the date 3 months ago from the current date
        $three_months_ago = date('Y-m-d', strtotime('-3 months', strtotime($current_date)));

        $query = "
        SELECT
            oi.product_name AS download_name,
            DATE_FORMAT(o.date_created, '%Y-%m') AS month,
            SUM(oi.total) AS total,
            SUM(CASE WHEN o.status = 'edd_subscription' THEN oi.total ELSE 0 END) AS edd_subscription,
            SUM(CASE WHEN o.status = 'refunded' THEN oi.total ELSE 0 END) AS refunded
        FROM
            {$wpdb->prefix}edd_orders o
        JOIN
            {$wpdb->prefix}edd_order_items oi ON o.id = oi.order_id
        WHERE
            oi.type = 'download'
            AND o.status IN ('complete', 'edd_subscription', 'refunded')
            AND o.date_created >= '{$three_months_ago}'
        GROUP BY
            oi.product_name, month
        ORDER BY
            month DESC;
    ";
    

        $results = $wpdb->get_results($query);

        // Group the results by month
        $grouped_results = array();
        foreach ($results as $row) {
            $grouped_results[$row->month][] = $row;
        }

        // Create separate widgets for each month's data
        foreach ($grouped_results as $month => $month_data) {
            $widget_id = 'edd_earnings_and_refunds_dashboard_widget_' . str_replace('-', '_', $month);
            $widget_title = 'EDD - ' . date('F Y', strtotime($month));

            wp_add_dashboard_widget(
                $widget_id,
                $widget_title,
                function () use ($month_data) {
                    display_earnings_and_refunds_widget_content($month_data);
                }
            );
        }
    }
}

function display_earnings_and_refunds_widget_content($data) {
    ?>
    <table class="edd-stats">
        <tr>
            <th class="download-name">Download Name</th>
            <th class="total">Total</th>
            <th class="edd_subscription">Subscription</th>
            <th class="refunded">Refunded</th>
        </tr>
        <?php foreach ($data as $row) { ?>
            <tr>
                <td><?php echo $row->download_name; ?></td>
                <?php
                $total_with_refunded = $row->total + $row->refunded;
                $total_to_display = abs($total_with_refunded);
                $is_refund_only = $total_with_refunded < 0;
                ?>
                <td>
                    <?php if ($is_refund_only) : ?>
                        - $<?php echo number_format($total_to_display, 2); ?>
                    <?php else : ?>
                        $<?php echo number_format($total_to_display, 2); ?>
                    <?php endif; ?>
                </td>
                <td>$<?php echo number_format(abs($row->edd_subscription), 2); ?></td>
                <td>$<?php echo number_format(abs($row->refunded), 2); ?></td>
            </tr>
        <?php } ?>
    </table>
    <button class="copy-table-button">Copy Table</button>
    <style>
        .edd-stats {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid lightgray;
        }

        .edd-stats th {
            text-align: left;
        }

        .download-name {
            width: 100%;
        }

        .total, .edd_subscription, .refunded {
            padding-right: 20px;
        }
    </style>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var copyTableButtons = document.querySelectorAll(".copy-table-button");
        copyTableButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                var table = button.previousElementSibling;
                var range = document.createRange();
                range.selectNode(table);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand("copy");
                window.getSelection().removeAllRanges();
                
                // Change the button text to "Copied" and revert after 3 seconds
                var originalButtonText = button.textContent;
                button.textContent = "Copied";
                setTimeout(function () {
                    button.textContent = originalButtonText;
                }, 3000);
            });
        });
    });
</script>


    <?php
}




add_action('wp_dashboard_setup', 'display_earnings_and_refunds_dashboard_widgets');
