<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('MOBOOKING_VERSION', '1.0.0');
define('MOBOOKING_PATH', get_template_directory());
define('MOBOOKING_URL', get_template_directory_uri());

/**
 * Enhanced autoloader with better error handling
 */
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

/**
 * Initialize the theme
 */
function mobooking_init() {
    $loader = new MoBooking\Core\Loader();
    $loader->init();
}
add_action('after_setup_theme', 'mobooking_init', 5);

/**
 * Load dashicons on frontend
 */
function mobooking_load_dashicons_frontend() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'mobooking_load_dashicons_frontend');

/**
 * Register and localize scripts
 */
function mobooking_register_scripts() {
    // Ensure jQuery is loaded
    wp_enqueue_script('jquery');
    
    // Main data object for all scripts
    $main_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-nonce'),
        'serviceNonce' => wp_create_nonce('mobooking-service-nonce'),
        'option_nonce' => wp_create_nonce('mobooking-option-nonce')
    );
    
    // Localize scripts with data
    wp_localize_script('jquery', 'mobooking_data', $main_data);
    
    // Legacy localization objects (for backward compatibility)
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

/**
 * Utility function for logging (only when WP_DEBUG is enabled)
 */
function mobooking_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = $message;
        if ($data !== null) {
            $log_message .= ': ' . (is_array($data) || is_object($data) ? json_encode($data) : $data);
        }
        error_log($log_message);
    }
}

/**
 * Initialize service manager
 * This ensures only one service manager is used
 */
function mobooking_initialize_service_manager() {
    if (class_exists('\MoBooking\Services\ServiceManager')) {
        return new \MoBooking\Services\ServiceManager();
    }
    return null;
}

/**
 * Register service-related AJAX handlers
 */
function mobooking_register_service_handlers() {
    // Unified service save handler
    add_action('wp_ajax_mobooking_save_unified_service', function() {
        $service_manager = mobooking_initialize_service_manager();
        if ($service_manager && method_exists($service_manager, 'ajax_save_unified_service')) {
            $service_manager->ajax_save_unified_service();
        } else {
            wp_send_json_error(['message' => 'Service manager not available']);
        }
    });
    
    // Legacy service handlers for backward compatibility
    add_action('wp_ajax_mobooking_save_service_ajax', 'mobooking_save_service_ajax_handler');
    add_action('wp_ajax_mobooking_delete_service_ajax', 'mobooking_delete_service_ajax_handler');
    
    // Direct option management handlers
    add_action('wp_ajax_mobooking_direct_save_options', 'mobooking_direct_save_service_options');
}
add_action('init', 'mobooking_register_service_handlers');

/**
 * Legacy AJAX handler for saving a service
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
    $services_manager = mobooking_initialize_service_manager();
    
    if (!$services_manager) {
        wp_send_json_error(['message' => __('Service manager not available', 'mobooking')]);
        return;
    }
    
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
 * Legacy AJAX handler for deleting a service
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
    $services_manager = mobooking_initialize_service_manager();
    
    if (!$services_manager) {
        wp_send_json_error(['message' => __('Service manager not available', 'mobooking')]);
        return;
    }
    
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
 * Direct service options saving handler
 * A fallback solution for saving options when unified save fails
 */
function mobooking_direct_save_service_options() {
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(['message' => 'Security verification failed']);
        return;
    }
    
    // Get basic service data
    $service_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$service_id) {
        wp_send_json_error(['message' => 'Service ID is required']);
        return;
    }
    
    // Get options data - try multiple formats to be flexible
    $options_data = [];
    
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        // Direct array access
        $options_data = $_POST['options'];
    } else if (isset($_POST['options']) && is_string($_POST['options'])) {
        // JSON string
        $decoded = json_decode(stripslashes($_POST['options']), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $options_data = $decoded;
        }
    }
    
    // Skip if no options
    if (empty($options_data)) {
        wp_send_json_error(['message' => 'No option data provided']);
        return;
    }
    
    // Process options directly
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_services';
    $success = 0;
    $errors = 0;
    
    // Verify service exists and belongs to user
    $service = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $service_id, $user_id
    ));
    
    if (!$service) {
        wp_send_json_error(['message' => 'Service not found or does not belong to you']);
        return;
    }
    
    // Begin transaction for safety
    $wpdb->query('START TRANSACTION');
    
    try {
        // First, remove old options
        $wpdb->delete(
            $table_name, 
            ['parent_id' => $service_id, 'entity_type' => 'option'],
            ['%d', '%s']
        );
        
        // Now save new options
        foreach ($options_data as $index => $option) {
            // Skip if no name
            if (empty($option['name'])) {
                continue;
            }
            
            $option_data = [
                'user_id' => $user_id,
                'parent_id' => $service_id,
                'entity_type' => 'option',
                'name' => sanitize_text_field($option['name']),
                'description' => isset($option['description']) ? sanitize_textarea_field($option['description']) : '',
                'price' => 0, // Options don't have a base price
                'duration' => 0, // Options don't have a duration 
                'type' => isset($option['type']) ? sanitize_text_field($option['type']) : 'checkbox',
                'is_required' => isset($option['is_required']) ? absint($option['is_required']) : 0,
                'default_value' => isset($option['default_value']) ? sanitize_text_field($option['default_value']) : '',
                'placeholder' => isset($option['placeholder']) ? sanitize_text_field($option['placeholder']) : '',
                'min_value' => isset($option['min_value']) && $option['min_value'] !== '' ? floatval($option['min_value']) : null,
                'max_value' => isset($option['max_value']) && $option['max_value'] !== '' ? floatval($option['max_value']) : null,
                'price_impact' => isset($option['price_impact']) ? floatval($option['price_impact']) : 0,
                'price_type' => isset($option['price_type']) ? sanitize_text_field($option['price_type']) : 'fixed',
                'options' => isset($option['options']) ? sanitize_textarea_field($option['options']) : '',
                'option_label' => isset($option['option_label']) ? sanitize_text_field($option['option_label']) : '',
                'step' => isset($option['step']) ? sanitize_text_field($option['step']) : '',
                'unit' => isset($option['unit']) ? sanitize_text_field($option['unit']) : '',
                'min_length' => isset($option['min_length']) && $option['min_length'] !== '' ? absint($option['min_length']) : null,
                'max_length' => isset($option['max_length']) && $option['max_length'] !== '' ? absint($option['max_length']) : null,
                'rows' => isset($option['rows']) && $option['rows'] !== '' ? absint($option['rows']) : null,
                'display_order' => $index,
            ];
            
            // Clean up null values for proper DB insertion
            foreach ($option_data as $key => $value) {
                if ($value === null) {
                    $option_data[$key] = '';
                }
            }
            
            $result = $wpdb->insert($table_name, $option_data);
            
            if ($result) {
                $success++;
            } else {
                $errors++;
            }
        }
        
        // Commit if successful
        $wpdb->query('COMMIT');
        
        wp_send_json_success([
            'message' => "Service updated with $success options",
            'options_saved' => $success
        ]);
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'Error saving options: ' . $e->getMessage()]);
    }
}





















// DELETE THIS PART AFTER TESTING
// Add a direct debug endpoint
add_action('wp_ajax_mobooking_debug_save_options', function() {
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Log the received data
    error_log('Debug options received: ' . json_encode($_POST));
    
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        wp_send_json_error(['message' => 'Service ID required']);
        return;
    }
    
    // Get the options data
    $service_id = absint($_POST['id']);
    $user_id = get_current_user_id();
    $options_data = isset($_POST['options']) ? $_POST['options'] : [];
    
    // Save directly to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    // First, clear existing options
    $wpdb->delete($table_name, [
        'parent_id' => $service_id,
        'entity_type' => 'option'
    ]);
    
    $success_count = 0;
    
    // Insert new options
    foreach ($options_data as $option) {
        if (empty($option['name'])) continue;
        
        $result = $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'parent_id' => $service_id,
            'entity_type' => 'option',
            'name' => sanitize_text_field($option['name']),
            'description' => isset($option['description']) ? sanitize_textarea_field($option['description']) : '',
            'type' => isset($option['type']) ? sanitize_text_field($option['type']) : 'checkbox',
            'is_required' => isset($option['is_required']) ? (int)$option['is_required'] : 0,
            'price_impact' => isset($option['price_impact']) ? floatval($option['price_impact']) : 0,
            'price_type' => isset($option['price_type']) ? sanitize_text_field($option['price_type']) : 'fixed',
            'options' => isset($option['options']) ? sanitize_textarea_field($option['options']) : '',
            'option_label' => isset($option['option_label']) ? sanitize_text_field($option['option_label']) : '',
            'display_order' => isset($option['display_order']) ? intval($option['display_order']) : 0
        ]);
        
        if ($result) $success_count++;
    }
    
    wp_send_json_success([
        'message' => "Successfully saved $success_count options",
        'count' => $success_count
    ]);
});
// Add this code temporarily to your theme's functions.php, then load any page once:
add_action('init', function() {
    $migration = new MoBooking\Database\ServicesTableMigration();
    $result = $migration->run();
    error_log('Migration result: ' . ($result ? 'Success' : 'Failed'));
});






// Add this to your functions.php file for comprehensive debugging

// 1. First, let's create a simple test to verify your database structure
add_action('wp_ajax_mobooking_test_db_structure', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if (!$table_exists) {
        wp_send_json_error(['message' => 'Services table does not exist']);
        return;
    }
    
    // Get table structure
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $column_names = array_map(function($col) { return $col->Field; }, $columns);
    
    // Check for required columns
    $required_columns = ['id', 'user_id', 'entity_type', 'parent_id', 'name', 'type', 'is_required'];
    $missing_columns = array_diff($required_columns, $column_names);
    
    if (!empty($missing_columns)) {
        wp_send_json_error([
            'message' => 'Missing required columns: ' . implode(', ', $missing_columns),
            'existing_columns' => $column_names
        ]);
        return;
    }
    
    wp_send_json_success([
        'message' => 'Database structure is correct',
        'columns' => $column_names
    ]);
});

// 2. Create a comprehensive options saving handler with detailed logging
add_action('wp_ajax_mobooking_debug_options_save', function() {
    // Enable error logging
    error_log('=== MOBOOKING DEBUG OPTIONS SAVE START ===');
    
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        error_log('MOBOOKING DEBUG: Nonce verification failed');
        wp_send_json_error(['message' => 'Security verification failed']);
        return;
    }
    
    error_log('MOBOOKING DEBUG: Nonce verified successfully');
    
    // Get and validate service ID
    $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
    if (!$service_id) {
        error_log('MOBOOKING DEBUG: No service ID provided');
        wp_send_json_error(['message' => 'Service ID is required']);
        return;
    }
    
    error_log('MOBOOKING DEBUG: Service ID = ' . $service_id);
    
    // Get user ID
    $user_id = get_current_user_id();
    error_log('MOBOOKING DEBUG: User ID = ' . $user_id);
    
    // Log all POST data for debugging
    error_log('MOBOOKING DEBUG: Full POST data = ' . print_r($_POST, true));
    
    // Get options data - try multiple methods
    $options_data = null;
    
    if (isset($_POST['options']) && is_array($_POST['options'])) {
        $options_data = $_POST['options'];
        error_log('MOBOOKING DEBUG: Found options as direct array, count = ' . count($options_data));
    } elseif (isset($_POST['options']) && is_string($_POST['options'])) {
        $decoded = json_decode(stripslashes($_POST['options']), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $options_data = $decoded;
            error_log('MOBOOKING DEBUG: Found options as JSON string, count = ' . count($options_data));
        } else {
            error_log('MOBOOKING DEBUG: JSON decode error: ' . json_last_error_msg());
        }
    }
    
    // Also check for individual option fields
    if (!$options_data) {
        $options_data = [];
        $option_index = 0;
        
        while (isset($_POST["option_{$option_index}_name"])) {
            $option = [
                'name' => $_POST["option_{$option_index}_name"],
                'type' => $_POST["option_{$option_index}_type"] ?? 'checkbox',
                'description' => $_POST["option_{$option_index}_description"] ?? '',
                'is_required' => $_POST["option_{$option_index}_is_required"] ?? 0,
                'price_type' => $_POST["option_{$option_index}_price_type"] ?? 'fixed',
                'price_impact' => $_POST["option_{$option_index}_price_impact"] ?? 0,
            ];
            
            $options_data[] = $option;
            $option_index++;
        }
        
        if (count($options_data) > 0) {
            error_log('MOBOOKING DEBUG: Found options using individual fields, count = ' . count($options_data));
        }
    }
    
    if (empty($options_data)) {
        error_log('MOBOOKING DEBUG: No options data found in any format');
        wp_send_json_error(['message' => 'No options data found']);
        return;
    }
    
    error_log('MOBOOKING DEBUG: Options data = ' . print_r($options_data, true));
    
    // Verify service exists and belongs to user
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    $service = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND entity_type = 'service'",
        $service_id, $user_id
    ));
    
    if (!$service) {
        error_log('MOBOOKING DEBUG: Service not found or does not belong to user');
        wp_send_json_error(['message' => 'Service not found or access denied']);
        return;
    }
    
    error_log('MOBOOKING DEBUG: Service found: ' . $service->name);
    
    // Begin transaction
    $wpdb->query('START TRANSACTION');
    error_log('MOBOOKING DEBUG: Transaction started');
    
    try {
        // Delete existing options
        $deleted = $wpdb->delete(
            $table_name,
            [
                'parent_id' => $service_id,
                'entity_type' => 'option'
            ],
            ['%d', '%s']
        );
        
        error_log('MOBOOKING DEBUG: Deleted ' . $deleted . ' existing options');
        
        $success_count = 0;
        $error_count = 0;
        
        // Insert new options
        foreach ($options_data as $index => $option) {
            if (empty($option['name'])) {
                error_log('MOBOOKING DEBUG: Skipping option ' . $index . ' - no name');
                continue;
            }
            
            $option_data = [
                'user_id' => $user_id,
                'parent_id' => $service_id,
                'entity_type' => 'option',
                'name' => sanitize_text_field($option['name']),
                'description' => isset($option['description']) ? sanitize_textarea_field($option['description']) : '',
                'price' => 0,
                'duration' => 0,
                'type' => isset($option['type']) ? sanitize_text_field($option['type']) : 'checkbox',
                'is_required' => isset($option['is_required']) ? absint($option['is_required']) : 0,
                'default_value' => isset($option['default_value']) ? sanitize_text_field($option['default_value']) : '',
                'placeholder' => isset($option['placeholder']) ? sanitize_text_field($option['placeholder']) : '',
                'min_value' => isset($option['min_value']) && $option['min_value'] !== '' ? floatval($option['min_value']) : null,
                'max_value' => isset($option['max_value']) && $option['max_value'] !== '' ? floatval($option['max_value']) : null,
                'price_impact' => isset($option['price_impact']) ? floatval($option['price_impact']) : 0,
                'price_type' => isset($option['price_type']) ? sanitize_text_field($option['price_type']) : 'fixed',
                'options' => isset($option['options']) ? sanitize_textarea_field($option['options']) : '',
                'option_label' => isset($option['option_label']) ? sanitize_text_field($option['option_label']) : '',
                'step' => isset($option['step']) ? sanitize_text_field($option['step']) : '',
                'unit' => isset($option['unit']) ? sanitize_text_field($option['unit']) : '',
                'min_length' => isset($option['min_length']) && $option['min_length'] !== '' ? absint($option['min_length']) : null,
                'max_length' => isset($option['max_length']) && $option['max_length'] !== '' ? absint($option['max_length']) : null,
                'rows' => isset($option['rows']) && $option['rows'] !== '' ? absint($option['rows']) : null,
                'display_order' => $index,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ];
            
            error_log('MOBOOKING DEBUG: Inserting option ' . $index . ': ' . print_r($option_data, true));
            
            $result = $wpdb->insert($table_name, $option_data);
            
            if ($result === false) {
                error_log('MOBOOKING DEBUG: Failed to insert option ' . $index . ': ' . $wpdb->last_error);
                $error_count++;
            } else {
                error_log('MOBOOKING DEBUG: Successfully inserted option ' . $index . ' with ID: ' . $wpdb->insert_id);
                $success_count++;
            }
        }
        
        if ($error_count > 0) {
            $wpdb->query('ROLLBACK');
            error_log('MOBOOKING DEBUG: Rolling back due to errors');
            wp_send_json_error(['message' => "Failed to save some options. $error_count errors occurred."]);
        } else {
            $wpdb->query('COMMIT');
            error_log('MOBOOKING DEBUG: Transaction committed successfully');
            wp_send_json_success([
                'message' => "Successfully saved $success_count options",
                'options_saved' => $success_count
            ]);
        }
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        error_log('MOBOOKING DEBUG: Exception occurred: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Database error: ' . $e->getMessage()]);
    }
    
    error_log('=== MOBOOKING DEBUG OPTIONS SAVE END ===');
});

// 3. Add a simple test option save
add_action('wp_ajax_mobooking_test_option_save', function() {
    if (!isset($_POST['service_id'])) {
        wp_send_json_error(['message' => 'Service ID required']);
        return;
    }
    
    $service_id = absint($_POST['service_id']);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_services';
    
    // Try to insert a simple test option
    $result = $wpdb->insert($table_name, [
        'user_id' => $user_id,
        'parent_id' => $service_id,
        'entity_type' => 'option',
        'name' => 'Test Option',
        'description' => 'Test option created at ' . current_time('mysql'),
        'type' => 'checkbox',
        'is_required' => 0,
        'price' => 0,
        'duration' => 0,
        'display_order' => 0,
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    ]);
    
    if ($result === false) {
        wp_send_json_error([
            'message' => 'Failed to insert test option',
            'error' => $wpdb->last_error
        ]);
    } else {
        wp_send_json_success([
            'message' => 'Test option created successfully',
            'option_id' => $wpdb->insert_id
        ]);
    }
});
