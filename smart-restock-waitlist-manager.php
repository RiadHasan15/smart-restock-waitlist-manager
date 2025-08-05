<?php
/**
 * Plugin Name: Smart Restock & Waitlist Manager
 * Plugin URI: https://yourwebsite.com/smart-restock-waitlist-manager
 * Description: Advanced WooCommerce plugin for managing product restocks and customer waitlists with supplier notifications, analytics, and Pro features.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: smart-restock-waitlist
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SRWM_VERSION', '1.0.0');
define('SRWM_PLUGIN_FILE', __FILE__);
define('SRWM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRWM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRWM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * License Manager Class
 * Handles license activation, validation, and Pro feature management
 */
class SRWM_License_Manager {
    
    private $plugin_slug = 'smart-restock-waitlist-manager';
    
    public function __construct() {
        add_action('admin_init', array($this, 'handle_license_actions'));
        add_action('admin_notices', array($this, 'show_license_notices'));
    }
    

    
    /**
     * Add license menu to admin
     */
    public function add_license_menu() {
        add_submenu_page(
            'smart-restock-waitlist',
            __('License', 'smart-restock-waitlist'),
            __('License', 'smart-restock-waitlist'),
            'manage_woocommerce',
            'smart-restock-waitlist-license',
            array($this, 'render_license_page')
        );
    }
    
    /**
     * Handle license actions (activate, deactivate, check)
     */
    public function handle_license_actions() {
        if (!isset($_POST['srwm_license_action']) || !wp_verify_nonce($_POST['srwm_license_nonce'], 'srwm_license_nonce')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['srwm_license_action']);
        
        switch ($action) {
            case 'activate':
                $this->activate_license();
                break;
            case 'deactivate':
                $this->deactivate_license();
                break;
            case 'check':
                $this->check_license_status();
                break;
        }
    }
    
    /**
     * Activate license
     */
    private function activate_license() {
        if (empty($_POST['license_key'])) {
            $this->add_notice('error', __('Please enter a license key.', 'smart-restock-waitlist'));
            return;
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        
        // Always activate successfully for local testing
        update_option($this->plugin_slug . '_license_key', $license_key);
        update_option($this->plugin_slug . '_license_status', 'valid');
        update_option($this->plugin_slug . '_license_last_check', time());
        
        $this->add_notice('success', __('License activated successfully! Pro features are now enabled. Please refresh the page to see the changes.', 'smart-restock-waitlist'));
        
        // Force page reload to update admin menu
        add_action('admin_footer', function() {
            echo '<script>
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            </script>';
        });
    }
    

    
    /**
     * Deactivate license
     */
    private function deactivate_license() {
        delete_option($this->plugin_slug . '_license_key');
        update_option($this->plugin_slug . '_license_status', 'inactive');
        update_option($this->plugin_slug . '_license_last_check', time());
        
        $this->add_notice('success', __('License deactivated successfully! Pro features are now disabled. Please refresh the page to see the changes.', 'smart-restock-waitlist'));
        
        // Force page reload to update admin menu
        add_action('admin_footer', function() {
            echo '<script>
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            </script>';
        });
    }
    

    
    /**
     * Check license status
     */
    private function check_license_status() {
        $license_key = get_option($this->plugin_slug . '_license_key');
        
        if (empty($license_key)) {
            $this->add_notice('error', __('No license key found.', 'smart-restock-waitlist'));
            return;
        }
        
        update_option($this->plugin_slug . '_license_status', 'valid');
        update_option($this->plugin_slug . '_license_last_check', time());
        
        $this->add_notice('success', __('License is valid and active!', 'smart-restock-waitlist'));
    }
    
    /**
     * Render license page
     */
    public function render_license_page() {
        $license_key = get_option($this->plugin_slug . '_license_key', '');
        $status = get_option($this->plugin_slug . '_license_status', 'inactive');
        $last_check = get_option($this->plugin_slug . '_license_last_check', 0);
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Restock & Waitlist Manager - License', 'smart-restock-waitlist'); ?></h1>
            
            <div class="srwm-license-container">
                <!-- License Management Section -->
                <div class="srwm-license-card">
                    <div class="srwm-license-header">
                        <h2><?php _e('License Management', 'smart-restock-waitlist'); ?></h2>
                        <div class="srwm-license-badge <?php echo $status == 'valid' ? 'active' : 'inactive'; ?>">
                            <?php if ($status == 'valid'): ?>
                                <span class="srwm-badge-icon">✓</span>
                                <?php _e('Pro Active', 'smart-restock-waitlist'); ?>
                            <?php else: ?>
                                <span class="srwm-badge-icon">⚡</span>
                                <?php _e('Free Version', 'smart-restock-waitlist'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="srwm-license-notice">
                        <div class="srwm-notice-icon">💡</div>
                        <div class="srwm-notice-content">
                            <strong><?php _e('Local Testing Mode:', 'smart-restock-waitlist'); ?></strong>
                            <?php _e('This is a dummy license system for local testing. Any license key will work.', 'smart-restock-waitlist'); ?>
                        </div>
                    </div>
                    
                    <form method="post" action="" class="srwm-license-form">
                        <?php wp_nonce_field('srwm_license_nonce', 'srwm_license_nonce'); ?>
                        
                        <div class="srwm-form-group">
                            <label for="license_key"><?php _e('License Key', 'smart-restock-waitlist'); ?></label>
                            <input type="text" id="license_key" name="license_key" value="<?php echo esc_attr($license_key); ?>" 
                                   placeholder="<?php _e('Enter any license key for testing', 'smart-restock-waitlist'); ?>" />
                            <small><?php _e('For local testing, any license key will work.', 'smart-restock-waitlist'); ?></small>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label><?php _e('Status', 'smart-restock-waitlist'); ?></label>
                            <div class="srwm-status-display">
                                <?php if ($status == 'valid'): ?>
                                    <span class="srwm-status-active">✓ <?php _e('Active', 'smart-restock-waitlist'); ?></span>
                                    <p><?php _e('Your license is active and Pro features are enabled.', 'smart-restock-waitlist'); ?></p>
                                <?php else: ?>
                                    <span class="srwm-status-inactive">✗ <?php _e('Inactive', 'smart-restock-waitlist'); ?></span>
                                    <p><?php _e('Pro features are disabled. Enter any license key and activate to enable Pro features.', 'smart-restock-waitlist'); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($last_check): ?>
                                    <small><?php _e('Last checked:', 'smart-restock-waitlist'); ?> <?php echo date('Y-m-d H:i:s', $last_check); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="srwm-form-group">
                            <label><?php _e('Current Version', 'smart-restock-waitlist'); ?></label>
                            <span class="srwm-version"><?php echo esc_html(SRWM_VERSION); ?></span>
                        </div>
                        
                        <div class="srwm-form-actions">
                            <?php if ($status == 'valid'): ?>
                                <button type="submit" name="srwm_license_action" value="deactivate" class="srwm-btn srwm-btn-secondary">
                                    <?php _e('Deactivate License', 'smart-restock-waitlist'); ?>
                                </button>
                                <button type="submit" name="srwm_license_action" value="check" class="srwm-btn srwm-btn-outline">
                                    <?php _e('Check Status', 'smart-restock-waitlist'); ?>
                                </button>
                            <?php else: ?>
                                <button type="submit" name="srwm_license_action" value="activate" class="srwm-btn srwm-btn-primary">
                                    <?php _e('Activate License', 'smart-restock-waitlist'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Pro Features Preview Section -->
                <div class="srwm-pro-preview">
                    <div class="srwm-pro-header">
                        <h2><?php _e('Pro Features Preview', 'smart-restock-waitlist'); ?></h2>
                        <p><?php _e('Unlock these powerful features with your Pro license', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="srwm-features-grid">
                        <!-- One-Click Restock -->
                        <div class="srwm-feature-card">
                            <div class="srwm-feature-icon">🚀</div>
                            <div class="srwm-feature-content">
                                <h3><?php _e('One-Click Restock', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Generate secure restock links for suppliers. No login required - suppliers click and update stock instantly.', 'smart-restock-waitlist'); ?></p>
                                <ul class="srwm-feature-list">
                                    <li><?php _e('Secure token-based links', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('No supplier login required', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Instant stock updates', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Activity logging & tracking', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Multi-Channel Notifications -->
                        <div class="srwm-feature-card">
                            <div class="srwm-feature-icon">📱</div>
                            <div class="srwm-feature-content">
                                <h3><?php _e('Multi-Channel Notifications', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Reach suppliers through multiple channels: Email, WhatsApp, and SMS notifications.', 'smart-restock-waitlist'); ?></p>
                                <ul class="srwm-feature-list">
                                    <li><?php _e('Email notifications', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('WhatsApp integration', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('SMS alerts (Twilio/Nexmo)', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Customizable templates', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Purchase Orders -->
                        <div class="srwm-feature-card">
                            <div class="srwm-feature-icon">📋</div>
                            <div class="srwm-feature-content">
                                <h3><?php _e('Automatic Purchase Orders', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Generate professional PDF purchase orders automatically when stock hits threshold levels.', 'smart-restock-waitlist'); ?></p>
                                <ul class="srwm-feature-list">
                                    <li><?php _e('PDF generation', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Branded templates', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Auto-email to suppliers', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Order tracking', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- CSV Upload -->
                        <div class="srwm-feature-card">
                            <div class="srwm-feature-icon">📊</div>
                            <div class="srwm-feature-content">
                                <h3><?php _e('CSV Bulk Upload', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Allow suppliers to upload CSV files for bulk stock updates across multiple products.', 'smart-restock-waitlist'); ?></p>
                                <ul class="srwm-feature-list">
                                    <li><?php _e('Secure upload links', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('CSV validation', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Bulk stock updates', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Error handling', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Stock Thresholds -->
                        <div class="srwm-feature-card">
                            <div class="srwm-feature-icon">⚖️</div>
                            <div class="srwm-feature-content">
                                <h3><?php _e('Stock Threshold Management', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Set custom stock thresholds per product and receive alerts when levels drop below minimum.', 'smart-restock-waitlist'); ?></p>
                                <ul class="srwm-feature-list">
                                    <li><?php _e('Per-product thresholds', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Global defaults', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Automatic alerts', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Threshold analytics', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Advanced Analytics -->
                        <div class="srwm-feature-card">
                            <div class="srwm-feature-icon">📈</div>
                            <div class="srwm-feature-content">
                                <h3><?php _e('Advanced Analytics', 'smart-restock-waitlist'); ?></h3>
                                <p><?php _e('Comprehensive analytics and reporting for supplier performance and restock efficiency.', 'smart-restock-waitlist'); ?></p>
                                <ul class="srwm-feature-list">
                                    <li><?php _e('Supplier performance', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Restock time analytics', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Conversion tracking', 'smart-restock-waitlist'); ?></li>
                                    <li><?php _e('Exportable reports', 'smart-restock-waitlist'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Call to Action -->
                    <div class="srwm-cta-section">
                        <div class="srwm-cta-content">
                            <h3><?php _e('Ready to Upgrade?', 'smart-restock-waitlist'); ?></h3>
                            <p><?php _e('Get access to all Pro features and take your waitlist management to the next level.', 'smart-restock-waitlist'); ?></p>
                            <div class="srwm-cta-buttons">
                                <a href="#" class="srwm-btn srwm-btn-primary srwm-btn-large">
                                    <?php _e('Get Pro License', 'smart-restock-waitlist'); ?>
                                </a>
                                <a href="#" class="srwm-btn srwm-btn-secondary srwm-btn-large">
                                    <?php _e('View Demo', 'smart-restock-waitlist'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        /* Modern License Page Styles */
        .srwm-license-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        /* License Management Card */
        .srwm-license-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            height: fit-content;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-license-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .srwm-license-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .srwm-license-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .srwm-license-badge.active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .srwm-license-badge.inactive {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }
        
        .srwm-badge-icon {
            font-size: 16px;
        }
        
        /* License Notice */
        .srwm-license-notice {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 25px;
        }
        
        .srwm-notice-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .srwm-notice-content {
            font-size: 14px;
            line-height: 1.5;
            color: #1e40af;
        }
        
        /* Form Styles */
        .srwm-license-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .srwm-form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .srwm-form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .srwm-form-group input {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .srwm-form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .srwm-form-group small {
            color: #6b7280;
            font-size: 12px;
        }
        
        .srwm-status-display {
            padding: 12px 16px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-status-active {
            color: #059669;
            font-weight: 600;
            font-size: 14px;
        }
        
        .srwm-status-inactive {
            color: #dc2626;
            font-weight: 600;
            font-size: 14px;
        }
        
        .srwm-status-display p {
            margin: 8px 0 0 0;
            color: #6b7280;
            font-size: 13px;
        }
        
        .srwm-status-display small {
            color: #9ca3af;
            font-size: 11px;
        }
        
        .srwm-version {
            font-family: 'Monaco', 'Menlo', monospace;
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            color: #374151;
        }
        
        /* Button Styles */
        .srwm-form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .srwm-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }
        
        .srwm-btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .srwm-btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .srwm-btn-secondary {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
        }
        
        .srwm-btn-secondary:hover {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        
        .srwm-btn-outline {
            background: transparent;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }
        
        .srwm-btn-outline:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .srwm-btn-large {
            padding: 14px 28px;
            font-size: 16px;
            min-height: 48px;
        }
        
        /* Pro Features Preview */
        .srwm-pro-preview {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        
        .srwm-pro-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }
        
        .srwm-pro-header h2 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: #1f2937;
            line-height: 1.2;
            word-wrap: break-word;
        }
        
        .srwm-pro-header p {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }
        
        /* Features Grid */
        .srwm-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .srwm-feature-card {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .srwm-feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .srwm-feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
            border-color: #cbd5e1;
        }
        
        .srwm-feature-icon {
            font-size: 32px;
            margin-bottom: 16px;
            display: block;
        }
        
        .srwm-feature-content h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 12px 0;
        }
        
        .srwm-feature-content p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        
        .srwm-feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .srwm-feature-list li {
            color: #4b5563;
            font-size: 13px;
            padding: 4px 0;
            position: relative;
            padding-left: 20px;
        }
        
        .srwm-feature-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        /* Call to Action */
        .srwm-cta-section {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: #1f2937;
        }
        
        .srwm-cta-content h3 {
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #1f2937;
        }
        
        .srwm-cta-content p {
            font-size: 16px;
            color: #6b7280;
            margin: 0 0 30px 0;
        }
        
        .srwm-cta-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .srwm-license-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .srwm-features-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .srwm-license-container {
                padding: 0 10px;
            }
            
            .srwm-license-card,
            .srwm-pro-preview {
                padding: 20px;
            }
            
            .srwm-features-grid {
                grid-template-columns: 1fr;
            }
            
            .srwm-form-actions {
                flex-direction: column;
            }
            
            .srwm-cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .srwm-license-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Show admin notices
     */
    public function show_license_notices() {
        $notices = get_option('srwm_license_notices', array());
        
        foreach ($notices as $notice) {
            $class = 'notice notice-' . $notice['type'];
            $message = $notice['message'];
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        }
        
        delete_option('srwm_license_notices');
    }
    
    /**
     * Add notice
     */
    private function add_notice($type, $message) {
        $notices = get_option('srwm_license_notices', array());
        $notices[] = array('type' => $type, 'message' => $message);
        update_option('srwm_license_notices', $notices);
    }
    
    /**
     * Check if Pro license is active
     */
    public function is_pro_active() {
        $status = get_option($this->plugin_slug . '_license_status', 'inactive');
        return $status === 'valid';
    }
    
    /**
     * Get license key
     */
    public function get_license_key() {
        return get_option($this->plugin_slug . '_license_key', '');
    }
    
    /**
     * Get license status
     */
    public function get_license_status() {
        return get_option($this->plugin_slug . '_license_status', 'inactive');
    }
    
    /**
     * Debug method to check license data
     */

    

    

    

    

}

/**
 * Main Plugin Class
 */
class SmartRestockWaitlistManager {
    
    private $license_manager;
    
    public function __construct() {
        // Initialize license manager
        $this->license_manager = new SRWM_License_Manager();
        
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Create database tables
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Load core classes
        $this->load_core_classes();
        
        // Load Pro classes if license is active
        $this->maybe_load_pro_classes();
        
        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize frontend
        $this->init_frontend();
        
        // Add AJAX handlers
        $this->add_ajax_handlers();
    }
    
    /**
     * Check and load Pro classes if license is active
     */
    private function maybe_load_pro_classes() {
        if ($this->license_manager->is_pro_active()) {
            $this->load_pro_classes();
        }
    }
    
    /**
     * Force reload Pro classes (called after license status changes)
     */
    public function reload_pro_classes() {
        // Clear any existing Pro class instances
        $pro_classes = array('SRWM_Pro_Restock', 'SRWM_Pro_Purchase_Order', 'SRWM_Pro_CSV_Upload');
        
        foreach ($pro_classes as $class_name) {
            if (class_exists($class_name)) {
                try {
                    $reflection = new ReflectionClass($class_name);
                    if ($reflection->hasProperty('instance')) {
                        $property = $reflection->getProperty('instance');
                        $property->setAccessible(true);
                        $property->setValue(null, null);
                    }
                } catch (Exception $e) {
                    // Ignore reflection errors
                }
            }
        }
        
        // Clear any cached license status
        wp_cache_delete('srwm_license_status', 'options');
        
        // Reload Pro classes based on current license status
        $this->maybe_load_pro_classes();
    }
    
    /**
     * Load core classes
     */
    private function load_core_classes() {
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-waitlist.php';
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-supplier.php';
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-email.php';
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-analytics.php';
    }
    
    /**
     * Load Pro classes
     */
    private function load_pro_classes() {
        require_once SRWM_PLUGIN_DIR . 'includes/pro/class-srwm-pro-restock.php';
        require_once SRWM_PLUGIN_DIR . 'includes/pro/class-srwm-pro-purchase-order.php';
        require_once SRWM_PLUGIN_DIR . 'includes/pro/class-srwm-pro-csv-upload.php';
    }
    
    /**
     * Check if Pro classes should be loaded
     */
    public function should_load_pro_classes() {
        return $this->license_manager->is_pro_active();
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        require_once SRWM_PLUGIN_DIR . 'includes/class-srwm-admin.php';
        require_once SRWM_PLUGIN_DIR . 'admin/class-srwm-admin-dashboard.php';
        
        new SRWM_Admin($this->license_manager);
        new SRWM_Admin_Dashboard($this->license_manager);
        

    }
    
    /**
     * Initialize frontend functionality
     */
    private function init_frontend() {
        // Initialize core classes with license manager
        $waitlist = SRWM_Waitlist::get_instance($this->license_manager);
        $supplier = SRWM_Supplier::get_instance($this->license_manager);
        
        // Add waitlist form to product pages
        add_action('woocommerce_single_product_summary', array($waitlist, 'display_waitlist_form'), 25);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($waitlist, 'enqueue_scripts'));
        
        // Hook into stock status changes
        add_action('woocommerce_product_set_stock_status', array($supplier, 'check_stock_levels'), 10, 3);
        add_action('woocommerce_product_set_stock_status', array($waitlist, 'check_restock_notification'), 10, 3);
    }
    
    /**
     * Add AJAX handlers
     */
    private function add_ajax_handlers() {
        // Frontend AJAX
        add_action('wp_ajax_srwm_add_to_waitlist', array($this, 'ajax_add_to_waitlist'));
        add_action('wp_ajax_nopriv_srwm_add_to_waitlist', array($this, 'ajax_add_to_waitlist'));
        
        // Admin AJAX
        add_action('wp_ajax_srwm_restock_product', array($this, 'ajax_restock_product'));
        add_action('wp_ajax_srwm_get_waitlist_data', array($this, 'ajax_get_waitlist_data'));
        add_action('wp_ajax_srwm_export_waitlist', array($this, 'ajax_export_waitlist'));
        add_action('wp_ajax_srwm_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_srwm_export_dashboard_report', array($this, 'ajax_export_dashboard_report'));
        
        // Pro AJAX handlers - Always register, check license in handler
        add_action('wp_ajax_srwm_generate_restock_link', array($this, 'ajax_generate_restock_link'));
        add_action('wp_ajax_srwm_generate_po', array($this, 'ajax_generate_po'));
        add_action('wp_ajax_srwm_generate_csv_upload_link', array($this, 'ajax_generate_csv_upload_link'));
        add_action('wp_ajax_srwm_save_threshold', array($this, 'ajax_save_threshold'));
        add_action('wp_ajax_srwm_reset_threshold', array($this, 'ajax_reset_threshold'));
        add_action('wp_ajax_srwm_save_notification_settings', array($this, 'ajax_save_notification_settings'));
        add_action('wp_ajax_srwm_save_email_templates', array($this, 'ajax_save_email_templates'));
        add_action('wp_ajax_srwm_save_global_threshold', array($this, 'ajax_save_global_threshold'));
        add_action('wp_ajax_srwm_download_csv_template', array($this, 'ajax_download_csv_template'));
    }
    
    /**
     * AJAX: Add customer to waitlist
     */
    public function ajax_add_to_waitlist() {
        check_ajax_referer('srwm_waitlist_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['name']);
        
        if (empty($email) || !is_email($email)) {
            wp_die(json_encode(array('success' => false, 'message' => __('Please enter a valid email address.', 'smart-restock-waitlist'))));
        }
        
        $result = SRWM_Waitlist::add_customer($product_id, $email, $name);
        
        if ($result) {
            wp_die(json_encode(array('success' => true, 'message' => __('You have been added to the waitlist!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('You are already on the waitlist for this product.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Restock product
     */
    public function ajax_restock_product() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        $result = SRWM_Waitlist::restock_and_notify($product_id, $quantity);
        
        if ($result) {
            wp_die(json_encode(array('success' => true, 'message' => __('Product restocked successfully!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to restock product.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Get waitlist data
     */
    public function ajax_get_waitlist_data() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $customers = SRWM_Waitlist::get_waitlist_customers($product_id);
        
        wp_die(json_encode(array('success' => true, 'data' => $customers)));
    }
    
    /**
     * AJAX: Export waitlist
     */
    public function ajax_export_waitlist() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $customers = SRWM_Waitlist::get_waitlist_customers($product_id);
        
        $filename = 'waitlist-export-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Name', 'Email', 'Date Added', 'Notified'));
        
        foreach ($customers as $customer) {
            fputcsv($output, array(
                $customer->customer_name,
                $customer->customer_email,
                $customer->date_added,
                $customer->notified ? 'Yes' : 'No'
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $data = $analytics->get_dashboard_data();
        
        wp_die(json_encode(array('success' => true, 'data' => $data)));
    }
    
    /**
     * AJAX: Export dashboard report
     */
    public function ajax_export_dashboard_report() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        $analytics = SRWM_Analytics::get_instance($this->license_manager);
        $analytics->export_analytics_csv();
    }
    
    /**
     * AJAX: Generate restock link (Pro)
     */
    public function ajax_generate_restock_link() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $supplier_email = sanitize_email($_POST['supplier_email']);
        
        if (class_exists('SRWM_Pro_Restock')) {
            $restock = SRWM_Pro_Restock::get_instance();
            $result = $restock->generate_restock_link($product_id, $supplier_email);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'message' => __('Pro feature not available.', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Generate purchase order (Pro)
     */
    public function ajax_generate_po() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $supplier_data = array(
            'name' => sanitize_text_field($_POST['supplier_name']),
            'email' => sanitize_email($_POST['supplier_email'])
        );
        
        if (class_exists('SRWM_Pro_Purchase_Order')) {
            $po = SRWM_Pro_Purchase_Order::get_instance();
            $result = $po->generate_purchase_order($product_id, $supplier_data);
            wp_die(json_encode($result));
        }
        
        wp_die(json_encode(array('success' => false, 'message' => __('Pro feature not available.', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Generate CSV upload link (Pro)
     */
    public function ajax_generate_csv_upload_link() {
        error_log('SRWM CSV AJAX: Handler called');
        
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            error_log('SRWM CSV AJAX: Insufficient permissions');
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        if (!$this->license_manager->is_pro_active()) {
            error_log('SRWM CSV AJAX: Pro license not active');
            wp_die(json_encode(array('success' => false, 'message' => __('Pro license required. Please activate your license first.', 'smart-restock-waitlist'))));
        }
        
        if (!isset($_POST['supplier_email'])) {
            error_log('SRWM CSV AJAX: Supplier email not provided');
            wp_die(json_encode(array('success' => false, 'message' => __('Supplier email is required.', 'smart-restock-waitlist'))));
        }
        
        $supplier_email = sanitize_email($_POST['supplier_email']);
        error_log('SRWM CSV AJAX: Supplier email: ' . $supplier_email);
        
        if (!$supplier_email) {
            error_log('SRWM CSV AJAX: Invalid email format');
            wp_die(json_encode(array('success' => false, 'message' => __('Please enter a valid email address.', 'smart-restock-waitlist'))));
        }
        
        // Ensure Pro classes are loaded
        if (!$this->should_load_pro_classes()) {
            error_log('SRWM CSV AJAX: Pro classes should not be loaded');
            wp_die(json_encode(array('success' => false, 'message' => __('Pro license not active.', 'smart-restock-waitlist'))));
        }
        
        // Load Pro classes if not already loaded
        if (!class_exists('SRWM_Pro_CSV_Upload')) {
            error_log('SRWM CSV AJAX: Loading Pro classes');
            $this->load_pro_classes();
        }
        
        // Ensure Pro tables exist
        error_log('SRWM CSV AJAX: Creating tables');
        $this->create_tables();
        
        if (class_exists('SRWM_Pro_CSV_Upload')) {
            error_log('SRWM CSV AJAX: Class exists, getting instance');
            $csv = SRWM_Pro_CSV_Upload::get_instance();
            $token = $csv->generate_csv_token($supplier_email);
            
            if ($token) {
                $upload_url = add_query_arg(array(
                    'srwm_csv_upload' => '1',
                    'token' => $token
                ), home_url());
                
                error_log('SRWM CSV AJAX: Success - Token: ' . $token . ', URL: ' . $upload_url);
                
                wp_die(json_encode(array(
                    'success' => true, 
                    'message' => __('CSV upload link generated successfully!', 'smart-restock-waitlist'),
                    'data' => array(
                        'link' => $upload_url,
                        'token' => $token
                    )
                )));
            } else {
                error_log('SRWM CSV AJAX: Failed to generate token');
                wp_die(json_encode(array('success' => false, 'message' => __('Failed to generate CSV upload link. Database error.', 'smart-restock-waitlist'))));
            }
        }
        
        error_log('SRWM CSV AJAX: Class not found');
        wp_die(json_encode(array('success' => false, 'message' => __('Pro feature not available. Class not found.', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save product threshold (Pro)
     */
    public function ajax_save_threshold() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        if (!$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Pro license required. Please activate your license first.', 'smart-restock-waitlist'))));
        }
        
        if (!isset($_POST['product_id']) || !isset($_POST['threshold'])) {
            wp_die(json_encode(array('success' => false, 'message' => __('Missing required data.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        $threshold = intval($_POST['threshold']);
        
        if ($product_id <= 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Invalid product ID.', 'smart-restock-waitlist'))));
        }
        
        // Check if product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_die(json_encode(array('success' => false, 'message' => __('Product not found.', 'smart-restock-waitlist'))));
        }
        
        // Check if product is a valid WooCommerce product
        if (!$product->is_type('simple') && !$product->is_type('variable')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Product type not supported.', 'smart-restock-waitlist'))));
        }
        
        if ($threshold < 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Threshold must be a positive number.', 'smart-restock-waitlist'))));
        }
        
        // Save threshold as product meta
        
        // Get existing value first
        $existing_value = get_post_meta($product_id, '_srwm_threshold', true);
        
        $result = update_post_meta($product_id, '_srwm_threshold', $threshold);
        
        // Consider it successful if:
        // 1. update_post_meta() returned true (value changed)
        // 2. update_post_meta() returned false but the value is already correct (no change needed)
        if ($result !== false || $existing_value == $threshold) {
            wp_die(json_encode(array('success' => true, 'message' => __('Threshold saved successfully!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to save threshold. Please try again.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Reset product threshold (Pro)
     */
    public function ajax_reset_threshold() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        if (!$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Pro license required. Please activate your license first.', 'smart-restock-waitlist'))));
        }
        
        if (!isset($_POST['product_id'])) {
            wp_die(json_encode(array('success' => false, 'message' => __('Product ID is required.', 'smart-restock-waitlist'))));
        }
        
        $product_id = intval($_POST['product_id']);
        
        if ($product_id <= 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Invalid product ID.', 'smart-restock-waitlist'))));
        }
        
        // Check if product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_die(json_encode(array('success' => false, 'message' => __('Product not found.', 'smart-restock-waitlist'))));
        }
        
        // Delete the custom threshold meta to use global default
        $result = delete_post_meta($product_id, '_srwm_threshold');
        
        if ($result !== false) {
            wp_die(json_encode(array('success' => true, 'message' => __('Threshold reset to global default!', 'smart-restock-waitlist'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to reset threshold. Please try again.', 'smart-restock-waitlist'))));
        }
    }
    
    /**
     * AJAX: Save notification settings (Pro)
     */
    public function ajax_save_notification_settings() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        // Save notification channel settings
        $notification_settings = array(
            'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
            'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? 1 : 0,
            'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
            'whatsapp_api_key' => sanitize_text_field($_POST['whatsapp_api_key'] ?? ''),
            'whatsapp_phone' => sanitize_text_field($_POST['whatsapp_phone'] ?? ''),
            'sms_api_key' => sanitize_text_field($_POST['sms_api_key'] ?? ''),
            'sms_phone' => sanitize_text_field($_POST['sms_phone'] ?? ''),
            'sms_provider' => sanitize_text_field($_POST['sms_provider'] ?? 'twilio')
        );
        
        update_option('srwm_notification_settings', $notification_settings);
        
        wp_die(json_encode(array('success' => true, 'message' => __('Notification settings saved successfully!', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save email templates (Pro)
     */
    public function ajax_save_email_templates() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') || !$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions or Pro license required.', 'smart-restock-waitlist'))));
        }
        
        // Save email templates
        $templates = array(
            'waitlist_email_template' => wp_kses_post($_POST['srwm_waitlist_email_template'] ?? ''),
            'restock_email_template' => wp_kses_post($_POST['srwm_restock_email_template'] ?? ''),
            'supplier_email_template' => wp_kses_post($_POST['srwm_supplier_email_template'] ?? ''),
            'po_email_template' => wp_kses_post($_POST['srwm_po_email_template'] ?? '')
        );
        
        foreach ($templates as $key => $template) {
            update_option($key, $template);
        }
        
        wp_die(json_encode(array('success' => true, 'message' => __('Email templates saved successfully!', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Save global threshold (Pro)
     */
    public function ajax_save_global_threshold() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        if (!$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Pro license required. Please activate your license first.', 'smart-restock-waitlist'))));
        }
        
        if (!isset($_POST['global_threshold'])) {
            wp_die(json_encode(array('success' => false, 'message' => __('Global threshold value is required.', 'smart-restock-waitlist'))));
        }
        
        $global_threshold = intval($_POST['global_threshold']);
        
        if ($global_threshold < 0) {
            wp_die(json_encode(array('success' => false, 'message' => __('Global threshold must be a positive number.', 'smart-restock-waitlist'))));
        }
        
        update_option('srwm_global_threshold', $global_threshold);
        
        wp_die(json_encode(array('success' => true, 'message' => __('Global threshold saved successfully!', 'smart-restock-waitlist'))));
    }
    
    /**
     * AJAX: Download CSV template (Pro)
     */
    public function ajax_download_csv_template() {
        check_ajax_referer('srwm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Insufficient permissions.', 'smart-restock-waitlist'))));
        }
        
        if (!$this->license_manager->is_pro_active()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Pro license required. Please activate your license first.', 'smart-restock-waitlist'))));
        }
        
        // Create CSV content
        $csv_content = "Product ID,SKU,Quantity,Notes\n";
        $csv_content .= "123,PROD-001,50,Restock product\n";
        $csv_content .= "456,PROD-002,25,Add more stock\n";
        $csv_content .= "789,PROD-003,100,Full restock\n";
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="csv_upload_template.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo $csv_content;
        exit;
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Waitlist table
        $table_waitlist = $wpdb->prefix . 'srwm_waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_name varchar(255) DEFAULT '',
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            notified tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY customer_email (customer_email),
            KEY notified (notified)
        ) $charset_collate;";
        
        // Suppliers table
        $table_supplier = $wpdb->prefix . 'srwm_suppliers';
        $sql_supplier = "CREATE TABLE $table_supplier (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            supplier_name varchar(255) NOT NULL,
            supplier_email varchar(255) NOT NULL,
            threshold int(11) DEFAULT 5,
            channels longtext,
            auto_generate_po tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY supplier_email (supplier_email)
        ) $charset_collate;";
        
        // Restock logs table
        $table_logs = $wpdb->prefix . 'srwm_restock_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            quantity int(11) NOT NULL,
            method varchar(50) DEFAULT 'manual',
            ip_address varchar(45) DEFAULT '',
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY method (method),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        // Pro tables - only create if license is active
        if ($this->license_manager->is_pro_active()) {
            // Restock tokens table
            $table_tokens = $wpdb->prefix . 'srwm_restock_tokens';
            $sql_tokens = "CREATE TABLE $table_tokens (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                product_id bigint(20) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                token varchar(255) NOT NULL,
                expires_at datetime NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY token (token),
                KEY expires_at (expires_at)
            ) $charset_collate;";
            
            // CSV upload tokens table
            $table_csv_tokens = $wpdb->prefix . 'srwm_csv_tokens';
            $sql_csv_tokens = "CREATE TABLE $table_csv_tokens (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                supplier_email varchar(255) NOT NULL,
                token varchar(255) NOT NULL,
                expires_at datetime NOT NULL,
                used tinyint(1) NOT NULL DEFAULT 0,
                used_at datetime DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY token (token),
                KEY supplier_email (supplier_email),
                KEY expires_at (expires_at),
                KEY used (used)
            ) $charset_collate;";
            
            // Purchase orders table
            $table_po = $wpdb->prefix . 'srwm_purchase_orders';
            $sql_po = "CREATE TABLE $table_po (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                po_number varchar(50) NOT NULL,
                product_id bigint(20) NOT NULL,
                supplier_email varchar(255) NOT NULL,
                quantity int(11) NOT NULL,
                status varchar(50) DEFAULT 'pending',
                pdf_path varchar(500) DEFAULT '',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY po_number (po_number),
                KEY product_id (product_id),
                KEY status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_tokens);
            dbDelta($sql_csv_tokens);
            dbDelta($sql_po);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_waitlist);
        dbDelta($sql_supplier);
        dbDelta($sql_logs);
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('smart-restock-waitlist', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>' . 
             __('Smart Restock & Waitlist Manager requires WooCommerce to be installed and activated.', 'smart-restock-waitlist') . 
             '</p></div>';
    }
}

// Initialize the plugin
global $srwm_plugin;
$srwm_plugin = new SmartRestockWaitlistManager();