<?php
/**
 * Pro CSV Upload Class
 * 
 * Handles supplier CSV upload for bulk restock (Pro feature).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Pro_CSV_Upload {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'handle_csv_upload'));
        // Removed duplicate AJAX handler - handled in main plugin file
    }
    
    /**
     * Handle CSV upload from supplier
     */
    public function handle_csv_upload() {
        if (!isset($_GET['srwm_csv_upload']) || !isset($_GET['token'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['token']);
        
        if (!$this->validate_csv_token($token)) {
            wp_die(__('Invalid or expired upload link.', 'smart-restock-waitlist'));
        }
        
        // Handle the upload form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['srwm_csv_submit'])) {
            $this->process_csv_upload($token);
        } else {
            $this->display_csv_upload_form($token);
        }
    }
    
    /**
     * Validate CSV upload token
     */
    private function validate_csv_token($token) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s AND expires_at > NOW() AND used = 0",
            $token
        ));
        
        return $result !== null;
    }
    
    /**
     * Display CSV upload form
     */
    private function display_csv_upload_form($token) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Bulk Restock Upload', 'smart-restock-waitlist'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 800px;
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
                .upload-info {
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
                    color: #495057;
                }
                input[type="file"] {
                    width: 100%;
                    padding: 10px;
                    border: 2px solid #e9ecef;
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
                .csv-template {
                    background-color: #e8f4fd;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .csv-template h4 {
                    margin-top: 0;
                    color: #2c3e50;
                }
                .csv-template code {
                    background: white;
                    padding: 10px;
                    border-radius: 3px;
                    display: block;
                    font-family: monospace;
                    white-space: pre-wrap;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Bulk Restock Upload', 'smart-restock-waitlist'); ?></h1>
                    <p><?php echo get_bloginfo('name'); ?></p>
                </div>
                
                <div class="content">
                    <div class="upload-info">
                        <h3><?php _e('Upload Instructions', 'smart-restock-waitlist'); ?></h3>
                        <p><?php _e('Please upload a CSV file with your restock information. The file should contain product SKU and quantity columns.', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="csv-template">
                        <h4><?php _e('CSV Template', 'smart-restock-waitlist'); ?></h4>
                        <p><?php _e('Your CSV file should follow this format:', 'smart-restock-waitlist'); ?></p>
                        <code>sku,quantity
ABC123,50
DEF456,25
GHI789,100</code>
                    </div>
                    
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="srwm_csv_token" value="<?php echo esc_attr($token); ?>">
                        
                        <div class="form-group">
                            <label for="csv_file"><?php _e('Select CSV File:', 'smart-restock-waitlist'); ?></label>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        </div>
                        
                        <button type="submit" name="srwm_csv_submit" class="button">
                            <?php _e('Upload and Process', 'smart-restock-waitlist'); ?>
                        </button>
                    </form>
                    
                    <p style="margin-top: 20px; font-size: 14px; color: #666;">
                        <?php _e('The system will automatically update product stock levels and notify waiting customers.', 'smart-restock-waitlist'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Process CSV upload
     */
    private function process_csv_upload($token) {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('File upload failed. Please try again.', 'smart-restock-waitlist'));
        }
        
        $file = $_FILES['csv_file'];
        
        // Validate file type
        $file_type = wp_check_filetype($file['name']);
        if ($file_type['ext'] !== 'csv') {
            wp_die(__('Please upload a valid CSV file.', 'smart-restock-waitlist'));
        }
        
        // Read CSV file
        $csv_data = $this->read_csv_file($file['tmp_name']);
        
        if (!$csv_data) {
            wp_die(__('Failed to read CSV file. Please check the file format.', 'smart-restock-waitlist'));
        }
        
        // Process the data
        $results = $this->process_csv_data($csv_data);
        
        // Mark token as used
        $this->mark_token_used($token);
        
        // Display results
        $this->display_upload_results($results);
    }
    
    /**
     * Read CSV file
     */
    private function read_csv_file($file_path) {
        $data = array();
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            // Read header row
            $header = fgetcsv($handle);
            
            if (!$header || count($header) < 2) {
                fclose($handle);
                return false;
            }
            
            // Find column indexes
            $sku_index = array_search('sku', array_map('strtolower', $header));
            $quantity_index = array_search('quantity', array_map('strtolower', $header));
            
            if ($sku_index === false || $quantity_index === false) {
                fclose($handle);
                return false;
            }
            
            // Read data rows
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $data[] = array(
                        'sku' => trim($row[$sku_index]),
                        'quantity' => intval($row[$quantity_index])
                    );
                }
            }
            
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Process CSV data
     */
    private function process_csv_data($csv_data) {
        $results = array(
            'success' => 0,
            'errors' => array(),
            'skipped' => 0
        );
        
        foreach ($csv_data as $row) {
            $sku = $row['sku'];
            $quantity = $row['quantity'];
            
            if (empty($sku) || $quantity <= 0) {
                $results['skipped']++;
                continue;
            }
            
            // Find product by SKU
            $product_id = wc_get_product_id_by_sku($sku);
            
            if (!$product_id) {
                $results['errors'][] = sprintf(
                    __('Product with SKU "%s" not found.', 'smart-restock-waitlist'),
                    $sku
                );
                continue;
            }
            
            $product = wc_get_product($product_id);
            
            if (!$product) {
                $results['errors'][] = sprintf(
                    __('Failed to load product with SKU "%s".', 'smart-restock-waitlist'),
                    $sku
                );
                continue;
            }
            
            // Restock the product
            $result = SRWM_Waitlist::restock_and_notify($product_id, $quantity);
            
            if ($result) {
                $results['success']++;
                
                // Log the restock action
                $this->log_csv_restock($product_id, $quantity, $sku);
            } else {
                $results['errors'][] = sprintf(
                    __('Failed to restock product "%s" (SKU: %s).', 'smart-restock-waitlist'),
                    $product->get_name(),
                    $sku
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Display upload results
     */
    private function display_upload_results($results) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Upload Results', 'smart-restock-waitlist'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 800px;
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
                }
                .result-summary {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .success-count {
                    color: #27ae60;
                    font-size: 24px;
                    font-weight: bold;
                }
                .error-count {
                    color: #e74c3c;
                    font-size: 24px;
                    font-weight: bold;
                }
                .error-list {
                    background-color: #f8d7da;
                    border: 1px solid #f5c6cb;
                    border-radius: 5px;
                    padding: 15px;
                    margin-top: 15px;
                }
                .error-list h4 {
                    margin-top: 0;
                    color: #721c24;
                }
                .error-list ul {
                    margin: 0;
                    padding-left: 20px;
                }
                .error-list li {
                    margin-bottom: 5px;
                    color: #721c24;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Upload Complete!', 'smart-restock-waitlist'); ?></h1>
                </div>
                
                <div class="content">
                    <div class="result-summary">
                        <h3><?php _e('Upload Summary', 'smart-restock-waitlist'); ?></h3>
                        <p>
                            <span class="success-count"><?php echo $results['success']; ?></span> 
                            <?php _e('products restocked successfully', 'smart-restock-waitlist'); ?>
                        </p>
                        
                        <?php if ($results['skipped'] > 0): ?>
                            <p><?php echo $results['skipped']; ?> <?php _e('rows skipped (invalid data)', 'smart-restock-waitlist'); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($results['errors'])): ?>
                            <p>
                                <span class="error-count"><?php echo count($results['errors']); ?></span> 
                                <?php _e('errors encountered', 'smart-restock-waitlist'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($results['errors'])): ?>
                        <div class="error-list">
                            <h4><?php _e('Errors:', 'smart-restock-waitlist'); ?></h4>
                            <ul>
                                <?php foreach ($results['errors'] as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p style="margin-top: 30px; font-size: 14px; color: #666;">
                        <?php _e('All waiting customers have been notified automatically for successfully restocked products.', 'smart-restock-waitlist'); ?>
                    </p>
                    
                    <p style="margin-top: 20px; font-size: 14px; color: #666;">
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
     * Generate CSV upload token for supplier
     */
    public function generate_csv_token($supplier_email) {
        global $wpdb;
        
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return false;
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'token' => $token,
                'supplier_email' => $supplier_email,
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
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
        
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        $wpdb->update(
            $table,
            array('used' => 1, 'used_at' => current_time('mysql')),
            array('token' => $token),
            array('%d', '%s'),
            array('%s')
        );
    }
    
    /**
     * Log CSV restock action
     */
    private function log_csv_restock($product_id, $quantity, $sku) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'method' => 'csv_upload',
                'ip_address' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
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
     * AJAX handler for generating CSV upload link (removed - handled in main plugin)
     * This method was causing conflicts with the main plugin's AJAX handler
     */
    
    /**
     * Check if Pro version is active
     */
    private function is_pro_active() {
        return function_exists('srwm_pro_init') || defined('SRWM_PRO_VERSION');
    }
}