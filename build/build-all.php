<?php
/**
 * Master Build Script
 * 
 * This script builds both Free and Pro versions of the plugin.
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

// Set up basic WordPress environment for CLI
if (defined('CLI_SCRIPT')) {
    require_once __DIR__ . '/../wp-config.php';
    require_once ABSPATH . 'wp-includes/load.php';
    require_once ABSPATH . 'wp-includes/plugin.php';
}

/**
 * Master Builder Class
 */
class SRWM_Master_Builder {
    
    private $build_dir;
    private $start_time;
    
    public function __construct() {
        $this->build_dir = dirname(__DIR__) . '/build';
        $this->start_time = microtime(true);
    }
    
    /**
     * Build both versions
     */
    public function build_all() {
        echo "Smart Restock & Waitlist Manager - Master Build\n";
        echo "==============================================\n\n";
        
        // Create build directory
        $this->create_build_directory();
        
        // Build Free version
        $this->build_free_version();
        
        // Build Pro version
        $this->build_pro_version();
        
        // Create documentation
        $this->create_documentation();
        
        // Show build summary
        $this->show_build_summary();
    }
    
    /**
     * Create build directory
     */
    private function create_build_directory() {
        if (!is_dir($this->build_dir)) {
            mkdir($this->build_dir, 0755, true);
        }
        echo "✓ Build directory ready\n";
    }
    
    /**
     * Build Free version
     */
    private function build_free_version() {
        echo "\n--- Building Free Version ---\n";
        
        require_once __DIR__ . '/free-version.php';
        $free_builder = new SRWM_Free_Version_Builder();
        $free_builder->build();
        
        echo "✓ Free version completed\n";
    }
    
    /**
     * Build Pro version
     */
    private function build_pro_version() {
        echo "\n--- Building Pro Version ---\n";
        
        require_once __DIR__ . '/pro-version.php';
        $pro_builder = new SRWM_Pro_Version_Builder();
        $pro_builder->build();
        
        echo "✓ Pro version completed\n";
    }
    
    /**
     * Create documentation
     */
    private function create_documentation() {
        echo "\n--- Creating Documentation ---\n";
        
        $this->create_build_readme();
        $this->create_changelog();
        $this->create_installation_guide();
        
        echo "✓ Documentation completed\n";
    }
    
    /**
     * Create build README
     */
    private function create_build_readme() {
        $readme = "# Smart Restock & Waitlist Manager - Build Output\n\n";
        $readme .= "This directory contains the built versions of the Smart Restock & Waitlist Manager plugin.\n\n";
        $readme .= "## Files\n\n";
        $readme .= "- `free-version.zip` - Free version for WordPress.org submission\n";
        $readme .= "- `pro-version.zip` - Pro version with license validation\n";
        $readme .= "- `schema.sql` - Database schema for manual installation\n";
        $readme .= "- `README.md` - This file\n";
        $readme .= "- `CHANGELOG.md` - Version history and changes\n";
        $readme .= "- `INSTALLATION.md` - Installation and setup guide\n\n";
        $readme .= "## Build Information\n\n";
        $readme .= "- **Build Date:** " . date('Y-m-d H:i:s') . "\n";
        $readme .= "- **Build Time:** " . round(microtime(true) - $this->start_time, 2) . " seconds\n";
        $readme .= "- **PHP Version:** " . PHP_VERSION . "\n";
        $readme .= "- **WordPress Version:** " . get_bloginfo('version') . "\n\n";
        $readme .= "## Version Information\n\n";
        $readme .= "### Free Version\n";
        $readme .= "- Basic waitlist functionality\n";
        $readme .= "- Email notifications\n";
        $readme .= "- Basic supplier alerts\n";
        $readme .= "- Simple admin dashboard\n\n";
        $readme .= "### Pro Version\n";
        $readme .= "- All free features\n";
        $readme .= "- One-click supplier restock\n";
        $readme .= "- Multi-channel notifications (WhatsApp/SMS)\n";
        $readme .= "- Automatic purchase order generation\n";
        $readme .= "- CSV upload for bulk restock\n";
        $readme .= "- Advanced analytics\n";
        $readme .= "- License validation\n\n";
        $readme .= "## Distribution\n\n";
        $readme .= "1. **Free Version**: Submit to WordPress.org plugin repository\n";
        $readme .= "2. **Pro Version**: Distribute through your own website\n";
        $readme .= "3. **Documentation**: Include with both versions\n\n";
        $readme .= "## Support\n\n";
        $readme .= "For support and updates, visit the plugin website or contact the development team.\n";
        
        file_put_contents($this->build_dir . '/README.md', $readme);
    }
    
    /**
     * Create changelog
     */
    private function create_changelog() {
        $changelog = "# Changelog\n\n";
        $changelog .= "All notable changes to Smart Restock & Waitlist Manager will be documented in this file.\n\n";
        $changelog .= "## [1.0.0] - " . date('Y-m-d') . "\n\n";
        $changelog .= "### Added\n";
        $changelog .= "- Initial release\n";
        $changelog .= "- Customer waitlist functionality\n";
        $changelog .= "- Supplier notification system\n";
        $changelog .= "- Basic admin dashboard\n";
        $changelog .= "- Email templates\n";
        $changelog .= "- Analytics and reporting\n";
        $changelog .= "- License management system\n";
        $changelog .= "- Pro features (one-click restock, multi-channel notifications, etc.)\n";
        $changelog .= "- Translation support\n";
        $changelog .= "- Responsive design\n\n";
        $changelog .= "### Technical\n";
        $changelog .= "- WordPress coding standards compliance\n";
        $changelog .= "- WooCommerce 6.0+ compatibility\n";
        $changelog .= "- Security best practices\n";
        $changelog .= "- Performance optimization\n";
        $changelog .= "- Database optimization\n\n";
        $changelog .= "### Files\n";
        $changelog .= "- Complete plugin structure\n";
        $changelog .= "- Admin interface\n";
        $changelog .= "- Frontend integration\n";
        $changelog .= "- Email templates\n";
        $changelog .= "- JavaScript and CSS assets\n";
        $changelog .= "- Database schema\n";
        $changelog .= "- Build scripts\n";
        $changelog .= "- Documentation\n\n";
        
        file_put_contents($this->build_dir . '/CHANGELOG.md', $changelog);
    }
    
    /**
     * Create installation guide
     */
    private function create_installation_guide() {
        $guide = "# Installation Guide\n\n";
        $guide .= "This guide will help you install and configure Smart Restock & Waitlist Manager.\n\n";
        $guide .= "## Requirements\n\n";
        $guide .= "- WordPress 5.0 or higher\n";
        $guide .= "- WooCommerce 6.0 or higher\n";
        $guide .= "- PHP 7.4 or higher\n";
        $guide .= "- MySQL 5.6 or higher\n\n";
        $guide .= "## Installation\n\n";
        $guide .= "### Method 1: WordPress Admin (Recommended)\n\n";
        $guide .= "1. Download the plugin ZIP file\n";
        $guide .= "2. Go to WordPress Admin → Plugins → Add New\n";
        $guide .= "3. Click \"Upload Plugin\"\n";
        $guide .= "4. Choose the ZIP file and click \"Install Now\"\n";
        $guide .= "5. Activate the plugin\n\n";
        $guide .= "### Method 2: FTP Upload\n\n";
        $guide .= "1. Extract the ZIP file\n";
        $guide .= "2. Upload the plugin folder to `/wp-content/plugins/`\n";
        $guide .= "3. Go to WordPress Admin → Plugins\n";
        $guide .= "4. Activate \"Smart Restock & Waitlist Manager\"\n\n";
        $guide .= "## Database Setup\n\n";
        $guide .= "The plugin will automatically create the required database tables on activation.\n";
        $guide .= "If you need to manually create the tables, use the SQL schema in `schema.sql`.\n\n";
        $guide .= "## Configuration\n\n";
        $guide .= "### Basic Setup\n\n";
        $guide .= "1. Go to **Smart Restock & Waitlist** in your admin menu\n";
        $guide .= "2. Configure general settings\n";
        $guide .= "3. Set up supplier information for products\n";
        $guide .= "4. Customize email templates\n\n";
        $guide .= "### Pro Features (Pro Version Only)\n\n";
        $guide .= "1. Go to **Smart Restock & Waitlist → License**\n";
        $guide .= "2. Enter your Pro license key\n";
        $guide .= "3. Activate the license\n";
        $guide .= "4. Configure Pro-specific settings\n\n";
        $guide .= "## Usage\n\n";
        $guide .= "### For Store Owners\n\n";
        $guide .= "1. **Dashboard**: Monitor waitlists and restock activities\n";
        $guide .= "2. **Settings**: Configure notifications and thresholds\n";
        $guide .= "3. **Analytics**: View performance metrics\n";
        $guide .= "4. **Products**: Manage supplier information per product\n\n";
        $guide .= "### For Customers\n\n";
        $guide .= "1. Visit out-of-stock product pages\n";
        $guide .= "2. Join the waitlist with name and email\n";
        $guide .= "3. Receive notification when product is restocked\n\n";
        $guide .= "### For Suppliers (Pro Version)\n\n";
        $guide .= "1. Receive low stock notifications\n";
        $guide .= "2. Use one-click restock links\n";
        $guide .= "3. Upload CSV files for bulk restock\n";
        $guide .= "4. Receive automatic purchase orders\n\n";
        $guide .= "## Troubleshooting\n\n";
        $guide .= "### Common Issues\n\n";
        $guide .= "**Plugin not activating**\n";
        $guide .= "- Ensure WooCommerce is installed and activated\n";
        $guide .= "- Check PHP version compatibility\n";
        $guide .= "- Verify file permissions\n\n";
        $guide .= "**Emails not sending**\n";
        $guide .= "- Check WordPress email configuration\n";
        $guide .= "- Verify SMTP settings if using custom email\n";
        $guide .= "- Check spam/junk folders\n\n";
        $guide .= "**Pro features not working**\n";
        $guide .= "- Verify license is activated\n";
        $guide .= "- Check license server connectivity\n";
        $guide .= "- Ensure Pro version is installed\n\n";
        $guide .= "## Support\n\n";
        $guide .= "For additional support:\n";
        $guide .= "- Check the plugin documentation\n";
        $guide .= "- Visit the support forum\n";
        $guide .= "- Contact the development team\n\n";
        $guide .= "## Updates\n\n";
        $guide .= "The plugin will notify you of available updates in the WordPress admin.\n";
        $guide .= "Always backup your site before updating.\n\n";
        
        file_put_contents($this->build_dir . '/INSTALLATION.md', $guide);
    }
    
    /**
     * Show build summary
     */
    private function show_build_summary() {
        $build_time = round(microtime(true) - $this->start_time, 2);
        
        echo "\n==============================================\n";
        echo "Build Summary\n";
        echo "==============================================\n";
        echo "✓ Free version: " . $this->build_dir . "/free-version.zip\n";
        echo "✓ Pro version: " . $this->build_dir . "/pro-version.zip\n";
        echo "✓ Documentation: " . $this->build_dir . "/\n";
        echo "✓ Build time: {$build_time} seconds\n";
        echo "✓ Status: Complete\n\n";
        
        echo "Next Steps:\n";
        echo "1. Test both versions in a staging environment\n";
        echo "2. Submit free version to WordPress.org\n";
        echo "3. Set up license server for Pro version\n";
        echo "4. Deploy to production\n\n";
    }
}

// Run the build if called directly
if (defined('CLI_SCRIPT')) {
    $builder = new SRWM_Master_Builder();
    $builder->build_all();
}