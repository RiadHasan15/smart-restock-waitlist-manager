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
    
    private $license_manager;
    
    public function __construct($license_manager) {
        $this->license_manager = $license_manager;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        // AJAX handlers moved to main plugin file to avoid conflicts
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
        
        add_submenu_page(
            'smart-restock-waitlist',
            __('Supplier Management', 'smart-restock-waitlist'),
            __('Supplier Management', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-suppliers',
            array($this, 'render_suppliers_page')
        );
        
        // Pro features menu items - always check current license status
        if ($this->license_manager->is_pro_active()) {
            add_submenu_page(
                'smart-restock-waitlist',
                __('Multi-Channel Notifications', 'smart-restock-waitlist'),
                __('Multi-Channel Notifications', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-notifications',
                array($this, 'render_notifications_page')
            );
            
            add_submenu_page(
                'smart-restock-waitlist',
                __('Email Templates', 'smart-restock-waitlist'),
                __('Email Templates', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-templates',
                array($this, 'render_templates_page')
            );
            
            add_submenu_page(
                'smart-restock-waitlist',
                __('Purchase Orders', 'smart-restock-waitlist'),
                __('Purchase Orders', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-purchase-orders',
                array($this, 'render_purchase_orders_page')
            );
            
            add_submenu_page(
                'smart-restock-waitlist',
                __('CSV Approvals', 'smart-restock-waitlist'),
                __('CSV Approvals', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-csv-approvals',
                array($this, 'render_csv_approvals_page')
            );
            
            add_submenu_page(
                'smart-restock-waitlist',
                __('Stock Thresholds', 'smart-restock-waitlist'),
                __('Stock Thresholds', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-thresholds',
                array($this, 'render_thresholds_page')
            );
        }
        
        // License menu (always show)
        add_submenu_page(
            'smart-restock-waitlist',
            __('License', 'smart-restock-waitlist'),
            __('License', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-license',
            array($this->license_manager, 'render_license_page')
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
        
        // Pro settings
        if ($this->license_manager->is_pro_active()) {
            register_setting('srwm_settings', 'srwm_whatsapp_enabled');
            register_setting('srwm_settings', 'srwm_sms_enabled');
            register_setting('srwm_settings', 'srwm_auto_generate_po');
            register_setting('srwm_settings', 'srwm_csv_require_approval');
            register_setting('srwm_settings', 'srwm_company_name');
            register_setting('srwm_settings', 'srwm_company_address');
            register_setting('srwm_settings', 'srwm_company_phone');
            register_setting('srwm_settings', 'srwm_company_email');
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Don't enqueue on license page - it doesn't need these scripts
        if (strpos($hook, 'smart-restock-waitlist-license') !== false) {
            return;
        }
        
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
        
        // Enqueue Font Awesome for icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );
        
        wp_localize_script('srwm-admin', 'srwm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
            'site_url' => site_url(),
            'nonce' => wp_create_nonce('srwm_admin_nonce'),
            'is_pro' => $this->license_manager->is_pro_active(),
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
        $total_waitlist_customers = $this->get_total_waitlist_customers();
        $waitlist_products = $this->get_waitlist_products();
        $supplier_products = $this->get_supplier_products();
        
        // Get analytics data for charts
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $analytics_data = $analytics->get_analytics_data();
        ?>
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('Smart Restock & Waitlist Dashboard', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-primary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-settings'); ?>'">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'smart-restock-waitlist'); ?>
                    </button>
                    <?php if ($this->license_manager->is_pro_active()): ?>
                        <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-analytics'); ?>'">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('Analytics', 'smart-restock-waitlist'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="srwm-stats-grid">
                <div class="srwm-stat-card srwm-stat-primary">
                    <div class="srwm-stat-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="srwm-stat-content">
                        <h3><?php _e('Total Waitlist Customers', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-stat-number"><?php echo number_format($total_waitlist_customers); ?></div>
                        <div class="srwm-stat-trend">
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                            <span><?php _e('Active', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-stat-card srwm-stat-success">
                    <div class="srwm-stat-icon">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div class="srwm-stat-content">
                        <h3><?php _e('Products with Waitlist', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-stat-number"><?php echo count($waitlist_products); ?></div>
                        <div class="srwm-stat-trend">
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                            <span><?php _e('In Demand', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-stat-card srwm-stat-warning">
                    <div class="srwm-stat-icon">
                        <span class="dashicons dashicons-businessman"></span>
                    </div>
                    <div class="srwm-stat-content">
                        <h3><?php _e('Products with Suppliers', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-stat-number"><?php echo count($supplier_products); ?></div>
                        <div class="srwm-stat-trend">
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                            <span><?php _e('Connected', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($this->license_manager->is_pro_active()): ?>
                <div class="srwm-stat-card srwm-stat-info">
                    <div class="srwm-stat-icon">
                        <span class="dashicons dashicons-chart-area"></span>
                    </div>
                    <div class="srwm-stat-content">
                        <h3><?php _e('Avg. Restock Time', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-stat-number"><?php echo $analytics_data['avg_restock_time']; ?> days</div>
                        <div class="srwm-stat-trend">
                            <span class="dashicons dashicons-arrow-down-alt"></span>
                            <span><?php _e('Improving', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="srwm-quick-actions">
                <h2><?php _e('Quick Actions', 'smart-restock-waitlist'); ?></h2>
                <div class="srwm-actions-grid">
                    <div class="srwm-action-card" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-settings'); ?>'">
                        <div class="srwm-action-icon">
                            <span class="dashicons dashicons-admin-settings"></span>
                        </div>
                        <h3><?php _e('Configure Settings', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Set up waitlist and notification preferences', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <?php if ($this->license_manager->is_pro_active()): ?>
                    <div class="srwm-action-card" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-suppliers'); ?>'">
                        <div class="srwm-action-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <h3><?php _e('Supplier Management', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Manage suppliers, generate upload links, and quick restock', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-action-card" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-notifications'); ?>'">
                        <div class="srwm-action-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <h3><?php _e('Multi-Channel Notifications', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Configure email, WhatsApp, and SMS alerts', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-action-card" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-purchase-orders'); ?>'">
                        <div class="srwm-action-icon">
                            <span class="dashicons dashicons-media-document"></span>
                        </div>
                        <h3><?php _e('Purchase Orders', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Generate and manage purchase orders', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-action-card" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-csv-approvals'); ?>'">
                        <div class="srwm-action-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php 
                            $pending_count = $this->get_pending_csv_approvals_count();
                            if ($pending_count > 0): 
                            ?>
                            <span class="srwm-badge"><?php echo $pending_count; ?></span>
                            <?php endif; ?>
                        </div>
                        <h3><?php _e('CSV Approvals', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Review and approve CSV uploads from suppliers', 'smart-restock-waitlist'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Products Tables -->
            <div class="srwm-dashboard-content">
                            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Products with Active Waitlist', 'smart-restock-waitlist'); ?></h2>
                    <div class="srwm-pro-actions">
                        <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-analytics'); ?>'">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('View Analytics', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                </div>
                <div class="srwm-pro-card-content">
                    
                    <?php if (!empty($waitlist_products)): ?>
                    <div class="srwm-table-container">
                        <table class="wp-list-table widefat fixed striped srwm-modern-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Current Stock', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Waitlist Count', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waitlist_products as $product_data): ?>
                                    <tr>
                                        <td>
                                            <div class="srwm-product-info">
                                                <strong><?php echo esc_html($product_data['name']); ?></strong>
                                                <small><?php echo esc_html($product_data['sku']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="srwm-stock-badge <?php echo $product_data['stock'] <= 5 ? 'srwm-stock-low' : 'srwm-stock-ok'; ?>">
                                                <?php echo esc_html($product_data['stock']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="srwm-waitlist-count"><?php echo esc_html($product_data['waitlist_count']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($product_data['stock'] == 0): ?>
                                                <span class="srwm-status srwm-status-out"><?php _e('Out of Stock', 'smart-restock-waitlist'); ?></span>
                                            <?php elseif ($product_data['stock'] <= 5): ?>
                                                <span class="srwm-status srwm-status-low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></span>
                                            <?php else: ?>
                                                <span class="srwm-status srwm-status-ok"><?php _e('In Stock', 'smart-restock-waitlist'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="srwm-action-buttons">
                                                <button class="button button-small view-waitlist" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                    <span class="dashicons dashicons-groups"></span>
                                                    <?php _e('View', 'smart-restock-waitlist'); ?>
                                                </button>
                                                <button class="button button-primary button-small restock-product" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                    <span class="dashicons dashicons-update"></span>
                                                    <?php _e('Restock', 'smart-restock-waitlist'); ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="srwm-empty-state">
                        <div class="srwm-empty-icon">
                            <span class="dashicons dashicons-cart"></span>
                        </div>
                        <h3><?php _e('No Products with Waitlist', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Products will appear here when customers join the waitlist.', 'smart-restock-waitlist'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($supplier_products)): ?>
                <div class="srwm-pro-card">
                    <div class="srwm-pro-card-header">
                        <h2><?php _e('Products with Suppliers', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-pro-actions">
                            <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-thresholds'); ?>'">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php _e('Manage Thresholds', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="srwm-pro-card-content">
                    
                    <div class="srwm-table-container">
                        <table class="srwm-pro-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Current Stock', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Threshold', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                    <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supplier_products as $product_data): ?>
                                    <tr>
                                        <td>
                                            <div class="srwm-product-info">
                                                <strong><?php echo esc_html($product_data['product_name']); ?></strong>
                                                <small><?php echo esc_html($product_data['sku'] ?? ''); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="srwm-supplier-info">
                                                <strong><?php echo esc_html($product_data['supplier_name']); ?></strong>
                                                <small><?php echo esc_html($product_data['supplier_email'] ?? ''); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="srwm-stock-badge <?php echo $product_data['current_stock'] <= $product_data['threshold'] ? 'srwm-stock-low' : 'srwm-stock-ok'; ?>">
                                                <?php echo esc_html($product_data['current_stock']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($product_data['threshold']); ?></td>
                                        <td>
                                            <?php if ($product_data['current_stock'] <= $product_data['threshold']): ?>
                                                <span class="srwm-status srwm-status-low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></span>
                                            <?php else: ?>
                                                <span class="srwm-status srwm-status-ok"><?php _e('In Stock', 'smart-restock-waitlist'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="srwm-action-buttons">
                                                <?php if ($this->license_manager->is_pro_active()): ?>
                                                <button class="button button-small generate-restock-link" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                    <span class="dashicons dashicons-admin-links"></span>
                                                    <?php _e('Restock Link', 'smart-restock-waitlist'); ?>
                                                </button>
                                                <?php endif; ?>
                                                <button class="button button-primary button-small restock-product" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                    <span class="dashicons dashicons-update"></span>
                                                    <?php _e('Restock', 'smart-restock-waitlist'); ?>
                                                </button>
                                                                                    </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Modals -->
        <div id="srwm-waitlist-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <div class="srwm-modal-header">
                    <h2><?php _e('Waitlist Customers', 'smart-restock-waitlist'); ?></h2>
                    <span class="srwm-modal-close">&times;</span>
                </div>
                <div id="srwm-waitlist-content"></div>
            </div>
        </div>
        
        <div id="srwm-restock-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <div class="srwm-modal-header">
                    <h2><?php _e('Restock Product', 'smart-restock-waitlist'); ?></h2>
                    <span class="srwm-modal-close">&times;</span>
                </div>
                <form id="srwm-restock-form">
                    <input type="hidden" id="srwm-restock-product-id" name="product_id">
                    <div class="srwm-form-group">
                        <label for="srwm-restock-quantity"><?php _e('Quantity to add:', 'smart-restock-waitlist'); ?></label>
                        <input type="number" id="srwm-restock-quantity" name="quantity" min="1" value="1" class="regular-text">
                    </div>
                    <div class="srwm-form-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Restock Product', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php
        // Include modern CSS
        $this->enqueue_modern_styles();
        ?>
        <?php
    }
    
    /**
     * Enqueue modern styles for dashboard
     */
    private function enqueue_modern_styles() {
        ?>
        <style>
        /* Modern Dashboard Styles */
        .srwm-dashboard {
            background: #f0f0f1;
            min-height: 100vh;
            padding: 20px;
        }
        
        .srwm-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-dashboard-header h1 {
            margin: 0;
            color: #1d2327;
            font-size: 28px;
            font-weight: 600;
        }
        
        .srwm-dashboard-actions {
            display: flex;
            gap: 10px;
        }
        
        .srwm-dashboard-actions .button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Stats Grid */
        .srwm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .srwm-stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .srwm-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .srwm-stat-primary .srwm-stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .srwm-stat-success .srwm-stat-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .srwm-stat-warning .srwm-stat-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        .srwm-stat-info .srwm-stat-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .srwm-stat-content {
            flex: 1;
        }
        
        .srwm-stat-content h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #646970;
            font-weight: 500;
        }
        
        .srwm-stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #1d2327;
            margin: 0 0 8px 0;
        }
        
        .srwm-stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #00a32a;
            font-weight: 500;
        }
        
        /* Quick Actions */
        .srwm-quick-actions {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .srwm-quick-actions h2 {
            margin: 0 0 20px 0;
            color: #1d2327;
            font-size: 20px;
            font-weight: 600;
        }
        
        .srwm-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .srwm-action-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .srwm-action-card:hover {
            border-color: #007cba;
            background: #f0f6fc;
            transform: translateY(-2px);
        }
        
        .srwm-action-icon {
            width: 50px;
            height: 50px;
            background: #007cba;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 20px;
        }
        
        .srwm-action-card h3 {
            margin: 0 0 8px 0;
            color: #1d2327;
            font-size: 16px;
            font-weight: 600;
        }
        
        .srwm-action-card p {
            margin: 0;
            color: #646970;
            font-size: 14px;
            line-height: 1.4;
        }
        
        /* Dashboard Content */
        .srwm-dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .srwm-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .srwm-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f1;
            background: #f8f9fa;
        }
        
        .srwm-section-header h2 {
            margin: 0;
            color: #1d2327;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-section-actions .button {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .srwm-table-container {
            padding: 0;
        }
        
        .srwm-modern-table {
            border: none;
            border-radius: 0;
        }
        
        .srwm-modern-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #e5e5e5;
            font-weight: 600;
            color: #1d2327;
        }
        
        .srwm-modern-table td {
            border-bottom: 1px solid #f0f0f1;
            vertical-align: middle;
        }
        
        .srwm-product-info,
        .srwm-supplier-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .srwm-product-info strong,
        .srwm-supplier-info strong {
            color: #1d2327;
            font-weight: 600;
        }
        
        .srwm-product-info small,
        .srwm-supplier-info small {
            color: #646970;
            font-size: 12px;
        }
        
        .srwm-stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            min-width: 40px;
        }
        
        .srwm-stock-ok {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .srwm-stock-low {
            background: #f8d7da;
            color: #721c24;
        }
        
        .srwm-waitlist-count {
            font-weight: 600;
            color: #1d2327;
            font-size: 16px;
        }
        
        .srwm-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .srwm-status-ok {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .srwm-status-low {
            background: #fff3cd;
            color: #856404;
        }
        
        .srwm-status-out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .srwm-action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .srwm-action-buttons .button {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Empty State */
        .srwm-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #646970;
        }
        
        .srwm-empty-icon {
            font-size: 48px;
            color: #dcdcde;
            margin-bottom: 20px;
        }
        
        .srwm-empty-state h3 {
            margin: 0 0 10px 0;
            color: #1d2327;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-empty-state p {
            margin: 0;
            font-size: 14px;
        }
        
        /* Modals */
        .srwm-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .srwm-modal-content {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .srwm-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .srwm-modal-header h2 {
            margin: 0;
            color: #1d2327;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #646970;
            line-height: 1;
        }
        
        .srwm-modal-close:hover {
            color: #1d2327;
        }
        
        .srwm-form-group {
            margin-bottom: 20px;
        }
        
        .srwm-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .srwm-form-actions {
            padding: 20px 25px;
            border-top: 1px solid #f0f0f1;
            text-align: right;
        }
        
        .srwm-form-actions .button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Pro Feature Pages - Modern Design */
        .srwm-pro-page {
            background: #f0f0f1;
            min-height: 100vh;
            padding: 20px;
        }
        
        .srwm-pro-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-pro-header h1 {
            margin: 0;
            color: #1d2327;
            font-size: 28px;
            font-weight: 600;
        }
        
        .srwm-pro-header .button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Pro Feature Cards */
        .srwm-pro-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .srwm-pro-card-header {
            background: #f8f9fa;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .srwm-pro-card-header h2 {
            margin: 0;
            color: #1d2327;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-pro-card-content {
            padding: 25px;
        }
        
        /* Form Styles */
        .srwm-form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .srwm-form-group {
            margin-bottom: 20px;
        }
        
        .srwm-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1d2327;
            font-size: 14px;
        }
        
        .srwm-form-group input[type="text"],
        .srwm-form-group input[type="email"],
        .srwm-form-group input[type="number"],
        .srwm-form-group input[type="password"],
        .srwm-form-group select,
        .srwm-form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            font-size: 14px;
            background: #ffffff;
            color: #1d2327;
            transition: all 0.2s ease;
        }
        
        .srwm-form-group input[type="text"]:focus,
        .srwm-form-group input[type="email"]:focus,
        .srwm-form-group input[type="number"]:focus,
        .srwm-form-group input[type="password"]:focus,
        .srwm-form-group select:focus,
        .srwm-form-group textarea:focus {
            border-color: #007cba;
            outline: none;
            box-shadow: 0 0 0 1px #007cba;
            background: #ffffff;
        }
        
        /* Toggle Switch */
        .srwm-toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .srwm-toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .srwm-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .srwm-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .srwm-toggle-slider {
            background-color: #007cba;
        }
        
        input:checked + .srwm-toggle-slider:before {
            transform: translateX(26px);
        }
        
        /* Stats Cards for Pro Features */
        .srwm-pro-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .srwm-pro-stat {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .srwm-pro-stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #1d2327;
            margin-bottom: 5px;
        }
        
        .srwm-pro-stat-label {
            font-size: 14px;
            color: #646970;
            font-weight: 500;
        }
        
        /* Action Buttons */
        .srwm-pro-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .srwm-pro-actions .button {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        /* Table Enhancements */
        .srwm-pro-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-pro-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #1d2327;
            border-bottom: 2px solid #e5e5e5;
        }
        
        .srwm-pro-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f1;
            vertical-align: middle;
        }
        
        .srwm-pro-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .srwm-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .srwm-status-active {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .srwm-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .srwm-status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Template Preview */
        .srwm-template-preview {
            background: #f8f9fa;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .srwm-template-preview h4 {
            margin: 0 0 10px 0;
            color: #1d2327;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .srwm-pro-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .srwm-form-row {
                grid-template-columns: 1fr;
            }
            
            .srwm-pro-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .srwm-pro-actions {
                flex-direction: column;
            }
        }
        
        /* Notifications Grid - Modern Design */
        .srwm-notifications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .srwm-notification-card {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.2s ease;
        }
        
        .srwm-notification-card:hover {
            border-color: #007cba;
            box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
        }
        
        .srwm-notification-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .srwm-notification-icon {
            width: 50px;
            height: 50px;
            background: #007cba;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .srwm-notification-title {
            flex: 1;
        }
        
        .srwm-notification-title h3 {
            margin: 0 0 5px 0;
            color: #1d2327;
            font-size: 16px;
            font-weight: 600;
        }
        
        .srwm-notification-title p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        
        .srwm-notification-toggle {
            margin-left: auto;
        }
        
        .srwm-notification-content {
            margin-top: 15px;
        }
        
        /* Toggle Switch - Modern Design */
        .srwm-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .srwm-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .srwm-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .srwm-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .srwm-slider {
            background-color: #007cba;
        }
        
        input:checked + .srwm-slider:before {
            transform: translateX(26px);
        }
        
        /* Templates Grid - Modern Design */
        .srwm-templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .srwm-template-card {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.2s ease;
        }
        
        .srwm-template-card:hover {
            border-color: #007cba;
            box-shadow: 0 2px 8px rgba(0, 124, 186, 0.1);
        }
        
        .srwm-template-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .srwm-template-icon {
            width: 50px;
            height: 50px;
            background: #007cba;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .srwm-template-title {
            flex: 1;
        }
        
        .srwm-template-title h3 {
            margin: 0 0 5px 0;
            color: #1d2327;
            font-size: 16px;
            font-weight: 600;
        }
        
        .srwm-template-title p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        
        .srwm-template-content {
            margin-top: 15px;
        }
        
        /* Threshold Settings - Modern Design */
        .srwm-threshold-settings {
            margin-bottom: 30px;
        }
        
        .srwm-global-threshold {
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .srwm-global-threshold h3 {
            margin: 0 0 10px 0;
            color: #1d2327;
            font-size: 16px;
            font-weight: 600;
        }
        
        .srwm-global-threshold p {
            margin: 0 0 15px 0;
            color: #646970;
            font-size: 14px;
        }
        
        .srwm-threshold-input {
            width: 80px !important;
            padding: 6px 8px !important;
            border: 1px solid #dcdcde !important;
            border-radius: 4px !important;
            background: #ffffff !important;
            color: #1d2327 !important;
            font-size: 14px !important;
        }
        
        .srwm-threshold-input:focus {
            border-color: #007cba !important;
            box-shadow: 0 0 0 1px #007cba !important;
            outline: none !important;
        }
        
        /* Override WordPress default dark styles for form elements */
        .srwm-pro-page input[type="text"],
        .srwm-pro-page input[type="email"],
        .srwm-pro-page input[type="number"],
        .srwm-pro-page input[type="password"],
        .srwm-pro-page select,
        .srwm-pro-page textarea {
            background: #ffffff !important;
            color: #1d2327 !important;
            border: 1px solid #dcdcde !important;
        }
        
        .srwm-pro-page input[type="text"]:focus,
        .srwm-pro-page input[type="email"]:focus,
        .srwm-pro-page input[type="number"]:focus,
        .srwm-pro-page input[type="password"]:focus,
        .srwm-pro-page select:focus,
        .srwm-pro-page textarea:focus {
            background: #ffffff !important;
            color: #1d2327 !important;
            border-color: #007cba !important;
            box-shadow: 0 0 0 1px #007cba !important;
        }
        
        /* Specific overrides for notification and template pages */
        .srwm-notification-content input[type="text"],
        .srwm-notification-content input[type="email"],
        .srwm-notification-content input[type="password"],
        .srwm-notification-content select,
        .srwm-notification-content textarea,
        .srwm-template-content input[type="text"],
        .srwm-template-content input[type="email"],
        .srwm-template-content input[type="password"],
        .srwm-template-content select,
        .srwm-template-content textarea {
            background: #ffffff !important;
            color: #1d2327 !important;
            border: 1px solid #dcdcde !important;
        }
        
        .srwm-notification-content input[type="text"]:focus,
        .srwm-notification-content input[type="email"]:focus,
        .srwm-notification-content input[type="password"]:focus,
        .srwm-notification-content select:focus,
        .srwm-notification-content textarea:focus,
        .srwm-template-content input[type="text"]:focus,
        .srwm-template-content input[type="email"]:focus,
        .srwm-template-content input[type="password"]:focus,
        .srwm-template-content select:focus,
        .srwm-template-content textarea:focus {
            background: #ffffff !important;
            color: #1d2327 !important;
            border-color: #007cba !important;
            box-shadow: 0 0 0 1px #007cba !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .srwm-dashboard-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .srwm-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-actions-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-section-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .srwm-action-buttons {
                flex-direction: column;
            }
        }
        
        /* Global Threshold Form Styling */
        .srwm-input-group {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin-bottom: 12px !important;
        }
        
        .srwm-input-suffix {
            color: #6b7280 !important;
            font-size: 14px !important;
            font-weight: 500 !important;
        }
        
        .srwm-global-threshold-form .srwm-form-group {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px !important;
        }
        
        .srwm-global-threshold-form .srwm-form-group label {
            font-weight: 600 !important;
            color: #374151 !important;
            margin-bottom: 4px !important;
        }
        
        #srwm-save-global-threshold {
            align-self: flex-start !important;
            margin-top: 8px !important;
        }
        
        /* CSV Upload Form Styling */
        .srwm-csv-form {
            margin-bottom: 30px !important;
        }
        
        .srwm-csv-form .srwm-form-group {
            margin-bottom: 20px !important;
        }
        
        .srwm-csv-form .srwm-input-group {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
        }
        
        .srwm-csv-form .srwm-input-group input[type="email"] {
            flex: 1 !important;
            min-width: 300px !important;
        }
        
        /* Status Badge Styling */
        .srwm-status-used {
            background: linear-gradient(135deg, #6b7280, #4b5563) !important;
            color: white !important;
        }
        
        /* Approval Notice Styling */
        .srwm-approval-notice {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .srwm-approval-notice.srwm-auto-approval {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #10b981;
        }
        
        .srwm-notice-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #f59e0b;
        }
        
        .srwm-auto-approval .srwm-notice-icon {
            color: #10b981;
        }
        
        .srwm-notice-content {
            flex: 1;
        }
        
        .srwm-notice-content h4 {
            margin: 0 0 8px 0;
            color: #92400e;
            font-size: 1.1rem;
        }
        
        .srwm-auto-approval .srwm-notice-content h4 {
            color: #065f46;
        }
        
        .srwm-notice-content p {
            margin: 0 0 15px 0;
            color: #78350f;
            line-height: 1.5;
        }
        
        .srwm-auto-approval .srwm-notice-content p {
            color: #047857;
        }
        </style>
        <?php
    }
    
    /**
     * Render Multi-Channel Notifications page
     */
    public function render_notifications_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('Multi-Channel Notifications', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Notification Channels', 'smart-restock-waitlist'); ?></h2>
                </div>
                <div class="srwm-pro-card-content">
                    <p><?php _e('Configure how suppliers are notified about low stock and restock requests.', 'smart-restock-waitlist'); ?></p>
                
                <form method="post" action="options.php">
                    <?php settings_fields('srwm_notifications'); ?>
                    
                    <div class="srwm-notifications-grid">
                        <!-- Email Notifications -->
                        <div class="srwm-notification-card">
                            <div class="srwm-notification-header">
                                <div class="srwm-notification-icon">
                                    <span class="dashicons dashicons-email-alt"></span>
                                </div>
                                <div class="srwm-notification-title">
                                    <h3><?php _e('Email Notifications', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Send notifications via email', 'smart-restock-waitlist'); ?></p>
                                </div>
                                <div class="srwm-notification-toggle">
                                    <label class="srwm-switch">
                                        <input type="checkbox" name="srwm_email_enabled" value="yes" <?php checked(get_option('srwm_email_enabled'), 'yes'); ?>>
                                        <span class="srwm-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="srwm-notification-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('Email Template:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_email_template" rows="6" class="large-text"><?php echo esc_textarea(get_option('srwm_email_template', $this->get_default_email_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {supplier_name}, {product_name}, {sku}, {current_stock}, {waitlist_count}, {restock_link}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- WhatsApp Notifications -->
                        <div class="srwm-notification-card">
                            <div class="srwm-notification-header">
                                <div class="srwm-notification-icon">
                                    <span class="dashicons dashicons-whatsapp"></span>
                                </div>
                                <div class="srwm-notification-title">
                                    <h3><?php _e('WhatsApp Notifications', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Send notifications via WhatsApp', 'smart-restock-waitlist'); ?></p>
                                </div>
                                <div class="srwm-notification-toggle">
                                    <label class="srwm-switch">
                                        <input type="checkbox" name="srwm_whatsapp_enabled" value="yes" <?php checked(get_option('srwm_whatsapp_enabled'), 'yes'); ?>>
                                        <span class="srwm-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="srwm-notification-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('WhatsApp API Key:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_whatsapp_api_key" value="<?php echo esc_attr(get_option('srwm_whatsapp_api_key')); ?>" class="regular-text">
                                    <p class="description"><?php _e('Enter your WhatsApp Business API key', 'smart-restock-waitlist'); ?></p>
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('WhatsApp Template:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_whatsapp_template" rows="6" class="large-text"><?php echo esc_textarea(get_option('srwm_whatsapp_template', $this->get_default_whatsapp_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {supplier_name}, {product_name}, {sku}, {current_stock}, {waitlist_count}, {restock_link}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SMS Notifications -->
                        <div class="srwm-notification-card">
                            <div class="srwm-notification-header">
                                <div class="srwm-notification-icon">
                                    <span class="dashicons dashicons-phone"></span>
                                </div>
                                <div class="srwm-notification-title">
                                    <h3><?php _e('SMS Notifications', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Send notifications via SMS', 'smart-restock-waitlist'); ?></p>
                                </div>
                                <div class="srwm-notification-toggle">
                                    <label class="srwm-switch">
                                        <input type="checkbox" name="srwm_sms_enabled" value="yes" <?php checked(get_option('srwm_sms_enabled'), 'yes'); ?>>
                                        <span class="srwm-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="srwm-notification-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('SMS Provider:', 'smart-restock-waitlist'); ?></label>
                                    <select name="srwm_sms_provider">
                                        <option value="twilio" <?php selected(get_option('srwm_sms_provider'), 'twilio'); ?>><?php _e('Twilio', 'smart-restock-waitlist'); ?></option>
                                        <option value="nexmo" <?php selected(get_option('srwm_sms_provider'), 'nexmo'); ?>><?php _e('Nexmo/Vonage', 'smart-restock-waitlist'); ?></option>
                                    </select>
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('API Key:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_sms_api_key" value="<?php echo esc_attr(get_option('srwm_sms_api_key')); ?>" class="regular-text">
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('API Secret:', 'smart-restock-waitlist'); ?></label>
                                    <input type="password" name="srwm_sms_api_secret" value="<?php echo esc_attr(get_option('srwm_sms_api_secret')); ?>" class="regular-text">
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('SMS Template:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_sms_template" rows="4" class="large-text"><?php echo esc_textarea(get_option('srwm_sms_template', $this->get_default_sms_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {supplier_name}, {product_name}, {sku}, {current_stock}, {waitlist_count}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="srwm-form-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Save Notification Settings', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
        
        <?php
        $this->enqueue_modern_styles();
        ?>
        <?php
    }
    
    /**
     * Enqueue notification styles (placeholder - styles are in enqueue_modern_styles)
     */
    private function enqueue_notification_styles() {
        // Styles are already included in enqueue_modern_styles()
    }
    
    /**
     * Enqueue template styles (placeholder - styles are in enqueue_modern_styles)
     */
    private function enqueue_template_styles() {
        // Styles are already included in enqueue_modern_styles()
    }
    
    /**
     * Enqueue purchase order styles (placeholder - styles are in enqueue_modern_styles)
     */
    private function enqueue_po_styles() {
        // Styles are already included in enqueue_modern_styles()
    }
    
    /**
     * Enqueue CSV upload styles (placeholder - styles are in enqueue_modern_styles)
     */
    private function enqueue_csv_styles() {
        // Styles are already included in enqueue_modern_styles()
    }
    
    /**
     * Enqueue threshold styles (placeholder - styles are in enqueue_modern_styles)
     */
    private function enqueue_threshold_styles() {
        // Styles are already included in enqueue_modern_styles()
    }
    
    /**
     * Get default email template
     */
    private function get_default_email_template() {
        return "Hi {supplier_name},\n\n" .
               "We need to restock the following product:\n\n" .
               "Product: {product_name}\n" .
               "SKU: {sku}\n" .
               "Current Stock: {current_stock}\n" .
               "Waitlist Count: {waitlist_count}\n\n" .
               "Please use the following link to restock:\n{restock_link}\n\n" .
               "Thank you,\n" . get_bloginfo('name');
    }
    
    /**
     * Get default WhatsApp template
     */
    private function get_default_whatsapp_template() {
        return "Hi {supplier_name},\n\n" .
               "We need to restock the following product:\n\n" .
               "Product: {product_name}\n" .
               "SKU: {sku}\n" .
               "Current Stock: {current_stock}\n" .
               "Waitlist Count: {waitlist_count}\n\n" .
               "Please use the following link to restock:\n{restock_link}\n\n" .
               "Thank you,\n" . get_bloginfo('name');
    }
    
    /**
     * Get default SMS template
     */
    private function get_default_sms_template() {
        return "Hi {supplier_name}, we need to restock {product_name} (SKU: {sku}). Current stock: {current_stock}, Waitlist: {waitlist_count}. Please check your email for the restock link.";
    }
    
    /**
     * Get default waitlist email template
     */
    private function get_default_waitlist_email_template() {
        return "Hi {customer_name},\n\n" .
               "Thank you for joining the waitlist for {product_name}.\n\n" .
               "We'll notify you as soon as this product is back in stock.\n\n" .
               "Best regards,\n" . get_bloginfo('name');
    }
    
    /**
     * Get default restock email template
     */
    private function get_default_restock_email_template() {
        return "Hi {customer_name},\n\n" .
               "Great news! {product_name} is now back in stock.\n\n" .
               "You can purchase it here: {product_link}\n\n" .
               "Best regards,\n" . get_bloginfo('name');
    }
    
    /**
     * Get default supplier email template
     */
    private function get_default_supplier_email_template() {
        return "Hi {supplier_name},\n\n" .
               "We need to restock the following product:\n\n" .
               "Product: {product_name}\n" .
               "SKU: {sku}\n" .
               "Current Stock: {current_stock}\n" .
               "Waitlist Count: {waitlist_count}\n\n" .
               "Please use the following link to restock:\n{restock_link}\n\n" .
               "Thank you,\n" . get_bloginfo('name');
    }
    
    /**
     * Get default purchase order email template
     */
    private function get_default_po_email_template() {
        return "Hi {supplier_name},\n\n" .
               "Please find attached Purchase Order #{po_number} for the following items:\n\n" .
               "Product: {product_name}\n" .
               "Quantity: {quantity}\n\n" .
               "Please confirm receipt of this purchase order.\n\n" .
               "Best regards,\n" . get_bloginfo('name');
    }
    
    /**
     * Get total purchase orders count
     */
    private function get_total_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0;
    }
    
    /**
     * Get pending purchase orders count
     */
    private function get_pending_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'") ?: 0;
    }
    
    /**
     * Get completed purchase orders count
     */
    private function get_completed_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'") ?: 0;
    }
    
    /**
     * Get all purchase orders
     */
    private function get_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10") ?: array();
    }
    
    /**
     * Get pending CSV approvals count
     */
    private function get_pending_csv_approvals_count() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_csv_approvals';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table 
            WHERE status = 'pending'
        ");
        
        return intval($count);
    }
    
    /**
     * Get products with thresholds
     */
    private function get_products_with_thresholds() {
        global $wpdb;
        
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish'
        ));
        
        $results = array();
        foreach ($products as $product) {
            $product_obj = new stdClass();
            $product_obj->id = $product->get_id();
            $product_obj->name = $product->get_name();
            $product_obj->sku = $product->get_sku();
            $product_obj->stock_quantity = $product->get_stock_quantity();
            $product_obj->threshold = get_post_meta($product->get_id(), '_srwm_threshold', true) ?: get_option('srwm_global_threshold', 5);
            $results[] = $product_obj;
        }
        
        return $results;
    }
    
    /**
     * Render Email Templates page
     */
    public function render_templates_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('Email Templates', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Customizable Templates', 'smart-restock-waitlist'); ?></h2>
                </div>
                <div class="srwm-pro-card-content">
                    <p><?php _e('Customize email, SMS, and WhatsApp templates with placeholders for dynamic content.', 'smart-restock-waitlist'); ?></p>
                
                <form method="post" action="options.php">
                    <?php settings_fields('srwm_templates'); ?>
                    
                    <div class="srwm-templates-grid">
                        <!-- Customer Waitlist Email -->
                        <div class="srwm-template-card">
                            <div class="srwm-template-header">
                                <div class="srwm-template-icon">
                                    <span class="dashicons dashicons-email-alt"></span>
                                </div>
                                <div class="srwm-template-title">
                                    <h3><?php _e('Customer Waitlist Email', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Sent when customer joins waitlist', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                            <div class="srwm-template-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('Subject:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_waitlist_email_subject" value="<?php echo esc_attr(get_option('srwm_waitlist_email_subject', __('You\'ve been added to the waitlist!', 'smart-restock-waitlist'))); ?>" class="large-text">
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('Email Body:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_waitlist_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('srwm_waitlist_email_template', $this->get_default_waitlist_email_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {customer_name}, {product_name}, {product_url}, {site_name}, {waitlist_position}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Restock Notification -->
                        <div class="srwm-template-card">
                            <div class="srwm-template-header">
                                <div class="srwm-template-icon">
                                    <span class="dashicons dashicons-update"></span>
                                </div>
                                <div class="srwm-template-title">
                                    <h3><?php _e('Customer Restock Notification', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Sent when product is restocked', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                            <div class="srwm-template-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('Subject:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_restock_email_subject" value="<?php echo esc_attr(get_option('srwm_restock_email_subject', __('Product is back in stock!', 'smart-restock-waitlist'))); ?>" class="large-text">
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('Email Body:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_restock_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('srwm_restock_email_template', $this->get_default_restock_email_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {customer_name}, {product_name}, {product_url}, {site_name}, {stock_quantity}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Supplier Notification -->
                        <div class="srwm-template-card">
                            <div class="srwm-template-header">
                                <div class="srwm-template-icon">
                                    <span class="dashicons dashicons-businessman"></span>
                                </div>
                                <div class="srwm-template-title">
                                    <h3><?php _e('Supplier Notification', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Sent to suppliers for low stock', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                            <div class="srwm-template-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('Subject:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_supplier_email_subject" value="<?php echo esc_attr(get_option('srwm_supplier_email_subject', __('Low Stock Alert - Action Required', 'smart-restock-waitlist'))); ?>" class="large-text">
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('Email Body:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_supplier_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('srwm_supplier_email_template', $this->get_default_supplier_email_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {supplier_name}, {product_name}, {sku}, {current_stock}, {waitlist_count}, {restock_link}, {po_number}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Purchase Order Email -->
                        <div class="srwm-template-card">
                            <div class="srwm-template-header">
                                <div class="srwm-template-icon">
                                    <span class="dashicons dashicons-media-document"></span>
                                </div>
                                <div class="srwm-template-title">
                                    <h3><?php _e('Purchase Order Email', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Sent with purchase order PDF', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                            <div class="srwm-template-content">
                                <div class="srwm-form-group">
                                    <label><?php _e('Subject:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_po_email_subject" value="<?php echo esc_attr(get_option('srwm_po_email_subject', __('Purchase Order #{po_number}', 'smart-restock-waitlist'))); ?>" class="large-text">
                                </div>
                                <div class="srwm-form-group">
                                    <label><?php _e('Email Body:', 'smart-restock-waitlist'); ?></label>
                                    <textarea name="srwm_po_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('srwm_po_email_template', $this->get_default_po_email_template())); ?></textarea>
                                    <p class="description"><?php _e('Available placeholders: {supplier_name}, {po_number}, {product_name}, {quantity}, {company_name}, {company_address}', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="srwm-form-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Save Templates', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
        
        <?php
        $this->enqueue_modern_styles();
        ?>
        <?php
    }
    
    /**
     * Render Purchase Orders page
     */
    public function render_purchase_orders_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('Purchase Orders', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Purchase Order Management', 'smart-restock-waitlist'); ?></h2>
                </div>
                <div class="srwm-pro-card-content">
                    <p><?php _e('Generate and manage purchase orders for suppliers when stock is low.', 'smart-restock-waitlist'); ?></p>
                
                <div class="srwm-pro-stats">
                    <div class="srwm-pro-stat">
                        <div class="srwm-pro-stat-number"><?php echo $this->get_total_purchase_orders(); ?></div>
                        <div class="srwm-pro-stat-label"><?php _e('Total POs', 'smart-restock-waitlist'); ?></div>
                    </div>
                    <div class="srwm-pro-stat">
                        <div class="srwm-pro-stat-number"><?php echo $this->get_pending_purchase_orders(); ?></div>
                        <div class="srwm-pro-stat-label"><?php _e('Pending', 'smart-restock-waitlist'); ?></div>
                    </div>
                    <div class="srwm-pro-stat">
                        <div class="srwm-pro-stat-number"><?php echo $this->get_completed_purchase_orders(); ?></div>
                        <div class="srwm-pro-stat-label"><?php _e('Completed', 'smart-restock-waitlist'); ?></div>
                    </div>
                </div>
                
                <div class="srwm-pro-actions">
                    <button class="button button-primary" id="srwm-generate-po">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Generate New PO', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="button button-secondary" id="srwm-export-pos">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export POs', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
                
                <div class="srwm-table-container">
                    <table class="srwm-pro-table">
                        <thead>
                            <tr>
                                <th><?php _e('PO Number', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Quantity', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Date Created', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->get_purchase_orders() as $po): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($po->po_number); ?></strong>
                                    </td>
                                    <td>
                                        <div class="srwm-product-info">
                                            <strong><?php echo esc_html($po->product_name); ?></strong>
                                            <small><?php echo esc_html($po->sku); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="srwm-supplier-info">
                                            <strong><?php echo esc_html($po->supplier_name); ?></strong>
                                            <small><?php echo esc_html($po->supplier_email); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="srwm-quantity-badge"><?php echo esc_html($po->quantity); ?></span>
                                    </td>
                                    <td>
                                        <span class="srwm-status srwm-status-<?php echo esc_attr($po->status); ?>">
                                            <?php echo esc_html(ucfirst($po->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date('M j, Y', strtotime($po->created_at))); ?>
                                    </td>
                                    <td>
                                        <div class="srwm-action-buttons">
                                            <button class="button button-small view-po" data-po-id="<?php echo $po->id; ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <?php _e('View', 'smart-restock-waitlist'); ?>
                                            </button>
                                            <button class="button button-small download-po" data-po-id="<?php echo $po->id; ?>">
                                                <span class="dashicons dashicons-download"></span>
                                                <?php _e('Download', 'smart-restock-waitlist'); ?>
                                            </button>
                                            <button class="button button-small resend-po" data-po-id="<?php echo $po->id; ?>">
                                                <span class="dashicons dashicons-email-alt"></span>
                                                <?php _e('Resend', 'smart-restock-waitlist'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php
        $this->enqueue_modern_styles();
        ?>
        <?php
    }
    
    /**
     * Render CSV Approvals page
     */
    public function render_csv_approvals_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('CSV Upload Approvals', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('CSV Upload Approvals', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Review and approve or reject CSV uploads from suppliers.', 'smart-restock-waitlist'); ?></p>
                    
                    <?php if (isset($_GET['token'])): ?>
                        <div class="srwm-filter-notice">
                            <span class="dashicons dashicons-filter"></span>
                            <?php _e('Filtered by token:', 'smart-restock-waitlist'); ?> 
                            <code><?php echo esc_html($_GET['token']); ?></code>
                            <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-csv-approvals'); ?>" class="button button-small">
                                <?php _e('Clear Filter', 'smart-restock-waitlist'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="srwm-pro-card-content">
                    <!-- Analytics Dashboard -->
                    <div class="srwm-analytics-dashboard">
                        <div class="srwm-analytics-grid">
                            <div class="srwm-analytics-card">
                                <div class="srwm-analytics-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="srwm-analytics-content">
                                    <h3 id="pending-count">0</h3>
                                    <p>Pending Approvals</p>
                                </div>
                            </div>
                            
                            <div class="srwm-analytics-card">
                                <div class="srwm-analytics-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="srwm-analytics-content">
                                    <h3 id="approved-count">0</h3>
                                    <p>Approved Today</p>
                                </div>
                            </div>
                            
                            <div class="srwm-analytics-card">
                                <div class="srwm-analytics-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="srwm-analytics-content">
                                    <h3 id="rejected-count">0</h3>
                                    <p>Rejected Today</p>
                                </div>
                            </div>
                            
                            <div class="srwm-analytics-card">
                                <div class="srwm-analytics-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="srwm-analytics-content">
                                    <h3 id="avg-approval-time">0m</h3>
                                    <p>Avg. Approval Time</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload Trends Chart -->
                        <div class="srwm-chart-container">
                            <h3>Upload Trends (Last 7 Days)</h3>
                            <canvas id="uploadTrendsChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                    
                    <div id="srwm-approvals-container">
                        <div class="srwm-loading">
                            <span class="spinner is-active"></span>
                            <?php _e('Loading approvals...', 'smart-restock-waitlist'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approval Modal -->
            <div id="srwm-approval-modal" class="srwm-modal" style="display: none;">
                <div class="srwm-modal-content">
                    <div class="srwm-modal-header">
                        <h3 id="srwm-modal-title"><?php _e('Review Upload', 'smart-restock-waitlist'); ?></h3>
                        <span class="srwm-modal-close">&times;</span>
                    </div>
                    <div class="srwm-modal-body">
                        <div id="srwm-upload-details"></div>
                        <div class="srwm-form-group">
                            <label for="srwm-admin-notes"><?php _e('Admin Notes (Optional):', 'smart-restock-waitlist'); ?></label>
                            <textarea id="srwm-admin-notes" rows="3" placeholder="<?php _e('Add any notes about this approval...', 'smart-restock-waitlist'); ?>"></textarea>
                        </div>
                    </div>
                    <div class="srwm-modal-footer">
                        <button type="button" class="button button-secondary srwm-modal-close"><?php _e('Cancel', 'smart-restock-waitlist'); ?></button>
                        <button type="button" class="button button-danger" id="srwm-reject-upload"><?php _e('Reject', 'smart-restock-waitlist'); ?></button>
                        <button type="button" class="button button-primary" id="srwm-approve-upload"><?php _e('Approve', 'smart-restock-waitlist'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .srwm-approval-card {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0;
                margin-bottom: 20px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                overflow: hidden;
            }
            
            .srwm-approval-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
                border-color: #cbd5e1;
            }
            
            .srwm-approval-item:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            
            .srwm-approval-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 25px;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-bottom: 1px solid #e2e8f0;
            }
            
            .srwm-approval-card-info {
                flex: 1;
            }
            
            .srwm-approval-card-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .srwm-approval-card-title i {
                color: #667eea;
            }
            
            .srwm-approval-card-meta {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }
            
            .srwm-meta-item {
                display: flex;
                align-items: center;
                gap: 5px;
                color: #6b7280;
                font-size: 0.9rem;
            }
            
            .srwm-meta-item i {
                color: #9ca3af;
                width: 14px;
            }
            
            .srwm-approval-card-status {
                display: flex;
                align-items: center;
            }
            
            .srwm-status-badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                color: white;
                display: flex;
                align-items: center;
                gap: 5px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .srwm-approval-info {
                flex: 1;
            }
            
            .srwm-approval-title {
                font-size: 1.1rem;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 5px;
            }
            
            .srwm-approval-meta {
                color: #6b7280;
                font-size: 0.9rem;
            }
            
            .srwm-approval-status {
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .srwm-status-pending {
                background: #fef3c7;
                color: #92400e;
            }
            
            .srwm-status-approved {
                background: #d1fae5;
                color: #065f46;
            }
            
            .srwm-status-rejected {
                background: #fee2e2;
                color: #991b1b;
            }
            
            .srwm-approval-actions {
                display: flex;
                gap: 10px;
            }
            
            .srwm-approval-card-content {
                padding: 25px;
            }
            
            .srwm-upload-preview {
                margin-bottom: 20px;
            }
            
            .srwm-upload-preview h4 {
                margin-bottom: 15px;
                color: #374151;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 1rem;
            }
            
            .srwm-upload-preview h4 i {
                color: #667eea;
            }
            
            .srwm-upload-table-container {
                background: #f8fafc;
                border-radius: 8px;
                padding: 15px;
                border: 1px solid #e2e8f0;
            }
            
            .srwm-upload-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.9rem;
            }
            
            .srwm-upload-table th,
            .srwm-upload-table td {
                padding: 8px 12px;
                text-align: left;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .srwm-upload-table th {
                background: #f1f5f9;
                font-weight: 600;
                color: #374151;
                padding: 12px 15px;
            }
            
            .srwm-upload-table td {
                padding: 10px 15px;
            }
            
            .srwm-upload-table tr.valid {
                background: rgba(16, 185, 129, 0.05);
            }
            
            .srwm-upload-table tr.invalid {
                background: rgba(239, 68, 68, 0.05);
            }
            
            .srwm-upload-table tr.more-rows {
                background: #f8fafc;
                color: #6b7280;
                font-style: italic;
            }
            
            .srwm-upload-table tr.more-rows td {
                text-align: center;
            }
            
            .srwm-approval-card-actions {
                display: flex;
                gap: 12px;
                justify-content: flex-end;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #e2e8f0;
            }
            
            .srwm-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                text-decoration: none;
                font-size: 0.9rem;
            }
            
            .srwm-btn-success {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
            }
            
            .srwm-btn-success:hover {
                background: linear-gradient(135deg, #059669 0%, #047857 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            }
            
            .srwm-btn-danger {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
            }
            
            .srwm-btn-danger:hover {
                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            }
            
            .srwm-approval-card-result {
                margin-top: 20px;
                padding: 15px;
                background: #f8fafc;
                border-radius: 8px;
                border-left: 4px solid #10b981;
            }
            
            .srwm-admin-notes {
                margin-bottom: 10px;
                color: #374151;
            }
            
            .srwm-processed-info {
                color: #6b7280;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            .srwm-modal {
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
            }
            
            .srwm-modal-content {
                background-color: white;
                margin: 5% auto;
                padding: 0;
                border-radius: 12px;
                width: 80%;
                max-width: 600px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            }
            
            .srwm-modal-header {
                padding: 20px 25px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .srwm-modal-header h3 {
                margin: 0;
                color: #1f2937;
            }
            
            .srwm-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6b7280;
            }
            
            .srwm-modal-body {
                padding: 25px;
            }
            
            .srwm-modal-footer {
                padding: 20px 25px;
                border-top: 1px solid #e2e8f0;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .srwm-loading {
                text-align: center;
                padding: 40px;
                color: #6b7280;
            }
            
            .srwm-no-approvals {
                text-align: center;
                padding: 40px;
                color: #6b7280;
                font-style: italic;
            }
            
            /* Animations */
            @keyframes slideInUp {
                from {
                    transform: translateY(30px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }
            
            @keyframes bounceIn {
                0% {
                    transform: scale(0.3);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.05);
                }
                70% {
                    transform: scale(0.9);
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            
            @keyframes pulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
                100% {
                    transform: scale(1);
                }
            }
            
            /* Notification System */
            .srwm-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                max-width: 300px;
            }
            
            .srwm-notification.show {
                transform: translateX(0);
            }
            
            .srwm-notification-success {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            }
            
            .srwm-notification-error {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            }
            
            .srwm-notification-warning {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            }
            
            .srwm-notification-info {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            }
            
            /* Mobile Responsive Design */
            @media (max-width: 768px) {
                .srwm-approval-card-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 15px;
                }
                
                .srwm-approval-card-meta {
                    flex-direction: column;
                    gap: 8px;
                }
                
                .srwm-approval-card-actions {
                    flex-direction: column;
                    gap: 10px;
                }
                
                .srwm-btn {
                    width: 100%;
                    justify-content: center;
                }
                
                .srwm-upload-table-container {
                    overflow-x: auto;
                }
                
                .srwm-upload-table {
                    min-width: 400px;
                }
                
                .srwm-notification {
                    right: 10px;
                    left: 10px;
                    max-width: none;
                }
                
                .srwm-modal-content {
                    width: 95%;
                    margin: 10% auto;
                }
            }
            
            @media (max-width: 480px) {
                .srwm-approval-card {
                    margin-bottom: 15px;
                }
                
                .srwm-approval-card-content {
                    padding: 15px;
                }
                
                .srwm-approval-card-header {
                    padding: 15px;
                }
                
                .srwm-upload-table {
                    min-width: 300px;
                    font-size: 0.8rem;
                }
                
                .srwm-upload-table th,
                .srwm-upload-table td {
                    padding: 6px 8px;
                }
            }
            
            /* Analytics Dashboard Styles */
            .srwm-analytics-dashboard {
                margin-bottom: 30px;
            }
            
            .srwm-analytics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .srwm-analytics-card {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 20px;
                display: flex;
                align-items: center;
                gap: 15px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .srwm-analytics-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }
            
            .srwm-analytics-icon {
                width: 50px;
                height: 50px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: white;
            }
            
            .srwm-analytics-card:nth-child(1) .srwm-analytics-icon {
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            }
            
            .srwm-analytics-card:nth-child(2) .srwm-analytics-icon {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            }
            
            .srwm-analytics-card:nth-child(3) .srwm-analytics-icon {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            }
            
            .srwm-analytics-card:nth-child(4) .srwm-analytics-icon {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            }
            
            .srwm-analytics-content h3 {
                margin: 0 0 5px 0;
                font-size: 1.8rem;
                font-weight: 700;
                color: #1f2937;
            }
            
            .srwm-analytics-content p {
                margin: 0;
                color: #6b7280;
                font-size: 0.9rem;
            }
            
            .srwm-chart-container {
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 25px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .srwm-chart-container h3 {
                margin: 0 0 20px 0;
                color: #1f2937;
                font-size: 1.1rem;
            }
            
            /* Error handling styles */
            .srwm-error-card {
                border-color: #ef4444;
                background: rgba(239, 68, 68, 0.05);
            }
            
            .srwm-error-message {
                color: #dc2626;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 15px;
                background: rgba(239, 68, 68, 0.1);
                border-radius: 8px;
                border-left: 4px solid #ef4444;
            }
            
            .srwm-error-message i {
                color: #ef4444;
            }
            
            .srwm-error-container {
                text-align: center;
                padding: 40px 20px;
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            
            .srwm-error-container .srwm-error-message {
                margin-bottom: 20px;
                justify-content: center;
            }
            
            .srwm-error-container button {
                margin-top: 15px;
            }
            
            .srwm-filter-notice {
                background: #f0f9ff;
                border: 1px solid #0ea5e9;
                border-radius: 8px;
                padding: 15px;
                margin-top: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .srwm-filter-notice .dashicons {
                color: #0ea5e9;
            }
            
            .srwm-filter-notice code {
                background: #e0f2fe;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 0.9em;
            }
            
            .srwm-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ef4444;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                line-height: 1;
            }
            
            .srwm-action-icon {
                position: relative;
            }
        </style>
        
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <script>
        jQuery(document).ready(function($) {
            let currentApprovalId = null;
            
            // Load approvals
            function loadApprovals() {
                try {
                    console.log('Starting to load approvals...');
                    
                    // Show loading state
                    $('#srwm-approvals-container').html('<div class="srwm-loading"><span class="spinner is-active"></span> Loading approvals...</div>');
                    
                    // Get token filter from URL if present
                    var urlParams = new URLSearchParams(window.location.search);
                    var tokenFilter = urlParams.get('token');
                    
                    var ajaxData = {
                        action: 'srwm_get_csv_approvals',
                        nonce: '<?php echo wp_create_nonce('srwm_admin_nonce'); ?>'
                    };
                    
                    if (tokenFilter) {
                        ajaxData.token = tokenFilter;
                        console.log('Filtering by token:', tokenFilter);
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: ajaxData,
                        timeout: 30000, // 30 second timeout
                        success: function(response) {
                            console.log('CSV Approvals Response:', response);
                            
                            try {
                                if (response && response.success) {
                                    console.log('Calling displayApprovals with:', response.data);
                                    displayApprovals(response.data);
                                } else {
                                    console.log('Response not successful, showing error');
                                    const errorMessage = response && response.message ? response.message : 'Unknown error occurred';
                                    showError('Failed to load approvals: ' + errorMessage);
                                }
                            } catch (error) {
                                console.error('Error processing response:', error);
                                showError('Error processing server response: ' + error.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('CSV Approvals Error:', xhr.responseText, status, error);
                            
                            let errorMessage = 'Error loading approvals';
                            
                            if (status === 'timeout') {
                                errorMessage = 'Request timed out. Please try again.';
                            } else if (status === 'error') {
                                errorMessage = 'Network error. Please check your connection.';
                            } else if (xhr.responseText) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.message || errorMessage;
                                } catch (e) {
                                    errorMessage = error || errorMessage;
                                }
                            }
                            
                            showError(errorMessage);
                        }
                    });
                } catch (error) {
                    console.error('Error in loadApprovals:', error);
                    showError('Error initializing approval loader: ' + error.message);
                }
            }
            
            // Show error message
            function showError(message) {
                const errorHtml = '<div class="srwm-error-container">' +
                    '<div class="srwm-error-message">' +
                    '<i class="fas fa-exclamation-triangle"></i> ' + message +
                    '</div>' +
                    '<button class="button button-primary" onclick="loadApprovals()">' +
                    '<i class="fas fa-redo"></i> Retry' +
                    '</button>' +
                    '</div>';
                
                $('#srwm-approvals-container').html(errorHtml);
            }
            
            // Display approvals
            function displayApprovals(approvals) {
                console.log('Displaying approvals:', approvals);
                console.log('Approvals type:', typeof approvals);
                console.log('Approvals length:', approvals ? approvals.length : 'null');
                
                if (!approvals || approvals.length === 0) {
                    $('#srwm-approvals-container').html('<div class="srwm-no-approvals">No pending approvals</div>');
                    return;
                }
                
                // Update analytics dashboard (only if function exists)
                // Temporarily disabled to fix main display issue
                /*
                if (typeof updateAnalyticsDashboard === 'function') {
                    updateAnalyticsDashboard(approvals);
                }
                */
                
                console.log('Starting to generate HTML for approvals...');
                
                let html = '';
                let processedCount = 0;
                
                approvals.forEach(function(approval, index) {
                    try {
                        console.log('Processing approval:', approval);
                        
                        let uploadData = [];
                        try {
                            uploadData = JSON.parse(approval.upload_data);
                            console.log('Parsed upload data:', uploadData);
                        } catch (error) {
                            console.error('Error parsing upload data:', error);
                            uploadData = [];
                        }
                    
                    const statusClass = 'srwm-status-' + approval.status;
                    const statusIcon = getStatusIcon(approval.status);
                    const statusColor = getStatusColor(approval.status);
                    
                    html += '<div class="srwm-approval-card" style="animation: slideInUp 0.6s ease ' + (index * 0.1) + 's both;">';
                    html += '<div class="srwm-approval-card-header">';
                    html += '<div class="srwm-approval-card-info">';
                    html += '<div class="srwm-approval-card-title">';
                    html += '<i class="fas fa-file-csv"></i> ' + approval.file_name;
                    html += '</div>';
                    html += '<div class="srwm-approval-card-meta">';
                    html += '<span class="srwm-meta-item"><i class="fas fa-user"></i> ' + approval.supplier_email + '</span>';
                    html += '<span class="srwm-meta-item"><i class="fas fa-clock"></i> ' + formatDate(approval.created_at) + '</span>';
                    html += '<span class="srwm-meta-item"><i class="fas fa-list"></i> ' + uploadData.length + ' items</span>';
                    html += '</div>';
                    html += '</div>';
                    html += '<div class="srwm-approval-card-status">';
                    html += '<span class="srwm-status-badge ' + statusClass + '" style="background: ' + statusColor + '">';
                    html += '<i class="' + statusIcon + '"></i> ' + approval.status.charAt(0).toUpperCase() + approval.status.slice(1);
                    html += '</span>';
                    html += '</div>';
                    html += '</div>';
                    
                    html += '<div class="srwm-approval-card-content">';
                    html += '<div class="srwm-upload-preview">';
                    html += '<h4><i class="fas fa-table"></i> Upload Preview</h4>';
                    html += '<div class="srwm-upload-table-container">';
                    html += '<table class="srwm-upload-table">';
                    html += '<thead><tr><th>SKU</th><th>Quantity</th><th>Status</th></tr></thead><tbody>';
                    
                    uploadData.slice(0, 5).forEach(function(row) {
                        const productExists = checkProductExists(row.sku);
                        const statusClass = productExists ? 'valid' : 'invalid';
                        const statusIcon = productExists ? 'fas fa-check' : 'fas fa-exclamation-triangle';
                        const statusText = productExists ? 'Valid' : 'Product not found';
                        
                        html += '<tr class="' + statusClass + '">';
                        html += '<td><strong>' + row.sku + '</strong></td>';
                        html += '<td>' + row.quantity + '</td>';
                        html += '<td><i class="' + statusIcon + '"></i> ' + statusText + '</td>';
                        html += '</tr>';
                    });
                    
                    if (uploadData.length > 5) {
                        html += '<tr class="more-rows">';
                        html += '<td colspan="3"><i class="fas fa-ellipsis-h"></i> ' + (uploadData.length - 5) + ' more items</td>';
                        html += '</tr>';
                    }
                    
                    html += '</tbody></table>';
                    html += '</div>';
                    html += '</div>';
                    
                    if (approval.status === 'pending') {
                        html += '<div class="srwm-approval-card-actions">';
                        html += '<button class="srwm-btn srwm-btn-danger" onclick="reviewUpload(' + approval.id + ', \'reject\')">';
                        html += '<i class="fas fa-times"></i> Reject';
                        html += '</button>';
                        html += '<button class="srwm-btn srwm-btn-success" onclick="reviewUpload(' + approval.id + ', \'approve\')">';
                        html += '<i class="fas fa-check"></i> Approve';
                        html += '</button>';
                        html += '</div>';
                    } else {
                        html += '<div class="srwm-approval-card-result">';
                        if (approval.admin_notes) {
                            html += '<div class="srwm-admin-notes">';
                            html += '<strong><i class="fas fa-comment"></i> Admin Notes:</strong> ' + approval.admin_notes;
                            html += '</div>';
                        }
                        html += '<div class="srwm-processed-info">';
                        html += '<i class="fas fa-calendar-check"></i> Processed: ' + formatDate(approval.processed_at);
                        html += '</div>';
                        html += '</div>';
                    }
                    html += '</div>';
                    html += '</div>';
                    
                    processedCount++;
                    console.log('Successfully processed approval ' + processedCount + ' of ' + approvals.length);
                    
                } catch (error) {
                    console.error('Error processing approval:', error);
                    html += '<div class="srwm-approval-card srwm-error-card">';
                    html += '<div class="srwm-approval-card-content">';
                    html += '<div class="srwm-error-message">';
                    html += '<i class="fas fa-exclamation-triangle"></i> Error processing approval: ' + error.message;
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                }
                });
                
                console.log('Generated HTML length:', html.length);
                console.log('HTML preview:', html.substring(0, 500));
                
                $('#srwm-approvals-container').html(html);
                console.log('HTML set to container');
            }
            
            // Helper functions
            function getStatusIcon(status) {
                try {
                    switch(status) {
                        case 'pending': return 'fas fa-clock';
                        case 'approved': return 'fas fa-check-circle';
                        case 'rejected': return 'fas fa-times-circle';
                        default: return 'fas fa-question-circle';
                    }
                } catch (error) {
                    console.error('Error in getStatusIcon:', error);
                    return 'fas fa-question-circle';
                }
            }
            
            function getStatusColor(status) {
                try {
                    switch(status) {
                        case 'pending': return '#f59e0b';
                        case 'approved': return '#10b981';
                        case 'rejected': return '#ef4444';
                        default: return '#6b7280';
                    }
                } catch (error) {
                    console.error('Error in getStatusColor:', error);
                    return '#6b7280';
                }
            }
            
            function formatDate(dateString) {
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                } catch (error) {
                    console.error('Error in formatDate:', error);
                    return dateString;
                }
            }
            
            function checkProductExists(sku) {
                try {
                    // This would be replaced with actual product validation
                    return sku && sku.length > 0;
                } catch (error) {
                    console.error('Error in checkProductExists:', error);
                    return false;
                }
            }
            
            // Update analytics dashboard
            function updateAnalyticsDashboard(approvals) {
                const today = new Date().toDateString();
                let pendingCount = 0;
                let approvedToday = 0;
                let rejectedToday = 0;
                let totalApprovalTime = 0;
                let processedCount = 0;
                
                approvals.forEach(approval => {
                    if (approval.status === 'pending') {
                        pendingCount++;
                    } else if (approval.status === 'approved') {
                        const processedDate = new Date(approval.processed_at).toDateString();
                        if (processedDate === today) {
                            approvedToday++;
                        }
                        
                        if (approval.processed_at && approval.created_at) {
                            const created = new Date(approval.created_at);
                            const processed = new Date(approval.processed_at);
                            const timeDiff = processed - created;
                            totalApprovalTime += timeDiff;
                            processedCount++;
                        }
                    } else if (approval.status === 'rejected') {
                        const processedDate = new Date(approval.processed_at).toDateString();
                        if (processedDate === today) {
                            rejectedToday++;
                        }
                        
                        if (approval.processed_at && approval.created_at) {
                            const created = new Date(approval.created_at);
                            const processed = new Date(approval.processed_at);
                            const timeDiff = processed - created;
                            totalApprovalTime += timeDiff;
                            processedCount++;
                        }
                    }
                });
                
                // Update dashboard counts
                $('#pending-count').text(pendingCount);
                $('#approved-count').text(approvedToday);
                $('#rejected-count').text(rejectedToday);
                
                // Calculate average approval time
                const avgTime = processedCount > 0 ? Math.round(totalApprovalTime / processedCount / 60000) : 0;
                $('#avg-approval-time').text(avgTime + 'm');
                
                // Update chart
                updateUploadTrendsChart(approvals);
            }
            
            // Update upload trends chart
            function updateUploadTrendsChart(approvals) {
                try {
                    const ctx = document.getElementById('uploadTrendsChart');
                    if (!ctx) {
                        console.log('Chart canvas not found, skipping chart update');
                        return;
                    }
                    
                    // Check if Chart.js is loaded
                    if (typeof Chart === 'undefined') {
                        console.log('Chart.js not loaded, skipping chart update');
                        return;
                    }
                
                // Get last 7 days
                const dates = [];
                const approvedData = [];
                const rejectedData = [];
                const pendingData = [];
                
                for (let i = 6; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    const dateStr = date.toISOString().split('T')[0];
                    dates.push(dateStr);
                    
                    let approved = 0, rejected = 0, pending = 0;
                    approvals.forEach(approval => {
                        const approvalDate = approval.created_at.split(' ')[0];
                        if (approvalDate === dateStr) {
                            if (approval.status === 'approved') approved++;
                            else if (approval.status === 'rejected') rejected++;
                            else if (approval.status === 'pending') pending++;
                        }
                    });
                    
                    approvedData.push(approved);
                    rejectedData.push(rejected);
                    pendingData.push(pending);
                }
                
                // Create or update chart
                if (window.uploadTrendsChart && typeof window.uploadTrendsChart.destroy === 'function') {
                    window.uploadTrendsChart.destroy();
                }
                
                window.uploadTrendsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: dates.map(date => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                        datasets: [
                            {
                                label: 'Approved',
                                data: approvedData,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Rejected',
                                data: rejectedData,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Pending',
                                data: pendingData,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
                
                console.log('Chart created successfully');
            } catch (error) {
                console.error('Error creating chart:', error);
            }
            }
            
            // Review upload
            window.reviewUpload = function(approvalId, action) {
                currentApprovalId = approvalId;
                
                if (action === 'approve') {
                    approveUpload();
                } else if (action === 'reject') {
                    rejectUpload();
                } else {
                    $('#srwm-modal-title').text('Review Upload #' + approvalId);
                    $('#srwm-admin-notes').val('');
                    $('#srwm-approval-modal').show();
                }
            };
            
            // Approve upload function
            function approveUpload() {
                if (!currentApprovalId) return;
                
                const adminNotes = $('#srwm-admin-notes').val() || '';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_approve_csv_upload',
                        approval_id: currentApprovalId,
                        admin_notes: adminNotes,
                        nonce: '<?php echo wp_create_nonce('srwm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Upload approved successfully!', 'success');
                            $('#srwm-approval-modal').hide();
                            loadApprovals();
                        } else {
                            showNotification('Error: ' + response.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error processing approval', 'error');
                    }
                });
            }
            
            // Reject upload function
            function rejectUpload() {
                if (!currentApprovalId) return;
                
                const adminNotes = prompt('Please provide a reason for rejection:');
                if (adminNotes === null) return; // User cancelled
                
                if (!adminNotes.trim()) {
                    showNotification('Please provide a reason for rejection', 'error');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_reject_csv_upload',
                        approval_id: currentApprovalId,
                        admin_notes: adminNotes,
                        nonce: '<?php echo wp_create_nonce('srwm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Upload rejected successfully!', 'error');
                            $('#srwm-approval-modal').hide();
                            loadApprovals();
                        } else {
                            showNotification('Error: ' + response.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error processing rejection', 'error');
                    }
                });
            }
            
            // Show notification function
            function showNotification(message, type) {
                const notification = $('<div class="srwm-notification srwm-notification-' + type + '">' + message + '</div>');
                $('body').append(notification);
                
                setTimeout(() => {
                    notification.addClass('show');
                }, 100);
                
                setTimeout(() => {
                    notification.removeClass('show');
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
            
            // Close modal
            $('.srwm-modal-close').click(function() {
                $('#srwm-approval-modal').hide();
            });
            
            // Approve upload
            $('#srwm-approve-upload').click(function() {
                if (!currentApprovalId) return;
                
                const adminNotes = $('#srwm-admin-notes').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_approve_csv_upload',
                        approval_id: currentApprovalId,
                        admin_notes: adminNotes,
                        nonce: '<?php echo wp_create_nonce('srwm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            $('#srwm-approval-modal').hide();
                            loadApprovals();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error processing approval');
                    }
                });
            });
            
            // Reject upload
            $('#srwm-reject-upload').click(function() {
                if (!currentApprovalId) return;
                
                const adminNotes = $('#srwm-admin-notes').val();
                if (!adminNotes.trim()) {
                    alert('Please provide a reason for rejection');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_reject_csv_upload',
                        approval_id: currentApprovalId,
                        admin_notes: adminNotes,
                        nonce: '<?php echo wp_create_nonce('srwm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            $('#srwm-approval-modal').hide();
                            loadApprovals();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error processing rejection');
                    }
                });
            });
            
            // Load approvals on page load
            console.log('Page loaded, checking container...');
            console.log('Container exists:', $('#srwm-approvals-container').length > 0);
            console.log('Container HTML:', $('#srwm-approvals-container').html());
            
            loadApprovals();
            
            // Touch gestures for mobile
            let touchStartX = 0;
            let touchEndX = 0;
            
            // Add touch event listeners to approval cards
            $(document).on('touchstart', '.srwm-approval-card', function(e) {
                touchStartX = e.originalEvent.touches[0].clientX;
            });
            
            $(document).on('touchend', '.srwm-approval-card', function(e) {
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > swipeThreshold) {
                    const card = $(document.elementFromPoint(touchEndX, touchEndY)).closest('.srwm-approval-card');
                    if (card.length) {
                        const approvalId = card.data('approval-id');
                        
                        if (diff > 0) {
                            // Swipe left - Approve
                            showNotification('Swiped to approve', 'info');
                            setTimeout(() => {
                                reviewUpload(approvalId, 'approve');
                            }, 500);
                        } else {
                            // Swipe right - Reject
                            showNotification('Swiped to reject', 'warning');
                            setTimeout(() => {
                                reviewUpload(approvalId, 'reject');
                            }, 500);
                        }
                    }
                }
            }
            
            // Add data attributes to cards for touch gestures
            function addTouchDataAttributes() {
                $('.srwm-approval-card').each(function(index) {
                    $(this).attr('data-approval-id', index);
                });
            }
            
            // Call after displaying approvals
            setTimeout(addTouchDataAttributes, 100);
        });
        </script>
        <?php
        $this->enqueue_modern_styles();
        ?>
        <?php
    }
    
    /**
     * Render Stock Thresholds page
     */
    public function render_thresholds_page() {
        if (!$this->license_manager->is_pro_active()) {
            // Show debug information
            $current_status = get_option('smart-restock-waitlist-manager_license_status', 'inactive');
            $current_key = get_option('smart-restock-waitlist-manager_license_key', '');
            $is_pro = $this->license_manager->is_pro_active();
            
            echo '<div class="notice notice-warning"><p><strong>Debug Info:</strong> Status: ' . esc_html($current_status) . 
                 ' | Key: ' . (empty($current_key) ? 'Empty' : 'Set') . 
                 ' | Pro Active: ' . ($is_pro ? 'Yes' : 'No') . '</p></div>';
            
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('Stock Thresholds', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Threshold Management', 'smart-restock-waitlist'); ?></h2>
                </div>
                <div class="srwm-pro-card-content">
                    <p><?php _e('Set global and per-product notification thresholds for supplier alerts.', 'smart-restock-waitlist'); ?></p>
                
                <div class="srwm-threshold-settings">
                    <div class="srwm-global-threshold">
                        <h3><?php _e('Global Default Threshold', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('This threshold will be used for all products unless a specific threshold is set.', 'smart-restock-waitlist'); ?></p>
                        <div class="srwm-global-threshold-form">
                            <div class="srwm-form-group">
                                <label><?php _e('Default Threshold:', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-input-group">
                                    <input type="number" id="srwm_global_threshold" name="srwm_global_threshold" value="<?php echo esc_attr(get_option('srwm_global_threshold', 5)); ?>" min="0" class="small-text">
                                    <span class="srwm-input-suffix"><?php _e('units', 'smart-restock-waitlist'); ?></span>
                                </div>
                                <button type="button" id="srwm-save-global-threshold" class="button button-primary">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Save Global Threshold', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-table-container">
                    <table class="srwm-pro-table">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Current Stock', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Current Threshold', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->get_products_with_thresholds() as $product): ?>
                                <tr>
                                    <td>
                                        <div class="srwm-product-info">
                                            <strong><?php echo esc_html($product->name); ?></strong>
                                            <small><?php echo esc_html($product->sku); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="srwm-stock-badge <?php echo $product->stock_quantity <= $product->threshold ? 'srwm-stock-low' : 'srwm-stock-ok'; ?>">
                                            <?php echo esc_html($product->stock_quantity); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" class="srwm-threshold-input" data-product-id="<?php echo $product->id; ?>" value="<?php echo esc_attr($product->threshold); ?>" min="0" class="small-text">
                                    </td>
                                    <td>
                                        <?php if ($product->stock_quantity <= $product->threshold): ?>
                                            <span class="srwm-status srwm-status-low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></span>
                                        <?php else: ?>
                                            <span class="srwm-status srwm-status-ok"><?php _e('In Stock', 'smart-restock-waitlist'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="srwm-action-buttons">
                                            <button class="button button-small save-threshold" data-product-id="<?php echo $product->id; ?>">
                                                <span class="dashicons dashicons-saved"></span>
                                                <?php _e('Save', 'smart-restock-waitlist'); ?>
                                            </button>
                                            <button class="button button-small reset-threshold" data-product-id="<?php echo $product->id; ?>">
                                                <span class="dashicons dashicons-undo"></span>
                                                <?php _e('Reset', 'smart-restock-waitlist'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
        
        <?php
        $this->enqueue_modern_styles();
        ?>
        <?php
    }
    
    /**
     * Render Pro feature locked page
     */
    private function render_pro_feature_locked() {
        ?>
        <div class="wrap srwm-dashboard">
            <div class="srwm-pro-locked">
                <div class="srwm-pro-locked-icon">
                    <span class="dashicons dashicons-lock"></span>
                </div>
                <h1><?php _e('Pro Feature Locked', 'smart-restock-waitlist'); ?></h1>
                <p><?php _e('This feature requires a valid Pro license. Please activate your license to unlock all Pro features.', 'smart-restock-waitlist'); ?></p>
                <div class="srwm-dev-license-info">
                    <p><strong><?php _e('For Development/Testing:', 'smart-restock-waitlist'); ?></strong></p>
                    <p><?php _e('Use any of these license keys:', 'smart-restock-waitlist'); ?></p>
                    <code>DEV-LICENSE-12345, TEST-LICENSE-67890, DEMO-LICENSE-11111, PRO-LICENSE-22222, TRIAL-LICENSE-33333</code>
                </div>
                <div class="srwm-pro-locked-actions">
                    <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-license'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <?php _e('Activate License', 'smart-restock-waitlist'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <style>
        .srwm-pro-locked {
            text-align: center;
            padding: 80px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .srwm-pro-locked-icon {
            font-size: 64px;
            color: #dcdcde;
            margin-bottom: 20px;
        }
        
        .srwm-pro-locked h1 {
            color: #1d2327;
            margin-bottom: 15px;
        }
        
        .srwm-pro-locked p {
            color: #646970;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .srwm-pro-locked-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .srwm-pro-locked-actions .button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
        }
        </style>
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
                
                <h2><?php _e('General Settings', 'smart-restock-waitlist'); ?></h2>
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
                
                <?php if ($this->license_manager->is_pro_active()): ?>
                <h2><?php _e('Pro Settings', 'smart-restock-waitlist'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('WhatsApp Notifications', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_whatsapp_enabled" value="yes" 
                                       <?php checked(get_option('srwm_whatsapp_enabled'), 'yes'); ?>>
                                <?php _e('Enable WhatsApp notifications for suppliers', 'smart-restock-waitlist'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('SMS Notifications', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_sms_enabled" value="yes" 
                                       <?php checked(get_option('srwm_sms_enabled'), 'yes'); ?>>
                                <?php _e('Enable SMS notifications for suppliers', 'smart-restock-waitlist'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Auto-generate Purchase Orders', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_auto_generate_po" value="yes" 
                                       <?php checked(get_option('srwm_auto_generate_po'), 'yes'); ?>>
                                <?php _e('Automatically generate purchase orders when stock is low', 'smart-restock-waitlist'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('CSV Upload Approval', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="srwm_csv_require_approval" value="yes" 
                                       <?php checked(get_option('srwm_csv_require_approval', 'yes'), 'yes'); ?>>
                                <?php _e('Require admin approval for CSV uploads before updating stock', 'smart-restock-waitlist'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, CSV uploads will be stored for review before processing. When disabled, uploads are processed immediately.', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Company Information (for Purchase Orders)', 'smart-restock-waitlist'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Company Name', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <input type="text" name="srwm_company_name" 
                                   value="<?php echo esc_attr(get_option('srwm_company_name', get_bloginfo('name'))); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Company Address', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <textarea name="srwm_company_address" rows="3" class="regular-text"><?php 
                                echo esc_textarea(get_option('srwm_company_address')); 
                            ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Company Phone', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <input type="text" name="srwm_company_phone" 
                                   value="<?php echo esc_attr(get_option('srwm_company_phone')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Company Email', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <input type="email" name="srwm_company_email" 
                                   value="<?php echo esc_attr(get_option('srwm_company_email', get_option('admin_email'))); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php endif; ?>
                
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
                                <?php if ($this->license_manager->is_pro_active()): ?>
                                    <br><?php _e('Pro placeholders: {restock_link}, {po_number}', 'smart-restock-waitlist'); ?>
                                <?php endif; ?>
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
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $analytics_data = $analytics->get_analytics_data();
        ?>
        <div class="wrap">
            <div class="srwm-analytics-header">
                <div class="srwm-header-content">
                    <h1><?php _e('Analytics Dashboard', 'smart-restock-waitlist'); ?></h1>
                    <p><?php _e('Comprehensive insights into your waitlist and restock performance', 'smart-restock-waitlist'); ?></p>
                </div>
                <div class="srwm-header-actions">
                    <a href="<?php echo admin_url('admin-ajax.php?action=srwm_export_waitlist&nonce=' . wp_create_nonce('srwm_export_nonce')); ?>" 
                       class="srwm-btn srwm-btn-primary">
                        <span class="srwm-btn-icon">📊</span>
                        <?php _e('Export Data', 'smart-restock-waitlist'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Key Metrics Cards -->
            <div class="srwm-metrics-grid">
                <div class="srwm-metric-card">
                    <div class="srwm-metric-icon">📈</div>
                    <div class="srwm-metric-content">
                        <h3><?php _e('Total Restocks', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-metric-number"><?php echo number_format($analytics_data['total_restocks']); ?></div>
                        <div class="srwm-metric-trend positive">
                            <span class="srwm-trend-icon">↗</span>
                            <span class="srwm-trend-text"><?php _e('Active', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-metric-card">
                    <div class="srwm-metric-icon">👥</div>
                    <div class="srwm-metric-content">
                        <h3><?php _e('Avg. Waitlist Size', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-metric-number"><?php echo number_format($analytics_data['avg_waitlist_size'], 1); ?></div>
                        <div class="srwm-metric-trend neutral">
                            <span class="srwm-trend-icon">→</span>
                            <span class="srwm-trend-text"><?php _e('Customers per product', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-metric-card">
                    <div class="srwm-metric-icon">⏱️</div>
                    <div class="srwm-metric-content">
                        <h3><?php _e('Avg. Restock Time', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-metric-number"><?php echo number_format($analytics_data['avg_restock_time'], 1); ?></div>
                        <div class="srwm-metric-unit"><?php _e('days', 'smart-restock-waitlist'); ?></div>
                        <div class="srwm-metric-trend neutral">
                            <span class="srwm-trend-icon">→</span>
                            <span class="srwm-trend-text"><?php _e('Average time', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($this->license_manager->is_pro_active()): ?>
                <div class="srwm-metric-card">
                    <div class="srwm-metric-icon">🎯</div>
                    <div class="srwm-metric-content">
                        <h3><?php _e('Conversion Rate', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-metric-number"><?php echo number_format($analytics_data['conversion_rate'], 1); ?>%</div>
                        <div class="srwm-metric-trend positive">
                            <span class="srwm-trend-icon">↗</span>
                            <span class="srwm-trend-text"><?php _e('Success rate', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Analytics Content Grid -->
            <div class="srwm-analytics-grid">
                <!-- Top Products Chart -->
                <div class="srwm-analytics-card">
                    <div class="srwm-card-header">
                        <h2><?php _e('Top Products by Demand', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-card-actions">
                            <button class="srwm-btn srwm-btn-outline srwm-btn-sm" id="refresh-products">
                                <span class="srwm-btn-icon">🔄</span>
                                <?php _e('Refresh', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                    <div class="srwm-card-content">
                        <div class="srwm-products-table">
                            <table class="srwm-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Waitlist', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Restocks', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Avg. Time', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics_data['top_products'] as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="srwm-product-info">
                                                    <div class="srwm-product-name"><?php echo esc_html($product['name']); ?></div>
                                                    <div class="srwm-product-sku">SKU: <?php echo esc_html($product['sku'] ?? 'N/A'); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="srwm-stat-badge">
                                                    <span class="srwm-stat-number"><?php echo number_format($product['waitlist_count']); ?></span>
                                                    <span class="srwm-stat-label"><?php _e('customers', 'smart-restock-waitlist'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="srwm-stat-badge">
                                                    <span class="srwm-stat-number"><?php echo number_format($product['restock_count']); ?></span>
                                                    <span class="srwm-stat-label"><?php _e('times', 'smart-restock-waitlist'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="srwm-stat-badge">
                                                    <span class="srwm-stat-number"><?php echo number_format($product['avg_restock_time'], 1); ?></span>
                                                    <span class="srwm-stat-label"><?php _e('days', 'smart-restock-waitlist'); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $stock_status = $product['stock_status'] ?? 'instock';
                                                $status_class = $stock_status === 'instock' ? 'in-stock' : 'out-of-stock';
                                                $status_text = $stock_status === 'instock' ? __('In Stock', 'smart-restock-waitlist') : __('Out of Stock', 'smart-restock-waitlist');
                                                ?>
                                                <span class="srwm-status-badge <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Insights -->
                <div class="srwm-analytics-card">
                    <div class="srwm-card-header">
                        <h2><?php _e('Performance Insights', 'smart-restock-waitlist'); ?></h2>
                    </div>
                    <div class="srwm-card-content">
                        <div class="srwm-insights-grid">
                            <div class="srwm-insight-item">
                                <div class="srwm-insight-icon">🚀</div>
                                <div class="srwm-insight-content">
                                    <h4><?php _e('Best Performing', 'smart-restock-waitlist'); ?></h4>
                                    <p><?php _e('Products with highest restock efficiency', 'smart-restock-waitlist'); ?></p>
                                    <div class="srwm-insight-value">
                                        <?php 
                                        $best_product = reset($analytics_data['top_products']);
                                        echo esc_html($best_product['name'] ?? __('No data', 'smart-restock-waitlist')); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="srwm-insight-item">
                                <div class="srwm-insight-icon">📊</div>
                                <div class="srwm-insight-content">
                                    <h4><?php _e('Average Wait Time', 'smart-restock-waitlist'); ?></h4>
                                    <p><?php _e('Time customers wait for restock', 'smart-restock-waitlist'); ?></p>
                                    <div class="srwm-insight-value">
                                        <?php echo number_format($analytics_data['avg_restock_time'], 1); ?> <?php _e('days', 'smart-restock-waitlist'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="srwm-insight-item">
                                <div class="srwm-insight-icon">🎯</div>
                                <div class="srwm-insight-content">
                                    <h4><?php _e('Success Rate', 'smart-restock-waitlist'); ?></h4>
                                    <p><?php _e('Percentage of successful restocks', 'smart-restock-waitlist'); ?></p>
                                    <div class="srwm-insight-value">
                                        <?php echo number_format($analytics_data['conversion_rate'] ?? 85, 1); ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <div class="srwm-insight-item">
                                <div class="srwm-insight-icon">📈</div>
                                <div class="srwm-insight-content">
                                    <h4><?php _e('Growth Trend', 'smart-restock-waitlist'); ?></h4>
                                    <p><?php _e('Monthly restock activity', 'smart-restock-waitlist'); ?></p>
                                    <div class="srwm-insight-value positive">
                                        +<?php echo number_format(($analytics_data['total_restocks'] / 10), 1); ?>% <?php _e('this month', 'smart-restock-waitlist'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pro Features Section -->
            <?php if ($this->license_manager->is_pro_active()): ?>
            <div class="srwm-pro-analytics">
                <div class="srwm-pro-header">
                    <h2><?php _e('Pro Analytics Features', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Advanced insights and reporting capabilities', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <div class="srwm-pro-features-grid">
                    <div class="srwm-pro-feature">
                        <div class="srwm-pro-icon">📊</div>
                        <h3><?php _e('Supplier Performance', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Track supplier response times and efficiency', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-pro-feature">
                        <div class="srwm-pro-icon">📈</div>
                        <h3><?php _e('Trend Analysis', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Identify patterns and predict future demand', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-pro-feature">
                        <div class="srwm-pro-icon">🎯</div>
                        <h3><?php _e('Conversion Tracking', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Monitor waitlist to purchase conversion rates', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-pro-feature">
                        <div class="srwm-pro-icon">📋</div>
                        <h3><?php _e('Custom Reports', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Generate detailed reports for stakeholders', 'smart-restock-waitlist'); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        /* Analytics Dashboard Styles */
        .srwm-analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .srwm-header-content h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .srwm-header-content p {
            margin: 0;
            color: #6b7280;
            font-size: 16px;
        }
        
        .srwm-header-actions {
            display: flex;
            gap: 12px;
        }
        
        /* Metrics Grid */
        .srwm-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .srwm-metric-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .srwm-metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .srwm-metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        
        .srwm-metric-icon {
            font-size: 32px;
            margin-bottom: 16px;
            display: block;
        }
        
        .srwm-metric-content h3 {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-metric-number {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 8px 0;
            line-height: 1;
        }
        
        .srwm-metric-unit {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .srwm-metric-trend {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .srwm-metric-trend.positive {
            color: #059669;
        }
        
        .srwm-metric-trend.negative {
            color: #dc2626;
        }
        
        .srwm-metric-trend.neutral {
            color: #6b7280;
        }
        
        .srwm-trend-icon {
            font-size: 14px;
        }
        
        /* Analytics Grid */
        .srwm-analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .srwm-analytics-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .srwm-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e2e8f0;
        }
        
        .srwm-card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-card-content {
            padding: 24px;
        }
        
        /* Table Styles */
        .srwm-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .srwm-table th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .srwm-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .srwm-table tr:hover {
            background: #f9fafb;
        }
        
        .srwm-product-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .srwm-product-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-product-sku {
            font-size: 12px;
            color: #6b7280;
        }
        
        .srwm-stat-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
        
        .srwm-stat-badge .srwm-stat-number {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
        }
        
        .srwm-stat-badge .srwm-stat-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-status-badge.in-stock {
            background: #dcfce7;
            color: #166534;
        }
        
        .srwm-status-badge.out-of-stock {
            background: #fef2f2;
            color: #dc2626;
        }
        
        /* Insights Grid */
        .srwm-insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .srwm-insight-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .srwm-insight-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .srwm-insight-content h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        
        .srwm-insight-content p {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .srwm-insight-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-insight-value.positive {
            color: #059669;
        }
        
        /* Pro Features Section */
        .srwm-pro-analytics {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .srwm-pro-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .srwm-pro-header h2 {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 8px 0;
        }
        
        .srwm-pro-header p {
            color: #6b7280;
            margin: 0;
        }
        
        .srwm-pro-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .srwm-pro-feature {
            text-align: center;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .srwm-pro-icon {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
        }
        
        .srwm-pro-feature h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 8px 0;
        }
        
        .srwm-pro-feature p {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
            line-height: 1.5;
        }
        
        /* Button Styles */
        .srwm-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .srwm-btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .srwm-btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-btn-outline {
            background: transparent;
            color: #6b7280;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-btn-outline:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }
        
        .srwm-btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .srwm-btn-icon {
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .srwm-analytics-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .srwm-metrics-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .srwm-analytics-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .srwm-metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-insights-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-pro-features-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-card-header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Get WooCommerce product categories
     */
    private function get_product_categories() {
        $categories = array();
        
        if (taxonomy_exists('product_cat')) {
            $terms = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[$term->slug] = $term->name;
                }
            }
        }
        
        // Add some default categories if no WooCommerce categories exist
        if (empty($categories)) {
            $categories = array(
                'electronics' => __('Electronics', 'smart-restock-waitlist'),
                'clothing' => __('Fashion & Apparel', 'smart-restock-waitlist'),
                'home' => __('Home & Garden', 'smart-restock-waitlist'),
                'automotive' => __('Automotive', 'smart-restock-waitlist'),
                'health' => __('Health & Beauty', 'smart-restock-waitlist'),
                'sports' => __('Sports & Outdoors', 'smart-restock-waitlist'),
                'books' => __('Books & Media', 'smart-restock-waitlist'),
                'toys' => __('Toys & Games', 'smart-restock-waitlist'),
                'food' => __('Food & Beverages', 'smart-restock-waitlist'),
                'other' => __('Other', 'smart-restock-waitlist')
            );
        }
        
        return $categories;
    }
    
    /**
     * Render Supplier Management page
     */
    public function render_suppliers_page() {
        $categories = $this->get_product_categories();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <i class="fas fa-users"></i>
                <?php _e('Supplier Management', 'smart-restock-waitlist'); ?>
            </h1>
            
            <!-- Horizontal Tabs Navigation -->
            <div class="srwm-tabs-navigation">
                <button class="srwm-tab-button active" data-tab="suppliers">
                    <i class="fas fa-users"></i>
                    <?php _e('Manage Suppliers', 'smart-restock-waitlist'); ?>
                </button>
                <button class="srwm-tab-button" data-tab="csv-upload">
                    <i class="fas fa-upload"></i>
                    <?php _e('CSV Upload', 'smart-restock-waitlist'); ?>
                </button>
                <button class="srwm-tab-button" data-tab="quick-restock">
                    <i class="fas fa-bolt"></i>
                    <?php _e('Quick Restock', 'smart-restock-waitlist'); ?>
                </button>
            </div>
            
            <!-- Tab Content Container -->
            <div class="srwm-tab-content-container">
                <!-- Suppliers Tab -->
                <div class="srwm-tab-content active" id="suppliers-tab">
                    <div class="srwm-suppliers-container">
                        <!-- Header with Search and Filters -->
                        <div class="srwm-suppliers-header">
                            <div class="srwm-search-filters">
                                <div class="srwm-search-box">
                                    <input type="text" id="supplier-search" placeholder="<?php _e('Search suppliers...', 'smart-restock-waitlist'); ?>">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="srwm-filters">
                                    <select id="category-filter">
                                        <option value=""><?php _e('All Categories', 'smart-restock-waitlist'); ?></option>
                                        <?php foreach ($categories as $slug => $name): ?>
                                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="status-filter">
                                        <option value=""><?php _e('All Status', 'smart-restock-waitlist'); ?></option>
                                        <option value="active"><?php _e('Active', 'smart-restock-waitlist'); ?></option>
                                        <option value="inactive"><?php _e('Inactive', 'smart-restock-waitlist'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <button class="button button-primary" id="add-supplier-btn">
                                <i class="fas fa-plus"></i>
                                <?php _e('Add New Supplier', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                        
                        <!-- Suppliers Grid -->
                        <div class="srwm-suppliers-grid" id="suppliers-grid">
                            <div class="srwm-loading">
                                <span class="spinner is-active"></span>
                                <?php _e('Loading suppliers...', 'smart-restock-waitlist'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- CSV Upload Tab -->
                <div class="srwm-tab-content" id="csv-upload-tab">
                    <div class="srwm-csv-upload-section">
                    <div class="srwm-section-header">
                        <h2><i class="fas fa-upload"></i> <?php _e('CSV Upload Management', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-section-actions">
                            <button class="button button-primary" id="download-template-btn">
                                <i class="fas fa-download"></i> <?php _e('Download Template', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- CSV Upload Info Cards -->
                    <div class="srwm-info-cards">
                        <div class="srwm-info-card">
                            <div class="srwm-info-icon">
                                <i class="fas fa-link"></i>
                            </div>
                            <div class="srwm-info-content">
                                <h4><?php _e('Generate Upload Links', 'smart-restock-waitlist'); ?></h4>
                                <p><?php _e('Generate secure upload links for suppliers to update multiple products via CSV.', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                        
                        <div class="srwm-info-card">
                            <div class="srwm-info-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="srwm-info-content">
                                <h4><?php _e('Approval Required', 'smart-restock-waitlist'); ?></h4>
                                <p><?php _e('CSV uploads require admin approval before stock is updated. Review uploads in the CSV Approvals tab.', 'smart-restock-waitlist'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-csv-approvals'); ?>" class="srwm-link-btn">
                                    <?php _e('View Pending Approvals', 'smart-restock-waitlist'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CSV Format Requirements -->
                    <div class="srwm-format-requirements">
                        <h3><i class="fas fa-file-csv"></i> <?php _e('CSV Format Requirements', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-requirements-grid">
                            <div class="srwm-requirement">
                                <i class="fas fa-check-circle"></i>
                                <span><?php _e('File must be in CSV format', 'smart-restock-waitlist'); ?></span>
                            </div>
                            <div class="srwm-requirement">
                                <i class="fas fa-check-circle"></i>
                                <span><?php _e('Required columns: Product ID, Quantity', 'smart-restock-waitlist'); ?></span>
                            </div>
                            <div class="srwm-requirement">
                                <i class="fas fa-check-circle"></i>
                                <span><?php _e('Optional columns: SKU, Notes', 'smart-restock-waitlist'); ?></span>
                            </div>
                            <div class="srwm-requirement">
                                <i class="fas fa-check-circle"></i>
                                <span><?php _e('Maximum file size: 10MB', 'smart-restock-waitlist'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Links Table -->
                    <div class="srwm-upload-links-section">
                        <div class="srwm-table-header">
                            <h3><i class="fas fa-list"></i> <?php _e('Generated Upload Links', 'smart-restock-waitlist'); ?></h3>
                            <div class="srwm-table-actions">
                                <button class="button button-secondary" id="refresh-links-btn">
                                    <i class="fas fa-sync-alt"></i> <?php _e('Refresh', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="srwm-table-container">
                            <table class="srwm-upload-links-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Upload Link', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Expires', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Uploads', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="upload-links-tbody">
                                    <tr>
                                        <td colspan="6" class="srwm-loading">
                                            <span class="spinner is-active"></span>
                                            <?php _e('Loading upload links...', 'smart-restock-waitlist'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Restock Tab -->
                <div class="srwm-tab-content" id="quick-restock-tab">
                    <div class="srwm-quick-restock-section">
                        <div class="srwm-section-header">
                            <h2><i class="fas fa-bolt"></i> <?php _e('Quick Restock Operations', 'smart-restock-waitlist'); ?></h2>
                            <div class="srwm-section-actions">
                                <button class="button button-primary" id="generate-quick-restock-btn">
                                    <i class="fas fa-plus"></i> <?php _e('Generate Quick Restock Link', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                    
                    <!-- Quick Restock Info Cards -->
                    <div class="srwm-info-cards">
                        <div class="srwm-info-card">
                            <div class="srwm-info-icon">
                                <i class="fas fa-link"></i>
                            </div>
                            <div class="srwm-info-content">
                                <h4><?php _e('Individual Product Restock', 'smart-restock-waitlist'); ?></h4>
                                <p><?php _e('Generate quick restock links for individual products. Perfect for one-time restock operations.', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                        
                        <div class="srwm-info-card">
                            <div class="srwm-info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="srwm-info-content">
                                <h4><?php _e('Instant Stock Updates', 'smart-restock-waitlist'); ?></h4>
                                <p><?php _e('Suppliers can update stock immediately without admin approval. Ideal for trusted suppliers.', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Restock Links Table -->
                    <div class="srwm-quick-restock-links-section">
                        <div class="srwm-table-header">
                            <h3><i class="fas fa-list"></i> <?php _e('Quick Restock Links', 'smart-restock-waitlist'); ?></h3>
                            <div class="srwm-table-actions">
                                <button class="button button-secondary" id="refresh-quick-links-btn">
                                    <i class="fas fa-sync-alt"></i> <?php _e('Refresh', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="srwm-table-container">
                            <table class="srwm-quick-restock-links-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Restock Link', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Expires', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                        <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="quick-restock-links-tbody">
                                    <tr>
                                        <td colspan="6" class="srwm-loading">
                                            <span class="spinner is-active"></span>
                                            <?php _e('Loading quick restock links...', 'smart-restock-waitlist'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Quick Restock Form -->
                    <div class="srwm-quick-restock-form">
                        <div class="srwm-form-section">
                            <h3><i class="fas fa-cog"></i> <?php _e('Quick Restock Configuration', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Generate secure, time-limited restock links for individual products. Suppliers can update stock immediately without admin approval.', 'smart-restock-waitlist'); ?></p>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Supplier Modal -->
        <div id="supplier-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <div class="srwm-modal-header">
                    <h3 id="modal-title"><?php _e('Add New Supplier', 'smart-restock-waitlist'); ?></h3>
                    <button class="srwm-modal-close">&times;</button>
                </div>
                <div class="srwm-modal-body">
                    <form id="supplier-form">
                        <input type="hidden" id="supplier-id" name="supplier_id" value="">
                        
                        <div class="srwm-form-row">
                            <div class="srwm-form-group">
                                <label for="supplier-name"><?php _e('Contact Person Name *', 'smart-restock-waitlist'); ?></label>
                                <input type="text" id="supplier-name" name="supplier_name" required>
                            </div>
                            <div class="srwm-form-group">
                                <label for="company-name"><?php _e('Company Name', 'smart-restock-waitlist'); ?></label>
                                <input type="text" id="company-name" name="company_name">
                            </div>
                        </div>
                        
                        <div class="srwm-form-row">
                            <div class="srwm-form-group">
                                <label for="supplier-email"><?php _e('Email Address *', 'smart-restock-waitlist'); ?></label>
                                <input type="email" id="supplier-email" name="supplier_email" required>
                            </div>
                            <div class="srwm-form-group">
                                <label for="supplier-phone"><?php _e('Phone Number', 'smart-restock-waitlist'); ?></label>
                                <input type="tel" id="supplier-phone" name="phone">
                            </div>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label for="supplier-address"><?php _e('Address', 'smart-restock-waitlist'); ?></label>
                            <textarea id="supplier-address" name="address" rows="3"></textarea>
                        </div>
                        
                        <div class="srwm-form-row">
                            <div class="srwm-form-group">
                                <label for="contact-person"><?php _e('Contact Person', 'smart-restock-waitlist'); ?></label>
                                <input type="text" id="contact-person" name="contact_person">
                            </div>
                            <div class="srwm-form-group">
                                <label for="supplier-category"><?php _e('Category', 'smart-restock-waitlist'); ?></label>
                                <select id="supplier-category" name="category">
                                    <option value=""><?php _e('Select Category', 'smart-restock-waitlist'); ?></option>
                                    <?php foreach ($categories as $slug => $name): ?>
                                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="srwm-form-row">
                            <div class="srwm-form-group">
                                <label for="supplier-threshold"><?php _e('Stock Threshold', 'smart-restock-waitlist'); ?></label>
                                <input type="number" id="supplier-threshold" name="threshold" min="0" value="5">
                            </div>
                            <div class="srwm-form-group" id="status-group" style="display: none;">
                                <label for="supplier-status"><?php _e('Status', 'smart-restock-waitlist'); ?></label>
                                <select id="supplier-status" name="status">
                                    <option value="active"><?php _e('Active', 'smart-restock-waitlist'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'smart-restock-waitlist'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label for="supplier-notes"><?php _e('Notes', 'smart-restock-waitlist'); ?></label>
                            <textarea id="supplier-notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="srwm-modal-footer">
                    <button type="button" class="button" id="cancel-supplier"><?php _e('Cancel', 'smart-restock-waitlist'); ?></button>
                    <button type="button" class="button button-primary" id="save-supplier"><?php _e('Save Supplier', 'smart-restock-waitlist'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div id="delete-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <div class="srwm-modal-header">
                    <h3><?php _e('Delete Supplier', 'smart-restock-waitlist'); ?></h3>
                    <button class="srwm-modal-close">&times;</button>
                </div>
                <div class="srwm-modal-body">
                    <p><?php _e('Are you sure you want to delete this supplier? This action cannot be undone.', 'smart-restock-waitlist'); ?></p>
                </div>
                <div class="srwm-modal-footer">
                    <button type="button" class="button" id="cancel-delete"><?php _e('Cancel', 'smart-restock-waitlist'); ?></button>
                    <button type="button" class="button button-primary button-danger" id="confirm-delete"><?php _e('Delete', 'smart-restock-waitlist'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Upload Link Generation Modal -->
        <div id="upload-link-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <div class="srwm-modal-header">
                    <h3><?php _e('Generate Upload Link', 'smart-restock-waitlist'); ?></h3>
                    <button class="srwm-modal-close">&times;</button>
                </div>
                <div class="srwm-modal-body">
                    <div id="upload-link-loading" style="display: none;">
                        <div class="srwm-loading">
                            <span class="spinner is-active"></span>
                            <?php _e('Generating upload link...', 'smart-restock-waitlist'); ?>
                        </div>
                    </div>
                    <div id="upload-link-content">
                        <div class="srwm-upload-link-info">
                            <div class="srwm-info-card">
                                <h4><i class="fas fa-info-circle"></i> <?php _e('Upload Link Details', 'smart-restock-waitlist'); ?></h4>
                                <ul>
                                    <li><strong><?php _e('Link Duration:', 'smart-restock-waitlist'); ?></strong> <?php _e('7 days', 'smart-restock-waitlist'); ?></li>
                                    <li><strong><?php _e('Email Notification:', 'smart-restock-waitlist'); ?></strong> <?php _e('Automatically sent to supplier', 'smart-restock-waitlist'); ?></li>
                                    <li><strong><?php _e('File Format:', 'smart-restock-waitlist'); ?></strong> <?php _e('CSV, Excel (.xlsx, .xls)', 'smart-restock-waitlist'); ?></li>
                                    <li><strong><?php _e('Max File Size:', 'smart-restock-waitlist'); ?></strong> <?php _e('10MB', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                        <div id="upload-link-result" style="display: none;">
                            <div class="srwm-success-card">
                                <h4><i class="fas fa-check-circle"></i> <?php _e('Upload Link Generated!', 'smart-restock-waitlist'); ?></h4>
                                <div class="srwm-link-details">
                                    <p><strong><?php _e('Supplier:', 'smart-restock-waitlist'); ?></strong> <span id="link-supplier-name"></span></p>
                                    <p><strong><?php _e('Email:', 'smart-restock-waitlist'); ?></strong> <span id="link-supplier-email"></span></p>
                                    <p><strong><?php _e('Expires:', 'smart-restock-waitlist'); ?></strong> <span id="link-expires"></span></p>
                                </div>
                                <div class="srwm-link-url">
                                    <label><?php _e('Upload Link:', 'smart-restock-waitlist'); ?></label>
                                    <div class="srwm-url-copy">
                                        <input type="text" id="generated-upload-url" readonly>
                                        <button type="button" class="button button-primary" id="copy-link-btn">
                                            <i class="fas fa-copy"></i> <?php _e('Copy', 'smart-restock-waitlist'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="srwm-modal-footer">
                    <button type="button" class="button" id="cancel-upload-link"><?php _e('Cancel', 'smart-restock-waitlist'); ?></button>
                    <button type="button" class="button button-primary" id="generate-upload-link-btn"><?php _e('Generate Link', 'smart-restock-waitlist'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Quick Restock Generation Modal -->
        <div id="quick-restock-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content">
                <div class="srwm-modal-header">
                    <h3><?php _e('Generate Quick Restock Link', 'smart-restock-waitlist'); ?></h3>
                    <button class="srwm-modal-close">&times;</button>
                </div>
                <div class="srwm-modal-body">
                    <div id="quick-restock-loading" style="display: none;">
                        <div class="srwm-loading">
                            <span class="spinner is-active"></span>
                            <?php _e('Generating quick restock link...', 'smart-restock-waitlist'); ?>
                        </div>
                    </div>
                    <div id="quick-restock-content">
                        <form id="quick-restock-form">
                            <div class="srwm-form-group">
                                <label for="quick-restock-product"><?php _e('Select Product *', 'smart-restock-waitlist'); ?></label>
                                <select id="quick-restock-product" name="product_id" required>
                                    <option value=""><?php _e('Choose a product...', 'smart-restock-waitlist'); ?></option>
                                </select>
                            </div>
                            
                            <div class="srwm-form-group">
                                <label for="quick-restock-supplier"><?php _e('Select Supplier *', 'smart-restock-waitlist'); ?></label>
                                <select id="quick-restock-supplier" name="supplier_email" required>
                                    <option value=""><?php _e('Choose a supplier...', 'smart-restock-waitlist'); ?></option>
                                </select>
                            </div>
                            
                            <div class="srwm-form-group">
                                <label for="quick-restock-expires"><?php _e('Link Expiration', 'smart-restock-waitlist'); ?></label>
                                <select id="quick-restock-expires" name="expires">
                                    <option value="1"><?php _e('1 day', 'smart-restock-waitlist'); ?></option>
                                    <option value="3"><?php _e('3 days', 'smart-restock-waitlist'); ?></option>
                                    <option value="7" selected><?php _e('7 days', 'smart-restock-waitlist'); ?></option>
                                    <option value="14"><?php _e('14 days', 'smart-restock-waitlist'); ?></option>
                                    <option value="30"><?php _e('30 days', 'smart-restock-waitlist'); ?></option>
                                </select>
                            </div>
                        </form>
                        
                        <div class="srwm-info-card">
                            <h4><i class="fas fa-info-circle"></i> <?php _e('Quick Restock Details', 'smart-restock-waitlist'); ?></h4>
                            <ul>
                                <li><strong><?php _e('Instant Updates:', 'smart-restock-waitlist'); ?></strong> <?php _e('Stock updates immediately without approval', 'smart-restock-waitlist'); ?></li>
                                <li><strong><?php _e('Email Notification:', 'smart-restock-waitlist'); ?></strong> <?php _e('Automatically sent to supplier', 'smart-restock-waitlist'); ?></li>
                                <li><strong><?php _e('Secure Access:', 'smart-restock-waitlist'); ?></strong> <?php _e('Unique token-based authentication', 'smart-restock-waitlist'); ?></li>
                                <li><strong><?php _e('Audit Trail:', 'smart-restock-waitlist'); ?></strong> <?php _e('All restock actions are logged', 'smart-restock-waitlist'); ?></li>
                            </ul>
                        </div>
                        
                        <div id="quick-restock-result" style="display: none;">
                            <div class="srwm-success-card">
                                <h4><i class="fas fa-check-circle"></i> <?php _e('Quick Restock Link Generated!', 'smart-restock-waitlist'); ?></h4>
                                <div class="srwm-link-details">
                                    <p><strong><?php _e('Product:', 'smart-restock-waitlist'); ?></strong> <span id="quick-link-product-name"></span></p>
                                    <p><strong><?php _e('Supplier:', 'smart-restock-waitlist'); ?></strong> <span id="quick-link-supplier-name"></span></p>
                                    <p><strong><?php _e('Expires:', 'smart-restock-waitlist'); ?></strong> <span id="quick-link-expires"></span></p>
                                </div>
                                <div class="srwm-link-url">
                                    <label><?php _e('Restock Link:', 'smart-restock-waitlist'); ?></label>
                                    <div class="srwm-url-copy">
                                        <input type="text" id="generated-quick-restock-url" readonly>
                                        <button type="button" class="button button-primary" id="copy-quick-link-btn">
                                            <i class="fas fa-copy"></i> <?php _e('Copy', 'smart-restock-waitlist'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="srwm-modal-footer">
                    <button type="button" class="button" id="cancel-quick-restock"><?php _e('Cancel', 'smart-restock-waitlist'); ?></button>
                    <button type="button" class="button button-primary" id="generate-quick-restock-link-btn"><?php _e('Generate Link', 'smart-restock-waitlist'); ?></button>
                </div>
            </div>
        </div>
        
        <style>
        /* Horizontal Tabs Navigation */
        .srwm-tabs-navigation {
            display: flex;
            background: white;
            border-radius: 12px 12px 0 0;
            border: 1px solid #e2e8f0;
            border-bottom: none;
            margin-top: 20px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .srwm-tab-button {
            flex: 1;
            background: #f8fafc;
            border: none;
            padding: 16px 24px;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-right: 1px solid #e2e8f0;
        }
        
        .srwm-tab-button:last-child {
            border-right: none;
        }
        
        .srwm-tab-button:hover {
            background: #f1f5f9;
            color: #475569;
        }
        
        .srwm-tab-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .srwm-tab-button.active i {
            color: white;
        }
        
        .srwm-tab-button i {
            font-size: 16px;
            color: #64748b;
        }
        
        .srwm-tab-content-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-tab-content {
            display: none;
            padding: 20px;
        }
        
        .srwm-tab-content.active {
            display: block !important;
        }
        
        /* Ensure tab content is visible */
        #quick-restock-tab {
            min-height: 200px;
        }
        
        #quick-restock-tab.active {
            display: block !important;
            visibility: visible !important;
        }
        
        .srwm-suppliers-container {
            margin-top: 0;
        }
        
        /* Quick Restock Section Styles */
        .srwm-quick-restock-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .srwm-quick-restock-form {
            padding: 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        
        .srwm-form-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .srwm-form-section h3 {
            margin: 0 0 12px 0;
            color: #374151;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .srwm-form-section p {
            margin: 0;
            color: #6b7280;
            line-height: 1.5;
        }
        
        /* Light theme for form inputs */
        .srwm-form-group input,
        .srwm-form-group select,
        .srwm-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #ffffff !important;
            color: #1f2937 !important;
        }
        
        .srwm-form-group input:focus,
        .srwm-form-group select:focus,
        .srwm-form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff !important;
            color: #1f2937 !important;
        }
        
        .srwm-form-group input::placeholder,
        .srwm-form-group textarea::placeholder {
            color: #9ca3af !important;
        }
        
        .srwm-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        /* Modal styling improvements */
        .srwm-modal-content {
            background: #ffffff !important;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .srwm-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            padding: 24px;
            margin: 0;
            border-bottom: none;
        }
        
        .srwm-modal-header h3 {
            margin: 0;
            color: white !important;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .srwm-modal-close {
            background: rgba(255, 255, 255, 0.2) !important;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white !important;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .srwm-modal-close:hover {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }
        
        .srwm-modal-body {
            padding: 24px;
            background: #ffffff !important;
        }
        
        .srwm-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 24px;
            border-top: 1px solid #e2e8f0;
            margin-top: 0;
            background: #f8fafc;
            border-radius: 0 0 12px 12px;
        }
        
        /* Button styling */
        .srwm-modal-footer .button {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }
        
        .srwm-modal-footer .button-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .srwm-modal-footer .button-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-modal-footer .button:not(.button-primary) {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .srwm-modal-footer .button:not(.button-primary):hover {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .button-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            border-color: #dc2626 !important;
            color: white !important;
        }
        
        .button-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .srwm-suppliers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-search-filters {
            display: flex;
            gap: 20px;
            align-items: center;
            flex: 1;
        }
        
        .srwm-search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        
        .srwm-search-box input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .srwm-search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .srwm-search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .srwm-filters {
            display: flex;
            gap: 12px;
        }
        
        .srwm-filters select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            background: white;
            font-size: 14px;
            min-width: 140px;
        }
        
        .srwm-filters select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        #add-supplier-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        #add-supplier-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .srwm-suppliers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .srwm-supplier-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        
        .srwm-supplier-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        
        .supplier-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .supplier-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .supplier-info h3 {
            margin: 0 0 4px 0;
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .supplier-company {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0 0 4px 0;
        }
        
        .supplier-category {
            color: #3b82f6;
            font-size: 0.8rem;
            margin: 0 0 8px 0;
            font-weight: 500;
            background: rgba(59, 130, 246, 0.1);
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .supplier-email {
            color: #3b82f6;
            font-size: 0.9rem;
            margin: 0;
            text-decoration: none;
        }
        
        .supplier-email:hover {
            text-decoration: underline;
        }
        
        .supplier-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .supplier-stat {
            text-align: center;
            padding: 12px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .supplier-stat .number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .supplier-stat .label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .supplier-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .supplier-action-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .supplier-action-btn.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .supplier-action-btn.secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .supplier-action-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .supplier-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .supplier-status {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .supplier-status.active {
            background: #dcfce7;
            color: #166534;
        }
        
        .supplier-status.inactive {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .srwm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        

        
        .srwm-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .srwm-form-group {
            margin-bottom: 20px;
        }
        

        
        .button-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            border-color: #dc2626 !important;
        }
        
        .srwm-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 40px;
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        /* Professional improvements */
        .srwm-empty {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #d1d5db;
        }
        
        .srwm-error {
            text-align: center;
            padding: 20px;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        /* Enhanced search box */
        .srwm-search-box input {
            background: #ffffff !important;
            color: #1f2937 !important;
        }
        
        .srwm-search-box input::placeholder {
            color: #9ca3af !important;
        }
        
        /* Enhanced filter dropdowns */
        .srwm-filters select {
            background: #ffffff !important;
            color: #1f2937 !important;
        }
        
        /* Professional card improvements */
        .srwm-supplier-card {
            border: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }
        
        .srwm-supplier-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.15);
        }
        
        /* Professional header */
        .srwm-suppliers-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
        }
        
        /* Upload Link Modal Styles */
        .srwm-info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .srwm-info-card h4 {
            margin: 0 0 15px 0;
            color: #1f2937;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .srwm-info-card h4 i {
            color: #3b82f6;
        }
        
        .srwm-info-card ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .srwm-info-card li {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #4b5563;
        }
        
        .srwm-info-card li:last-child {
            border-bottom: none;
        }
        
        .srwm-success-card {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .srwm-success-card h4 {
            margin: 0 0 15px 0;
            color: #166534;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .srwm-success-card h4 i {
            color: #16a34a;
        }
        
        .srwm-link-details {
            margin-bottom: 20px;
        }
        
        .srwm-link-details p {
            margin: 8px 0;
            color: #166534;
        }
        
        .srwm-link-url label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #166534;
        }
        
        .srwm-url-copy {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .srwm-url-copy input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #86efac;
            border-radius: 6px;
            background: white;
            color: #1f2937;
            font-size: 13px;
            font-family: monospace;
        }
        
        .srwm-url-copy input:focus {
            outline: none;
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        
        #copy-link-btn {
            padding: 10px 16px;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        #copy-link-btn:hover {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }
        
        #copy-link-btn.copied {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        /* CSV Upload Section Styles */
        .srwm-csv-upload-section {
            margin-top: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .srwm-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .srwm-section-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .srwm-section-header h2 i {
            font-size: 1.2rem;
        }
        
        .srwm-section-actions .button {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .srwm-section-actions .button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
        
        /* Info Cards */
        .srwm-info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 24px;
            background: #f8fafc;
        }
        
        .srwm-info-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all 0.3s ease;
        }
        
        .srwm-info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-info-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .srwm-info-content h4 {
            margin: 0 0 8px 0;
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .srwm-info-content p {
            margin: 0 0 12px 0;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .srwm-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .srwm-link-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }
        
        /* Format Requirements */
        .srwm-format-requirements {
            padding: 24px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .srwm-format-requirements h3 {
            margin: 0 0 20px 0;
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .srwm-format-requirements h3 i {
            color: #3b82f6;
        }
        
        .srwm-requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        
        .srwm-requirement {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .srwm-requirement i {
            color: #10b981;
            font-size: 1.1rem;
        }
        
        .srwm-requirement span {
            color: #374151;
            font-weight: 500;
        }
        
        /* Upload Links Table */
        .srwm-upload-links-section {
            padding: 24px;
        }
        
        .srwm-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .srwm-table-header h3 {
            margin: 0;
            color: #1f2937;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .srwm-table-header h3 i {
            color: #3b82f6;
        }
        
        .srwm-table-actions .button {
            padding: 8px 16px;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .srwm-table-actions .button:hover {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .srwm-table-container {
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .srwm-upload-links-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .srwm-upload-links-table th {
            background: #f8fafc;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        
        .srwm-upload-links-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
            font-size: 0.9rem;
        }
        
        .srwm-upload-links-table tr:hover {
            background: #f8fafc;
        }
        
        .srwm-upload-links-table tr:last-child td {
            border-bottom: none;
        }
        
        .srwm-link-token {
            font-family: monospace;
            font-size: 0.8rem;
            color: #6b7280;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .srwm-supplier-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .srwm-supplier-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-supplier-email {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .srwm-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-status-badge.active {
            background: #dcfce7;
            color: #166534;
        }
        
        .srwm-status-badge.used {
            background: #fef3c7;
            color: #92400e;
        }
        
        .srwm-status-badge.expired {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .srwm-upload-count {
            text-align: center;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-table-actions {
            display: flex;
            gap: 8px;
        }
        
        .srwm-table-action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .srwm-table-action-btn.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .srwm-table-action-btn.secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .srwm-table-action-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .srwm-table-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-table-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .srwm-table-action-btn i {
            font-size: 14px;
            display: inline-block;
        }
        
        /* Highlight newly generated links */
        .srwm-new-link {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
            border-left: 4px solid #f59e0b;
            animation: highlightNewLink 2s ease-in-out;
        }
        
        @keyframes highlightNewLink {
            0% { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
            50% { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
            100% { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        }
        
        @media (max-width: 768px) {
            .srwm-suppliers-header {
                flex-direction: column;
                gap: 20px;
            }
            
            .srwm-search-filters {
                flex-direction: column;
                width: 100%;
            }
            
            .srwm-suppliers-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-form-row {
                grid-template-columns: 1fr;
            }
            
            .supplier-stats {
                grid-template-columns: 1fr;
            }
            
            .srwm-url-copy {
                flex-direction: column;
                gap: 8px;
            }
            
            .srwm-url-copy input {
                font-size: 12px;
            }
            
            #copy-link-btn {
                width: 100%;
                justify-content: center;
            }
            
            /* CSV Upload Section Mobile */
            .srwm-section-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .srwm-info-cards {
                grid-template-columns: 1fr;
            }
            
            .srwm-requirements-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-table-header {
                flex-direction: column;
                gap: 16px;
            }
            
            .srwm-upload-links-table {
                font-size: 0.8rem;
            }
            
            .srwm-upload-links-table th,
            .srwm-upload-links-table td {
                padding: 8px 6px;
            }
            
            .srwm-link-token {
                max-width: 120px;
                font-size: 0.7rem;
            }
            
            .srwm-table-actions {
                flex-direction: column;
                gap: 4px;
            }
            
            .srwm-table-action-btn {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let currentSupplierId = null;
            let categories = <?php echo json_encode($categories); ?>;
            
            function getCategoryName(categorySlug) {
                return categories[categorySlug] || categorySlug;
            }
            
            // Tab switching functionality
            $('.srwm-tab-button').on('click', function() {
                const tabId = $(this).data('tab');
                console.log('Tab clicked:', tabId);
                
                // Remove active class from all tabs and buttons
                $('.srwm-tab-button').removeClass('active');
                $('.srwm-tab-content').removeClass('active');
                
                // Add active class to clicked button and corresponding content
                $(this).addClass('active');
                const targetTab = $('#' + tabId + '-tab');
                targetTab.addClass('active');
                
                console.log('Target tab found:', targetTab.length > 0);
                console.log('Target tab content:', targetTab.html().substring(0, 100) + '...');
            });
            
            // Load suppliers on page load
            loadSuppliers();
            
            // Load upload links on page load
            loadUploadLinks();
            
            // Load quick restock links on page load
            loadQuickRestockLinks();
            
            // Debug: Check if Quick Restock tab content exists
            console.log('Quick Restock tab exists:', $('#quick-restock-tab').length > 0);
            console.log('Quick Restock tab content length:', $('#quick-restock-tab').html().length);
            console.log('Quick Restock tab HTML:', $('#quick-restock-tab').html().substring(0, 200));
            
            // Force show Quick Restock tab for testing
            setTimeout(function() {
                console.log('Testing Quick Restock tab visibility...');
                $('#quick-restock-tab').show().addClass('active');
                console.log('Quick Restock tab display:', $('#quick-restock-tab').css('display'));
                console.log('Quick Restock tab visibility:', $('#quick-restock-tab').css('visibility'));
            }, 1000);
            
            // Search and filter functionality
            $('#supplier-search').on('input', debounce(loadSuppliers, 300));
            $('#category-filter, #status-filter').on('change', loadSuppliers);
            
            // Add supplier button
            $('#add-supplier-btn').on('click', function() {
                openSupplierModal();
            });
            
            // Modal close buttons
            $('.srwm-modal-close, #cancel-supplier, #cancel-delete').on('click', function() {
                closeAllModals();
            });
            
            // Save supplier
            $('#save-supplier').on('click', function() {
                saveSupplier();
            });
            
            // Confirm delete
            $('#confirm-delete').on('click', function() {
                deleteSupplier();
            });
            
            // Upload link generation
            $('#generate-upload-link-btn').on('click', function() {
                generateUploadLink();
            });
            
            // Copy link button
            $('#copy-link-btn').on('click', function() {
                copyUploadLink();
            });
            
            // Cancel upload link
            $('#cancel-upload-link').on('click', function() {
                closeAllModals();
            });
            
            // Download template
            $('#download-template-btn').on('click', function() {
                downloadCSVTemplate();
            });
            
            // Refresh upload links
            $('#refresh-links-btn').on('click', function() {
                loadUploadLinks();
            });
            
            // Quick Restock functionality
            $('#generate-quick-restock-btn').on('click', function() {
                openQuickRestockModal();
            });
            
            // Refresh quick restock links
            $('#refresh-quick-links-btn').on('click', function() {
                loadQuickRestockLinks();
            });
            
            // Quick restock modal events
            $('#cancel-quick-restock').on('click', function() {
                closeAllModals();
            });
            
            $('#generate-quick-restock-link-btn').on('click', function() {
                generateQuickRestockLink();
            });
            
            $('#copy-quick-link-btn').on('click', function() {
                copyQuickRestockLink();
            });
            
            // Close modal on outside click
            $('.srwm-modal').on('click', function(e) {
                if (e.target === this) {
                    closeAllModals();
                }
            });
            
            function loadSuppliers() {
                const search = $('#supplier-search').val();
                const category = $('#category-filter').val();
                const status = $('#status-filter').val();
                
                $('#suppliers-grid').html('<div class="srwm-loading"><span class="spinner is-active"></span> Loading suppliers...</div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_suppliers',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        search: search,
                        category: category,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            displaySuppliers(response.data.suppliers);
                        } else {
                            $('#suppliers-grid').html('<div class="srwm-error">Error loading suppliers: ' + response.data + '</div>');
                        }
                    },
                    error: function() {
                        $('#suppliers-grid').html('<div class="srwm-error">Error loading suppliers. Please try again.</div>');
                    }
                });
            }
            
            function displaySuppliers(suppliers) {
                if (suppliers.length === 0) {
                    $('#suppliers-grid').html('<div class="srwm-empty">No suppliers found. <button class="button button-primary" onclick="openSupplierModal()">Add your first supplier</button></div>');
                    return;
                }
                
                let html = '';
                suppliers.forEach(function(supplier) {
                    const avatarText = supplier.supplier_name.charAt(0).toUpperCase();
                    const lastUpload = supplier.last_upload ? new Date(supplier.last_upload).toLocaleDateString() : 'Never';
                    const categoryName = supplier.category ? getCategoryName(supplier.category) : 'No category';
                    
                    html += `
                        <div class="srwm-supplier-card">
                            <div class="supplier-status ${supplier.status}">${supplier.status}</div>
                            <div class="supplier-header">
                                <div class="supplier-avatar">${avatarText}</div>
                                <div class="supplier-info">
                                    <h3>${supplier.supplier_name}</h3>
                                    <p class="supplier-company">${supplier.company_name || 'No company name'}</p>
                                    <p class="supplier-category">${categoryName}</p>
                                    <a href="mailto:${supplier.supplier_email}" class="supplier-email">${supplier.supplier_email}</a>
                                </div>
                            </div>
                            <div class="supplier-stats">
                                <div class="supplier-stat">
                                    <span class="number">${supplier.upload_count || 0}</span>
                                    <span class="label">Uploads</span>
                                </div>
                                <div class="supplier-stat">
                                    <span class="number">${parseFloat(supplier.trust_score || 0).toFixed(1)}</span>
                                    <span class="label">Trust Score</span>
                                </div>
                            </div>
                            <div class="supplier-actions">
                                <button class="supplier-action-btn primary" onclick="generateUploadLink(${supplier.id})">
                                    <i class="fas fa-link"></i> Generate Link
                                </button>
                                <button class="supplier-action-btn secondary" onclick="editSupplier(${supplier.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="supplier-action-btn danger" onclick="deleteSupplierConfirm(${supplier.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                $('#suppliers-grid').html(html);
            }
            
            function openSupplierModal(supplierId = null) {
                currentSupplierId = supplierId;
                
                if (supplierId) {
                    // Edit mode
                    $('#modal-title').text('Edit Supplier');
                    $('#status-group').show();
                    loadSupplierData(supplierId);
                } else {
                    // Add mode
                    $('#modal-title').text('Add New Supplier');
                    $('#status-group').hide();
                    $('#supplier-form')[0].reset();
                    $('#supplier-id').val('');
                }
                
                $('#supplier-modal').show();
            }
            
            function loadSupplierData(supplierId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_supplier',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        supplier_id: supplierId
                    },
                    success: function(response) {
                        if (response.success) {
                            const supplier = response.data;
                            $('#supplier-id').val(supplier.id);
                            $('#supplier-name').val(supplier.supplier_name);
                            $('#company-name').val(supplier.company_name);
                            $('#supplier-email').val(supplier.supplier_email);
                            $('#supplier-phone').val(supplier.phone);
                            $('#supplier-address').val(supplier.address);
                            $('#contact-person').val(supplier.contact_person);
                            $('#supplier-category').val(supplier.category);
                            $('#supplier-status').val(supplier.status);
                            $('#supplier-threshold').val(supplier.threshold);
                            $('#supplier-notes').val(supplier.notes);
                        }
                    }
                });
            }
            
            function saveSupplier() {
                const formData = new FormData($('#supplier-form')[0]);
                formData.append('action', currentSupplierId ? 'srwm_update_supplier' : 'srwm_add_supplier');
                formData.append('nonce', '<?php echo wp_create_nonce('srwm_nonce'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            closeAllModals();
                            loadSuppliers();
                            showNotification(response.data.message || 'Supplier saved successfully!', 'success');
                        } else {
                            showNotification(response.data || 'Error saving supplier', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error saving supplier. Please try again.', 'error');
                    }
                });
            }
            
            function deleteSupplierConfirm(supplierId) {
                currentSupplierId = supplierId;
                $('#delete-modal').show();
            }
            
            function deleteSupplier() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_delete_supplier',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        supplier_id: currentSupplierId
                    },
                    success: function(response) {
                        if (response.success) {
                            closeAllModals();
                            loadSuppliers();
                            showNotification(response.data || 'Supplier deleted successfully!', 'success');
                        } else {
                            showNotification(response.data || 'Error deleting supplier', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error deleting supplier. Please try again.', 'error');
                    }
                });
            }
            
            function closeAllModals() {
                $('.srwm-modal').hide();
                currentSupplierId = null;
                
                // Reset upload link modal
                $('#upload-link-content').show();
                $('#upload-link-result').hide();
                $('#upload-link-loading').hide();
                $('#generate-upload-link-btn').prop('disabled', false).text('Generate Link');
            }
            
            function showNotification(message, type) {
                const notification = $(`
                    <div class="notice notice-${type} is-dismissible">
                        <p>${message}</p>
                    </div>
                `);
                
                $('.wrap h1').after(notification);
                
                setTimeout(function() {
                    notification.fadeOut();
                }, 3000);
            }
            
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
            
            // Global functions for onclick handlers
            window.openSupplierModal = openSupplierModal;
            window.editSupplier = function(supplierId) {
                openSupplierModal(supplierId);
            };
            window.deleteSupplierConfirm = deleteSupplierConfirm;
            window.generateUploadLink = function(supplierId) {
                openUploadLinkModal(supplierId);
            };
            
            // Global functions for upload link actions
            window.copyLinkToClipboard = copyLinkToClipboard;
            window.viewLinkDetails = viewLinkDetails;
            window.deleteUploadLink = deleteUploadLink;
            
            function openUploadLinkModal(supplierId) {
                currentSupplierId = supplierId;
                $('#upload-link-modal').show();
                $('#upload-link-content').show();
                $('#upload-link-result').hide();
                $('#upload-link-loading').hide();
            }
            
            function generateUploadLink() {
                if (!currentSupplierId) {
                    showNotification('No supplier selected.', 'error');
                    return;
                }
                
                $('#upload-link-loading').show();
                $('#upload-link-content').hide();
                $('#generate-upload-link-btn').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_generate_supplier_upload_link',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        supplier_id: currentSupplierId
                    },
                    success: function(response) {
                        $('#upload-link-loading').hide();
                        $('#generate-upload-link-btn').prop('disabled', false);
                        
                        if (response.success) {
                            displayUploadLinkResult(response.data);
                            showNotification(response.data.message + ' Upload links table will refresh automatically.', 'success');
                            
                            // Refresh the upload links table to show the new link
                            setTimeout(function() {
                                // Show a brief loading indicator
                                $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-loading"><span class="spinner is-active"></span> Refreshing upload links...</td></tr>');
                                loadUploadLinks();
                            }, 500);
                        } else {
                            $('#upload-link-content').show();
                            showNotification(response.data || 'Error generating upload link', 'error');
                        }
                    },
                    error: function() {
                        $('#upload-link-loading').hide();
                        $('#upload-link-content').show();
                        $('#generate-upload-link-btn').prop('disabled', false);
                        showNotification('Error generating upload link. Please try again.', 'error');
                    }
                });
            }
            
            function displayUploadLinkResult(data) {
                $('#link-supplier-name').text(data.supplier_name);
                $('#link-supplier-email').text(data.supplier_email);
                $('#link-expires').text(new Date(data.expires_at).toLocaleString());
                $('#generated-upload-url').val(data.upload_url);
                
                $('#upload-link-content').show();
                $('#upload-link-result').show();
                
                // Update button text
                $('#generate-upload-link-btn').text('Generate Another Link');
            }
            
            function copyUploadLink() {
                const urlInput = document.getElementById('generated-upload-url');
                urlInput.select();
                urlInput.setSelectionRange(0, 99999); // For mobile devices
                
                try {
                    document.execCommand('copy');
                    const copyBtn = $('#copy-link-btn');
                    copyBtn.addClass('copied');
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    
                    setTimeout(function() {
                        copyBtn.removeClass('copied');
                        copyBtn.html('<i class="fas fa-copy"></i> Copy');
                    }, 2000);
                    
                    showNotification('Upload link copied to clipboard!', 'success');
                } catch (err) {
                    showNotification('Failed to copy link. Please copy manually.', 'error');
                }
            }
            
            function loadUploadLinks() {
                $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-loading"><span class="spinner is-active"></span> Loading upload links...</td></tr>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_csv_upload_links',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayUploadLinks(response.data);
                        } else {
                            $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-error">Error loading upload links: ' + response.data + '</td></tr>');
                        }
                    },
                    error: function() {
                        $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-error">Error loading upload links. Please try again.</td></tr>');
                    }
                });
            }
            
            function displayUploadLinks(links) {
                console.log('Displaying upload links:', links);
                
                if (links.length === 0) {
                    $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-empty">No upload links found. Generate links from supplier cards above.</td></tr>');
                    return;
                }
                
                let html = '';
                links.forEach(function(link, index) {
                    console.log('Processing link:', link, 'used field:', link.used, 'type:', typeof link.used, 'parsed:', parseInt(link.used));
                    const expiresDate = new Date(link.expires_at);
                    const now = new Date();
                    const isExpired = expiresDate < now;
                    const status = isExpired ? 'expired' : (parseInt(link.used) === 1 ? 'used' : 'active');
                    console.log('Status determination:', { isExpired, used: parseInt(link.used), status });
                    
                    // Check if this is a newly generated link (first in the list and active)
                    const isNewLink = index === 0 && status === 'active' && parseInt(link.upload_count) === 0;
                    const newLinkClass = isNewLink ? 'srwm-new-link' : '';
                    
                    html += `
                        <tr class="${newLinkClass}">
                            <td>
                                <div class="srwm-link-token" title="${link.token}">${link.token}</div>
                            </td>
                            <td>
                                <div class="srwm-supplier-info">
                                    <div class="srwm-supplier-name">${link.supplier_name || 'Unknown'}</div>
                                    <div class="srwm-supplier-email">${link.supplier_email}</div>
                                </div>
                            </td>
                            <td>${expiresDate.toLocaleDateString()} ${expiresDate.toLocaleTimeString()}</td>
                            <td>
                                <span class="srwm-status-badge ${status}">
                                    <i class="fas fa-${status === 'active' ? 'check-circle' : (status === 'used' ? 'clock' : 'times-circle')}"></i>
                                    ${status}
                                </span>
                            </td>
                            <td class="srwm-upload-count">${link.upload_count || 0}</td>
                            <td>
                                <div class="srwm-table-actions">
                                    <button class="srwm-table-action-btn primary" onclick="copyLinkToClipboard('${link.token}')" title="Copy Link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="srwm-table-action-btn secondary" onclick="viewLinkDetails('${link.token}')" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="srwm-table-action-btn danger" onclick="deleteUploadLink('${link.token}')" title="Delete Link">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                console.log('Generated HTML for upload links table:', html);
                $('#upload-links-tbody').html(html);
            }
            
            function downloadCSVTemplate() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_download_csv_template',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Create a temporary link to download the file
                            const link = document.createElement('a');
                            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                            link.download = 'csv_upload_template.csv';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            showNotification('CSV template downloaded successfully!', 'success');
                        } else {
                            showNotification('Error downloading template: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error downloading template. Please try again.', 'error');
                    }
                });
            }
            
            function copyLinkToClipboard(token) {
                console.log('Copy link clicked for token:', token);
                const uploadUrl = '<?php echo site_url(); ?>/?srwm_csv_upload=1&token=' + token;
                console.log('Generated URL:', uploadUrl);
                
                navigator.clipboard.writeText(uploadUrl).then(function() {
                    showNotification('Upload link copied to clipboard!', 'success');
                }).catch(function() {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = uploadUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('Upload link copied to clipboard!', 'success');
                });
            }
            
            function viewLinkDetails(token) {
                console.log('View details clicked for token:', token);
                // TODO: Implement link details modal
                showNotification('Link details feature coming soon!', 'info');
            }
            
            function deleteUploadLink(token) {
                console.log('Delete link clicked for token:', token);
                if (confirm('Are you sure you want to delete this upload link? This action cannot be undone.')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'srwm_delete_upload_link',
                            nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                            token: token
                        },
                        success: function(response) {
                            if (response.success) {
                                loadUploadLinks();
                                showNotification('Upload link deleted successfully!', 'success');
                            } else {
                                showNotification('Error deleting link: ' + response.data, 'error');
                            }
                        },
                        error: function() {
                            showNotification('Error deleting link. Please try again.', 'error');
                        }
                    });
                }
            }
            
            // Quick Restock Functions
            function openQuickRestockModal() {
                $('#quick-restock-modal').show();
                $('#quick-restock-content').show();
                $('#quick-restock-result').hide();
                $('#quick-restock-loading').hide();
                loadQuickRestockProducts();
                loadQuickRestockSuppliers();
            }
            
            function loadQuickRestockProducts() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_products_for_restock',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value=""><?php _e('Choose a product...', 'smart-restock-waitlist'); ?></option>';
                            response.data.forEach(function(product) {
                                options += `<option value="${product.id}">${product.name} (SKU: ${product.sku || 'N/A'})</option>`;
                            });
                            $('#quick-restock-product').html(options);
                        }
                    }
                });
            }
            
            function loadQuickRestockSuppliers() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_suppliers',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        search: '',
                        category: '',
                        status: 'active'
                    },
                    success: function(response) {
                        if (response.success) {
                            let options = '<option value=""><?php _e('Choose a supplier...', 'smart-restock-waitlist'); ?></option>';
                            response.data.suppliers.forEach(function(supplier) {
                                options += `<option value="${supplier.supplier_email}">${supplier.supplier_name} (${supplier.supplier_email})</option>`;
                            });
                            $('#quick-restock-supplier').html(options);
                        }
                    }
                });
            }
            
            function generateQuickRestockLink() {
                const productId = $('#quick-restock-product').val();
                const supplierEmail = $('#quick-restock-supplier').val();
                const expires = $('#quick-restock-expires').val();
                
                if (!productId || !supplierEmail) {
                    showNotification('Please select both product and supplier.', 'error');
                    return;
                }
                
                $('#quick-restock-loading').show();
                $('#quick-restock-content').hide();
                $('#generate-quick-restock-link-btn').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_generate_quick_restock_link',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        product_id: productId,
                        supplier_email: supplierEmail,
                        expires: expires
                    },
                    success: function(response) {
                        $('#quick-restock-loading').hide();
                        $('#generate-quick-restock-link-btn').prop('disabled', false);
                        
                        if (response.success) {
                            displayQuickRestockResult(response.data);
                            showNotification(response.data.message + ' Quick restock links table will refresh automatically.', 'success');
                            
                            // Refresh the quick restock links table
                            setTimeout(function() {
                                $('#quick-restock-links-tbody').html('<tr><td colspan="6" class="srwm-loading"><span class="spinner is-active"></span> Refreshing quick restock links...</td></tr>');
                                loadQuickRestockLinks();
                            }, 500);
                        } else {
                            $('#quick-restock-content').show();
                            showNotification(response.data || 'Error generating quick restock link', 'error');
                        }
                    },
                    error: function() {
                        $('#quick-restock-loading').hide();
                        $('#quick-restock-content').show();
                        $('#generate-quick-restock-link-btn').prop('disabled', false);
                        showNotification('Error generating quick restock link. Please try again.', 'error');
                    }
                });
            }
            
            function displayQuickRestockResult(data) {
                $('#quick-link-product-name').text(data.product_name);
                $('#quick-link-supplier-name').text(data.supplier_name);
                $('#quick-link-expires').text(new Date(data.expires_at).toLocaleString());
                $('#generated-quick-restock-url').val(data.restock_url);
                
                $('#quick-restock-content').show();
                $('#quick-restock-result').show();
                
                // Update button text
                $('#generate-quick-restock-link-btn').text('Generate Another Link');
            }
            
            function copyQuickRestockLink() {
                const urlInput = document.getElementById('generated-quick-restock-url');
                urlInput.select();
                urlInput.setSelectionRange(0, 99999);
                
                try {
                    document.execCommand('copy');
                    const copyBtn = $('#copy-quick-link-btn');
                    copyBtn.addClass('copied');
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    
                    setTimeout(function() {
                        copyBtn.removeClass('copied');
                        copyBtn.html('<i class="fas fa-copy"></i> Copy');
                    }, 2000);
                    
                    showNotification('Quick restock link copied to clipboard!', 'success');
                } catch (err) {
                    showNotification('Failed to copy link. Please copy manually.', 'error');
                }
            }
            
            function loadQuickRestockLinks() {
                $('#quick-restock-links-tbody').html('<tr><td colspan="6" class="srwm-loading"><span class="spinner is-active"></span> Loading quick restock links...</td></tr>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_quick_restock_links',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayQuickRestockLinks(response.data);
                        } else {
                            $('#quick-restock-links-tbody').html('<tr><td colspan="6" class="srwm-error">Error loading quick restock links: ' + response.data + '</td></tr>');
                        }
                    },
                    error: function() {
                        $('#quick-restock-links-tbody').html('<tr><td colspan="6" class="srwm-error">Error loading quick restock links. Please try again.</td></tr>');
                    }
                });
            }
            
            function displayQuickRestockLinks(links) {
                if (links.length === 0) {
                    $('#quick-restock-links-tbody').html('<tr><td colspan="6" class="srwm-empty">No quick restock links found. Generate links using the button above.</td></tr>');
                    return;
                }
                
                let html = '';
                links.forEach(function(link, index) {
                    const expiresDate = new Date(link.expires_at);
                    const now = new Date();
                    const isExpired = expiresDate < now;
                    const status = isExpired ? 'expired' : (parseInt(link.used) === 1 ? 'used' : 'active');
                    
                    // Check if this is a newly generated link
                    const isNewLink = index === 0 && status === 'active' && parseInt(link.used) === 0;
                    const newLinkClass = isNewLink ? 'srwm-new-link' : '';
                    
                    html += `
                        <tr class="${newLinkClass}">
                            <td>
                                <div class="srwm-product-info">
                                    <div class="srwm-product-name">${link.product_name || 'Unknown'}</div>
                                    <div class="srwm-product-sku">SKU: ${link.product_sku || 'N/A'}</div>
                                </div>
                            </td>
                            <td>
                                <div class="srwm-link-token" title="${link.token}">${link.token}</div>
                            </td>
                            <td>
                                <div class="srwm-supplier-info">
                                    <div class="srwm-supplier-name">${link.supplier_name || 'Unknown'}</div>
                                    <div class="srwm-supplier-email">${link.supplier_email}</div>
                                </div>
                            </td>
                            <td>${expiresDate.toLocaleDateString()} ${expiresDate.toLocaleTimeString()}</td>
                            <td>
                                <span class="srwm-status-badge ${status}">
                                    <i class="fas fa-${status === 'active' ? 'check-circle' : (status === 'used' ? 'clock' : 'times-circle')}"></i>
                                    ${status}
                                </span>
                            </td>
                            <td>
                                <div class="srwm-table-actions">
                                    <button class="srwm-table-action-btn primary" onclick="copyQuickRestockLinkToClipboard('${link.token}')" title="Copy Link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="srwm-table-action-btn secondary" onclick="viewQuickRestockLinkDetails('${link.token}')" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="srwm-table-action-btn danger" onclick="deleteQuickRestockLink('${link.token}')" title="Delete Link">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                $('#quick-restock-links-tbody').html(html);
            }
            
            function copyQuickRestockLinkToClipboard(token) {
                const restockUrl = '<?php echo site_url(); ?>/?srwm_restock=1&token=' + token;
                
                navigator.clipboard.writeText(restockUrl).then(function() {
                    showNotification('Quick restock link copied to clipboard!', 'success');
                }).catch(function() {
                    const textArea = document.createElement('textarea');
                    textArea.value = restockUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('Quick restock link copied to clipboard!', 'success');
                });
            }
            
            function viewQuickRestockLinkDetails(token) {
                showNotification('Quick restock link details feature coming soon!', 'info');
            }
            
            function deleteQuickRestockLink(token) {
                if (confirm('Are you sure you want to delete this quick restock link? This action cannot be undone.')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'srwm_delete_quick_restock_link',
                            nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                            token: token
                        },
                        success: function(response) {
                            if (response.success) {
                                loadQuickRestockLinks();
                                showNotification('Quick restock link deleted successfully!', 'success');
                            } else {
                                showNotification('Error deleting link: ' + response.data, 'error');
                            }
                        },
                        error: function() {
                            showNotification('Error deleting link. Please try again.', 'error');
                        }
                    });
                }
            }
            
            // Update closeAllModals to include quick restock modal
            function closeAllModals() {
                $('.srwm-modal').hide();
                currentSupplierId = null;
                
                // Reset upload link modal
                $('#upload-link-content').show();
                $('#upload-link-result').hide();
                $('#upload-link-loading').hide();
                $('#generate-upload-link-btn').prop('disabled', false).text('Generate Link');
                
                // Reset quick restock modal
                $('#quick-restock-content').show();
                $('#quick-restock-result').hide();
                $('#quick-restock-loading').hide();
                $('#generate-quick-restock-link-btn').prop('disabled', false).text('Generate Link');
            }
            
            // Global functions for quick restock actions
            window.copyQuickRestockLinkToClipboard = copyQuickRestockLinkToClipboard;
            window.viewQuickRestockLinkDetails = viewQuickRestockLinkDetails;
            window.deleteQuickRestockLink = deleteQuickRestockLink;
        });
        </script>
        <?php
    }
    
    /**
     * Render Pro features page
     */
    public function render_pro_features_page() {
        if (!$this->license_manager->is_pro_active()) {
            wp_die(__('Pro features are not available. Please activate your license.', 'smart-restock-waitlist'));
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Pro Features', 'smart-restock-waitlist'); ?></h1>
            
            <div class="srwm-pro-features">
                <div class="srwm-feature-card">
                    <h3><?php _e('One-Click Supplier Restock', 'smart-restock-waitlist'); ?></h3>
                    <p><?php _e('Generate secure restock links for suppliers to update stock without logging in.', 'smart-restock-waitlist'); ?></p>
                    <button class="button button-primary" id="generate-restock-link">
                        <?php _e('Generate Restock Link', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
                
                <div class="srwm-feature-card">
                    <h3><?php _e('Multi-Channel Notifications', 'smart-restock-waitlist'); ?></h3>
                    <p><?php _e('Send supplier alerts via Email, WhatsApp, and SMS.', 'smart-restock-waitlist'); ?></p>
                    <button class="button button-primary" id="test-notifications">
                        <?php _e('Test Notifications', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
                
                <div class="srwm-feature-card">
                    <h3><?php _e('Purchase Order Generation', 'smart-restock-waitlist'); ?></h3>
                    <p><?php _e('Automatically generate and send branded purchase orders to suppliers.', 'smart-restock-waitlist'); ?></p>
                    <button class="button button-primary" id="generate-po">
                        <?php _e('Generate PO', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
                
                <div class="srwm-feature-card">
                    <h3><?php _e('CSV Upload', 'smart-restock-waitlist'); ?></h3>
                    <p><?php _e('Allow suppliers to upload CSV files for bulk restock operations.', 'smart-restock-waitlist'); ?></p>
                    <button class="button button-primary" id="generate-csv-link">
                        <?php _e('Generate CSV Upload Link', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get products with active waitlists
     */
    private function get_waitlist_products() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_results(
            "SELECT p.ID as product_id, p.post_title as name, pm.meta_value as sku,
                    wc.stock_quantity as stock, COUNT(w.id) as waitlist_count
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
             LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wc ON p.ID = wc.product_id
             INNER JOIN $table w ON p.ID = w.product_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             GROUP BY p.ID
             ORDER BY waitlist_count DESC",
            ARRAY_A
        );
    }
    
    /**
     * Get products with supplier alerts
     */
    private function get_supplier_products() {
        $supplier = SRWM_Supplier::get_instance();
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