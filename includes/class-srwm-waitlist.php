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
        
        // Check if waitlist is enabled
        if (get_option('srwm_waitlist_enabled') !== 'yes') {
            return;
        }
        
        // Get waitlist display threshold
        $waitlist_threshold = get_option('srwm_waitlist_display_threshold', 5);
        
        // Get current stock quantity
        $current_stock = $product->get_stock_quantity();
        
        // Show waitlist if:
        // 1. Product is out of stock, OR
        // 2. Stock is at or below the threshold
        if ($product->is_in_stock() && $current_stock > $waitlist_threshold) {
            return;
        }
        
        $waitlist_count = self::get_waitlist_count($product->get_id());
        $is_on_waitlist = self::is_customer_on_waitlist($product->get_id(), $this->get_current_customer_email());
        $customer_position = $this->get_customer_queue_position($product->get_id(), $this->get_current_customer_email());
        
        ?>
        <div class="srwm-waitlist-container">
            <?php 
            $hide_header_after_submit = get_option('srwm_hide_header_after_submit', '1');
            if (!$is_on_waitlist || !$hide_header_after_submit): 
            ?>
            <div class="srwm-waitlist-header">
                <h3><?php _e('Join the Waitlist', 'smart-restock-waitlist'); ?></h3>
                <div class="srwm-waitlist-subtitle">
                    <?php _e('Be the first to know when this product is back in stock!', 'smart-restock-waitlist'); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($is_on_waitlist): ?>
                <!-- User is on waitlist - Show success status -->
                <div class="srwm-waitlist-status">
                    <div class="srwm-status-card active">
                        <div class="srwm-status-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="srwm-status-content">
                            <h4><?php _e('You\'re on the waitlist!', 'smart-restock-waitlist'); ?></h4>
                            <p><?php _e('We\'ll notify you as soon as this product is back in stock.', 'smart-restock-waitlist'); ?></p>
                            
                            <?php if ($customer_position > 0): ?>
                            <div class="srwm-queue-position">
                                <div class="srwm-queue-info">
                                    <span class="srwm-queue-label"><?php _e('Your position:', 'smart-restock-waitlist'); ?></span>
                                    <span class="srwm-queue-number">#<?php echo $customer_position; ?></span>
                                </div>
                                <div class="srwm-queue-progress">
                                    <div class="srwm-progress-bar">
                                        <div class="srwm-progress-fill" style="width: <?php echo min(100, ($customer_position / max(1, $waitlist_count)) * 100); ?>%"></div>
                                    </div>
                                    <small><?php printf(__('%d people ahead of you', 'smart-restock-waitlist'), $customer_position - 1); ?></small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Social Proof Section - Respect admin settings -->
            <?php 
            $hide_social_proof = get_option('srwm_hide_social_proof', '0');
            $social_proof_style = get_option('srwm_social_proof_style', 'full');
            
            if ($waitlist_count > 0 && !$hide_social_proof): 
            ?>
            <div class="srwm-waitlist-preview <?php echo $social_proof_style === 'compact' ? 'srwm-compact' : ''; ?>">
                <?php if ($social_proof_style === 'compact'): ?>
                    <!-- Compact Social Proof -->
                    <div class="srwm-preview-compact">
                        <div class="srwm-compact-count">
                            <span class="srwm-count-number" data-count="<?php echo $waitlist_count; ?>">0</span>
                            <span class="srwm-compact-label">
                                <?php printf(
                                    _n('%s person waiting', '%s people waiting', $waitlist_count, 'smart-restock-waitlist'),
                                    number_format($waitlist_count)
                                ); ?>
                            </span>
                        </div>
                        <div class="srwm-compact-subtitle">
                            <?php if ($is_on_waitlist): ?>
                                <?php _e('You\'re on the list!', 'smart-restock-waitlist'); ?>
                            <?php else: ?>
                                <?php _e('Join the waitlist', 'smart-restock-waitlist'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Full Social Proof -->
                    <div class="srwm-preview-header">
                        <div class="srwm-preview-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="srwm-preview-content">
                            <div class="srwm-preview-count">
                                <span class="srwm-count-number" data-count="<?php echo $waitlist_count; ?>">0</span>
                                <span class="srwm-count-label">
                                    <?php printf(
                                        _n('%s person is waiting', '%s people are waiting', $waitlist_count, 'smart-restock-waitlist'),
                                        number_format($waitlist_count)
                                    ); ?>
                                </span>
                            </div>
                            <div class="srwm-preview-subtitle">
                                <?php if ($is_on_waitlist): ?>
                                    <?php _e('You\'re part of an exclusive group!', 'smart-restock-waitlist'); ?>
                                <?php else: ?>
                                    <?php _e('Join them and get notified first!', 'smart-restock-waitlist'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="srwm-queue-visualization">
                        <div class="srwm-queue-bar">
                            <div class="srwm-queue-fill" style="width: <?php echo min(100, ($waitlist_count / 100) * 100); ?>%"></div>
                        </div>
                        <div class="srwm-queue-stats">
                            <span class="srwm-stat-item">
                                <span class="srwm-stat-icon">⚡</span>
                                <?php _e('Fast notifications', 'smart-restock-waitlist'); ?>
                            </span>
                            <span class="srwm-stat-item">
                                <span class="srwm-stat-icon">🎯</span>
                                <?php _e('Priority access', 'smart-restock-waitlist'); ?>
                            </span>
                            <span class="srwm-stat-item">
                                <span class="srwm-stat-icon">🔒</span>
                                <?php _e('Secure & private', 'smart-restock-waitlist'); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$is_on_waitlist): ?>
                <!-- User is not on waitlist - Show form -->
                <div class="srwm-waitlist-form-container">
                    <form class="srwm-waitlist-form" method="post">
                        <?php wp_nonce_field('srwm_waitlist_nonce', 'srwm_waitlist_nonce'); ?>
                        <input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">
                        
                        <div class="srwm-form-fields">
                            <div class="srwm-field-group">
                                <label for="srwm_customer_name"><?php _e('Your Name', 'smart-restock-waitlist'); ?></label>
                                <input type="text" id="srwm_customer_name" name="name" 
                                       value="<?php echo esc_attr($this->get_current_customer_name()); ?>" 
                                       placeholder="<?php _e('Enter your full name', 'smart-restock-waitlist'); ?>" required>
                            </div>
                            
                            <div class="srwm-field-group">
                                <label for="srwm_customer_email"><?php _e('Email Address', 'smart-restock-waitlist'); ?></label>
                                <input type="email" id="srwm_customer_email" name="email" 
                                       value="<?php echo esc_attr($this->get_current_customer_email()); ?>" 
                                       placeholder="<?php _e('your@email.com', 'smart-restock-waitlist'); ?>" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="srwm-waitlist-submit">
                            <span class="srwm-submit-icon">
                                <span class="dashicons dashicons-bell"></span>
                            </span>
                            <span class="srwm-submit-text"><?php _e('Join Waitlist', 'smart-restock-waitlist'); ?></span>
                        </button>
                    </form>
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
        
        // Add dynamic CSS for custom colors
        wp_add_inline_style('srwm-waitlist', $this->get_dynamic_css());
        
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
     * Generate dynamic CSS based on user settings
     */
    private function get_dynamic_css() {
        $css = '';
        
        // Get user settings
        $container_bg = get_option('srwm_container_bg', '#ffffff');
        $header_bg = get_option('srwm_header_bg', '#f8f9fa');
        $header_text = get_option('srwm_header_text', '#333333');
        $body_text = get_option('srwm_body_text', '#666666');
        $btn_primary_bg = get_option('srwm_btn_primary_bg', '#007cba');
        $btn_primary_text = get_option('srwm_btn_primary_text', '#ffffff');
        $success_bg = get_option('srwm_success_bg', '#d4edda');
        $success_text = get_option('srwm_success_text', '#155724');
        $border_color = get_option('srwm_border_color', '#e9ecef');
        $input_bg = get_option('srwm_input_bg', '#ffffff');
        $input_border = get_option('srwm_input_border', '#ced4da');
        $input_focus_border = get_option('srwm_input_focus_border', '#007cba');
        $progress_fill = get_option('srwm_progress_fill', '#007cba');
        $border_radius = get_option('srwm_border_radius', '8');
        $font_size = get_option('srwm_font_size', 'medium');
        
        // Font size mapping
        $font_sizes = array(
            'small' => '12px',
            'medium' => '14px',
            'large' => '16px',
            'xlarge' => '18px'
        );
        
        $base_font_size = isset($font_sizes[$font_size]) ? $font_sizes[$font_size] : '14px';
        
        $css .= "
        /* SRWM Dynamic Styles */
        .srwm-waitlist-container {
            background-color: {$container_bg} !important;
            border: 1px solid {$border_color} !important;
            border-radius: {$border_radius}px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08) !important;
            font-size: {$base_font_size} !important;
        }
        
        .srwm-waitlist-header h3 {
            color: {$header_text} !important;
        }
        
        .srwm-waitlist-subtitle {
            color: {$body_text} !important;
        }
        
        .srwm-waitlist-form-container {
            background-color: {$input_bg} !important;
            border: 1px solid {$border_color} !important;
            border-radius: {$border_radius}px !important;
        }
        
        .srwm-waitlist-form input[type='email'],
        .srwm-waitlist-form input[type='text'] {
            background-color: {$input_bg} !important;
            border: 2px solid {$input_border} !important;
            border-radius: {$border_radius}px !important;
            color: {$body_text} !important;
        }
        
        .srwm-waitlist-form input[type='email']:focus,
        .srwm-waitlist-form input[type='text']:focus {
            border-color: {$input_focus_border} !important;
            outline: none !important;
            box-shadow: 0 8px 32px rgba(" . $this->hex_to_rgb($input_focus_border) . ", 0.15) !important;
        }
        
        .srwm-waitlist-submit {
            background-color: {$btn_primary_bg} !important;
            color: {$btn_primary_text} !important;
            border-radius: {$border_radius}px !important;
            box-shadow: 0 4px 16px rgba(" . $this->hex_to_rgb($btn_primary_bg) . ", 0.3) !important;
        }
        
        .srwm-waitlist-submit:hover {
            background-color: {$this->adjust_brightness($btn_primary_bg, -10)} !important;
            box-shadow: 0 12px 40px rgba(" . $this->hex_to_rgb($btn_primary_bg) . ", 0.4) !important;
        }
        
        .srwm-status-card.active {
            background-color: {$success_bg} !important;
            border: 1px solid {$success_text} !important;
            border-radius: {$border_radius}px !important;
        }
        
        .srwm-status-card.active h4 {
            color: {$success_text} !important;
        }
        
        .srwm-status-card.active p {
            color: {$success_text} !important;
        }
        
        .srwm-queue-fill {
            background: linear-gradient(90deg, {$progress_fill}, " . $this->adjust_brightness($progress_fill, -15) . ") !important;
            border-radius: {$border_radius}px !important;
        }
        
        .srwm-progress-fill {
            background: linear-gradient(90deg, {$progress_fill}, " . $this->adjust_brightness($progress_fill, -15) . ") !important;
            border-radius: {$border_radius}px !important;
        }
        
        .srwm-preview-header {
            background-color: {$header_bg} !important;
            border: 1px solid {$border_color} !important;
            border-radius: {$border_radius}px !important;
        }
        
        .srwm-count-number {
            color: {$header_text} !important;
        }
        
        .srwm-count-label {
            color: {$body_text} !important;
        }
        
        .srwm-preview-subtitle {
            color: {$body_text} !important;
        }
        
        .srwm-queue-number {
            color: {$btn_primary_bg} !important;
            text-shadow: 0 2px 4px rgba(" . $this->hex_to_rgb($btn_primary_bg) . ", 0.2) !important;
        }
        ";
        
        return $css;
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "$r, $g, $b";
    }
    
    /**
     * Adjust color brightness
     */
    private function adjust_brightness($hex, $steps) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
        
        return sprintf("#%02x%02x%02x", $r, $g, $b);
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
            
            // Send confirmation email to customer
            self::send_waitlist_confirmation_email($product_id, $email, $name);
            
            // Check if supplier notification is needed
            self::check_supplier_notification($product_id);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Send waitlist confirmation email
     */
    private static function send_waitlist_confirmation_email($product_id, $email, $name) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        // Get email template
        $template = get_option('srwm_email_template_waitlist');
        
        // If no custom template is set, use the professional default
        if (empty($template)) {
            $admin = new SRWM_Admin();
            $template = $admin->get_default_waitlist_email_template();
        }
        
        $placeholders = array(
            '{customer_name}' => $name ?: __('Customer', 'smart-restock-waitlist'),
            '{product_name}' => $product->get_name(),
            '{product_url}' => $product->get_permalink(),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url()
        );
        
        $subject = self::replace_placeholders(
            get_option('srwm_waitlist_email_subject', __('Welcome to the Waitlist!', 'smart-restock-waitlist')),
            $placeholders
        );
        
        $message = self::replace_placeholders($template, $placeholders);
        
        // Don't wrap if it's already a complete HTML email
        if (strpos($message, '<!DOCTYPE html>') === false) {
            $message = self::wrap_email_content($message);
        }
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Replace placeholders in text
     */
    private static function replace_placeholders($text, $placeholders) {
        foreach ($placeholders as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    }
    
    /**
     * Wrap email content in basic HTML template
     */
    private static function wrap_email_content($content) {
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . get_bloginfo('name') . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px;">
                <h2 style="color: #0073aa; margin-top: 0;">' . get_bloginfo('name') . '</h2>
                <div style="background-color: white; padding: 20px; border-radius: 5px; margin-top: 20px;">
                    ' . $content . '
                </div>
                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
                    <p>' . sprintf(__('This email was sent from %s', 'smart-restock-waitlist'), get_bloginfo('name')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $template;
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

    /**
     * Get customer's position in the waitlist queue
     */
    private function get_customer_queue_position($product_id, $email) {
        global $wpdb;
        
        if (!$email) {
            return 0;
        }
        
        $table = $wpdb->prefix . 'srwm_waitlist';
        
        $position = $wpdb->get_var($wpdb->prepare(
            "SELECT position FROM (
                SELECT customer_email, ROW_NUMBER() OVER (ORDER BY date_added ASC) as position 
                FROM $table 
                WHERE product_id = %d
            ) ranked 
            WHERE customer_email = %s",
            $product_id,
            $email
        ));
        
        return intval($position);
    }
}