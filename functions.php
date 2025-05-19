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


// In functions.php - replace existing service manager instantiation
function initialize_service_manager() {
    // Only initialize the new ServiceManager
    return new \MoBooking\Services\ServiceManager();
}

// Use this function instead of directly instantiating either manager
add_action('init', function() {
    if (class_exists('\MoBooking\Services\ServiceManager')) {
        initialize_service_manager();
    }
}, 20); // Higher priority to run after autoloading





/**
 * Direct Service Data Access
 * 
 * This code creates dedicated AJAX endpoints that directly query the database
 * to bypass conflicting manager classes.
 * 
 * Add this code to your theme's functions.php file.
 */

// Create dedicated AJAX endpoints
add_action('wp_ajax_mobooking_direct_get_service', 'mobooking_direct_get_service');
add_action('wp_ajax_mobooking_direct_get_service_options', 'mobooking_direct_get_service_options');

/**
 * Custom AJAX handler to get service data directly from the database
 */
function mobooking_direct_get_service() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'You do not have permission to do this.'));
    }
    
    // Check service ID
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        wp_send_json_error(array('message' => 'No service specified.'));
    }
    
    global $wpdb;
    $service_id = absint($_POST['id']);
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    // Direct database query
    $service = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND entity_type = 'service'",
        $service_id, $user_id
    ));
    
    if (!$service) {
        // Try without entity_type filter in case you're using the old table structure
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $service_id, $user_id
        ));
    }
    
    if (!$service) {
        wp_send_json_error(array('message' => 'Service not found or you do not have permission to view it.'));
    }
    
    wp_send_json_success(array(
        'service' => $service
    ));
}

/**
 * Custom AJAX handler to get service options directly from the database
 */
function mobooking_direct_get_service_options() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }
    
    // Check service ID
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        wp_send_json_error(array('message' => 'No service specified.'));
    }
    
    global $wpdb;
    $service_id = absint($_POST['service_id']);
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    // First check if we're using the new unified table structure
    $options = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE parent_id = %d AND entity_type = 'option' ORDER BY display_order ASC, id ASC",
        $service_id
    ));
    
    // If no options found, check for old table structure with service_options table
    if (empty($options)) {
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'");
        
        if ($table_exists) {
            $options = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $options_table WHERE service_id = %d ORDER BY display_order ASC, id ASC",
                $service_id
            ));
        }
    }
    
    wp_send_json_success(array(
        'options' => $options ?: array()
    ));
}