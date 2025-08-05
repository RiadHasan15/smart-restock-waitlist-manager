<?php
/**
 * Plugin Name: Smart Restock & Waitlist Manager
 * Plugin URI: https://yourwebsite.com/smart-restock-waitlist-manager
 * Description: Advanced WooCommerce plugin for managing product restocks and customer waitlists with supplier notifications, analytics, and Pro features.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: smart-restock-waitlist
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SRWM_VERSION', '1.0.0');
define('SRWM_PLUGIN_FILE', __FILE__);
define('SRWM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRWM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRWM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * License Manager Class
 * Handles license activation, validation, and Pro feature management
 */
class SRWM_License_Manager {
    
    private $plugin_slug = 'smart-restock-waitlist-manager';
    
    public function __construct() {
        add_action('admin_init', array($this, 'handle_license_actions'));
        add_action('admin_notices', array($this, 'show_license_notices'));
    }
    

    
    /**
     * Add license menu to admin
     */
    public function add_license_menu() {
        add_submenu_page(
            'smart-restock-waitlist',
            __('License', 'smart-restock-waitlist'),
            __('License', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-license',
            array($this, 'render_license_page')
        );
    }
    
    /**
     * Handle license actions (activate, deactivate, check)
     */
    public function handle_license_actions() {
        if (!isset($_POST['srwm_license_action']) || !wp_verify_nonce($_POST['srwm_license_nonce'], 'srwm_license_nonce')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['srwm_license_action']);
        
        switch ($action) {
            case 'activate':
                $this->activate_license();
                break;
            case 'deactivate':
                $this->deactivate_license();
                break;
            case 'check':
                $this->check_license_status();
                break;
        }
    }
    
    /**
     * Activate license
     */
    private function activate_license() {
        if (empty($_POST['license_key'])) {
            $this->add_notice('error', __('Please enter a license key.', 'smart-restock-waitlist'));
            return;
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        // Always activate successfully for local testing
        update_option($this->plugin_slug . '_license_key', $license_key);
        update_option($this->plugin_slug . '_license_status', 'valid');
        update_option($this->plugin_slug . '_license_last_check', time());
        
        $this->add_notice('success', __('License activated successfully! Pro features are now enabled.', 'smart-restock-waitlist'));
    }
    

    
    /**
     * Deactivate license
     */
    private function deactivate_license() {
        delete_option($this->plugin_slug . '_license_key');
        update_option($this->plugin_slug . '_license_status', 'inactive');
        update_option($this->plugin_slug . '_license_last_check', time());
        
        $this->add_notice('success', __('License deactivated successfully! Pro features are now disabled.', 'smart-restock-waitlist'));
    }
    

    
    /**
     * Check license status
     */
    private function check_license_status() {
        $license_key = get_option($this->plugin_slug . '_license_key');
        
        if (empty($license_key)) {
            $this->add_notice('error', __('No license key found.', 'smart-restock-waitlist'));
            return;
        }
        
        update_option($this->plugin_slug . '_license_status', 'valid');
        update_option($this->plugin_slug . '_license_last_check', time());
        
        $this->add_notice('success', __('License is valid and active!', 'smart-restock-waitlist'));
    }
    
    /**
     * Render license page
     */
    public function render_license_page() {
        $license_key = get_option($this->plugin_slug . '_license_key', '');
        $status = get_option($this->plugin_slug . '_license_status', 'inactive');
        $last_check = get_option($this->plugin_slug . '_license_last_check', 0);
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Restock & Waitlist Manager - License', 'smart-restock-waitlist'); ?></h1>
            
            <div class="card" style="max-width: 600px;">
                <h2><?php _e('License Management (Local Testing)', 'smart-restock-waitlist'); ?></h2>
                
                <div class="notice notice-info">
                    <p><strong><?php _e('Local Testing Mode:', 'smart-restock-waitlist'); ?></strong> 
                    <?php _e('This is a dummy license system for local testing. Any license key will work.', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <form method="post" action="">
                    <?php wp_nonce_field('srwm_license_nonce', 'srwm_license_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('License Key', 'smart-restock-waitlist'); ?></th>
                            <td>
                                <input type="text" name="license_key" value="<?php echo esc_attr($license_key); ?>" 
                                       class="regular-text" placeholder="<?php _e('Enter any license key', 'smart-restock-waitlist'); ?>" />
                                <p class="description"><?php _e('For local testing, any license key will work.', 'smart-restock-waitlist'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Status', 'smart-restock-waitlist'); ?></th>
                            <td>
                                <?php if ($status == 'valid'): ?>
                                    <span style="color: green; font-weight: bold;">✓ <?php _e('Active', 'smart-restock-waitlist'); ?></span>
                                    <p class="description"><?php _e('Your license is active and Pro features are enabled.', 'smart-restock-waitlist'); ?></p>
                                <?php elseif ($status == 'invalid'): ?>
                                    <span style="color: red; font-weight: bold;">✗ <?php _e('Invalid', 'smart-restock-waitlist'); ?></span>
                                    <p class="description"><?php _e('Please check your license key and try again.', 'smart-restock-waitlist'); ?></p>
                                <?php elseif ($status == 'expired'): ?>
                                    <span style="color: orange; font-weight: bold;">⚠ <?php _e('Expired', 'smart-restock-waitlist'); ?></span>
                                    <p class="description"><?php _e('Your license has expired. Please renew to continue receiving updates.', 'smart-restock-waitlist'); ?></p>
                                <?php else: ?>
                                    <span style="color: gray;"><?php _e('Not activated', 'smart-restock-waitlist'); ?></span>
                                    <p class="description"><?php _e('Enter your license key and activate to enable Pro features.', 'smart-restock-waitlist'); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($last_check): ?>
                                    <p class="description"><?php _e('Last checked:', 'smart-restock-waitlist'); ?> <?php echo date('Y-m-d H:i:s', $last_check); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Current Version', 'smart-restock-waitlist'); ?></th>
                            <td><?php echo esc_html(SRWM_VERSION); ?></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <?php if ($status == 'valid'): ?>
                            <button type="submit" name="srwm_license_action" value="deactivate" class="button">
                                <?php _e('Deactivate License', 'smart-restock-waitlist'); ?>
                            </button>
                            <button type="submit" name="srwm_license_action" value="check" class="button">
                                <?php _e('Check Status', 'smart-restock-waitlist'); ?>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="srwm_license_action" value="activate" class="button-primary">
                                <?php _e('Activate License', 'smart-restock-waitlist'); ?>
                            </button>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
            
            
        </div>
        
        <style>
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; }
        .card h2 { margin-top: 0; }
        </style>
        <?php
    }
    
    /**
     * Show admin notices
     */
    public function show_license_notices() {
        $notices = get_option('srwm_license_notices', array());
        
        foreach ($notices as $notice) {
            $class = 'notice notice-' . $notice['type'];
            $message = $notice['message'];
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
        
        delete_option('srwm_license_notices');
    }
    
    /**
     * Add notice
     */
    private function add_notice($type, $message) {
        $notices = get_option('srwm_license_notices', array());
        $notices[] = array('type' => $type, 'message' => $message);
        update_option('srwm_license_notices', $notices);
    }
    
    /**
     * Check if Pro license is active
     */
    public function is_pro_active() {
        // For local testing, always return true
        return true;
    }
    
    /**
     * Get license key
     */
    public function get_license_key() {
        return $this->license_key;
    }
    
    /**
     * Get license status
     */
    public function get_license_status() {
        return $this->license_status;
    }
    
    /**
     * Debug method to check license data
     */

    

    

    

    

}

/**
 * Main Plugin Class
 */
class SmartRestockWaitlistManager {
    
    private $license_manager;
    
    public function __construct() {
        // Initialize license manager
        $this->license_manager = new SRWM_License_Manager();
        
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Create database tables
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Load core classes
        $this->load_core_classes();
        
        // Load Pro classes if license is active
        $this->maybe_load_pro_classes();
        
        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize frontend
        $this->init_frontend();
        
        // Add AJAX handlers
        $this->add_ajax_handlers();
    }
    
    /**
     * Check and load Pro classes if license is active
     */
    private function maybe_load_pro_classes() {
        if ($this->license_manager->is_pro_active()) {
            $this->load_pro_classes();
        }
    }
    
    /**
     * Force reload Pro classes (called after license status changes)
     */
    public function reload_pro_classes() {
        // Clear any existing Pro class instances
        $pro_classes = array('SRWM_Pro_Restock', 'SRWM_Pro_Purchase_Order', 'SRWM_Pro_CSV_Upload');
        
        foreach ($pro_classes as $class_name) {
            if (class_exists($class_name)) {
                try {
                    $reflection = new ReflectionClass($class_name);
                    if ($reflection->hasProperty('instance')) {
                        $property = $reflection->getProperty('instance');
                        $property->setAccessible(true);
                        $property->setValue(null, null);
                    }
                } catch (Exception $e) {
                    // Ignore reflection errors
                }
            }
        }
        
        // Clear any cached license status
        wp_cache_delete('srwm_license_status', 'options');
        
        // Reload Pro classes based on current license status
        $this->maybe_load_pro_classes();
    }
    
    /**
     * Load core classes
     */
    private function load_core_classes() {
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-waitlist.php';
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-supplier.php';
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-email.php';
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-analytics.php';
    }
    
    /**
     * Load Pro classes
     */
    private function load_pro_classes() {
        require_once SRWM_PLUGIN_DIR . 'includes/pro/class-srwm-pro-restock.php';
        require_once SRWM_PLUGIN_DIR . 'includes/pro/class-srwm-pro-purchase-order.php';
        require_once SRWM_PLUGIN_DIR . 'includes/pro/class-srwm-pro-csv-upload.php';
    }
    
    /**
     * Check if Pro classes should be loaded
     */
    public function should_load_pro_classes() {
        return $this->license_manager->is_pro_active();
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-admin.php';
        require_once SRWM_PLUGIN_DIR . 'admin/class-srwm-admin-dashboard.php';
        
        new SRWM_Admin($this->license_manager);
        new SRWM_Admin_Dashboard($this->license_manager);
        

    }
    
    /**
     * Initialize frontend functionality
     */
    private function init_frontend() {
        // Initialize core classes with license manager
        $waitlist = SRWM_Waitlist::get_instance($this->license_manager);
        $supplier = SRWM_Supplier::get_instance($this->license_manager);
        
        // Add waitlist form to product pages
        add_action('woocommerce_single_product_summary', array($waitlist, 'display_waitlist_form'), 25);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($waitlist, 'enqueue_scripts'));
        
        // Hook into stock status changes
        add_action('woocommerce_product_set_stock_status', array($supplier, 'check_stock_levels'), 10, 3);
        add_action('woocommerce_product_set_stock_status', array($waitlist, 'check_restock_notification'), 10, 3);
    }
    
    /**
     * Add AJAX handlers
     */
    private function add_ajax_handlers() {
        // Frontend AJAX
        add_action('wp_ajax_srwm_add_to_waitlist', array($this, 'ajax_add_to_waitlist'));
        add_action('wp_ajax_nopriv_srwm_add_to_waitlist', array($this, 'ajax_add_to_waitlist'));
        
        // Admin AJAX
        add_action('wp_ajax_srwm_restock_product', array($this, 'ajax_restock_product'));
        add_action('wp_ajax_srwm_get_waitlist_data', array($this, 'ajax_get_waitlist_data'));
        add_action('wp_ajax_srwm_export_waitlist', array($this, 'ajax_export_waitlist'));
        add_action('wp_ajax_srwm_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_srwm_export_dashboard_report', array($this, 'ajax_export_dashboard_report'));
        
        // Pro AJAX handlers
        if ($this->license_manager->is_pro_active()) {
            add_action('wp_ajax_srwm_generate_restock_link', array($this, 'ajax_generate_restock_link'));
            add_action('wp_ajax_srwm_generate_po', array($this, 'ajax_generate_po'));
            add_action('wp_ajax_srwm_generate_csv_upload_link', array($this, 'ajax_generate_csv_upload_link'));
            add_action('wp_ajax_srwm_save_threshold', array($this, 'ajax_save_threshold'));
            add_action('wp_ajax_srwm_reset_threshold', array($this, 'ajax_reset_threshold'));
            add_action('wp_ajax_srwm_save_notification_settings', array($this, 'ajax_save_notification_settings'));
            add_action('wp_ajax_srwm_save_email_templates', array($this, 'ajax_save_email_templates'));
            add_action('wp_ajax_srwm_save_global_threshold', array($this, 'ajax_save_global_threshold'));
        }
    }
    
    /**
     * AJAX: Add customer to waitlist
     */
    public function ajax_add_to_waitlist() {
        check_ajax_referer('srwm_waitlist_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['name']);
        
        if (empty($email) || !is_email($email)) {
            wp_die(json_encode(array('success' => false, 'message' => __('Please enter a valid email address.', 'smart-restock-waitlist'))));
        }
        
        $result = SRWM_Waitlist::add_customer($product_id, $email, $name);
        
        if ($result) {
            wp_die(json_encode(array('success' => true, 'message' => __('You have been added to the waitlist!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('You are already on the waitlist for this product.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Restock product
     */
    public function ajax_restock_product() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        $result = SRWM_Waitlist::restock_and_notify($product_id, $quantity);
        
        if ($result) {
            wp_die(json_encode(array('success' => true, 'message' => __('Product restocked successfully!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to restock product.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Get waitlist data
     */
    public function ajax_get_waitlist_data() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $customers = SRWM_Waitlist::get_waitlist_customers($product_id);
        
        wp_die(json_encode(array('success' => true, 'data' => $customers)));
    }
    
    /**
     * AJAX: Export waitlist
     */
    public function ajax_export_waitlist() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $customers = SRWM_Waitlist::get_waitlist_customers($product_id);
        
        $filename = 'waitlist-export-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Name', 'Email', 'Date Added', 'Notified'));
        
        foreach ($customers as $customer) {
            fputcsv($output, array(
                $customer->customer_name,
                $customer->customer_email,
                $customer->date_added,
                $customer->notified ? 'Yes' : 'No'
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $data = $analytics->get_dashboard_data();
        
        wp_die(json_encode(array('success' => true, 'data' => $data)));
    }
    
    /**
     * AJAX: Export dashboard report
     */
    public function ajax_export_dashboard_report() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $analytics->export_analytics_csv();
    }
    
    /**
     * AJAX: Generate restock link (Pro)
     */
    public function ajax_generate_restock_link() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $supplier_email = sanitize_email($_POST['supplier_email']);
        
        if (class_exists('SRWM_Pro_Restock')) {
            $restock = SRWM_Pro_Restock::get_instance();
            $result = $restock->generate_restock_link($product_id, $supplier_email);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'message' => __('Pro feature not available.', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Generate purchase order (Pro)
     */
    public function ajax_generate_po() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $supplier_data = array(
            'name' => sanitize_text_field($_POST['supplier_name']),
            'email' => sanitize_email($_POST['supplier_email'])
        );
        
        if (class_exists('SRWM_Pro_Purchase_Order')) {
            $po = SRWM_Pro_Purchase_Order::get_instance();
            $result = $po->generate_purchase_order($product_id, $supplier_data);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'message' => __('Pro feature not available.', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Generate CSV upload link (Pro)
     */
    public function ajax_generate_csv_upload_link() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        if (!isset($_POST['supplier_email'])) {
            wp_die(json_encode(array('success' => false, 'message' => __('Supplier email is required.', 'smart-restock-waitlist'))));
        }
        
        $supplier_email = sanitize_email($_POST['supplier_email']);
        
        if (!$supplier_email) {
            wp_die(json_encode(array('success' => false, 'message' => __('Please enter a valid email address.', 'smart-restock-waitlist'))));
        }
        
        if (class_exists('SRWM_Pro_CSV_Upload')) {
            $csv = SRWM_Pro_CSV_Upload::get_instance();
            $token = $csv->generate_csv_token($supplier_email);
            
            if ($token) {
                $upload_url = add_query_arg(array(
                    'srwm_csv_upload' => '1',
                    'token' => $token
                ), home_url());
                
                wp_die(json_encode(array(
                    'success' => true, 
                    'message' => __('CSV upload link generated successfully!', 'smart-restock-waitlist'),
                    'data' => array(
                        'link' => $upload_url,
                        'token' => $token
                    )
                )));
            } else {
                wp_die(json_encode(array('success' => false, 'message' => __('Failed to generate CSV upload link.', 'smart-restock-waitlist'))));
            }
        }
        
        wp_die(json_encode(array('success' => false, 'message' => __('Pro feature not available.', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save product threshold (Pro)
     */
    public function ajax_save_threshold() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        // Debug logging
        error_log('SRWM Save Threshold - User can manage WooCommerce: ' . (current_user_can('manage_woocommerce') ? 'Yes' : 'No'));
        error_log('SRWM Save Threshold - Pro license active: ' . ($this->license_manager->is_pro_active() ? 'Yes' : 'No'));
        error_log('SRWM Save Threshold - POST data: ' . print_r($_POST, true));
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        if (!$this->license_manager->is_pro_active()) {
            // For development, allow threshold saving even without license
            $current_key = get_option($this->plugin_slug . '_license_key', '');
            if (empty($current_key)) {
                wp_die(json_encode(array('success' => false, 'message' => __('Pro license required. Please activate your license first. Use DEV-LICENSE-12345 for testing.', 'smart-restock-waitlist'))));
            }
        }
        
        if (!isset($_POST['product_id']) || !isset($_POST['threshold'])) {
            wp_die(json_encode(array('success' => false, 'message' => __('Missing required data.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $threshold = intval($_POST['threshold']);
        
        if ($product_id <= 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Invalid product ID.', 'smart-restock-waitlist'))));
        }
        
        if ($threshold < 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Threshold must be a positive number.', 'smart-restock-waitlist'))));
        }
        
        // Save threshold as product meta
        $result = update_post_meta($product_id, '_srwm_threshold', $threshold);
        
        if ($result !== false) {
            wp_die(json_encode(array('success' => true, 'message' => __('Threshold saved successfully!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to save threshold. Please try again.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Reset product threshold (Pro)
     */
    public function ajax_reset_threshold() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        
        // Delete the custom threshold meta to use global default
        delete_post_meta($product_id, '_srwm_threshold');
        
        wp_die(json_encode(array('success' => true, 'message' => __('Threshold reset to global default!', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save notification settings (Pro)
     */
    public function ajax_save_notification_settings() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        // Save notification channel settings
        $notification_settings = array(
            'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
            'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? 1 : 0,
            'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
            'whatsapp_api_key' => sanitize_text_field($_POST['whatsapp_api_key'] ?? ''),
            'whatsapp_phone' => sanitize_text_field($_POST['whatsapp_phone'] ?? ''),
            'sms_api_key' => sanitize_text_field($_POST['sms_api_key'] ?? ''),
            'sms_phone' => sanitize_text_field($_POST['sms_phone'] ?? ''),
            'sms_provider' => sanitize_text_field($_POST['sms_provider'] ?? 'twilio')
        );
        
        update_option('srwm_notification_settings', $notification_settings);
        
        wp_die(json_encode(array('success' => true, 'message' => __('Notification settings saved successfully!', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save email templates (Pro)
     */
    public function ajax_save_email_templates() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        // Save email templates
        $templates = array(
            'waitlist_email_template' => wp_kses_post($_POST['srwm_waitlist_email_template'] ?? ''),
            'restock_email_template' => wp_kses_post($_POST['srwm_restock_email_template'] ?? ''),
            'supplier_email_template' => wp_kses_post($_POST['srwm_supplier_email_template'] ?? ''),
            'po_email_template' => wp_kses_post($_POST['srwm_po_email_template'] ?? '')
        );
        
        foreach ($templates as $key => $template) {
            update_option($key, $template);
        }
        
        wp_die(json_encode(array('success' => true, 'message' => __('Email templates saved successfully!', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save global threshold (Pro)
     */
    public function ajax_save_global_threshold() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        $global_threshold = intval($_POST['global_threshold']);
        
        if ($global_threshold < 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Global threshold must be a positive number.', 'smart-restock-waitlist'))));
        }
        
        update_option('srwm_global_threshold', $global_threshold);
        
        wp_die(json_encode(array('success' => true, 'message' => __('Global threshold saved successfully!', 'smart-restock-waitlist'))));
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Waitlist table
        $table_waitlist = $wpdb->prefix . 'srwm_waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_name varchar(255) DEFAULT '',
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            notified tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY customer_email (customer_email),
            KEY notified (notified)
        ) $charset_collate;";
        
        // Suppliers table
        $table_supplier = $wpdb->prefix . 'srwm_suppliers';
        $sql_supplier = "CREATE TABLE $table_supplier (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            supplier_name varchar(255) NOT NULL,
            supplier_email varchar(255) NOT NULL,
            threshold int(11) DEFAULT 5,
            channels longtext,
            auto_generate_po tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY supplier_email (supplier_email)
        ) $charset_collate;";
        
        // Restock logs table
        $table_logs = $wpdb->prefix . 'srwm_restock_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            quantity int(11) NOT NULL,
            method varchar(50) DEFAULT 'manual',
            ip_address varchar(45) DEFAULT '',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY method (method),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Pro tables - only create if license is active
        if ($this->license_manager->is_pro_active()) {
            // Restock tokens table
            $table_tokens = $wpdb->prefix . 'srwm_restock_tokens';
            $sql_tokens = "CREATE TABLE $table_tokens (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                product_id bigint(20) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                token varchar(255) NOT NULL,
                expires_at datetime NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY token (token),
                KEY expires_at (expires_at)
            ) $charset_collate;";
            
            // CSV upload tokens table
            $table_csv_tokens = $wpdb->prefix . 'srwm_csv_tokens';
            $sql_csv_tokens = "CREATE TABLE $table_csv_tokens (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                supplier_email varchar(255) NOT NULL,
                token varchar(255) NOT NULL,
                expires_at datetime NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY token (token),
                KEY expires_at (expires_at)
            ) $charset_collate;";
            
            // Purchase orders table
            $table_po = $wpdb->prefix . 'srwm_purchase_orders';
            $sql_po = "CREATE TABLE $table_po (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                po_number varchar(50) NOT NULL,
                product_id bigint(20) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                quantity int(11) NOT NULL,
                status varchar(50) DEFAULT 'pending',
                pdf_path varchar(500) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY po_number (po_number),
                KEY product_id (product_id),
                KEY status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_tokens);
            dbDelta($sql_csv_tokens);
            dbDelta($sql_po);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_waitlist);
        dbDelta($sql_supplier);
        dbDelta($sql_logs);
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('smart-restock-waitlist', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>' . 
             __('Smart Restock & Waitlist Manager requires WooCommerce to be installed and activated.', 'smart-restock-waitlist') . 
             '</p></div>';
    }
}

// Initialize the plugin
global $srwm_plugin;
$srwm_plugin = new SmartRestockWaitlistManager();