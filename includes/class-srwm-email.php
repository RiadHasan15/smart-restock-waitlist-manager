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
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_filter('woocommerce_email_styles', array($this, 'add_email_styles'));
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
    public function send_supplier_notification($product, $supplier_data) {
        $template = get_option('srwm_email_template_supplier', $this->get_default_supplier_template());
        
        $waitlist_count = SRWM_Waitlist::get_waitlist_count($product->get_id());
        
        $placeholders = array(
            '{supplier_name}' => $supplier_data['supplier_name'] ?: __('Supplier', 'smart-restock-waitlist'),
            '{product_name}' => $product->get_name(),
            '{sku}' => $product->get_sku(),
            '{current_stock}' => $product->get_stock_quantity(),
            '{waitlist_count}' => $waitlist_count,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => get_site_url(),
            '{product_url}' => $product->get_permalink()
        );
        
        $subject = $this->replace_placeholders(
            get_option('srwm_supplier_email_subject', __('Low stock alert: {product_name}', 'smart-restock-waitlist')),
            $placeholders
        );
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($supplier_data['supplier_email'], $subject, $message, $headers);
    }
    
    /**
     * Send one-click restock link to supplier (Pro feature)
     */
    public function send_restock_link($product, $supplier_data, $restock_token) {
        if (!$this->is_pro_active()) {
            return false;
        }
        
        $restock_url = add_query_arg(array(
            'srwm_restock' => $restock_token,
            'product_id' => $product->get_id()
        ), home_url());
        
        $template = get_option('srwm_restock_link_template', $this->get_default_restock_link_template());
        
        $placeholders = array(
            '{supplier_name}' => $supplier_data['supplier_name'] ?: __('Supplier', 'smart-restock-waitlist'),
            '{product_name}' => $product->get_name(),
            '{sku}' => $product->get_sku(),
            '{current_stock}' => $product->get_stock_quantity(),
            '{waitlist_count}' => SRWM_Waitlist::get_waitlist_count($product->get_id()),
            '{restock_link}' => $restock_url,
            '{site_name}' => get_bloginfo('name')
        );
        
        $subject = $this->replace_placeholders(
            get_option('srwm_restock_link_subject', __('Restock request: {product_name}', 'smart-restock-waitlist')),
            $placeholders
        );
        
        $message = $this->replace_placeholders($template, $placeholders);
        $message = $this->wrap_email_content($message);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        return wp_mail($supplier_data['supplier_email'], $subject, $message, $headers);
    }
    
    /**
     * Send purchase order to supplier (Pro feature)
     */
    public function send_purchase_order($product, $supplier_data, $po_data) {
        if (!$this->is_pro_active()) {
            return false;
        }
        
        $template = get_option('srwm_po_email_template', $this->get_default_po_template());
        
        $placeholders = array(
            '{supplier_name}' => $supplier_data['supplier_name'] ?: __('Supplier', 'smart-restock-waitlist'),
            '{po_number}' => $po_data['po_number'],
            '{po_date}' => $po_data['po_date'],
            '{total_amount}' => $po_data['total_amount'],
            '{delivery_date}' => $po_data['delivery_date'],
            '{site_name}' => get_bloginfo('name')
        );
        
        $subject = $this->replace_placeholders(
            get_option('srwm_po_email_subject', __('Purchase Order #{po_number} - {site_name}', 'smart-restock-waitlist')),
            $placeholders
        );
        
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
        
        return wp_mail($supplier_data['supplier_email'], $subject, $message, $headers, $attachments);
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
        $template = $this->get_email_template();
        return str_replace('{content}', $content, $template);
    }
    
    /**
     * Get email HTML template
     */
    private function get_email_template() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . get_bloginfo('name') . '</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4;">
                <tr>
                    <td align="center" style="padding: 20px 0;">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <tr>
                                <td style="padding: 30px; text-align: center; background-color: #2c3e50; color: #ffffff; border-radius: 8px 8px 0 0;">
                                    <h1 style="margin: 0; font-size: 24px;">' . get_bloginfo('name') . '</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 30px;">
                                    {content}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 20px 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center; color: #6c757d; font-size: 12px;">
                                    <p style="margin: 0;">' . sprintf(__('This email was sent from %s', 'smart-restock-waitlist'), get_bloginfo('name')) . '</p>
                                    <p style="margin: 5px 0 0 0;">' . get_site_url() . '</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
    
    /**
     * Get default waitlist email template
     */
    private function get_default_waitlist_template() {
        return '
        <h2 style="color: #2c3e50; margin-bottom: 20px;">' . __('Great News!', 'smart-restock-waitlist') . '</h2>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Hi {customer_name},', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Great news! {product_name} is back in stock and ready for purchase.', 'smart-restock-waitlist') . '
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{product_url}" style="display: inline-block; background-color: #27ae60; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                ' . __('Buy Now', 'smart-restock-waitlist') . '
            </a>
        </div>
        
        <p style="font-size: 14px; line-height: 1.6; color: #666; margin-bottom: 20px;">
            ' . __('This product was in high demand, so we recommend purchasing soon to avoid missing out again.', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 14px; line-height: 1.6; color: #666;">
            ' . __('Best regards,', 'smart-restock-waitlist') . '<br>
            <strong>{site_name}</strong>
        </p>';
    }
    
    /**
     * Get default supplier email template
     */
    private function get_default_supplier_template() {
        return '
        <h2 style="color: #e74c3c; margin-bottom: 20px;">' . __('Low Stock Alert', 'smart-restock-waitlist') . '</h2>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('We need to inform you that {product_name} (SKU: {sku}) is running low on stock.', 'smart-restock-waitlist') . '
        </p>
        
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">' . __('Product Details', 'smart-restock-waitlist') . '</h3>
            <p style="margin: 5px 0;"><strong>' . __('Product:', 'smart-restock-waitlist') . '</strong> {product_name}</p>
            <p style="margin: 5px 0;"><strong>' . __('SKU:', 'smart-restock-waitlist') . '</strong> {sku}</p>
            <p style="margin: 5px 0;"><strong>' . __('Current Stock:', 'smart-restock-waitlist') . '</strong> {current_stock}</p>
            <p style="margin: 5px 0;"><strong>' . __('Customers Waiting:', 'smart-restock-waitlist') . '</strong> {waitlist_count}</p>
        </div>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Please restock this product as soon as possible to meet customer demand.', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 14px; line-height: 1.6; color: #666;">
            ' . __('Best regards,', 'smart-restock-waitlist') . '<br>
            <strong>{site_name}</strong>
        </p>';
    }
    
    /**
     * Get default restock link template (Pro feature)
     */
    private function get_default_restock_link_template() {
        return '
        <h2 style="color: #2c3e50; margin-bottom: 20px;">' . __('Restock Request', 'smart-restock-waitlist') . '</h2>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('We need to restock {product_name} (SKU: {sku}).', 'smart-restock-waitlist') . '
        </p>
        
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">' . __('Product Details', 'smart-restock-waitlist') . '</h3>
            <p style="margin: 5px 0;"><strong>' . __('Product:', 'smart-restock-waitlist') . '</strong> {product_name}</p>
            <p style="margin: 5px 0;"><strong>' . __('SKU:', 'smart-restock-waitlist') . '</strong> {sku}</p>
            <p style="margin: 5px 0;"><strong>' . __('Current Stock:', 'smart-restock-waitlist') . '</strong> {current_stock}</p>
            <p style="margin: 5px 0;"><strong>' . __('Customers Waiting:', 'smart-restock-waitlist') . '</strong> {waitlist_count}</p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{restock_link}" style="display: inline-block; background-color: #3498db; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                ' . __('Restock Product', 'smart-restock-waitlist') . '
            </a>
        </div>
        
        <p style="font-size: 14px; line-height: 1.6; color: #666;">
            ' . __('Click the button above to restock this product instantly. No login required.', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 14px; line-height: 1.6; color: #666;">
            ' . __('Best regards,', 'smart-restock-waitlist') . '<br>
            <strong>{site_name}</strong>
        </p>';
    }
    
    /**
     * Get default purchase order template (Pro feature)
     */
    private function get_default_po_template() {
        return '
        <h2 style="color: #2c3e50; margin-bottom: 20px;">' . __('Purchase Order', 'smart-restock-waitlist') . '</h2>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Please find attached our purchase order #{po_number} dated {po_date}.', 'smart-restock-waitlist') . '
        </p>
        
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">' . __('Order Summary', 'smart-restock-waitlist') . '</h3>
            <p style="margin: 5px 0;"><strong>' . __('PO Number:', 'smart-restock-waitlist') . '</strong> {po_number}</p>
            <p style="margin: 5px 0;"><strong>' . __('Order Date:', 'smart-restock-waitlist') . '</strong> {po_date}</p>
            <p style="margin: 5px 0;"><strong>' . __('Total Amount:', 'smart-restock-waitlist') . '</strong> {total_amount}</p>
            <p style="margin: 5px 0;"><strong>' . __('Delivery Date:', 'smart-restock-waitlist') . '</strong> {delivery_date}</p>
        </div>
        
        <p style="font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 20px;">
            ' . __('Please confirm receipt of this purchase order and provide an estimated delivery date.', 'smart-restock-waitlist') . '
        </p>
        
        <p style="font-size: 14px; line-height: 1.6; color: #666;">
            ' . __('Best regards,', 'smart-restock-waitlist') . '<br>
            <strong>{site_name}</strong>
        </p>';
    }
    
    /**
     * Add custom styles to WooCommerce emails
     */
    public function add_email_styles($css) {
        $css .= '
        .srwm-email-content {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .srwm-email-button {
            display: inline-block;
            background-color: #27ae60;
            color: #ffffff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .srwm-email-info-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        ';
        return $css;
    }
    
    /**
     * Check if Pro version is active
     */
    private function is_pro_active() {
        return function_exists('srwm_pro_init') || defined('SRWM_PRO_VERSION');
    }
}