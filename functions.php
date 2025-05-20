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









/**
 * Register AJAX endpoints for service management
 */
function mobooking_register_service_ajax_endpoints() {
    add_action('wp_ajax_mobooking_save_service_ajax', 'mobooking_save_service_ajax_handler');
    add_action('wp_ajax_mobooking_delete_service_ajax', 'mobooking_delete_service_ajax_handler');
}
add_action('init', 'mobooking_register_service_ajax_endpoints');

/**
 * AJAX handler for saving a service
 */
function mobooking_save_service_ajax_handler() {
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    
    // Build service data from POST
    $service_data = array(
        'user_id' => $user_id,
        'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'price' => isset($_POST['price']) ? floatval($_POST['price']) : 0,
        'duration' => isset($_POST['duration']) ? intval($_POST['duration']) : 60,
        'icon' => isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : '',
        'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
        'image_url' => isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '',
        'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
    );
    
    // Add ID if editing
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    if ($service_id > 0) {
        $service_data['id'] = $service_id;
    }
    
    // Validate required fields
    $errors = array();
    if (empty($service_data['name'])) {
        $errors[] = __('Service name is required', 'mobooking');
    }
    if ($service_data['price'] <= 0) {
        $errors[] = __('Price must be greater than zero', 'mobooking');
    }
    if ($service_data['duration'] < 15) {
        $errors[] = __('Duration must be at least 15 minutes', 'mobooking');
    }
    
    if (!empty($errors)) {
        wp_send_json_error(['message' => implode('<br>', $errors)]);
        return;
    }
    
    // Initialize the service manager
    $services_manager = new \MoBooking\Services\ServiceManager();
    
    // Save service
    $new_service_id = $services_manager->save_service($service_data);
    
    if (!$new_service_id) {
        wp_send_json_error(['message' => __('Failed to save service. Please try again.', 'mobooking')]);
        return;
    }
    
    // Return success
    wp_send_json_success([
        'id' => $new_service_id,
        'message' => $service_id > 0 ? 
            __('Service updated successfully', 'mobooking') : 
            __('Service created successfully', 'mobooking')
    ]);
}

/**
 * AJAX handler for deleting a service
 */
function mobooking_delete_service_ajax_handler() {
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
    }
    
    // Check service ID
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    if ($service_id <= 0) {
        wp_send_json_error(['message' => __('Invalid service ID.', 'mobooking')]);
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    
    // Initialize the service manager
    $services_manager = new \MoBooking\Services\ServiceManager();
    
    // Delete service
    $result = $services_manager->delete_service($service_id, $user_id);
    
    if (!$result) {
        wp_send_json_error(['message' => __('Failed to delete service.', 'mobooking')]);
        return;
    }
    
    // Return success
    wp_send_json_success([
        'message' => __('Service deleted successfully', 'mobooking')
    ]);
}

/**
 * Register AJAX endpoints for option management
 */
function mobooking_register_option_ajax_endpoints() {
    add_action('wp_ajax_mobooking_save_option_ajax', 'mobooking_save_option_ajax_handler');
    add_action('wp_ajax_mobooking_delete_option_ajax', 'mobooking_delete_option_ajax_handler');
}
add_action('init', 'mobooking_register_option_ajax_endpoints');

/**
 * AJAX handler for saving a service option
 */
function mobooking_save_option_ajax_handler() {
    // Check nonce
    if (!isset($_POST['option_nonce']) || !wp_verify_nonce($_POST['option_nonce'], 'mobooking-option-nonce')) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
    }
    
    // Check service ID
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        wp_send_json_error(['message' => __('No service specified.', 'mobooking')]);
    }
    
    $service_id = absint($_POST['service_id']);
    $user_id = get_current_user_id();
    
    // Prepare option data
    $option_data = array(
        'service_id' => $service_id,
        'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'checkbox',
        'is_required' => isset($_POST['is_required']) ? absint($_POST['is_required']) : 0,
        'default_value' => isset($_POST['default_value']) ? sanitize_text_field($_POST['default_value']) : '',
        'placeholder' => isset($_POST['placeholder']) ? sanitize_text_field($_POST['placeholder']) : '',
        'min_value' => isset($_POST['min_value']) && $_POST['min_value'] !== '' ? floatval($_POST['min_value']) : null,
        'max_value' => isset($_POST['max_value']) && $_POST['max_value'] !== '' ? floatval($_POST['max_value']) : null,
        'price_impact' => isset($_POST['price_impact']) ? floatval($_POST['price_impact']) : 0,
        'price_type' => isset($_POST['price_type']) ? sanitize_text_field($_POST['price_type']) : 'fixed',
        'option_label' => isset($_POST['option_label']) ? sanitize_text_field($_POST['option_label']) : '',
        'step' => isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '',
        'unit' => isset($_POST['unit']) ? sanitize_text_field($_POST['unit']) : '',
        'min_length' => isset($_POST['min_length']) && $_POST['min_length'] !== '' ? absint($_POST['min_length']) : null,
        'max_length' => isset($_POST['max_length']) && $_POST['max_length'] !== '' ? absint($_POST['max_length']) : null,
        'rows' => isset($_POST['rows']) && $_POST['rows'] !== '' ? absint($_POST['rows']) : null
    );
    
    // Handle choices for select/radio options
    if (($option_data['type'] === 'select' || $option_data['type'] === 'radio') && isset($_POST['choice_value']) && is_array($_POST['choice_value'])) {
        $choices = array();
        $choice_values = $_POST['choice_value'];
        $choice_labels = isset($_POST['choice_label']) ? $_POST['choice_label'] : array();
        $choice_prices = isset($_POST['choice_price']) ? $_POST['choice_price'] : array();
        
        for ($i = 0; $i < count($choice_values); $i++) {
            $value = trim($choice_values[$i]);
            if (empty($value)) continue; // Skip empty values
            
            $label = isset($choice_labels[$i]) ? trim($choice_labels[$i]) : $value;
            $price = isset($choice_prices[$i]) ? floatval($choice_prices[$i]) : 0;
            
            if ($price > 0) {
                $choices[] = "$value|$label:$price";
            } else {
                $choices[] = "$value|$label";
            }
        }
        
        $option_data['options'] = implode("\n", $choices);
    }
    
    // Add ID if editing
    if (isset($_POST['option_id']) && !empty($_POST['option_id'])) {
        $option_data['id'] = absint($_POST['option_id']);
    }
    
    // Validate required fields
    $errors = array();
    if (empty($option_data['name'])) {
        $errors[] = __('Option name is required', 'mobooking');
    }
    if (empty($option_data['type'])) {
        $errors[] = __('Option type is required', 'mobooking');
    }
    
    if (!empty($errors)) {
        wp_send_json_error(['message' => implode('<br>', $errors)]);
        return;
    }
    
    // Initialize the service manager
    $services_manager = new \MoBooking\Services\ServiceManager();
    
    // Save option
    $option_id = $services_manager->save_option($option_data);
    
    if (!$option_id) {
        wp_send_json_error(['message' => __('Failed to save option. Please try again.', 'mobooking')]);
        return;
    }
    
    // Return success
    wp_send_json_success([
        'id' => $option_id,
        'message' => isset($option_data['id']) ? 
            __('Option updated successfully', 'mobooking') : 
            __('Option created successfully', 'mobooking')
    ]);
}

/**
 * AJAX handler for deleting a service option
 */
function mobooking_delete_option_ajax_handler() {
    // Check nonce
    if (!isset($_POST['option_nonce']) || !wp_verify_nonce($_POST['option_nonce'], 'mobooking-option-nonce')) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
    }
    
    // Check option ID
    if (!isset($_POST['option_id']) || empty($_POST['option_id'])) {
        wp_send_json_error(['message' => __('No option specified.', 'mobooking')]);
    }
    
    $option_id = absint($_POST['option_id']);
    
    // Initialize the service manager
    $services_manager = new \MoBooking\Services\ServiceManager();
    
    // Delete option
    $result = $services_manager->delete_service_option($option_id);
    
    if (!$result) {
        wp_send_json_error(['message' => __('Failed to delete option.', 'mobooking')]);
        return;
    }
    
    // Return success
    wp_send_json_success([
        'message' => __('Option deleted successfully', 'mobooking')
    ]);
}