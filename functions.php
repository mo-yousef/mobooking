<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('MOBOOKING_VERSION', '1.0.0');
define('MOBOOKING_PATH', get_template_directory());
define('MOBOOKING_URL', get_template_directory_uri());

// Autoloader
spl_autoload_register(function ($class) {
    // Check if the class uses our namespace
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }
    
    // Remove namespace and replace \ with /
    $relative_class = str_replace('MoBooking\\', '', $class);
    $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, load it
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the theme
$loader = new MoBooking\Core\Loader();
$loader->init();

function mobooking_load_dashicons_frontend() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'mobooking_load_dashicons_frontend');

// Add script localization for AJAX functionality
function mobooking_register_scripts() {
    // Enqueue jQuery (already included with WordPress, but we'll make sure it's loaded)
    wp_enqueue_script('jquery');
    
    // Register and localize mobooking scripts
    wp_register_script('mobooking-admin-scripts', MOBOOKING_URL . '/assets/js/admin.js', array('jquery'), MOBOOKING_VERSION, true);
    
    // Localize scripts for various sections
    wp_localize_script('jquery', 'mobooking_services', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-service-nonce'),
        'option_nonce' => wp_create_nonce('mobooking-option-nonce') // Add this line
    ));
    
    wp_localize_script('jquery', 'mobooking_areas', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-area-nonce')
    ));
    
    wp_localize_script('jquery', 'mobooking_discounts', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-discount-nonce')
    ));
    
    wp_localize_script('jquery', 'mobooking_bookings', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-booking-status-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mobooking_register_scripts');
add_action('admin_enqueue_scripts', 'mobooking_register_scripts');





// This code should be added to functions.php or a similar loading file
/**
 * Replace the old Service classes with the new consolidated ServiceManager
 */
function mobooking_register_unified_service_manager() {
    // Remove the old hooks first to prevent duplicate actions
    if (class_exists('\\MoBooking\\Services\\Manager')) {
        $old_manager = new \MoBooking\Services\Manager();
        remove_action('wp_ajax_mobooking_save_service', array($old_manager, 'ajax_save_service'));
        remove_action('wp_ajax_mobooking_delete_service', array($old_manager, 'ajax_delete_service'));
        remove_action('wp_ajax_mobooking_get_service', array($old_manager, 'ajax_get_service'));
        remove_action('wp_ajax_mobooking_get_services', array($old_manager, 'ajax_get_services'));
        remove_action('wp_ajax_nopriv_mobooking_get_services_by_zip', array($old_manager, 'ajax_get_services_by_zip'));
    }
    
    if (class_exists('\\MoBooking\\Services\\OptionsManager')) {
        $old_options_manager = new \MoBooking\Services\OptionsManager();
        remove_action('wp_ajax_mobooking_save_service_option', array($old_options_manager, 'ajax_save_service_option'));
        remove_action('wp_ajax_mobooking_delete_service_option', array($old_options_manager, 'ajax_delete_service_option'));
        remove_action('wp_ajax_mobooking_get_service_option', array($old_options_manager, 'ajax_get_service_option'));
        remove_action('wp_ajax_mobooking_get_service_options', array($old_options_manager, 'ajax_get_service_options'));
        remove_action('wp_ajax_mobooking_update_options_order', array($old_options_manager, 'ajax_update_options_order'));
    }
    
    // Initialize the new unified service manager
    new \MoBooking\Services\ServiceManager();
}

// Run this after the old classes would have been initialized
add_action('init', 'mobooking_register_unified_service_manager', 20);

/**
 * Update autoloader paths to find the new ServiceManager
 */
function mobooking_update_autoloader_paths($file, $class) {
    // Check if we're looking for the old manager classes
    if ($class === 'MoBooking\\Services\\Manager' || $class === 'MoBooking\\Services\\OptionsManager') {
        // Redirect to the new ServiceManager
        $new_file = MOBOOKING_PATH . '/classes/Services/ServiceManager.php';
        if (file_exists($new_file)) {
            // Include the new file
            require_once $new_file;
            
            // Define a class alias to maintain backward compatibility
            if (!class_exists($class)) {
                class_alias('MoBooking\\Services\\ServiceManager', $class);
            }
            
            // Return false to stop the default autoloader
            return false;
        }
    }
    
    // Let the default autoloader continue
    return $file;
}

// Add filter to the autoloader
add_filter('mobooking_autoload_file', 'mobooking_update_autoloader_paths', 10, 2);

