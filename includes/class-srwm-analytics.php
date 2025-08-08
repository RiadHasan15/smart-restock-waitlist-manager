<?php
/**
 * Smart Restock & Waitlist Manager - Analytics Class
 * 
 * Handles all analytics and dashboard data queries
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Analytics {
    
    private $license_manager;
    private static $instance = null;
    
    /**
     * Constructor
     */
    private function __construct($license_manager) {
        $this->license_manager = $license_manager;
    }
    
    /**
     * Get singleton instance
     */
    public static function get_instance($license_manager = null) {
        if (self::$instance === null) {
            self::$instance = new self($license_manager);
        }
        return self::$instance;
    }
    
    /**
     * Get basic dashboard data
     */
    public function get_dashboard_data() {
        global $wpdb;
        
        // Check for cached data (5 minutes cache)
        $cache_key = 'srwm_dashboard_data';
        $cached_data = wp_cache_get($cache_key, 'srwm_analytics');
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        try {
            // Get total waitlist customers
            $total_waitlist_customers = $this->get_total_waitlist_customers();
            
            // Get today's waitlist additions
            $today_waitlists = $this->get_today_waitlist_additions();
            
            // Get today's restocks
            $today_restocks = $this->get_today_restocks();
            
            // Get pending notifications
            $pending_notifications = $this->get_pending_notifications();
            
            // Get low stock products
            $low_stock_products = $this->get_low_stock_products();
            
            // Get average restock time
            $avg_restock_time = $this->get_average_restock_time();
            
            $data = array(
                'total_waitlist_customers' => $total_waitlist_customers,
                'waitlist_products' => $this->get_waitlist_products_count(),
                'today_waitlists' => $today_waitlists,
                'today_restocks' => $today_restocks,
                'pending_notifications' => $pending_notifications,
                'low_stock_products' => $low_stock_products,
                'avg_restock_time' => $avg_restock_time
            );
            
            // Cache the result for 5 minutes
            wp_cache_set($cache_key, $data, 'srwm_analytics', 300);
            
            return $data;
            
        } catch (Exception $e) {
            error_log('SRWM Analytics: Exception in get_dashboard_data: ' . $e->getMessage());
            return array(
                'total_waitlist_customers' => 0,
                'waitlist_products' => 0,
                'today_waitlists' => 0,
                'today_restocks' => 0,
                'pending_notifications' => 0,
                'low_stock_products' => 0,
                'avg_restock_time' => 0
            );
        }
    }
    
    /**
     * Get waitlist growth trend
     */
    public function get_waitlist_growth_trend($days = 7) {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_waitlist';
            
            // Check if table exists
            if (!$this->table_exists($table_name)) {
                return array();
            }
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    DATE(date_added) as date,
                    COUNT(*) as count
                FROM {$table_name}
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
                GROUP BY DATE(date_added)
                ORDER BY date ASC
            ", $days));
            
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Database error in get_waitlist_growth_trend: ' . $wpdb->last_error);
                return array();
            }
            
            $data = array();
            foreach ($results as $row) {
                $data[] = array(
                    'date' => $row->date,
                    'count' => intval($row->count)
                );
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log('SRWM Analytics: Exception in get_waitlist_growth_trend: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get restock method breakdown
     */
    public function get_restock_method_breakdown() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_restock_logs';
            
            // Check if table exists
            if (!$this->table_exists($table_name)) {
                return array();
            }
            
            $results = $wpdb->get_results("
                SELECT 
                    method,
                    COUNT(*) as count
                FROM {$table_name}
                WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY method
                ORDER BY count DESC
            ");
            
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Database error in get_restock_method_breakdown: ' . $wpdb->last_error);
                return array(
                    array('method' => 'manual', 'count' => 0),
                    array('method' => 'csv_upload', 'count' => 0),
                    array('method' => 'quick_restock', 'count' => 0)
                );
            }
            
            $data = array();
            foreach ($results as $row) {
                $data[] = array(
                    'method' => $row->method,
                    'count' => intval($row->count)
                );
            }
            
            // If no data, return default structure
            if (empty($data)) {
                return array(
                    array('method' => 'manual', 'count' => 0),
                    array('method' => 'csv_upload', 'count' => 0),
                    array('method' => 'quick_restock', 'count' => 0)
                );
            }
            
            return $data;
            
        } catch (Exception $e) {
            return array(
                array('method' => 'manual', 'count' => 0),
                array('method' => 'csv_upload', 'count' => 0),
                array('method' => 'quick_restock', 'count' => 0)
            );
        }
    }
    
    /**
     * Get comprehensive analytics data for dashboard
     */
    public function get_analytics_data() {
        global $wpdb;
        
        try {
            $data = array(
                'total_restocks' => $this->get_total_restocks(),
                'avg_waitlist_size' => $this->get_average_waitlist_size(),
                'avg_restock_time' => $this->get_average_restock_time(),
                'conversion_rate' => $this->get_conversion_rate(),
                'top_products' => $this->get_top_products(),
                'recent_activity' => $this->get_recent_activity(),
                'waitlist_trends' => $this->get_waitlist_trends(),
                'restock_efficiency' => $this->get_restock_efficiency(),
                'customer_engagement' => $this->get_customer_engagement(),
                'real_time_stats' => $this->get_real_time_stats()
            );
            
            return $data;
            
        } catch (Exception $e) {
            return array(
                'total_restocks' => 0,
                'avg_waitlist_size' => 0,
                'avg_restock_time' => 0,
                'conversion_rate' => 0,
                'top_products' => array(),
                'recent_activity' => array(),
                'waitlist_trends' => array(),
                'restock_efficiency' => 0,
                'customer_engagement' => 0,
                'real_time_stats' => array()
            );
        }
    }
    
    /**
     * Get real-time statistics
     */
    public function get_real_time_stats() {
        global $wpdb;
        
        try {
            $stats = array();
            
            // Current active waitlists
            $stats['active_waitlists'] = $this->get_active_waitlists_count();
            
            // Today's new waitlist additions
            $stats['today_new_waitlists'] = $this->get_today_waitlist_additions();
            
            // Today's restocks
            $stats['today_restocks'] = $this->get_today_restocks();
            
            // Average waitlist size today
            $stats['avg_waitlist_size_today'] = $this->get_average_waitlist_size_today();
            
            // Products with highest demand
            $stats['high_demand_products'] = $this->get_high_demand_products();
            
            // Recent customer activity
            $stats['recent_customers'] = $this->get_recent_customer_activity();
            
            // Waitlist growth rate
            $stats['growth_rate'] = $this->get_waitlist_growth_rate();
            
            // Restock frequency
            $stats['restock_frequency'] = $this->get_restock_frequency();
            
            return $stats;
            
        } catch (Exception $e) {
            return array(
                'active_waitlists' => 0,
                'today_new_waitlists' => 0,
                'today_restocks' => 0,
                'avg_waitlist_size_today' => 0,
                'high_demand_products' => array(),
                'recent_customers' => array(),
                'growth_rate' => 0,
                'restock_frequency' => 0
            );
        }
    }
    
    /**
     * Get active waitlists count
     */
    private function get_active_waitlists_count() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        return intval($wpdb->get_var("
            SELECT COUNT(DISTINCT product_id) 
            FROM {$table_name} 
            WHERE notified = 0
        ") ?? 0);
    }
    
    /**
     * Get average waitlist size today
     */
    private function get_average_waitlist_size_today() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        $result = $wpdb->get_var("
            SELECT AVG(waitlist_count) as avg_size
            FROM (
                SELECT product_id, COUNT(*) as waitlist_count
                FROM {$table_name}
                WHERE DATE(date_added) = CURDATE()
                GROUP BY product_id
            ) as daily_waitlists
        ");
        
        return round($result ?? 0, 1);
    }
    
    /**
     * Get high demand products
     */
    private function get_high_demand_products() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        $results = $wpdb->get_results("
            SELECT 
                w.product_id,
                p.post_title as product_name,
                COUNT(w.id) as waitlist_count,
                MAX(w.date_added) as last_addition
            FROM {$table_name} w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            WHERE w.notified = 0
            GROUP BY w.product_id
            ORDER BY waitlist_count DESC
            LIMIT 5
        ");
        
        return $results;
    }
    
    /**
     * Get recent customer activity
     */
    private function get_recent_customer_activity() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        $results = $wpdb->get_results("
            SELECT 
                customer_name,
                customer_email,
                product_id,
                date_added,
                TIMESTAMPDIFF(MINUTE, date_added, NOW()) as minutes_ago
            FROM {$table_name}
            WHERE date_added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY date_added DESC
            LIMIT 10
        ");
        
        return $results;
    }
    
    /**
     * Get waitlist growth rate
     */
    private function get_waitlist_growth_rate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        // Get today's count
        $today_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE DATE(date_added) = CURDATE()
        ");
        
        // Get yesterday's count
        $yesterday_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_name} 
            WHERE DATE(date_added) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        ");
        
        // Handle null values
        $today_count = $today_count ?? 0;
        $yesterday_count = $yesterday_count ?? 0;
        
        if ($yesterday_count == 0) {
            return $today_count > 0 ? 100 : 0;
        }
        
        $growth_rate = (($today_count - $yesterday_count) / $yesterday_count) * 100;
        return round($growth_rate, 1);
    }
    
    /**
     * Get restock frequency
     */
    private function get_restock_frequency() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_restock_logs';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        // Get average restocks per day in the last 7 days
        $result = $wpdb->get_var("
            SELECT AVG(daily_restocks) as avg_frequency
            FROM (
                SELECT DATE(timestamp) as restock_date, COUNT(*) as daily_restocks
                FROM {$table_name}
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(timestamp)
            ) as daily_stats
        ");
        
        return round($result ?? 0, 1);
    }
    
    /**
     * Get recent activity feed
     */
    public function get_recent_activity() {
        global $wpdb;
        
        try {
            $activities = array();
            
            // Get recent waitlist additions
            $waitlist_activities = $this->get_recent_waitlist_activities();
            $activities = array_merge($activities, $waitlist_activities);
            
            // Get recent restocks
            $restock_activities = $this->get_recent_restock_activities();
            $activities = array_merge($activities, $restock_activities);
            
            // Sort by timestamp
            usort($activities, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });
            
            return array_slice($activities, 0, 20);
            
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get recent waitlist activities
     */
    private function get_recent_waitlist_activities() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        $results = $wpdb->get_results("
            SELECT 
                'waitlist' as type,
                customer_name,
                customer_email,
                product_id,
                date_added as timestamp,
                'joined waitlist' as action
            FROM {$table_name}
            WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY date_added DESC
            LIMIT 10
        ");
        
        return $results;
    }
    
    /**
     * Get recent restock activities
     */
    private function get_recent_restock_activities() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_restock_logs';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        $results = $wpdb->get_results("
            SELECT 
                'restock' as type,
                '' as customer_name,
                '' as customer_email,
                product_id,
                product_name,
                sku,
                supplier_email,
                method,
                quantity,
                action_details,
                ip_address,
                waitlist_count,
                timestamp,
                CASE 
                    WHEN method = 'restock_link_generated' THEN CONCAT('Restock link generated for ', COALESCE(product_name, 'Product #', product_id))
                    WHEN method = 'quick_restock' THEN CONCAT('Quick restock: ', quantity, ' units added to ', COALESCE(product_name, 'Product #', product_id))
                    WHEN method = 'supplier_link' THEN CONCAT('Supplier restock: ', quantity, ' units added to ', COALESCE(product_name, 'Product #', product_id))
                    WHEN method = 'csv_upload' THEN CONCAT('CSV upload: ', quantity, ' units added to ', COALESCE(product_name, 'Product #', product_id))
                    WHEN method = 'manual' THEN CONCAT('Manual restock: ', quantity, ' units added to ', COALESCE(product_name, 'Product #', product_id))
                    ELSE CONCAT('Restocked ', quantity, ' units of ', COALESCE(product_name, 'Product #', product_id))
                END as action
            FROM {$table_name}
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY timestamp DESC
            LIMIT 10
        ");
        
        return $results;
    }
    
    /**
     * Get waitlist trends
     */
    public function get_waitlist_trends() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_waitlist';
            
            if (!$this->table_exists($table_name)) {
                return array();
            }
            
            $results = $wpdb->get_results("
                SELECT 
                    DATE(date_added) as date,
                    COUNT(*) as count,
                    COUNT(DISTINCT product_id) as products
                FROM {$table_name}
                WHERE date_added >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(date_added)
                ORDER BY date_added DESC
            ");
            
            return $results;
            
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get restock efficiency
     */
    public function get_restock_efficiency() {
        global $wpdb;
        
        try {
            $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
            $restock_table = $wpdb->prefix . 'srwm_restock_logs';
            
            if (!$this->table_exists($waitlist_table) || !$this->table_exists($restock_table)) {
                return 0;
            }
            
            // Calculate efficiency based on time between waitlist and restock
                    $result = $wpdb->get_var("
            SELECT AVG(TIMESTAMPDIFF(HOUR, w.date_added, r.timestamp)) as avg_hours
            FROM {$waitlist_table} w
            JOIN {$restock_table} r ON w.product_id = r.product_id
            WHERE w.notified = 1
            AND r.timestamp > w.date_added
        ");
        
        return round($result ?? 0, 1);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get customer engagement
     */
    public function get_customer_engagement() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_waitlist';
            
            if (!$this->table_exists($table_name)) {
                return 0;
            }
            
            // Calculate engagement based on multiple waitlist joins
                    $result = $wpdb->get_var("
            SELECT 
                (COUNT(DISTINCT customer_email) * 100.0 / COUNT(*)) as engagement_rate
            FROM {$table_name}
            WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return round($result ?? 0, 1);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get count of products with waitlists
     */
    private function get_waitlist_products_count() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_waitlist';
            
            if (!$this->table_exists($table_name)) {
                return 0;
            }
            
            $count = $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM {$table_name}");
            return intval($count ?? 0);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get total waitlist customers
     */
    private function get_total_waitlist_customers() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_waitlist';
            
            if (!$this->table_exists($table_name)) {
                return 0;
            }
            
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            return intval($count ?? 0);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get today's waitlist additions
     */
    private function get_today_waitlist_additions() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_waitlist';
            
            if (!$this->table_exists($table_name)) {
                return 0;
            }
            
            $count = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$table_name} 
                WHERE DATE(date_added) = CURDATE()
            ");
            return intval($count ?? 0);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get today's restocks
     */
    private function get_today_restocks() {
        global $wpdb;
        
        try {
            $table_name = $wpdb->prefix . 'srwm_restock_logs';
            
            if (!$this->table_exists($table_name)) {
                return 0;
            }
            
            $count = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$table_name} 
                WHERE DATE(timestamp) = CURDATE()
            ");
            return intval($count ?? 0);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get pending notifications
     */
    private function get_pending_notifications() {
        // For now, return 0 as notification system is not fully implemented
        return 0;
    }
    
    /**
     * Get low stock products
     */
    private function get_low_stock_products() {
        try {
            if (!class_exists('WC_Product')) {
                return 0;
            }
            
            // Get low stock threshold from WooCommerce settings
            $low_stock_threshold = get_option('woocommerce_notify_low_stock_amount', 2);
            
            // Query products with low stock - limit to 100 for performance
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_stock',
                        'value' => $low_stock_threshold,
                        'compare' => '<=',
                        'type' => 'NUMERIC'
                    ),
                    array(
                        'key' => '_stock',
                        'value' => 0,
                        'compare' => '>',
                        'type' => 'NUMERIC'
                    )
                ),
                'posts_per_page' => 100,
                'fields' => 'ids'
            );
            
            $low_stock_products = get_posts($args);
            return count($low_stock_products);
            
        } catch (Exception $e) {
            error_log('SRWM Analytics: Error getting low stock products: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get average restock time
     */
    private function get_average_restock_time() {
        global $wpdb;
        
        try {
            $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
            $restock_table = $wpdb->prefix . 'srwm_restock_logs';
            
            if (!$this->table_exists($waitlist_table) || !$this->table_exists($restock_table)) {
                return 0;
            }
            
            // Get average time between waitlist addition and restock
            $avg_time = $wpdb->get_var("
                SELECT AVG(DATEDIFF(r.timestamp, w.date_added)) as avg_days
                FROM {$waitlist_table} w
                INNER JOIN {$restock_table} r ON w.product_id = r.product_id
                WHERE r.timestamp > w.date_added
                AND r.timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            
            return round(floatval($avg_time ?? 0), 1);
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get total restocks
     */
    private function get_total_restocks() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_restock_logs';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        return intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") ?? 0);
    }
    
    /**
     * Get average waitlist size
     */
    private function get_average_waitlist_size() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        $result = $wpdb->get_var("
            SELECT AVG(waitlist_count) as avg_size
            FROM (
                SELECT product_id, COUNT(*) as waitlist_count
                FROM {$table_name}
                GROUP BY product_id
            ) as product_waitlists
        ");
        
        return round($result ?? 0, 1);
    }
    
    /**
     * Get conversion rate
     */
    private function get_conversion_rate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return 0;
        }
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $notified = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE notified = 1");
        
        if ($total == 0 || $total === null) {
            return 0;
        }
        
        return round((($notified ?? 0) / ($total ?? 1)) * 100, 1);
    }
    
    /**
     * Get top products
     */
    private function get_top_products() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        $results = $wpdb->get_results("
            SELECT 
                w.product_id,
                p.post_title as product_name,
                COUNT(w.id) as waitlist_count,
                COUNT(CASE WHEN w.notified = 1 THEN 1 END) as restocks,
                AVG(TIMESTAMPDIFF(DAY, w.date_added, 
                    CASE WHEN w.notified = 1 THEN 
                        (SELECT MIN(timestamp) FROM {$wpdb->prefix}srwm_restock_logs WHERE product_id = w.product_id AND timestamp > w.date_added)
                    ELSE NOW() END
                )) as avg_wait_time
            FROM {$table_name} w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            GROUP BY w.product_id
            ORDER BY waitlist_count DESC
            LIMIT 10
        ");
        
        return $results;
    }
    
    /**
     * Check if table exists
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(1) 
                FROM information_schema.tables 
                WHERE table_schema = %s 
                AND table_name = %s
            ", DB_NAME, $table_name));
            
            return $result > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get stat card details
     */
    public function get_stat_card_details($stat_type) {
        global $wpdb;
        
        // Check for cached data (5 minutes cache)
        $cache_key = 'srwm_stat_card_' . $stat_type;
        $cached_data = wp_cache_get($cache_key, 'srwm_analytics');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        try {
            if (empty($stat_type)) {
                return array('error' => 'Invalid stat type');
            }
            
            switch($stat_type) {
                case 'total_waitlist_customers':
                    return $this->get_waitlist_customers_details();
                case 'waitlist_products':
                    return $this->get_waitlist_products_details();
                case 'avg_restock_time':
                    return $this->get_restock_time_details();
                case 'today_waitlists':
                    return $this->get_today_waitlists_details();
                case 'today_restocks':
                    return $this->get_today_restocks_details();
                case 'pending_notifications':
                    return $this->get_pending_notifications_details();
                case 'low_stock_products':
                    return $this->get_low_stock_products_details();
                default:
                    return array('error' => 'Invalid stat type: ' . $stat_type);
            }
        } catch (Exception $e) {
            error_log('SRWM Analytics: Exception in get_stat_card_details: ' . $e->getMessage());
            $result = array('error' => 'Failed to load details: ' . $e->getMessage());
        }
        
        // Cache the result for 5 minutes
        wp_cache_set($cache_key, $result, 'srwm_analytics', 300);
        
        return $result;
    }
    
    /**
     * Get waitlist customers details
     */
    private function get_waitlist_customers_details() {
        global $wpdb;
        
        try {
            $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
            
            // Check if table exists
            if (!$this->table_exists($waitlist_table)) {
                return array(
                    'summary' => array(
                        'total_customers' => 0,
                        'active_waitlists' => 0,
                        'avg_wait_time' => 'N/A',
                        'conversion_rate' => '0%'
                    ),
                    'recent_activity' => array()
                );
            }
            
            // Get total customers
            $total_customers = $wpdb->get_var("SELECT COUNT(DISTINCT customer_email) FROM {$waitlist_table}");
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Error getting total customers: ' . $wpdb->last_error);
                $total_customers = 0;
            } else {
                $total_customers = intval($total_customers ?: 0);
            }
            
            // Get active waitlists (not notified)
            $active_waitlists = $wpdb->get_var("SELECT COUNT(*) FROM {$waitlist_table} WHERE notified = 0");
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Error getting active waitlists: ' . $wpdb->last_error);
                $active_waitlists = 0;
            }
            
            // Get average wait time
            $avg_wait_time = $wpdb->get_var("
                SELECT AVG(DATEDIFF(date_added, CURDATE())) 
                FROM {$waitlist_table} 
                WHERE notified = 1
            ");
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Error getting average wait time: ' . $wpdb->last_error);
                $avg_wait_time = null;
            }
            
            // Get conversion rate (notified vs total)
            $total_notified = $wpdb->get_var("SELECT COUNT(*) FROM {$waitlist_table} WHERE notified = 1");
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Error getting total notified: ' . $wpdb->last_error);
                $total_notified = 0;
            }
            
            $conversion_rate = $total_customers > 0 ? round(($total_notified / $total_customers) * 100, 1) : 0;
            
            // Get recent activity with product names
            $recent_activity = $wpdb->get_results("
                SELECT w.customer_email as email, w.product_id, p.post_title as product_name, w.date_added, w.notified
                FROM {$waitlist_table} w
                LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
                ORDER BY w.date_added DESC
                LIMIT 10
            ");
            if ($wpdb->last_error) {
                error_log('SRWM Analytics: Error getting recent activity: ' . $wpdb->last_error);
                $recent_activity = array();
            }
            
            // Validate and sanitize data
            $result = array(
                'summary' => array(
                    'total_customers' => intval($total_customers ?: 0),
                    'active_waitlists' => intval($active_waitlists ?: 0),
                    'avg_wait_time' => $avg_wait_time ? round(abs($avg_wait_time), 1) . ' days' : 'N/A',
                    'conversion_rate' => $conversion_rate . '%'
                ),
                'recent_activity' => is_array($recent_activity) ? $recent_activity : array()
            );
            
            // Validate summary data
            foreach ($result['summary'] as $key => $value) {
                if (is_numeric($value) && $value < 0) {
                    $result['summary'][$key] = 0;
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SRWM Analytics: Exception in get_waitlist_customers_details: ' . $e->getMessage());
            return array(
                'summary' => array(
                    'total_customers' => 0,
                    'active_waitlists' => 0,
                    'avg_wait_time' => 'N/A',
                    'conversion_rate' => '0%'
                ),
                'recent_activity' => array()
            );
        }
    }
    
    /**
     * Get waitlist products details
     */
    private function get_waitlist_products_details() {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($waitlist_table)) {
            return array(
                'summary' => array(
                    'total_products' => 0,
                    'high_demand' => 0,
                    'out_of_stock' => 0,
                    'low_stock' => 0
                ),
                'top_products' => array()
            );
        }
        
        // Get product statistics with product names
        $product_stats = $wpdb->get_results("
            SELECT 
                w.product_id,
                p.post_title as product_name,
                COUNT(*) as waitlist_count,
                SUM(CASE WHEN w.notified = 0 THEN 1 ELSE 0 END) as active_count
            FROM {$waitlist_table} w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            GROUP BY w.product_id
            ORDER BY waitlist_count DESC
            LIMIT 10
        ");
        
        $total_products = count($product_stats);
        $high_demand = 0;
        
        foreach ($product_stats as $product) {
            if ($product->waitlist_count > 50) $high_demand++;
        }
        
        return array(
            'summary' => array(
                'total_products' => $total_products,
                'high_demand' => $high_demand,
                'out_of_stock' => 0, // Would need WooCommerce integration
                'low_stock' => 0     // Would need WooCommerce integration
            ),
            'top_products' => $product_stats
        );
    }
    
    /**
     * Get restock time details
     */
    private function get_restock_time_details() {
        global $wpdb;
        
        $restock_logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        if (!$this->table_exists($restock_logs_table)) {
            return array(
                'summary' => array(
                    'total_restocks' => 0,
                    'methods' => array()
                ),
                'recent_activity' => array()
            );
        }
        
        // Get restock statistics with product names
        $restock_stats = $wpdb->get_results("
            SELECT 
                r.product_id,
                p.post_title as product_name,
                r.method,
                r.timestamp,
                r.quantity
            FROM {$restock_logs_table} r
            LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
            ORDER BY r.timestamp DESC
            LIMIT 20
        ");
        
        $total_restocks = count($restock_stats);
        $methods = array();
        foreach ($restock_stats as $restock) {
            $methods[$restock->method] = ($methods[$restock->method] ?? 0) + 1;
        }
        
        return array(
            'summary' => array(
                'total_restocks' => $total_restocks,
                'methods' => $methods
            ),
            'recent_activity' => $restock_stats
        );
    }
    
    /**
     * Get today's waitlists details
     */
    private function get_today_waitlists_details() {
        global $wpdb;
        
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        
        if (!$this->table_exists($waitlist_table)) {
            return array(
                'summary' => array(
                    'new_today' => 0,
                    'hourly_breakdown' => array()
                ),
                'recent_activity' => array()
            );
        }
        
        // Get today's waitlists with product names
        $today_waitlists = $wpdb->get_results("
            SELECT w.customer_email as email, w.product_id, p.post_title as product_name, w.date_added
            FROM {$waitlist_table} w
            LEFT JOIN {$wpdb->posts} p ON w.product_id = p.ID
            WHERE DATE(w.date_added) = CURDATE()
            ORDER BY w.date_added DESC
        ");
        
        $new_today = count($today_waitlists);
        $hourly_breakdown = array();
        
        foreach ($today_waitlists as $waitlist) {
            $hour = date('H', strtotime($waitlist->date_added));
            $hourly_breakdown[$hour] = ($hourly_breakdown[$hour] ?? 0) + 1;
        }
        
        return array(
            'summary' => array(
                'new_today' => $new_today,
                'hourly_breakdown' => $hourly_breakdown
            ),
            'recent_activity' => $today_waitlists
        );
    }
    
    /**
     * Get today's restocks details
     */
    private function get_today_restocks_details() {
        global $wpdb;
        
        $restock_logs_table = $wpdb->prefix . 'srwm_restock_logs';
        
        if (!$this->table_exists($restock_logs_table)) {
            return array(
                'summary' => array(
                    'restocks_today' => 0,
                    'total_stock_added' => 0
                ),
                'recent_activity' => array()
            );
        }
        
        // Get today's restocks with product names
        $today_restocks = $wpdb->get_results("
            SELECT r.product_id, p.post_title as product_name, r.method, r.timestamp, r.quantity
            FROM {$restock_logs_table} r
            LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
            WHERE DATE(r.timestamp) = CURDATE()
            ORDER BY r.timestamp DESC
        ");
        
        $restocks_today = count($today_restocks);
        $total_stock_added = array_sum(array_column($today_restocks, 'quantity'));
        
        return array(
            'summary' => array(
                'restocks_today' => $restocks_today,
                'total_stock_added' => $total_stock_added
            ),
            'recent_activity' => $today_restocks
        );
    }
    
    /**
     * Get pending notifications details
     */
    private function get_pending_notifications_details() {
        global $wpdb;
        
        // Return real data - count actual pending notifications
        $pending_count = 0;
        $email_count = 0;
        $sms_count = 0;
        $whatsapp_count = 0;
        
        // For now, return zero since notification system is not fully integrated
        // In a real implementation, this would query notification queue tables
        return array(
            'summary' => array(
                'pending' => $pending_count,
                'email' => $email_count,
                'sms' => $sms_count,
                'whatsapp' => $whatsapp_count
            ),
            'recent_activity' => array()
        );
    }
    
    /**
     * Get low stock products details
     */
    private function get_low_stock_products_details() {
        global $wpdb;
        
        // Try to get real WooCommerce low stock products
        $low_stock_products = array();
        $out_of_stock = 0;
        $low_stock = 0;
        $critical_level = 0;
        
        // Check if WooCommerce is active and has products
        if (!class_exists('WooCommerce') || !function_exists('wc_get_products')) {
            return array(
                'summary' => array(
                    'low_stock' => 0,
                    'out_of_stock' => 0,
                    'critical_level' => 0,
                    'total_value' => '$0'
                ),
                'recent_activity' => array()
            );
        }
        
        try {
                    // Get products with low stock (limit for performance)
        $products = wc_get_products(array(
            'limit' => 20, // Reduced for better performance
            'status' => 'publish',
            'stock_status' => array('outofstock', 'onbackorder')
        ));
            
            foreach ($products as $product) {
                $stock_quantity = $product->get_stock_quantity();
                $low_stock_amount = $product->get_low_stock_amount();
                
                if ($stock_quantity === null || $stock_quantity <= 0) {
                    $out_of_stock++;
                    $low_stock_products[] = array(
                        'product_id' => $product->get_id(),
                        'current_stock' => 0,
                        'threshold' => $low_stock_amount ?: 10,
                        'status' => 'Out of Stock'
                    );
                } elseif ($stock_quantity <= ($low_stock_amount ?: 10)) {
                    $low_stock++;
                    $low_stock_products[] = array(
                        'product_id' => $product->get_id(),
                        'current_stock' => $stock_quantity,
                        'threshold' => $low_stock_amount ?: 10,
                        'status' => 'Low Stock'
                    );
                }
            }
            
            // Get products with very low stock (critical)
            $critical_products = wc_get_products(array(
                'limit' => 20,
                'status' => 'publish',
                'stock_status' => 'instock'
            ));
            
            foreach ($critical_products as $product) {
                $stock_quantity = $product->get_stock_quantity();
                if ($stock_quantity !== null && $stock_quantity > 0 && $stock_quantity <= 3) {
                    $critical_level++;
                    $low_stock_products[] = array(
                        'product_id' => $product->get_id(),
                        'current_stock' => $stock_quantity,
                        'threshold' => 5,
                        'status' => 'Critical'
                    );
                }
            }
        } catch (Exception $e) {
            error_log('SRWM Analytics: Error getting WooCommerce products: ' . $e->getMessage());
            return array(
                'summary' => array(
                    'low_stock' => 0,
                    'out_of_stock' => 0,
                    'critical_level' => 0,
                    'total_value' => '$0'
                ),
                'recent_activity' => array()
            );
        }
        
        // Calculate real total value from WooCommerce products
        $total_value = 0;
        $real_products = array();
        
        foreach ($low_stock_products as $product_data) {
            $product_id = $product_data['product_id'];
            $product = wc_get_product($product_id);
            
            if ($product) {
                $price = $product->get_price();
                $stock_quantity = $product_data['current_stock'];
                
                // Validate price is numeric and positive
                if (is_numeric($price) && $price > 0) {
                    // Calculate value based on current stock
                    if ($stock_quantity > 0) {
                        $total_value += ($price * $stock_quantity);
                    } else {
                        // For out of stock, use price * threshold for potential value
                        $threshold = $product_data['threshold'];
                        $total_value += ($price * $threshold);
                    }
                }
                
                // Add real product data
                $real_products[] = array(
                    'product_id' => $product_id,
                    'product_name' => $product->get_name(),
                    'current_stock' => $stock_quantity,
                    'threshold' => $product_data['threshold'],
                    'price' => $price,
                    'status' => $product_data['status']
                );
            }
        }
        
        // If no real data, return empty data
        if (empty($real_products)) {
            return array(
                'summary' => array(
                    'low_stock' => 0,
                    'out_of_stock' => 0,
                    'critical_level' => 0,
                    'total_value' => '$0'
                ),
                'recent_activity' => array()
            );
        }
        
        return array(
            'summary' => array(
                'low_stock' => $low_stock + $critical_level,
                'out_of_stock' => $out_of_stock,
                'critical_level' => $critical_level,
                'total_value' => '$' . number_format($total_value, 2, '.', ',')
            ),
            'recent_activity' => array_slice($real_products, 0, 10)
        );
    }
    

    
    /**
     * Export analytics data to CSV
     */
    public function export_analytics_csv() {
        try {
            // Get comprehensive analytics data
            $dashboard_data = $this->get_dashboard_data();
            $waitlist_growth = $this->get_waitlist_growth_trend(30); // Last 30 days
            $restock_activity = $this->get_restock_method_breakdown();
            
            // Prepare CSV data
            $csv_data = array();
            
            // Dashboard Overview
            $csv_data[] = array('Dashboard Overview');
            $csv_data[] = array('Metric', 'Value');
            $csv_data[] = array('Total Waitlist Customers', $dashboard_data['total_waitlist_customers']);
            $csv_data[] = array('Today\'s Waitlists', $dashboard_data['today_waitlists']);
            $csv_data[] = array('Today\'s Restocks', $dashboard_data['today_restocks']);
            $csv_data[] = array('Pending Notifications', $dashboard_data['pending_notifications']);
            $csv_data[] = array('Low Stock Products', $dashboard_data['low_stock_products']);
            $csv_data[] = array('Average Restock Time (days)', $dashboard_data['avg_restock_time']);
            $csv_data[] = array(''); // Empty row
            
            // Waitlist Growth Trend
            $csv_data[] = array('Waitlist Growth Trend (Last 30 Days)');
            $csv_data[] = array('Date', 'New Signups');
            foreach ($waitlist_growth as $data) {
                $csv_data[] = array($data['date'], $data['count']);
            }
            $csv_data[] = array(''); // Empty row
            
            // Restock Activity
            $csv_data[] = array('Restock Activity Breakdown');
            $csv_data[] = array('Method', 'Count');
            foreach ($restock_activity as $data) {
                $csv_data[] = array($data['method'], $data['count']);
            }
            
            // Convert to CSV string
            $csv_string = '';
            foreach ($csv_data as $row) {
                $csv_string .= '"' . implode('","', $row) . '"' . "\n";
            }
            
            return $csv_string;
            
        } catch (Exception $e) {
            return 'Error generating report: ' . $e->getMessage();
        }
    }
}