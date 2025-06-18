<?php
/**
 * MoBooking Autoloader
 * Handles automatic loading of classes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced autoloader for MoBooking classes
 */
spl_autoload_register(function ($class) {
    // Only handle MoBooking namespace classes
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }

    // Remove MoBooking namespace prefix
    $relative_class = str_replace('MoBooking\\', '', $class);
    
    // Convert namespace separators to directory separators
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts); // Get the class name
    $namespace_path = implode('/', $parts); // Path from namespace parts

    $file_path_segment = '';
    if (!empty($namespace_path)) {
        $file_path_segment = $namespace_path . '/';
    }
    $file_path_segment .= strtolower($class_name) . '.php'; // Lowercase only the filename

    $file = MOBOOKING_PATH . '/classes/' . $file_path_segment;

    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
        
        // Debug logging in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Loaded class {$class} from {$file}");
        }
    } else {
        // Log missing class files in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Could not load class {$class}, file not found: {$file}");
        }
    }
});

/**
 * Manually load critical files that might be needed before autoloader
 */
function mobooking_load_critical_files() {
    $critical_files_map = array(
        'MoBooking\Core\Loader' => '/classes/core/loader.php',
        'MoBooking\Database\Manager' => '/classes/database/manager.php',
        // Add other truly critical classes here if needed
    );

    foreach ($critical_files_map as $class_name => $file) {
        $file_path = MOBOOKING_PATH . $file;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking CRITICAL LOAD ATTEMPT: Attempting to load class {$class_name} from {$file_path}. MOBOOKING_PATH is: " . MOBOOKING_PATH);
        }

        if (file_exists($file_path)) {
            require_once $file_path;

            // Explicit check after require_once
            if (!class_exists($class_name, false)) { // Use false to prevent re-triggering autoloader
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking CRITICAL LOAD FAILURE: Class {$class_name} not found AFTER attempting to require_once {$file_path}.");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking CRITICAL LOAD SUCCESS: Class {$class_name} was successfully loaded from {$file_path}.");
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking CRITICAL LOAD FAILURE: File {$file_path} for class {$class_name} not found. Check MOBOOKING_PATH and file existence.");
            }
        }
    }
}

// Load critical files immediately
mobooking_load_critical_files();

/**
 * Check if all required classes can be loaded
 */
function mobooking_check_class_dependencies() {
    $required_classes = array(
        'MoBooking\Core\Loader',
        'MoBooking\Database\Manager',
        'MoBooking\Auth\Manager',
        'MoBooking\Dashboard\Manager',
        'MoBooking\Services\ServicesManager',
        'MoBooking\Bookings\Manager',
    );
    
    $missing_classes = array();
    
    foreach ($required_classes as $class) {
        if (!class_exists($class)) {
            $missing_classes[] = $class;
        }
    }
    
    if (!empty($missing_classes) && defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Missing required classes: ' . implode(', ', $missing_classes));
    }
    
    return empty($missing_classes);
}

// Check dependencies after autoloader is registered
add_action('after_setup_theme', 'mobooking_check_class_dependencies', 1);