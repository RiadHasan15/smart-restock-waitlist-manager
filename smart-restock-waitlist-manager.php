<?php
/**
 * Plugin Name: Smart Restock & Waitlist Manager
 * Plugin URI: https://example.com/smart-restock-waitlist-manager
 * Description: Advanced restock management and customer waitlist system for WooCommerce. Includes supplier notifications, one-click restock, and analytics. Upgrade to Pro for advanced automation features.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: smart-restock-waitlist
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 6.0
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
define('SRWM_PLUGIN_FILE', __FILE__);

// License management
class SRWM_License_Manager {
    
    private static $instance = null;
    private $license_key = '';
    private $license_status = 'inactive';
    private $pro_features_enabled = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->license_key = get_option('srwm_license_key', '');
        $this->license_status = get_option('srwm_license_status', 'inactive');
        $this->pro_features_enabled = ($this->license_status === 'valid');
        
        add_action('admin_init', array($this, 'init_license_page'));
        add_action('wp_ajax_srwm_activate_license', array($this, 'activate_license'));
        add_action('wp_ajax_srwm_deactivate_license', array($this, 'deactivate_license'));
    }
    
    public function is_pro_active() {
        return $this->pro_features_enabled;
    }
    
    public function get_license_key() {
        return $this->license_key;
    }
    
    public function get_license_status() {
        return $this->license_status;
    }
    
    public function init_license_page() {
        add_submenu_page(
            'smart-restock-waitlist',
            __('License', 'smart-restock-waitlist'),
            __('License', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-license',
            array($this, 'render_license_page')
        );
    }
    
    public function render_license_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Restock & Waitlist Manager - License', 'smart-restock-waitlist'); ?></h1>
            
            <div class="srwm-license-container">
                <div class="srwm-license-info">
                    <h2><?php _e('License Status', 'smart-restock-waitlist'); ?></h2>
                    
                    <div class="srwm-license-status">
                        <strong><?php _e('Status:', 'smart-restock-waitlist'); ?></strong>
                        <span class="srwm-status-<?php echo $this->license_status; ?>">
                            <?php echo ucfirst($this->license_status); ?>
                        </span>
                    </div>
                    
                    <?php if ($this->license_status === 'valid'): ?>
                        <div class="srwm-license-features">
                            <h3><?php _e('Pro Features Enabled:', 'smart-restock-waitlist'); ?></h3>
                            <ul>
                                <li><?php _e('One-Click Supplier Restock', 'smart-restock-waitlist'); ?></li>
                                <li><?php _e('Multi-Channel Notifications (WhatsApp/SMS)', 'smart-restock-waitlist'); ?></li>
                                <li><?php _e('Automatic Purchase Order Generation', 'smart-restock-waitlist'); ?></li>
                                <li><?php _e('Supplier CSV Upload', 'smart-restock-waitlist'); ?></li>
                                <li><?php _e('Advanced Analytics', 'smart-restock-waitlist'); ?></li>
                                <li><?php _e('Customizable Templates', 'smart-restock-waitlist'); ?></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="srwm-license-upgrade">
                            <h3><?php _e('Upgrade to Pro', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Unlock advanced features for better supplier management and automation.', 'smart-restock-waitlist'); ?></p>
                            <a href="https://example.com/pro" class="button button-primary" target="_blank">
                                <?php _e('Get Pro License', 'smart-restock-waitlist'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="srwm-license-form">
                    <h2><?php _e('License Key', 'smart-restock-waitlist'); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('srwm_license_nonce', 'srwm_license_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="srwm_license_key"><?php _e('License Key:', 'smart-restock-waitlist'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="srwm_license_key" name="srwm_license_key" 
                                           value="<?php echo esc_attr($this->license_key); ?>" class="regular-text">
                                    <p class="description">
                                        <?php _e('Enter your license key to activate Pro features.', 'smart-restock-waitlist'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <?php if ($this->license_status === 'valid'): ?>
                                <button type="button" class="button button-secondary" id="srwm-deactivate-license">
                                    <?php _e('Deactivate License', 'smart-restock-waitlist'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="button button-primary" id="srwm-activate-license">
                                    <?php _e('Activate License', 'smart-restock-waitlist'); ?>
                                </button>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#srwm-activate-license').on('click', function() {
                var licenseKey = $('#srwm_license_key').val();
                if (!licenseKey) {
                    alert('<?php _e('Please enter a license key.', 'smart-restock-waitlist'); ?>');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'srwm_activate_license',
                        license_key: licenseKey,
                        nonce: '<?php echo wp_create_nonce('srwm_license_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || '<?php _e('License activation failed.', 'smart-restock-waitlist'); ?>');
                        }
                    }
                });
            });
            
            $('#srwm-deactivate-license').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to deactivate the license?', 'smart-restock-waitlist'); ?>')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'srwm_deactivate_license',
                            nonce: '<?php echo wp_create_nonce('srwm_license_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data || '<?php _e('License deactivation failed.', 'smart-restock-waitlist'); ?>');
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    public function activate_license() {
        check_ajax_referer('srwm_license_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        if (empty($license_key)) {
            wp_send_json_error(__('License key is required.', 'smart-restock-waitlist'));
        }
        
        // Simulate license validation (replace with actual API call)
        $is_valid = $this->validate_license_key($license_key);
        
        if ($is_valid) {
            update_option('srwm_license_key', $license_key);
            update_option('srwm_license_status', 'valid');
            wp_send_json_success(__('License activated successfully!', 'smart-restock-waitlist'));
        } else {
            wp_send_json_error(__('Invalid license key. Please check and try again.', 'smart-restock-waitlist'));
        }
    }
    
    public function deactivate_license() {
        check_ajax_referer('srwm_license_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        delete_option('srwm_license_key');
        update_option('srwm_license_status', 'inactive');
        
        wp_send_json_success(__('License deactivated successfully!', 'smart-restock-waitlist'));
    }
    
    private function validate_license_key($license_key) {
        // This is a placeholder for actual license validation
        // In production, you would make an API call to your license server
        return (strlen($license_key) >= 10 && strpos($license_key, 'PRO') !== false);
    }
}

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
    private $license_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->license_manager = SRWM_License_Manager::get_instance();
        
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
        // Core classes (Free features)
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-waitlist.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-supplier.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-admin.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-email.php';
        require_once SRWM_PLUGIN_PATH . 'includes/class-srwm-analytics.php';
        
        // Pro features (only load if license is valid)
        if ($this->license_manager->is_pro_active()) {
            require_once SRWM_PLUGIN_PATH . 'includes/pro/class-srwm-pro-restock.php';
            require_once SRWM_PLUGIN_PATH . 'includes/pro/class-srwm-pro-purchase-order.php';
            require_once SRWM_PLUGIN_PATH . 'includes/pro/class-srwm-pro-csv-upload.php';
        }
    }
    
    private function init_hooks() {
        // Initialize core functionality (Free features)
        SRWM_Waitlist::get_instance();
        SRWM_Supplier::get_instance();
        SRWM_Admin::get_instance();
        SRWM_Email::get_instance();
        SRWM_Analytics::get_instance();
        
        // Initialize Pro features (only if license is valid)
        if ($this->license_manager->is_pro_active()) {
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
        
        // Pro tables (only create if Pro is active)
        if ($this->license_manager->is_pro_active()) {
            // Restock tokens table
            $table_tokens = $wpdb->prefix . 'srwm_restock_tokens';
            $sql_tokens = "CREATE TABLE $table_tokens (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                token varchar(255) NOT NULL,
                product_id bigint(20) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                expires_at datetime NOT NULL,
                used tinyint(1) DEFAULT 0,
                used_at datetime,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY token (token),
                KEY product_id (product_id)
            ) $charset_collate;";
            
            // CSV tokens table
            $table_csv_tokens = $wpdb->prefix . 'srwm_csv_tokens';
            $sql_csv_tokens = "CREATE TABLE $table_csv_tokens (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                token varchar(255) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                expires_at datetime NOT NULL,
                used tinyint(1) DEFAULT 0,
                used_at datetime,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY token (token)
            ) $charset_collate;";
            
            // Purchase orders table
            $table_po = $wpdb->prefix . 'srwm_purchase_orders';
            $sql_po = "CREATE TABLE $table_po (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                po_number varchar(255) NOT NULL,
                product_id bigint(20) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                quantity int(11) NOT NULL,
                total_amount decimal(10,2) NOT NULL,
                status varchar(50) DEFAULT 'sent',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY po_number (po_number),
                KEY product_id (product_id)
            ) $charset_collate;";
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_waitlist);
        dbDelta($sql_supplier);
        dbDelta($sql_logs);
        
        if ($this->license_manager->is_pro_active()) {
            dbDelta($sql_tokens);
            dbDelta($sql_csv_tokens);
            dbDelta($sql_po);
        }
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