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
    
    mobooking_log('Direct options save handler called', isset($_POST['options']) ? 'Options present' : 'No options');
    
    // Get basic service data
    $service_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $user_id = get_current_user_id();
    
    if (!$service_id) {
        mobooking_log('No service ID provided for options');
        wp_send_json_error(['message' => 'Service ID is required']);
        return;
    }
    
    // Check if options are in JSON format and decode if needed
    $options_data = null;
    if (isset($_POST['options'])) {
        if (is_string($_POST['options'])) {
            // Try to decode as JSON
            $decoded = json_decode(stripslashes($_POST['options']), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $options_data = $decoded;
                mobooking_log('Decoded options from JSON', count($options_data) . ' options found');
            } else {
                mobooking_log('Failed to decode options JSON', json_last_error_msg());
            }
        } elseif (is_array($_POST['options'])) {
            $options_data = $_POST['options'];
            mobooking_log('Using options array directly', count($options_data) . ' options found');
        }
    }
    
    // Skip if no options
    if (!$options_data) {
        mobooking_log('No options data available to save');
        wp_send_json_success(['message' => 'Service saved but no options to process']);
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
        mobooking_log('Service not found or does not belong to user');
        wp_send_json_error(['message' => 'Service not found']);
        return;
    }
    
    // Check if entity_type column exists (needed for correct schema detection)
    $entity_type_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'entity_type'");
    
    if (!$entity_type_exists) {
        mobooking_log('Entity type column not found in services table');
        wp_send_json_error(['message' => 'Database schema does not support options']);
        return;
    }
    
    // Begin transaction for safety
    $wpdb->query('START TRANSACTION');
    
    try {
        // First, remove old options
        $deleted = $wpdb->delete(
            $table_name, 
            ['parent_id' => $service_id, 'entity_type' => 'option'],
            ['%d', '%s']
        );
        mobooking_log("Deleted existing options", "$deleted options removed");
        
        // Now save new options
        foreach ($options_data as $index => $option) {
            // Skip if no name
            if (empty($option['name'])) {
                mobooking_log("Skipping option at index $index", "No name provided");
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
            
            mobooking_log('Inserting option', $option_data['name'] . ' (' . $option_data['type'] . ')');
            
            // Clean up null values for proper DB insertion
            foreach ($option_data as $key => $value) {
                if ($value === null) {
                    $option_data[$key] = '';
                }
            }
            
            $result = $wpdb->insert($table_name, $option_data);
            
            if ($result) {
                $success++;
                mobooking_log("Successfully inserted option", "ID: " . $wpdb->insert_id);
            } else {
                $errors++;
                mobooking_log('Error inserting option', $wpdb->last_error);
            }
        }
        
        // Commit if successful
        $wpdb->query('COMMIT');
        
        mobooking_log("Options save completed", "$success saved, $errors failed");
        wp_send_json_success([
            'message' => "Service updated with $success options",
            'options_saved' => $success
        ]);
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        mobooking_log('Exception saving options', $e->getMessage());
        wp_send_json_error(['message' => 'Error saving options: ' . $e->getMessage()]);
    }
}