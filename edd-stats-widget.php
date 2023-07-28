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
                SUM(CASE WHEN o.status = 'complete' THEN oi.total ELSE 0 END) AS earnings,
                SUM(CASE WHEN o.status = 'refunded' THEN -oi.total ELSE 0 END) AS refunds,
                COUNT(CASE WHEN o.status = 'complete' THEN oi.id ELSE NULL END) AS total_downloads
            FROM
                {$wpdb->prefix}edd_orders o
            JOIN
                {$wpdb->prefix}edd_order_items oi ON o.id = oi.order_id
            WHERE
                oi.type = 'download'
                AND o.status IN ('complete', 'refunded')
                AND o.date_created >= '{$three_months_ago}'
            GROUP BY
                oi.product_name, month
            ORDER BY
                month DESC, total_downloads DESC;
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
            $widget_title = 'EDD Earnings and Refunds - ' . date('F Y', strtotime($month));

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
            <th class="download-earnings">Earnings</th>
            <th class="download-refunds">Refunds</th>
        </tr>
        <?php foreach ($data as $row) { ?>
            <tr>
                <td><?php echo $row->download_name; ?></td>
                <td>$<?php echo number_format($row->earnings, 2); ?></td>
                <td>$<?php echo number_format($row->refunds, 2); ?></td>
            </tr>
        <?php } ?>
    </table>
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

        .download-earnings {
            padding-right: 20px;
        }
    </style>
    <?php
}

add_action('wp_dashboard_setup', 'display_earnings_and_refunds_dashboard_widgets');
