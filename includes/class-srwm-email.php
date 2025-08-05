<?php
/**
 * Email Management Class
 * 
 * Handles all email notifications for customers and suppliers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Email {
    
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
    }
    
    /**
     * Send restock notification to customer
     */
    public function send_restock_notification($customer, $product) {
        $template = get_option('srwm_email_template_waitlist', $this->get_default_waitlist_template());
        
        $placeholders = array(
            '{customer_name}' => $customer->customer_name ?: __('Customer', 'smart-restock-waitlist'),
            '{product_name}' => $product->get_name(),
            '{product_url}' => $product->get_permalink(),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url()
        );
        
        $subject = $this->replace_placeholders(
            get_option('srwm_waitlist_email_subject', __('{product_name} is back in stock!', 'smart-restock-waitlist')),
            $placeholders
        );
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($customer->customer_email, $subject, $message, $headers);
    }
    
    /**
     * Send supplier notification
     */
    public function send_supplier_notification($product, $supplier_data, $current_stock, $waitlist_count) {
        $template = get_option('srwm_email_template_supplier', $this->get_default_supplier_template());
        
        $placeholders = array(
            '{supplier_name}' => $supplier_data['name'] ?: __('Supplier', 'smart-restock-waitlist'),
            '{product_name}' => $product->get_name(),
            '{sku}' => $product->get_sku(),
            '{current_stock}' => $current_stock,
            '{waitlist_count}' => $waitlist_count,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url()
        );
        
        // Add Pro placeholders if license is active
        if ($this->license_manager->is_pro_active()) {
            $placeholders['{restock_link}'] = $this->generate_restock_link($product->get_id(), $supplier_data['email']);
            $placeholders['{po_number}'] = $this->generate_po_number($product->get_id());
        }
        
        $subject = $this->replace_placeholders(
            get_option('srwm_supplier_email_subject', __('Low Stock Alert: {product_name}', 'smart-restock-waitlist')),
            $placeholders
        );
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($supplier_data['email'], $subject, $message, $headers);
    }
    
    /**
     * Send one-click restock link (Pro feature)
     */
    public function send_restock_link($product, $supplier_data) {
        if (!$this->license_manager->is_pro_active()) {
            return false;
        }
        
        $restock_link = $this->generate_restock_link($product->get_id(), $supplier_data['email']);
        
        $template = $this->get_restock_link_template();
        
        $placeholders = array(
            '{supplier_name}' => $supplier_data['name'] ?: __('Supplier', 'smart-restock-waitlist'),
            '{product_name}' => $product->get_name(),
            '{sku}' => $product->get_sku(),
            '{restock_link}' => $restock_link,
            '{site_name}' => get_bloginfo('name')
        );
        
        $subject = __('One-Click Restock Link: {product_name}', 'smart-restock-waitlist');
        $subject = $this->replace_placeholders($subject, $placeholders);
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($supplier_data['email'], $subject, $message, $headers);
    }
    
    /**
     * Send purchase order (Pro feature)
     */
    public function send_purchase_order($product, $supplier_data, $po_data) {
        if (!$this->license_manager->is_pro_active()) {
            return false;
        }
        
        $template = $this->get_purchase_order_template();
        
        $placeholders = array(
            '{supplier_name}' => $supplier_data['name'] ?: __('Supplier', 'smart-restock-waitlist'),
            '{po_number}' => $po_data['po_number'],
            '{product_name}' => $product->get_name(),
            '{sku}' => $product->get_sku(),
            '{quantity}' => $po_data['quantity'],
            '{total_amount}' => $po_data['total_amount'],
            '{site_name}' => get_bloginfo('name'),
            '{company_name}' => get_option('srwm_company_name', get_bloginfo('name')),
            '{company_address}' => get_option('srwm_company_address', ''),
            '{company_phone}' => get_option('srwm_company_phone', ''),
            '{company_email}' => get_option('srwm_company_email', get_option('admin_email'))
        );
        
        $subject = __('Purchase Order #{po_number} - {product_name}', 'smart-restock-waitlist');
        $subject = $this->replace_placeholders($subject, $placeholders);
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Attach PDF if available
        $attachments = array();
        if (isset($po_data['pdf_path']) && file_exists($po_data['pdf_path'])) {
            $attachments[] = $po_data['pdf_path'];
        }
        
        return wp_mail($supplier_data['email'], $subject, $message, $headers, $attachments);
    }
    
    /**
     * Send CSV upload link (Pro feature)
     */
    public function send_csv_upload_link($supplier_email, $csv_link) {
        if (!$this->license_manager->is_pro_active()) {
            return false;
        }
        
        $template = $this->get_csv_upload_template();
        
        $placeholders = array(
            '{csv_upload_link}' => $csv_link,
            '{site_name}' => get_bloginfo('name'),
            '{expiry_date}' => date('Y-m-d H:i:s', strtotime('+7 days'))
        );
        
        $subject = __('CSV Upload Link - Bulk Restock', 'smart-restock-waitlist');
        $subject = $this->replace_placeholders($subject, $placeholders);
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($supplier_email, $subject, $message, $headers);
    }
    
    /**
     * Replace placeholders in text
     */
    private function replace_placeholders($text, $placeholders) {
        foreach ($placeholders as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    }
    
    /**
     * Wrap email content in HTML template
     */
    private function wrap_email_content($content) {
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
     * Get default waitlist email template
     */
    private function get_default_waitlist_template() {
        return '
        <p>' . __('Hi {customer_name},', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('Great news! {product_name} is back in stock and ready for purchase.', 'smart-restock-waitlist') . '</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="{product_url}" style="background-color: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                ' . __('Buy Now', 'smart-restock-waitlist') . '
            </a>
        </p>
        
        <p>' . __('Don\'t miss out - this product may sell out quickly!', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
        <strong>{site_name}</strong></p>';
    }
    
    /**
     * Get default supplier email template
     */
    private function get_default_supplier_template() {
        $template = '
        <p>' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('This is a low stock alert for the following product:', 'smart-restock-waitlist') . '</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>' . __('Product:', 'smart-restock-waitlist') . '</strong> {product_name}</p>
            <p><strong>' . __('SKU:', 'smart-restock-waitlist') . '</strong> {sku}</p>
            <p><strong>' . __('Current Stock:', 'smart-restock-waitlist') . '</strong> {current_stock}</p>
            <p><strong>' . __('Waitlist Customers:', 'smart-restock-waitlist') . '</strong> {waitlist_count}</p>
        </div>
        
        <p>' . __('Please restock this product as soon as possible to meet customer demand.', 'smart-restock-waitlist') . '</p>';
        
        if ($this->license_manager->is_pro_active()) {
            $template .= '
            <p style="text-align: center; margin: 30px 0;">
                <a href="{restock_link}" style="background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    ' . __('One-Click Restock', 'smart-restock-waitlist') . '
                </a>
            </p>';
        }
        
        $template .= '
        <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
        <strong>{site_name}</strong></p>';
        
        return $template;
    }
    
    /**
     * Get restock link email template (Pro feature)
     */
    private function get_restock_link_template() {
        return '
        <p>' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('You can restock {product_name} (SKU: {sku}) with one click using the link below:', 'smart-restock-waitlist') . '</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="{restock_link}" style="background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px;">
                ' . __('Restock Product', 'smart-restock-waitlist') . '
            </a>
        </p>
        
        <p><strong>' . __('Important:', 'smart-restock-waitlist') . '</strong></p>
        <ul>
            <li>' . __('This link is secure and can only be used once', 'smart-restock-waitlist') . '</li>
            <li>' . __('The link will expire in 7 days', 'smart-restock-waitlist') . '</li>
            <li>' . __('You can choose the quantity to add during the restock process', 'smart-restock-waitlist') . '</li>
        </ul>
        
        <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
        <strong>{site_name}</strong></p>';
    }
    
    /**
     * Get purchase order email template (Pro feature)
     */
    private function get_purchase_order_template() {
        return '
        <p>' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('Please find attached Purchase Order #{po_number} for the following product:', 'smart-restock-waitlist') . '</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>' . __('Product:', 'smart-restock-waitlist') . '</strong> {product_name}</p>
            <p><strong>' . __('SKU:', 'smart-restock-waitlist') . '</strong> {sku}</p>
            <p><strong>' . __('Quantity:', 'smart-restock-waitlist') . '</strong> {quantity}</p>
            <p><strong>' . __('Total Amount:', 'smart-restock-waitlist') . '</strong> ${total_amount}</p>
        </div>
        
        <p>' . __('Please process this order and confirm delivery details.', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
        <strong>{company_name}</strong></p>';
    }
    
    /**
     * Get CSV upload email template (Pro feature)
     */
    private function get_csv_upload_template() {
        return '
        <p>' . __('Hi,', 'smart-restock-waitlist') . '</p>
        
        <p>' . __('You can upload a CSV file for bulk restock operations using the link below:', 'smart-restock-waitlist') . '</p>
        
        <p style="text-align: center; margin: 30px 0;">
            <a href="{csv_upload_link}" style="background-color: #0073aa; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-size: 16px;">
                ' . __('Upload CSV File', 'smart-restock-waitlist') . '
            </a>
        </p>
        
        <p><strong>' . __('CSV Format:', 'smart-restock-waitlist') . '</strong></p>
        <ul>
            <li>' . __('Column 1: Product SKU', 'smart-restock-waitlist') . '</li>
            <li>' . __('Column 2: Quantity to add', 'smart-restock-waitlist') . '</li>
        </ul>
        
        <p><strong>' . __('Important:', 'smart-restock-waitlist') . '</strong></p>
        <ul>
            <li>' . __('This link is secure and can only be used once', 'smart-restock-waitlist') . '</li>
            <li>' . __('The link will expire on {expiry_date}', 'smart-restock-waitlist') . '</li>
            <li>' . __('Maximum file size: 5MB', 'smart-restock-waitlist') . '</li>
        </ul>
        
        <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
        <strong>{site_name}</strong></p>';
    }
    
    /**
     * Generate restock link (Pro feature)
     */
    private function generate_restock_link($product_id, $supplier_email) {
        if (!$this->license_manager->is_pro_active()) {
            return '';
        }
        
        if (class_exists('SRWM_Pro_Restock')) {
            $restock = SRWM_Pro_Restock::get_instance();
            return $restock->generate_restock_link($product_id, $supplier_email);
        }
        
        return '';
    }
    
    /**
     * Generate PO number (Pro feature)
     */
    private function generate_po_number($product_id) {
        if (!$this->license_manager->is_pro_active()) {
            return '';
        }
        
        return 'PO-' . date('Y') . '-' . str_pad($product_id, 6, '0', STR_PAD_LEFT);
    }
}