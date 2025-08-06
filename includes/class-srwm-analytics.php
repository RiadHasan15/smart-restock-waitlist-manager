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
            
            return array(
                'total_waitlist_customers' => $total_waitlist_customers,
                'today_waitlists' => $today_waitlists,
                'today_restocks' => $today_restocks,
                'pending_notifications' => $pending_notifications,
                'low_stock_products' => $low_stock_products,
                'avg_restock_time' => $avg_restock_time
            );
            
        } catch (Exception $e) {
            error_log('Analytics get_dashboard_data error: ' . $e->getMessage());
            return array(
                'total_waitlist_customers' => 0,
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
            
            $data = array();
            foreach ($results as $row) {
                $data[] = array(
                    'date' => $row->date,
                    'count' => intval($row->count)
                );
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log('Analytics get_waitlist_growth_trend error: ' . $e->getMessage());
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
            error_log('Analytics get_restock_method_breakdown error: ' . $e->getMessage());
            return array(
                array('method' => 'manual', 'count' => 0),
                array('method' => 'csv_upload', 'count' => 0),
                array('method' => 'quick_restock', 'count' => 0)
            );
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
            return intval($count);
            
        } catch (Exception $e) {
            error_log('Analytics get_total_waitlist_customers error: ' . $e->getMessage());
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
            return intval($count);
            
        } catch (Exception $e) {
            error_log('Analytics get_today_waitlist_additions error: ' . $e->getMessage());
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
            return intval($count);
            
        } catch (Exception $e) {
            error_log('Analytics get_today_restocks error: ' . $e->getMessage());
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
            
            // Query products with low stock
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
                'posts_per_page' => -1,
                'fields' => 'ids'
            );
            
            $low_stock_products = get_posts($args);
            return count($low_stock_products);
            
        } catch (Exception $e) {
            error_log('Analytics get_low_stock_products error: ' . $e->getMessage());
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
            
            return round(floatval($avg_time), 1);
            
        } catch (Exception $e) {
            error_log('Analytics get_average_restock_time error: ' . $e->getMessage());
            return 0;
        }
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
            error_log('Analytics table_exists error: ' . $e->getMessage());
            return false;
        }
    }
}