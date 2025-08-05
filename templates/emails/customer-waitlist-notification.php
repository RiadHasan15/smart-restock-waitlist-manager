<?php
/**
 * Customer Waitlist Notification Email Template
 * 
 * Template for notifying customers when a product is back in stock.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get customer waitlist notification email template
 */
function srwm_get_customer_waitlist_template($customer, $product) {
    $template = get_option('srwm_email_template_waitlist', srwm_get_default_waitlist_template());
    
    $placeholders = array(
        '{customer_name}' => $customer->customer_name ?: __('Customer', 'smart-restock-waitlist'),
        '{product_name}' => $product->get_name(),
        '{product_url}' => $product->get_permalink(),
        '{product_image}' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
        '{product_price}' => $product->get_price_html(),
        '{product_sku}' => $product->get_sku(),
        '{site_name}' => get_bloginfo('name'),
        '{site_url}' => get_site_url(),
        '{site_logo}' => get_option('srwm_site_logo', ''),
        '{current_date}' => date_i18n(get_option('date_format')),
        '{expiry_date}' => date_i18n(get_option('date_format'), strtotime('+7 days'))
    );
    
    return srwm_replace_placeholders($template, $placeholders);
}

/**
 * Get default waitlist email template
 */
function srwm_get_default_waitlist_template() {
    return '
    <!DOCTYPE html>
    <html lang="' . get_locale() . '">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . __('Product Back in Stock', 'smart-restock-waitlist') . '</title>
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
                background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
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
            .product-section {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 25px;
                margin: 25px 0;
                text-align: center;
            }
            .product-image {
                max-width: 200px;
                height: auto;
                border-radius: 8px;
                margin-bottom: 15px;
            }
            .product-name {
                font-size: 20px;
                font-weight: bold;
                color: #0073aa;
                margin-bottom: 10px;
            }
            .product-price {
                font-size: 18px;
                color: #28a745;
                font-weight: bold;
                margin-bottom: 15px;
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
            .social-links {
                margin-top: 20px;
            }
            .social-links a {
                display: inline-block;
                margin: 0 10px;
                color: #0073aa;
                text-decoration: none;
            }
            @media only screen and (max-width: 600px) {
                .email-container {
                    width: 100% !important;
                }
                .header, .content, .footer {
                    padding: 20px !important;
                }
                .product-section {
                    padding: 20px !important;
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
                <h1>' . __('Great News!', 'smart-restock-waitlist') . '</h1>
                <p>' . __('Your waitlisted product is back in stock', 'smart-restock-waitlist') . '</p>
            </div>
            
            <div class="content">
                <p>' . __('Hi {customer_name},', 'smart-restock-waitlist') . '</p>
                
                <p>' . __('We\'re excited to let you know that {product_name} is back in stock and ready for purchase!', 'smart-restock-waitlist') . '</p>
                
                <div class="product-section">
                    {product_image}
                    <div class="product-name">{product_name}</div>
                    <div class="product-price">{product_price}</div>
                    <a href="{product_url}" class="cta-button">' . __('Buy Now', 'smart-restock-waitlist') . '</a>
                </div>
                
                <div class="urgency-notice">
                    <strong>' . __('Don\'t miss out!', 'smart-restock-waitlist') . '</strong><br>
                    ' . __('This product was in high demand, so we recommend purchasing soon to avoid missing out again.', 'smart-restock-waitlist') . '
                </div>
                
                <p>' . __('If you have any questions or need assistance, please don\'t hesitate to contact us.', 'smart-restock-waitlist') . '</p>
                
                <p>' . __('Best regards,', 'smart-restock-waitlist') . '<br>
                <strong>{site_name}</strong></p>
            </div>
            
            <div class="footer">
                <p>' . sprintf(__('This email was sent from %s', 'smart-restock-waitlist'), '{site_name}') . '</p>
                <p><a href="{site_url}">' . __('Visit our website', 'smart-restock-waitlist') . '</a></p>
                
                <div class="social-links">
                    <a href="#">' . __('Facebook', 'smart-restock-waitlist') . '</a> |
                    <a href="#">' . __('Twitter', 'smart-restock-waitlist') . '</a> |
                    <a href="#">' . __('Instagram', 'smart-restock-waitlist') . '</a>
                </div>
                
                <p style="margin-top: 20px; font-size: 12px; color: #999;">
                    ' . __('You received this email because you joined the waitlist for this product. If you no longer wish to receive these notifications, please contact us.', 'smart-restock-waitlist') . '
                </p>
            </div>
        </div>
    </body>
    </html>';
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
function srwm_get_product_image_html($product) {
    $image_id = $product->get_image_id();
    if ($image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'medium');
        return '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($product->get_name()) . '" class="product-image" style="max-width: 200px; height: auto; border-radius: 8px; margin-bottom: 15px;">';
    }
    return '';
}

/**
 * Get site logo HTML
 */
function srwm_get_site_logo_html() {
    $logo_url = get_option('srwm_site_logo', '');
    if ($logo_url) {
        return '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-width: 150px; height: auto;">';
    }
    return '<h2 style="margin: 0; color: white;">' . get_bloginfo('name') . '</h2>';
}