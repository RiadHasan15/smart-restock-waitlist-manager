<?php
/**
 * Admin Dashboard UI Class
 * 
 * Handles the admin dashboard interface and UI components.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Admin_Dashboard {
    
    private $license_manager;
    
    public function __construct($license_manager) {
        $this->license_manager = $license_manager;
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_scripts'));
        // AJAX handlers moved to main plugin file to avoid conflicts
    }
    
    /**
     * Enqueue dashboard-specific scripts and styles
     */
    public function enqueue_dashboard_scripts($hook) {
        // Only enqueue on the main dashboard page, not on other plugin pages
        if ($hook !== 'toplevel_page_smart-restock-waitlist') {
            return;
        }
        
        wp_enqueue_script(
            'srwm-dashboard',
            SRWM_PLUGIN_URL . 'admin/js/dashboard.js',
            array('jquery', 'wp-util', 'chart-js'),
            SRWM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'srwm-dashboard',
            SRWM_PLUGIN_URL . 'admin/css/dashboard.css',
            array(),
            SRWM_VERSION
        );
        
        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_localize_script('srwm-dashboard', 'srwm_dashboard', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srwm_dashboard_nonce'),
            'is_pro' => $this->license_manager->is_pro_active(),
            'messages' => array(
                'loading' => __('Loading...', 'smart-restock-waitlist'),
                'error' => __('Error loading data.', 'smart-restock-waitlist'),
                'export_success' => __('Report exported successfully!', 'smart-restock-waitlist'),
                'export_error' => __('Failed to export report.', 'smart-restock-waitlist')
            )
        ));
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard() {
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $dashboard_data = $analytics->get_dashboard_data();
        ?>
        <div class="wrap srwm-dashboard">
            <h1><?php _e('Smart Restock & Waitlist Dashboard', 'smart-restock-waitlist'); ?></h1>
            
            <?php $this->render_upgrade_notice(); ?>
            
            <!-- Real-time Stats -->
            <div class="srwm-dashboard-stats">
                <div class="srwm-stat-card">
                    <div class="stat-icon">📊</div>
                    <h3><?php _e('Today\'s Waitlists', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $dashboard_data['today_waitlists']; ?></div>
                    <div class="stat-change positive">+12% from yesterday</div>
                </div>
                
                <div class="srwm-stat-card">
                    <div class="stat-icon">📦</div>
                    <h3><?php _e('Today\'s Restocks', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $dashboard_data['today_restocks']; ?></div>
                    <div class="stat-change positive">+8% from yesterday</div>
                </div>
                
                <div class="srwm-stat-card">
                    <div class="stat-icon">⏰</div>
                    <h3><?php _e('Pending Notifications', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $dashboard_data['pending_notifications']; ?></div>
                    <div class="stat-change neutral">No change</div>
                </div>
                
                <div class="srwm-stat-card">
                    <div class="stat-icon">⚠️</div>
                    <h3><?php _e('Low Stock Products', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $dashboard_data['low_stock_products']; ?></div>
                    <div class="stat-change negative">+3 from yesterday</div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="srwm-dashboard-charts">
                <div class="srwm-chart-container">
                    <h3><?php _e('Waitlist Growth (Last 30 Days)', 'smart-restock-waitlist'); ?></h3>
                    <canvas id="waitlistChart" width="400" height="200"></canvas>
                </div>
                
                <div class="srwm-chart-container">
                    <h3><?php _e('Restock Activity (Last 30 Days)', 'smart-restock-waitlist'); ?></h3>
                    <canvas id="restockChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="srwm-quick-actions">
                <h3><?php _e('Quick Actions', 'smart-restock-waitlist'); ?></h3>
                <div class="srwm-action-buttons">
                    <button class="button button-primary" id="srwm-view-waitlists">
                        <?php _e('View All Waitlists', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="button button-secondary" id="srwm-export-report">
                        <?php _e('Export Report', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="button button-secondary" id="srwm-manage-suppliers">
                        <?php _e('Manage Suppliers', 'smart-restock-waitlist'); ?>
                    </button>
                    <?php if ($this->license_manager->is_pro_active()): ?>
                    <button class="button button-primary" id="srwm-pro-features">
                        <?php _e('Pro Features', 'smart-restock-waitlist'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="srwm-recent-activity">
                <h3><?php _e('Recent Activity', 'smart-restock-waitlist'); ?></h3>
                <div id="srwm-activity-feed">
                    <?php $this->render_activity_feed(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize charts
            srwm_init_charts();
            
            // Initialize real-time updates
            srwm_init_realtime_updates();
        });
        </script>
        <?php
    }
    
    /**
     * Render upgrade notice for free users
     */
    private function render_upgrade_notice() {
        if ($this->license_manager->is_pro_active()) {
            return;
        }
        ?>
        <div class="notice notice-info srwm-upgrade-notice">
            <div class="srwm-upgrade-content">
                <div class="srwm-upgrade-text">
                    <h4><?php _e('Upgrade to Pro for Advanced Features', 'smart-restock-waitlist'); ?></h4>
                    <p><?php _e('Unlock one-click supplier restock, multi-channel notifications, automatic purchase orders, and advanced analytics.', 'smart-restock-waitlist'); ?></p>
                </div>
                <div class="srwm-upgrade-actions">
                    <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-license'); ?>" class="button button-primary">
                        <?php _e('Get Pro License', 'smart-restock-waitlist'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render activity feed
     */
    private function render_activity_feed() {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'srwm_restock_logs';
        $waitlist_table = $wpdb->prefix . 'srwm_waitlist';
        
        // Get recent restock activities
        $recent_restocks = $wpdb->get_results(
            "SELECT rl.*, p.post_title as product_name
             FROM $logs_table rl
             JOIN {$wpdb->posts} p ON rl.product_id = p.ID
             ORDER BY rl.timestamp DESC
             LIMIT 10"
        );
        
        // Get recent waitlist additions
        $recent_waitlists = $wpdb->get_results(
            "SELECT w.*, p.post_title as product_name
             FROM $waitlist_table w
             JOIN {$wpdb->posts} p ON w.product_id = p.ID
             ORDER BY w.date_added DESC
             LIMIT 10"
        );
        
        if (empty($recent_restocks) && empty($recent_waitlists)) {
            echo '<p>' . __('No recent activity.', 'smart-restock-waitlist') . '</p>';
            return;
        }
        
        echo '<div class="srwm-activity-list">';
        
        // Combine and sort activities
        $activities = array();
        
        foreach ($recent_restocks as $restock) {
            $activities[] = array(
                'type' => 'restock',
                'timestamp' => $restock->timestamp,
                'product_name' => $restock->product_name,
                'quantity' => $restock->quantity,
                'method' => $restock->method
            );
        }
        
        foreach ($recent_waitlists as $waitlist) {
            $activities[] = array(
                'type' => 'waitlist',
                'timestamp' => $waitlist->date_added,
                'product_name' => $waitlist->product_name,
                'customer_email' => $waitlist->customer_email
            );
        }
        
        // Sort by timestamp
        usort($activities, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Display activities
        foreach (array_slice($activities, 0, 10) as $activity) {
            $this->render_activity_item($activity);
        }
        
        echo '</div>';
    }
    
    /**
     * Render individual activity item
     */
    private function render_activity_item($activity) {
        $time_ago = human_time_diff(strtotime($activity['timestamp']), current_time('timestamp'));
        
        if ($activity['type'] === 'restock') {
            $icon = '📦';
            $message = sprintf(
                __('%s restocked with %d units via %s', 'smart-restock-waitlist'),
                $activity['product_name'],
                $activity['quantity'],
                $activity['method']
            );
        } else {
            $icon = '👤';
            $message = sprintf(
                __('New waitlist signup for %s', 'smart-restock-waitlist'),
                $activity['product_name']
            );
        }
        
        ?>
        <div class="srwm-activity-item">
            <div class="srwm-activity-icon"><?php echo $icon; ?></div>
            <div class="srwm-activity-content">
                <div class="srwm-activity-message"><?php echo esc_html($message); ?></div>
                <div class="srwm-activity-time"><?php echo $time_ago . ' ' . __('ago', 'smart-restock-waitlist'); ?></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for getting dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('srwm_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $data = array(
            'dashboard_data' => $analytics->get_dashboard_data(),
            'waitlist_growth' => $analytics->get_waitlist_growth_trend(30),
            'restock_activity' => $analytics->get_restock_method_breakdown(),
            'category_analytics' => $analytics->get_category_analytics()
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for exporting dashboard report
     */
    public function ajax_export_dashboard_report() {
        check_ajax_referer('srwm_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $analytics->export_analytics_csv();
    }
}