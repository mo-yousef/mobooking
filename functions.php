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
 * Improved direct database access for service options
 */
function mobooking_direct_get_service_options() {
    // Check nonce and permissions
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
    }
    
    // Check service ID
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        wp_send_json_error(array('message' => 'No service specified.'));
    }
    
    global $wpdb;
    $service_id = absint($_POST['service_id']);
    $options = array();
    
    // Try multiple database structures to ensure we find all options
    
    // 1. Try the unified table with entity_type
    $services_table = $wpdb->prefix . 'mobooking_services';
    $entity_type_exists = $wpdb->get_var("SHOW COLUMNS FROM {$services_table} LIKE 'entity_type'");
    
    if ($entity_type_exists) {
        // New structure with entity_type
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $services_table WHERE parent_id = %d AND entity_type = 'option' ORDER BY display_order ASC, id ASC",
            $service_id
        ));
    }
    
    // 2. If no options found, try the separate options table
    if (empty($options)) {
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'");
        
        if ($table_exists) {
            $options = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $options_table WHERE service_id = %d ORDER BY display_order ASC, id ASC",
                $service_id
            ));
        }
    }
    
    // Debug log what we found
    error_log('Found ' . count($options) . ' options for service ID ' . $service_id);
    
    wp_send_json_success(array(
        'options' => $options ?: array()
    ));
}

// Register the direct access endpoint
add_action('wp_ajax_mobooking_direct_get_service_options', 'mobooking_direct_get_service_options');









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
 * Make sure the option nonce is included in the localized script data
 */
function mobooking_update_localized_data() {
    wp_localize_script('jquery', 'mobooking_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-service-nonce'),
        'option_nonce' => wp_create_nonce('mobooking-option-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mobooking_update_localized_data', 20);

/**
 * Unified AJAX handler for saving service options
 */
function mobooking_save_option_ajax_handler() {
    // Debug logging
    error_log('Option save request received: ' . json_encode($_POST));
    
    // Check for different potential nonce field names
    $nonce_verified = false;
    
    if (isset($_POST['option_nonce']) && wp_verify_nonce($_POST['option_nonce'], 'mobooking-option-nonce')) {
        $nonce_verified = true;
    } elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        $nonce_verified = true;
    }
    
    if (!$nonce_verified) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
        return;
    }
    
    // Check service ID
    if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
        wp_send_json_error(['message' => __('No service specified.', 'mobooking')]);
        return;
    }
    
    $service_id = absint($_POST['service_id']);
    $user_id = get_current_user_id();
    
    // Get option ID if editing
    $option_id = isset($_POST['option_id']) && !empty($_POST['option_id']) ? absint($_POST['option_id']) : 0;
    
    // Prepare the option data
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
        'options' => isset($_POST['options']) ? sanitize_textarea_field($_POST['options']) : '',
        'option_label' => isset($_POST['option_label']) ? sanitize_text_field($_POST['option_label']) : '',
        'step' => isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '',
        'unit' => isset($_POST['unit']) ? sanitize_text_field($_POST['unit']) : '',
        'min_length' => isset($_POST['min_length']) && $_POST['min_length'] !== '' ? absint($_POST['min_length']) : null,
        'max_length' => isset($_POST['max_length']) && $_POST['max_length'] !== '' ? absint($_POST['max_length']) : null,
        'rows' => isset($_POST['rows']) && $_POST['rows'] !== '' ? absint($_POST['rows']) : null
    );
    
    // Add ID if editing
    if ($option_id > 0) {
        $option_data['id'] = $option_id;
    }
    
    // Validate data
    if (empty($option_data['name'])) {
        wp_send_json_error(['message' => __('Option name is required.', 'mobooking')]);
        return;
    }
    
    // Now save directly to the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    // Check if the services table has entity_type column - important for schema detection
    $entity_type_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'entity_type'");
    
    if ($entity_type_exists) {
        // New unified structure
        
        // Get user_id from parent service
        $service_user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE id = %d",
            $service_id
        ));
        
        if (!$service_user_id) {
            wp_send_json_error(['message' => __('Service not found.', 'mobooking')]);
            return;
        }
        
        // Prepare the data for the unified table structure
        $db_data = array(
            'user_id' => $service_user_id,
            'parent_id' => $service_id,
            'entity_type' => 'option',
            'name' => $option_data['name'],
            'description' => $option_data['description'],
            'price' => 0, // Options don't have a base price
            'duration' => 0, // Options don't have a duration
            'type' => $option_data['type'],
            'is_required' => $option_data['is_required'],
            'default_value' => $option_data['default_value'],
            'placeholder' => $option_data['placeholder'],
            'min_value' => $option_data['min_value'],
            'max_value' => $option_data['max_value'],
            'price_impact' => $option_data['price_impact'],
            'price_type' => $option_data['price_type'],
            'options' => $option_data['options'],
            'option_label' => $option_data['option_label'],
            'step' => $option_data['step'],
            'unit' => $option_data['unit'],
            'min_length' => $option_data['min_length'],
            'max_length' => $option_data['max_length'],
            'rows' => $option_data['rows']
        );
        
        // Define formats for proper sanitization
        $formats = array(
            '%d', // user_id
            '%d', // parent_id
            '%s', // entity_type
            '%s', // name
            '%s', // description
            '%f', // price
            '%d', // duration
            '%s', // type
            '%d', // is_required
            '%s', // default_value
            '%s', // placeholder
            is_null($db_data['min_value']) ? '%s' : '%f', // min_value
            is_null($db_data['max_value']) ? '%s' : '%f', // max_value
            '%f', // price_impact
            '%s', // price_type
            '%s', // options
            '%s', // option_label
            '%s', // step
            '%s', // unit
            is_null($db_data['min_length']) ? '%s' : '%d', // min_length
            is_null($db_data['max_length']) ? '%s' : '%d', // max_length
            is_null($db_data['rows']) ? '%s' : '%d'  // rows
        );
        
        if ($option_id > 0) {
            // Update existing option
            $result = $wpdb->update(
                $table_name,
                $db_data,
                array('id' => $option_id),
                $formats,
                array('%d')
            );
            
            if ($result === false) {
                error_log('Failed to update option: ' . $wpdb->last_error);
                wp_send_json_error(['message' => __('Failed to update option.', 'mobooking')]);
                return;
            }
            
            $new_option_id = $option_id;
        } else {
            // Get highest display order for this service
            $highest_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(display_order) FROM $table_name WHERE parent_id = %d AND entity_type = 'option'",
                $service_id
            ));
            
            $db_data['display_order'] = ($highest_order !== null) ? intval($highest_order) + 1 : 0;
            
            // Insert new option
            $result = $wpdb->insert($table_name, $db_data, $formats);
            
            if ($result === false) {
                error_log('Failed to insert option: ' . $wpdb->last_error);
                wp_send_json_error(['message' => __('Failed to create option.', 'mobooking')]);
                return;
            }
            
            $new_option_id = $wpdb->insert_id;
        }
    } else {
        // Old structure with separate table
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Check if the options table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'");
        
        if (!$table_exists) {
            error_log('Options table does not exist and services table does not have entity_type');
            wp_send_json_error(['message' => __('Database structure not supported.', 'mobooking')]);
            return;
        }
        
        // Prepare data for old structure
        $db_data = array(
            'service_id' => $service_id,
            'name' => $option_data['name'],
            'description' => $option_data['description'],
            'type' => $option_data['type'],
            'is_required' => $option_data['is_required'],
            'default_value' => $option_data['default_value'],
            'placeholder' => $option_data['placeholder'],
            'min_value' => $option_data['min_value'],
            'max_value' => $option_data['max_value'],
            'price_impact' => $option_data['price_impact'],
            'price_type' => $option_data['price_type'],
            'options' => $option_data['options'],
            'option_label' => $option_data['option_label'],
            'step' => $option_data['step'],
            'unit' => $option_data['unit'],
            'min_length' => $option_data['min_length'],
            'max_length' => $option_data['max_length'],
            'rows' => $option_data['rows']
        );
        
        $formats = array(
            '%d', // service_id
            '%s', // name
            '%s', // description
            '%s', // type
            '%d', // is_required
            '%s', // default_value
            '%s', // placeholder
            is_null($db_data['min_value']) ? '%s' : '%f', // min_value
            is_null($db_data['max_value']) ? '%s' : '%f', // max_value
            '%f', // price_impact
            '%s', // price_type
            '%s', // options
            '%s', // option_label
            '%s', // step
            '%s', // unit
            is_null($db_data['min_length']) ? '%s' : '%d', // min_length
            is_null($db_data['max_length']) ? '%s' : '%d', // max_length
            is_null($db_data['rows']) ? '%s' : '%d'  // rows
        );
        
        if ($option_id > 0) {
            // Update existing option
            $result = $wpdb->update(
                $options_table,
                $db_data,
                array('id' => $option_id),
                $formats,
                array('%d')
            );
            
            if ($result === false) {
                error_log('Failed to update option in options table: ' . $wpdb->last_error);
                wp_send_json_error(['message' => __('Failed to update option.', 'mobooking')]);
                return;
            }
            
            $new_option_id = $option_id;
        } else {
            // Get highest display order
            $highest_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(display_order) FROM $options_table WHERE service_id = %d",
                $service_id
            ));
            
            $db_data['display_order'] = ($highest_order !== null) ? intval($highest_order) + 1 : 0;
            
            // Insert new option
            $result = $wpdb->insert($options_table, $db_data, $formats);
            
            if ($result === false) {
                error_log('Failed to insert option into options table: ' . $wpdb->last_error);
                wp_send_json_error(['message' => __('Failed to create option.', 'mobooking')]);
                return;
            }
            
            $new_option_id = $wpdb->insert_id;
        }
    }
    
    // Return success
    wp_send_json_success([
        'id' => $new_option_id,
        'message' => $option_id > 0 ? 
            __('Option updated successfully', 'mobooking') : 
            __('Option created successfully', 'mobooking')
    ]);
}
// Register the AJAX handler for option saving
add_action('wp_ajax_mobooking_save_option_ajax', 'mobooking_save_option_ajax_handler');


/**
 * AJAX handler for deleting a service option
 */
function mobooking_delete_option_ajax_handler() {
    // Check nonce
    if (!isset($_POST['option_nonce']) && !isset($_POST['nonce'])) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
        return;
    }
    
    $nonce_verified = false;
    if (isset($_POST['option_nonce']) && wp_verify_nonce($_POST['option_nonce'], 'mobooking-option-nonce')) {
        $nonce_verified = true;
    } elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        $nonce_verified = true;
    }
    
    if (!$nonce_verified) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
        return;
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
        return;
    }
    
    // Check option ID
    if (!isset($_POST['option_id']) || empty($_POST['option_id'])) {
        wp_send_json_error(['message' => __('No option specified.', 'mobooking')]);
        return;
    }
    
    $option_id = absint($_POST['option_id']);
    
    // Delete from appropriate table
    global $wpdb;
    $services_table = $wpdb->prefix . 'mobooking_services';
    $options_table = $wpdb->prefix . 'mobooking_service_options';
    
    // Check if entity_type exists in services table
    $entity_type_exists = $wpdb->get_var("SHOW COLUMNS FROM {$services_table} LIKE 'entity_type'");
    
    if ($entity_type_exists) {
        // Delete from unified table
        $result = $wpdb->delete(
            $services_table, 
            ['id' => $option_id, 'entity_type' => 'option'], 
            ['%d', '%s']
        );
    } else {
        // Delete from options table
        $result = $wpdb->delete(
            $options_table,
            ['id' => $option_id],
            ['%d']
        );
    }
    
    if ($result === false) {
        wp_send_json_error(['message' => __('Failed to delete option.', 'mobooking')]);
        return;
    }
    
    wp_send_json_success([
        'message' => __('Option deleted successfully', 'mobooking')
    ]);
}
add_action('wp_ajax_mobooking_delete_option_ajax', 'mobooking_delete_option_ajax_handler');





// Register the unified service save AJAX handler
function register_unified_service_handler() {
    add_action('wp_ajax_mobooking_save_unified_service', function() {
        $service_manager = new \MoBooking\Services\ServiceManager();
        $service_manager->ajax_save_unified_service();
    });
}
add_action('init', 'register_unified_service_handler');