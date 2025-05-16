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

// Add script localization for AJAX functionality
function mobooking_register_scripts() {
    // Enqueue jQuery (already included with WordPress, but we'll make sure it's loaded)
    wp_enqueue_script('jquery');
    
    // Register and localize mobooking scripts
    wp_register_script('mobooking-admin-scripts', MOBOOKING_URL . '/assets/js/admin.js', array('jquery'), MOBOOKING_VERSION, true);
    
    // Localize scripts for various sections
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