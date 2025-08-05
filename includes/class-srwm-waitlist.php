<?php
/**
 * Waitlist Management Class
 * 
 * Handles customer waitlist functionality including adding customers,
 * managing waitlist data, and sending notifications on restock.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Waitlist {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'display_waitlist_form'), 25);
        add_action('woocommerce_after_shop_loop_item', array($this, 'display_waitlist_form_loop'), 15);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_product_set_stock_status', array($this, 'check_stock_status_change'), 10, 3);
    }
    
    /**
     * Display waitlist form on single product page
     */
    public function display_waitlist_form() {
        global $product;
        
        if (!$product || !$this->should_show_waitlist($product)) {
            return;
        }
        
        $this->render_waitlist_form($product);
    }
    
    /**
     * Display waitlist form in product loop
     */
    public function display_waitlist_form_loop() {
        global $product;
        
        if (!$product || !$this->should_show_waitlist($product)) {
            return;
        }
        
        $this->render_waitlist_form($product, 'loop');
    }
    
    /**
     * Check if waitlist should be shown for a product
     */
    private function should_show_waitlist($product) {
        if (!$product || !is_object($product)) {
            return false;
        }
        
        // Check if waitlist is enabled
        if (get_option('srwm_waitlist_enabled') !== 'yes') {
            return false;
        }
        
        // Check if product is out of stock
        if ($product->is_in_stock()) {
            return false;
        }
        
        // Check if product type is supported
        $supported_types = array('simple', 'variable');
        if (!in_array($product->get_type(), $supported_types)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Render waitlist form
     */
    private function render_waitlist_form($product, $context = 'single') {
        $product_id = $product->get_id();
        $waitlist_count = $this->get_waitlist_count($product_id);
        $is_on_waitlist = $this->is_customer_on_waitlist($product_id);
        
        ?>
        <div class="srwm-waitlist-form" data-product-id="<?php echo esc_attr($product_id); ?>">
            <?php if ($is_on_waitlist): ?>
                <div class="srwm-waitlist-message success">
                    <p><?php _e('You are on the waitlist for this product!', 'smart-restock-waitlist'); ?></p>
                    <?php if ($waitlist_count > 1): ?>
                        <small><?php printf(__('There are %d other customers waiting.', 'smart-restock-waitlist'), $waitlist_count - 1); ?></small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="srwm-waitlist-form-container">
                    <h4><?php _e('Join the Waitlist', 'smart-restock-waitlist'); ?></h4>
                    <p><?php _e('Get notified when this product is back in stock!', 'smart-restock-waitlist'); ?></p>
                    
                    <form class="srwm-waitlist-form" method="post">
                        <?php wp_nonce_field('srwm_waitlist_nonce', 'srwm_nonce'); ?>
                        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                        
                        <div class="form-row">
                            <label for="srwm_name_<?php echo $product_id; ?>"><?php _e('Name:', 'smart-restock-waitlist'); ?></label>
                            <input type="text" id="srwm_name_<?php echo $product_id; ?>" name="name" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="srwm_email_<?php echo $product_id; ?>"><?php _e('Email:', 'smart-restock-waitlist'); ?></label>
                            <input type="email" id="srwm_email_<?php echo $product_id; ?>" name="email" required>
                        </div>
                        
                        <button type="submit" class="button srwm-waitlist-submit">
                            <?php _e('Join Waitlist', 'smart-restock-waitlist'); ?>
                        </button>
                    </form>
                    
                    <?php if ($waitlist_count > 0): ?>
                        <small><?php printf(__('%d customers are waiting for this product.', 'smart-restock-waitlist'), $waitlist_count); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product() && !is_shop() && !is_product_category()) {
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
                'error' => __('Failed to add to waitlist.', 'smart-restock-waitlist'),
                'already_on_list' => __('You are already on the waitlist for this product.', 'smart-restock-waitlist')
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
            // Trigger action for other plugins
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
    public static function is_customer_on_waitlist($product_id, $email = null) {
        global $wpdb;
        
        if (!$email) {
            $email = wp_get_current_user()->user_email;
        }
        
        if (!$email) {
            return false;
        }
        
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
    public static function restock_and_notify($product_id, $quantity = 0) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        // Update stock
        if ($quantity > 0) {
            $current_stock = $product->get_stock_quantity();
            $product->set_stock_quantity($current_stock + $quantity);
        }
        
        $product->set_stock_status('instock');
        $product->save();
        
        // Get waitlist customers
        $customers = self::get_waitlist_customers($product_id);
        
        if (!empty($customers)) {
            // Send notifications
            foreach ($customers as $customer) {
                self::send_restock_notification($customer, $product);
            }
            
            // Mark customers as notified
            self::mark_customers_notified($product_id);
            
            // Log restock action
            self::log_restock_action($product_id, $quantity);
        }
        
        return true;
    }
    
    /**
     * Send restock notification to customer
     */
    private static function send_restock_notification($customer, $product) {
        $email = new SRWM_Email();
        $email->send_restock_notification($customer, $product);
    }
    
    /**
     * Mark customers as notified
     */
    private static function mark_customers_notified($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $wpdb->update(
            $table,
            array('notified' => 1),
            array('product_id' => $product_id),
            array('%d'),
            array('%d')
        );
    }
    
    /**
     * Log restock action
     */
    private static function log_restock_action($product_id, $quantity) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'method' => 'manual',
                'ip_address' => self::get_client_ip(),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Check supplier notification on stock change
     */
    public function check_stock_status_change($product_id, $status, $product) {
        if ($status === 'outofstock') {
            self::check_supplier_notification($product_id);
        }
    }
    
    /**
     * Check if supplier notification is needed
     */
    private static function check_supplier_notification($product_id) {
        $supplier = new SRWM_Supplier();
        $supplier->check_and_send_notification($product_id);
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
}