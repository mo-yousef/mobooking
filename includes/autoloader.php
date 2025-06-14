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
    if ($relative_class === 'Core\Loader') {
        $file = MOBOOKING_PATH . '/classes/Core/Loader.php';
    } else {
        $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';
    }

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
    $critical_files = array(
        '/classes/Core/Loader.php',
        '/classes/Database/Manager.php',
    );
    
    foreach ($critical_files as $file) {
        $file_path = MOBOOKING_PATH . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
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