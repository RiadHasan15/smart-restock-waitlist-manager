<?php
/**
 * Analytics Class
 * 
 * Handles analytics and reporting for waitlist and restock activities.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Analytics {
    
    private static $instance = null;
    private $license_manager;
    
    public static function get_instance($license_manager = null) {
        if (null === self::$instance) {
            self::$instance = new self($license_manager);
        }
        return self::$instance;
    }
    
    private function __construct($license_manager = null) {
        $this->license_manager = $license_manager;
    }
    
    /**
     * Get comprehensive analytics data
     */
    public function get_analytics_data() {
        try {
            return array(
                'total_restocks' => $this->get_total_restocks(),
                'avg_waitlist_size' => $this->get_average_waitlist_size(),
                'avg_restock_time' => $this->get_average_restock_time(),
                'top_products' => $this->get_top_products_by_demand(),
                'supplier_performance' => $this->get_supplier_performance(),
                'monthly_stats' => $this->get_monthly_statistics(),
                'conversion_rate' => $this->get_conversion_rate()
            );
        } catch (Exception $e) {
            // Return default values if there's an error
            error_log('Analytics data error: ' . $e->getMessage());
            return array(
                'total_restocks' => 0,
                'avg_waitlist_size' => 0,
                'avg_restock_time' => 0,
                'top_products' => array(),
                'supplier_performance' => array(),
                'monthly_stats' => array(),
                'conversion_rate' => 0
            );
        }
    }
    
    /**
     * Get total number of restocks
     */
    public function get_total_restocks() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return 0;
        }
        
        $result = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        return $result ?: 0;
    }
    
    /**
     * Get average waitlist size
     */
    public function get_average_waitlist_size() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return 0;
        }
        
        $result = $wpdb->get_var(
            "SELECT AVG(waitlist_count) FROM (
                SELECT COUNT(*) as waitlist_count 
                FROM $table 
                GROUP BY product_id
            ) as subquery"
        );
        
        return round($result ?: 0, 1);
    }
    
    /**
     * Get average restock time (days from first waitlist to restock)
     */
    public function get_average_restock_time() {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        $result = $wpdb->get_var(
            "SELECT AVG(DATEDIFF(r.timestamp, w.date_added)) as avg_days
             FROM $logs_table r
             JOIN $waitlist_table w ON r.product_id = w.product_id
             WHERE w.date_added <= r.timestamp
             AND w.notified = 1"
        );
        
        return round($result ?: 0, 1);
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
                    COUNT(r.id) as restock_count,
                    AVG(DATEDIFF(r.timestamp, w.date_added)) as avg_restock_time
             FROM {$wpdb->posts} p
             LEFT JOIN $waitlist_table w ON p.ID = w.product_id
             LEFT JOIN $logs_table r ON p.ID = r.product_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             GROUP BY p.ID
             HAVING waitlist_count > 0
             ORDER BY waitlist_count DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);
    }
    
    /**
     * Get supplier performance (Pro feature)
     */
    public function get_supplier_performance() {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return array();
        }
        
        global $wpdb;
        
        $suppliers_table = $wpdb->prefix . 'srwm_suppliers';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_results(
            "SELECT s.supplier_name, s.supplier_email,
                    COUNT(DISTINCT s.product_id) as products_managed,
                    COUNT(r.id) as total_restocks,
                    AVG(r.quantity) as avg_restock_quantity,
                    AVG(DATEDIFF(r.timestamp, s.created_at)) as avg_response_time
             FROM $suppliers_table s
             LEFT JOIN $logs_table r ON s.product_id = r.product_id
             GROUP BY s.supplier_email
             ORDER BY total_restocks DESC"
        , ARRAY_A);
    }
    
    /**
     * Get monthly statistics
     */
    public function get_monthly_statistics($months = 6) {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        $results = array();
        
        for ($i = 0; $i < $months; $i++) {
            $date = date('Y-m', strtotime("-$i months"));
            $start_date = date('Y-m-01', strtotime("-$i months"));
            $end_date = date('Y-m-t', strtotime("-$i months"));
            
            $waitlist_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $waitlist_table 
                 WHERE DATE(date_added) BETWEEN %s AND %s",
                $start_date,
                $end_date
            ));
            
            $restock_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $logs_table 
                 WHERE DATE(timestamp) BETWEEN %s AND %s",
                $start_date,
                $end_date
            ));
            
            $results[] = array(
                'month' => $date,
                'waitlist_count' => $waitlist_count,
                'restock_count' => $restock_count
            );
        }
        
        return array_reverse($results);
    }
    
    /**
     * Get conversion rate (Pro feature)
     */
    public function get_conversion_rate() {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return 0;
        }
        
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        
        $total_waitlist = $wpdb->get_var("SELECT COUNT(*) FROM $waitlist_table");
        $notified_waitlist = $wpdb->get_var("SELECT COUNT(*) FROM $waitlist_table WHERE notified = 1");
        
        if ($total_waitlist == 0) {
            return 0;
        }
        
        return round(($notified_waitlist / $total_waitlist) * 100, 1);
    }
    
    /**
     * Export analytics data to CSV
     */
    public function export_analytics_csv() {
        $data = $this->get_analytics_data();
        
        $filename = 'srwm-analytics-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Export waitlist data
        fputcsv($output, array('Waitlist Analytics'));
        fputcsv($output, array('Total Restocks', $data['total_restocks']));
        fputcsv($output, array('Average Waitlist Size', $data['avg_waitlist_size']));
        fputcsv($output, array('Average Restock Time (days)', $data['avg_restock_time']));
        
        if ($this->license_manager->is_pro_active()) {
            fputcsv($output, array('Conversion Rate (%)', $data['conversion_rate']));
        }
        
        fputcsv($output, array('')); // Empty row
        
        // Export top products
        fputcsv($output, array('Top Products by Demand'));
        fputcsv($output, array('Product Name', 'Waitlist Count', 'Restock Count', 'Avg Restock Time (days)'));
        
        foreach ($data['top_products'] as $product) {
            fputcsv($output, array(
                $product['name'],
                $product['waitlist_count'],
                $product['restock_count'],
                round($product['avg_restock_time'] ?: 0, 1)
            ));
        }
        
        fputcsv($output, array('')); // Empty row
        
        // Export monthly statistics
        fputcsv($output, array('Monthly Statistics'));
        fputcsv($output, array('Month', 'Waitlist Count', 'Restock Count'));
        
        foreach ($data['monthly_stats'] as $stat) {
            fputcsv($output, array(
                $stat['month'],
                $stat['waitlist_count'],
                $stat['restock_count']
            ));
        }
        
        if ($this->license_manager && $this->license_manager->is_pro_active() && !empty($data['supplier_performance'])) {
            fputcsv($output, array('')); // Empty row
            
            // Export supplier performance
            fputcsv($output, array('Supplier Performance'));
            fputcsv($output, array('Supplier Name', 'Products Managed', 'Total Restocks', 'Avg Restock Quantity', 'Avg Response Time (days)'));
            
            foreach ($data['supplier_performance'] as $supplier) {
                fputcsv($output, array(
                    $supplier['supplier_name'],
                    $supplier['products_managed'],
                    $supplier['total_restocks'],
                    round($supplier['avg_restock_quantity'] ?: 0, 1),
                    round($supplier['avg_response_time'] ?: 0, 1)
                ));
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get restock method breakdown
     */
    public function get_restock_method_breakdown() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_results(
            "SELECT method, COUNT(*) as count
             FROM $table
             GROUP BY method
             ORDER BY count DESC"
        , ARRAY_A);
    }
    
    /**
     * Get waitlist growth trend
     */
    public function get_waitlist_growth_trend($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_added) as date, COUNT(*) as count
             FROM $table
             WHERE date_added >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(date_added)
             ORDER BY date",
            $days
        ), ARRAY_A);
    }
    
    /**
     * Get product category analytics
     */
    public function get_category_analytics() {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_results(
            "SELECT t.name as category_name,
                    COUNT(DISTINCT w.product_id) as products_with_waitlist,
                    COUNT(w.id) as total_waitlist_entries,
                    COUNT(r.id) as total_restocks
             FROM {$wpdb->terms} t
             JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
             JOIN {$wpdb->posts} p ON tr.object_id = p.ID
             LEFT JOIN $waitlist_table w ON p.ID = w.product_id
             LEFT JOIN $logs_table r ON p.ID = r.product_id
             WHERE tt.taxonomy = 'product_cat'
             AND p.post_type = 'product'
             AND p.post_status = 'publish'
             GROUP BY t.term_id
             HAVING total_waitlist_entries > 0
             ORDER BY total_waitlist_entries DESC"
        , ARRAY_A);
    }
    
    /**
     * Get customer engagement metrics
     */
    public function get_customer_engagement_metrics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $metrics = array();
        
        // Customers on multiple waitlists
        $metrics['multiple_waitlists'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT customer_email) 
             FROM (
                 SELECT customer_email, COUNT(*) as waitlist_count
                 FROM $table
                 GROUP BY customer_email
                 HAVING waitlist_count > 1
             ) as subquery"
        );
        
        // Average waitlist duration
        $metrics['avg_waitlist_duration'] = $wpdb->get_var(
            "SELECT AVG(DATEDIFF(NOW(), date_added))
             FROM $table
             WHERE notified = 0"
        );
        
        // Most active customers
        $metrics['top_customers'] = $wpdb->get_results(
            "SELECT customer_email, customer_name, COUNT(*) as waitlist_count
             FROM $table
             GROUP BY customer_email
             ORDER BY waitlist_count DESC
             LIMIT 10"
        , ARRAY_A);
        
        return $metrics;
    }
    
    /**
     * Get real-time dashboard data
     */
    public function get_dashboard_data() {
        return array(
            'today_waitlists' => $this->get_today_waitlists(),
            'today_restocks' => $this->get_today_restocks(),
            'pending_notifications' => $this->get_pending_notifications(),
            'low_stock_products' => $this->get_low_stock_products()
        );
    }
    
    /**
     * Get today's waitlist additions
     */
    private function get_today_waitlists() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE DATE(date_added) = %s",
            date('Y-m-d')
        ));
    }
    
    /**
     * Get today's restocks
     */
    private function get_today_restocks() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE DATE(timestamp) = %s",
            date('Y-m-d')
        ));
    }
    
    /**
     * Get pending notifications
     */
    private function get_pending_notifications() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE notified = 0"
        );
    }
    
    /**
     * Get low stock products
     */
    private function get_low_stock_products() {
        global $wpdb;
        
        $threshold = get_option('srwm_low_stock_threshold', 5);
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_meta_lookup 
             WHERE stock_quantity <= %d AND stock_quantity > 0",
            $threshold
        ));
    }
}