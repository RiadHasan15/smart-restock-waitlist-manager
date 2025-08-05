<?php
/**
 * Supplier Notification Email Template
 * 
 * Template for notifying suppliers about low stock products.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get supplier notification email template
 */
function srwm_get_supplier_notification_template($product, $supplier_data, $current_stock, $waitlist_count) {
    $template = get_option('srwm_email_template_supplier', srwm_get_default_supplier_template());
    
    $placeholders = array(
        '{supplier_name}' => $supplier_data['name'] ?: __('Supplier', 'smart-restock-waitlist'),
        '{product_name}' => $product->get_name(),
        '{product_url}' => $product->get_permalink(),
        '{product_image}' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
        '{product_price}' => $product->get_price_html(),
        '{sku}' => $product->get_sku(),
        '{current_stock}' => $current_stock,
        '{waitlist_count}' => $waitlist_count,
        '{site_name}' => get_bloginfo('name'),
        '{site_url}' => get_site_url(),
        '{site_logo}' => get_option('srwm_site_logo', ''),
        '{current_date}' => date_i18n(get_option('date_format')),
        '{threshold}' => $supplier_data['threshold'] ?: get_option('srwm_low_stock_threshold', 5)
    );
    
    // Add Pro placeholders if license is active
    if (class_exists('SRWM_License_Manager')) {
        $license_manager = SRWM_License_Manager::get_instance();
        if ($license_manager->is_pro_active()) {
            $placeholders['{restock_link}'] = srwm_generate_restock_link($product->get_id(), $supplier_data['email']);
            $placeholders['{po_number}'] = srwm_generate_po_number($product->get_id());
        }
    }
    
    return srwm_replace_placeholders($template, $placeholders);
}

/**
 * Get default supplier email template
 */
function srwm_get_default_supplier_template() {
    return '
    <!DOCTYPE html>
    <html lang="' . get_locale() . '">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . __('Low Stock Alert', 'smart-restock-waitlist') . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
            }
            .content {
                padding: 40px 30px;
            }
            .alert-section {
                background-color: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 8px;
                padding: 25px;
                margin: 25px 0;
                text-align: center;
            }
            .alert-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }
            .product-section {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 25px;
                margin: 25px 0;
                text-align: center;
            }
            .product-image {
                max-width: 150px;
                height: auto;
                border-radius: 8px;
                margin-bottom: 15px;
            }
            .product-name {
                font-size: 18px;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 10px;
            }
            .stock-info {
                display: flex;
                justify-content: space-around;
                margin: 20px 0;
                flex-wrap: wrap;
            }
            .stock-item {
                text-align: center;
                padding: 15px;
                background-color: white;
                border-radius: 6px;
                margin: 5px;
                min-width: 120px;
            }
            .stock-number {
                font-size: 24px;
                font-weight: bold;
                display: block;
            }
            .stock-label {
                font-size: 12px;
                color: #666;
                text-transform: uppercase;
            }
            .current-stock {
                color: #dc3545;
            }
            .waitlist-count {
                color: #0073aa;
            }
            .cta-button {
                display: inline-block;
                background-color: #28a745;
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 6px;
                font-weight: bold;
                font-size: 16px;
                margin: 20px 0;
                transition: background-color 0.3s ease;
            }
            .cta-button:hover {
                background-color: #1e7e34;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 25px 30px;
                text-align: center;
                color: #666;
                font-size: 14px;
            }
            .footer a {
                color: #0073aa;
                text-decoration: none;
            }
            .footer a:hover {
                text-decoration: underline;
            }
            .urgency-notice {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
                padding: 15px;
                margin: 20px 0;
                text-align: center;
                color: #856404;
            }
            .pro-features {
                background-color: #e7f3ff;
                border: 1px solid #b3d9ff;
                border-radius: 6px;
                padding: 20px;
                margin: 20px 0;
            }
            .pro-features h3 {
                color: #0073aa;
                margin-top: 0;
            }
            .pro-features ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .pro-features li {
                margin: 5px 0;
            }
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                .header, .content, .footer {
                    padding: 20px !important;
                }
                .product-section, .alert-section {
                    padding: 20px !important;
                }
                .stock-info {
                    flex-direction: column;
                }
                .stock-item {
                    margin: 5px 0;
                }
                .cta-button {
                    display: block !important;
                    width: 100% !important;
                    text-align: center !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>' . __('Low Stock Alert', 'smart-restock-waitlist') . '</h1>
                <p>' . __('Action required - Product needs restocking', 'smart-restock-waitlist') . '</p>
            </div>
            
            <div class="content">
                <p>' . __('Hi {supplier_name},', 'smart-restock-waitlist') . '</p>
                
                <div class="alert-section">
                    <div class="alert-icon">⚠️</div>
                    <h2>' . __('Low Stock Alert', 'smart-restock-waitlist') . '</h2>
                    <p>' . __('The following product is running low on stock and requires immediate attention:', 'smart-restock-waitlist') . '</p>
                </div>
                
                <div class="product-section">
                    {product_image}
                    <div class="product-name">{product_name}</div>
                    <div class="product-price">{product_price}</div>
                    
                    <div class="stock-info">
                        <div class="stock-item">
                            <span class="stock-number current-stock">{current_stock}</span>
                            <span class="stock-label">' . __('Current Stock', 'smart-restock-waitlist') . '</span>
                        </div>
                        <div class="stock-item">
                            <span class="stock-number waitlist-count">{waitlist_count}</span>
                            <span class="stock-label">' . __('Customers Waiting', 'smart-restock-waitlist') . '</span>
                        </div>
                    </div>
                    
                    <div class="urgency-notice">
                        <strong>' . __('Urgent Action Required', 'smart-restock-waitlist') . '</strong><br>
                        ' . __('This product has customers waiting and needs to be restocked as soon as possible.', 'smart-restock-waitlist') . '
                    </div>
                    
                    {restock_link}
                </div>
                
                <div class="pro-features">
                    <h3>' . __('Pro Features Available', 'smart-restock-waitlist') . '</h3>
                    <ul>
                        <li>' . __('One-click restock via secure link', 'smart-restock-waitlist') . '</li>
                        <li>' . __('Automatic purchase order generation', 'smart-restock-waitlist') . '</li>
                        <li>' . __('Bulk restock via CSV upload', 'smart-restock-waitlist') . '</li>
                        <li>' . __('Multi-channel notifications (WhatsApp/SMS)', 'smart-restock-waitlist') . '</li>
                    </ul>
                    <p><a href="{site_url}/wp-admin/admin.php?page=smart-restock-waitlist-license" style="color: #0073aa;">' . __('Upgrade to Pro for advanced features', 'smart-restock-waitlist') . '</a></p>
                </div>
                
                <p>' . __('Please restock this product as soon as possible to meet customer demand.', 'smart-restock-waitlist') . '</p>
                
                <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
                <strong>{site_name}</strong></p>
            </div>
            
            <div class="footer">
                <p>' . sprintf(__('This email was sent from %s', 'smart-restock-waitlist'), '{site_name}') . '</p>
                <p><a href="{site_url}">' . __('Visit our website', 'smart-restock-waitlist') . '</a></p>
                
                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    ' . __('You received this email because you are listed as a supplier for this product. If you no longer wish to receive these notifications, please contact us.', 'smart-restock-waitlist') . '
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate restock link (Pro feature)
 */
function srwm_generate_restock_link($product_id, $supplier_email) {
    if (!class_exists('SRWM_License_Manager')) {
        return '';
    }
    
    $license_manager = SRWM_License_Manager::get_instance();
    if (!$license_manager->is_pro_active()) {
        return '';
    }
    
    if (class_exists('SRWM_Pro_Restock')) {
        $restock = SRWM_Pro_Restock::get_instance();
        $restock_url = $restock->generate_restock_link($product_id, $supplier_email);
        
        if ($restock_url) {
            return '<a href="' . esc_url($restock_url) . '" class="cta-button">' . __('One-Click Restock', 'smart-restock-waitlist') . '</a>';
        }
    }
    
    return '';
}

/**
 * Generate PO number (Pro feature)
 */
function srwm_generate_po_number($product_id) {
    if (!class_exists('SRWM_License_Manager')) {
        return '';
    }
    
    $license_manager = SRWM_License_Manager::get_instance();
    if (!$license_manager->is_pro_active()) {
        return '';
    }
    
    return 'PO-' . date('Y') . '-' . str_pad($product_id, 6, '0', STR_PAD_LEFT);
}

/**
 * Replace placeholders in template
 */
function srwm_replace_placeholders($template, $placeholders) {
    foreach ($placeholders as $placeholder => $value) {
        $template = str_replace($placeholder, $value, $template);
    }
    return $template;
}

/**
 * Get product image HTML
 */
function srwm_get_supplier_product_image_html($product) {
    $image_id = $product->get_image_id();
    if ($image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'medium');
        return '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product->get_name()) . '" class="product-image" style="max-width: 150px; height: auto; border-radius: 8px; margin-bottom: 15px;">';
    }
    return '';
}

/**
 * Get site logo HTML
 */
function srwm_get_supplier_site_logo_html() {
    $logo_url = get_option('srwm_site_logo', '');
    if ($logo_url) {
        return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-width: 150px; height: auto;">';
    }
    return '<h2 style="margin: 0; color: white;">' . get_bloginfo('name') . '</h2>';
}