# Smart Restock & Waitlist Manager

A comprehensive WooCommerce plugin for managing customer waitlists and supplier restocking in a single workflow. Features both free and pro versions with advanced automation capabilities.

## 🎯 Overview

Smart Restock & Waitlist Manager helps store owners manage customer waitlists and supplier relationships efficiently. The plugin automatically notifies customers when products are restocked and alerts suppliers when stock is running low.

## 🔓 Free Version Features

### Customer Waitlist Management
- **Waitlist Forms**: Display on out-of-stock product pages
- **Customer Storage**: Store customer emails per product
- **Confirmation Messages**: Show success/error messages after signup
- **Waitlist Count Display**: Show how many people are waiting

### Automatic Notifications
- **Restock Notifications**: Automatically email waitlisted customers when stock is updated
- **WooCommerce Integration**: Uses default WooCommerce email templates
- **Customizable Templates**: Edit email content with placeholders

### Supplier Management
- **Supplier Assignment**: Assign one supplier email per product
- **Low Stock Alerts**: Notify suppliers when stock hits threshold
- **Email Notifications**: Send alerts via email (no login required)

### Admin Dashboard
- **Waitlist Overview**: View products with active waitlists
- **Customer Counts**: See how many customers are waiting per product
- **Basic Analytics**: Track waitlist and restock activities
- **Manual Restock**: Restock products and notify customers

## 🔐 Pro Version Features

### One-Click Supplier Restock
- **Secure Links**: Generate time-limited restock links for suppliers
- **No Login Required**: Suppliers can restock without WordPress access
- **Quick Options**: Preset quantities (+10, +25, +50) or custom amounts
- **Action Logging**: Track time, IP, and quantity for each restock

### Multi-Channel Notifications
- **Email Notifications**: Enhanced email templates with restock links
- **WhatsApp Integration**: Send alerts via WhatsApp Business API
- **SMS Notifications**: Integrate with Twilio/Nexmo for SMS alerts
- **Channel Preferences**: Set per-supplier notification preferences

### Advanced Automation
- **Automatic Purchase Orders**: Generate branded PDF POs when stock is low
- **CSV Bulk Upload**: Allow suppliers to upload CSV files for bulk restock
- **Stock Threshold Management**: Set global or per-product thresholds
- **Auto-disable Products**: Hide products when stock reaches zero

### Enhanced Analytics
- **Conversion Tracking**: Monitor waitlist-to-purchase conversion rates
- **Supplier Performance**: Track response times and restock frequency
- **Demand Analysis**: Identify products with highest waitlist demand
- **Export Reports**: Download analytics data as CSV files

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **WooCommerce**: 6.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher

## 🚀 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate Plugin**

### Method 2: FTP Upload
1. Extract the plugin ZIP file
2. Upload the `smart-restock-waitlist-manager` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin → Plugins** and activate the plugin

## ⚙️ Configuration

### Initial Setup
1. **Activate Plugin**: The plugin will automatically create required database tables
2. **Configure Settings**: Go to **Restock Manager → Settings**
3. **Enable Features**: Toggle waitlist and supplier notifications
4. **Set Thresholds**: Configure low stock alert levels

### License Activation (Pro Features)
1. **Get License**: Purchase a Pro license from the plugin website
2. **Activate License**: Go to **Restock Manager → License**
3. **Enter License Key**: Paste your license key and click **Activate**
4. **Verify Status**: Confirm Pro features are now available

### Product Configuration
1. **Edit Product**: Go to any WooCommerce product
2. **Supplier Meta Box**: Find the "Supplier Information" meta box
3. **Add Supplier**: Enter supplier email, name, and threshold
4. **Save Product**: The supplier will be notified when stock is low

## 📧 Email Templates

### Available Placeholders

#### Customer Waitlist Email
- `{customer_name}` - Customer's name
- `{product_name}` - Product name
- `{product_url}` - Direct link to product
- `{site_name}` - Your website name
- `{site_url}` - Your website URL

#### Supplier Notification Email
- `{supplier_name}` - Supplier's name
- `{product_name}` - Product name
- `{sku}` - Product SKU
- `{current_stock}` - Current stock quantity
- `{waitlist_count}` - Number of customers waiting
- `{site_name}` - Your website name
- `{site_url}` - Your website URL

#### Pro Placeholders (License Required)
- `{restock_link}` - One-click restock link
- `{po_number}` - Purchase order number

### Template Customization
1. Go to **Restock Manager → Settings**
2. Scroll to **Email Templates** section
3. Edit the templates using the available placeholders
4. Click **Save Changes**

## 🔧 Usage Examples

### Basic Waitlist Setup
```php
// Check if customer is on waitlist
$is_on_waitlist = SRWM_Waitlist::is_customer_on_waitlist($product_id, $email);

// Get waitlist count
$count = SRWM_Waitlist::get_waitlist_count($product_id);

// Add customer to waitlist
SRWM_Waitlist::add_customer($product_id, $email, $name);
```

### Manual Restock
```php
// Restock product and notify customers
SRWM_Waitlist::restock_and_notify($product_id, $quantity);
```

### Supplier Management
```php
// Get supplier data
$supplier = new SRWM_Supplier();
$supplier_data = $supplier->get_supplier_data($product_id);

// Check if Pro features are available
$license_manager = SRWM_License_Manager::get_instance();
if ($license_manager->is_pro_active()) {
    // Pro features available
}
```

## 🎨 Customization

### Hooks and Filters

#### Actions
```php
// Customer added to waitlist
do_action('srwm_customer_added_to_waitlist', $product_id, $email, $name);

// Product restocked
do_action('srwm_product_restocked', $product_id, $quantity, $customers);

// Supplier notified
do_action('srwm_supplier_notified', $product_id, $supplier_data);
```

#### Filters
```php
// Modify waitlist email subject
add_filter('srwm_waitlist_email_subject', function($subject) {
    return 'Custom Subject: ' . $subject;
});

// Modify supplier notification template
add_filter('srwm_supplier_email_template', function($template) {
    return $template . '<p>Custom content</p>';
});
```

### CSS Customization
```css
/* Custom waitlist form styling */
.srwm-waitlist-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

/* Custom admin dashboard styling */
.srwm-stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
```

## 📊 Database Schema

### Core Tables
```sql
-- Waitlist table
wp_srwm_waitlist (
    id, product_id, customer_email, customer_name, 
    date_added, notified
)

-- Supplier table
wp_srwm_suppliers (
    id, product_id, supplier_email, supplier_name, 
    notification_channels, threshold
)

-- Restock logs table
wp_srwm_restock_logs (
    id, product_id, supplier_id, quantity, 
    method, ip_address, timestamp
)
```

### Pro Tables (License Required)
```sql
-- Restock tokens table
wp_srwm_restock_tokens (
    id, token, product_id, supplier_email, 
    expires_at, used, used_at, created_at
)

-- CSV tokens table
wp_srwm_csv_tokens (
    id, token, supplier_email, expires_at, 
    used, used_at, created_at
)

-- Purchase orders table
wp_srwm_purchase_orders (
    id, po_number, product_id, supplier_email, 
    quantity, total_amount, status, created_at
)
```

## 🔒 Security Features

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Input Sanitization**: All user inputs are properly sanitized
- **Capability Checks**: Admin functions require proper permissions
- **Secure Tokens**: Pro features use cryptographically secure tokens
- **IP Logging**: Track restock actions for security monitoring

## 🌐 Multilingual Support

The plugin is translation-ready and includes:
- **Text Domain**: `smart-restock-waitlist`
- **POT File**: Available in `/languages/` directory
- **Translation Functions**: All strings use `__()` and `_e()`
- **RTL Support**: Compatible with right-to-left languages

## 📱 Responsive Design

- **Mobile-Friendly**: Works on all device sizes
- **Touch-Optimized**: Buttons and forms optimized for touch
- **Progressive Enhancement**: Core functionality works without JavaScript
- **Accessibility**: WCAG 2.1 AA compliant

## 🚨 Troubleshooting

### Common Issues

#### Waitlist Form Not Showing
- Check if product is out of stock
- Verify waitlist is enabled in settings
- Ensure WooCommerce is active

#### Emails Not Sending
- Check WordPress email configuration
- Verify SMTP settings if using external provider
- Check spam/junk folders

#### Pro Features Not Working
- Verify license is activated
- Check license status in admin
- Ensure license key is valid

#### Database Errors
- Check MySQL version compatibility
- Verify database permissions
- Try deactivating and reactivating plugin

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📈 Performance Optimization

### Database Optimization
- **Indexed Queries**: All database queries use proper indexes
- **Efficient Joins**: Optimized table joins for better performance
- **Query Caching**: Results cached where appropriate

### Frontend Optimization
- **Minified Assets**: CSS and JS files are minified
- **Lazy Loading**: Images and heavy content load on demand
- **CDN Ready**: Assets can be served from CDN

### Caching Compatibility
- **WooCommerce Cache**: Compatible with WooCommerce caching
- **Page Caching**: Works with popular page caching plugins
- **Object Caching**: Compatible with Redis/Memcached

## 🔄 Updates

### Automatic Updates
- **WordPress.org**: Free version updates via WordPress admin
- **Pro Updates**: Automatic updates for valid licenses
- **Backup Recommended**: Always backup before updating

### Update Process
1. **Backup**: Create full site backup
2. **Test**: Test updates on staging site first
3. **Update**: Run update in WordPress admin
4. **Verify**: Check all functionality works

## 📞 Support

### Free Support
- **WordPress.org Forums**: Community support for free version
- **Documentation**: Comprehensive documentation available
- **FAQ**: Common questions and answers

### Pro Support
- **Priority Support**: Faster response times for Pro users
- **Email Support**: Direct email support
- **Live Chat**: Available during business hours
- **Video Tutorials**: Step-by-step video guides

## 📝 Changelog

### Version 1.0.0
- Initial release
- Free version with basic waitlist functionality
- Pro version with advanced features
- License management system
- Comprehensive admin dashboard
- Email template system
- Analytics and reporting

## 📄 License

- **Free Version**: GPL v2 or later
- **Pro Version**: Commercial license
- **Source Code**: Available on GitHub
- **Contributions**: Welcome via pull requests

## 🤝 Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📞 Contact

- **Website**: [Plugin Website](https://example.com)
- **Email**: support@example.com
- **Twitter**: [@PluginHandle](https://twitter.com/PluginHandle)
- **GitHub**: [Repository](https://github.com/username/smart-restock-waitlist-manager)

---

**Smart Restock & Waitlist Manager** - Streamline your inventory management and customer satisfaction with intelligent waitlist and supplier automation.