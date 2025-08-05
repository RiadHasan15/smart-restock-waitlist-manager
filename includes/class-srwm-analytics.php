<?php
/**
 * Analytics Class
 * 
 * Handles waitlist and restock analytics, reporting, and data export.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Analytics {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Analytics functionality is loaded automatically
    }
    
    /**
     * Get comprehensive analytics data
     */
    public function get_analytics_data() {
        return array(
            'total_restocks' => $this->get_total_restocks(),
            'avg_waitlist_size' => $this->get_average_waitlist_size(),
            'avg_restock_time' => $this->get_average_restock_time(),
            'top_products' => $this->get_top_products_by_demand(),
            'supplier_performance' => $this->get_supplier_performance(),
            'monthly_stats' => $this->get_monthly_statistics(),
            'conversion_rate' => $this->get_conversion_rate()
        );
    }
    
    /**
     * Get total number of restocks
     */
    public function get_total_restocks() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    /**
     * Get average waitlist size
     */
    public function get_average_waitlist_size() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $result = $wpdb->get_var(
            "SELECT AVG(waitlist_count) FROM (
                SELECT product_id, COUNT(*) as waitlist_count 
                FROM $table 
                GROUP BY product_id
            ) as subquery"
        );
        
        return round($result, 1);
    }
    
    /**
     * Get average restock time (days from first waitlist to restock)
     */
    public function get_average_restock_time() {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        $result = $wpdb->get_var(
            "SELECT AVG(DATEDIFF(rl.timestamp, wl.first_waitlist_date)) as avg_days
             FROM (
                 SELECT product_id, MIN(date_added) as first_waitlist_date
                 FROM $waitlist_table
                 GROUP BY product_id
             ) wl
             JOIN $logs_table rl ON wl.product_id = rl.product_id"
        );
        
        return round($result, 1);
    }
    
    /**
     * Get top products by demand
     */
    public function get_top_products_by_demand($limit = 10) {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as product_id, p.post_title as name,
                    COUNT(DISTINCT w.id) as waitlist_count,
                    COUNT(rl.id) as restock_count,
                    AVG(DATEDIFF(rl.timestamp, w.first_waitlist_date)) as avg_restock_time
             FROM {$wpdb->posts} p
             LEFT JOIN (
                 SELECT product_id, id, MIN(date_added) as first_waitlist_date
                 FROM $waitlist_table
                 GROUP BY product_id
             ) w ON p.ID = w.product_id
             LEFT JOIN $logs_table rl ON p.ID = rl.product_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             GROUP BY p.ID
             HAVING waitlist_count > 0
             ORDER BY waitlist_count DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Get supplier performance data
     */
    public function get_supplier_performance() {
        global $wpdb;
        
        $supplier_table = $wpdb->prefix . 'srwm_suppliers';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_results(
            "SELECT s.supplier_name, s.supplier_email,
                    COUNT(DISTINCT s.product_id) as products_managed,
                    COUNT(rl.id) as total_restocks,
                    AVG(rl.quantity) as avg_restock_quantity,
                    MAX(rl.timestamp) as last_restock
             FROM $supplier_table s
             LEFT JOIN $logs_table rl ON s.product_id = rl.product_id
             GROUP BY s.supplier_email
             ORDER BY total_restocks DESC"
        );
    }
    
    /**
     * Get monthly statistics
     */
    public function get_monthly_statistics($months = 12) {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(date_added, '%%Y-%%m') as month,
                COUNT(*) as new_waitlist_customers,
                COUNT(DISTINCT product_id) as products_with_waitlists
             FROM $waitlist_table
             WHERE date_added >= DATE_SUB(NOW(), INTERVAL %d MONTH)
             GROUP BY DATE_FORMAT(date_added, '%%Y-%%m')
             ORDER BY month DESC",
            $months
        ));
    }
    
    /**
     * Get conversion rate (waitlist customers who purchased)
     */
    public function get_conversion_rate() {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $orders_table = $wpdb->prefix . 'wc_order_stats';
        $order_items_table = $wpdb->prefix . 'wc_order_product_lookup';
        
        // Get total waitlist customers
        $total_waitlist = $wpdb->get_var("SELECT COUNT(DISTINCT customer_email) FROM $waitlist_table");
        
        if ($total_waitlist == 0) {
            return 0;
        }
        
        // Get waitlist customers who made purchases
        $converted_customers = $wpdb->get_var(
            "SELECT COUNT(DISTINCT w.customer_email)
             FROM $waitlist_table w
             JOIN $order_items_table oi ON w.product_id = oi.product_id
             JOIN $orders_table o ON oi.order_id = o.order_id
             WHERE o.status = 'wc-completed'
             AND o.date_created_gmt >= w.date_added"
        );
        
        return round(($converted_customers / $total_waitlist) * 100, 2);
    }
    
    /**
     * Get waitlist growth trend
     */
    public function get_waitlist_growth_trend($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_added) as date,
                    COUNT(*) as new_customers,
                    COUNT(DISTINCT product_id) as active_products
             FROM $table
             WHERE date_added >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(date_added)
             ORDER BY date ASC",
            $days
        ));
    }
    
    /**
     * Get restock frequency by product
     */
    public function get_restock_frequency($product_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $where_clause = '';
        if ($product_id) {
            $where_clause = $wpdb->prepare("WHERE product_id = %d", $product_id);
        }
        
        return $wpdb->get_results(
            "SELECT product_id,
                    COUNT(*) as restock_count,
                    AVG(quantity) as avg_quantity,
                    MIN(timestamp) as first_restock,
                    MAX(timestamp) as last_restock,
                    DATEDIFF(MAX(timestamp), MIN(timestamp)) as days_between
             FROM $table
             $where_clause
             GROUP BY product_id
             ORDER BY restock_count DESC"
        );
    }
    
    /**
     * Export analytics data to CSV
     */
    public function export_analytics_data($type = 'all') {
        $filename = 'srwm-analytics-' . $type . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($type) {
            case 'waitlist':
                $this->export_waitlist_data($output);
                break;
            case 'restocks':
                $this->export_restock_data($output);
                break;
            case 'suppliers':
                $this->export_supplier_data($output);
                break;
            case 'products':
                $this->export_product_data($output);
                break;
            default:
                $this->export_all_data($output);
                break;
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export waitlist data
     */
    private function export_waitlist_data($output) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        // Headers
        fputcsv($output, array(
            __('Product ID', 'smart-restock-waitlist'),
            __('Product Name', 'smart-restock-waitlist'),
            __('Customer Name', 'smart-restock-waitlist'),
            __('Customer Email', 'smart-restock-waitlist'),
            __('Date Added', 'smart-restock-waitlist'),
            __('Notified', 'smart-restock-waitlist')
        ));
        
        // Data
        $results = $wpdb->get_results(
            "SELECT w.*, p.post_title as product_name
             FROM $table w
             JOIN {$wpdb->posts} p ON w.product_id = p.ID
             ORDER BY w.date_added DESC"
        );
        
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->product_id,
                $row->product_name,
                $row->customer_name,
                $row->customer_email,
                $row->date_added,
                $row->notified ? __('Yes', 'smart-restock-waitlist') : __('No', 'smart-restock-waitlist')
            ));
        }
    }
    
    /**
     * Export restock data
     */
    private function export_restock_data($output) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        // Headers
        fputcsv($output, array(
            __('Product ID', 'smart-restock-waitlist'),
            __('Product Name', 'smart-restock-waitlist'),
            __('Quantity', 'smart-restock-waitlist'),
            __('Method', 'smart-restock-waitlist'),
            __('IP Address', 'smart-restock-waitlist'),
            __('Timestamp', 'smart-restock-waitlist')
        ));
        
        // Data
        $results = $wpdb->get_results(
            "SELECT rl.*, p.post_title as product_name
             FROM $table rl
             JOIN {$wpdb->posts} p ON rl.product_id = p.ID
             ORDER BY rl.timestamp DESC"
        );
        
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->product_id,
                $row->product_name,
                $row->quantity,
                $row->method,
                $row->ip_address,
                $row->timestamp
            ));
        }
    }
    
    /**
     * Export supplier data
     */
    private function export_supplier_data($output) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        // Headers
        fputcsv($output, array(
            __('Product ID', 'smart-restock-waitlist'),
            __('Product Name', 'smart-restock-waitlist'),
            __('Supplier Name', 'smart-restock-waitlist'),
            __('Supplier Email', 'smart-restock-waitlist'),
            __('Threshold', 'smart-restock-waitlist'),
            __('Notification Channels', 'smart-restock-waitlist')
        ));
        
        // Data
        $results = $wpdb->get_results(
            "SELECT s.*, p.post_title as product_name
             FROM $table s
             JOIN {$wpdb->posts} p ON s.product_id = p.ID
             ORDER BY p.post_title ASC"
        );
        
        foreach ($results as $row) {
            $channels = maybe_unserialize($row->notification_channels);
            $channels_text = is_array($channels) ? implode(', ', array_keys($channels)) : '';
            
            fputcsv($output, array(
                $row->product_id,
                $row->product_name,
                $row->supplier_name,
                $row->supplier_email,
                $row->threshold,
                $channels_text
            ));
        }
    }
    
    /**
     * Export product data
     */
    private function export_product_data($output) {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        // Headers
        fputcsv($output, array(
            __('Product ID', 'smart-restock-waitlist'),
            __('Product Name', 'smart-restock-waitlist'),
            __('SKU', 'smart-restock-waitlist'),
            __('Current Stock', 'smart-restock-waitlist'),
            __('Waitlist Count', 'smart-restock-waitlist'),
            __('Restock Count', 'smart-restock-waitlist'),
            __('Avg. Restock Time (Days)', 'smart-restock-waitlist')
        ));
        
        // Data
        $results = $wpdb->get_results(
            "SELECT p.ID as product_id, p.post_title as name, pm.meta_value as sku,
                    wc.stock_quantity as stock,
                    COUNT(DISTINCT w.id) as waitlist_count,
                    COUNT(rl.id) as restock_count,
                    AVG(DATEDIFF(rl.timestamp, w.first_waitlist_date)) as avg_restock_time
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
             LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wc ON p.ID = wc.product_id
             LEFT JOIN (
                 SELECT product_id, id, MIN(date_added) as first_waitlist_date
                 FROM $waitlist_table
                 GROUP BY product_id
             ) w ON p.ID = w.product_id
             LEFT JOIN $logs_table rl ON p.ID = rl.product_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             GROUP BY p.ID
             ORDER BY waitlist_count DESC"
        );
        
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->product_id,
                $row->name,
                $row->sku,
                $row->stock,
                $row->waitlist_count,
                $row->restock_count,
                round($row->avg_restock_time, 1)
            ));
        }
    }
    
    /**
     * Export all data
     */
    private function export_all_data($output) {
        // Export all data types
        fputcsv($output, array(__('=== WAITLIST DATA ===', 'smart-restock-waitlist')));
        $this->export_waitlist_data($output);
        
        fputcsv($output, array(''));
        fputcsv($output, array(__('=== RESTOCK DATA ===', 'smart-restock-waitlist')));
        $this->export_restock_data($output);
        
        fputcsv($output, array(''));
        fputcsv($output, array(__('=== SUPPLIER DATA ===', 'smart-restock-waitlist')));
        $this->export_supplier_data($output);
        
        fputcsv($output, array(''));
        fputcsv($output, array(__('=== PRODUCT ANALYTICS ===', 'smart-restock-waitlist')));
        $this->export_product_data($output);
    }
}