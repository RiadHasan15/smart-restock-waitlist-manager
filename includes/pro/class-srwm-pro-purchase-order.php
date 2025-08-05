<?php
/**
 * Pro Purchase Order Class
 * 
 * Handles automatic purchase order generation and management (Pro feature).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Pro_Purchase_Order {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handler moved to main plugin file to avoid conflicts
        add_action('srwm_supplier_notified', array($this, 'maybe_generate_po'), 10, 2);
    }
    
    /**
     * Generate purchase order for a product
     */
    public function generate_purchase_order($product_id, $supplier_data, $quantity = null) {
        if (!$this->is_pro_active()) {
            return false;
        }
        
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        // Generate PO data
        $po_data = $this->create_po_data($product, $supplier_data, $quantity);
        
        // Generate PDF
        $pdf_path = $this->generate_pdf($po_data);
        
        if ($pdf_path) {
            $po_data['pdf_path'] = $pdf_path;
            
            // Send PO to supplier
            $email = SRWM_Email::get_instance();
            $email->send_purchase_order($product, $supplier_data, $po_data);
            
            // Log PO generation
            $this->log_po_generation($po_data);
            
            return $po_data;
        }
        
        return false;
    }
    
    /**
     * Create PO data
     */
    private function create_po_data($product, $supplier_data, $quantity = null) {
        $po_number = $this->generate_po_number();
        
        // Calculate suggested quantity based on waitlist and current stock
        if (!$quantity) {
            $waitlist_count = SRWM_Waitlist::get_waitlist_count($product->get_id());
            $current_stock = $product->get_stock_quantity();
            $suggested_quantity = max(10, $waitlist_count * 2); // At least 10, or 2x waitlist
            $quantity = $suggested_quantity;
        }
        
        // Get product cost (if available)
        $cost = $this->get_product_cost($product);
        $total_amount = $cost * $quantity;
        
        return array(
            'po_number' => $po_number,
            'po_date' => current_time('Y-m-d'),
            'delivery_date' => date('Y-m-d', strtotime('+14 days')),
            'supplier_name' => $supplier_data['supplier_name'] ?: __('Supplier', 'smart-restock-waitlist'),
            'supplier_email' => $supplier_data['supplier_email'],
            'product_id' => $product->get_id(),
            'product_name' => $product->get_name(),
            'product_sku' => $product->get_sku(),
            'quantity' => $quantity,
            'unit_cost' => $cost,
            'total_amount' => $total_amount,
            'company_info' => $this->get_company_info()
        );
    }
    
    /**
     * Generate PO number
     */
    private function generate_po_number() {
        $prefix = get_option('srwm_po_prefix', 'PO');
        $year = date('Y');
        $month = date('m');
        
        // Get last PO number for this month
        global $wpdb;
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        
        $last_po = $wpdb->get_var($wpdb->prepare(
            "SELECT po_number FROM $table WHERE po_number LIKE %s ORDER BY id DESC LIMIT 1",
            $prefix . '-' . $year . $month . '-%'
        ));
        
        if ($last_po) {
            $parts = explode('-', $last_po);
            $last_number = intval(end($parts));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $new_number);
    }
    
    /**
     * Get product cost
     */
    private function get_product_cost($product) {
        // Try to get cost from meta
        $cost = get_post_meta($product->get_id(), '_cost', true);
        
        if (!$cost) {
            // Try to get from regular price
            $cost = $product->get_regular_price();
        }
        
        if (!$cost) {
            // Default cost
            $cost = 10.00;
        }
        
        return floatval($cost);
    }
    
    /**
     * Get company information
     */
    private function get_company_info() {
        return array(
            'name' => get_option('srwm_company_name', get_bloginfo('name')),
            'address' => get_option('srwm_company_address', ''),
            'phone' => get_option('srwm_company_phone', ''),
            'email' => get_option('srwm_company_email', get_option('admin_email')),
            'website' => get_option('srwm_company_website', get_site_url()),
            'tax_id' => get_option('srwm_company_tax_id', ''),
            'logo' => get_option('srwm_company_logo', '')
        );
    }
    
    /**
     * Generate PDF purchase order
     */
    private function generate_pdf($po_data) {
        // Check if we can generate PDFs
        if (!class_exists('TCPDF') && !function_exists('fpdf')) {
            // Use simple HTML to PDF conversion
            return $this->generate_simple_pdf($po_data);
        }
        
        // Use TCPDF if available
        if (class_exists('TCPDF')) {
            return $this->generate_tcpdf($po_data);
        }
        
        return false;
    }
    
    /**
     * Generate simple PDF (HTML to PDF)
     */
    private function generate_simple_pdf($po_data) {
        $html = $this->get_po_html($po_data);
        
        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $po_dir = $upload_dir['basedir'] . '/purchase-orders/';
        
        if (!file_exists($po_dir)) {
            wp_mkdir_p($po_dir);
        }
        
        $filename = 'po-' . $po_data['po_number'] . '.html';
        $filepath = $po_dir . $filename;
        
        // Save HTML file
        file_put_contents($filepath, $html);
        
        return $filepath;
    }
    
    /**
     * Generate TCPDF
     */
    private function generate_tcpdf($po_data) {
        // This would use TCPDF library to generate proper PDF
        // For now, we'll return false and use HTML version
        return false;
    }
    
    /**
     * Get PO HTML template
     */
    private function get_po_html($po_data) {
        $company = $po_data['company_info'];
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Purchase Order - <?php echo esc_html($po_data['po_number']); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }
                .header {
                    border-bottom: 2px solid #2c3e50;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .company-info {
                    float: left;
                    width: 50%;
                }
                .po-info {
                    float: right;
                    width: 40%;
                    text-align: right;
                }
                .clear {
                    clear: both;
                }
                .po-title {
                    font-size: 24px;
                    font-weight: bold;
                    color: #2c3e50;
                    margin-bottom: 10px;
                }
                .po-number {
                    font-size: 18px;
                    color: #3498db;
                    margin-bottom: 5px;
                }
                .po-date {
                    color: #666;
                }
                .supplier-info {
                    margin-bottom: 30px;
                }
                .supplier-info h3 {
                    margin: 0 0 10px 0;
                    color: #2c3e50;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    text-align: left;
                }
                th {
                    background: #f8f9fa;
                    font-weight: bold;
                }
                .total-row {
                    font-weight: bold;
                    background: #f8f9fa;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                    color: #666;
                }
                .terms {
                    margin-top: 30px;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 5px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-info">
                    <h1><?php echo esc_html($company['name']); ?></h1>
                    <?php if ($company['address']): ?>
                        <p><?php echo esc_html($company['address']); ?></p>
                    <?php endif; ?>
                    <?php if ($company['phone']): ?>
                        <p>Phone: <?php echo esc_html($company['phone']); ?></p>
                    <?php endif; ?>
                    <p>Email: <?php echo esc_html($company['email']); ?></p>
                    <?php if ($company['website']): ?>
                        <p>Website: <?php echo esc_html($company['website']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="po-info">
                    <div class="po-title">PURCHASE ORDER</div>
                    <div class="po-number"><?php echo esc_html($po_data['po_number']); ?></div>
                    <div class="po-date">Date: <?php echo esc_html($po_data['po_date']); ?></div>
                    <div class="po-date">Delivery: <?php echo esc_html($po_data['delivery_date']); ?></div>
                </div>
                
                <div class="clear"></div>
            </div>
            
            <div class="supplier-info">
                <h3>Supplier Information</h3>
                <p><strong>Name:</strong> <?php echo esc_html($po_data['supplier_name']); ?></p>
                <p><strong>Email:</strong> <?php echo esc_html($po_data['supplier_email']); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><?php echo esc_html($po_data['product_sku']); ?></td>
                        <td><?php echo esc_html($po_data['product_name']); ?></td>
                        <td><?php echo esc_html($po_data['quantity']); ?></td>
                        <td>$<?php echo number_format($po_data['unit_cost'], 2); ?></td>
                        <td>$<?php echo number_format($po_data['total_amount'], 2); ?></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5" style="text-align: right;"><strong>Total:</strong></td>
                        <td><strong>$<?php echo number_format($po_data['total_amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="terms">
                <h4>Terms and Conditions</h4>
                <ul>
                    <li>Payment terms: Net 30 days</li>
                    <li>Delivery: <?php echo esc_html($po_data['delivery_date']); ?></li>
                    <li>All items must be in new condition</li>
                    <li>Returns accepted within 30 days</li>
                    <li>Please include packing slip with shipment</li>
                </ul>
            </div>
            
            <div class="footer">
                <p>This purchase order was generated automatically by Smart Restock & Waitlist Manager.</p>
                <p>For questions, please contact: <?php echo esc_html($company['email']); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Log PO generation
     */
    private function log_po_generation($po_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_purchase_orders';
        
        $wpdb->insert(
            $table,
            array(
                'po_number' => $po_data['po_number'],
                'product_id' => $po_data['product_id'],
                'supplier_email' => $po_data['supplier_email'],
                'quantity' => $po_data['quantity'],
                'total_amount' => $po_data['total_amount'],
                'status' => 'sent',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%d', '%f', '%s', '%s')
        );
    }
    
    /**
     * Maybe generate PO when supplier is notified
     */
    public function maybe_generate_po($product_id, $supplier_data) {
        // Check if auto PO generation is enabled
        if (get_option('srwm_auto_generate_po') !== 'yes') {
            return;
        }
        
        // Check if supplier has PO generation enabled
        if (!isset($supplier_data['auto_po']) || !$supplier_data['auto_po']) {
            return;
        }
        
        // Generate PO
        $this->generate_purchase_order($product_id, $supplier_data);
    }
    
    /**
     * AJAX handler for generating PO
     */
    public function ajax_generate_po() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Insufficient permissions.', 'smart-restock-waitlist'));
        }
        
        $product_id = intval($_POST['product_id']);
        $supplier_email = sanitize_email($_POST['supplier_email']);
        $quantity = intval($_POST['quantity']);
        
        if (!$product_id || !$supplier_email) {
            wp_send_json_error(__('Invalid data provided.', 'smart-restock-waitlist'));
        }
        
        $supplier = new SRWM_Supplier();
        $supplier_data = $supplier->get_supplier_data($product_id);
        
        if (empty($supplier_data)) {
            wp_send_json_error(__('Supplier data not found.', 'smart-restock-waitlist'));
        }
        
        $result = $this->generate_purchase_order($product_id, $supplier_data, $quantity);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Purchase order generated and sent successfully!', 'smart-restock-waitlist'),
                'po_number' => $result['po_number']
            ));
        } else {
            wp_send_json_error(__('Failed to generate purchase order.', 'smart-restock-waitlist'));
        }
    }
    
    /**
     * Check if Pro version is active
     */
    private function is_pro_active() {
        return function_exists('srwm_pro_init') || defined('SRWM_PRO_VERSION');
    }
}