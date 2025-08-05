<?php
/**
 * Free Version Build Script
 * 
 * This script creates the free version of the plugin by excluding Pro features.
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
 * Free Version Builder Class
 */
class SRWM_Free_Version_Builder {
    
    private $source_dir;
    private $build_dir;
    private $excluded_files = array();
    private $excluded_dirs = array();
    private $modified_files = array();
    
    public function __construct() {
        $this->source_dir = dirname(__DIR__);
        $this->build_dir = $this->source_dir . '/build/free-version';
        
        // Files to exclude from free version
        $this->excluded_files = array(
            'includes/pro/',
            'admin/class-srwm-admin-dashboard.php',
            'admin/js/dashboard.js',
            'admin/css/dashboard.css',
            'database/schema.sql'
        );
        
        // Directories to exclude
        $this->excluded_dirs = array(
            'includes/pro',
            'admin',
            'database'
        );
        
        // Files that need modification for free version
        $this->modified_files = array(
            'smart-restock-waitlist-manager.php',
            'includes/class-srwm-admin.php',
            'includes/class-srwm-supplier.php',
            'includes/class-srwm-waitlist.php',
            'includes/class-srwm-email.php',
            'includes/class-srwm-analytics.php'
        );
    }
    
    /**
     * Build the free version
     */
    public function build() {
        echo "Building Smart Restock & Waitlist Manager - Free Version\n";
        echo "=====================================================\n\n";
        
        // Clean build directory
        $this->clean_build_directory();
        
        // Copy files
        $this->copy_files();
        
        // Modify files for free version
        $this->modify_files();
        
        // Create ZIP file
        $this->create_zip();
        
        echo "\nFree version build completed successfully!\n";
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
     * Copy files to build directory
     */
    private function copy_files() {
        $this->copy_directory($this->source_dir, $this->build_dir);
        echo "✓ Copied source files\n";
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
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;
            
            // Check if file/directory should be excluded
            $relative_path = str_replace($this->source_dir . '/', '', $source_path);
            if ($this->should_exclude($relative_path)) {
                continue;
            }
            
            if (is_dir($source_path)) {
                $this->copy_directory($source_path, $dest_path);
            } else {
                copy($source_path, $dest_path);
            }
        }
    }
    
    /**
     * Check if file/directory should be excluded
     */
    private function should_exclude($path) {
        // Check excluded files
        foreach ($this->excluded_files as $excluded) {
            if (strpos($path, $excluded) === 0) {
                return true;
            }
        }
        
        // Check excluded directories
        foreach ($this->excluded_dirs as $excluded) {
            if (strpos($path, $excluded) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Modify files for free version
     */
    private function modify_files() {
        foreach ($this->modified_files as $file) {
            $file_path = $this->build_dir . '/' . $file;
            if (file_exists($file_path)) {
                $this->modify_file_for_free_version($file_path, $file);
            }
        }
        echo "✓ Modified files for free version\n";
    }
    
    /**
     * Modify specific file for free version
     */
    private function modify_file_for_free_version($file_path, $file_name) {
        $content = file_get_contents($file_path);
        
        switch ($file_name) {
            case 'smart-restock-waitlist-manager.php':
                $content = $this->modify_main_plugin_file($content);
                break;
                
            case 'includes/class-srwm-admin.php':
                $content = $this->modify_admin_class($content);
                break;
                
            case 'includes/class-srwm-supplier.php':
                $content = $this->modify_supplier_class($content);
                break;
                
            case 'includes/class-srwm-waitlist.php':
                $content = $this->modify_waitlist_class($content);
                break;
                
            case 'includes/class-srwm-email.php':
                $content = $this->modify_email_class($content);
                break;
                
            case 'includes/class-srwm-analytics.php':
                $content = $this->modify_analytics_class($content);
                break;
        }
        
        file_put_contents($file_path, $content);
    }
    
    /**
     * Modify main plugin file for free version
     */
    private function modify_main_plugin_file($content) {
        // Remove license manager class
        $content = preg_replace('/class SRWM_License_Manager[\s\S]*?}/', '', $content);
        
        // Remove license manager initialization
        $content = str_replace('$this->license_manager = SRWM_License_Manager::get_instance();', '', $content);
        
        // Remove Pro feature loading
        $content = preg_replace('/if \(\$this->license_manager->is_pro_active\(\)\)[\s\S]*?}/', '', $content);
        
        // Update plugin header
        $content = str_replace(
            'Version: 1.0.0',
            'Version: 1.0.0 (Free)',
            $content
        );
        
        return $content;
    }
    
    /**
     * Modify admin class for free version
     */
    private function modify_admin_class($content) {
        // Remove license manager references
        $content = str_replace('private $license_manager;', '', $content);
        $content = str_replace('$this->license_manager = SRWM_License_Manager::get_instance();', '', $content);
        
        // Remove Pro menu items
        $content = preg_replace('/if \(\$this->license_manager->is_pro_active\(\)\)[\s\S]*?}/', '', $content);
        
        // Remove Pro settings
        $content = preg_replace('/Pro Settings[\s\S]*?}/', '', $content);
        
        return $content;
    }
    
    /**
     * Modify supplier class for free version
     */
    private function modify_supplier_class($content) {
        // Remove license manager references
        $content = str_replace('private $license_manager;', '', $content);
        $content = str_replace('$this->license_manager = SRWM_License_Manager::get_instance();', '', $content);
        
        // Remove Pro notification channels
        $content = preg_replace('/if \(\$this->license_manager->is_pro_active\(\)\)[\s\S]*?else[\s\S]*?endif;/', '', $content);
        
        return $content;
    }
    
    /**
     * Modify waitlist class for free version
     */
    private function modify_waitlist_class($content) {
        // Remove license manager references
        $content = str_replace('private $license_manager;', '', $content);
        $content = str_replace('$this->license_manager = SRWM_License_Manager::get_instance();', '', $content);
        
        return $content;
    }
    
    /**
     * Modify email class for free version
     */
    private function modify_email_class($content) {
        // Remove license manager references
        $content = str_replace('private $license_manager;', '', $content);
        $content = str_replace('$this->license_manager = SRWM_License_Manager::get_instance();', '', $content);
        
        // Remove Pro placeholders
        $content = preg_replace('/if \(\$this->license_manager->is_pro_active\(\)\)[\s\S]*?}/', '', $content);
        
        return $content;
    }
    
    /**
     * Modify analytics class for free version
     */
    private function modify_analytics_class($content) {
        // Remove license manager references
        $content = str_replace('private $license_manager;', '', $content);
        $content = str_replace('$this->license_manager = SRWM_License_Manager::get_instance();', '', $content);
        
        // Remove Pro analytics
        $content = preg_replace('/if \(\$this->license_manager->is_pro_active\(\)\)[\s\S]*?}/', '', $content);
        
        return $content;
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
    $builder = new SRWM_Free_Version_Builder();
    $builder->build();
}