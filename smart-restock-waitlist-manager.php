<?php
/**
 * Plugin Name: Smart Restock & Waitlist Manager
 * Plugin URI: https://example.com/smart-restock-waitlist-manager
 * Description: Advanced restock management and customer waitlist system for WooCommerce. Includes supplier notifications, one-click restock, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: smart-restock-waitlist
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SRWM_VERSION', '1.0.0');
define('SRWM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRWM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SRWM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
function srwm_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . 
                 __('Smart Restock & Waitlist Manager requires WooCommerce to be installed and activated.', 'smart-restock-waitlist') . 
                 '</p></div>';
        });
        return false;
    }
    return true;
}

// Main plugin class
class SmartRestockWaitlistManager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        if (!srwm_check_woocommerce()) {
            return;
        }
        
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('smart-restock-waitlist', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function load_dependencies() {
        // Core classes
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-waitlist.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-supplier.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-admin.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-email.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-analytics.php';
        
        // Pro features
        if ($this->is_pro_active()) {
            require_once SRWM_PLUGIN_PATH . 'includes/pro/class-srwm-pro-restock.php';
            require_once SRWM_PLUGIN_PATH . 'includes/pro/class-srwm-pro-purchase-order.php';
            require_once SRWM_PLUGIN_PATH . 'includes/pro/class-srwm-pro-csv-upload.php';
        }
    }
    
    private function init_hooks() {
        // Initialize core functionality
        SRWM_Waitlist::get_instance();
        SRWM_Supplier::get_instance();
        SRWM_Admin::get_instance();
        SRWM_Email::get_instance();
        SRWM_Analytics::get_instance();
        
        // Pro features
        if ($this->is_pro_active()) {
            SRWM_Pro_Restock::get_instance();
            SRWM_Pro_Purchase_Order::get_instance();
            SRWM_Pro_CSV_Upload::get_instance();
        }
        
        // AJAX handlers
        add_action('wp_ajax_srwm_add_to_waitlist', array($this, 'ajax_add_to_waitlist'));
        add_action('wp_ajax_nopriv_srwm_add_to_waitlist', array($this, 'ajax_add_to_waitlist'));
        add_action('wp_ajax_srwm_restock_product', array($this, 'ajax_restock_product'));
    }
    
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Waitlist table
        $table_waitlist = $wpdb->prefix . 'srwm_waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_name varchar(255),
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            notified tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY customer_email (customer_email)
        ) $charset_collate;";
        
        // Supplier table
        $table_supplier = $wpdb->prefix . 'srwm_suppliers';
        $sql_supplier = "CREATE TABLE $table_supplier (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            supplier_email varchar(255) NOT NULL,
            supplier_name varchar(255),
            notification_channels text,
            threshold int(11) DEFAULT 5,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Restock logs table
        $table_logs = $wpdb->prefix . 'srwm_restock_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            supplier_id bigint(20),
            quantity int(11) NOT NULL,
            method varchar(50),
            ip_address varchar(45),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_waitlist);
        dbDelta($sql_supplier);
        dbDelta($sql_logs);
    }
    
    private function set_default_options() {
        $defaults = array(
            'waitlist_enabled' => 'yes',
            'supplier_notifications' => 'yes',
            'email_template_waitlist' => 'Hi {customer_name},\n\nGreat news! {product_name} is back in stock.\n\nYou can purchase it here: {product_url}\n\nBest regards,\n{site_name}',
            'email_template_supplier' => 'Hi {supplier_name},\n\nProduct {product_name} (SKU: {sku}) is running low on stock.\n\nCurrent stock: {current_stock}\nWaitlist count: {waitlist_count}\n\nPlease restock as soon as possible.\n\nBest regards,\n{site_name}',
            'low_stock_threshold' => 5,
            'auto_disable_at_zero' => 'no'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option('srwm_' . $key) === false) {
                update_option('srwm_' . $key, $value);
            }
        }
    }
    
    public function is_pro_active() {
        // Check if pro version is active
        return function_exists('srwm_pro_init') || defined('SRWM_PRO_VERSION');
    }
    
    public function ajax_add_to_waitlist() {
        check_ajax_referer('srwm_waitlist_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['name']);
        
        if (!$product_id || !$email) {
            wp_die(__('Invalid data provided.', 'smart-restock-waitlist'));
        }
        
        $result = SRWM_Waitlist::add_customer($product_id, $email, $name);
        
        if ($result) {
            wp_send_json_success(__('Successfully added to waitlist!', 'smart-restock-waitlist'));
        } else {
            wp_send_json_error(__('Failed to add to waitlist.', 'smart-restock-waitlist'));
        }
    }
    
    public function ajax_restock_product() {
        check_ajax_referer('srwm_restock_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if (!$product_id || $quantity <= 0) {
            wp_die(__('Invalid data provided.', 'smart-restock-waitlist'));
        }
        
        $result = SRWM_Waitlist::restock_and_notify($product_id, $quantity);
        
        if ($result) {
            wp_send_json_success(__('Product restocked and customers notified!', 'smart-restock-waitlist'));
        } else {
            wp_send_json_error(__('Failed to restock product.', 'smart-restock-waitlist'));
        }
    }
}

// Initialize the plugin
function srwm_init() {
    return SmartRestockWaitlistManager::get_instance();
}

// Start the plugin
srwm_init();