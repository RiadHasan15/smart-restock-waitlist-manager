# Smart Restock & Waitlist Manager

A comprehensive WordPress plugin for WooCommerce that provides advanced restock management and customer waitlist functionality. Perfect for small shops, dropshipping businesses, and multi-supplier stores.

## 🚀 Features

### 🔓 Free Version
- **Customer Waitlist**: Show waitlist form on out-of-stock products
- **Waitlist Notifications**: Automatically notify customers when products are restocked
- **Basic Supplier Alerts**: Email notifications to suppliers when stock is low
- **Admin Dashboard**: Overview of active waitlists and supplier alerts
- **Email Templates**: Customizable email notifications

### 🔐 Pro Version
- **One-Click Supplier Restock**: Secure links for suppliers to restock without login
- **Multi-Channel Notifications**: Email, WhatsApp, and SMS notifications
- **Purchase Order Generation**: Automatic PDF purchase orders
- **CSV Upload**: Supplier bulk restock via CSV upload
- **Advanced Analytics**: Detailed waitlist and restock analytics
- **Stock Threshold Management**: Per-product and global stock thresholds

## 📋 Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 🛠️ Installation

1. **Upload the plugin** to your `/wp-content/plugins/smart-restock-waitlist-manager/` directory
2. **Activate the plugin** through the 'Plugins' menu in WordPress
3. **Configure settings** in WooCommerce → Restock Manager
4. **Set up supplier information** for products that need supplier notifications

## ⚙️ Configuration

### Basic Setup

1. Go to **WooCommerce → Restock Manager → Settings**
2. Enable waitlist functionality
3. Configure email templates
4. Set global stock thresholds

### Supplier Setup

1. Edit any product in WooCommerce
2. Scroll to the "Supplier Information" meta box
3. Add supplier email, name, and notification preferences
4. Set low stock threshold for the product

### Pro Features Setup

1. **One-Click Restock**: Generate secure restock links for suppliers
2. **Multi-Channel Notifications**: Configure WhatsApp and SMS settings
3. **Purchase Orders**: Set up company information for PO generation
4. **CSV Upload**: Configure upload settings and validation rules

## 📧 Email Templates

The plugin uses customizable email templates with placeholders:

### Customer Waitlist Email
- `{customer_name}` - Customer's name
- `{product_name}` - Product name
- `{product_url}` - Direct link to product
- `{site_name}` - Your website name

### Supplier Notification Email
- `{supplier_name}` - Supplier's name
- `{product_name}` - Product name
- `{sku}` - Product SKU
- `{current_stock}` - Current stock level
- `{waitlist_count}` - Number of customers waiting
- `{site_name}` - Your website name

## 🎯 Usage Examples

### For Store Owners

1. **Monitor Waitlists**: Check the dashboard for products with active waitlists
2. **Restock Products**: Use the one-click restock feature to add inventory
3. **View Analytics**: Track waitlist performance and supplier response times
4. **Export Data**: Download waitlist and restock data for analysis

### For Suppliers

1. **Receive Notifications**: Get alerted when products need restocking
2. **One-Click Restock**: Use secure links to restock products instantly
3. **Bulk Upload**: Upload CSV files for multiple product restocks
4. **View Purchase Orders**: Receive and review automatic purchase orders

## 🔧 Hooks and Filters

### Actions
```php
// Customer added to waitlist
do_action('srwm_customer_added_to_waitlist', $product_id, $email, $name);

// Product restocked
do_action('srwm_product_restocked', $product_id, $quantity, $method);

// Supplier notified
do_action('srwm_supplier_notified', $product_id, $supplier_data);
```

### Filters
```php
// Modify waitlist email template
add_filter('srwm_waitlist_email_template', 'custom_waitlist_template');

// Modify supplier notification template
add_filter('srwm_supplier_email_template', 'custom_supplier_template');

// Modify restock quantity
add_filter('srwm_restock_quantity', 'custom_restock_quantity', 10, 2);
```

## 📊 Database Tables

The plugin creates three main tables:

- `wp_srwm_waitlist` - Customer waitlist data
- `wp_srwm_suppliers` - Supplier information and settings
- `wp_srwm_restock_logs` - Restock activity logs

## 🔒 Security Features

- **Nonce Verification**: All AJAX requests are protected
- **Input Sanitization**: All user inputs are properly sanitized
- **Capability Checks**: Admin functions require proper permissions
- **Secure Tokens**: One-click restock links use secure, time-limited tokens
- **IP Logging**: All restock actions are logged with IP addresses

## 🌐 Multilingual Support

The plugin is translation-ready and includes:
- Text domain: `smart-restock-waitlist`
- Language files in `/languages/` directory
- RTL support for right-to-left languages

## 📱 Responsive Design

- Mobile-friendly waitlist forms
- Responsive admin dashboard
- Touch-friendly interface elements
- Optimized for all screen sizes

## 🔧 Troubleshooting

### Common Issues

1. **Waitlist form not showing**: Check if product is out of stock and waitlist is enabled
2. **Emails not sending**: Verify WordPress email configuration and check spam folders
3. **Supplier notifications not working**: Ensure supplier email is configured for the product
4. **Pro features not available**: Verify Pro version is properly installed and activated

### Debug Mode

Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📈 Performance Optimization

- Database queries are optimized with proper indexing
- AJAX requests are used for better user experience
- Email sending is handled asynchronously
- Caching is implemented for frequently accessed data

## 🔄 Updates and Maintenance

- Regular security updates
- Compatibility with latest WordPress and WooCommerce versions
- Performance improvements
- New features and enhancements

## 📞 Support

For support and documentation:
- **Documentation**: [Plugin Documentation](https://example.com/docs)
- **Support Forum**: [WordPress.org Support](https://wordpress.org/support/)
- **Email Support**: support@example.com

## 🤝 Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🙏 Credits

- Built with WordPress coding standards
- Uses WooCommerce hooks and filters
- Responsive design with modern CSS
- Accessibility compliant

## 📝 Changelog

### Version 1.0.0
- Initial release
- Basic waitlist functionality
- Supplier notifications
- Admin dashboard
- Email templates
- Pro features framework

---

**Smart Restock & Waitlist Manager** - Streamline your inventory management and never lose a sale again!