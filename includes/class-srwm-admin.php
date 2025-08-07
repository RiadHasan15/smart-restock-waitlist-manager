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
        
        // Clean up corrupted email templates
        add_action('init', array($this, 'cleanup_corrupted_templates'));
        
        // AJAX handlers moved to main plugin file to avoid conflicts
    }
    
    /**
     * Clean up corrupted email templates
     */
    public function cleanup_corrupted_templates() {
        // Check if waitlist email template is corrupted
        $waitlist_template = get_option('srwm_email_template_waitlist');
        if (!empty($waitlist_template) && strpos($waitlist_template, '\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\') !== false) {
            // Template is corrupted, replace with correct default
            update_option('srwm_email_template_waitlist', $this->get_default_waitlist_email_template());
        }
        
        // Check if registration email template is corrupted
        $registration_template = get_option('srwm_email_template_registration');
        if (!empty($registration_template) && strpos($registration_template, '\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\') !== false) {
            // Template is corrupted, replace with correct default
            update_option('srwm_email_template_registration', $this->get_default_registration_email_template());
        }
        
        // Check if supplier email template is corrupted
        $supplier_template = get_option('srwm_email_template_supplier');
        if (!empty($supplier_template) && strpos($supplier_template, '\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\') !== false) {
            // Template is corrupted, replace with correct default
            update_option('srwm_email_template_supplier', $this->get_default_supplier_email_template());
        }
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
        
        // Analytics functionality moved to Dashboard
        
        // Pro features menu items - always check current license status
        if ($this->license_manager->is_pro_active()) {
            add_submenu_page(
                'smart-restock-waitlist',
                __('Supplier Management', 'smart-restock-waitlist'),
                __('Supplier Management', 'smart-restock-waitlist'),
                'manage_woocommerce',
                'smart-restock-waitlist-suppliers',
                array($this, 'render_suppliers_page')
            );
        }
        
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
        register_setting('srwm_settings', 'srwm_email_template_registration');
        register_setting('srwm_settings', 'srwm_email_template_supplier');
        register_setting('srwm_settings', 'srwm_low_stock_threshold');
        register_setting('srwm_settings', 'srwm_auto_disable_at_zero');
        
        // Social proof display settings
        register_setting('srwm_settings', 'srwm_hide_social_proof');
        register_setting('srwm_settings', 'srwm_social_proof_style');
        register_setting('srwm_settings', 'srwm_hide_header_after_submit');
        
        // Styling options
        register_setting('srwm_settings', 'srwm_container_bg');
        register_setting('srwm_settings', 'srwm_header_bg');
        register_setting('srwm_settings', 'srwm_header_text');
        register_setting('srwm_settings', 'srwm_body_text');
        register_setting('srwm_settings', 'srwm_btn_primary_bg');
        register_setting('srwm_settings', 'srwm_btn_primary_text');
        register_setting('srwm_settings', 'srwm_btn_secondary_bg');
        register_setting('srwm_settings', 'srwm_btn_secondary_text');
        register_setting('srwm_settings', 'srwm_success_bg');
        register_setting('srwm_settings', 'srwm_success_text');
        register_setting('srwm_settings', 'srwm_border_color');
        register_setting('srwm_settings', 'srwm_input_bg');
        register_setting('srwm_settings', 'srwm_input_border');
        register_setting('srwm_settings', 'srwm_input_focus_border');
        register_setting('srwm_settings', 'srwm_progress_bg');
        register_setting('srwm_settings', 'srwm_progress_fill');
        register_setting('srwm_settings', 'srwm_border_radius');
        register_setting('srwm_settings', 'srwm_font_size');
        
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
            
            // Notification settings
            register_setting('srwm_notifications', 'srwm_email_enabled');
            register_setting('srwm_notifications', 'srwm_email_template');
            register_setting('srwm_notifications', 'srwm_whatsapp_enabled');
            register_setting('srwm_notifications', 'srwm_whatsapp_api_key');
            register_setting('srwm_notifications', 'srwm_whatsapp_phone');
            register_setting('srwm_notifications', 'srwm_whatsapp_template');
            register_setting('srwm_notifications', 'srwm_sms_enabled');
            register_setting('srwm_notifications', 'srwm_sms_provider');
            register_setting('srwm_notifications', 'srwm_sms_api_key');
            register_setting('srwm_notifications', 'srwm_sms_api_secret');
            register_setting('srwm_notifications', 'srwm_sms_phone');
            register_setting('srwm_notifications', 'srwm_sms_template');
        }
    }
    

    
    /**
     * Reset email templates to defaults
     */
    private function reset_email_templates() {
        // Force delete and recreate waitlist email template
        delete_option('srwm_email_template_waitlist');
        update_option('srwm_email_template_waitlist', $this->get_default_waitlist_email_template());
        
        // Force delete and recreate registration email template
        delete_option('srwm_email_template_registration');
        update_option('srwm_email_template_registration', $this->get_default_registration_email_template());
        
        // Force delete and recreate supplier email template if Pro is active
        if ($this->license_manager->is_pro_active()) {
            delete_option('srwm_email_template_supplier');
            update_option('srwm_email_template_supplier', $this->get_default_supplier_email_template());
        }
    }
    
    /**
     * Reset settings to defaults
     */
    private function reset_settings_to_defaults() {
        // Reset general settings
        update_option('srwm_waitlist_enabled', 'yes');
        update_option('srwm_auto_disable_at_zero', 'no');
        update_option('srwm_waitlist_display_threshold', 5);
        update_option('srwm_email_template_waitlist', $this->get_default_waitlist_email_template());
        
        // Reset styling options to defaults
        $default_styling = array(
            'srwm_container_bg' => '#ffffff',
            'srwm_header_bg' => '#f8f9fa',
            'srwm_header_text' => '#333333',
            'srwm_body_text' => '#666666',
            'srwm_btn_primary_bg' => '#007cba',
            'srwm_btn_primary_text' => '#ffffff',
            'srwm_btn_secondary_bg' => '#6c757d',
            'srwm_btn_secondary_text' => '#ffffff',
            'srwm_success_bg' => '#d4edda',
            'srwm_success_text' => '#155724',
            'srwm_border_color' => '#e9ecef',
            'srwm_input_bg' => '#ffffff',
            'srwm_input_border' => '#ced4da',
            'srwm_input_focus_border' => '#007cba',
            'srwm_progress_bg' => '#e9ecef',
            'srwm_progress_fill' => '#007cba',
            'srwm_border_radius' => '8',
            'srwm_font_size' => 'medium'
        );
        
        foreach ($default_styling as $option => $value) {
            update_option($option, $value);
        }
        
        // Reset social proof display settings to defaults
        update_option('srwm_hide_social_proof', '0');
        update_option('srwm_social_proof_style', 'full');
        update_option('srwm_hide_header_after_submit', '1');
        
        // Reset registration email template
        update_option('srwm_email_template_registration', $this->get_default_registration_email_template());
        
        // Reset Pro settings if license is active
        if ($this->license_manager->is_pro_active()) {
            update_option('srwm_supplier_notifications', 'yes');
            update_option('srwm_email_template_supplier', $this->get_default_supplier_email_template());
            update_option('srwm_company_name', get_bloginfo('name'));
            update_option('srwm_company_address', '');
            update_option('srwm_company_phone', '');
            update_option('srwm_company_email', get_option('admin_email'));
            update_option('srwm_low_stock_threshold', 5);
        }
        
        // Redirect to show success message
        wp_redirect(add_query_arg('settings-reset', 'true', admin_url('admin.php?page=smart-restock-waitlist-settings')));
        exit;
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
        
        // Add dashboard-specific scripts for the main dashboard page
        if (strpos($hook, 'toplevel_page_smart-restock-waitlist') !== false) {
            // Enqueue Chart.js for analytics
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );
            
            // Enqueue dashboard JavaScript
            wp_enqueue_script(
                'srwm-dashboard',
                SRWM_PLUGIN_URL . 'admin/js/dashboard.js',
                array('jquery', 'wp-util', 'chart-js'),
                SRWM_VERSION,
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
            
            wp_localize_script('srwm-dashboard', 'srwm_dashboard_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('srwm_dashboard_nonce')
            ));
        }
        
        // Add notification-specific scripts
        if (strpos($hook, 'smart-restock-waitlist-notifications') !== false) {
            wp_add_inline_script('srwm-admin', '
                jQuery(document).ready(function($) {
                    // Handle form submission with feedback
                    $("form[action=\"options.php\"]").on("submit", function() {
                        const submitBtn = $(this).find("button[type=\"submit\"]");
                        const originalText = submitBtn.html();
                        
                        // Show loading state
                        submitBtn.html("<span class=\"dashicons dashicons-update-alt\" style=\"animation: spin 1s linear infinite;\"></span> Saving...");
                        submitBtn.prop("disabled", true);
                        
                        // Re-enable after a short delay (form will redirect)
                        setTimeout(function() {
                            submitBtn.html(originalText);
                            submitBtn.prop("disabled", false);
                        }, 2000);
                    });
                    
                    // Show success message if settings were saved
                    if (window.location.search.includes("settings-updated=true")) {
                        showNotification("Notification settings saved successfully!", "success");
                    }
                    
                    // Test notifications button
                    $("#test-notifications").on("click", function() {
                        const btn = $(this);
                        const originalText = btn.html();
                        
                        btn.html("<span class=\"dashicons dashicons-update-alt\" style=\"animation: spin 1s linear infinite;\"></span> Testing...");
                        btn.prop("disabled", true);
                        
                        // Get current settings
                        const emailEnabled = $("input[name=\"srwm_email_enabled\"]").is(":checked");
                        const whatsappEnabled = $("input[name=\"srwm_whatsapp_enabled\"]").is(":checked");
                        const smsEnabled = $("input[name=\"srwm_sms_enabled\"]").is(":checked");
                        
                        if (!emailEnabled && !whatsappEnabled && !smsEnabled) {
                            showNotification("Please enable at least one notification channel first.", "warning");
                            btn.html(originalText);
                            btn.prop("disabled", false);
                            return;
                        }
                        
                        // Send test notification via AJAX
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "srwm_test_notifications",
                                nonce: srwm_admin.nonce,
                                email_enabled: emailEnabled,
                                whatsapp_enabled: whatsappEnabled,
                                sms_enabled: smsEnabled
                            },
                            success: function(response) {
                                if (response.success) {
                                    showNotification("Test notifications sent successfully! Check your configured channels.", "success");
                                } else {
                                    showNotification("Failed to send test notifications: " + response.data, "error");
                                }
                            },
                            error: function() {
                                showNotification("Error sending test notifications. Please try again.", "error");
                            },
                            complete: function() {
                                btn.html(originalText);
                                btn.prop("disabled", false);
                            }
                        });
                    });
                });
            ');
        }
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        try {
            // Check if WooCommerce is active
            if (!class_exists('WooCommerce')) {
                throw new Exception('WooCommerce is required for this plugin to function properly.');
            }
            
            $total_waitlist_customers = $this->get_total_waitlist_customers();
            $waitlist_products = $this->get_waitlist_products();
            $supplier_products = $this->get_supplier_products();
            
            // Get analytics data for charts
            $analytics = SRWM_Analytics::get_instance($this->license_manager);
            $analytics_data = $analytics->get_dashboard_data();
            
        } catch (Exception $e) {
            // Log the error for debugging
            error_log('SRWM Dashboard Error: ' . $e->getMessage());
            
            // Fallback values if there's an error
            $total_waitlist_customers = 0;
            $waitlist_products = array();
            $supplier_products = array();
            $analytics_data = array(
                'total_waitlist_customers' => 0,
                'waitlist_products' => 0,
                'today_waitlists' => 0,
                'today_restocks' => 0,
                'pending_notifications' => 0,
                'low_stock_products' => 0,
                'avg_restock_time' => 0
            );
            
            // Show error notice to admin
            if (current_user_can('manage_woocommerce')) {
                echo '<div class="notice notice-error"><p><strong>' . esc_html__('Dashboard Error:', 'smart-restock-waitlist') . '</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap srwm-dashboard">
            <!-- Header Section -->
            <div class="srwm-dashboard-header">
                <div class="srwm-header-content">
                    <div class="srwm-header-left">
                        <h1 class="srwm-page-title">
                            <span class="dashicons dashicons-chart-area"></span>
                            <?php _e('Dashboard Overview', 'smart-restock-waitlist'); ?>
                        </h1>
                        <p class="srwm-page-subtitle">
                            <?php _e('Monitor your waitlist performance and restock activities', 'smart-restock-waitlist'); ?>
                        </p>
                    </div>
                    <div class="srwm-header-actions">
                        <div class="srwm-period-selector">
                            <label for="srwm-global-period"><?php _e('Time Period:', 'smart-restock-waitlist'); ?></label>
                            <select id="srwm-global-period" class="srwm-period-select">
                                <option value="7"><?php _e('Last 7 Days', 'smart-restock-waitlist'); ?></option>
                                <option value="30"><?php _e('Last 30 Days', 'smart-restock-waitlist'); ?></option>
                                <option value="90"><?php _e('Last 90 Days', 'smart-restock-waitlist'); ?></option>
                            </select>
                        </div>
                        <button id="srwm-export-report" class="srwm-btn srwm-btn-primary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export Report', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($total_waitlist_customers == 0 && empty($waitlist_products) && empty($supplier_products)): ?>
                <div class="srwm-welcome-section">
                    <div class="srwm-welcome-card">
                        <div class="srwm-welcome-icon">
                            <span class="dashicons dashicons-smiley"></span>
                        </div>
                        <div class="srwm-welcome-content">
                            <h3><?php _e('Welcome to Smart Restock & Waitlist Manager!', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('No data found yet. Start by adding products to your waitlist or configuring your settings to see your dashboard in action.', 'smart-restock-waitlist'); ?></p>
                            <div class="srwm-welcome-actions">
                                <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-settings'); ?>" class="srwm-btn srwm-btn-primary">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <?php _e('Configure Settings', 'smart-restock-waitlist'); ?>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-pro'); ?>" class="srwm-btn srwm-btn-secondary">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <?php _e('Explore Pro Features', 'smart-restock-waitlist'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="srwm-dashboard-content">
                <!-- Dashboard Tabs Navigation -->
                <div class="srwm-dashboard-tabs">
                    <button class="srwm-tab-button active" data-tab="overview">
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php _e('Overview', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="srwm-tab-button" data-tab="analytics">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Analytics', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="srwm-tab-button" data-tab="reports">
                        <span class="dashicons dashicons-analytics"></span>
                        <?php _e('Reports', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="srwm-tab-button" data-tab="actions">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Quick Actions', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
                
                <!-- Tab Content Container -->
                <div class="srwm-tab-content-container">
                    <!-- Overview Tab -->
                    <div class="srwm-tab-content active" data-tab="overview">
                        <!-- Statistics Overview -->
                        <div class="srwm-section">
                    <div class="srwm-section-header">
                        <h2 class="srwm-section-title">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php _e('Analytics Dashboard', 'smart-restock-waitlist'); ?>
                        </h2>
                        <p class="srwm-section-description">
                            <?php _e('Real-time statistics and performance indicators', 'smart-restock-waitlist'); ?>
                        </p>
                        <div class="srwm-section-actions">
                            <button id="srwm-refresh-dashboard" class="srwm-btn srwm-btn-primary srwm-btn-sm">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Refresh Data', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="srwm-stats-grid">
                        <div class="srwm-stat-card" data-stat="total_waitlist_customers">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo number_format($total_waitlist_customers); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Total Waitlist Customers', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('All time', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="srwm-stat-card" data-stat="waitlist_products">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-cart"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo count($waitlist_products); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Products with Waitlist', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Active products', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($this->license_manager->is_pro_active()): ?>
                        <div class="srwm-stat-card" data-stat="supplier_products">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-businessman"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo esc_html(count($supplier_products)); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Products with Suppliers', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Managed products', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="srwm-stat-card srwm-pro-locked">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-lock"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-star-filled"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php _e('Pro', 'smart-restock-waitlist'); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Supplier Management', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Upgrade required', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="srwm-stat-card" data-stat="avg_restock_time">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-clock"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-down">
                                        <span class="dashicons dashicons-arrow-down-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo esc_html(number_format($analytics_data['avg_restock_time'] ?? 0, 1)); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Avg. Restock Time', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Days', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Statistics Cards -->
                        <div class="srwm-stat-card" data-stat="today_waitlists">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo esc_html(number_format($analytics_data['today_waitlists'] ?? 0)); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Today\'s Waitlists', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Today', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="srwm-stat-card" data-stat="today_restocks">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-update"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo esc_html(number_format($analytics_data['today_restocks'] ?? 0)); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Today\'s Restocks', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Today', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="srwm-stat-card" data-stat="pending_notifications">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-bell"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-up">
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo esc_html(number_format($analytics_data['pending_notifications'] ?? 0)); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Pending Notifications', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Awaiting', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="srwm-stat-card" data-stat="low_stock_products">
                            <div class="srwm-stat-header">
                                <div class="srwm-stat-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="srwm-stat-trend">
                                    <span class="srwm-trend-indicator srwm-trend-down">
                                        <span class="dashicons dashicons-arrow-down-alt"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="srwm-stat-content">
                                <h3 class="srwm-stat-number"><?php echo esc_html(number_format($analytics_data['low_stock_products'] ?? 0)); ?></h3>
                                <p class="srwm-stat-label"><?php _e('Low Stock Products', 'smart-restock-waitlist'); ?></p>
                                <div class="srwm-stat-meta">
                                    <span class="srwm-stat-period"><?php _e('Needs attention', 'smart-restock-waitlist'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Charts (Moved to Overview Tab) -->
                <div class="srwm-section">
                    <div class="srwm-section-header">
                        <h2 class="srwm-section-title">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('Analytics Overview', 'smart-restock-waitlist'); ?>
                        </h2>
                        <p class="srwm-section-description">
                            <?php _e('Visual insights into waitlist growth and restock activities', 'smart-restock-waitlist'); ?>
                        </p>
                    </div>
                    
                    <div class="srwm-charts-grid">
                        <div class="srwm-chart-card">
                            <div class="srwm-chart-header">
                                <h3 class="srwm-chart-title">
                                    <span class="dashicons dashicons-chart-area"></span>
                                    <?php _e('Waitlist Growth Trend', 'smart-restock-waitlist'); ?>
                                </h3>
                                <div class="srwm-chart-actions">
                                    <select class="srwm-chart-period">
                                        <option value="7"><?php _e('Last 7 Days', 'smart-restock-waitlist'); ?></option>
                                        <option value="30"><?php _e('Last 30 Days', 'smart-restock-waitlist'); ?></option>
                                        <option value="90"><?php _e('Last 90 Days', 'smart-restock-waitlist'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="srwm-chart-container">
                                <canvas id="waitlistChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="srwm-chart-card">
                            <div class="srwm-chart-header">
                                <h3 class="srwm-chart-title">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                    <?php _e('Restock Activity Breakdown', 'smart-restock-waitlist'); ?>
                                </h3>
                                <div class="srwm-chart-actions">
                                    <button class="srwm-btn srwm-btn-sm srwm-btn-primary srwm-btn-refresh-chart" data-chart="restock">
                                        <span class="dashicons dashicons-update"></span>
                                        <?php _e('Refresh', 'smart-restock-waitlist'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="srwm-chart-container">
                                <canvas id="restockChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Products Overview -->
                <?php if (!empty($waitlist_products)): ?>
                <div class="srwm-section">
                    <div class="srwm-section-header">
                        <h2 class="srwm-section-title">
                            <span class="dashicons dashicons-products"></span>
                            <?php _e('Products with Active Waitlist', 'smart-restock-waitlist'); ?>
                        </h2>
                        <p class="srwm-section-description">
                            <?php _e('Products currently experiencing high demand', 'smart-restock-waitlist'); ?>
                        </p>
                    </div>
                    
                    <!-- Table Controls - Always Visible -->
                    <div class="srwm-table-controls">
                        <div class="srwm-table-info">
    
                        </div>
                        <div class="srwm-table-search">
                            <input type="text" id="srwm-waitlist-search" class="srwm-search-input" placeholder="<?php _e('Search products...', 'smart-restock-waitlist'); ?>">
                            <span class="dashicons dashicons-search"></span>
                        </div>
                        <div class="srwm-table-filters">
                            <select id="srwm-status-filter" class="srwm-filter-select">
                                <option value=""><?php _e('All Status', 'smart-restock-waitlist'); ?></option>
                                <option value="out"><?php _e('Out of Stock', 'smart-restock-waitlist'); ?></option>
                                <option value="low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></option>
                                <option value="ok"><?php _e('In Stock', 'smart-restock-waitlist'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="srwm-table-container">
                        <table class="srwm-modern-table srwm-interactive-table" id="srwm-waitlist-table">
                            <thead>
                                <tr>
                                    <th class="srwm-sortable" data-sort="product">
                                        <?php _e('Product', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th class="srwm-sortable" data-sort="stock">
                                        <?php _e('Current Stock', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th class="srwm-sortable" data-sort="waitlist">
                                        <?php _e('Waitlist Count', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th class="srwm-sortable" data-sort="status">
                                        <?php _e('Status', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($waitlist_products)): ?>
                                    <?php foreach ($waitlist_products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="srwm-product-info">
                                                    <strong><?php echo esc_html($product->name ?? $product['name'] ?? ''); ?></strong>
                                                    <small><?php echo esc_html($product->sku ?? $product['sku'] ?? ''); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $stock = $product->stock ?? $product['stock'] ?? 0;
                                                $stock_class = $stock <= 0 ? 'srwm-stock-out' : ($stock <= 10 ? 'srwm-stock-low' : 'srwm-stock-ok');
                                                ?>
                                                <span class="srwm-stock-badge <?php echo $stock_class; ?>">
                                                    <?php echo esc_html($stock); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="srwm-waitlist-count"><?php echo esc_html($product->waitlist_count ?? $product['waitlist_count'] ?? 0); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $stock = $product->stock ?? $product['stock'] ?? 0;
                                                if ($stock <= 0): ?>
                                                    <span class="srwm-status srwm-status-out"><?php _e('Out of Stock', 'smart-restock-waitlist'); ?></span>
                                                <?php elseif ($stock <= 10): ?>
                                                    <span class="srwm-status srwm-status-low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></span>
                                                <?php else: ?>
                                                    <span class="srwm-status srwm-status-ok"><?php _e('In Stock', 'smart-restock-waitlist'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="srwm-action-buttons">
                                                    <button class="button button-small view-waitlist" data-product-id="<?php echo esc_attr($product->product_id ?? $product['product_id'] ?? 0); ?>">
                                                        <span class="dashicons dashicons-groups"></span>
                                                        <?php _e('View', 'smart-restock-waitlist'); ?>
                                                    </button>
                                                    <button class="button button-primary button-small restock-product" data-product-id="<?php echo esc_attr($product->product_id ?? $product['product_id'] ?? 0); ?>">
                                                        <span class="dashicons dashicons-update"></span>
                                                        <?php _e('Restock', 'smart-restock-waitlist'); ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="srwm-empty-state">
                                            <div class="srwm-empty-icon">
                                                <span class="dashicons dashicons-cart"></span>
                                            </div>
                                            <h3><?php _e('No Products with Waitlists', 'smart-restock-waitlist'); ?></h3>
                                            <p><?php _e('When customers join waitlists for products, they will appear here.', 'smart-restock-waitlist'); ?></p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="srwm-table-container">
                        <table class="srwm-modern-table srwm-interactive-table" id="srwm-waitlist-table">
                            <thead>
                                <tr>
                                    <th class="srwm-sortable" data-sort="product">
                                        <?php _e('Product', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th class="srwm-sortable" data-sort="stock">
                                        <?php _e('Current Stock', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th class="srwm-sortable" data-sort="waitlist">
                                        <?php _e('Waitlist Count', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th class="srwm-sortable" data-sort="status">
                                        <?php _e('Status', 'smart-restock-waitlist'); ?>
                                        <span class="srwm-sort-icon dashicons dashicons-arrow-up-alt2"></span>
                                    </th>
                                    <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($waitlist_products)): ?>
                                    <?php foreach ($waitlist_products as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="srwm-product-info">
                                                    <strong><?php echo esc_html($product->product_name); ?></strong>
                                                    <small><?php echo esc_html($product->sku); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="srwm-stock-badge <?php echo $product->stock_quantity <= 0 ? 'srwm-stock-out' : ($product->stock_quantity <= 10 ? 'srwm-stock-low' : 'srwm-stock-ok'); ?>"><?php echo esc_html($product->stock_quantity); ?></span>
                                            </td>
                                            <td>
                                                <span class="srwm-waitlist-count"><?php echo esc_html($product->waitlist_count); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($product->stock_quantity <= 0): ?>
                                                    <span class="srwm-status srwm-status-out"><?php _e('Out of Stock', 'smart-restock-waitlist'); ?></span>
                                                <?php elseif ($product->stock_quantity <= 10): ?>
                                                    <span class="srwm-status srwm-status-low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></span>
                                                <?php else: ?>
                                                    <span class="srwm-status srwm-status-ok"><?php _e('In Stock', 'smart-restock-waitlist'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="srwm-action-buttons">
                                                    <button class="button button-small view-waitlist" data-product-id="<?php echo esc_attr($product->product_id); ?>">
                                                        <span class="dashicons dashicons-groups"></span>
                                                        <?php _e('View', 'smart-restock-waitlist'); ?>
                                                    </button>
                                                    <button class="button button-primary button-small restock-product" data-product-id="<?php echo esc_attr($product->product_id); ?>">
                                                        <span class="dashicons dashicons-update"></span>
                                                        <?php _e('Restock', 'smart-restock-waitlist'); ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="srwm-empty-state">
                                            <div class="srwm-empty-icon">
                                                <span class="dashicons dashicons-groups"></span>
                                            </div>
                                            <h3><?php _e('No Products with Waitlists', 'smart-restock-waitlist'); ?></h3>
                                            <p><?php _e('When customers join waitlists for products, they will appear here.', 'smart-restock-waitlist'); ?></p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($this->license_manager->is_pro_active() && !empty($supplier_products)): ?>
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
                                                <button class="button button-small generate-restock-link" data-product-id="<?php echo $product_data['product_id']; ?>">
                                                    <span class="dashicons dashicons-admin-links"></span>
                                                    <?php _e('Restock Link', 'smart-restock-waitlist'); ?>
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
                    <?php endif; ?>
                </div>
                    </div>
                    
                    <!-- Analytics Tab -->
                    <div class="srwm-tab-content" data-tab="analytics">
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php _e('Advanced Analytics', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Detailed analytics and performance insights', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-analytics-grid">
                                <div class="srwm-analytics-card">
                                    <h3><?php _e('Trend Analysis', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Coming soon: Advanced trend analysis and predictions', 'smart-restock-waitlist'); ?></p>
                                </div>
                                
                                <div class="srwm-analytics-card">
                                    <h3><?php _e('Performance Metrics', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Coming soon: Detailed performance metrics and KPIs', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reports Tab -->
                    <div class="srwm-tab-content" data-tab="reports">
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-analytics"></span>
                                    <?php _e('Custom Reports', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Generate custom reports and export data', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-reports-grid">
                                <div class="srwm-report-card">
                                    <h3><?php _e('Waitlist Report', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Export comprehensive waitlist data', 'smart-restock-waitlist'); ?></p>
                                    <button class="srwm-btn srwm-btn-primary" onclick="exportDashboardReport()">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php _e('Generate Report', 'smart-restock-waitlist'); ?>
                                    </button>
                                </div>
                                
                                <div class="srwm-report-card">
                                    <h3><?php _e('Restock Activity Report', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Coming soon: Detailed restock activity analysis', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Tab -->
                    <div class="srwm-tab-content" data-tab="actions">
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php _e('Quick Actions', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Common tasks and shortcuts for efficient management', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-actions-grid">
                                <div class="srwm-action-card">
                                    <div class="srwm-action-icon">
                                        <span class="dashicons dashicons-list-view"></span>
                                    </div>
                                    <div class="srwm-action-content">
                                        <h3><?php _e('View Waitlists', 'smart-restock-waitlist'); ?></h3>
                                        <p><?php _e('Browse and manage customer waitlists', 'smart-restock-waitlist'); ?></p>
                                    </div>
                                    <div class="srwm-action-footer">
                                        <button id="srwm-view-waitlists" class="srwm-btn srwm-btn-primary">
                                            <?php _e('Open', 'smart-restock-waitlist'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($this->license_manager->is_pro_active()): ?>
                                <div class="srwm-action-card">
                                    <div class="srwm-action-icon">
                                        <span class="dashicons dashicons-businessman"></span>
                                    </div>
                                    <div class="srwm-action-content">
                                        <h3><?php _e('Manage Suppliers', 'smart-restock-waitlist'); ?></h3>
                                        <p><?php _e('Add and manage supplier relationships', 'smart-restock-waitlist'); ?></p>
                                    </div>
                                    <div class="srwm-action-footer">
                                        <button id="srwm-manage-suppliers" class="srwm-btn srwm-btn-primary">
                                            <?php _e('Open', 'smart-restock-waitlist'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="srwm-action-card srwm-pro-locked">
                                    <div class="srwm-action-icon">
                                        <span class="dashicons dashicons-lock"></span>
                                    </div>
                                    <div class="srwm-action-content">
                                        <h3><?php _e('Supplier Management', 'smart-restock-waitlist'); ?></h3>
                                        <p><?php _e('Advanced supplier management with categories, analytics, and bulk operations', 'smart-restock-waitlist'); ?></p>
                                    </div>
                                    <div class="srwm-action-footer">
                                        <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-license'); ?>" class="srwm-btn srwm-btn-secondary">
                                            <?php _e('Upgrade to Pro', 'smart-restock-waitlist'); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="srwm-action-card">
                                    <div class="srwm-action-icon">
                                        <span class="dashicons dashicons-star-filled"></span>
                                    </div>
                                    <div class="srwm-action-content">
                                        <h3><?php _e('Pro Features', 'smart-restock-waitlist'); ?></h3>
                                        <p><?php _e('Explore advanced features and capabilities', 'smart-restock-waitlist'); ?></p>
                                    </div>
                                    <div class="srwm-action-footer">
                                        <button id="srwm-pro-features" class="srwm-btn srwm-btn-secondary">
                                            <?php _e('Explore', 'smart-restock-waitlist'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="srwm-action-card">
                                    <div class="srwm-action-icon">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                    </div>
                                    <div class="srwm-action-content">
                                        <h3><?php _e('Settings', 'smart-restock-waitlist'); ?></h3>
                                        <p><?php _e('Configure plugin settings and preferences', 'smart-restock-waitlist'); ?></p>
                                    </div>
                                    <div class="srwm-action-footer">
                                        <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-settings'); ?>" class="srwm-btn srwm-btn-secondary">
                                            <?php _e('Configure', 'smart-restock-waitlist'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
                <?php if (!$this->license_manager->is_pro_active()): ?>
                <div class="srwm-pro-card">
                    <div class="srwm-pro-card-header">
                        <h2><?php _e('Supplier Management', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-pro-actions">
                            <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-license'); ?>" class="button button-primary">
                                <span class="dashicons dashicons-star-filled"></span>
                                <?php _e('Upgrade to Pro', 'smart-restock-waitlist'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="srwm-pro-card-content">
                        <div class="srwm-pro-feature-preview">
                            <div class="srwm-pro-feature-icon">
                                <span class="dashicons dashicons-businessman"></span>
                            </div>
                            <div class="srwm-pro-feature-content">
                                <h3><?php _e('Advanced Supplier Management', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Upgrade to Pro to unlock advanced supplier management features:', 'smart-restock-waitlist'); ?></p>
                                <ul>
                                    <li><?php _e('• Supplier profiles with company information', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('• Supplier categories and trust scores', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('• Bulk CSV upload operations', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('• Secure restock link generation', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('• Supplier analytics and performance tracking', 'smart-restock-waitlist'); ?></li>
                                </ul>
                                <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-license'); ?>" class="srwm-btn srwm-btn-primary">
                                    <?php _e('Upgrade to Pro', 'smart-restock-waitlist'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stat Card Detail Modals -->
        <div id="srwm-stat-detail-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content srwm-stat-modal">
                <div class="srwm-modal-header">
                    <h2 id="srwm-stat-modal-title"><?php _e('Statistics Details', 'smart-restock-waitlist'); ?></h2>
                    <span class="srwm-modal-close">&times;</span>
                </div>
                <div class="srwm-modal-body">
                    <div id="srwm-stat-modal-content"></div>
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
        /* Optimized Modern Dashboard Styles */
        .srwm-dashboard {
            margin: 20px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 0;
        }
        
        /* Enhanced Analytics Styles */
        .srwm-analytics-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .srwm-header-content h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .srwm-header-content p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .srwm-header-actions {
            display: flex;
            gap: 12px;
        }
        
        /* Real-time Section */
        .srwm-realtime-section {
            margin-bottom: 32px;
        }
        
        .srwm-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .srwm-section-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .srwm-live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #22c55e;
            font-weight: 500;
        }
        
        .srwm-live-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .srwm-realtime-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .srwm-realtime-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .srwm-realtime-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-realtime-icon {
            font-size: 32px;
            flex-shrink: 0;
        }
        
        .srwm-realtime-content h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-realtime-number {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .srwm-realtime-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Dashboard Analytics Enhancements */
        .srwm-section-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .srwm-live-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #22c55e;
            font-size: 12px;
            font-weight: 500;
        }
        
        .srwm-live-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .srwm-analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .srwm-analytics-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .srwm-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .srwm-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #374151;
        }
        
        .srwm-activity-feed {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .srwm-activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .srwm-activity-item:last-child {
            border-bottom: none;
        }
        
        .srwm-activity-icon {
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .srwm-activity-content {
            flex: 1;
        }
        
        .srwm-activity-text {
            font-size: 13px;
            color: #374151;
            margin-bottom: 2px;
        }
        
        .srwm-activity-time {
            font-size: 11px;
            color: #9ca3af;
        }
        
        .srwm-metrics-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .srwm-metric-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        
        .srwm-metric-label {
            font-size: 13px;
            color: #6b7280;
        }
        
        .srwm-metric-value {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        
        /* Export Actions */
        .srwm-export-actions {
            display: flex;
            gap: 16px;
            margin-top: 20px;
        }
        
        /* Enhanced Dashboard Header */
        .srwm-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .srwm-breadcrumb-item {
            color: #6b7280;
            text-decoration: none;
        }
        
        .srwm-breadcrumb-item.active {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .srwm-breadcrumb-separator {
            color: #d1d5db;
        }
        
        .srwm-action-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .srwm-period-selector {
            position: relative;
        }
        
        .srwm-select {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .srwm-select:hover {
            border-color: #3b82f6;
        }
        
        .srwm-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Dashboard Tabs */
        .srwm-dashboard-tabs {
            display: flex;
            gap: 2px;
            margin-top: 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 8px 8px 0 0;
            padding: 4px;
        }
        
        .srwm-tab-button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .srwm-tab-button:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .srwm-tab-button.active {
            background: white;
            color: #3b82f6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-tab-button .dashicons {
            font-size: 16px;
        }
        
        /* Enhanced Stat Cards */
        .srwm-stat-card {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .srwm-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .srwm-stat-card:hover::before {
            transform: scaleX(1);
        }
        
        /* Live Indicators */
        .srwm-live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .srwm-live-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Floating Action Button temporarily disabled */
        
        /* Smart Notifications */
        .srwm-notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Enhanced Loading States */
        .srwm-loading {
            position: relative;
            overflow: hidden;
        }
        
        .srwm-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Auto-refresh animation */
        @keyframes auto-refresh-pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
        
        .srwm-auto-refresh {
            animation: auto-refresh-pulse 2s ease-in-out;
        }
        
        /* Dashboard Tabs */
        .srwm-dashboard-tabs {
            display: flex;
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        
        .srwm-tab-button {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
            color: #6b7280;
            font-size: 14px;
        }
        
        .srwm-tab-button:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .srwm-tab-button.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-tab-button .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        /* Tab Content */
        .srwm-tab-content {
            display: none;
        }
        
        .srwm-tab-content.active {
            display: block;
        }
        
        /* Analytics Grid */
        .srwm-analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .srwm-analytics-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .srwm-analytics-card.srwm-card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-analytics-card.srwm-card-animated {
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .srwm-analytics-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 20px;
            color: #fff;
        }
        
        .srwm-icon-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }
        
        .srwm-icon-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .srwm-icon-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .srwm-icon-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }
        
        .srwm-analytics-content {
            flex: 1;
        }
        
        .srwm-analytics-number {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .srwm-unit {
            font-size: 18px;
            font-weight: 500;
            color: #6b7280;
        }
        
        .srwm-analytics-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .srwm-analytics-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .srwm-trend-up {
            color: #10b981;
        }
        
        .srwm-trend-down {
            color: #ef4444;
        }
        
        .srwm-trend-neutral {
            color: #6b7280;
        }
        
        .srwm-analytics-trend i {
            font-size: 10px;
        }
        
        /* Legacy support for old structure */
        .srwm-analytics-card h3 {
            margin: 0 0 12px 0;
            color: #111827;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-analytics-card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.5;
        }
        
        /* Reports Grid */
        .srwm-reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        .srwm-report-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .srwm-report-card h3 {
            margin: 0 0 12px 0;
            color: #111827;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-report-card p {
            margin: 0 0 20px 0;
            color: #6b7280;
            line-height: 1.5;
        }
        
        /* Global Period Selector */
        .srwm-period-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-right: 16px;
        }
        
        .srwm-period-selector label {
            font-weight: 500;
            color: #374151;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .srwm-period-select {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 140px;
        }
        
        .srwm-period-select:hover {
            border-color: #3b82f6;
        }
        
        .srwm-period-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Header Actions Layout */
        .srwm-header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* Enhanced Search and Filters */
        .srwm-search-filters {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .srwm-search-box {
            position: relative;
            min-width: 300px;
        }
        
        .srwm-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }
        
        .srwm-search-box input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #374151;
            background: #fff;
            transition: all 0.2s ease;
        }
        
        .srwm-search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .srwm-filter-group {
            display: flex;
            gap: 12px;
        }
        
        .srwm-select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #374151;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 140px;
        }
        
        .srwm-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Quick Actions Grid */
        .srwm-quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        
        /* Bulk Actions Modal Styles */
        .srwm-bulk-actions-content {
            padding: 20px 0;
        }
        
        .srwm-bulk-section {
            margin-bottom: 25px;
        }
        
        .srwm-bulk-section h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }
        
        .srwm-bulk-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .srwm-radio-option,
        .srwm-checkbox-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .srwm-radio-option:hover,
        .srwm-checkbox-option:hover {
            background-color: #f8f9fa;
            border-color: #0073aa;
        }
        
        .srwm-radio-option input[type="radio"],
        .srwm-checkbox-option input[type="checkbox"] {
            margin: 0;
            cursor: pointer;
        }
        
        .srwm-radio-label,
        .srwm-checkbox-label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            flex: 1;
        }
        
        .srwm-bulk-selection {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            background: #f9f9f9;
        }
        
        .srwm-po-checkboxes {
            margin-top: 15px;
        }
        
        .srwm-loading {
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        .srwm-error {
            color: #dc3545;
            text-align: center;
            font-weight: 500;
        }
        
        .srwm-quick-action-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .srwm-quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-quick-action-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 24px;
            color: #fff;
        }
        
        .srwm-quick-action-card h3 {
            margin: 0 0 8px 0;
            color: #111827;
            font-size: 18px;
            font-weight: 600;
        }
        
        .srwm-quick-action-card p {
            margin: 0 0 20px 0;
            color: #6b7280;
            line-height: 1.5;
            font-size: 14px;
        }
        
        /* Placeholder Content */
        .srwm-placeholder-content {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .srwm-placeholder-icon {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }
        
        .srwm-placeholder-content h3 {
            margin: 0 0 8px 0;
            color: #374151;
            font-size: 20px;
            font-weight: 600;
        }
        
        .srwm-placeholder-content p {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Enhanced PO Modal Styles */
        .srwm-po-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .srwm-step {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: #e5e7eb;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .srwm-step.active {
            background: #3b82f6;
            color: #fff;
        }
        
        .srwm-step.completed {
            background: #10b981;
            color: #fff;
        }
        
        .srwm-step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .srwm-step-label {
            font-size: 14px;
            font-weight: 500;
        }
        
        .srwm-po-step-content {
            display: none;
            padding: 24px;
        }
        
        .srwm-po-step-content.active {
            display: block;
        }
        
        .srwm-step-header {
            margin-bottom: 24px;
        }
        
        .srwm-step-header h3 {
            margin: 0 0 8px 0;
            color: #111827;
            font-size: 20px;
            font-weight: 600;
        }
        
        .srwm-step-header p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Product Search Section */
        .srwm-product-search-section {
            margin-bottom: 24px;
        }
        
        .srwm-bulk-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        
        /* Products Grid */
        .srwm-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            max-height: 400px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }
        
        .srwm-product-card {
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .srwm-product-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .srwm-product-card.selected {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .srwm-product-card input[type="checkbox"] {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 18px;
            height: 18px;
        }
        
        .srwm-product-info {
            margin-bottom: 12px;
        }
        
        .srwm-product-name {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .srwm-product-sku {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .srwm-product-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #6b7280;
        }
        
        .srwm-stock-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .srwm-stock-low {
            background: #fef3c7;
            color: #d97706;
        }
        
        .srwm-stock-out {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .srwm-stock-ok {
            background: #d1fae5;
            color: #059669;
        }
        
        /* Selected Products */
        .srwm-selected-products {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .srwm-selected-product {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 12px;
            background: #fff;
        }
        
        .srwm-selected-product-info {
            flex: 1;
        }
        
        .srwm-selected-product-name {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .srwm-selected-product-sku {
            font-size: 12px;
            color: #6b7280;
        }
        
        .srwm-quantity-input {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .srwm-quantity-input input {
            width: 80px;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-align: center;
        }
        
        .srwm-suggested-quantity {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        /* Step Actions */
        .srwm-step-actions {
            display: flex;
            gap: 12px;
        }
        
        /* Pagination */
        .srwm-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .srwm-pagination button {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .srwm-pagination button:hover {
            background: #f3f4f6;
        }
        
        .srwm-pagination button.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }
        
        .srwm-pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Loading State */
        .srwm-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 40px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .srwm-loading i {
            font-size: 18px;
        }
        
        /* Additional PO Modal Styles */
        .srwm-waitlist-info {
            font-size: 11px;
            color: #dc2626;
            font-weight: 500;
            margin-top: 4px;
        }
        
        .srwm-no-products {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .srwm-error {
            text-align: center;
            padding: 40px;
            color: #dc2626;
            font-size: 14px;
        }
        
        .srwm-pagination-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 12px;
        }
        
        .srwm-pagination-btn:hover {
            background: #f3f4f6;
        }
        
        .srwm-pagination-btn.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }
        
        .srwm-pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .srwm-pagination-dots {
            padding: 8px 4px;
            color: #6b7280;
        }
        
        .srwm-review-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .srwm-review-section h4 {
            margin: 0 0 16px 0;
            color: #111827;
            font-size: 16px;
            font-weight: 600;
        }
        
        .srwm-review-products {
            margin-bottom: 20px;
        }
        
        .srwm-review-product {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #fff;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-review-product-name {
            font-weight: 500;
            color: #111827;
        }
        
        .srwm-review-product-quantity {
            font-weight: 600;
            color: #3b82f6;
        }
        
        .srwm-review-supplier p {
            margin: 8px 0;
            color: #374151;
        }
        
        .srwm-quantity-input input.error {
            border-color: #dc2626;
            background: #fef2f2;
        }
        
        /* Delivery Method Selection */
        .srwm-delivery-method {
            margin-bottom: 24px;
        }
        
        .srwm-method-option {
            margin-bottom: 16px;
        }
        
        .srwm-method-option input[type="radio"] {
            display: none;
        }
        
        .srwm-method-option label {
            display: block;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s ease;
            background: #fff;
        }
        
        .srwm-method-option input[type="radio"]:checked + label {
            border-color: #3b82f6;
            background: #f0f9ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .srwm-method-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .srwm-method-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #6b7280;
        }
        
        .srwm-method-option input[type="radio"]:checked + label .srwm-method-icon {
            background: #3b82f6;
            color: #fff;
        }
        
        .srwm-method-info h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
        
        .srwm-method-info p {
            margin: 0;
            font-size: 14px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .srwm-form-section {
            margin-bottom: 24px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-help-text {
            margin: 8px 0 0 0;
            font-size: 12px;
            color: #6b7280;
            font-style: italic;
        }
        
        /* Fix dark colors to match dashboard */
        .srwm-modal {
            background: rgba(0, 0, 0, 0.4);
        }
        
        .srwm-modal-content {
            background: #fff;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-modal-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .srwm-modal-header h2 {
            color: #111827;
        }
        
        .srwm-modal-close {
            color: #6b7280;
        }
        
        .srwm-modal-close:hover {
            color: #111827;
        }
        
        .srwm-po-steps {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .srwm-step {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .srwm-step.active {
            background: #3b82f6;
            color: #fff;
        }
        
        .srwm-step.completed {
            background: #10b981;
            color: #fff;
        }
        
        .srwm-products-grid {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-product-card {
            background: #fff;
            border: 2px solid #e5e7eb;
        }
        
        .srwm-product-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .srwm-product-card.selected {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .srwm-selected-product {
            background: #fff;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-review-section {
            background: #f9fafb;
        }
        
        .srwm-review-product {
            background: #fff;
            border: 1px solid #e5e7eb;
        }
        
        /* Fix dark form elements */
        .srwm-form-group input[type="date"],
        .srwm-form-group input[type="email"],
        .srwm-form-group input[type="text"],
        .srwm-form-group input[type="number"],
        .srwm-form-group select,
        .srwm-form-group textarea {
            background: #fff !important;
            color: #111827 !important;
            border: 1px solid #d1d5db !important;
        }
        
        .srwm-form-group input[type="date"]:focus,
        .srwm-form-group input[type="email"]:focus,
        .srwm-form-group input[type="text"]:focus,
        .srwm-form-group input[type="number"]:focus,
        .srwm-form-group select:focus,
        .srwm-form-group textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }
        
        .srwm-form-group input[type="checkbox"] {
            background: #fff !important;
            border: 1px solid #d1d5db !important;
        }
        
        .srwm-form-group input[type="checkbox"]:checked {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
        }
        
        .srwm-form-group label {
            color: #111827 !important;
            font-weight: 500 !important;
        }
        
        .srwm-form-group input[type="checkbox"] + label {
            color: #374151 !important;
            font-weight: 400 !important;
        }
        
        /* Fix any remaining dark elements */
        .srwm-modal * {
            color: inherit;
        }
        
        .srwm-modal input,
        .srwm-modal select,
        .srwm-modal textarea {
            color: #111827 !important;
        }
        
        .srwm-modal label {
            color: #111827 !important;
        }
        
        /* Button Styling */
        .srwm-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }
        
        .srwm-btn-primary {
            background: #3b82f6;
            color: #fff;
        }
        
        .srwm-btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-btn-secondary {
            background: #6b7280;
            color: #fff;
        }
        
        .srwm-btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        .srwm-btn-success {
            background: #10b981;
            color: #fff;
        }
        
        .srwm-btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .srwm-btn-outline {
            background: transparent;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
        
        .srwm-btn-outline:hover {
            background: #3b82f6;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .srwm-btn i {
            font-size: 16px;
        }
        
        /* Step Actions Styling */
        .srwm-step-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .srwm-modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        /* Notification Styling */
        .srwm-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 4px solid #10b981;
            animation: slideInRight 0.3s ease-out;
        }
        
        .srwm-notification-success {
            border-left-color: #10b981;
        }
        
        .srwm-notification-error {
            border-left-color: #ef4444;
        }
        
        .srwm-notification-warning {
            border-left-color: #f59e0b;
        }
        
        .srwm-notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
        }
        
        .srwm-notification-content i {
            font-size: 20px;
            color: #10b981;
        }
        
        .srwm-notification-error .srwm-notification-content i {
            color: #ef4444;
        }
        
        .srwm-notification-warning .srwm-notification-content i {
            color: #f59e0b;
        }
        
        .srwm-notification-content span {
            color: #111827;
            font-weight: 500;
        }
        
        .srwm-notification-close {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            font-size: 18px;
            color: #6b7280;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .srwm-notification-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Dashboard Loading State */
        .srwm-loading {
            position: relative;
        }
        
        .srwm-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            pointer-events: none;
        }
        
        .srwm-loading .srwm-stat-card,
        .srwm-loading .srwm-chart-card {
            opacity: 0.6;
            transition: opacity 0.3s ease;
        }
        
        /* Interactive Table Controls - Matching Dashboard Design */
        .srwm-table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 20px;
            flex-wrap: wrap;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        
        .srwm-table-info {
            display: flex;
            align-items: center;
        }
        

        
        .srwm-table-search {
            position: relative;
            flex: 1;
            max-width: 300px;
        }
        
        .srwm-search-input {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .srwm-table-search .dashicons {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 16px;
        }
        
        .srwm-table-filters {
            display: flex;
            gap: 12px;
        }
        
        .srwm-filter-select {
            padding: 10px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            min-width: 140px;
        }
        
        .srwm-filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Sortable Table Headers - Enhanced Design */
        .srwm-sortable {
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: all 0.3s ease;
            padding: 16px 12px;
            font-weight: 600;
            color: #374151;
        }
        
        .srwm-sortable:hover {
            background-color: #f8fafc;
            color: #3b82f6;
            transform: translateY(-1px);
        }
        
        .srwm-sort-icon {
            margin-left: 8px;
            font-size: 14px;
            color: #9ca3af;
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        
        .srwm-sortable.sorted-asc .srwm-sort-icon {
            color: #3b82f6;
            transform: rotate(0deg);
            opacity: 1;
        }
        
        .srwm-sortable.sorted-desc .srwm-sort-icon {
            color: #3b82f6;
            transform: rotate(180deg);
            opacity: 1;
        }
        
        /* Table Row Filtering */
        .srwm-table-row-hidden {
            display: none !important;
        }
        
        .srwm-table-row-highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2) !important;
            transform: scale(1.01);
            transition: all 0.3s ease;
        }
        
        /* Enhanced Stat Cards - Clickable */
        .srwm-stat-card {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .srwm-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .srwm-stat-card:hover::before {
            left: 100%;
        }
        
        .srwm-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .srwm-stat-card:active {
            transform: translateY(-2px);
        }
        
        /* Stat Card Click Indicator */
        .srwm-stat-card::after {
            content: 'Click for details';
            position: absolute;
            bottom: 8px;
            right: 12px;
            font-size: 10px;
            color: #6b7280;
            opacity: 0;
            transition: opacity 0.3s ease;
            font-weight: 500;
        }
        
        .srwm-stat-card:hover::after {
            opacity: 1;
        }
        
        /* Stat Detail Modal */
        .srwm-stat-modal {
            max-width: 800px;
            width: 90%;
        }
        
        .srwm-stat-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .srwm-stat-detail-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-stat-detail-card h4 {
            margin: 0 0 8px 0;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
        }
        
        .srwm-stat-detail-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .srwm-stat-detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        
        .srwm-stat-detail-table th,
        .srwm-stat-detail-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .srwm-stat-detail-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .srwm-stat-detail-table tr:hover {
            background: #f8fafc;
        }
        
        /* Loading Spinner */
        .srwm-loading-content {
            text-align: center;
            padding: 40px 20px;
        }
        
        .srwm-loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Status Badges for Modal */
        .srwm-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .srwm-status-waiting {
            background: #fef3c7;
            color: #92400e;
        }
        
        .srwm-status-notified {
            background: #d1fae5;
            color: #065f46;
        }
        
        .srwm-status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .srwm-status-critical {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Error Messages */
        .srwm-error {
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 8px;
            margin: 16px 0;
        }
        
        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .srwm-dashboard-tabs {
                flex-wrap: wrap;
            }
            
            .srwm-tab-button {
                flex: 1 1 calc(50% - 4px);
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .srwm-action-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .srwm-fab {
                bottom: 16px;
                right: 16px;
                width: 48px;
                height: 48px;
                font-size: 20px;
            }
        }
        
        /* Modal Styles */
        .srwm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .srwm-modal.srwm-modal-active {
            display: flex;
        }
        
        .srwm-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .srwm-modal-content.srwm-modal-large {
            max-width: 900px;
            width: 95%;
            max-height: 90vh;
        }
        
        .srwm-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .srwm-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .srwm-modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        .srwm-modal-body {
            padding: 24px;
        }
        
        /* Quick Actions Menu */
        .srwm-quick-actions-menu {
            position: fixed;
            bottom: 100px;
            right: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 8px;
            z-index: 1001;
            min-width: 200px;
        }
        
        .srwm-quick-action-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #374151;
        }
        
        .srwm-quick-action-item:hover {
            background: #f3f4f6;
            color: #3b82f6;
        }
        
        .srwm-quick-action-item .dashicons {
            font-size: 18px;
            color: #6b7280;
        }
        
        .srwm-quick-action-item:hover .dashicons {
            color: #3b82f6;
        }
        
        /* Smart Notifications */
        .srwm-smart-notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 16px;
            z-index: 1002;
            max-width: 350px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .srwm-notification-icon {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .srwm-notification-content h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-notification-content p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .srwm-notification-close {
            background: none;
            border: none;
            font-size: 18px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        
        .srwm-notification-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        
        /* Quick Navigation */
        .srwm-quick-nav {
            margin-bottom: 24px;
        }
        
        .srwm-nav-notice {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #1e40af;
        }
        
        .srwm-nav-notice .dashicons {
            color: #3b82f6;
            font-size: 16px;
        }
        
        .srwm-nav-notice a {
            color: #1e40af;
            text-decoration: none;
            font-weight: 600;
        }
        
        .srwm-nav-notice a:hover {
            text-decoration: underline;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .srwm-analytics-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .srwm-realtime-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .srwm-analytics-grid {
                grid-template-columns: 1fr;
            }
            

        }
        
        /* Pro Locked Elements */
        .srwm-pro-locked {
            opacity: 0.7;
            position: relative;
        }
        
        .srwm-pro-locked::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 8px;
            pointer-events: none;
        }
        
        .srwm-pro-locked .srwm-stat-icon .dashicons,
        .srwm-pro-locked .srwm-action-icon .dashicons {
            color: #ff6b35;
        }
        
        .srwm-pro-feature-preview {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        
        .srwm-pro-feature-icon {
            font-size: 48px;
            color: rgba(255,255,255,0.9);
        }
        
        .srwm-pro-feature-content h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .srwm-pro-feature-content p {
            margin: 0 0 15px 0;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .srwm-pro-feature-content ul {
            margin: 0 0 20px 0;
            padding-left: 20px;
        }
        
        .srwm-pro-feature-content li {
            margin-bottom: 5px;
            opacity: 0.9;
        }
        
        /* Enhanced Header Section */
        .srwm-dashboard-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 25%, #8b5cf6 50%, #a855f7 75%, #c084fc 100%);
            border-radius: 20px;
            margin-bottom: 40px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(79, 70, 229, 0.25), 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .srwm-dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .srwm-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 40px;
            color: white;
            position: relative;
            z-index: 2;
        }
        
        .srwm-header-left {
            flex: 1;
        }
        
        .srwm-page-title {
            margin: 0 0 12px 0;
            font-size: 36px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 16px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
        }
        
        .srwm-page-title .dashicons {
            font-size: 36px;
            width: 36px;
            height: 36px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        
        .srwm-page-subtitle {
            margin: 0;
            font-size: 18px;
            opacity: 0.95;
            font-weight: 400;
            line-height: 1.4;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-header-actions {
            display: flex;
            gap: 16px;
        }
        
        /* Enhanced Welcome Section */
        .srwm-welcome-section {
            margin-bottom: 40px;
        }
        
        .srwm-welcome-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 48px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.08), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }
        
        .srwm-welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #8b5cf6, #a855f7);
        }
        
        .srwm-welcome-icon {
            font-size: 72px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 28px;
            filter: drop-shadow(0 4px 8px rgba(79, 70, 229, 0.2));
        }
        
        .srwm-welcome-content h3 {
            margin: 0 0 20px 0;
            font-size: 32px;
            color: #1e293b;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .srwm-welcome-content p {
            margin: 0 0 36px 0;
            font-size: 18px;
            color: #64748b;
            line-height: 1.7;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .srwm-welcome-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Enhanced Dashboard Content */
        .srwm-dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 40px;
            padding: 0 20px;
        }
        
        /* Enhanced Section Styles */
        .srwm-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.08), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
        }
        
        .srwm-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #8b5cf6);
        }
        
        .srwm-section-header {
            margin-bottom: 32px;
        }
        
        .srwm-section-title {
            margin: 0 0 12px 0;
            font-size: 28px;
            color: #1e293b;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 16px;
            letter-spacing: -0.5px;
        }
        
        .srwm-section-title .dashicons {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            width: 28px;
            height: 28px;
        }
        
        .srwm-section-description {
            margin: 0;
            color: #64748b;
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* Enhanced Stats Grid */
        .srwm-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 28px;
        }
        
        /* Responsive grid for smaller screens */
        @media (max-width: 1400px) {
            .srwm-stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 1100px) {
            .srwm-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .srwm-stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .srwm-stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }
        
        .srwm-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #8b5cf6, #a855f7);
        }
        
        .srwm-stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12), 0 8px 32px rgba(0, 0, 0, 0.08);
        }
        
        .srwm-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .srwm-stat-icon {
            font-size: 36px;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.1));
            border-radius: 16px;
            color: #4f46e5;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }
        
        .srwm-stat-trend {
            display: flex;
            align-items: center;
        }
        
        .srwm-trend-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-trend-up {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
            color: #10b981;
        }
        
        .srwm-trend-down {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
        }
        
        .srwm-stat-content {
            text-align: center;
        }
        
        .srwm-stat-number {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #1e293b, #334155);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
            line-height: 1;
            letter-spacing: -1px;
        }
        
        .srwm-stat-label {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: #64748b;
            font-weight: 600;
        }
        
        .srwm-stat-meta {
            display: flex;
            justify-content: center;
        }
        
        .srwm-stat-period {
            font-size: 13px;
            color: #94a3b8;
            background: linear-gradient(135deg, rgba(148, 163, 184, 0.1), rgba(203, 213, 225, 0.1));
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        /* Enhanced Charts Grid */
        .srwm-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 32px;
        }
        
        .srwm-chart-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            position: relative;
            overflow: hidden;
        }
        
        .srwm-chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #8b5cf6);
        }
        
        .srwm-chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .srwm-chart-title {
            margin: 0;
            font-size: 20px;
            color: #1e293b;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }
        
        .srwm-chart-title .dashicons {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .srwm-chart-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .srwm-chart-actions .srwm-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .srwm-chart-actions .srwm-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-chart-period {
            padding: 8px 16px;
            border: 1px solid rgba(79, 70, 229, 0.2);
            border-radius: 10px;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            font-size: 14px;
            color: #4f46e5;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .srwm-chart-period:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .srwm-chart-container {
            height: 320px;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
        }
        
        /* Enhanced Actions Grid */
        .srwm-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 28px;
        }
        
        .srwm-action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }
        
        .srwm-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed, #8b5cf6);
        }
        
        .srwm-action-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12), 0 8px 32px rgba(0, 0, 0, 0.08);
        }
        
        .srwm-action-icon {
            font-size: 36px;
            color: #4f46e5;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.1));
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
        }
        
        .srwm-action-content {
            flex: 1;
            margin-bottom: 24px;
        }
        
        .srwm-action-content h3 {
            margin: 0 0 12px 0;
            font-size: 20px;
            color: #1e293b;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .srwm-action-content p {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
            font-size: 15px;
        }
        
        .srwm-action-footer {
            margin-top: auto;
        }
        
        /* Enhanced Button Styles */
        .srwm-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 1;
            position: relative;
            overflow: hidden;
            letter-spacing: -0.2px;
        }
        
        .srwm-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .srwm-btn:hover::before {
            left: 100%;
        }
        
        .srwm-btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #8b5cf6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .srwm-btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.4), 0 4px 15px rgba(79, 70, 229, 0.3);
            color: white;
        }
        
        .srwm-btn-secondary {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #4f46e5;
            border: 2px solid rgba(79, 70, 229, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .srwm-btn-secondary:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            transform: translateY(-3px) scale(1.02);
            color: #4f46e5;
            border-color: rgba(79, 70, 229, 0.4);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-btn-sm {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 10px;
        }
        
        /* Enhanced Table Styles */
        .srwm-table-container {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }
        
        .srwm-modern-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            table-layout: fixed;
        }
        
        /* Column widths for PO table */
        .srwm-modern-table th:nth-child(1) { width: 15%; } /* PO Number */
        .srwm-modern-table th:nth-child(2) { width: 20%; } /* Product */
        .srwm-modern-table th:nth-child(3) { width: 20%; } /* Supplier */
        .srwm-modern-table th:nth-child(4) { width: 10%; } /* Quantity */
        .srwm-modern-table th:nth-child(5) { width: 10%; } /* Date Created */
        .srwm-modern-table th:nth-child(6) { width: 15%; } /* Status */
        .srwm-modern-table th:nth-child(7) { width: 10%; } /* Actions */
        
        .srwm-modern-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px 16px;
            text-align: left;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid rgba(79, 70, 229, 0.1);
            font-size: 14px;
            letter-spacing: -0.2px;
        }
        
        .srwm-modern-table td {
            padding: 20px 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            vertical-align: middle;
            transition: all 0.3s ease;
        }
        
        .srwm-modern-table tr:hover {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.02), rgba(124, 58, 237, 0.02));
            transform: scale(1.001);
        }
        
        /* Purchase Order Table Specific Styles */
        .srwm-modern-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 16px 12px;
            text-align: left;
            font-weight: 700;
            color: #1e293b;
            border-bottom: 2px solid rgba(79, 70, 229, 0.1);
            font-size: 13px;
            letter-spacing: -0.2px;
            white-space: nowrap;
        }
        
        .srwm-modern-table td {
            padding: 16px 12px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            vertical-align: middle;
            transition: all 0.3s ease;
            color: #374151;
            font-size: 14px;
        }
        
        /* PO Number styling */
        .srwm-po-number strong {
            color: #1e293b;
            font-weight: 700;
            font-size: 14px;
        }
        
        /* Product info styling */
        .srwm-product-info .srwm-product-name {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .srwm-product-info .srwm-product-sku {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Supplier info styling */
        .srwm-supplier-info .srwm-supplier-name {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .srwm-supplier-info .srwm-supplier-email {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Quantity badge styling */
        .srwm-quantity-badge {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
        }
        
        /* Status select styling */
        .srwm-status-select {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
            min-width: 120px;
        }
        
        .srwm-status-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Date info styling */
        .srwm-date-info .srwm-date {
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .srwm-date-info .srwm-time {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Action buttons styling */
        .srwm-action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
            justify-content: flex-start;
        }
        
        .srwm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 32px;
            height: 32px;
        }
        
        .srwm-btn-small {
            padding: 4px 8px;
            font-size: 11px;
            min-width: 28px;
            height: 28px;
        }
        
        .srwm-btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .srwm-btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .srwm-btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .srwm-btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .srwm-btn i {
            font-size: 12px;
        }
        
        .srwm-btn-small i {
            font-size: 10px;
        }
        
        /* Notification styles */
        .srwm-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 16px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        }
        
        .srwm-notification-success {
            background: #10b981;
        }
        
        .srwm-notification-error {
            background: #ef4444;
        }
        
        .srwm-notification-content {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .srwm-notification-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            opacity: 0.8;
        }
        
        .srwm-notification-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.1);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .srwm-product-info strong {
            display: block;
            color: #1e293b;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
        }
        
        .srwm-product-info small {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }
        
        .srwm-stock-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .srwm-stock-ok {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
            color: #10b981;
        }
        
        .srwm-stock-low {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            color: #f59e0b;
        }
        
        .srwm-waitlist-count {
            font-weight: 700;
            color: #4f46e5;
            font-size: 16px;
        }
        
        .srwm-status {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .srwm-status-ok {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
            color: #10b981;
        }
        
        .srwm-status-low {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            color: #f59e0b;
        }
        
        .srwm-status-out {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
        }
        
        /* Chart animations */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .srwm-spinning {
            animation: spin 1s linear infinite;
        }
        
        .srwm-btn-refresh-chart:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Chart empty state */
        .srwm-chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            color: #94a3b8;
            font-style: italic;
        }
        
        .srwm-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        
        .srwm-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
        
        .srwm-empty-state h3 {
            margin: 0 0 8px 0;
            color: #64748b;
            font-size: 18px;
        }
        
        .srwm-empty-state p {
            margin: 0;
            color: #94a3b8;
            font-size: 14px;
        }
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
        
        /* Enhanced PO Action Buttons and Status Styles */
        .srwm-status-select {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            background-color: #fff;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 120px;
        }
        
        .srwm-status-select:hover {
            border-color: #9ca3af;
        }
        
        .srwm-status-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .srwm-status-select:disabled {
            background-color: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        /* Action Buttons Styles */
        .srwm-action-buttons {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .srwm-btn-small {
            padding: 6px 10px;
            font-size: 11px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .srwm-btn-small:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-btn-small:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .srwm-btn-primary {
            background-color: #3b82f6;
            color: #fff;
        }
        
        .srwm-btn-primary:hover {
            background-color: #2563eb;
        }
        
        .srwm-btn-secondary {
            background-color: #6b7280;
            color: #fff;
        }
        
        .srwm-btn-secondary:hover {
            background-color: #4b5563;
        }
        
        /* PO Details Modal Styles */
        .srwm-po-details {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .srwm-po-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .srwm-po-header h2 {
            margin: 0;
            color: #111827;
            font-size: 24px;
            font-weight: 700;
        }
        
        .srwm-po-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .srwm-po-section {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-po-section h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 8px;
        }
        
        .srwm-po-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .srwm-po-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .srwm-po-info-item label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-po-info-item span {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
        }
        
        .srwm-po-notes {
            background-color: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 15px;
            font-size: 14px;
            line-height: 1.5;
            color: #374151;
        }
        
        /* Notification Styles */
        .srwm-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
        }
        
        .srwm-notification-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .srwm-notification-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .srwm-notification-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .srwm-notification-content {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .srwm-notification-content i {
            font-size: 16px;
        }
        
        .srwm-notification-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .srwm-notification-close:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Modern PO Details Modal Styles */
        .srwm-po-details-modern {
            max-width: 100%;
            margin: 0;
        }
        
        .srwm-po-overview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .srwm-po-overview::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        
        .srwm-po-main-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .srwm-po-number-display {
            flex: 1;
        }
        
        .srwm-po-number-label {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-po-number-value {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .srwm-po-meta {
            display: flex;
            gap: 20px;
        }
        
        .srwm-po-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .srwm-po-meta-item i {
            font-size: 16px;
        }
        
        .srwm-po-content-modern {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .srwm-po-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .srwm-po-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-po-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .srwm-po-card-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .srwm-po-card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .srwm-po-card-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-po-card-content {
            padding: 20px;
        }
        
        .srwm-po-info-item-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .srwm-po-info-item-modern:last-child {
            border-bottom: none;
        }
        
        .srwm-po-info-label {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 120px;
        }
        
        .srwm-po-info-value {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
            text-align: right;
            flex: 1;
        }
        
        .srwm-quantity-display {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .srwm-email-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .srwm-email-link:hover {
            text-decoration: underline;
        }
        
        .srwm-delivery-date {
            color: #059669;
            font-weight: 600;
        }
        
        .srwm-no-date {
            color: #6b7280;
            font-style: italic;
        }
        
        .srwm-urgency-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .srwm-urgency-normal {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .srwm-urgency-high {
            background: #fef3c7;
            color: #92400e;
        }
        
        .srwm-urgency-urgent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .srwm-po-notes-card {
            grid-column: 1 / -1;
        }
        
        .srwm-po-notes-content {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            font-size: 14px;
            line-height: 1.6;
            color: #374151;
        }
        
        /* Modal Header and Footer Enhancements */
        .srwm-modal-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .srwm-modal-title {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .srwm-modal-title h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-po-status-display {
            display: flex;
            align-items: center;
        }
        
        .srwm-modal-actions {
            display: flex;
            gap: 10px;
        }
        
        .srwm-modal-actions .srwm-modal-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }
        
        .srwm-modal-actions .srwm-modal-close:hover {
            background-color: #f3f4f6;
            color: #374151;
        }
        
        .srwm-modal-actions .srwm-modal-close i {
            font-size: 16px;
        }
        
        .srwm-modal-footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .srwm-modal-footer-primary {
            display: flex;
            gap: 10px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .srwm-po-main-info {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .srwm-po-meta {
                justify-content: center;
            }
            
            .srwm-po-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-po-info-item-modern {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .srwm-po-info-value {
                text-align: left;
            }
            
            .srwm-modal-footer-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .srwm-modal-footer-primary {
                width: 100%;
                justify-content: center;
            }
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
                                    <span class="dashicons dashicons-phone"></span>
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
                                    <label><?php _e('WhatsApp Phone Number:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_whatsapp_phone" value="<?php echo esc_attr(get_option('srwm_whatsapp_phone')); ?>" class="regular-text" placeholder="+1234567890">
                                    <p class="description"><?php _e('Enter the WhatsApp phone number to send from (with country code)', 'smart-restock-waitlist'); ?></p>
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
                                    <label><?php _e('SMS Phone Number:', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" name="srwm_sms_phone" value="<?php echo esc_attr(get_option('srwm_sms_phone')); ?>" class="regular-text" placeholder="+1234567890">
                                    <p class="description"><?php _e('Enter the phone number to send SMS from (with country code)', 'smart-restock-waitlist'); ?></p>
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
                        <button type="button" class="button button-secondary" id="test-notifications">
                            <span class="dashicons dashicons-testing"></span>
                            <?php _e('Test Notifications', 'smart-restock-waitlist'); ?>
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
    public function get_default_registration_email_template() {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        $admin_email = get_option('admin_email');
        
        return 'Hi {customer_name},

Thank you for joining the waitlist for {product_name}!

We\'re excited to have you as part of our exclusive waitlist community. You\'ve been successfully added to our notification system, and we\'ll keep you updated on the status of this product.

Product Details:
- Product: {product_name}
- Product Page: {product_url}

What happens next:
✓ You\'ll be among the first to know when this product is back in stock
✓ You\'ll receive priority access to purchase before the general public
✓ You may receive exclusive discounts and special offers
✓ We\'ll only contact you when there\'s important news about this product

We understand how valuable your time is, so we promise to only send you relevant updates about this specific product.

If you have any questions or need to make changes to your waitlist preferences, please don\'t hesitate to contact our customer support team at ' . $admin_email . '.

Thank you for your interest and patience!

Best regards,
The ' . $site_name . ' Team
' . $site_url . '

---
This email was sent to you because you joined the waitlist for {product_name}.
If you no longer wish to receive these notifications, please contact us.';
    }
    
    public function get_default_waitlist_email_template() {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        $admin_email = get_option('admin_email');
        
        return 'Hi {customer_name},

Great news! {product_name} is back in stock and ready for purchase.

You can purchase it here: {product_url}

Due to high demand, we recommend purchasing soon to secure your item. Stock levels may be limited.

If you have any questions, please contact our customer support team at ' . $admin_email . '.

Thank you for your patience and loyalty!

Best regards,
' . $site_name . '
' . $site_url . '

This email was sent to you because you joined the waitlist for {product_name}.
If you no longer wish to receive these emails, please contact us.';
    }
    
    
    
    /**
     * Get default supplier email template
     */
    public function get_default_supplier_email_template() {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restock Request - ' . $site_name . '</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); padding: 40px 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #333333; line-height: 1.6; margin-bottom: 30px; }
        .product-details { background-color: #f8f9fa; border-radius: 12px; padding: 25px; margin: 30px 0; border-left: 4px solid #ff9800; }
        .product-name { font-size: 20px; font-weight: 700; color: #333333; margin-bottom: 15px; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .detail-label { font-weight: 600; color: #666666; }
        .detail-value { font-weight: 700; color: #333333; }
        .urgency-alert { background-color: #fff3e0; border-radius: 12px; padding: 25px; margin: 30px 0; border-left: 4px solid #ff9800; }
        .urgency-alert h3 { color: #e65100; margin: 0 0 15px 0; font-size: 18px; }
        .urgency-alert p { margin: 0; color: #666666; line-height: 1.5; }
        .cta-button { text-align: center; margin: 40px 0; }
        .cta-button a { display: inline-block; background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: #ffffff; padding: 18px 40px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px; }
        .footer { background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer p { margin: 0; color: #666666; font-size: 14px; }
        .social-links { margin-top: 20px; }
        .social-links a { display: inline-block; margin: 0 10px; color: #ff9800; text-decoration: none; }
        .social-links a:hover { text-decoration: underline; }
        @media only screen and (max-width: 600px) {
            .header { padding: 30px 20px; }
            .header h1 { font-size: 24px; }
            .content { padding: 30px 20px; }
            .footer { padding: 20px; }
            .cta-button a { padding: 15px 30px; font-size: 16px; }
            .detail-row { flex-direction: column; }
            .detail-value { margin-top: 5px; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>📦 Restock Request</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hi <strong>{supplier_name}</strong>,
                <br><br>
                We need to restock a product that\'s currently in high demand. Please review the details below and take action as soon as possible.
            </div>
            
            <div class="product-details">
                <div class="product-name">{product_name}</div>
                
                <div class="detail-row">
                    <span class="detail-label">SKU:</span>
                    <span class="detail-value">{sku}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Current Stock:</span>
                    <span class="detail-value">{current_stock} units</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Waitlist Count:</span>
                    <span class="detail-value">{waitlist_count} customers</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" style="color: #d32f2f; font-weight: 700;">⚠️ Low Stock Alert</span>
                </div>
            </div>
            
            <div class="urgency-alert">
                <h3>🚨 Urgent Action Required</h3>
                <p>This product has customers waiting on the waitlist. Please restock as soon as possible to avoid losing sales and disappointing customers.</p>
            </div>
            
            <div class="cta-button">
                <a href="{restock_link}">
                    📋 Process Restock
                </a>
            </div>
            
            <div style="color: #666666; font-size: 14px; line-height: 1.5; margin-top: 30px; text-align: center;">
                <p><strong>Need assistance?</strong> Contact our procurement team for support.</p>
                <p>Email: <a href="mailto:' . get_option('admin_email') . '" style="color: #ff9800;">' . get_option('admin_email') . '</a></p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>' . $site_name . '</strong></p>
            <p>Thank you for your partnership!</p>
            <div class="social-links">
                <a href="' . $site_url . '">Visit Website</a> |
                <a href="mailto:' . get_option('admin_email') . '">Contact Support</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px; color: #999999;">
                This is an automated restock request from ' . $site_name . '.<br>
                Please respond promptly to maintain our inventory levels.
            </p>
        </div>
    </div>
</body>
</html>';
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
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table") ?: 0;
    }
    
    /**
     * Get pending purchase orders count
     */
    private function get_pending_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'") ?: 0;
    }
    
    /**
     * Get completed purchase orders count
     */
    private function get_completed_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'") ?: 0;
    }
    
    /**
     * Get all purchase orders
     */
    private function get_purchase_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return array();
        }
        
        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10") ?: array();
        
        // Enhance results with product and supplier information
        foreach ($results as $po) {
            // Get product information
            $product = wc_get_product($po->product_id);
            if ($product) {
                $po->product_name = $product->get_name();
                $po->sku = $product->get_sku();
            } else {
                $po->product_name = __('Product not found', 'smart-restock-waitlist');
                $po->sku = '';
            }
            
            // Get supplier information
            $supplier = $wpdb->get_row($wpdb->prepare(
                "SELECT supplier_name FROM {$wpdb->prefix}srwm_suppliers WHERE supplier_email = %s",
                $po->supplier_email
            ));
            
            if ($supplier) {
                $po->supplier_name = $supplier->supplier_name;
            } else {
                $po->supplier_name = __('Unknown Supplier', 'smart-restock-waitlist');
            }
            
            // Map database status to frontend status for display
            $status_mapping = array(
                'draft' => 'pending',
                'confirmed' => 'confirmed',
                'sent' => 'shipped', 
                'received' => 'completed',
                'cancelled' => 'cancelled'
            );
            
            $po->display_status = isset($status_mapping[$po->status]) ? $status_mapping[$po->status] : $po->status;
        }
        
        return $results;
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
     * Get suppliers list
     */
    private function get_suppliers() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        // Add debugging
        error_log('SRWM: Getting suppliers from table: ' . $table);
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        error_log('SRWM: Table exists: ' . ($table_exists ? 'yes' : 'no'));
        
        if (!$table_exists) {
            error_log('SRWM: Suppliers table does not exist - creating it');
            $this->create_suppliers_table();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            error_log('SRWM: Table created successfully: ' . ($table_exists ? 'yes' : 'no'));
        }
        
        $suppliers = $wpdb->get_results("SELECT * FROM $table ORDER BY supplier_name ASC");
        error_log('SRWM: Found suppliers: ' . count($suppliers));
        
        // If no suppliers exist, add test suppliers
        if (empty($suppliers)) {
            $this->add_test_suppliers();
            $suppliers = $wpdb->get_results("SELECT * FROM $table ORDER BY supplier_name ASC");
            error_log('SRWM: After adding test suppliers: ' . count($suppliers));
        }
        
        return $suppliers ?: array();
    }
    
    /**
     * Create suppliers table if it doesn't exist
     */
    private function create_suppliers_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) DEFAULT NULL,
            supplier_name varchar(255) NOT NULL,
            company_name varchar(255) DEFAULT '',
            supplier_email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT '',
            address text DEFAULT '',
            contact_person varchar(255) DEFAULT '',
            notes text DEFAULT '',
            category varchar(100) DEFAULT '',
            status enum('active', 'inactive') DEFAULT 'active',
            trust_score decimal(3,2) DEFAULT 0.00,
            threshold int(11) DEFAULT 5,
            channels longtext DEFAULT '',
            auto_generate_po tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (supplier_email),
            KEY product_id (product_id),
            KEY status (status),
            KEY category (category),
            KEY trust_score (trust_score),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('SRWM: Suppliers table creation attempted');
    }
    
    /**
     * Add test suppliers if none exist
     */
    private function add_test_suppliers() {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        // Check if we already have suppliers
        $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($existing_count > 0) {
            return; // Already have suppliers
        }
        
        // Add test suppliers
        $test_suppliers = array(
            array(
                'supplier_name' => 'ABC Electronics',
                'supplier_email' => 'orders@abcelectronics.com',
                'phone' => '+1-555-0123',
                'address' => '123 Tech Street, Silicon Valley, CA 94025',
                'category' => 'Electronics',
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array(
                'supplier_name' => 'Global Parts Co.',
                'supplier_email' => 'purchasing@globalparts.com',
                'phone' => '+1-555-0456',
                'address' => '456 Industrial Blvd, Manufacturing District, TX 75001',
                'category' => 'Industrial',
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array(
                'supplier_name' => 'Premium Supplies Ltd.',
                'supplier_email' => 'orders@premiumsupplies.com',
                'phone' => '+1-555-0789',
                'address' => '789 Quality Lane, Business Park, NY 10001',
                'category' => 'General',
                'status' => 'active',
                'created_at' => current_time('mysql')
            )
        );
        
        foreach ($test_suppliers as $supplier) {
            $wpdb->insert($table, $supplier);
        }
        
        error_log('SRWM: Added ' . count($test_suppliers) . ' test suppliers');
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
            $product_obj->category = $this->get_product_category($product->get_id());
            $product_obj->waitlist_count = $this->get_waitlist_count($product->get_id());
            $results[] = $product_obj;
        }
        
        return $results;
    }
    
    /**
     * Get product category
     */
    private function get_product_category($product_id) {
        $terms = get_the_terms($product_id, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        return '';
    }
    
    /**
     * Get waitlist count for product
     */
    private function get_waitlist_count($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return 0;
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE product_id = %d",
            $product_id
        )) ?: 0;
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
        <div class="wrap srwm-dashboard">
            <!-- Header Section -->
            <div class="srwm-dashboard-header">
                <div class="srwm-header-content">
                    <div class="srwm-header-left">
                        <h1 class="srwm-page-title">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Purchase Orders', 'smart-restock-waitlist'); ?>
                        </h1>
                        <p class="srwm-page-subtitle">
                            <?php _e('Generate and manage purchase orders for suppliers when stock is low', 'smart-restock-waitlist'); ?>
                        </p>
                    </div>
                    <div class="srwm-header-actions">
                        <button class="srwm-btn srwm-btn-secondary" id="srwm-export-pos">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export POs', 'smart-restock-waitlist'); ?>
                        </button>
                        <button class="srwm-btn srwm-btn-primary" id="srwm-generate-po">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Generate New PO', 'smart-restock-waitlist'); ?>
                        </button>

                    </div>
                </div>
            </div>
            
            <div class="srwm-dashboard-content">
                <!-- Dashboard Tabs Navigation -->
                <div class="srwm-dashboard-tabs">
                    <button class="srwm-tab-button active" data-tab="overview">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Overview', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="srwm-tab-button" data-tab="analytics">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Analytics', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="srwm-tab-button" data-tab="reports">
                        <span class="dashicons dashicons-analytics"></span>
                        <?php _e('Reports', 'smart-restock-waitlist'); ?>
                    </button>
                    <button class="srwm-tab-button" data-tab="actions">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Quick Actions', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
                
                <!-- Tab Content Container -->
                <div class="srwm-tab-content-container">
                    <!-- Overview Tab -->
                    <div class="srwm-tab-content active" data-tab="overview">
                        <!-- Enhanced Analytics Cards -->
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                    <?php _e('Purchase Order Analytics', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Key metrics and performance indicators for your purchase order management', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-analytics-grid">
                                <div class="srwm-analytics-card srwm-card-hover">
                                    <div class="srwm-analytics-icon srwm-icon-primary">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div class="srwm-analytics-content">
                                        <div class="srwm-analytics-number"><?php echo number_format($this->get_total_purchase_orders()); ?></div>
                                        <div class="srwm-analytics-label"><?php _e('Total Purchase Orders', 'smart-restock-waitlist'); ?></div>
                                        <div class="srwm-analytics-trend srwm-trend-up">
                                            <i class="fas fa-arrow-up"></i>
                                            <span>12% this month</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="srwm-analytics-card srwm-card-hover">
                                    <div class="srwm-analytics-icon srwm-icon-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="srwm-analytics-content">
                                        <div class="srwm-analytics-number"><?php echo number_format($this->get_pending_purchase_orders()); ?></div>
                                        <div class="srwm-analytics-label"><?php _e('Pending Orders', 'smart-restock-waitlist'); ?></div>
                                        <div class="srwm-analytics-trend srwm-trend-neutral">
                                            <i class="fas fa-minus"></i>
                                            <span>No change</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="srwm-analytics-card srwm-card-hover">
                                    <div class="srwm-analytics-icon srwm-icon-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="srwm-analytics-content">
                                        <div class="srwm-analytics-number"><?php echo number_format($this->get_completed_purchase_orders()); ?></div>
                                        <div class="srwm-analytics-label"><?php _e('Completed Orders', 'smart-restock-waitlist'); ?></div>
                                        <div class="srwm-analytics-trend srwm-trend-up">
                                            <i class="fas fa-arrow-up"></i>
                                            <span>8% this month</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="srwm-analytics-card srwm-card-hover">
                                    <div class="srwm-analytics-icon srwm-icon-info">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="srwm-analytics-content">
                                        <div class="srwm-analytics-number">2.5<span class="srwm-unit">days</span></div>
                                        <div class="srwm-analytics-label"><?php _e('Avg. Processing Time', 'smart-restock-waitlist'); ?></div>
                                        <div class="srwm-analytics-trend srwm-trend-down">
                                            <i class="fas fa-arrow-down"></i>
                                            <span>15% faster</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Purchase Orders Table Section -->
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-list-view"></span>
                                    <?php _e('Recent Purchase Orders', 'smart-restock-waitlist'); ?>
                                </h2>
                                <div class="srwm-section-actions">
                                    <div class="srwm-search-filters">
                                        <div class="srwm-search-box">
                                            <i class="fas fa-search"></i>
                                            <input type="text" id="po-search" placeholder="<?php _e('Search by PO number, product, or supplier...', 'smart-restock-waitlist'); ?>">
                                        </div>
                                        <div class="srwm-filter-group">
                                            <select id="po-status-filter" class="srwm-select">
                                                <option value=""><?php _e('All Status', 'smart-restock-waitlist'); ?></option>
                                                <option value="pending"><?php _e('⏳ Pending', 'smart-restock-waitlist'); ?></option>
                                                <option value="confirmed"><?php _e('✅ Confirmed', 'smart-restock-waitlist'); ?></option>
                                                <option value="shipped"><?php _e('🚚 Shipped', 'smart-restock-waitlist'); ?></option>
                                                <option value="completed"><?php _e('🎉 Completed', 'smart-restock-waitlist'); ?></option>
                                            </select>
                                            <select id="po-sort-filter" class="srwm-select">
                                                <option value="newest"><?php _e('Newest First', 'smart-restock-waitlist'); ?></option>
                                                <option value="oldest"><?php _e('Oldest First', 'smart-restock-waitlist'); ?></option>
                                                <option value="status"><?php _e('By Status', 'smart-restock-waitlist'); ?></option>
                                                <option value="supplier"><?php _e('By Supplier', 'smart-restock-waitlist'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="srwm-table-container">
                                <table class="srwm-modern-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('PO Number', 'smart-restock-waitlist'); ?></th>
                                            <th><?php _e('Product', 'smart-restock-waitlist'); ?></th>
                                            <th><?php _e('Supplier', 'smart-restock-waitlist'); ?></th>
                                            <th><?php _e('Quantity', 'smart-restock-waitlist'); ?></th>
                                            <th><?php _e('Date Created', 'smart-restock-waitlist'); ?></th>
                                            <th><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                                            <th><?php _e('Actions', 'smart-restock-waitlist'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $purchase_orders = $this->get_purchase_orders();
                                        if (!empty($purchase_orders)):
                                            foreach ($purchase_orders as $po): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="srwm-po-number">
                                                        <strong><?php echo esc_html($po->po_number); ?></strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="srwm-product-info">
                                                        <div class="srwm-product-name"><?php echo esc_html($po->product_name); ?></div>
                                                        <div class="srwm-product-sku"><?php echo esc_html($po->sku); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="srwm-supplier-info">
                                                        <div class="srwm-supplier-name"><?php echo esc_html($po->supplier_name); ?></div>
                                                        <div class="srwm-supplier-email"><?php echo esc_html($po->supplier_email); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="srwm-quantity-badge"><?php echo esc_html($po->quantity); ?></span>
                                                </td>
                                                <td>
                                                    <div class="srwm-date-info">
                                                        <div class="srwm-date"><?php echo esc_html(date('M j, Y', strtotime($po->created_at))); ?></div>
                                                        <div class="srwm-time"><?php echo esc_html(date('g:i A', strtotime($po->created_at))); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select class="srwm-status-select update-po-status" data-po-id="<?php echo $po->id; ?>" data-original-value="<?php echo esc_attr($po->display_status); ?>">
                                                        <option value="pending" <?php selected($po->display_status, 'pending'); ?>><?php _e('⏳ Pending', 'smart-restock-waitlist'); ?></option>
                                                        <option value="confirmed" <?php selected($po->display_status, 'confirmed'); ?>><?php _e('✅ Confirmed', 'smart-restock-waitlist'); ?></option>
                                                        <option value="shipped" <?php selected($po->display_status, 'shipped'); ?>><?php _e('🚚 Shipped', 'smart-restock-waitlist'); ?></option>
                                                        <option value="completed" <?php selected($po->display_status, 'completed'); ?>><?php _e('🎉 Completed', 'smart-restock-waitlist'); ?></option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="srwm-action-buttons">
                                                        <button class="srwm-btn srwm-btn-small srwm-btn-primary view-po" data-po-id="<?php echo $po->id; ?>" title="<?php _e('View Details', 'smart-restock-waitlist'); ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="srwm-btn srwm-btn-small srwm-btn-secondary download-po" data-po-id="<?php echo $po->id; ?>" title="<?php _e('Download PDF', 'smart-restock-waitlist'); ?>">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                        <button class="srwm-btn srwm-btn-small srwm-btn-secondary resend-po" data-po-id="<?php echo $po->id; ?>" title="<?php _e('Resend to Supplier', 'smart-restock-waitlist'); ?>">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="7" class="srwm-empty-state">
                                                    <div class="srwm-empty-icon">
                                                        <i class="fas fa-clipboard-list"></i>
                                                    </div>
                                                    <h3><?php _e('No Purchase Orders Yet', 'smart-restock-waitlist'); ?></h3>
                                                    <p><?php _e('Start by generating your first purchase order when products run low on stock.', 'smart-restock-waitlist'); ?></p>
                                                    <button class="srwm-btn srwm-btn-primary" id="srwm-generate-first-po">
                                                        <i class="fas fa-plus"></i>
                                                        <?php _e('Generate First PO', 'smart-restock-waitlist'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Analytics Tab -->
                    <div class="srwm-tab-content" data-tab="analytics">
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php _e('Purchase Order Analytics', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Detailed analytics and insights for your purchase order performance', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-placeholder-content">
                                <div class="srwm-placeholder-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h3><?php _e('Analytics Coming Soon', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Advanced analytics and reporting features will be available in the next update.', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reports Tab -->
                    <div class="srwm-tab-content" data-tab="reports">
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-analytics"></span>
                                    <?php _e('Purchase Order Reports', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Generate comprehensive reports and export data for analysis', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-placeholder-content">
                                <div class="srwm-placeholder-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3><?php _e('Reports Coming Soon', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Advanced reporting and export features will be available in the next update.', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Tab -->
                    <div class="srwm-tab-content" data-tab="actions">
                        <div class="srwm-section">
                            <div class="srwm-section-header">
                                <h2 class="srwm-section-title">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php _e('Quick Actions', 'smart-restock-waitlist'); ?>
                                </h2>
                                <p class="srwm-section-description">
                                    <?php _e('Common actions and shortcuts for managing purchase orders', 'smart-restock-waitlist'); ?>
                                </p>
                            </div>
                            
                            <div class="srwm-quick-actions-grid">
                                <div class="srwm-quick-action-card">
                                    <div class="srwm-quick-action-icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <h3><?php _e('Generate New PO', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Create a new purchase order for low stock products', 'smart-restock-waitlist'); ?></p>
                                    <button class="srwm-btn srwm-btn-primary" id="srwm-quick-generate-po">
                                        <i class="fas fa-plus"></i>
                                        <?php _e('Generate PO', 'smart-restock-waitlist'); ?>
                                    </button>
                                </div>
                                
                                <div class="srwm-quick-action-card">
                                    <div class="srwm-quick-action-icon">
                                        <i class="fas fa-download"></i>
                                    </div>
                                    <h3><?php _e('Export Data', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Export purchase orders data for external analysis', 'smart-restock-waitlist'); ?></p>
                                    <button class="srwm-btn srwm-btn-secondary" id="srwm-quick-export">
                                        <i class="fas fa-download"></i>
                                        <?php _e('Export', 'smart-restock-waitlist'); ?>
                                    </button>
                                </div>
                                
                                <div class="srwm-quick-action-card">
                                    <div class="srwm-quick-action-icon">
                                        <i class="fas fa-sync"></i>
                                    </div>
                                    <h3><?php _e('Bulk Actions', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Perform bulk operations on multiple purchase orders', 'smart-restock-waitlist'); ?></p>
                                    <button class="srwm-btn srwm-btn-secondary" id="srwm-quick-bulk">
                                        <i class="fas fa-tasks"></i>
                                        <?php _e('Bulk Actions', 'smart-restock-waitlist'); ?>
                                    </button>
                                </div>
                                
                                <div class="srwm-quick-action-card">
                                    <div class="srwm-quick-action-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <h3><?php _e('Settings', 'smart-restock-waitlist'); ?></h3>
                                    <p><?php _e('Configure purchase order settings and preferences', 'smart-restock-waitlist'); ?></p>
                                    <button class="srwm-btn srwm-btn-secondary" id="srwm-quick-settings">
                                        <i class="fas fa-cog"></i>
                                        <?php _e('Settings', 'smart-restock-waitlist'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced PO Generation Modal -->
        <div id="srwm-po-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content srwm-modal-large">
                <div class="srwm-modal-header">
                    <h2><?php _e('Generate Purchase Order', 'smart-restock-waitlist'); ?></h2>
                    <span class="srwm-modal-close">&times;</span>
                </div>
                
                <!-- Step Navigation -->
                <div class="srwm-po-steps">
                    <div class="srwm-step active" data-step="1">
                        <div class="srwm-step-number">1</div>
                        <div class="srwm-step-label"><?php _e('Select Products', 'smart-restock-waitlist'); ?></div>
                    </div>
                    <div class="srwm-step" data-step="2">
                        <div class="srwm-step-number">2</div>
                        <div class="srwm-step-label"><?php _e('Set Quantities', 'smart-restock-waitlist'); ?></div>
                    </div>
                    <div class="srwm-step" data-step="3">
                        <div class="srwm-step-number">3</div>
                        <div class="srwm-step-label"><?php _e('Supplier & Delivery', 'smart-restock-waitlist'); ?></div>
                    </div>
                    <div class="srwm-step" data-step="4">
                        <div class="srwm-step-number">4</div>
                        <div class="srwm-step-label"><?php _e('Review & Generate', 'smart-restock-waitlist'); ?></div>
                    </div>
                </div>
                
                <div class="srwm-modal-body">
                    <!-- Step 1: Product Selection -->
                    <div class="srwm-po-step-content active" data-step="1">
                        <div class="srwm-step-header">
                            <h3><?php _e('Select Products for Purchase Order', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Search and select products that need restocking. You can select multiple products at once.', 'smart-restock-waitlist'); ?></p>
                        </div>
                        
                        <!-- Search and Filters -->
                        <div class="srwm-product-search-section">
                            <div class="srwm-search-filters">
                                <div class="srwm-search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="po-product-search" placeholder="<?php _e('Search products by name, SKU, or category...', 'smart-restock-waitlist'); ?>">
                                </div>
                                <div class="srwm-filter-group">
                                    <select id="po-category-filter" class="srwm-select">
                                        <option value=""><?php _e('All Categories', 'smart-restock-waitlist'); ?></option>
                                        <?php 
                                        $categories = $this->get_product_categories();
                                        if ($categories) {
                                            foreach ($categories as $slug => $name): 
                                        ?>
                                            <option value="<?php echo esc_attr($name); ?>"><?php echo esc_html($name); ?></option>
                                        <?php 
                                            endforeach;
                                        }
                                        ?>
                                    </select>
                                    <select id="po-stock-filter" class="srwm-select">
                                        <option value=""><?php _e('All Stock Levels', 'smart-restock-waitlist'); ?></option>
                                        <option value="low"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></option>
                                        <option value="out"><?php _e('Out of Stock', 'smart-restock-waitlist'); ?></option>
                                        <option value="waitlist"><?php _e('Has Waitlist', 'smart-restock-waitlist'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="srwm-bulk-actions">
                                <button type="button" class="srwm-btn srwm-btn-outline" id="po-select-all">
                                    <i class="fas fa-check-square"></i>
                                    <?php _e('Select All', 'smart-restock-waitlist'); ?>
                                </button>
                                <button type="button" class="srwm-btn srwm-btn-outline" id="po-clear-selection">
                                    <i class="fas fa-square"></i>
                                    <?php _e('Clear Selection', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Products Grid -->
                        <div class="srwm-products-grid" id="po-products-grid">
                            <div class="srwm-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <?php _e('Loading products...', 'smart-restock-waitlist'); ?>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="srwm-pagination" id="po-products-pagination">
                            <!-- Pagination will be loaded dynamically -->
                        </div>
                    </div>
                    
                    <!-- Step 2: Set Quantities -->
                    <div class="srwm-po-step-content" data-step="2">
                        <div class="srwm-step-header">
                            <h3><?php _e('Set Quantities for Selected Products', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Review and adjust quantities for each selected product. Suggested quantities are based on current stock and waitlist.', 'smart-restock-waitlist'); ?></p>
                        </div>
                        
                        <div class="srwm-selected-products" id="po-selected-products">
                            <!-- Selected products will be loaded here -->
                        </div>
                    </div>
                    
                    <!-- Step 3: Supplier & Delivery -->
                    <div class="srwm-po-step-content" data-step="3">
                        <div class="srwm-step-header">
                            <h3><?php _e('Supplier & Delivery Details', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Choose how to send your purchase order - via supplier or email.', 'smart-restock-waitlist'); ?></p>
                        </div>
                        
                        <!-- Delivery Method Selection -->
                        <div class="srwm-delivery-method">
                            <div class="srwm-method-option">
                                <input type="radio" id="po-method-supplier" name="po_delivery_method" value="supplier" checked>
                                <label for="po-method-supplier">
                                    <div class="srwm-method-content">
                                        <div class="srwm-method-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="srwm-method-info">
                                            <h4><?php _e('Send to Supplier', 'smart-restock-waitlist'); ?></h4>
                                            <p><?php _e('Send PO to an existing supplier from your supplier list', 'smart-restock-waitlist'); ?></p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="srwm-method-option">
                                <input type="radio" id="po-method-email" name="po_delivery_method" value="email">
                                <label for="po-method-email">
                                    <div class="srwm-method-content">
                                        <div class="srwm-method-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="srwm-method-info">
                                            <h4><?php _e('Send via Email', 'smart-restock-waitlist'); ?></h4>
                                            <p><?php _e('Send PO directly to any email address', 'smart-restock-waitlist'); ?></p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Supplier Selection (shown when supplier method is selected) -->
                        <div id="po-supplier-section" class="srwm-form-section">
                            <div class="srwm-form-row">
                                <div class="srwm-form-group">
                                    <label for="po-supplier"><?php _e('Select Supplier', 'smart-restock-waitlist'); ?> *</label>
                                    <select id="po-supplier" name="supplier_id">
                                        <option value=""><?php _e('Choose a supplier...', 'smart-restock-waitlist'); ?></option>
                                        <?php 
                                        $suppliers = $this->get_suppliers();
                                        error_log('SRWM: Modal suppliers count: ' . count($suppliers));
                                        if ($suppliers && is_array($suppliers)) {
                                            foreach ($suppliers as $supplier): 
                                                error_log('SRWM: Adding supplier: ' . $supplier->supplier_name);
                                        ?>
                                            <option value="<?php echo esc_attr($supplier->id); ?>">
                                                <?php echo esc_html($supplier->supplier_name); ?> (<?php echo esc_html($supplier->supplier_email); ?>)
                                            </option>
                                        <?php 
                                            endforeach;
                                        } else {
                                            error_log('SRWM: No suppliers found for modal');
                                        }
                                        ?>
                                    </select>
                                    <?php if (empty($suppliers)): ?>
                                        <p class="srwm-help-text"><?php _e('No suppliers found. You can add suppliers in the Supplier Management section.', 'smart-restock-waitlist'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Section (shown when email method is selected) -->
                        <div id="po-email-section" class="srwm-form-section" style="display: none;">
                            <div class="srwm-form-row">
                                <div class="srwm-form-group">
                                    <label for="po-email-address"><?php _e('Email Address', 'smart-restock-waitlist'); ?> *</label>
                                    <input type="email" id="po-email-address" name="email_address" placeholder="<?php _e('Enter supplier email address...', 'smart-restock-waitlist'); ?>">
                                </div>
                                <div class="srwm-form-group">
                                    <label for="po-supplier-name"><?php _e('Supplier Name (Optional)', 'smart-restock-waitlist'); ?></label>
                                    <input type="text" id="po-supplier-name" name="supplier_name" placeholder="<?php _e('Enter supplier name...', 'smart-restock-waitlist'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Common Fields -->
                        <div class="srwm-form-row">
                            <div class="srwm-form-group">
                                <label for="po-delivery-date"><?php _e('Expected Delivery Date', 'smart-restock-waitlist'); ?></label>
                                <input type="date" id="po-delivery-date" name="delivery_date" 
                                       value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                            </div>
                            <div class="srwm-form-group">
                                <label for="po-urgency"><?php _e('Urgency Level', 'smart-restock-waitlist'); ?></label>
                                <select id="po-urgency" name="urgency">
                                    <option value="normal"><?php _e('Normal', 'smart-restock-waitlist'); ?></option>
                                    <option value="urgent"><?php _e('Urgent', 'smart-restock-waitlist'); ?></option>
                                    <option value="critical"><?php _e('Critical', 'smart-restock-waitlist'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label for="po-notes"><?php _e('Notes (Optional)', 'smart-restock-waitlist'); ?></label>
                            <textarea id="po-notes" name="notes" rows="3" placeholder="<?php _e('Add any special instructions or notes for the supplier...', 'smart-restock-waitlist'); ?>"></textarea>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label for="po-send-notification">
                                <input type="checkbox" id="po-send-notification" name="send_notification" checked>
                                <?php _e('Send notification immediately', 'smart-restock-waitlist'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Step 4: Review & Generate -->
                    <div class="srwm-po-step-content" data-step="4">
                        <div class="srwm-step-header">
                            <h3><?php _e('Review Purchase Order', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Review all details before generating the purchase order.', 'smart-restock-waitlist'); ?></p>
                        </div>
                        
                        <div class="srwm-po-review" id="po-review-content">
                            <!-- Review content will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <div class="srwm-modal-footer">
                    <div class="srwm-step-actions">
                        <button type="button" class="srwm-btn srwm-btn-secondary" id="srwm-po-prev-step" style="display: none;">
                            <i class="fas fa-arrow-left"></i>
                            <?php _e('Previous', 'smart-restock-waitlist'); ?>
                        </button>
                        <button type="button" class="srwm-btn srwm-btn-primary" id="srwm-po-next-step">
                            <?php _e('Next', 'smart-restock-waitlist'); ?>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="srwm-btn srwm-btn-success" id="srwm-generate-po-submit" style="display: none;">
                            <i class="fas fa-check"></i>
                            <?php _e('Generate Purchase Order', 'smart-restock-waitlist'); ?>
                        </button>
                    </div>
                    <button type="button" class="srwm-btn srwm-btn-secondary srwm-modal-close">
                        <?php _e('Cancel', 'smart-restock-waitlist'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- PO Details Modal -->
        <div id="srwm-po-details-modal" class="srwm-modal" style="display: none;">
            <div class="srwm-modal-content srwm-modal-large">
                <div class="srwm-modal-header">
                    <div class="srwm-modal-header-content">
                        <div class="srwm-modal-title">
                            <h2><?php _e('Purchase Order Details', 'smart-restock-waitlist'); ?></h2>
                            <div class="srwm-po-status-display">
                                <span class="srwm-status-badge" id="srwm-modal-status-badge">
                                    <i class="fas fa-circle"></i>
                                    <span id="srwm-modal-status-text">Pending</span>
                                </span>
                            </div>
                        </div>
                        <div class="srwm-modal-actions">
                            <button type="button" class="srwm-modal-close" title="<?php _e('Close', 'smart-restock-waitlist'); ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="srwm-modal-body" id="srwm-po-details-content">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="srwm-modal-footer">
                    <div class="srwm-modal-footer-actions">
                        <div class="srwm-modal-footer-primary">
                            <button type="button" class="srwm-btn srwm-btn-secondary" id="srwm-modal-resend-po">
                                <i class="fas fa-envelope"></i>
                                <?php _e('Resend to Supplier', 'smart-restock-waitlist'); ?>
                            </button>
                            <button type="button" class="srwm-btn srwm-btn-primary" id="srwm-modal-download-po">
                                <i class="fas fa-download"></i>
                                <?php _e('Download PDF', 'smart-restock-waitlist'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Global variables
            var currentStep = 1;
            var selectedProducts = [];
            var allProducts = [];
            var currentPage = 1;
            var productsPerPage = 12;
            
            // Ensure modals are hidden on page load
            $('#srwm-po-modal, #srwm-po-details-modal').hide();
            
            // Tab functionality
            $('.srwm-tab-button').on('click', function() {
                var tabId = $(this).data('tab');
                
                // Update active tab button
                $('.srwm-tab-button').removeClass('active');
                $(this).addClass('active');
                
                // Show active tab content
                $('.srwm-tab-content').removeClass('active');
                $('.srwm-tab-content[data-tab="' + tabId + '"]').addClass('active');
            });
            
            // PO Generation Modal
            $('#srwm-generate-po, #srwm-generate-first-po, #srwm-quick-generate-po').on('click', function() {
                $('#srwm-po-modal').show().addClass('srwm-modal-active');
                loadProducts(); // Load products when modal opens
            });
            
            // Close modal
            $('.srwm-modal-close, .srwm-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).closest('.srwm-modal').hide().removeClass('srwm-modal-active');
                    resetModal(); // Reset modal state
                }
            });
            
            // Load products via AJAX
            function loadProducts() {
                console.log('SRWM: Loading products...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_products_for_po',
                        nonce: '<?php echo wp_create_nonce('srwm_get_products_for_po'); ?>'
                    },
                    beforeSend: function() {
                        console.log('SRWM: AJAX request started');
                        $('#po-products-grid').html('<div class="srwm-loading"><i class="fas fa-spinner fa-spin"></i> <?php _e('Loading products...', 'smart-restock-waitlist'); ?></div>');
                    },
                    success: function(response) {
                        console.log('SRWM: AJAX response received:', response);
                        if (response.success) {
                            allProducts = response.data.products;
                            console.log('SRWM: Products loaded:', allProducts.length);
                            renderProductsGrid();
                        } else {
                            console.log('SRWM: AJAX failed with message:', response.data);
                            $('#po-products-grid').html('<div class="srwm-error"><?php _e('Failed to load products', 'smart-restock-waitlist'); ?>: ' + (response.data || 'Unknown error') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('SRWM: AJAX error:', status, error);
                        console.log('SRWM: XHR response:', xhr.responseText);
                        $('#po-products-grid').html('<div class="srwm-error"><?php _e('Error loading products', 'smart-restock-waitlist'); ?>: ' + error + '</div>');
                    }
                });
            }
            
            // Render products grid
            function renderProductsGrid() {
                var filteredProducts = filterProducts(allProducts);
                var startIndex = (currentPage - 1) * productsPerPage;
                var endIndex = startIndex + productsPerPage;
                var pageProducts = filteredProducts.slice(startIndex, endIndex);
                
                var html = '';
                if (pageProducts.length === 0) {
                    html = '<div class="srwm-no-products"><?php _e('No products found matching your criteria', 'smart-restock-waitlist'); ?></div>';
                } else {
                    pageProducts.forEach(function(product) {
                        var isSelected = selectedProducts.some(function(p) { return p.id === product.id; });
                        var stockStatus = getStockStatus(product.stock_quantity, product.threshold);
                        
                        html += '<div class="srwm-product-card ' + (isSelected ? 'selected' : '') + '" data-product-id="' + product.id + '">';
                        html += '<input type="checkbox" ' + (isSelected ? 'checked' : '') + '>';
                        html += '<div class="srwm-product-info">';
                        html += '<div class="srwm-product-name">' + product.name + '</div>';
                        html += '<div class="srwm-product-sku">SKU: ' + product.sku + '</div>';
                        html += '<div class="srwm-product-meta">';
                        html += '<span>Stock: ' + product.stock_quantity + '</span>';
                        html += '<span class="srwm-stock-status ' + stockStatus.class + '">' + stockStatus.text + '</span>';
                        html += '</div>';
                        if (product.waitlist_count > 0) {
                            html += '<div class="srwm-waitlist-info">Waitlist: ' + product.waitlist_count + '</div>';
                        }
                        html += '</div>';
                        html += '</div>';
                    });
                }
                
                $('#po-products-grid').html(html);
                renderPagination(filteredProducts.length);
            }
            
            // Filter products
            function filterProducts(products) {
                var searchTerm = $('#po-product-search').val().toLowerCase();
                var categoryFilter = $('#po-category-filter').val();
                var stockFilter = $('#po-stock-filter').val();
                
                return products.filter(function(product) {
                    var matchesSearch = product.name.toLowerCase().includes(searchTerm) || 
                                       product.sku.toLowerCase().includes(searchTerm);
                    var matchesCategory = !categoryFilter || product.category === categoryFilter;
                    var matchesStock = !stockFilter || matchesStockFilter(product, stockFilter);
                    
                    return matchesSearch && matchesCategory && matchesStock;
                });
            }
            
            // Check if product matches stock filter
            function matchesStockFilter(product, filter) {
                switch(filter) {
                    case 'low':
                        return product.stock_quantity <= product.threshold && product.stock_quantity > 0;
                    case 'out':
                        return product.stock_quantity <= 0;
                    case 'waitlist':
                        return product.waitlist_count > 0;
                    default:
                        return true;
                }
            }
            
            // Get stock status
            function getStockStatus(stock, threshold) {
                if (stock <= 0) {
                    return { class: 'srwm-stock-out', text: 'Out of Stock' };
                } else if (stock <= threshold) {
                    return { class: 'srwm-stock-low', text: 'Low Stock' };
                } else {
                    return { class: 'srwm-stock-ok', text: 'In Stock' };
                }
            }
            
            // Render pagination
            function renderPagination(totalProducts) {
                var totalPages = Math.ceil(totalProducts / productsPerPage);
                var html = '';
                
                if (totalPages > 1) {
                    html += '<button class="srwm-pagination-btn" ' + (currentPage === 1 ? 'disabled' : '') + ' data-page="' + (currentPage - 1) + '">Previous</button>';
                    
                    for (var i = 1; i <= totalPages; i++) {
                        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                            html += '<button class="srwm-pagination-btn ' + (i === currentPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
                        } else if (i === currentPage - 3 || i === currentPage + 3) {
                            html += '<span class="srwm-pagination-dots">...</span>';
                        }
                    }
                    
                    html += '<button class="srwm-pagination-btn" ' + (currentPage === totalPages ? 'disabled' : '') + ' data-page="' + (currentPage + 1) + '">Next</button>';
                }
                
                $('#po-products-pagination').html(html);
            }
            
            // Product selection
            $(document).on('change', '.srwm-product-card input[type="checkbox"]', function() {
                var productId = parseInt($(this).closest('.srwm-product-card').data('product-id'));
                var product = allProducts.find(function(p) { return p.id === productId; });
                
                if ($(this).is(':checked')) {
                    if (!selectedProducts.some(function(p) { return p.id === productId; })) {
                        selectedProducts.push(product);
                    }
                    $(this).closest('.srwm-product-card').addClass('selected');
                } else {
                    selectedProducts = selectedProducts.filter(function(p) { return p.id !== productId; });
                    $(this).closest('.srwm-product-card').removeClass('selected');
                }
                
                updateStepButtons();
            });
            
            // Bulk actions
            $('#po-select-all').on('click', function() {
                var visibleProducts = $('#po-products-grid .srwm-product-card:visible');
                visibleProducts.find('input[type="checkbox"]').prop('checked', true).trigger('change');
            });
            
            $('#po-clear-selection').on('click', function() {
                $('.srwm-product-card input[type="checkbox"]').prop('checked', false).trigger('change');
            });
            
            // Search and filters
            $('#po-product-search, #po-category-filter, #po-stock-filter').on('input change', function() {
                currentPage = 1;
                renderProductsGrid();
            });
            
            // Pagination
            $(document).on('click', '.srwm-pagination-btn', function() {
                if (!$(this).prop('disabled')) {
                    currentPage = parseInt($(this).data('page'));
                    renderProductsGrid();
                }
            });
            
            // Step navigation
            $('#srwm-po-next-step').on('click', function() {
                if (canProceedToNextStep()) {
                    currentStep++;
                    updateStepDisplay();
                }
            });
            
            $('#srwm-po-prev-step').on('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    updateStepDisplay();
                }
            });
            
            // Check if can proceed to next step
            function canProceedToNextStep() {
                switch(currentStep) {
                    case 1:
                        return selectedProducts.length > 0;
                    case 2:
                        return validateQuantities();
                    case 3:
                        return validateSupplierForm();
                    default:
                        return true;
                }
            }
            
            // Validate quantities
            function validateQuantities() {
                var valid = true;
                $('.srwm-quantity-input input').each(function() {
                    var quantity = parseInt($(this).val());
                    if (isNaN(quantity) || quantity <= 0) {
                        valid = false;
                        $(this).addClass('error');
                    } else {
                        $(this).removeClass('error');
                    }
                });
                return valid;
            }
            
            // Validate supplier form
            function validateSupplierForm() {
                var deliveryMethod = $('input[name="po_delivery_method"]:checked').val();
                
                if (deliveryMethod === 'supplier') {
                    return $('#po-supplier').val() !== '';
                } else if (deliveryMethod === 'email') {
                    return $('#po-email-address').val() !== '' && isValidEmail($('#po-email-address').val());
                }
                
                return false;
            }
            
            // Email validation
            function isValidEmail(email) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            // Handle delivery method switching
            $('input[name="po_delivery_method"]').on('change', function() {
                var method = $(this).val();
                
                if (method === 'supplier') {
                    $('#po-supplier-section').show();
                    $('#po-email-section').hide();
                    $('#po-supplier').prop('required', true);
                    $('#po-email-address').prop('required', false);
                } else if (method === 'email') {
                    $('#po-supplier-section').hide();
                    $('#po-email-section').show();
                    $('#po-supplier').prop('required', false);
                    $('#po-email-address').prop('required', true);
                }
            });
            
            // Update step display
            function updateStepDisplay() {
                // Update step indicators
                $('.srwm-step').removeClass('active completed');
                for (var i = 1; i <= 4; i++) {
                    if (i < currentStep) {
                        $('.srwm-step[data-step="' + i + '"]').addClass('completed');
                    } else if (i === currentStep) {
                        $('.srwm-step[data-step="' + i + '"]').addClass('active');
                    }
                }
                
                // Show/hide step content
                $('.srwm-po-step-content').removeClass('active');
                $('.srwm-po-step-content[data-step="' + currentStep + '"]').addClass('active');
                
                // Update buttons
                updateStepButtons();
                
                // Load step-specific content
                loadStepContent();
            }
            
            // Update step buttons
            function updateStepButtons() {
                $('#srwm-po-prev-step').toggle(currentStep > 1);
                $('#srwm-po-next-step').toggle(currentStep < 4);
                $('#srwm-generate-po-submit').toggle(currentStep === 4);
                
                // Update next button text
                if (currentStep === 1) {
                    $('#srwm-po-next-step').text('<?php _e('Next', 'smart-restock-waitlist'); ?> (' + selectedProducts.length + ' selected)');
                } else {
                    $('#srwm-po-next-step').text('<?php _e('Next', 'smart-restock-waitlist'); ?>');
                }
            }
            
            // Load step-specific content
            function loadStepContent() {
                switch(currentStep) {
                    case 2:
                        renderSelectedProducts();
                        break;
                    case 4:
                        renderReviewContent();
                        break;
                }
            }
            
            // Render selected products for quantity setting
            function renderSelectedProducts() {
                var html = '';
                selectedProducts.forEach(function(product) {
                    var suggestedQuantity = Math.max(10, (product.threshold - product.stock_quantity) * 2 + product.waitlist_count);
                    
                    html += '<div class="srwm-selected-product" data-product-id="' + product.id + '">';
                    html += '<div class="srwm-selected-product-info">';
                    html += '<div class="srwm-selected-product-name">' + product.name + '</div>';
                    html += '<div class="srwm-selected-product-sku">SKU: ' + product.sku + '</div>';
                    html += '<div class="srwm-suggested-quantity">Suggested: ' + suggestedQuantity + ' (based on stock: ' + product.stock_quantity + ', threshold: ' + product.threshold + ', waitlist: ' + product.waitlist_count + ')</div>';
                    html += '</div>';
                    html += '<div class="srwm-quantity-input">';
                    html += '<label>Quantity:</label>';
                    html += '<input type="number" min="1" value="' + suggestedQuantity + '" data-product-id="' + product.id + '">';
                    html += '</div>';
                    html += '</div>';
                });
                
                $('#po-selected-products').html(html);
            }
            
            // Render review content
            function renderReviewContent() {
                var html = '<div class="srwm-review-section">';
                html += '<h4><?php _e('Selected Products', 'smart-restock-waitlist'); ?></h4>';
                html += '<div class="srwm-review-products">';
                
                selectedProducts.forEach(function(product) {
                    var quantity = $('input[data-product-id="' + product.id + '"]').val();
                    html += '<div class="srwm-review-product">';
                    html += '<span class="srwm-review-product-name">' + product.name + '</span>';
                    html += '<span class="srwm-review-product-quantity">Qty: ' + quantity + '</span>';
                    html += '</div>';
                });
                
                html += '</div>';
                
                var deliveryMethod = $('input[name="po_delivery_method"]:checked').val();
                html += '<h4><?php _e('Delivery Details', 'smart-restock-waitlist'); ?></h4>';
                html += '<div class="srwm-review-supplier">';
                
                if (deliveryMethod === 'supplier') {
                    html += '<p><strong><?php _e('Method:', 'smart-restock-waitlist'); ?></strong> <?php _e('Send to Supplier', 'smart-restock-waitlist'); ?></p>';
                    html += '<p><strong><?php _e('Supplier:', 'smart-restock-waitlist'); ?></strong> ' + $('#po-supplier option:selected').text() + '</p>';
                } else if (deliveryMethod === 'email') {
                    html += '<p><strong><?php _e('Method:', 'smart-restock-waitlist'); ?></strong> <?php _e('Send via Email', 'smart-restock-waitlist'); ?></p>';
                    html += '<p><strong><?php _e('Email:', 'smart-restock-waitlist'); ?></strong> ' + $('#po-email-address').val() + '</p>';
                    if ($('#po-supplier-name').val()) {
                        html += '<p><strong><?php _e('Supplier Name:', 'smart-restock-waitlist'); ?></strong> ' + $('#po-supplier-name').val() + '</p>';
                    }
                }
                
                html += '<p><strong><?php _e('Delivery Date:', 'smart-restock-waitlist'); ?></strong> ' + $('#po-delivery-date').val() + '</p>';
                html += '<p><strong><?php _e('Urgency:', 'smart-restock-waitlist'); ?></strong> ' + $('#po-urgency option:selected').text() + '</p>';
                if ($('#po-notes').val()) {
                    html += '<p><strong><?php _e('Notes:', 'smart-restock-waitlist'); ?></strong> ' + $('#po-notes').val() + '</p>';
                }
                html += '</div>';
                html += '</div>';
                
                $('#po-review-content').html(html);
            }
            
            // Generate PO
            $('#srwm-generate-po-submit').on('click', function() {
                console.log('SRWM: Generate PO clicked');
                console.log('SRWM: selectedProducts:', selectedProducts);
                console.log('SRWM: selectedProducts type:', typeof selectedProducts);
                console.log('SRWM: selectedProducts length:', selectedProducts.length);
                
                var deliveryMethod = $('input[name="po_delivery_method"]:checked').val();
                console.log('SRWM: deliveryMethod:', deliveryMethod);
                
                if (!selectedProducts || selectedProducts.length === 0) {
                    alert('<?php _e('No products selected. Please select at least one product.', 'smart-restock-waitlist'); ?>');
                    return;
                }
                
                var formData = {
                    products: selectedProducts.map(function(product) {
                        console.log('SRWM: Processing product:', product);
                        return {
                            id: product.id,
                            quantity: parseInt($('input[data-product-id="' + product.id + '"]').val())
                        };
                    }),
                    delivery_method: deliveryMethod,
                    delivery_date: $('#po-delivery-date').val(),
                    urgency: $('#po-urgency').val(),
                    notes: $('#po-notes').val(),
                    send_notification: $('#po-send-notification').is(':checked')
                };
                
                // Add delivery method specific data
                if (deliveryMethod === 'supplier') {
                    var supplierId = $('#po-supplier').val();
                    console.log('SRWM: Supplier ID from dropdown:', supplierId);
                    console.log('SRWM: Supplier dropdown element:', $('#po-supplier'));
                    console.log('SRWM: Supplier dropdown options:', $('#po-supplier option').length);
                    formData.supplier_id = supplierId;
                } else if (deliveryMethod === 'email') {
                    formData.email_address = $('#po-email-address').val();
                    formData.supplier_name = $('#po-supplier-name').val();
                }
                
                console.log('SRWM: Final formData:', formData);
                
                console.log('SRWM: Making AJAX request to:', ajaxurl);
                console.log('SRWM: AJAX data:', {
                    action: 'srwm_generate_po',
                    form_data: JSON.stringify(formData),
                    nonce: '<?php echo wp_create_nonce('srwm_generate_po'); ?>'
                });
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_generate_po',
                        form_data: JSON.stringify(formData),
                        nonce: '<?php echo wp_create_nonce('srwm_generate_po'); ?>'
                    },
                    beforeSend: function() {
                        $('#srwm-generate-po-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Generating...', 'smart-restock-waitlist'); ?>');
                    },
                    success: function(response) {
                        console.log('SRWM: AJAX success response:', response);
                        if (response.success) {
                            // Show success message
                            var message = response.data.message || '<?php _e('Purchase order generated successfully!', 'smart-restock-waitlist'); ?>';
                            
                            // Create success notification
                            var notification = $('<div class="srwm-notification srwm-notification-success">' +
                                '<div class="srwm-notification-content">' +
                                '<i class="fas fa-check-circle"></i>' +
                                '<span>' + message + '</span>' +
                                '</div>' +
                                '<button class="srwm-notification-close">&times;</button>' +
                                '</div>');
                            
                            $('body').append(notification);
                            
                            // Auto-hide after 5 seconds
                            setTimeout(function() {
                                notification.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 5000);
                            
                            // Close modal and reload
                            $('#srwm-po-modal').hide().removeClass('srwm-modal-active');
                            
                            // Small delay before reload to show notification
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            alert(response.data.message || '<?php _e('Failed to generate purchase order', 'smart-restock-waitlist'); ?>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('SRWM: AJAX error occurred');
                        console.log('SRWM: Status:', status);
                        console.log('SRWM: Error:', error);
                        console.log('SRWM: Response Text:', xhr.responseText);
                        console.error('AJAX Error:', xhr.responseText);
                        var errorMessage = '<?php _e('An error occurred while generating the purchase order', 'smart-restock-waitlist'); ?>';
                        
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage = response.data.message;
                            }
                        } catch (e) {
                            // Use default error message
                        }
                        
                        alert(errorMessage);
                    },
                    complete: function() {
                        $('#srwm-generate-po-submit').prop('disabled', false).html('<i class="fas fa-check"></i> <?php _e('Generate Purchase Order', 'smart-restock-waitlist'); ?>');
                    }
                });
            });
            
            // Reset modal
            function resetModal() {
                currentStep = 1;
                selectedProducts = [];
                currentPage = 1;
                updateStepDisplay();
                $('#po-products-grid').html('<div class="srwm-loading"><i class="fas fa-spinner fa-spin"></i> <?php _e('Loading products...', 'smart-restock-waitlist'); ?></div>');
            }
            
            // Notification close functionality
            $(document).on('click', '.srwm-notification-close', function() {
                $(this).closest('.srwm-notification').fadeOut(function() {
                    $(this).remove();
                });
            });
            
            // Enhanced Search functionality for PO table
            $('#po-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                $('.srwm-data-table tbody tr').each(function() {
                    var text = $(this).text().toLowerCase();
                    $(this).toggle(text.indexOf(searchTerm) > -1);
                });
            });
            
            // Enhanced Status filter for PO table
            $('#po-status-filter').on('change', function() {
                var status = $(this).val().toLowerCase();
                $('.srwm-data-table tbody tr').each(function() {
                    if (status === '') {
                        $(this).show();
                    } else {
                        var rowStatus = $(this).find('.srwm-status-badge').text().toLowerCase().trim();
                        $(this).toggle(rowStatus === status);
                    }
                });
            });
            
            // Sort functionality for PO table
            $('#po-sort-filter').on('change', function() {
                var sortBy = $(this).val();
                var tbody = $('.srwm-data-table tbody');
                var rows = tbody.find('tr').toArray();
                
                rows.sort(function(a, b) {
                    var aVal, bVal;
                    
                    switch(sortBy) {
                        case 'newest':
                            aVal = new Date($(a).find('.srwm-date').text());
                            bVal = new Date($(b).find('.srwm-date').text());
                            return bVal - aVal;
                        case 'oldest':
                            aVal = new Date($(a).find('.srwm-date').text());
                            bVal = new Date($(b).find('.srwm-date').text());
                            return aVal - bVal;
                        case 'status':
                            aVal = $(a).find('.srwm-status-badge').text().toLowerCase();
                            bVal = $(b).find('.srwm-status-badge').text().toLowerCase();
                            return aVal.localeCompare(bVal);
                        case 'supplier':
                            aVal = $(a).find('.srwm-supplier-name').text().toLowerCase();
                            bVal = $(b).find('.srwm-supplier-name').text().toLowerCase();
                            return aVal.localeCompare(bVal);
                        default:
                            return 0;
                    }
                });
                
                tbody.empty().append(rows);
            });
            
            // Global variable to store current PO ID
            var currentPoId = null;
            
            // View PO Details
            $('.view-po').on('click', function() {
                var poId = $(this).data('po-id');
                var button = $(this);
                
                // Store current PO ID globally
                currentPoId = poId;
                
                // Add loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_po_details',
                        po_id: poId,
                        nonce: '<?php echo wp_create_nonce('srwm_get_po_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#srwm-po-details-content').html(response.data.html);
                            
                            // Update modal status badge
                            var status = response.data.status || 'pending';
                            var statusText = response.data.status_text || 'Pending';
                            $('#srwm-modal-status-badge').removeClass().addClass('srwm-status-badge srwm-status-' + status);
                            $('#srwm-modal-status-text').text(statusText);
                            
                            $('#srwm-po-details-modal').show().addClass('srwm-modal-active');
                        } else {
                            alert('<?php _e('Failed to load PO details. Please try again.', 'smart-restock-waitlist'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error loading PO details. Please try again.', 'smart-restock-waitlist'); ?>');
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html('<i class="fas fa-eye"></i>');
                    }
                });
            });
            
            // Download PO PDF
            $('.download-po').on('click', function() {
                var poId = $(this).data('po-id');
                var button = $(this);
                
                // Add loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_download_po',
                        po_id: poId,
                        nonce: '<?php echo wp_create_nonce('srwm_download_po'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Create download link
                            var link = document.createElement('a');
                            link.href = response.data.download_url;
                            link.download = response.data.filename;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            // Show success notification
                            showNotification('<?php _e('PO PDF downloaded successfully!', 'smart-restock-waitlist'); ?>', 'success');
                        } else {
                            alert('<?php _e('Failed to generate PDF. Please try again.', 'smart-restock-waitlist'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error generating PDF. Please try again.', 'smart-restock-waitlist'); ?>');
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html('<i class="fas fa-download"></i>');
                    }
                });
            });
            
            // Resend PO to Supplier
            $('.resend-po').on('click', function() {
                var poId = $(this).data('po-id');
                var button = $(this);
                
                if (!confirm('<?php _e('Are you sure you want to resend this PO to the supplier?', 'smart-restock-waitlist'); ?>')) {
                    return;
                }
                
                // Add loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_resend_po',
                        po_id: poId,
                        nonce: '<?php echo wp_create_nonce('srwm_resend_po'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('<?php _e('PO resent to supplier successfully!', 'smart-restock-waitlist'); ?>', 'success');
                        } else {
                            alert('<?php _e('Failed to resend PO. Please try again.', 'smart-restock-waitlist'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error resending PO. Please try again.', 'smart-restock-waitlist'); ?>');
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html('<i class="fas fa-envelope"></i>');
                    }
                });
            });
            

            
            // Update PO Status
            $('.update-po-status').on('change', function() {
                var poId = $(this).data('po-id');
                var newStatus = $(this).val();
                var originalStatus = $(this).data('original-value');
                var select = $(this);
                
                // Show confirmation dialog
                if (!confirm('<?php _e('Are you sure you want to change the status from', 'smart-restock-waitlist'); ?> "' + originalStatus + '" <?php _e('to', 'smart-restock-waitlist'); ?> "' + newStatus + '"?\n\n<?php _e('This action cannot be undone.', 'smart-restock-waitlist'); ?>')) {
                    // Revert to original value if user cancels
                    select.val(originalStatus);
                    return;
                }
                
                // Add loading state
                select.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_update_po_status_safe',
                        po_id: poId,
                        status: newStatus,
                        nonce: '<?php echo wp_create_nonce('srwm_update_po_status'); ?>'
                    },
                    success: function(response) {
                        console.log('SRWM: Status update response:', response);
                        if (response.success) {
                            showNotification('<?php _e('PO status updated successfully!', 'smart-restock-waitlist'); ?>', 'success');
                            // Update the original value for future confirmations
                            select.data('original-value', newStatus);
                            // Update the status badge in the table
                            var statusBadge = select.closest('tr').find('.srwm-status-badge');
                            statusBadge.removeClass().addClass('srwm-status-badge srwm-status-' + newStatus);
                            statusBadge.html('<i class="fas fa-circle"></i> ' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                        } else {
                            var errorMessage = response.data || '<?php _e('Failed to update status. Please try again.', 'smart-restock-waitlist'); ?>';
                            showNotification(errorMessage, 'error');
                            // Revert select value
                            select.val(select.data('original-value'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('SRWM: Status update error:', {xhr: xhr, status: status, error: error});
                        console.log('SRWM: Response text:', xhr.responseText);
                        
                        var errorMessage = '<?php _e('Error updating status. Please try again.', 'smart-restock-waitlist'); ?>';
                        
                        // Try to parse JSON response
                        try {
                            if (xhr.responseJSON && xhr.responseJSON.data) {
                                errorMessage = xhr.responseJSON.data;
                            }
                        } catch (e) {
                            console.log('SRWM: Could not parse JSON response:', e);
                            // Show raw response if JSON parsing fails
                            if (xhr.responseText) {
                                errorMessage = 'Server response: ' + xhr.responseText.substring(0, 200);
                            }
                        }
                        
                        showNotification(errorMessage, 'error');
                        // Revert select value
                        select.val(select.data('original-value'));
                    },
                    complete: function() {
                        // Restore select state
                        select.prop('disabled', false);
                    }
                });
            });
            
            // Function to show notifications
            function showNotification(message, type) {
                var notification = $('<div class="srwm-notification srwm-notification-' + type + '">' +
                    '<div class="srwm-notification-content">' +
                    '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i>' +
                    '<span>' + message + '</span>' +
                    '</div>' +
                    '<button class="srwm-notification-close"><i class="fas fa-times"></i></button>' +
                    '</div>');
                
                $('body').append(notification);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Modal Download PO
            $('#srwm-modal-download-po').on('click', function() {
                if (!currentPoId) {
                    alert('<?php _e('No PO selected. Please try again.', 'smart-restock-waitlist'); ?>');
                    return;
                }
                
                var button = $(this);
                
                // Add loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Generating...', 'smart-restock-waitlist'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_download_po',
                        po_id: currentPoId,
                        nonce: '<?php echo wp_create_nonce('srwm_download_po'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Open PDF in new window for printing/saving
                            var newWindow = window.open(response.data.download_url, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
                            
                            if (newWindow) {
                                // Show success notification
                                showNotification('<?php _e('PO opened in new window. Use the Print button to save as PDF!', 'smart-restock-waitlist'); ?>', 'success');
                            } else {
                                // Fallback: direct download
                                var link = document.createElement('a');
                                link.href = response.data.download_url;
                                link.target = '_blank';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                
                                showNotification('<?php _e('PO opened in new tab. Use browser print to save as PDF!', 'smart-restock-waitlist'); ?>', 'success');
                            }
                        } else {
                            alert('<?php _e('Failed to generate PDF. Please try again.', 'smart-restock-waitlist'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error generating PDF. Please try again.', 'smart-restock-waitlist'); ?>');
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html('<i class="fas fa-download"></i> <?php _e('Download PDF', 'smart-restock-waitlist'); ?>');
                    }
                });
            });
            
            // Modal Resend PO
            $('#srwm-modal-resend-po').on('click', function() {
                if (!currentPoId) {
                    alert('<?php _e('No PO selected. Please try again.', 'smart-restock-waitlist'); ?>');
                    return;
                }
                
                if (!confirm('<?php _e('Are you sure you want to resend this PO to the supplier?', 'smart-restock-waitlist'); ?>')) {
                    return;
                }
                
                var button = $(this);
                
                // Add loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Sending...', 'smart-restock-waitlist'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_resend_po',
                        po_id: currentPoId,
                        nonce: '<?php echo wp_create_nonce('srwm_resend_po'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('<?php _e('PO resent to supplier successfully!', 'smart-restock-waitlist'); ?>', 'success');
                        } else {
                            alert('<?php _e('Failed to resend PO. Please try again.', 'smart-restock-waitlist'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error resending PO. Please try again.', 'smart-restock-waitlist'); ?>');
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html('<i class="fas fa-envelope"></i> <?php _e('Resend to Supplier', 'smart-restock-waitlist'); ?>');
                    }
                });
            });
            
            // Export POs
            $('#srwm-export-pos, #srwm-quick-export').on('click', function() {
                var button = $(this);
                
                // Add loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Generating...', 'smart-restock-waitlist'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_export_pos',
                        nonce: '<?php echo wp_create_nonce('srwm_export_pos'); ?>'
                    },
                    success: function(response) {
                        console.log('SRWM: Export POs response:', response);
                        if (response.success) {
                            // Create download link
                            var link = document.createElement('a');
                            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                            link.download = 'purchase_orders_' + new Date().toISOString().slice(0, 10) + '.csv';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            showNotification('<?php _e('Purchase orders exported successfully!', 'smart-restock-waitlist'); ?>', 'success');
                        } else {
                            showNotification('<?php _e('Failed to export purchase orders. Please try again.', 'smart-restock-waitlist'); ?>', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('SRWM: Export POs error:', {xhr: xhr, status: status, error: error});
                        showNotification('<?php _e('Error exporting purchase orders. Please try again.', 'smart-restock-waitlist'); ?>', 'error');
                    },
                    complete: function() {
                        // Restore button state
                        button.prop('disabled', false).html('<i class="fas fa-download"></i> <?php _e('Export', 'smart-restock-waitlist'); ?>');
                    }
                });
            });
            
            // Quick Actions
            $('#srwm-quick-bulk').on('click', function() {
                // Show bulk actions modal
                showBulkActionsModal();
            });
            
            // Function to show bulk actions modal
            function showBulkActionsModal() {
                var modal = $('<div class="srwm-modal srwm-modal-active" style="display: flex;">' +
                    '<div class="srwm-modal-content" style="max-width: 600px;">' +
                        '<div class="srwm-modal-header">' +
                            '<h3><i class="fas fa-tasks"></i> <?php _e('Bulk Actions', 'smart-restock-waitlist'); ?></h3>' +
                            '<button class="srwm-modal-close">&times;</button>' +
                        '</div>' +
                        '<div class="srwm-modal-body">' +
                            '<div class="srwm-bulk-actions-content">' +
                                '<div class="srwm-bulk-section">' +
                                    '<h4><?php _e('Select Action', 'smart-restock-waitlist'); ?></h4>' +
                                    '<div class="srwm-bulk-options">' +
                                        '<label class="srwm-radio-option">' +
                                            '<input type="radio" name="bulk_action" value="status_update" checked>' +
                                            '<span class="srwm-radio-label"><?php _e('Update Status', 'smart-restock-waitlist'); ?></span>' +
                                        '</label>' +
                                        '<label class="srwm-radio-option">' +
                                            '<input type="radio" name="bulk_action" value="resend">' +
                                            '<span class="srwm-radio-label"><?php _e('Resend to Suppliers', 'smart-restock-waitlist'); ?></span>' +
                                        '</label>' +
                                        '<label class="srwm-radio-option">' +
                                            '<input type="radio" name="bulk_action" value="delete">' +
                                            '<span class="srwm-radio-label"><?php _e('Delete POs', 'smart-restock-waitlist'); ?></span>' +
                                        '</label>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="srwm-bulk-section" id="bulk-status-section">' +
                                    '<h4><?php _e('New Status', 'smart-restock-waitlist'); ?></h4>' +
                                    '<select id="bulk-status-select" class="srwm-select">' +
                                        '<option value="pending"><?php _e('Pending', 'smart-restock-waitlist'); ?></option>' +
                                        '<option value="confirmed"><?php _e('Confirmed', 'smart-restock-waitlist'); ?></option>' +
                                        '<option value="shipped"><?php _e('Shipped', 'smart-restock-waitlist'); ?></option>' +
                                        '<option value="completed"><?php _e('Completed', 'smart-restock-waitlist'); ?></option>' +
                                    '</select>' +
                                '</div>' +
                                '<div class="srwm-bulk-section">' +
                                    '<h4><?php _e('Select Purchase Orders', 'smart-restock-waitlist'); ?></h4>' +
                                    '<div class="srwm-bulk-selection">' +
                                        '<label class="srwm-checkbox-option">' +
                                            '<input type="checkbox" id="select-all-pos">' +
                                            '<span class="srwm-checkbox-label"><?php _e('Select All', 'smart-restock-waitlist'); ?></span>' +
                                        '</label>' +
                                        '<div class="srwm-po-checkboxes" id="po-checkboxes">' +
                                            '<p class="srwm-loading"><?php _e('Loading purchase orders...', 'smart-restock-waitlist'); ?></p>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="srwm-modal-footer">' +
                            '<button class="srwm-btn srwm-btn-secondary" onclick="$(this).closest(\'.srwm-modal\').remove();"><?php _e('Cancel', 'smart-restock-waitlist'); ?></button>' +
                            '<button class="srwm-btn srwm-btn-primary" id="execute-bulk-action"><?php _e('Execute Action', 'smart-restock-waitlist'); ?></button>' +
                        '</div>' +
                    '</div>' +
                '</div>');
                
                $('body').append(modal);
                
                // Load purchase orders for selection
                loadPurchaseOrdersForBulk();
                
                // Handle action type change
                $('input[name="bulk_action"]').on('change', function() {
                    var action = $(this).val();
                    if (action === 'status_update') {
                        $('#bulk-status-section').show();
                    } else {
                        $('#bulk-status-section').hide();
                    }
                });
                
                // Handle select all
                $('#select-all-pos').on('change', function() {
                    $('.po-checkbox').prop('checked', $(this).is(':checked'));
                });
                
                // Execute bulk action
                $('#execute-bulk-action').on('click', function() {
                    executeBulkAction();
                });
                
                // Close modal
                modal.find('.srwm-modal-close, .srwm-modal').on('click', function(e) {
                    if (e.target === this) {
                        modal.remove();
                    }
                });
            }
            
            // Load purchase orders for bulk selection
            function loadPurchaseOrdersForBulk() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_pos_for_bulk',
                        nonce: '<?php echo wp_create_nonce('srwm_get_pos_for_bulk'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '';
                            response.data.forEach(function(po) {
                                html += '<label class="srwm-checkbox-option">' +
                                    '<input type="checkbox" class="po-checkbox" value="' + po.id + '">' +
                                    '<span class="srwm-checkbox-label">' + po.po_number + ' - ' + po.product_name + '</span>' +
                                '</label>';
                            });
                            $('#po-checkboxes').html(html);
                        } else {
                            $('#po-checkboxes').html('<p class="srwm-error"><?php _e('Failed to load purchase orders.', 'smart-restock-waitlist'); ?></p>');
                        }
                    },
                    error: function() {
                        $('#po-checkboxes').html('<p class="srwm-error"><?php _e('Error loading purchase orders.', 'smart-restock-waitlist'); ?></p>');
                    }
                });
            }
            
            // Execute bulk action
            function executeBulkAction() {
                var selectedPos = $('.po-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selectedPos.length === 0) {
                    showNotification('<?php _e('Please select at least one purchase order.', 'smart-restock-waitlist'); ?>', 'error');
                    return;
                }
                
                var action = $('input[name="bulk_action"]:checked').val();
                var status = $('#bulk-status-select').val();
                
                var button = $('#execute-bulk-action');
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e('Processing...', 'smart-restock-waitlist'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_execute_bulk_action',
                        nonce: '<?php echo wp_create_nonce('srwm_execute_bulk_action'); ?>',
                        po_ids: selectedPos,
                        bulk_action: action,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                            $('.srwm-modal').remove();
                            // Reload the page to refresh the table
                            location.reload();
                        } else {
                            showNotification(response.data, 'error');
                        }
                    },
                    error: function() {
                        showNotification('<?php _e('Error executing bulk action. Please try again.', 'smart-restock-waitlist'); ?>', 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('<?php _e('Execute Action', 'smart-restock-waitlist'); ?>');
                    }
                });
            }
            
            $('#srwm-quick-settings').on('click', function() {
                window.location.href = '<?php echo admin_url('admin.php?page=smart-restock-waitlist-settings'); ?>';
            });
            
            // Animate analytics cards on load
            $('.srwm-analytics-card').each(function(index) {
                var card = $(this);
                setTimeout(function() {
                    card.addClass('srwm-card-animated');
                }, index * 100);
            });
        });
        </script>
        
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
    
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: ajaxData,
                        timeout: 30000, // 30 second timeout
                        success: function(response) {
    
                            
                            try {
                                if (response && response.success) {

                                    displayApprovals(response.data);
                                } else {
  
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
                

                
                let html = '';
                let processedCount = 0;
                
                approvals.forEach(function(approval, index) {
                    try {
    
                        
                        let uploadData = [];
                        try {
                            uploadData = JSON.parse(approval.upload_data);
                            
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
                

                
                $('#srwm-approvals-container').html(html);
                
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
        
                        return;
                    }
                    
                    // Check if Chart.js is loaded
                    if (typeof Chart === 'undefined') {
        
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
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'smart-restock-waitlist'));
        }
        
        // Handle form submission with validation
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'srwm_settings-options')) {
            $this->save_settings();
        }
        
        // Handle reset to defaults
        if (isset($_GET['action']) && $_GET['action'] === 'reset-defaults' && 
            wp_verify_nonce($_GET['_wpnonce'], 'srwm_reset_defaults')) {
            $this->reset_settings_to_defaults();
        }
        
        ?>
        <div class="wrap">
            <style>
                .srwm-settings-section {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                
                .srwm-color-picker {
                    width: 50px;
                    height: 30px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    margin-right: 10px;
                    vertical-align: middle;
                }
                
                .srwm-hex-input {
                    width: 100px;
                    padding: 5px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-family: monospace;
                    font-size: 12px;
                }
                
                .srwm-color-group {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .srwm-two-column-layout {
                    display: flex;
                    gap: 30px;
                    margin-bottom: 20px;
                }
                
                .srwm-column {
                    flex: 1;
                    min-width: 0;
                }
                
                .srwm-column h3 {
                    margin-top: 0;
                    margin-bottom: 15px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #e1e5e9;
                    color: #23282d;
                    font-size: 16px;
                }
                
                .srwm-color-option {
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #f9f9f9;
                    border-radius: 6px;
                    border-left: 4px solid #007cba;
                }
                
                .srwm-color-option label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #23282d;
                }
                
                .srwm-color-option .description {
                    margin-top: 5px;
                    margin-bottom: 0;
                    font-size: 12px;
                    color: #666;
                }
                
                .srwm-layout-options {
                    margin-top: 20px;
                    padding: 20px;
                    background: #f9f9f9;
                    border-radius: 6px;
                    border: 1px solid #e1e5e9;
                }
                
                .srwm-layout-options h3 {
                    margin-top: 0;
                    margin-bottom: 15px;
                    color: #23282d;
                    font-size: 16px;
                }
                
                .srwm-option-row {
                    display: flex;
                    gap: 30px;
                }
                
                .srwm-option {
                    flex: 1;
                }
                
                .srwm-option label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #23282d;
                }
                
                .srwm-option .description {
                    margin-top: 5px;
                    margin-bottom: 0;
                    font-size: 12px;
                    color: #666;
                }
                
                @media (max-width: 768px) {
                    .srwm-two-column-layout {
                        flex-direction: column;
                        gap: 20px;
                    }
                    
                    .srwm-option-row {
                        flex-direction: column;
                        gap: 20px;
                    }
                }
                .srwm-settings-section h2 {
                    margin-top: 0;
                    color: #23282d;
                    border-bottom: 1px solid #eee;
                    padding-bottom: 10px;
                }
                .srwm-settings-section .form-table th {
                    width: 200px;
                    padding: 15px 10px 15px 0;
                }
                .srwm-settings-section .form-table td {
                    padding: 15px 10px;
                }
                .srwm-settings-section .description {
                    color: #666;
                    font-style: italic;
                    margin-top: 5px;
                }
                .srwm-submit-actions {
                    background: #f9f9f9;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 15px;
                    margin-top: 20px;
                }
                .srwm-submit-actions .button {
                    margin-right: 10px;
                }
            </style>
            
            <h1><?php _e('Smart Restock & Waitlist Settings', 'smart-restock-waitlist'); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'smart-restock-waitlist'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['settings-reset'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings reset to defaults successfully!', 'smart-restock-waitlist'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php settings_fields('srwm_settings'); ?>
                
                <div class="srwm-settings-section">
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
                        <th scope="row"><?php _e('Waitlist Display Threshold', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <input type="number" name="srwm_waitlist_display_threshold" 
                                   value="<?php echo esc_attr(get_option('srwm_waitlist_display_threshold', 5)); ?>" 
                                   min="0" max="1000" class="regular-text">
                            <p class="description">
                                <?php _e('Stock level at which to show waitlist option to customers. Must be between 0 and 1000.', 'smart-restock-waitlist'); ?>
                                <br><strong><?php _e('Example:', 'smart-restock-waitlist'); ?></strong> <?php _e('If set to 5, customers will see the waitlist option when stock is 5 or less.', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <?php if ($this->license_manager->is_pro_active()): ?>
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
                    <?php endif; ?>
                    
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
                </div>
                
                <?php if ($this->license_manager->is_pro_active()): ?>
                <div class="srwm-settings-section">
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
                    
                    <tr>
                        <th scope="row"><?php _e('Low Stock Threshold', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <input type="number" name="srwm_low_stock_threshold" 
                                   value="<?php echo esc_attr(get_option('srwm_low_stock_threshold', 5)); ?>" 
                                   min="0" max="1000" class="regular-text">
                            <p class="description">
                                <?php _e('Stock level at which to notify suppliers (global default). Must be between 0 and 1000.', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!$this->license_manager->is_pro_active()): ?>
                <div class="srwm-settings-section">
                    <div class="notice notice-info">
                        <p><strong><?php _e('Upgrade to Pro', 'smart-restock-waitlist'); ?></strong></p>
                        <p><?php _e('Unlock supplier management, automated notifications, and advanced features to streamline your restock workflow.', 'smart-restock-waitlist'); ?></p>
                        <p><a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-pro'); ?>" class="button button-primary"><?php _e('View Pro Features', 'smart-restock-waitlist'); ?></a></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($this->license_manager->is_pro_active()): ?>
                <div class="srwm-settings-section">
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
                </div>
                <?php endif; ?>
                
                <div class="srwm-settings-section">
                    <h2><?php _e('Waitlist Styling & Colors', 'smart-restock-waitlist'); ?></h2>
                    
                    <div class="srwm-two-column-layout">
                        <!-- Left Column -->
                        <div class="srwm-column">
                            <h3><?php _e('Main Colors', 'smart-restock-waitlist'); ?></h3>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Container Background', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_container_bg" 
                                           value="<?php echo esc_attr(get_option('srwm_container_bg', '#ffffff')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_container_bg_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_container_bg', '#ffffff')); ?>" 
                                           class="srwm-hex-input" placeholder="#ffffff">
                                </div>
                                <p class="description"><?php _e('Main container background', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Header Background', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_header_bg" 
                                           value="<?php echo esc_attr(get_option('srwm_header_bg', '#f8f9fa')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_header_bg_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_header_bg', '#f8f9fa')); ?>" 
                                           class="srwm-hex-input" placeholder="#f8f9fa">
                                </div>
                                <p class="description"><?php _e('Header section background', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Header Text Color', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_header_text" 
                                           value="<?php echo esc_attr(get_option('srwm_header_text', '#333333')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_header_text_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_header_text', '#333333')); ?>" 
                                           class="srwm-hex-input" placeholder="#333333">
                                </div>
                                <p class="description"><?php _e('Titles and headings', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Body Text Color', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_body_text" 
                                           value="<?php echo esc_attr(get_option('srwm_body_text', '#666666')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_body_text_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_body_text', '#666666')); ?>" 
                                           class="srwm-hex-input" placeholder="#666666">
                                </div>
                                <p class="description"><?php _e('Description text', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Border Color', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_border_color" 
                                           value="<?php echo esc_attr(get_option('srwm_border_color', '#e9ecef')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_border_color_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_border_color', '#e9ecef')); ?>" 
                                           class="srwm-hex-input" placeholder="#e9ecef">
                                </div>
                                <p class="description"><?php _e('Container borders', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Primary Button Background', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_btn_primary_bg" 
                                           value="<?php echo esc_attr(get_option('srwm_btn_primary_bg', '#007cba')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_btn_primary_bg_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_btn_primary_bg', '#007cba')); ?>" 
                                           class="srwm-hex-input" placeholder="#007cba">
                                </div>
                                <p class="description"><?php _e('Join Waitlist button', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Primary Button Text', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_btn_primary_text" 
                                           value="<?php echo esc_attr(get_option('srwm_btn_primary_text', '#ffffff')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_btn_primary_text_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_btn_primary_text', '#ffffff')); ?>" 
                                           class="srwm-hex-input" placeholder="#ffffff">
                                </div>
                                <p class="description"><?php _e('Button text color', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="srwm-column">
                            <h3><?php _e('Form & Status Colors', 'smart-restock-waitlist'); ?></h3>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Input Field Background', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_input_bg" 
                                           value="<?php echo esc_attr(get_option('srwm_input_bg', '#ffffff')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_input_bg_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_input_bg', '#ffffff')); ?>" 
                                           class="srwm-hex-input" placeholder="#ffffff">
                                </div>
                                <p class="description"><?php _e('Email input background', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Input Field Border', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_input_border" 
                                           value="<?php echo esc_attr(get_option('srwm_input_border', '#ced4da')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_input_border_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_input_border', '#ced4da')); ?>" 
                                           class="srwm-hex-input" placeholder="#ced4da">
                                </div>
                                <p class="description"><?php _e('Input border color', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Input Focus Border', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_input_focus_border" 
                                           value="<?php echo esc_attr(get_option('srwm_input_focus_border', '#007cba')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_input_focus_border_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_input_focus_border', '#007cba')); ?>" 
                                           class="srwm-hex-input" placeholder="#007cba">
                                </div>
                                <p class="description"><?php _e('Focused input border', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Success Status Background', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_success_bg" 
                                           value="<?php echo esc_attr(get_option('srwm_success_bg', '#d4edda')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_success_bg_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_success_bg', '#d4edda')); ?>" 
                                           class="srwm-hex-input" placeholder="#d4edda">
                                </div>
                                <p class="description"><?php _e('Success message background', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Success Status Text', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_success_text" 
                                           value="<?php echo esc_attr(get_option('srwm_success_text', '#155724')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_success_text_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_success_text', '#155724')); ?>" 
                                           class="srwm-hex-input" placeholder="#155724">
                                </div>
                                <p class="description"><?php _e('Success message text', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Progress Bar Background', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_progress_bg" 
                                           value="<?php echo esc_attr(get_option('srwm_progress_bg', '#e9ecef')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_progress_bg_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_progress_bg', '#e9ecef')); ?>" 
                                           class="srwm-hex-input" placeholder="#e9ecef">
                                </div>
                                <p class="description"><?php _e('Progress bar track', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-color-option">
                                <label><?php _e('Progress Bar Fill', 'smart-restock-waitlist'); ?></label>
                                <div class="srwm-color-group">
                                    <input type="color" name="srwm_progress_fill" 
                                           value="<?php echo esc_attr(get_option('srwm_progress_fill', '#007cba')); ?>" 
                                           class="srwm-color-picker">
                                    <input type="text" name="srwm_progress_fill_hex" 
                                           value="<?php echo esc_attr(get_option('srwm_progress_fill', '#007cba')); ?>" 
                                           class="srwm-hex-input" placeholder="#007cba">
                                </div>
                                <p class="description"><?php _e('Progress bar fill', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Layout Options -->
                    <div class="srwm-layout-options">
                        <h3><?php _e('Layout Options', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-option-row">
                            <div class="srwm-option">
                                <label><?php _e('Border Radius', 'smart-restock-waitlist'); ?></label>
                                <input type="number" name="srwm_border_radius" 
                                       value="<?php echo esc_attr(get_option('srwm_border_radius', '8')); ?>" 
                                       min="0" max="50" class="small-text"> px
                                <p class="description"><?php _e('Rounded corners (0-50px)', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-option">
                                <label><?php _e('Font Size', 'smart-restock-waitlist'); ?></label>
                                <select name="srwm_font_size">
                                    <option value="small" <?php selected(get_option('srwm_font_size', 'medium'), 'small'); ?>><?php _e('Small (12px)', 'smart-restock-waitlist'); ?></option>
                                    <option value="medium" <?php selected(get_option('srwm_font_size', 'medium'), 'medium'); ?>><?php _e('Medium (14px)', 'smart-restock-waitlist'); ?></option>
                                    <option value="large" <?php selected(get_option('srwm_font_size', 'medium'), 'large'); ?>><?php _e('Large (16px)', 'smart-restock-waitlist'); ?></option>
                                    <option value="xlarge" <?php selected(get_option('srwm_font_size', 'medium'), 'xlarge'); ?>><?php _e('Extra Large (18px)', 'smart-restock-waitlist'); ?></option>
                                </select>
                                <p class="description"><?php _e('Base font size', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Proof Options -->
                    <div class="srwm-social-proof-options">
                        <h3><?php _e('Social Proof Display', 'smart-restock-waitlist'); ?></h3>
                        <div class="srwm-option-row">
                            <div class="srwm-option">
                                <label>
                                    <input type="checkbox" name="srwm_hide_social_proof" 
                                           value="1" <?php checked(get_option('srwm_hide_social_proof', '0'), '1'); ?>>
                                    <?php _e('Hide Social Proof Section', 'smart-restock-waitlist'); ?>
                                </label>
                                <p class="description"><?php _e('Hide the "people waiting" section to save space', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-option">
                                <label><?php _e('Social Proof Style', 'smart-restock-waitlist'); ?></label>
                                <select name="srwm_social_proof_style">
                                    <option value="compact" <?php selected(get_option('srwm_social_proof_style', 'full'), 'compact'); ?>><?php _e('Compact (Minimal)', 'smart-restock-waitlist'); ?></option>
                                    <option value="full" <?php selected(get_option('srwm_social_proof_style', 'full'), 'full'); ?>><?php _e('Full (Detailed)', 'smart-restock-waitlist'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choose between compact or full social proof display', 'smart-restock-waitlist'); ?></p>
                            </div>
                            
                            <div class="srwm-option">
                                <label>
                                    <input type="checkbox" name="srwm_hide_header_after_submit" 
                                           value="1" <?php checked(get_option('srwm_hide_header_after_submit', '1'), '1'); ?>>
                                    <?php _e('Hide Header After Submission', 'smart-restock-waitlist'); ?>
                                </label>
                                <p class="description"><?php _e('Hide "Join the Waitlist" header when user is already on waitlist', 'smart-restock-waitlist'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="srwm-settings-section">
                    <h2><?php _e('Email Templates', 'smart-restock-waitlist'); ?></h2>
                    <p class="description"><?php _e('Configure email templates for different notifications. Each template serves a specific purpose.', 'smart-restock-waitlist'); ?></p>
                    <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Waitlist Registration Email', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <textarea name="srwm_email_template_registration" rows="8" cols="50" class="large-text"><?php 
                                $template = get_option('srwm_email_template_registration');
                                if (empty($template)) {
                                    $template = $this->get_default_registration_email_template();
                                }
                                echo esc_textarea($template); 
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {customer_name}, {product_name}, {product_url}, {site_name}', 'smart-restock-waitlist'); ?>
                                <br><strong><?php _e('This email is sent when a customer joins the waitlist (welcome/confirmation email).', 'smart-restock-waitlist'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Restock Notification Email', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <textarea name="srwm_email_template_waitlist" rows="8" cols="50" class="large-text"><?php 
                                $template = get_option('srwm_email_template_waitlist');
                                // Force use of new default template if old corrupted template exists
                                if (empty($template) || strpos($template, '\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\') !== false) {
                                    $template = $this->get_default_waitlist_email_template();
                                    // Update the database with the correct template
                                    update_option('srwm_email_template_waitlist', $template);
                                }
                                echo esc_textarea($template); 
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {customer_name}, {product_name}, {product_url}, {site_name}', 'smart-restock-waitlist'); ?>
                                <br><strong><?php _e('This email is sent when a product comes back in stock to notify waitlist customers.', 'smart-restock-waitlist'); ?></strong>
                            </p>
                        </td>
                    </tr>
                    

                    
                    <?php if ($this->license_manager->is_pro_active()): ?>
                    <tr>
                        <th scope="row"><?php _e('Supplier Notification Email', 'smart-restock-waitlist'); ?></th>
                        <td>
                            <textarea name="srwm_email_template_supplier" rows="8" cols="50" class="large-text"><?php 
                                $template = get_option('srwm_email_template_supplier');
                                if (empty($template)) {
                                    $template = $this->get_default_supplier_email_template();
                                }
                                echo esc_textarea($template); 
                            ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {supplier_name}, {product_name}, {sku}, {current_stock}, {waitlist_count}, {site_name}', 'smart-restock-waitlist'); ?>
                                <br><?php _e('Pro placeholders: {restock_link}, {po_number}', 'smart-restock-waitlist'); ?>
                            </p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </table>
                    
                    <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                        <p style="margin: 0 0 10px 0;"><strong><?php _e('Need to reset email templates?', 'smart-restock-waitlist'); ?></strong></p>
                        <p style="margin: 0 0 15px 0; color: #666;"><?php _e('If your email templates are showing the wrong content, you can reset them to the correct defaults.', 'smart-restock-waitlist'); ?></p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-restock-waitlist-settings&action=reset-email-templates'), 'srwm_reset_email_templates'); ?>" 
                           class="button button-secondary" 
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset email templates to defaults?', 'smart-restock-waitlist'); ?>')">
                            <?php _e('Reset Email Templates to Defaults', 'smart-restock-waitlist'); ?>
                        </a>
                        <br><br>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-restock-waitlist-settings&action=force-cleanup'), 'srwm_force_cleanup'); ?>" 
                           class="button button-primary" 
                           onclick="return confirm('<?php esc_attr_e('This will force clean up any corrupted email templates. Continue?', 'smart-restock-waitlist'); ?>')">
                            <?php _e('Force Clean Up Corrupted Templates', 'smart-restock-waitlist'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="srwm-submit-actions">
                    <p class="submit">
                    <?php submit_button(__('Save Settings', 'smart-restock-waitlist'), 'primary', 'submit', false); ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=smart-restock-waitlist-settings&action=reset-defaults'), 'srwm_reset_defaults'); ?>" 
                       class="button button-secondary" 
                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset all settings to defaults?', 'smart-restock-waitlist'); ?>')">
                        <?php _e('Reset to Defaults', 'smart-restock-waitlist'); ?>
                    </a>
                    </p>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Sync color picker with hex input
            $('.srwm-color-picker').on('change', function() {
                var colorPicker = $(this);
                var hexInput = colorPicker.siblings('.srwm-hex-input');
                hexInput.val(colorPicker.val());
            });
            
            // Sync hex input with color picker
            $('.srwm-hex-input').on('input', function() {
                var hexInput = $(this);
                var colorPicker = hexInput.siblings('.srwm-color-picker');
                var value = hexInput.val();
                
                // Validate hex color format
                if (/^#[0-9A-F]{6}$/i.test(value)) {
                    colorPicker.val(value);
                }
            });
            
            // Initialize sync on page load
            $('.srwm-color-picker').each(function() {
                var colorPicker = $(this);
                var hexInput = colorPicker.siblings('.srwm-hex-input');
                hexInput.val(colorPicker.val());
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render analytics page
     */
    // Analytics functionality moved to Dashboard
    public function render_analytics_page() {
        // This method has been removed - analytics functionality moved to Dashboard
        wp_die(__('Analytics functionality has been moved to the Dashboard. Please use the Dashboard for all analytics features.', 'smart-restock-waitlist'));
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
                        
                        <!-- Search and Filter Controls -->
                        <div class="srwm-search-filters">
                            <div class="srwm-search-box">
                                <input type="text" id="upload-links-search" placeholder="<?php _e('Search by supplier name, company, or email...', 'smart-restock-waitlist'); ?>">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="srwm-filters">
                                <select id="upload-links-status-filter">
                                    <option value=""><?php _e('All Status', 'smart-restock-waitlist'); ?></option>
                                    <option value="active"><?php _e('Active', 'smart-restock-waitlist'); ?></option>
                                    <option value="used"><?php _e('Used', 'smart-restock-waitlist'); ?></option>
                                    <option value="expired"><?php _e('Expired', 'smart-restock-waitlist'); ?></option>
                                </select>
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
                </div>
                
                <!-- Quick Restock Tab -->
                <div class="srwm-tab-content" id="quick-restock-tab">
                    <div class="srwm-quick-restock-section">
                        <div class="srwm-section-header">
                            <h2><i class="fas fa-bolt"></i> <?php _e('Quick Restock Operations', 'smart-restock-waitlist'); ?></h2>
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
                    
                    <!-- Product Selection Section -->
                    <div class="srwm-product-selection-section">
                        <div class="srwm-section-header">
                            <h3><i class="fas fa-search"></i> <?php _e('Select Products for Restock', 'smart-restock-waitlist'); ?></h3>
                        </div>
                        
                        <!-- Search and Filter Controls -->
                        <div class="srwm-search-filters">
                            <div class="srwm-search-box">
                                <input type="text" id="quick-restock-product-search" placeholder="<?php _e('Search products by name, SKU, or category...', 'smart-restock-waitlist'); ?>">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="srwm-filters">
                                <select id="quick-restock-category-filter">
                                    <option value=""><?php _e('All Categories', 'smart-restock-waitlist'); ?></option>
                                    <?php foreach ($categories as $slug => $name): ?>
                                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="quick-restock-stock-filter">
                                    <option value=""><?php _e('All Stock Status', 'smart-restock-waitlist'); ?></option>
                                    <option value="instock"><?php _e('In Stock', 'smart-restock-waitlist'); ?></option>
                                    <option value="outofstock"><?php _e('Out of Stock', 'smart-restock-waitlist'); ?></option>
                                    <option value="lowstock"><?php _e('Low Stock', 'smart-restock-waitlist'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Product Selection Controls -->
                        <div class="srwm-selection-controls">
                            <div class="srwm-selection-info">
                                <span id="selected-products-count">0</span> <?php _e('products selected', 'smart-restock-waitlist'); ?>
                            </div>
                            <div class="srwm-selection-actions">
                                <button type="button" class="button button-secondary" id="select-all-products">
                                    <i class="fas fa-check-square"></i> <?php _e('Select All', 'smart-restock-waitlist'); ?>
                                </button>
                                <button type="button" class="button button-secondary" id="clear-selection">
                                    <i class="fas fa-square"></i> <?php _e('Clear Selection', 'smart-restock-waitlist'); ?>
                                </button>
                                <button type="button" class="button button-primary" id="generate-links-for-selected" style="display: none;">
                                    <i class="fas fa-link"></i> <?php _e('Generate Links for Selected Products', 'smart-restock-waitlist'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Products Grid -->
                        <div class="srwm-products-grid" id="quick-restock-products-grid">
                            <div class="srwm-loading">
                                <span class="spinner is-active"></span>
                                <?php _e('Loading products...', 'smart-restock-waitlist'); ?>
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
        
        .srwm-quick-restock-links-section {
            padding: 0;
        }
        
        .srwm-quick-restock-links-section .srwm-table-container {
            margin: 0;
            border-radius: 0;
            border: none;
        }
        
        .srwm-quick-restock-links-table {
            table-layout: fixed;
        }
        
        .srwm-quick-restock-links-table th:nth-child(1) { width: 20%; } /* Product */
        .srwm-quick-restock-links-table th:nth-child(2) { width: 25%; } /* Restock Link */
        .srwm-quick-restock-links-table th:nth-child(3) { width: 20%; } /* Supplier */
        .srwm-quick-restock-links-table th:nth-child(4) { width: 15%; } /* Expires */
        .srwm-quick-restock-links-table th:nth-child(5) { width: 10%; } /* Status */
        .srwm-quick-restock-links-table th:nth-child(6) { width: 10%; } /* Actions */
        
        /* Product Selection Section Styles */
        .srwm-product-selection-section {
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .srwm-product-selection-section .srwm-section-header {
            background: #f8fafc;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .srwm-product-selection-section .srwm-section-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .srwm-search-filters {
            padding: 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .srwm-search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .srwm-search-box input {
            width: 100%;
            padding: 12px 16px 12px 40px;
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
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .srwm-filters {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .srwm-filters select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #374151;
            min-width: 150px;
        }
        
        .srwm-filters select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .srwm-selection-controls {
            padding: 16px 20px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .srwm-selection-info {
            font-size: 14px;
            color: #6b7280;
        }
        
        .srwm-selection-info span {
            font-weight: 600;
            color: #3b82f6;
        }
        
        .srwm-selection-actions {
            display: flex;
            gap: 8px;
        }
        
        .srwm-products-grid {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .srwm-product-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .srwm-product-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }
        
        .srwm-product-card.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .srwm-product-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #3b82f6;
        }
        
        .srwm-product-info {
            flex: 1;
        }
        
        .srwm-product-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .srwm-product-sku {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .srwm-product-stock {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .srwm-product-stock.instock {
            color: #059669;
        }
        
        .srwm-product-stock.outofstock {
            color: #dc2626;
        }
        
        .srwm-product-stock.lowstock {
            color: #d97706;
        }
        
        /* Pagination Styles */
        .srwm-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 20px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .srwm-pagination-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .srwm-pagination-btn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-pagination-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .srwm-page-numbers {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .srwm-page-number {
            background: white;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 36px;
            text-align: center;
        }
        
        .srwm-page-number:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .srwm-page-number.active {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border-color: #3b82f6;
        }
        
        .srwm-page-ellipsis {
            color: #6b7280;
            padding: 6px 8px;
            font-size: 0.9rem;
        }
        
        .srwm-page-info {
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .srwm-pagination {
                flex-direction: column;
                gap: 12px;
            }
            
            .srwm-page-numbers {
                order: 2;
            }
            
            .srwm-page-info {
                order: 3;
                text-align: center;
            }
        }
        
        /* Upload Links Table Pagination */
        .srwm-pagination-cell {
            padding: 0 !important;
            border: none !important;
        }
        
        .srwm-pagination-cell .srwm-pagination {
            margin: 0;
            border-radius: 0;
            border: none;
            background: #f8fafc;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .srwm-pagination-btn,
        .srwm-page-number {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .srwm-pagination-btn:hover,
        .srwm-page-number:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #1f2937;
        }
        
        .srwm-page-number.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .srwm-page-numbers {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .srwm-page-ellipsis {
            padding: 8px 4px;
            color: #6b7280;
        }
        
        .srwm-page-info {
            color: #6b7280;
            font-size: 14px;
            margin-left: 16px;
        }
        
        /* Selected Products Display */
        .srwm-selected-products-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .srwm-selected-products-info h4 {
            margin: 0 0 12px 0;
            color: #374151;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .srwm-selected-products-list {
            max-height: 150px;
            overflow-y: auto;
        }
        
        .srwm-selected-product {
            padding: 8px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #4b5563;
        }
        
        .srwm-selected-product:last-child {
            margin-bottom: 0;
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
        
        .srwm-upload-links-table,
        .srwm-quick-restock-links-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .srwm-upload-links-table th,
        .srwm-quick-restock-links-table th {
            background: #f8fafc;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }
        
        .srwm-upload-links-table td,
        .srwm-quick-restock-links-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
            font-size: 0.9rem;
        }
        
        .srwm-upload-links-table tr:hover,
        .srwm-quick-restock-links-table tr:hover {
            background: #f8fafc;
        }
        
        .srwm-upload-links-table tr:last-child td,
        .srwm-quick-restock-links-table tr:last-child td {
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
        
        /* Quick Restock Table Specific Styles - Matching CSV Upload Colors */
        .srwm-quick-restock-links-table .srwm-table-action-btn {
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
        
        .srwm-quick-restock-links-table .srwm-table-action-btn.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        
        .srwm-quick-restock-links-table .srwm-table-action-btn.secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .srwm-quick-restock-links-table .srwm-table-action-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .srwm-quick-restock-links-table .srwm-table-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
    
                    
                    // Remove active class from all tabs and buttons
                    $('.srwm-tab-button').removeClass('active');
                    $('.srwm-tab-content').removeClass('active');
                    
                    // Add active class to clicked button and corresponding content
                    $(this).addClass('active');
                    const targetTab = $('#' + tabId + '-tab');
                    targetTab.addClass('active');
                    
                    
                    
                    // Load content based on tab
                    if (tabId === 'quick-restock') {
                        loadQuickRestockProducts();
                    } else if (tabId === 'csv-upload') {
                        loadUploadLinks();
                    }
                });
            
            // Load suppliers on page load
            loadSuppliers();
            

            
            // Load quick restock links on page load
            loadQuickRestockLinks();
            
            // Load products if Quick Restock tab is active
            if ($('#quick-restock-tab').hasClass('active')) {
                loadQuickRestockProducts();
            }
            
            // Load upload links if CSV Upload tab is active
            if ($('#csv-upload-tab').hasClass('active')) {
                loadUploadLinks();
            }
            
            // Debug: Check if Quick Restock tab content exists
            
            
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
                loadUploadLinks(1);
            });
            
            // Upload links search and filter
            $('#upload-links-search').on('input', debounce(function() {
                loadUploadLinks(1);
            }, 300));
            
            $('#upload-links-status-filter').on('change', function() {
                loadUploadLinks(1);
            });
            
            // Quick Restock functionality - Generate links for selected products
            $('#generate-links-for-selected').on('click', function() {
                if (selectedProducts.size === 0) {
                    showNotification('Please select at least one product first.', 'warning');
                    return;
                }
                openBulkQuickRestockModal();
            });
            
            // Refresh quick restock links
            $('#refresh-quick-links-btn').on('click', function() {
                loadQuickRestockLinks();
            });
            
            // Quick Restock Product Selection functionality
            $('#quick-restock-product-search').on('input', debounce(filterQuickRestockProducts, 300));
            $('#quick-restock-category-filter, #quick-restock-stock-filter').on('change', filterQuickRestockProducts);
            
            // Product selection controls
            $('#select-all-products').on('click', function() {
                selectAllProducts();
            });
            
            $('#clear-selection').on('click', function() {
                clearProductSelection();
            });
            
            // Pagination event delegation for upload links and quick restock
            $(document).on('click', '.srwm-pagination-btn, .srwm-page-number', function() {
                const page = $(this).data('page');
                if (page) {
    
                    
                    // Determine which pagination was clicked based on the active tab
                    const activeTab = $('.srwm-tab-button.active').attr('data-tab');
                    
                    if (activeTab === 'quick-restock') {
                        loadQuickRestockProducts(page);
                    } else if (activeTab === 'csv-upload') {
                        loadUploadLinks(page);
                    }
                }
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
            
            let currentUploadLinksPage = 1;
            
            function loadUploadLinks(page = 1) {
                currentUploadLinksPage = page;
                $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-loading"><span class="spinner is-active"></span> Loading upload links...</td></tr>');
                
                const searchTerm = $('#upload-links-search').val();
                const statusFilter = $('#upload-links-status-filter').val();
                

                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_csv_upload_links',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        page: page,
                        per_page: 10,
                        search: searchTerm,
                        status: statusFilter
                    },
                    success: function(response) {
        
                        if (response.success) {
                            const data = response.data;
                            displayUploadLinks(data.links, data.pagination);
                        } else {
                            $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-error">Error loading upload links: ' + response.data + '</td></tr>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Upload links AJAX error:', { xhr, status, error });
                        $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-error">Error loading upload links. Please try again.</td></tr>');
                    }
                });
            }
            
            function displayUploadLinks(links, pagination) {
                if (links.length === 0) {
                    $('#upload-links-tbody').html('<tr><td colspan="6" class="srwm-empty">No upload links found. Generate links from supplier cards above.</td></tr>');
                    return;
                }
                
                let html = '';
                links.forEach(function(link, index) {
                    const expiresDate = new Date(link.expires_at);
                    const now = new Date();
                    const isExpired = expiresDate < now;
                    const status = isExpired ? 'expired' : (parseInt(link.used) === 1 ? 'used' : 'active');
                    
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
                
                // Add pagination row if needed
                if (pagination && pagination.total_count > 0) {
                    html += generateUploadLinksPaginationControls(pagination);
                }
                

                
                $('#upload-links-tbody').html(html);
            }
            
            function generateUploadLinksPaginationControls(pagination) {
                let html = '<tr><td colspan="6" class="srwm-pagination-cell">';
                html += '<div class="srwm-pagination">';
                
                // Previous button
                if (pagination.has_prev) {
                    html += `<button class="srwm-pagination-btn" data-page="${pagination.current_page - 1}">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>`;
                }
                
                // Page numbers
                html += '<div class="srwm-page-numbers">';
                for (let i = 1; i <= pagination.total_pages; i++) {
                    if (i === pagination.current_page) {
                        html += `<span class="srwm-page-number active">${i}</span>`;
                    } else if (i === 1 || i === pagination.total_pages || 
                              (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `<button class="srwm-page-number" data-page="${i}">${i}</button>`;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += '<span class="srwm-page-ellipsis">...</span>';
                    }
                }
                html += '</div>';
                
                // Next button
                if (pagination.has_next) {
                    html += `<button class="srwm-pagination-btn" data-page="${pagination.current_page + 1}">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>`;
                }
                
                // Page info
                html += `<div class="srwm-page-info">
                            Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to 
                            ${Math.min(pagination.current_page * pagination.per_page, pagination.total_count)} 
                            of ${pagination.total_count} upload links
                        </div>`;
                
                html += '</div></td></tr>';
                return html;
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
                const uploadUrl = '<?php echo site_url(); ?>/?srwm_csv_upload=1&token=' + token;
                
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

                // TODO: Implement link details modal
                showNotification('Link details feature coming soon!', 'info');
            }
            
            function deleteUploadLink(token) {

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
                                const displayName = supplier.company_name ? 
                                    `${supplier.supplier_name} - ${supplier.company_name}` : 
                                    supplier.supplier_name;
                                options += `<option value="${supplier.supplier_email}">${displayName} (${supplier.supplier_email})</option>`;
                            });
                            
                            // Populate both the old and new supplier dropdowns
                            $('#quick-restock-supplier').html(options);
                            $('#bulk-quick-restock-supplier').html(options);
                        } else {
                            console.error('Error loading suppliers:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error loading suppliers:', error);
                    }
                });
            }
            
            function generateQuickRestockLink() {
                // Check if this is bulk generation or single generation
                const isBulkGeneration = selectedProducts.size > 0;
                
                if (isBulkGeneration) {
                    // Bulk generation for selected products
                    const supplierEmail = $('#bulk-quick-restock-supplier').val();
                    const expires = $('#bulk-quick-restock-expires').val();
                    
                    if (!supplierEmail) {
                        showNotification('Please select a supplier.', 'error');
                        return;
                    }
                    
                    generateBulkQuickRestockLinks(supplierEmail, expires);
                } else {
                    // Single product generation (legacy)
                    const productId = $('#quick-restock-product').val();
                    const supplierEmail = $('#quick-restock-supplier').val();
                    const expires = $('#quick-restock-expires').val();
                    
                    if (!productId || !supplierEmail) {
                        showNotification('Please select both product and supplier.', 'error');
                        return;
                    }
                    
                    generateSingleQuickRestockLink(productId, supplierEmail, expires);
                }
            }
            
            function generateBulkQuickRestockLinks(supplierEmail, expires) {
                $('#quick-restock-loading').show();
                $('#quick-restock-content').hide();
                $('#generate-quick-restock-link-btn').prop('disabled', true);
                
                // Get selected product IDs
                const productIds = Array.from(selectedProducts);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_generate_bulk_quick_restock_links',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        product_ids: productIds,
                        supplier_email: supplierEmail,
                        expires: expires
                    },
                    success: function(response) {
                        $('#quick-restock-loading').hide();
                        $('#generate-quick-restock-link-btn').prop('disabled', false);
                        
                        if (response.success) {
                            showNotification(`Successfully generated ${response.data.generated_count} quick restock links!`, 'success');
                            
                            // Clear selection and refresh
                            selectedProducts.clear();
                            updateSelectedCount();
                            
                            // Refresh the quick restock links table
                            setTimeout(function() {
                                $('#quick-restock-links-tbody').html('<tr><td colspan="6" class="srwm-loading"><span class="spinner is-active"></span> Refreshing quick restock links...</td></tr>');
                                loadQuickRestockLinks();
                            }, 500);
                            
                            // Close modal
                            closeAllModals();
                        } else {
                            $('#quick-restock-content').show();
                            showNotification(response.data || 'Error generating quick restock links', 'error');
                        }
                    },
                    error: function() {
                        $('#quick-restock-loading').hide();
                        $('#quick-restock-content').show();
                        $('#generate-quick-restock-link-btn').prop('disabled', false);
                        showNotification('Error generating quick restock links. Please try again.', 'error');
                    }
                });
            }
            
            function generateSingleQuickRestockLink(productId, supplierEmail, expires) {
                
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
            
            // Product Selection Functions
            let allProducts = [];
            let filteredProducts = [];
            let selectedProducts = new Set();
            
            let currentPage = 1;
            let totalPages = 1;
            let totalProducts = 0;
            
            function loadQuickRestockProducts(page = 1) {
                currentPage = page;
                $('#quick-restock-products-grid').html('<div class="srwm-loading"><span class="spinner is-active"></span> Loading products...</div>');
                
                const searchTerm = $('#quick-restock-product-search').val();
                const categoryFilter = $('#quick-restock-category-filter').val();
                const stockFilter = $('#quick-restock-stock-filter').val();
                

                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_get_products_for_restock',
                        nonce: '<?php echo wp_create_nonce('srwm_nonce'); ?>',
                        page: page,
                        per_page: 10,
                        search: searchTerm,
                        category: categoryFilter,
                        stock_status: stockFilter
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            displayQuickRestockProducts(data.products, data.pagination);
                        } else {
                            $('#quick-restock-products-grid').html('<div class="srwm-error">Error loading products: ' + response.data + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#quick-restock-products-grid').html('<div class="srwm-error">Error loading products. Please try again.</div>');
                    }
                });
            }
            
            function displayQuickRestockProducts(products, pagination) {
                if (products.length === 0) {
                    $('#quick-restock-products-grid').html('<div class="srwm-empty">No products found matching your criteria.</div>');
                    return;
                }
                
                let html = '';
                products.forEach(function(product) {
                    const isSelected = selectedProducts.has(product.id);
                    const stockClass = product.stock_status === 'instock' ? 'instock' : 
                                     (product.stock_status === 'outofstock' ? 'outofstock' : 'lowstock');
                    
                    html += `
                        <div class="srwm-product-card ${isSelected ? 'selected' : ''}" data-product-id="${product.id}">
                            <input type="checkbox" class="srwm-product-checkbox" ${isSelected ? 'checked' : ''} 
                                   onchange="toggleProductSelection(${product.id}, this.checked)">
                            <div class="srwm-product-info">
                                <div class="srwm-product-name">${product.name}</div>
                                <div class="srwm-product-sku">SKU: ${product.sku || 'N/A'}</div>
                                <div class="srwm-product-stock ${stockClass}">
                                    Stock: ${product.stock_quantity || 0} | Status: ${product.stock_status}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Add pagination controls
                if (pagination && pagination.total_count > 0) {
                    html += generatePaginationControls(pagination);
                }
                
                $('#quick-restock-products-grid').html(html);
                updateSelectedCount();
            }
            
            function generatePaginationControls(pagination) {
                let html = '<div class="srwm-pagination">';
                
                // Previous button
                if (pagination.has_prev) {
                    html += `<button class="srwm-pagination-btn" data-page="${pagination.current_page - 1}">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>`;
                }
                
                // Page numbers
                html += '<div class="srwm-page-numbers">';
                for (let i = 1; i <= pagination.total_pages; i++) {
                    if (i === pagination.current_page) {
                        html += `<span class="srwm-page-number active">${i}</span>`;
                    } else if (i === 1 || i === pagination.total_pages || 
                              (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                        html += `<button class="srwm-page-number" data-page="${i}">${i}</button>`;
                    } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                        html += '<span class="srwm-page-ellipsis">...</span>';
                    }
                }
                html += '</div>';
                
                // Next button
                if (pagination.has_next) {
                    html += `<button class="srwm-pagination-btn" data-page="${pagination.current_page + 1}">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>`;
                }
                
                // Page info
                html += `<div class="srwm-page-info">
                            Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to 
                            ${Math.min(pagination.current_page * pagination.per_page, pagination.total_count)} 
                            of ${pagination.total_count} products
                        </div>`;
                
                html += '</div>';
                return html;
            }
            
            function filterQuickRestockProducts() {
                // Reset to first page when filtering
                loadQuickRestockProducts(1);
            }
            
            function toggleProductSelection(productId, isSelected) {
                if (isSelected) {
                    selectedProducts.add(productId);
                } else {
                    selectedProducts.delete(productId);
                }
                
                updateSelectedCount();
                
                // Update card appearance
                const card = $(`.srwm-product-card[data-product-id="${productId}"]`);
                if (isSelected) {
                    card.addClass('selected');
                } else {
                    card.removeClass('selected');
                }
            }
            
            function selectAllProducts() {
                // Select all products on the current page
                $('.srwm-product-checkbox').each(function() {
                    const productId = parseInt($(this).closest('.srwm-product-card').data('product-id'));
                    selectedProducts.add(productId);
                    $(this).prop('checked', true);
                    $(this).closest('.srwm-product-card').addClass('selected');
                });
                updateSelectedCount();
            }
            
            function clearProductSelection() {
                selectedProducts.clear();
                $('.srwm-product-checkbox').prop('checked', false);
                $('.srwm-product-card').removeClass('selected');
                updateSelectedCount();
            }
            
            function updateSelectedCount() {
                $('#selected-products-count').text(selectedProducts.size);
                
                // Show/hide generate button based on selection
                if (selectedProducts.size > 0) {
                    $('#generate-links-for-selected').show();
                } else {
                    $('#generate-links-for-selected').hide();
                }
            }
            
            // Global function for product selection
            window.toggleProductSelection = toggleProductSelection;
            
            function openBulkQuickRestockModal() {
                // Get selected product details from the current page
                const selectedProductDetails = [];
                selectedProducts.forEach(function(productId) {
                    // Find the product card and extract details
                    const productCard = $(`.srwm-product-card[data-product-id="${productId}"]`);
                    if (productCard.length > 0) {
                        const productName = productCard.find('.srwm-product-name').text();
                        const productSku = productCard.find('.srwm-product-sku').text().replace('SKU: ', '');
                        selectedProductDetails.push({
                            id: productId,
                            name: productName,
                            sku: productSku
                        });
                    }
                });
                
                // Update modal content for bulk generation
                let productsList = '';
                selectedProductDetails.forEach(function(product) {
                    productsList += `
                        <div class="srwm-selected-product">
                            <strong>${product.name}</strong> (SKU: ${product.sku || 'N/A'})
                        </div>
                    `;
                });
                
                // Show selected products in modal
                $('#quick-restock-content').html(`
                    <div class="srwm-selected-products-info">
                        <h4><i class="fas fa-list"></i> Selected Products (${selectedProductDetails.length})</h4>
                        <div class="srwm-selected-products-list">
                            ${productsList}
                        </div>
                    </div>
                    
                    <form id="bulk-quick-restock-form">
                        <div class="srwm-form-group">
                            <label for="bulk-quick-restock-supplier"><?php _e('Select Supplier *', 'smart-restock-waitlist'); ?></label>
                            <select id="bulk-quick-restock-supplier" name="supplier_email" required>
                                <option value=""><?php _e('Choose a supplier...', 'smart-restock-waitlist'); ?></option>
                            </select>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label for="bulk-quick-restock-expires"><?php _e('Link Expiration', 'smart-restock-waitlist'); ?></label>
                            <select id="bulk-quick-restock-expires" name="expires">
                                <option value="1"><?php _e('1 day', 'smart-restock-waitlist'); ?></option>
                                <option value="3"><?php _e('3 days', 'smart-restock-waitlist'); ?></option>
                                <option value="7" selected><?php _e('7 days', 'smart-restock-waitlist'); ?></option>
                                <option value="14"><?php _e('14 days', 'smart-restock-waitlist'); ?></option>
                                <option value="30"><?php _e('30 days', 'smart-restock-waitlist'); ?></option>
                            </select>
                        </div>
                    </form>
                    
                    <div class="srwm-info-card">
                        <h4><i class="fas fa-info-circle"></i> <?php _e('Bulk Quick Restock Details', 'smart-restock-waitlist'); ?></h4>
                        <ul>
                            <li><strong><?php _e('Products:', 'smart-restock-waitlist'); ?></strong> ${selectedProductDetails.length} products selected</li>
                            <li><strong><?php _e('Instant Updates:', 'smart-restock-waitlist'); ?></strong> <?php _e('Stock updates immediately without approval', 'smart-restock-waitlist'); ?></li>
                            <li><strong><?php _e('Email Notification:', 'smart-restock-waitlist'); ?></strong> <?php _e('Automatically sent to supplier', 'smart-restock-waitlist'); ?></li>
                            <li><strong><?php _e('Secure Access:', 'smart-restock-waitlist'); ?></strong> <?php _e('Unique token-based authentication', 'smart-restock-waitlist'); ?></li>
                        </ul>
                    </div>
                `);
                
                // Load suppliers for the dropdown
                loadQuickRestockSuppliers();
                
                // Show modal
                $('#quick-restock-modal').show();
                
                // Update modal title and button
                $('#quick-restock-modal .srwm-modal-header h3').text('Generate Quick Restock Links for Selected Products');
                $('#generate-quick-restock-link-btn').text('Generate Links for ' + selectedProductDetails.length + ' Products');
            }
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
                    <h3><?php _e('Advanced Supplier Management', 'smart-restock-waitlist'); ?></h3>
                    <p><?php _e('Complete supplier relationship management with profiles, categories, and analytics.', 'smart-restock-waitlist'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-suppliers'); ?>" class="button button-primary">
                        <?php _e('Manage Suppliers', 'smart-restock-waitlist'); ?>
                    </a>
                </div>
                
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
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return array();
        }
        
        // Check if WooCommerce is active and tables exist
        $wc_table = $wpdb->prefix . 'wc_product_meta_lookup';
        $wc_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$wc_table'") == $wc_table;
        
        if ($wc_table_exists) {
            $query = "SELECT p.ID as product_id, p.post_title as name, pm.meta_value as sku,
                            wc.stock_quantity as stock, COUNT(w.id) as waitlist_count
                     FROM {$wpdb->posts} p
                     LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
                     LEFT JOIN $wc_table wc ON p.ID = wc.product_id
                     INNER JOIN $table w ON p.ID = w.product_id
                     WHERE p.post_type = 'product' AND p.post_status = 'publish'
                     GROUP BY p.ID
                     ORDER BY waitlist_count DESC";
        } else {
            // Fallback query without WooCommerce tables
            $query = "SELECT p.ID as product_id, p.post_title as name, pm.meta_value as sku,
                            'N/A' as stock, COUNT(w.id) as waitlist_count
                     FROM {$wpdb->posts} p
                     LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
                     INNER JOIN $table w ON p.ID = w.product_id
                     WHERE p.post_type = 'product' AND p.post_status = 'publish'
                     GROUP BY p.ID
                     ORDER BY waitlist_count DESC";
        }
        
        $result = $wpdb->get_results($query, ARRAY_A);
        
        if ($wpdb->last_error) {
            error_log('SRWM Admin: Database error in get_waitlist_products: ' . $wpdb->last_error);
            return array();
        }
        
        return $result ?: array();
    }
    
    /**
     * Get products with supplier alerts
     */
    private function get_supplier_products() {
        try {
            $supplier = SRWM_Supplier::get_instance();
            return $supplier->get_products_with_suppliers();
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Get total waitlist customers
     */
    private function get_total_waitlist_customers() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return 0;
        }
        
        $result = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        return $result ?: 0;
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