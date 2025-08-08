<?php
/**
 * Supplier Management Class
 * 
 * Handles supplier information, notifications, and Pro features.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Supplier {
    
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
        
        add_action('add_meta_boxes', array($this, 'add_supplier_meta_box'));
        add_action('save_post', array($this, 'save_supplier_data'));
        add_action('woocommerce_product_set_stock_status', array($this, 'check_stock_levels'), 10, 3);
    }
    
    /**
     * Add supplier meta box to product edit page
     */
    public function add_supplier_meta_box() {
        add_meta_box(
            'srwm_supplier_meta_box',
            __('Supplier Information', 'smart-restock-waitlist'),
            array($this, 'render_supplier_meta_box'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render supplier meta box
     */
    public function render_supplier_meta_box($post) {
        wp_nonce_field('srwm_supplier_nonce', 'srwm_supplier_nonce');
        
        $supplier_data = $this->get_supplier_data($post->ID);
        ?>
        <div class="srwm-supplier-meta-box">
            <p>
                <label for="srwm_supplier_email"><?php _e('Supplier Email:', 'smart-restock-waitlist'); ?></label>
                <input type="email" id="srwm_supplier_email" name="srwm_supplier_email"
                       value="<?php echo esc_attr($supplier_data['email'] ?? ''); ?>" class="widefat">
            </p>
            
            <p>
                <label for="srwm_supplier_name"><?php _e('Supplier Name:', 'smart-restock-waitlist'); ?></label>
                <input type="text" id="srwm_supplier_name" name="srwm_supplier_name"
                       value="<?php echo esc_attr($supplier_data['name'] ?? ''); ?>" class="widefat">
            </p>
            
            <p>
                <label for="srwm_supplier_threshold"><?php _e('Low Stock Threshold:', 'smart-restock-waitlist'); ?></label>
                <input type="number" id="srwm_supplier_threshold" name="srwm_supplier_threshold"
                       value="<?php echo esc_attr($supplier_data['threshold'] ?? 5); ?>" min="0" class="widefat">
                <small><?php _e('Leave empty to use global threshold', 'smart-restock-waitlist'); ?></small>
            </p>
            
            <?php if ($this->license_manager->is_pro_active()): ?>
            <p>
                <label><?php _e('Notification Channels:', 'smart-restock-waitlist'); ?></label><br>
                <label>
                    <input type="checkbox" name="srwm_notify_email" value="1"
                           <?php checked(isset($supplier_data['channels']['email'])); ?>>
                    <?php _e('Email', 'smart-restock-waitlist'); ?>
                </label><br>
                <label>
                    <input type="checkbox" name="srwm_notify_whatsapp" value="1"
                           <?php checked(isset($supplier_data['channels']['whatsapp'])); ?>>
                    <?php _e('WhatsApp', 'smart-restock-waitlist'); ?>
                </label><br>
                <label>
                    <input type="checkbox" name="srwm_notify_sms" value="1"
                           <?php checked(isset($supplier_data['channels']['sms'])); ?>>
                    <?php _e('SMS', 'smart-restock-waitlist'); ?>
                </label>
            </p>
            <?php else: ?>
            <p>
                <label><?php _e('Notification Channels:', 'smart-restock-waitlist'); ?></label><br>
                <label>
                    <input type="checkbox" name="srwm_notify_email" value="1"
                           <?php checked(isset($supplier_data['channels']['email'])); ?>>
                    <?php _e('Email', 'smart-restock-waitlist'); ?>
                </label><br>
                <small><?php _e('Upgrade to Pro for WhatsApp and SMS notifications', 'smart-restock-waitlist'); ?></small>
            </p>
            <?php endif; ?>
            
            <?php if ($this->license_manager->is_pro_active()): ?>
            <p>
                <label for="srwm_auto_generate_po"><?php _e('Auto-generate PO:', 'smart-restock-waitlist'); ?></label>
                <input type="checkbox" id="srwm_auto_generate_po" name="srwm_auto_generate_po" value="1"
                       <?php checked(isset($supplier_data['auto_generate_po'])); ?>>
                <small><?php _e('Automatically generate purchase order when stock is low', 'smart-restock-waitlist'); ?></small>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save supplier data
     */
    public function save_supplier_data($post_id) {
        if (!isset($_POST['srwm_supplier_nonce']) || !wp_verify_nonce($_POST['srwm_supplier_nonce'], 'srwm_supplier_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        $supplier_email = sanitize_email($_POST['srwm_supplier_email'] ?? '');
        $supplier_name = sanitize_text_field($_POST['srwm_supplier_name'] ?? '');
        $threshold = intval($_POST['srwm_supplier_threshold'] ?? 0);
        
        $channels = array();
        if (isset($_POST['srwm_notify_email'])) {
            $channels['email'] = true;
        }
        
        if ($this->license_manager->is_pro_active()) {
            if (isset($_POST['srwm_notify_whatsapp'])) {
                $channels['whatsapp'] = true;
            }
            if (isset($_POST['srwm_notify_sms'])) {
                $channels['sms'] = true;
            }
        }
        
        $auto_generate_po = false;
        if ($this->license_manager->is_pro_active() && isset($_POST['srwm_auto_generate_po'])) {
            $auto_generate_po = true;
        }
        
        $this->save_supplier_to_database($post_id, $supplier_email, $supplier_name, $threshold, $channels, $auto_generate_po);
    }
    
    /**
     * Save supplier data to database
     */
    private function save_supplier_to_database($product_id, $email, $name, $threshold, $channels, $auto_generate_po) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        if (empty($email)) {
            // Remove supplier if email is empty
            $wpdb->delete($table, array('product_id' => $product_id), array('%d'));
            return;
        }
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE product_id = %d",
            $product_id
        ));
        
        $data = array(
            'product_id' => $product_id,
            'supplier_email' => $email,
            'supplier_name' => $name,
            'channels' => maybe_serialize($channels),
            'threshold' => $threshold > 0 ? $threshold : null
        );
        
        if ($this->license_manager->is_pro_active()) {
            $data['auto_generate_po'] = $auto_generate_po ? 1 : 0;
        }
        
        if ($existing) {
            $wpdb->update($table, $data, array('product_id' => $product_id));
        } else {
            $wpdb->insert($table, $data);
        }
    }
    
    /**
     * Get supplier data for a product
     */
    public function get_supplier_data($product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE product_id = %d",
            $product_id
        ));
        
        if (!$result) {
            return array();
        }
        
        $data = array(
            'email' => $result->supplier_email,
            'name' => $result->supplier_name,
            'threshold' => $result->threshold,
            'channels' => maybe_unserialize($result->channels) ?: array('email' => true)
        );
        
        if ($this->license_manager->is_pro_active() && isset($result->auto_generate_po)) {
            $data['auto_generate_po'] = (bool) $result->auto_generate_po;
        }
        
        return $data;
    }
    
    /**
     * Get products with suppliers
     */
    public function get_products_with_suppliers() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        return $wpdb->get_results(
            "SELECT s.*, p.post_title as product_name, wc.stock_quantity as current_stock
             FROM $table s
             JOIN {$wpdb->posts} p ON s.product_id = p.ID
             LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup wc ON s.product_id = wc.product_id
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             ORDER BY p.post_title",
            ARRAY_A
        );
    }
    
    /**
     * Check stock levels and notify suppliers if needed
     */
    public function check_stock_levels($product_id, $status, $product) {
        if ($status !== 'outofstock' && $status !== 'onbackorder') {
            return;
        }
        
        $supplier_data = $this->get_supplier_data($product_id);
        if (empty($supplier_data['email'])) {
            return;
        }
        
        $current_stock = $product->get_stock_quantity();
        $threshold = $supplier_data['threshold'] ?: get_option('srwm_low_stock_threshold', 5);
        
        if ($current_stock > $threshold) {
            return;
        }
        
        $this->notify_supplier($product_id, $supplier_data, $current_stock);
    }
    
    /**
     * Notify supplier about low stock
     */
    public function notify_supplier($product_id, $supplier_data, $current_stock) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        $waitlist_count = SRWM_Waitlist::get_waitlist_count($product_id);
        
        // Email notification (always available)
        if (isset($supplier_data['channels']['email'])) {
            $this->send_email_notification($product, $supplier_data, $current_stock, $waitlist_count);
        }
        
        // Pro features
        if ($this->license_manager && $this->license_manager->is_pro_active()) {
            // Auto-generate restock link for PRO users
            $this->auto_generate_restock_link($product, $supplier_data);
            
            // WhatsApp notification
            if (isset($supplier_data['channels']['whatsapp'])) {
                $this->send_whatsapp_notification($product, $supplier_data, $current_stock, $waitlist_count);
            }
            
            // SMS notification
            if (isset($supplier_data['channels']['sms'])) {
                $this->send_sms_notification($product, $supplier_data, $current_stock, $waitlist_count);
            }
            
            // Auto-generate purchase order
            if (isset($supplier_data['auto_generate_po']) && $supplier_data['auto_generate_po']) {
                $this->generate_purchase_order($product_id, $supplier_data);
            }
        }
    }
    
    /**
     * Send email notification to supplier
     */
    private function send_email_notification($product, $supplier_data, $current_stock, $waitlist_count) {
        $email = SRWM_Email::get_instance($this->license_manager);
        $email->send_supplier_notification($product, $supplier_data, $current_stock, $waitlist_count);
    }
    
    /**
     * Send WhatsApp notification (Pro feature)
     */
    private function send_whatsapp_notification($product, $supplier_data, $current_stock, $waitlist_count) {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return;
        }
        
        // This would integrate with WhatsApp Business API
        // For now, we'll just log the action silently
    }
    
    /**
     * Send SMS notification (Pro feature)
     */
    private function send_sms_notification($product, $supplier_data, $current_stock, $waitlist_count) {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return;
        }
        
        // This would integrate with Twilio or similar SMS service
        // For now, we'll just log the action silently
    }
    
    /**
     * Auto-generate restock link (Pro feature)
     */
    private function auto_generate_restock_link($product, $supplier_data) {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return;
        }
        
        // Check if auto-restock links are enabled
        $auto_restock_enabled = get_option('srwm_auto_generate_restock_links', 1);
        if (!$auto_restock_enabled) {
            return;
        }
        
        // Generate restock link automatically
        if (class_exists('SRWM_Pro_Restock')) {
            $restock = SRWM_Pro_Restock::get_instance();
            $restock_link = $restock->generate_restock_token($product->get_id(), $supplier_data['email']);
            
            if ($restock_link) {
                // Log the auto-generation
                $this->log_auto_restock_link_generated($product->get_id(), $supplier_data['email'], $restock_link);
            }
        }
    }
    
    /**
     * Log auto-generated restock link (Pro feature)
     */
    private function log_auto_restock_link_generated($product_id, $supplier_email, $restock_link) {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : 'Unknown Product';
        $sku = $product ? $product->get_sku() : '';
        $waitlist_count = SRWM_Waitlist::get_waitlist_count($product_id);
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'product_name' => $product_name,
                'sku' => $sku,
                'quantity' => 0, // Link generated, not restocked yet
                'method' => 'restock_link_generated',
                'supplier_email' => $supplier_email,
                'ip_address' => $this->get_client_ip(),
                'waitlist_count' => $waitlist_count,
                'action_details' => 'Auto-generated restock link for supplier notification',
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
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
     * Generate purchase order (Pro feature)
     */
    private function generate_purchase_order($product_id, $supplier_data) {
        if (!$this->license_manager || !$this->license_manager->is_pro_active()) {
            return;
        }
        
        // This would use the Pro purchase order class
        if (class_exists('SRWM_Pro_Purchase_Order')) {
            $po_generator = SRWM_Pro_Purchase_Order::get_instance();
            $po_generator->generate_purchase_order($product_id, $supplier_data);
        }
    }
    
    /**
     * Get supplier by email
     */
    public function get_supplier_by_email($email) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE supplier_email = %s",
            $email
        ));
    }
    
    /**
     * Get all suppliers
     */
    public function get_all_suppliers() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        return $wpdb->get_results(
            "SELECT DISTINCT supplier_email, supplier_name FROM $table ORDER BY supplier_name"
        );
    }
}