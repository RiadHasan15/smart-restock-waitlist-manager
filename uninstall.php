<?php
/**
 * Uninstall Smart Restock & Waitlist Manager
 * 
 * This file is executed when the plugin is uninstalled.
 * It removes all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to uninstall
if (!current_user_can('activate_plugins')) {
    return;
}

// Get global wpdb
global $wpdb;

/**
 * Remove all plugin data
 */
function srwm_remove_plugin_data() {
    global $wpdb;
    
    // Get table prefix
    $prefix = $wpdb->prefix;
    
    // Core tables to remove
    $tables = array(
        $prefix . 'srwm_waitlist',
        $prefix . 'srwm_suppliers',
        $prefix . 'srwm_restock_logs',
        $prefix . 'srwm_restock_tokens',
        $prefix . 'srwm_csv_tokens',
        $prefix . 'srwm_purchase_orders'
    );
    
    // Drop tables
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }
    
    // Remove options
    $options = array(
        'srwm_waitlist_enabled',
        'srwm_supplier_notifications',
        'srwm_low_stock_threshold',
        'srwm_auto_disable_zero_stock',
        'srwm_whatsapp_enabled',
        'srwm_sms_enabled',
        'srwm_auto_generate_po',
        'srwm_company_name',
        'srwm_company_address',
        'srwm_company_phone',
        'srwm_company_email',
        'srwm_email_template_waitlist',
        'srwm_email_template_supplier',
        'srwm_site_logo',
        'srwm_license_key',
        'srwm_license_status',
        'srwm_pro_active',
        'srwm_version',
        'srwm_db_version',
        'srwm_activation_date',
        'srwm_last_activity'
    );
    
    // Remove options
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Remove license cache options
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            'srwm_license_cache_%'
        )
    );
    
    // Remove user meta
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'srwm_%'
        )
    );
    
    // Remove post meta
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            'srwm_%'
        )
    );
    
    // Clear any transients
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_srwm_%',
            '_transient_timeout_srwm_%'
        )
    );
}

/**
 * Remove scheduled events
 */
function srwm_remove_scheduled_events() {
    // Clear scheduled license check
    wp_clear_scheduled_hook('srwm_daily_license_check');
    
    // Clear any other scheduled events
    wp_clear_scheduled_hook('srwm_cleanup_expired_tokens');
    wp_clear_scheduled_hook('srwm_send_supplier_reminders');
}

/**
 * Remove custom capabilities
 */
function srwm_remove_capabilities() {
    // Get admin role
    $admin_role = get_role('administrator');
    
    if ($admin_role) {
        // Remove custom capabilities
        $capabilities = array(
            'manage_srwm_waitlists',
            'view_srwm_analytics',
            'manage_srwm_suppliers',
            'export_srwm_data'
        );
        
        foreach ($capabilities as $cap) {
            $admin_role->remove_cap($cap);
        }
    }
}

/**
 * Clean up files (optional - only if user confirms)
 */
function srwm_cleanup_files() {
    // Get plugin directory
    $plugin_dir = plugin_dir_path(__FILE__);
    
    // Files to remove (optional)
    $files_to_remove = array(
        $plugin_dir . 'logs/',
        $plugin_dir . 'cache/',
        $plugin_dir . 'temp/'
    );
    
    // Only remove if they exist and are within plugin directory
    foreach ($files_to_remove as $file_path) {
        if (file_exists($file_path) && strpos(realpath($file_path), realpath($plugin_dir)) === 0) {
            if (is_dir($file_path)) {
                srwm_remove_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
    }
}

/**
 * Remove directory recursively
 */
function srwm_remove_directory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $file_path = $dir . '/' . $file;
        if (is_dir($file_path)) {
            srwm_remove_directory($file_path);
        } else {
            unlink($file_path);
        }
    }
    
    rmdir($dir);
}

/**
 * Log uninstall action
 */
function srwm_log_uninstall() {
    // Log the uninstall action (optional)
    $log_entry = array(
        'action' => 'plugin_uninstalled',
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'user_email' => wp_get_current_user()->user_email,
        'plugin_version' => get_option('srwm_version', 'unknown')
    );
    
    // You could log this to a file or external service
    // For now, we'll just add it to the WordPress debug log if enabled
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Smart Restock & Waitlist Manager uninstalled: ' . json_encode($log_entry));
    }
}

/**
 * Send uninstall notification (optional)
 */
function srwm_send_uninstall_notification() {
    // Only send if user has opted in for feedback
    if (get_option('srwm_allow_feedback', false)) {
        $admin_email = get_option('admin_email');
        $site_url = get_site_url();
        
        $subject = 'Smart Restock & Waitlist Manager Uninstalled';
        $message = "The Smart Restock & Waitlist Manager plugin has been uninstalled from:\n\n";
        $message .= "Site: {$site_url}\n";
        $message .= "Admin Email: {$admin_email}\n";
        $message .= "Date: " . current_time('mysql') . "\n\n";
        $message .= "If this was unintentional, please contact support.\n";
        
        // Send to plugin support (replace with actual support email)
        wp_mail('support@example.com', $subject, $message);
    }
}

/**
 * Main uninstall process
 */
function srwm_uninstall_plugin() {
    // Log the uninstall
    srwm_log_uninstall();
    
    // Remove all plugin data
    srwm_remove_plugin_data();
    
    // Remove scheduled events
    srwm_remove_scheduled_events();
    
    // Remove custom capabilities
    srwm_remove_capabilities();
    
    // Clean up files (optional)
    srwm_cleanup_files();
    
    // Send uninstall notification (optional)
    srwm_send_uninstall_notification();
    
    // Clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Clear object cache if available
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
}

// Execute the uninstall process
srwm_uninstall_plugin();

// Final cleanup - remove any remaining options that might have been missed
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        'srwm_%'
    )
);

// Remove any remaining user meta
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
        'srwm_%'
    )
);

// Remove any remaining post meta
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
        'srwm_%'
    )
);

// Log completion
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Smart Restock & Waitlist Manager uninstall completed successfully');
}