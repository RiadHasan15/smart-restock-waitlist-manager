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
        
        // Pro features menu items - always check current license status
        if ($this->license_manager->is_pro_active()) {
            add_submenu_page(
                'smart-restock-waitlist',
                __('One-Click Restock', 'smart-restock-waitlist'),
                __('One-Click Restock', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-oneclick',
                array($this, 'render_oneclick_restock_page')
            );
            
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
                __('CSV Upload', 'smart-restock-waitlist'),
                __('CSV Upload', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-csv-upload',
                array($this, 'render_csv_upload_page')
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
        
        wp_localize_script('srwm-admin', 'srwm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
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
                    <div class="srwm-action-card" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-oneclick'); ?>'">
                        <div class="srwm-action-icon">
                            <span class="dashicons dashicons-update"></span>
                        </div>
                        <h3><?php _e('One-Click Restock', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Generate secure restock links for suppliers', 'smart-restock-waitlist'); ?></p>
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
        </style>
        <?php
    }
    
    /**
     * Render One-Click Restock page
     */
    public function render_oneclick_restock_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        $products = $this->get_waitlist_products();
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('One-Click Restock', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Generate Secure Restock Links', 'smart-restock-waitlist'); ?></h2>
                </div>
                <div class="srwm-pro-card-content">
                                        <p><?php _e('Create secure, time-limited restock links that suppliers can use to update product stock without logging in.', 'smart-restock-waitlist'); ?></p>
                    
                    <?php if (!empty($products)): ?>
                    <div class="srwm-table-container">
                        <table class="srwm-pro-table">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Current Stock', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Waitlist Count', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product_data): ?>
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
                                        <div class="srwm-supplier-info">
                                            <strong><?php echo esc_html($product_data['supplier_name'] ?? __('No supplier', 'smart-restock-waitlist')); ?></strong>
                                            <small><?php echo esc_html($product_data['supplier_email'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="srwm-action-buttons">
                                            <button class="button button-primary button-small generate-restock-link" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                <span class="dashicons dashicons-admin-links"></span>
                                                <?php _e('Generate Link', 'smart-restock-waitlist'); ?>
                                            </button>
                                            <button class="button button-secondary button-small view-restock-links" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                <span class="dashicons dashicons-list-view"></span>
                                                <?php _e('View Links', 'smart-restock-waitlist'); ?>
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
                        <span class="dashicons dashicons-admin-links"></span>
                    </div>
                    <h3><?php _e('No Products Available', 'smart-restock-waitlist'); ?></h3>
                    <p><?php _e('Products will appear here when they have active waitlists.', 'smart-restock-waitlist'); ?></p>
                </div>
                <?php endif; ?>
                </div>
            </div>
            
            <!-- Restock Link Generation Modal -->
            <div id="srwm-restock-link-modal" class="srwm-modal" style="display: none;">
                <div class="srwm-modal-content">
                    <div class="srwm-modal-header">
                        <h2><?php _e('Generate Restock Link', 'smart-restock-waitlist'); ?></h2>
                        <span class="srwm-modal-close">&times;</span>
                    </div>
                    <form id="srwm-restock-link-form">
                        <div class="srwm-form-group">
                            <label for="srwm-supplier-email"><?php _e('Supplier Email:', 'smart-restock-waitlist'); ?></label>
                            <input type="email" id="srwm-supplier-email" name="supplier_email" class="regular-text" required>
                            <p class="description"><?php _e('The email address where the restock link will be sent.', 'smart-restock-waitlist'); ?></p>
                        </div>
                        <div class="srwm-form-group">
                            <label for="srwm-link-expiry"><?php _e('Link Expiry (hours):', 'smart-restock-waitlist'); ?></label>
                            <select id="srwm-link-expiry" name="expiry_hours">
                                <option value="24">24 hours</option>
                                <option value="48">48 hours</option>
                                <option value="72">72 hours</option>
                                <option value="168">1 week</option>
                            </select>
                        </div>
                        <div class="srwm-form-group">
                            <label for="srwm-restock-quantity"><?php _e('Default Restock Quantity:', 'smart-restock-waitlist'); ?></label>
                            <input type="number" id="srwm-restock-quantity" name="default_quantity" min="1" value="10" class="regular-text">
                            <p class="description"><?php _e('The default quantity that will be suggested to the supplier.', 'smart-restock-waitlist'); ?></p>
                        </div>
                        <div class="srwm-form-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-admin-links"></span>
                                <?php _e('Generate & Send Link', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Restock Links List Modal -->
            <div id="srwm-links-list-modal" class="srwm-modal" style="display: none;">
                <div class="srwm-modal-content">
                    <div class="srwm-modal-header">
                        <h2><?php _e('Restock Links History', 'smart-restock-waitlist'); ?></h2>
                        <span class="srwm-modal-close">&times;</span>
                    </div>
                    <div id="srwm-links-list-content"></div>
                </div>
            </div>
        </div>
        
        <?php
        $this->enqueue_modern_styles();
        ?>
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
     * Get CSV upload links
     */
    private function get_csv_upload_links() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        // Get CSV tokens with proper data
        $results = $wpdb->get_results("
            SELECT 
                t.*,
                CASE 
                    WHEN t.used = 1 THEN 'Used'
                    WHEN t.expires_at < NOW() THEN 'Expired'
                    ELSE 'Active'
                END as status,
                CASE 
                    WHEN t.used = 1 THEN 1
                    ELSE 0
                END as upload_count
            FROM $table t 
            ORDER BY t.created_at DESC 
            LIMIT 10
        ");
        
        if ($results === null) {
            $results = array();
        }
        
        // Add supplier name (for now, use email as name)
        foreach ($results as $link) {
            $link->supplier_name = $link->supplier_email;
        }
        
        return $results;
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
     * Render CSV Upload page
     */
    public function render_csv_upload_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-pro-page">
            <div class="srwm-pro-header">
                <h1><?php _e('CSV Upload', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-pro-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-pro-card">
                <div class="srwm-pro-card-header">
                    <h2><?php _e('Bulk Stock Update', 'smart-restock-waitlist'); ?></h2>
                </div>
                <div class="srwm-pro-card-content">
                    <p><?php _e('Generate secure upload links for suppliers to update multiple products via CSV.', 'smart-restock-waitlist'); ?></p>
                
                <div class="srwm-csv-form">
                    <form id="srwm-generate-csv-form" method="post">
                        <?php wp_nonce_field('srwm_admin_nonce', 'srwm_admin_nonce'); ?>
                        <div class="srwm-form-group">
                            <label for="srwm_supplier_email"><?php _e('Supplier Email:', 'smart-restock-waitlist'); ?></label>
                            <div class="srwm-input-group">
                                <input type="email" id="srwm_supplier_email" name="srwm_supplier_email" required class="regular-text" placeholder="<?php _e('Enter supplier email address', 'smart-restock-waitlist'); ?>">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <?php _e('Generate Upload Link', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="srwm-pro-actions">
                        <button class="button button-secondary" id="srwm-download-template">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Download CSV Template', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="srwm-csv-info">
                    <h3><?php _e('CSV Format Requirements:', 'smart-restock-waitlist'); ?></h3>
                    <ul>
                        <li><?php _e('File must be in CSV format', 'smart-restock-waitlist'); ?></li>
                        <li><?php _e('Required columns: Product ID, Quantity', 'smart-restock-waitlist'); ?></li>
                        <li><?php _e('Optional columns: SKU, Notes', 'smart-restock-waitlist'); ?></li>
                        <li><?php _e('Maximum file size: 5MB', 'smart-restock-waitlist'); ?></li>
                    </ul>
                </div>
                
                <div class="srwm-table-container">
                    <table class="srwm-pro-table">
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
                        <tbody>
                            <?php foreach ($this->get_csv_upload_links() as $link): ?>
                                <tr>
                                    <td>
                                        <code><?php echo esc_html($link->token); ?></code>
                                    </td>
                                    <td>
                                        <div class="srwm-supplier-info">
                                            <strong><?php echo esc_html($link->supplier_name); ?></strong>
                                            <small><?php echo esc_html($link->supplier_email); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date('M j, Y H:i', strtotime($link->expires_at))); ?>
                                    </td>
                                    <td>
                                        <?php if ($link->status === 'Used'): ?>
                                            <span class="srwm-status srwm-status-used"><?php _e('Used', 'smart-restock-waitlist'); ?></span>
                                        <?php elseif ($link->status === 'Expired'): ?>
                                            <span class="srwm-status srwm-status-expired"><?php _e('Expired', 'smart-restock-waitlist'); ?></span>
                                        <?php else: ?>
                                            <span class="srwm-status srwm-status-active"><?php _e('Active', 'smart-restock-waitlist'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="srwm-upload-count"><?php echo esc_html($link->upload_count); ?></span>
                                    </td>
                                    <td>
                                        <div class="srwm-action-buttons">
                                            <button class="button button-small copy-link" data-token="<?php echo esc_attr($link->token); ?>">
                                                <span class="dashicons dashicons-admin-links"></span>
                                                <?php _e('Copy Link', 'smart-restock-waitlist'); ?>
                                            </button>
                                            <button class="button button-small view-uploads" data-token="<?php echo esc_attr($link->token); ?>">
                                                <span class="dashicons dashicons-list-view"></span>
                                                <?php _e('View Uploads', 'smart-restock-waitlist'); ?>
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