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

// In functions.php - Update script localization

function mobooking_register_scripts() {
    // Enqueue jQuery (already included with WordPress, but we'll make sure it's loaded)
    wp_enqueue_script('jquery');
    
    // Localize scripts for various sections
    wp_localize_script('jquery', 'mobooking_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-service-nonce'),
        'option_nonce' => wp_create_nonce('mobooking-option-nonce')
    ));
    
    // Keep these for backwards compatibility
    wp_localize_script('jquery', 'mobooking_services', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-service-nonce')
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

// Add at the top of your functions.php, after the initial checks
$manager_file = get_template_directory() . '/classes/Services/Manager.php';
if (file_exists($manager_file)) {
    include_once $manager_file;
    error_log('Manager.php file included successfully');
} else {
    error_log('Manager.php file not found at: ' . $manager_file);
}



spl_autoload_register(function ($class) {
    // Check if the class uses our namespace
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }
    
    // Remove namespace and replace \ with /
    $relative_class = str_replace('MoBooking\\', '', $class);
    $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // Debug output
    error_log('Trying to load class: ' . $class);
    error_log('Looking for file: ' . $file);
    error_log('File exists: ' . (file_exists($file) ? 'Yes' : 'No'));
    
    // If the file exists, load it
    if (file_exists($file)) {
        require_once $file;
    }
});


// In functions.php - REMOVE this section
$manager_file = get_template_directory() . '/classes/Services/Manager.php';
if (file_exists($manager_file)) {
    include_once $manager_file;
    error_log('Manager.php file included successfully');
} else {
    error_log('Manager.php file not found at: ' . $manager_file);
}





function mobooking_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message . (is_null($data) ? '' : ': ' . print_r($data, true)));
    }
}

// Update autoloader with debugging
spl_autoload_register(function ($class) {
    // Check if the class uses our namespace
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }
    
    // Remove namespace and replace \ with /
    $relative_class = str_replace('MoBooking\\', '', $class);
    $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // Debug output if needed
    mobooking_debug_log('Trying to load class: ' . $class);
    mobooking_debug_log('Looking for file: ' . $file . ' (exists: ' . (file_exists($file) ? 'Yes' : 'No') . ')');
    
    // If the file exists, load it
    if (file_exists($file)) {
        require_once $file;
        mobooking_debug_log('Successfully loaded class: ' . $class);
    }
});