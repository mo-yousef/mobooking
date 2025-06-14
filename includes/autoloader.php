<?php
/**
 * MoBooking Autoloader - COMPREHENSIVE FIX
 * Handles automatic loading of classes with proper case handling
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced autoloader for MoBooking classes with comprehensive file detection
 */
spl_autoload_register(function ($class) {
    // Only handle MoBooking namespace classes
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }

    // Remove MoBooking namespace prefix
    $relative_class = str_replace('MoBooking\\', '', $class);
    
    // Convert namespace separators to directory separators
    $file_path = str_replace('\\', '/', $relative_class) . '.php';
    
    // Create multiple possible file paths to try (case variations)
    $possible_paths = array();
    
    // Special handling for Core\Loader
    if ($relative_class === 'Core\\Loader' || $relative_class === 'Core\Loader') {
        $possible_paths = array(
            MOBOOKING_PATH . '/classes/Core/Loader.php',      // Standard case
            MOBOOKING_PATH . '/classes/core/Loader.php',      // Mixed case 1
            MOBOOKING_PATH . '/classes/core/loader.php',      // All lowercase
            MOBOOKING_PATH . '/classes/Core/loader.php',      // Mixed case 2
        );
    } else {
        // General case handling for other classes
        $base_path = MOBOOKING_PATH . '/classes/';
        
        $possible_paths = array(
            $base_path . $file_path,                          // Original case
            $base_path . strtolower($file_path),              // All lowercase
            $base_path . str_replace('/', '/'.strtolower(basename(dirname($file_path))).'/', $file_path), // Lowercase folder
        );
        
        // Add specific variations for common patterns
        $parts = explode('/', $file_path);
        if (count($parts) == 2) {
            $folder = $parts[0];
            $file = $parts[1];
            
            $possible_paths[] = $base_path . strtolower($folder) . '/' . $file;      // lowercase folder, original file
            $possible_paths[] = $base_path . $folder . '/' . strtolower($file);      // original folder, lowercase file
            $possible_paths[] = $base_path . strtolower($folder) . '/' . strtolower($file); // both lowercase
        }
    }

    // Try each possible path
    foreach ($possible_paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            
            // Debug logging in development
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Successfully loaded class {$class} from {$file}");
            }
            return;
        }
    }
    
    // Log missing class files in debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("MoBooking: Could not load class {$class}. Tried paths: " . implode(', ', $possible_paths));
    }
});

/**
 * Manually load critical files with multiple path attempts
 */
function mobooking_load_critical_files() {
    $critical_files = array(
        // Core Loader variants
        array(
            '/classes/Core/Loader.php',
            '/classes/core/Loader.php',
            '/classes/core/loader.php',
            '/classes/Core/loader.php',
        ),
        // Database Manager variants
        array(
            '/classes/Database/Manager.php',
            '/classes/database/Manager.php',
            '/classes/database/manager.php',
            '/classes/Database/manager.php',
        ),
    );
    
    foreach ($critical_files as $file_variants) {
        $loaded = false;
        foreach ($file_variants as $file) {
            $file_path = MOBOOKING_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Manually loaded critical file: {$file_path}");
                }
                $loaded = true;
                break; // Stop trying variants once we load one
            }
        }
        
        if (!$loaded && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Could not load any variant of critical file: " . implode(', ', $file_variants));
        }
    }
}

// Load critical files immediately
mobooking_load_critical_files();

/**
 * Scan and report actual file structure for debugging
 */
function mobooking_debug_file_structure() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $classes_dir = MOBOOKING_PATH . '/classes';
    
    if (is_dir($classes_dir)) {
        error_log("MoBooking: Scanning classes directory structure...");
        
        // Scan for directories
        $dirs = scandir($classes_dir);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($classes_dir . '/' . $dir)) {
                error_log("MoBooking: Found directory: /classes/{$dir}/");
                
                // Scan files in this directory
                $subdir = $classes_dir . '/' . $dir;
                $files = scandir($subdir);
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                        error_log("MoBooking: Found file: /classes/{$dir}/{$file}");
                    }
                }
            }
        }
    } else {
        error_log("MoBooking: Classes directory not found: {$classes_dir}");
    }
}

/**
 * Check if all required classes can be loaded
 */
function mobooking_check_class_dependencies() {
    $required_classes = array(
        'MoBooking\Core\Loader' => 'Core theme loader',
        'MoBooking\Database\Manager' => 'Database management',
    );
    
    $missing_classes = array();
    
    foreach ($required_classes as $class => $description) {
        if (!class_exists($class)) {
            $missing_classes[$class] = $description;
        }
    }
    
    if (!empty($missing_classes)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Missing required classes:');
            foreach ($missing_classes as $class => $description) {
                error_log("  - {$class} ({$description})");
            }
            
            // Run file structure debug
            mobooking_debug_file_structure();
        }
        
        // Show admin notice for missing classes
        add_action('admin_notices', function() use ($missing_classes) {
            if (current_user_can('administrator')) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>MoBooking Error:</strong> Missing required classes. Check error log for details.';
                echo '<br><small>Missing: ' . implode(', ', array_keys($missing_classes)) . '</small>';
                echo '</p></div>';
            }
        });
    }
    
    return empty($missing_classes);
}

// Check dependencies after autoloader is registered
add_action('after_setup_theme', 'mobooking_check_class_dependencies', 1);

/**
 * Emergency loader - try to manually include files if autoloader fails
 */
function mobooking_emergency_loader() {
    if (!class_exists('MoBooking\Core\Loader')) {
        $emergency_paths = array(
            MOBOOKING_PATH . '/classes/core/loader.php',
            MOBOOKING_PATH . '/classes/Core/Loader.php',
            MOBOOKING_PATH . '/classes/core/Loader.php',
            MOBOOKING_PATH . '/classes/Core/loader.php',
        );
        
        foreach ($emergency_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Emergency loaded Loader from: {$path}");
                }
                break;
            }
        }
    }
}

// Run emergency loader before init
add_action('after_setup_theme', 'mobooking_emergency_loader', 0);