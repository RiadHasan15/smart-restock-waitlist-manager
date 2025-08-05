<?php
/**
 * Supplier Management Class
 * 
 * Handles supplier notifications, settings, and multi-channel communication.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Supplier {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_supplier_meta_box'));
        add_action('save_post', array($this, 'save_supplier_data'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_supplier_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_supplier_fields'));
    }
    
    /**
     * Add supplier meta box to product edit page
     */
    public function add_supplier_meta_box() {
        add_meta_box(
            'srwm-supplier-info',
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
        $product_id = $post->ID;
        $supplier_data = $this->get_supplier_data($product_id);
        
        wp_nonce_field('srwm_supplier_nonce', 'srwm_supplier_nonce');
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
            </p>
            
            <p>
                <label><?php _e('Notification Channels:', 'smart-restock-waitlist'); ?></label><br>
                <label>
                    <input type="checkbox" name="srwm_notify_email" value="1" 
                           <?php checked(isset($supplier_data['channels']['email'])); ?>>
                    <?php _e('Email', 'smart-restock-waitlist'); ?>
                </label><br>
                
                <?php if ($this->is_pro_active()): ?>
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
                <?php else: ?>
                <small><?php _e('Upgrade to Pro for WhatsApp and SMS notifications', 'smart-restock-waitlist'); ?></small>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save supplier data from meta box
     */
    public function save_supplier_data($post_id) {
        if (!isset($_POST['srwm_supplier_nonce']) || 
            !wp_verify_nonce($_POST['srwm_supplier_nonce'], 'srwm_supplier_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $supplier_email = sanitize_email($_POST['srwm_supplier_email'] ?? '');
        $supplier_name = sanitize_text_field($_POST['srwm_supplier_name'] ?? '');
        $threshold = intval($_POST['srwm_supplier_threshold'] ?? 5);
        
        $channels = array();
        if (isset($_POST['srwm_notify_email'])) {
            $channels['email'] = true;
        }
        if ($this->is_pro_active()) {
            if (isset($_POST['srwm_notify_whatsapp'])) {
                $channels['whatsapp'] = true;
            }
            if (isset($_POST['srwm_notify_sms'])) {
                $channels['sms'] = true;
            }
        }
        
        $this->save_supplier_data_to_db($post_id, $supplier_email, $supplier_name, $threshold, $channels);
    }
    
    /**
     * Add supplier fields to WooCommerce product data
     */
    public function add_supplier_fields() {
        global $post;
        
        $product_id = $post->ID;
        $supplier_data = $this->get_supplier_data($product_id);
        
        echo '<div class="options_group">';
        
        woocommerce_wp_text_input(array(
            'id' => 'srwm_supplier_email',
            'label' => __('Supplier Email', 'smart-restock-waitlist'),
            'desc_tip' => true,
            'description' => __('Email address for supplier notifications', 'smart-restock-waitlist'),
            'value' => $supplier_data['email'] ?? ''
        ));
        
        woocommerce_wp_text_input(array(
            'id' => 'srwm_supplier_name',
            'label' => __('Supplier Name', 'smart-restock-waitlist'),
            'desc_tip' => true,
            'description' => __('Name of the supplier', 'smart-restock-waitlist'),
            'value' => $supplier_data['name'] ?? ''
        ));
        
        woocommerce_wp_text_input(array(
            'id' => 'srwm_supplier_threshold',
            'label' => __('Low Stock Threshold', 'smart-restock-waitlist'),
            'type' => 'number',
            'desc_tip' => true,
            'description' => __('Stock level at which to notify supplier', 'smart-restock-waitlist'),
            'value' => $supplier_data['threshold'] ?? 5,
            'custom_attributes' => array(
                'min' => '0'
            )
        ));
        
        echo '</div>';
    }
    
    /**
     * Save supplier fields from WooCommerce product data
     */
    public function save_supplier_fields($post_id) {
        $supplier_email = sanitize_email($_POST['srwm_supplier_email'] ?? '');
        $supplier_name = sanitize_text_field($_POST['srwm_supplier_name'] ?? '');
        $threshold = intval($_POST['srwm_supplier_threshold'] ?? 5);
        
        $this->save_supplier_data_to_db($post_id, $supplier_email, $supplier_name, $threshold);
    }
    
    /**
     * Save supplier data to database
     */
    private function save_supplier_data_to_db($product_id, $email, $name, $threshold, $channels = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        // Check if supplier data exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE product_id = %d",
            $product_id
        ));
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table,
                array(
                    'supplier_email' => $email,
                    'supplier_name' => $name,
                    'threshold' => $threshold,
                    'notification_channels' => maybe_serialize($channels)
                ),
                array('product_id' => $product_id),
                array('%s', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $table,
                array(
                    'product_id' => $product_id,
                    'supplier_email' => $email,
                    'supplier_name' => $name,
                    'threshold' => $threshold,
                    'notification_channels' => maybe_serialize($channels)
                ),
                array('%d', '%s', '%s', '%d', '%s')
            );
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
        ), ARRAY_A);
        
        if ($result) {
            $result['channels'] = maybe_unserialize($result['notification_channels']);
            return $result;
        }
        
        return array();
    }
    
    /**
     * Check and send supplier notification
     */
    public function check_and_send_notification($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        $supplier_data = $this->get_supplier_data($product_id);
        
        if (empty($supplier_data) || empty($supplier_data['email'])) {
            return false;
        }
        
        $current_stock = $product->get_stock_quantity();
        $threshold = $supplier_data['threshold'];
        
        // Check if stock is at or below threshold
        if ($current_stock > $threshold) {
            return false;
        }
        
        // Send notification
        return $this->send_supplier_notification($product, $supplier_data);
    }
    
    /**
     * Send supplier notification
     */
    public function send_supplier_notification($product, $supplier_data) {
        $email = new SRWM_Email();
        
        // Send email notification
        if (isset($supplier_data['channels']['email'])) {
            $email->send_supplier_notification($product, $supplier_data);
        }
        
        // Send WhatsApp notification (Pro feature)
        if ($this->is_pro_active() && isset($supplier_data['channels']['whatsapp'])) {
            $this->send_whatsapp_notification($product, $supplier_data);
        }
        
        // Send SMS notification (Pro feature)
        if ($this->is_pro_active() && isset($supplier_data['channels']['sms'])) {
            $this->send_sms_notification($product, $supplier_data);
        }
        
        return true;
    }
    
    /**
     * Send WhatsApp notification (Pro feature)
     */
    private function send_whatsapp_notification($product, $supplier_data) {
        // This would integrate with WhatsApp Business API
        // For now, we'll just log the action
        error_log('WhatsApp notification would be sent for product: ' . $product->get_id());
    }
    
    /**
     * Send SMS notification (Pro feature)
     */
    private function send_sms_notification($product, $supplier_data) {
        // This would integrate with Twilio or similar SMS service
        // For now, we'll just log the action
        error_log('SMS notification would be sent for product: ' . $product->get_id());
    }
    
    /**
     * Get all products with supplier data
     */
    public function get_products_with_suppliers() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_suppliers';
        
        return $wpdb->get_results(
            "SELECT s.*, p.post_title as product_name 
             FROM $table s 
             JOIN {$wpdb->posts} p ON s.product_id = p.ID 
             WHERE p.post_type = 'product' AND p.post_status = 'publish'
             ORDER BY p.post_title ASC"
        );
    }
    
    /**
     * Check if Pro version is active
     */
    private function is_pro_active() {
        return function_exists('srwm_pro_init') || defined('SRWM_PRO_VERSION');
    }
}