<?php
/**
 * Customer Waitlist Class
 * 
 * Handles customer waitlist functionality, form display, and restock notifications.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Waitlist {
    
    private static $instance = null;
    private $license_manager;
    
    public static function get_instance($license_manager = null) {
        if (null === self::$instance) {
            self::$instance = new self($license_manager);
        }
        return self::$instance;
    }
    
    private function __construct($license_manager = null) {
        $this->license_manager = $license_manager;
        
        add_action('woocommerce_single_product_summary', array($this, 'display_waitlist_form'), 25);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_product_set_stock_status', array($this, 'check_restock_notification'), 10, 3);
    }
    
    /**
     * Display waitlist form on product page
     */
    public function display_waitlist_form() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Only show on out-of-stock products
        if ($product->is_in_stock()) {
            return;
        }
        
        // Check if waitlist is enabled
        if (get_option('srwm_waitlist_enabled') !== 'yes') {
            return;
        }
        
        $waitlist_count = self::get_waitlist_count($product->get_id());
        $is_on_waitlist = self::is_customer_on_waitlist($product->get_id(), $this->get_current_customer_email());
        
        ?>
        <div class="srwm-waitlist-container">
            <h3><?php _e('Join the Waitlist', 'smart-restock-waitlist'); ?></h3>
            
            <?php if ($is_on_waitlist): ?>
                <div class="srwm-waitlist-message success">
                    <?php _e('You are already on the waitlist for this product!', 'smart-restock-waitlist'); ?>
                </div>
            <?php else: ?>
                <form class="srwm-waitlist-form" method="post">
                    <?php wp_nonce_field('srwm_waitlist_nonce', 'srwm_waitlist_nonce'); ?>
                    <input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">
                    
                    <p>
                        <label for="srwm_customer_name"><?php _e('Name:', 'smart-restock-waitlist'); ?></label>
                        <input type="text" id="srwm_customer_name" name="name" 
                               value="<?php echo esc_attr($this->get_current_customer_name()); ?>" required>
                    </p>
                    
                    <p>
                        <label for="srwm_customer_email"><?php _e('Email:', 'smart-restock-waitlist'); ?></label>
                        <input type="email" id="srwm_customer_email" name="email" 
                               value="<?php echo esc_attr($this->get_current_customer_email()); ?>" required>
                    </p>
                    
                    <button type="submit" class="srwm-waitlist-submit button">
                        <?php _e('Join Waitlist', 'smart-restock-waitlist'); ?>
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if ($waitlist_count > 0): ?>
                <div class="srwm-waitlist-count">
                    <small>
                        <?php printf(
                            _n('%d person is waiting for this product', '%d people are waiting for this product', $waitlist_count, 'smart-restock-waitlist'),
                            $waitlist_count
                        ); ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <div class="srwm-waitlist-message" style="display: none;"></div>
        </div>
        <?php
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_script(
            'srwm-waitlist',
            SRWM_PLUGIN_URL . 'assets/js/waitlist.js',
            array('jquery'),
            SRWM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'srwm-waitlist',
            SRWM_PLUGIN_URL . 'assets/css/waitlist.css',
            array(),
            SRWM_VERSION
        );
        
        wp_localize_script('srwm-waitlist', 'srwm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srwm_waitlist_nonce'),
            'messages' => array(
                'success' => __('Successfully added to waitlist!', 'smart-restock-waitlist'),
                'error' => __('Failed to add to waitlist. Please try again.', 'smart-restock-waitlist'),
                'already_on_waitlist' => __('You are already on the waitlist for this product.', 'smart-restock-waitlist')
            )
        ));
    }
    
    /**
     * Add customer to waitlist
     */
    public static function add_customer($product_id, $email, $name = '') {
        global $wpdb;
        
        $product_id = intval($product_id);
        $email = sanitize_email($email);
        $name = sanitize_text_field($name);
        
        if (!$product_id || !$email) {
            return false;
        }
        
        // Check if customer is already on waitlist
        if (self::is_customer_on_waitlist($product_id, $email)) {
            return false;
        }
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $result = $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'customer_email' => $email,
                'customer_name' => $name,
                'date_added' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        if ($result) {
            do_action('srwm_customer_added_to_waitlist', $product_id, $email, $name);
            
            // Check if supplier notification is needed
            self::check_supplier_notification($product_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if customer is on waitlist
     */
    public static function is_customer_on_waitlist($product_id, $email) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE product_id = %d AND customer_email = %s",
            $product_id,
            $email
        ));
        
        return $count > 0;
    }
    
    /**
     * Get waitlist count for a product
     */
    public static function get_waitlist_count($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE product_id = %d",
            $product_id
        ));
    }
    
    /**
     * Get waitlist customers for a product
     */
    public static function get_waitlist_customers($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE product_id = %d ORDER BY date_added ASC",
            $product_id
        ));
    }
    
    /**
     * Restock product and notify waitlist customers
     */
    public static function restock_and_notify($product_id, $quantity = 10) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        // Update stock
        $current_stock = $product->get_stock_quantity();
        $new_stock = $current_stock + $quantity;
        
        $product->set_stock_quantity($new_stock);
        $product->save();
        
        // Log restock action
        self::log_restock_action($product_id, $quantity);
        
        // Notify waitlist customers
        $customers = self::get_waitlist_customers($product_id);
        
        if (!empty($customers)) {
            $email = SRWM_Email::get_instance($this->license_manager);
            
            foreach ($customers as $customer) {
                $email->send_restock_notification($customer, $product);
                
                // Mark as notified
                self::mark_customer_notified($customer->id);
            }
        }
        
        // Clear waitlist (optional - you might want to keep it for future restocks)
        // self::clear_waitlist($product_id);
        
        do_action('srwm_product_restocked', $product_id, $quantity, $customers);
        
        return true;
    }
    
    /**
     * Check restock notification when stock status changes
     */
    public function check_restock_notification($product_id, $status, $product) {
        if ($status !== 'instock') {
            return;
        }
        
        // Check if there are waitlist customers
        $waitlist_count = self::get_waitlist_count($product_id);
        
        if ($waitlist_count > 0) {
            // Notify waitlist customers
            $customers = self::get_waitlist_customers($product_id);
            $email = SRWM_Email::get_instance($this->license_manager);
            
            foreach ($customers as $customer) {
                $email->send_restock_notification($customer, $product);
                self::mark_customer_notified($customer->id);
            }
        }
    }
    
    /**
     * Check if supplier notification is needed
     */
    private static function check_supplier_notification($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        $current_stock = $product->get_stock_quantity();
        
        // Check if stock is at or below threshold
        $threshold = get_option('srwm_low_stock_threshold', 5);
        
        if ($current_stock <= $threshold) {
            $supplier = SRWM_Supplier::get_instance($this->license_manager);
            $supplier_data = $supplier->get_supplier_data($product_id);
            
            if (!empty($supplier_data['email'])) {
                $supplier->notify_supplier($product_id, $supplier_data, $current_stock);
            }
        }
    }
    
    /**
     * Log restock action
     */
    private static function log_restock_action($product_id, $quantity, $method = 'manual') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'method' => $method,
                'ip_address' => self::get_client_ip(),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Mark customer as notified
     */
    private static function mark_customer_notified($customer_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $wpdb->update(
            $table,
            array('notified' => 1),
            array('id' => $customer_id),
            array('%d'),
            array('%d')
        );
    }
    
    /**
     * Clear waitlist for a product
     */
    public static function clear_waitlist($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        return $wpdb->delete(
            $table,
            array('product_id' => $product_id),
            array('%d')
        );
    }
    
    /**
     * Get current customer email
     */
    private function get_current_customer_email() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            return $user->user_email;
        }
        
        return '';
    }
    
    /**
     * Get current customer name
     */
    private function get_current_customer_name() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            return $user->display_name;
        }
        
        return '';
    }
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * Get waitlist statistics
     */
    public static function get_waitlist_stats() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $stats = array(
            'total_waitlists' => $wpdb->get_var("SELECT COUNT(DISTINCT product_id) FROM $table"),
            'total_customers' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'notified_customers' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE notified = 1"),
            'pending_customers' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE notified = 0")
        );
        
        return $stats;
    }
}