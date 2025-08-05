<?php
/**
 * Admin Dashboard Class
 * 
 * Handles WordPress admin interface, settings, and dashboard functionality.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_srwm_get_waitlist_data', array($this, 'ajax_get_waitlist_data'));
        add_action('wp_ajax_srwm_export_waitlist', array($this, 'ajax_export_waitlist'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Smart Restock & Waitlist', 'smart-restock-waitlist'),
            __('Restock Manager', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist',
            array($this, 'render_dashboard_page'),
            'dashicons-cart',
            56
        );
        
        add_submenu_page(
            'smart-restock-waitlist',
            __('Dashboard', 'smart-restock-waitlist'),
            __('Dashboard', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'smart-restock-waitlist',
            __('Settings', 'smart-restock-waitlist'),
            __('Settings', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'smart-restock-waitlist',
            __('Analytics', 'smart-restock-waitlist'),
            __('Analytics', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('srwm_settings', 'srwm_waitlist_enabled');
        register_setting('srwm_settings', 'srwm_supplier_notifications');
        register_setting('srwm_settings', 'srwm_email_template_waitlist');
        register_setting('srwm_settings', 'srwm_email_template_supplier');
        register_setting('srwm_settings', 'srwm_low_stock_threshold');
        register_setting('srwm_settings', 'srwm_auto_disable_at_zero');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smart-restock-waitlist') === false) {
            return;
        }
        
        wp_enqueue_script(
            'srwm-admin',
            SRWM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            SRWM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'srwm-admin',
            SRWM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SRWM_VERSION
        );
        
        wp_localize_script('srwm-admin', 'srwm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srwm_admin_nonce'),
            'messages' => array(
                'restock_success' => __('Product restocked successfully!', 'smart-restock-waitlist'),
                'restock_error' => __('Failed to restock product.', 'smart-restock-waitlist'),
                'export_success' => __('Export completed successfully!', 'smart-restock-waitlist'),
                'export_error' => __('Failed to export data.', 'smart-restock-waitlist')
            )
        ));
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $waitlist_products = $this->get_waitlist_products();
        $supplier_products = $this->get_supplier_products();
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Restock & Waitlist Manager', 'smart-restock-waitlist'); ?></h1>
            
            <div class="srwm-dashboard-stats">
                <div class="srwm-stat-card">
                    <h3><?php _e('Active Waitlists', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo count($waitlist_products); ?></div>
                </div>
                
                <div class="srwm-stat-card">
                    <h3><?php _e('Total Waitlist Customers', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $this->get_total_waitlist_customers(); ?></div>
                </div>
                
                <div class="srwm-stat-card">
                    <h3><?php _e('Products with Suppliers', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo count($supplier_products); ?></div>
                </div>
            </div>
            
            <div class="srwm-dashboard-content">
                <div class="srwm-section">
                    <h2><?php _e('Products with Active Waitlists', 'smart-restock-waitlist'); ?></h2>
                    
                    <?php if (empty($waitlist_products)): ?>
                        <p><?php _e('No products currently have active waitlists.', 'smart-restock-waitlist'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('SKU', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Current Stock', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Waitlist Count', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waitlist_products as $product_data): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($product_data['name']); ?></strong>
                                            <div class="row-actions">
                                                <span class="edit">
                                                    <a href="<?php echo get_edit_post_link($product_data['product_id']); ?>">
                                                        <?php _e('Edit', 'smart-restock-waitlist'); ?>
                                                    </a>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($product_data['sku']); ?></td>
                                        <td><?php echo esc_html($product_data['stock']); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($product_data['waitlist_count']); ?></strong>
                                            <button class="button button-small view-waitlist" 
                                                    data-product-id="<?php echo $product_data['product_id']; ?>">
                                                <?php _e('View', 'smart-restock-waitlist'); ?>
                                            </button>
                                        </td>
                                        <td>
                                            <button class="button restock-product" 
                                                    data-product-id="<?php echo $product_data['product_id']; ?>">
                                                <?php _e('Restock', 'smart-restock-waitlist'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <div class="srwm-section">
                    <h2><?php _e('Products with Supplier Alerts', 'smart-restock-waitlist'); ?></h2>
                    
                    <?php if (empty($supplier_products)): ?>
                        <p><?php _e('No products have supplier alerts configured.', 'smart-restock-waitlist'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Current Stock', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Threshold', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supplier_products as $product_data): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($product_data['product_name']); ?></strong>
                                        </td>
                                        <td><?php echo esc_html($product_data['supplier_name']); ?></td>
                                        <td><?php echo esc_html($product_data['current_stock']); ?></td>
                                        <td><?php echo esc_html($product_data['threshold']); ?></td>
                                        <td>
                                            <?php if ($product_data['current_stock'] <= $product_data['threshold']): ?>
                                                <span class="srwm-status alert"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></span>
                                            <?php else: ?>
                                                <span class="srwm-status ok"><?php _e('OK', 'smart-restock-waitlist'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Restock Modal -->
        <div id="srwm-restock-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <span class="srwm-modal-close">&times;</span>
                <h3><?php _e('Restock Product', 'smart-restock-waitlist'); ?></h3>
                <form id="srwm-restock-form">
                    <input type="hidden" id="restock-product-id" name="product_id">
                    <p>
                        <label for="restock-quantity"><?php _e('Quantity to add:', 'smart-restock-waitlist'); ?></label>
                        <input type="number" id="restock-quantity" name="quantity" min="1" value="10" required>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php _e('Restock & Notify Customers', 'smart-restock-waitlist'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Waitlist Modal -->
        <div id="srwm-waitlist-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <span class="srwm-modal-close">&times;</span>
                <h3><?php _e('Waitlist Customers', 'smart-restock-waitlist'); ?></h3>
                <div id="srwm-waitlist-content"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Restock & Waitlist Settings', 'smart-restock-waitlist'); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('srwm_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Waitlist', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_waitlist_enabled" value="yes" 
                                       <?php checked(get_option('srwm_waitlist_enabled'), 'yes'); ?>>
                                <?php _e('Enable customer waitlist functionality', 'smart-restock-waitlist'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Supplier Notifications', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_supplier_notifications" value="yes" 
                                       <?php checked(get_option('srwm_supplier_notifications'), 'yes'); ?>>
                                <?php _e('Enable supplier notifications', 'smart-restock-waitlist'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Low Stock Threshold', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <input type="number" name="srwm_low_stock_threshold" 
                                   value="<?php echo esc_attr(get_option('srwm_low_stock_threshold', 5)); ?>" 
                                   min="0" class="regular-text">
                            <p class="description">
                                <?php _e('Stock level at which to notify suppliers (global default)', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Auto-disable at Zero Stock', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_auto_disable_at_zero" value="yes" 
                                       <?php checked(get_option('srwm_auto_disable_at_zero'), 'yes'); ?>>
                                <?php _e('Automatically hide products when stock reaches zero', 'smart-restock-waitlist'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Email Templates', 'smart-restock-waitlist'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Customer Waitlist Email', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <textarea name="srwm_email_template_waitlist" rows="8" cols="50" class="large-text"><?php 
                                echo esc_textarea(get_option('srwm_email_template_waitlist')); 
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {customer_name}, {product_name}, {product_url}, {site_name}', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Supplier Notification Email', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <textarea name="srwm_email_template_supplier" rows="8" cols="50" class="large-text"><?php 
                                echo esc_textarea(get_option('srwm_email_template_supplier')); 
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {supplier_name}, {product_name}, {sku}, {current_stock}, {waitlist_count}, {site_name}', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        $analytics = new SRWM_Analytics();
        $analytics_data = $analytics->get_analytics_data();
        ?>
        <div class="wrap">
            <h1><?php _e('Waitlist & Restock Analytics', 'smart-restock-waitlist'); ?></h1>
            
            <div class="srwm-analytics-stats">
                <div class="srwm-stat-card">
                    <h3><?php _e('Total Restocks', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $analytics_data['total_restocks']; ?></div>
                </div>
                
                <div class="srwm-stat-card">
                    <h3><?php _e('Avg. Waitlist Size', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $analytics_data['avg_waitlist_size']; ?></div>
                </div>
                
                <div class="srwm-stat-card">
                    <h3><?php _e('Avg. Restock Time', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $analytics_data['avg_restock_time']; ?> days</div>
                </div>
            </div>
            
            <div class="srwm-analytics-content">
                <div class="srwm-section">
                    <h2><?php _e('Top Products by Demand', 'smart-restock-waitlist'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Total Waitlist Customers', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Restock Count', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Avg. Restock Time', 'smart-restock-waitlist'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics_data['top_products'] as $product): ?>
                                <tr>
                                    <td><?php echo esc_html($product['name']); ?></td>
                                    <td><?php echo esc_html($product['waitlist_count']); ?></td>
                                    <td><?php echo esc_html($product['restock_count']); ?></td>
                                    <td><?php echo esc_html($product['avg_restock_time']); ?> days</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <p>
                <a href="<?php echo admin_url('admin-ajax.php?action=srwm_export_waitlist&nonce=' . wp_create_nonce('srwm_export_nonce')); ?>" 
                   class="button button-primary">
                    <?php _e('Export Analytics Data', 'smart-restock-waitlist'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get products with active waitlists
     */
    private function get_waitlist_products() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as product_id, p.post_title as name, pm.meta_value as sku,
                    wc.stock_quantity as stock, COUNT(w.id) as waitlist_count
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
             LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wc ON p.ID = wc.product_id
             INNER JOIN $table w ON p.ID = w.product_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             GROUP BY p.ID
             ORDER BY waitlist_count DESC",
        ), ARRAY_A);
    }
    
    /**
     * Get products with supplier alerts
     */
    private function get_supplier_products() {
        $supplier = new SRWM_Supplier();
        return $supplier->get_products_with_suppliers();
    }
    
    /**
     * Get total waitlist customers
     */
    private function get_total_waitlist_customers() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }
    
    /**
     * AJAX handler for getting waitlist data
     */
    public function ajax_get_waitlist_data() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $product_id = intval($_POST['product_id']);
        $customers = SRWM_Waitlist::get_waitlist_customers($product_id);
        
        $html = '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr><th>' . __('Name', 'smart-restock-waitlist') . '</th><th>' . __('Email', 'smart-restock-waitlist') . '</th><th>' . __('Date Added', 'smart-restock-waitlist') . '</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($customers as $customer) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($customer->customer_name) . '</td>';
            $html .= '<td>' . esc_html($customer->customer_email) . '</td>';
            $html .= '<td>' . esc_html($customer->date_added) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        wp_send_json_success($html);
    }
    
    /**
     * AJAX handler for exporting waitlist data
     */
    public function ajax_export_waitlist() {
        check_ajax_referer('srwm_export_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $filename = 'waitlist-export-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            __('Product ID', 'smart-restock-waitlist'),
            __('Product Name', 'smart-restock-waitlist'),
            __('Customer Name', 'smart-restock-waitlist'),
            __('Customer Email', 'smart-restock-waitlist'),
            __('Date Added', 'smart-restock-waitlist')
        ));
        
        // Get all waitlist data
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_waitlist';
        
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
                $row->date_added
            ));
        }
        
        fclose($output);
        exit;
    }
}