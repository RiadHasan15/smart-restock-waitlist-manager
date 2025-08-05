<?php
/**
 * Pro Version Build Script
 * 
 * This script creates the Pro version of the plugin with license validation.
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
 * Pro Version Builder Class
 */
class SRWM_Pro_Version_Builder {
    
    private $source_dir;
    private $build_dir;
    private $license_server_url = 'https://your-license-server.com/api/validate';
    
    public function __construct() {
        $this->source_dir = dirname(__DIR__);
        $this->build_dir = $this->source_dir . '/build/pro-version';
    }
    
    /**
     * Build the Pro version
     */
    public function build() {
        echo "Building Smart Restock & Waitlist Manager - Pro Version\n";
        echo "====================================================\n\n";
        
        // Clean build directory
        $this->clean_build_directory();
        
        // Copy all files
        $this->copy_files();
        
        // Add license validation
        $this->add_license_validation();
        
        // Create ZIP file
        $this->create_zip();
        
        echo "\nPro version build completed successfully!\n";
        echo "Location: {$this->build_dir}.zip\n";
    }
    
    /**
     * Clean build directory
     */
    private function clean_build_directory() {
        if (is_dir($this->build_dir)) {
            $this->remove_directory($this->build_dir);
        }
        mkdir($this->build_dir, 0755, true);
        echo "✓ Cleaned build directory\n";
    }
    
    /**
     * Copy all files to build directory
     */
    private function copy_files() {
        $this->copy_directory($this->source_dir, $this->build_dir);
        echo "✓ Copied all source files\n";
    }
    
    /**
     * Copy directory recursively
     */
    private function copy_directory($source, $destination) {
        if (!is_dir($source)) {
            return;
        }
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'build') {
                continue;
            }
            
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;
            
            if (is_dir($source_path)) {
                $this->copy_directory($source_path, $dest_path);
            } else {
                copy($source_path, $dest_path);
            }
        }
    }
    
    /**
     * Add license validation to Pro version
     */
    private function add_license_validation() {
        // Create license validation class
        $this->create_license_validator();
        
        // Update main plugin file
        $this->update_main_plugin_file();
        
        // Update license manager
        $this->update_license_manager();
        
        echo "✓ Added license validation\n";
    }
    
    /**
     * Create license validator class
     */
    private function create_license_validator() {
        $license_validator = '<?php
/**
 * License Validator for Pro Version
 * 
 * Handles license validation with external server.
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

class SRWM_License_Validator {
    
    private static $instance = null;
    private $license_server_url = \'' . $this->license_server_url . '\';
    private $cache_duration = 86400; // 24 hours
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action(\'admin_init\', array($this, \'schedule_license_check\'));
        add_action(\'srwm_daily_license_check\', array($this, \'check_license_status\'));
    }
    
    /**
     * Schedule daily license check
     */
    public function schedule_license_check() {
        if (!wp_next_scheduled(\'srwm_daily_license_check\')) {
            wp_schedule_event(time(), \'daily\', \'srwm_daily_license_check\');
        }
    }
    
    /**
     * Validate license key with server
     */
    public function validate_license($license_key, $domain = null) {
        if (empty($license_key)) {
            return array(
                \'valid\' => false,
                \'message\' => __(\'License key is required.\', \'smart-restock-waitlist\')
            );
        }
        
        if (!$domain) {
            $domain = $this->get_site_domain();
        }
        
        $response = wp_remote_post($this->license_server_url, array(
            \'timeout\' => 30,
            \'body\' => array(
                \'action\' => \'validate_license\',
                \'license_key\' => $license_key,
                \'domain\' => $domain,
                \'product\' => \'smart-restock-waitlist-pro\'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                \'valid\' => false,
                \'message\' => __(\'Failed to connect to license server.\', \'smart-restock-waitlist\')
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return array(
                \'valid\' => false,
                \'message\' => __(\'Invalid response from license server.\', \'smart-restock-waitlist\')
            );
        }
        
        if (isset($data[\'valid\']) && $data[\'valid\']) {
            // Cache the validation result
            $this->cache_license_status($license_key, $data);
            return $data;
        } else {
            return array(
                \'valid\' => false,
                \'message\' => isset($data[\'message\']) ? $data[\'message\'] : __(\'Invalid license key.\', \'smart-restock-waitlist\')
            );
        }
    }
    
    /**
     * Check license status (cached)
     */
    public function check_license_status() {
        $license_key = get_option(\'srwm_license_key\', \'\');
        if (empty($license_key)) {
            return false;
        }
        
        $cached = $this->get_cached_license_status($license_key);
        if ($cached && $cached[\'expires\'] > time()) {
            return $cached[\'data\'];
        }
        
        // Re-validate with server
        $result = $this->validate_license($license_key);
        if ($result[\'valid\']) {
            return $result;
        } else {
            // License is invalid, deactivate Pro features
            $this->deactivate_pro_features();
            return false;
        }
    }
    
    /**
     * Cache license status
     */
    private function cache_license_status($license_key, $data) {
        $cache_data = array(
            \'data\' => $data,
            \'expires\' => time() + $this->cache_duration
        );
        
        update_option(\'srwm_license_cache_\' . md5($license_key), $cache_data);
    }
    
    /**
     * Get cached license status
     */
    private function get_cached_license_status($license_key) {
        return get_option(\'srwm_license_cache_\' . md5($license_key), false);
    }
    
    /**
     * Deactivate Pro features
     */
    private function deactivate_pro_features() {
        update_option(\'srwm_pro_active\', false);
        update_option(\'srwm_license_status\', \'invalid\');
        
        // Clear any Pro-specific caches
        delete_option(\'srwm_license_cache_\' . md5(get_option(\'srwm_license_key\', \'\')));
    }
    
    /**
     * Get site domain
     */
    private function get_site_domain() {
        $domain = get_site_url();
        return parse_url($domain, PHP_URL_HOST);
    }
    
    /**
     * Get license server URL
     */
    public function get_license_server_url() {
        return $this->license_server_url;
    }
    
    /**
     * Test connection to license server
     */
    public function test_connection() {
        $response = wp_remote_get($this->license_server_url . \'?action=ping\', array(
            \'timeout\' => 10
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}

// Initialize license validator
SRWM_License_Validator::get_instance();
';
        
        file_put_contents($this->build_dir . '/includes/class-srwm-license-validator.php', $license_validator);
    }
    
    /**
     * Update main plugin file for Pro version
     */
    private function update_main_plugin_file() {
        $main_file = $this->build_dir . '/smart-restock-waitlist-manager.php';
        $content = file_get_contents($main_file);
        
        // Update plugin header
        $content = str_replace(
            'Version: 1.0.0',
            'Version: 1.0.0 (Pro)',
            $content
        );
        
        // Add license validator include
        $include_pattern = '/require_once.*class-srwm-license-manager\.php/';
        $replacement = '$0' . "\nrequire_once SRWM_PLUGIN_PATH . 'includes/class-srwm-license-validator.php';";
        $content = preg_replace($include_pattern, $replacement, $content);
        
        file_put_contents($main_file, $content);
    }
    
    /**
     * Update license manager for Pro version
     */
    private function update_license_manager() {
        $license_file = $this->build_dir . '/smart-restock-waitlist-manager.php';
        $content = file_get_contents($license_file);
        
        // Find the license manager class and update the validate_license_key method
        $pattern = '/private function validate_license_key\(\$license_key\)[\s\S]*?}/';
        $replacement = 'private function validate_license_key($license_key) {
        if (class_exists(\'SRWM_License_Validator\')) {
            $validator = SRWM_License_Validator::get_instance();
            $result = $validator->validate_license($license_key);
            return $result[\'valid\'];
        }
        
        // Fallback validation for development/testing
        return !empty($license_key) && strlen($license_key) >= 10;
    }';
        
        $content = preg_replace($pattern, $replacement, $content);
        
        file_put_contents($license_file, $content);
    }
    
    /**
     * Create ZIP file
     */
    private function create_zip() {
        $zip_file = $this->build_dir . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $this->add_directory_to_zip($zip, $this->build_dir, basename($this->build_dir));
            $zip->close();
            echo "✓ Created ZIP file\n";
        } else {
            echo "✗ Failed to create ZIP file\n";
        }
    }
    
    /**
     * Add directory to ZIP recursively
     */
    private function add_directory_to_zip($zip, $dir, $base_path = '') {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $file_path = $dir . '/' . $file;
            $zip_path = $base_path ? $base_path . '/' . $file : $file;
            
            if (is_dir($file_path)) {
                $this->add_directory_to_zip($zip, $file_path, $zip_path);
            } else {
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function remove_directory($dir) {
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
                $this->remove_directory($file_path);
            } else {
                unlink($file_path);
            }
        }
        
        rmdir($dir);
    }
}

// Run the build if called directly
if (defined('CLI_SCRIPT')) {
    $builder = new SRWM_Pro_Version_Builder();
    $builder->build();
}