<?php
/**
 * Pro Restock Class
 * 
 * Handles one-click restock functionality for suppliers (Pro feature).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Pro_Restock {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'handle_restock_request'), 1);
        // AJAX handler moved to main plugin file to avoid conflicts
    }
    
    /**
     * Handle restock request from supplier link
     */
    public function handle_restock_request() {
        if (!isset($_GET['srwm_restock']) || !isset($_GET['product_id'])) {
            return;
        }
        error_log('SRWM_PRO: Pro restock handler running for token: ' . $_GET['srwm_restock']);
        
        $token = sanitize_text_field($_GET['srwm_restock']);
        $product_id = intval($_GET['product_id']);
        
        if (!$this->validate_restock_token($token, $product_id)) {
            wp_die(__('Invalid or expired restock link.', 'smart-restock-waitlist'));
        }
        
        // Handle the restock form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['srwm_restock_submit'])) {
            $this->process_restock_request($product_id, $token);
        } else {
            $this->display_restock_form($product_id, $token);
        }
    }
    
    /**
     * Validate restock token
     */
    private function validate_restock_token($token, $product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_tokens';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s AND product_id = %d AND expires_at > NOW() AND used = 0",
            $token,
            $product_id
        ));
        
        return $result !== null;
    }
    
    /**
     * Display restock form for supplier
     */
    private function display_restock_form($product_id, $token) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_die(__('Product not found.', 'smart-restock-waitlist'));
        }
        
        $supplier_data = $this->get_supplier_data($product_id);
        $waitlist_count = SRWM_Waitlist::get_waitlist_count($product_id);
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Restock Product', 'smart-restock-waitlist'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background-color: #2c3e50;
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .content {
                    padding: 30px;
                }
                .product-info {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                }
                input[type="number"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 16px;
                }
                .button {
                    background-color: #27ae60;
                    color: white;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 5px;
                    font-size: 16px;
                    cursor: pointer;
                    width: 100%;
                }
                .button:hover {
                    background-color: #219a52;
                }
                .quick-restock {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 10px;
                    margin-bottom: 20px;
                }
                .quick-restock button {
                    padding: 10px;
                    border: 1px solid #ddd;
                    background: white;
                    cursor: pointer;
                    border-radius: 4px;
                }
                .quick-restock button:hover {
                    background-color: #f8f9fa;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Restock Product', 'smart-restock-waitlist'); ?></h1>
                    <p><?php echo get_bloginfo('name'); ?></p>
                </div>
                
                <div class="content">
                    <div class="product-info">
                        <h2><?php echo esc_html($product->get_name()); ?></h2>
                        <p><strong><?php _e('SKU:', 'smart-restock-waitlist'); ?></strong> <?php echo esc_html($product->get_sku()); ?></p>
                        <p><strong><?php _e('Current Stock:', 'smart-restock-waitlist'); ?></strong> <?php echo esc_html($product->get_stock_quantity()); ?></p>
                        <p><strong><?php _e('Customers Waiting:', 'smart-restock-waitlist'); ?></strong> <?php echo esc_html($waitlist_count); ?></p>
                        <?php if ($supplier_data): ?>
                            <p><strong><?php _e('Supplier:', 'smart-restock-waitlist'); ?></strong> <?php echo esc_html($supplier_data['supplier_name']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post" action="">
                        <input type="hidden" name="srwm_restock_token" value="<?php echo esc_attr($token); ?>">
                        <input type="hidden" name="srwm_product_id" value="<?php echo esc_attr($product_id); ?>">
                        
                        <div class="form-group">
                            <label><?php _e('Quick Restock Options:', 'smart-restock-waitlist'); ?></label>
                            <div class="quick-restock">
                                <button type="button" onclick="setQuantity(10)">+10</button>
                                <button type="button" onclick="setQuantity(25)">+25</button>
                                <button type="button" onclick="setQuantity(50)">+50</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="restock_quantity"><?php _e('Quantity to add:', 'smart-restock-waitlist'); ?></label>
                            <input type="number" id="restock_quantity" name="restock_quantity" min="1" value="10" required>
                        </div>
                        
                        <button type="submit" name="srwm_restock_submit" class="button">
                            <?php _e('Restock Product & Notify Customers', 'smart-restock-waitlist'); ?>
                        </button>
                    </form>
                    
                    <p style="margin-top: 20px; font-size: 14px; color: #666;">
                        <?php _e('This action will immediately update the product stock and notify all waiting customers.', 'smart-restock-waitlist'); ?>
                    </p>
                </div>
            </div>
            
            <script>
                function setQuantity(quantity) {
                    document.getElementById('restock_quantity').value = quantity;
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Process restock request
     */
    private function process_restock_request($product_id, $token) {
        $quantity = intval($_POST['restock_quantity']);
        
        if ($quantity <= 0) {
            wp_die(__('Invalid quantity provided.', 'smart-restock-waitlist'));
        }
        
        // Restock the product
        $result = SRWM_Waitlist::restock_and_notify($product_id, $quantity);
        
        if ($result) {
            // Mark token as used
            $this->mark_token_used($token);
            
            // Log the restock action
            $this->log_restock_action($product_id, $quantity, 'supplier_link');
            
            $this->display_success_message($product_id, $quantity);
        } else {
            wp_die(__('Failed to restock product. Please try again.', 'smart-restock-waitlist'));
        }
    }
    
    /**
     * Display success message
     */
    private function display_success_message($product_id, $quantity) {
        $product = wc_get_product($product_id);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Restock Successful', 'smart-restock-waitlist'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background-color: #27ae60;
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .content {
                    padding: 30px;
                    text-align: center;
                }
                .success-icon {
                    font-size: 48px;
                    color: #27ae60;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Restock Successful!', 'smart-restock-waitlist'); ?></h1>
                </div>
                
                <div class="content">
                    <div class="success-icon">✓</div>
                    <h2><?php _e('Product Restocked Successfully', 'smart-restock-waitlist'); ?></h2>
                    <p><?php printf(__('Added %d units to %s', 'smart-restock-waitlist'), $quantity, $product->get_name()); ?></p>
                    <p><?php _e('All waiting customers have been notified automatically.', 'smart-restock-waitlist'); ?></p>
                    <p style="margin-top: 30px; font-size: 14px; color: #666;">
                        <?php _e('You can close this window now.', 'smart-restock-waitlist'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Generate restock token for supplier
     */
    public function generate_restock_token($product_id, $supplier_email) {
        global $wpdb;
        
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $table = $wpdb->prefix . 'srwm_restock_tokens';
        
        $result = $wpdb->insert(
            $table,
            array(
                'token' => $token,
                'product_id' => $product_id,
                'supplier_email' => $supplier_email,
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Mark token as used
     */
    private function mark_token_used($token) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_tokens';
        
        $wpdb->update(
            $table,
            array('used' => 1, 'used_at' => current_time('mysql')),
            array('token' => $token),
            array('%d', '%s'),
            array('%s')
        );
    }
    
    /**
     * Log restock action
     */
    private function log_restock_action($product_id, $quantity, $method) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $product = wc_get_product($product_id);
        $product_name = $product ? $product->get_name() : 'Unknown Product';
        $sku = $product ? $product->get_sku() : '';
        $waitlist_count = SRWM_Waitlist::get_waitlist_count($product_id);
        
        // Get supplier email from token
        $supplier_email = '';
        if (isset($_GET['srwm_restock'])) {
            $token = sanitize_text_field($_GET['srwm_restock']);
            $token_data = $wpdb->get_row($wpdb->prepare(
                "SELECT supplier_email FROM {$wpdb->prefix}srwm_restock_tokens WHERE token = %s",
                $token
            ));
            if ($token_data) {
                $supplier_email = $token_data->supplier_email;
            }
        }
        
        $action_details = sprintf(
            'Product restocked via %s. Quantity: %d units. IP: %s',
            $method,
            $quantity,
            $this->get_client_ip()
        );
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'product_name' => $product_name,
                'sku' => $sku,
                'quantity' => $quantity,
                'method' => $method,
                'supplier_email' => $supplier_email,
                'ip_address' => $this->get_client_ip(),
                'waitlist_count' => $waitlist_count,
                'action_details' => $action_details,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get supplier data
     */
    private function get_supplier_data($product_id) {
        $supplier = new SRWM_Supplier();
        return $supplier->get_supplier_data($product_id);
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
     * AJAX handler for generating restock link
     */
    public function ajax_generate_restock_link() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $product_id = intval($_POST['product_id']);
        $supplier_email = sanitize_email($_POST['supplier_email']);
        
        if (!$product_id || !$supplier_email) {
            wp_send_json_error(__('Invalid data provided.', 'smart-restock-waitlist'));
        }
        
        $token = $this->generate_restock_token($product_id, $supplier_email);
        
        if ($token) {
            $restock_url = add_query_arg(array(
                'srwm_restock' => $token,
                'product_id' => $product_id
            ), home_url());
            
            wp_send_json_success(array(
                'restock_url' => $restock_url,
                'message' => __('Restock link generated successfully!', 'smart-restock-waitlist')
            ));
        } else {
            wp_send_json_error(__('Failed to generate restock link.', 'smart-restock-waitlist'));
        }
    }
}