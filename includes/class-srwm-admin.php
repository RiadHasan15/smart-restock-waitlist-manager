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
        
        // Pro features menu items
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
                <div class="srwm-section">
                    <div class="srwm-section-header">
                        <h2><?php _e('Products with Active Waitlist', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-section-actions">
                            <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-analytics'); ?>'">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php _e('View Analytics', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                    
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
                <div class="srwm-section">
                    <div class="srwm-section-header">
                        <h2><?php _e('Products with Suppliers', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-section-actions">
                            <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist-thresholds'); ?>'">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php _e('Manage Thresholds', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="srwm-table-container">
                        <table class="wp-list-table widefat fixed striped srwm-modern-table">
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
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('One-Click Restock', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-section">
                <div class="srwm-section-header">
                    <h2><?php _e('Generate Secure Restock Links', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Create secure, time-limited restock links that suppliers can use to update product stock without logging in.', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <?php if (!empty($products)): ?>
                <div class="srwm-table-container">
                    <table class="wp-list-table widefat fixed striped srwm-modern-table">
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
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('Multi-Channel Notifications', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-section">
                <div class="srwm-section-header">
                    <h2><?php _e('Notification Channels', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Configure how suppliers are notified about low stock and restock requests.', 'smart-restock-waitlist'); ?></p>
                </div>
                
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
        
        <?php
        $this->enqueue_modern_styles();
        $this->enqueue_notification_styles();
        ?>
        <?php
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
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('Email Templates', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-section">
                <div class="srwm-section-header">
                    <h2><?php _e('Customizable Templates', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Customize email, SMS, and WhatsApp templates with placeholders for dynamic content.', 'smart-restock-waitlist'); ?></p>
                </div>
                
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
        
        <?php
        $this->enqueue_modern_styles();
        $this->enqueue_template_styles();
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
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('Purchase Orders', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-section">
                <div class="srwm-section-header">
                    <h2><?php _e('Purchase Order Management', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Generate and manage purchase orders for suppliers when stock is low.', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <div class="srwm-po-stats">
                    <div class="srwm-po-stat">
                        <span class="srwm-po-stat-number"><?php echo $this->get_total_purchase_orders(); ?></span>
                        <span class="srwm-po-stat-label"><?php _e('Total POs', 'smart-restock-waitlist'); ?></span>
                    </div>
                    <div class="srwm-po-stat">
                        <span class="srwm-po-stat-number"><?php echo $this->get_pending_purchase_orders(); ?></span>
                        <span class="srwm-po-stat-label"><?php _e('Pending', 'smart-restock-waitlist'); ?></span>
                    </div>
                    <div class="srwm-po-stat">
                        <span class="srwm-po-stat-number"><?php echo $this->get_completed_purchase_orders(); ?></span>
                        <span class="srwm-po-stat-label"><?php _e('Completed', 'smart-restock-waitlist'); ?></span>
                    </div>
                </div>
                
                <div class="srwm-po-actions">
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
                    <table class="wp-list-table widefat fixed striped srwm-modern-table">
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
        $this->enqueue_po_styles();
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
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('CSV Upload', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-section">
                <div class="srwm-section-header">
                    <h2><?php _e('Bulk Stock Update', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Generate secure upload links for suppliers to update multiple products via CSV.', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <div class="srwm-csv-actions">
                    <button class="button button-primary" id="srwm-generate-csv-link">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Generate Upload Link', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="button button-secondary" id="srwm-download-template">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Download CSV Template', 'smart-restock-waitlist'); ?>
                    </button>
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
                    <table class="wp-list-table widefat fixed striped srwm-modern-table">
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
                                        <?php if (strtotime($link->expires_at) < time()): ?>
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
        
        <?php
        $this->enqueue_modern_styles();
        $this->enqueue_csv_styles();
        ?>
        <?php
    }
    
    /**
     * Render Stock Thresholds page
     */
    public function render_thresholds_page() {
        if (!$this->license_manager->is_pro_active()) {
            $this->render_pro_feature_locked();
            return;
        }
        
        ?>
        <div class="wrap srwm-dashboard">
            <div class="srwm-dashboard-header">
                <h1><?php _e('Stock Thresholds', 'smart-restock-waitlist'); ?></h1>
                <div class="srwm-dashboard-actions">
                    <button class="button button-secondary" onclick="location.href='<?php echo admin_url('admin.php?page=smart-restock-waitlist'); ?>'">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back to Dashboard', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
            
            <div class="srwm-section">
                <div class="srwm-section-header">
                    <h2><?php _e('Threshold Management', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('Set global and per-product notification thresholds for supplier alerts.', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <div class="srwm-threshold-settings">
                    <div class="srwm-global-threshold">
                        <h3><?php _e('Global Default Threshold', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('This threshold will be used for all products unless a specific threshold is set.', 'smart-restock-waitlist'); ?></p>
                        <div class="srwm-form-group">
                            <label><?php _e('Default Threshold:', 'smart-restock-waitlist'); ?></label>
                            <input type="number" name="srwm_global_threshold" value="<?php echo esc_attr(get_option('srwm_global_threshold', 5)); ?>" min="0" class="small-text">
                            <span><?php _e('units', 'smart-restock-waitlist'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-table-container">
                    <table class="wp-list-table widefat fixed striped srwm-modern-table">
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
        
        <?php
        $this->enqueue_modern_styles();
        $this->enqueue_threshold_styles();
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
                
                <?php if ($this->license_manager->is_pro_active()): ?>
                <div class="srwm-stat-card">
                    <h3><?php _e('Conversion Rate', 'smart-restock-waitlist'); ?></h3>
                    <div class="stat-number"><?php echo $analytics_data['conversion_rate']; ?>%</div>
                </div>
                <?php endif; ?>
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