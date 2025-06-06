<?php
namespace MoBooking\Services;

/**
 * Service Options Manager class - Fixed Duplicate Issues
 */
class ServiceOptionsManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_service_option', array($this, 'ajax_save_option'));
        add_action('wp_ajax_mobooking_delete_service_option', array($this, 'ajax_delete_option'));
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_options'));
        add_action('wp_ajax_mobooking_get_service_option', array($this, 'ajax_get_option'));
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Service Options AJAX handlers registered');
        }
    }
    

    
    /**
     * Get options for a service
     */
    public function get_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_id = %d ORDER BY display_order ASC, id ASC",
            $service_id
        ));
    }
    
    /**
     * Get a specific option
     */
    public function get_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $option_id
        ));
    }
    
    /**
     * Save an option - FIXED to prevent duplicates with transaction support
     */
    public function save_option($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Saving option with data: ' . print_r($data, true));
        }
        
        // Sanitize data
        $option_data = array(
            'service_id' => absint($data['service_id']),
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'type' => sanitize_text_field($data['type']),
            'is_required' => isset($data['is_required']) ? absint($data['is_required']) : 0,
            'default_value' => isset($data['default_value']) ? sanitize_textarea_field($data['default_value']) : '',
            'placeholder' => isset($data['placeholder']) ? sanitize_text_field($data['placeholder']) : '',
            'min_value' => isset($data['min_value']) && $data['min_value'] !== '' ? floatval($data['min_value']) : null,
            'max_value' => isset($data['max_value']) && $data['max_value'] !== '' ? floatval($data['max_value']) : null,
            'price_impact' => isset($data['price_impact']) ? floatval($data['price_impact']) : 0,
            'price_type' => isset($data['price_type']) ? sanitize_text_field($data['price_type']) : 'fixed',
            'options' => isset($data['options']) ? sanitize_textarea_field($data['options']) : '',
            'option_label' => isset($data['option_label']) ? sanitize_text_field($data['option_label']) : '',
            'step' => isset($data['step']) ? sanitize_text_field($data['step']) : '1',
            'unit' => isset($data['unit']) ? sanitize_text_field($data['unit']) : '',
            'min_length' => isset($data['min_length']) && $data['min_length'] !== '' ? absint($data['min_length']) : null,
            'max_length' => isset($data['max_length']) && $data['max_length'] !== '' ? absint($data['max_length']) : null,
            'rows' => isset($data['rows']) ? absint($data['rows']) : 3,
            'display_order' => isset($data['display_order']) ? absint($data['display_order']) : $this->get_next_display_order($data['service_id'])
        );
        
        // IMPROVED TRANSACTION HANDLING - Use MySQL transactions to ensure atomicity
        $wpdb->query('START TRANSACTION');
        
        try {
            // Check if we're updating or creating - FIXED LOGIC
            if (!empty($data['id']) && absint($data['id']) > 0) {
                // Update existing option
                $option_id = absint($data['id']);
                
                // Verify the option exists and belongs to the correct service
                $existing_option = $this->get_option($option_id);
                if (!$existing_option || $existing_option->service_id != $option_data['service_id']) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Option not found or service mismatch for ID: ' . $option_id);
                    }
                    $wpdb->query('ROLLBACK');
                    return false;
                }
                
                $result = $wpdb->update(
                    $table_name,
                    $option_data,
                    array('id' => $option_id),
                    array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d'),
                    array('%d')
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Update result: ' . ($result !== false ? 'SUCCESS' : 'FAILED') . ' for option ID: ' . $option_id);
                }
                
                if ($result === false) {
                    $wpdb->query('ROLLBACK');
                    return false;
                }
                
                $wpdb->query('COMMIT');
                return $option_id;
            } else {
                // Create new option - ensure no duplicate names for the same service using a direct SELECT FOR UPDATE
                // This uses a database lock to prevent race conditions
                $wpdb->query($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE service_id = %d AND name = %s FOR UPDATE",
                    $option_data['service_id'],
                    $option_data['name']
                ));
                
                $existing_option = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE service_id = %d AND name = %s",
                    $option_data['service_id'],
                    $option_data['name']
                ));
                
                if ($existing_option) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Option with same name already exists for service: ' . $option_data['service_id']);
                    }
                    $wpdb->query('ROLLBACK');
                    return false; // Prevent duplicate names
                }
                
                $result = $wpdb->insert(
                    $table_name,
                    $option_data,
                    array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Insert result: ' . ($result !== false ? 'SUCCESS' : 'FAILED') . ', Insert ID: ' . $wpdb->insert_id);
                }
                
                if ($result === false) {
                    $wpdb->query('ROLLBACK');
                    return false;
                }
                
                $new_id = $wpdb->insert_id;
                $wpdb->query('COMMIT');
                return $new_id;
            }
        } catch (Exception $e) {
            // Rollback on any exception
            $wpdb->query('ROLLBACK');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception during option save: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Delete an option
     */
    public function delete_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $option_id),
            array('%d')
        );
    }
    
    /**
     * Get next display order for a service
     */
    private function get_next_display_order($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $max_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(display_order) FROM $table_name WHERE service_id = %d",
            $service_id
        ));
        
        return $max_order ? $max_order + 1 : 1;
    }
    
    /**
     * Update options order
     */
    public function update_options_order($service_id, $order_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        foreach ($order_data as $item) {
            $wpdb->update(
                $table_name,
                array('display_order' => absint($item['order'])),
                array('id' => absint($item['id']), 'service_id' => $service_id),
                array('%d'),
                array('%d', '%d')
            );
        }
        
        return true;
    }
    
    /**
     * Parse option choices from string
     */
    public function parse_choices($options_string) {
        if (empty($options_string)) {
            return array();
        }
        
        $choices = array();
        $lines = explode("\n", $options_string);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $parts = explode('|', $line);
            $value = trim($parts[0]);
            
            if (empty($value)) {
                continue;
            }
            
            $label = $value;
            $price = 0;
            
            if (isset($parts[1])) {
                $label_price = explode(':', $parts[1]);
                $label = trim($label_price[0]);
                if (isset($label_price[1])) {
                    $price = floatval($label_price[1]);
                }
            }
            
            $choices[] = array(
                'value' => $value,
                'label' => $label,
                'price' => $price
            );
        }
        
        return $choices;
    }
    
    /**
     * Calculate option price impact
     */
    public function calculate_price_impact($option, $value) {
        if ($option->price_type === 'none' || $option->price_impact == 0) {
            return 0;
        }
        
        $price_impact = 0;
        
        switch ($option->price_type) {
            case 'fixed':
                $price_impact = $option->price_impact;
                break;
                
            case 'percentage':
                $price_impact = $option->price_impact;
                break;
                
            case 'multiply':
                if (is_numeric($value)) {
                    $price_impact = $option->price_impact * floatval($value);
                }
                break;
                
            case 'choice':
                if (in_array($option->type, array('select', 'radio'))) {
                    $choices = $this->parse_choices($option->options);
                    foreach ($choices as $choice) {
                        if ($choice['value'] === $value) {
                            $price_impact = $choice['price'];
                            break;
                        }
                    }
                }
                break;
        }
        
        return $price_impact;
    }
    
    /**
     * Validate option value
     */
    public function validate_option_value($option, $value) {
        $errors = array();
        
        // Check if required
        if ($option->is_required && (empty($value) && $value !== '0')) {
            $errors[] = sprintf(__('%s is required.', 'mobooking'), $option->name);
            return $errors;
        }
        
        // Skip validation for empty optional fields
        if (empty($value) && $value !== '0') {
            return $errors;
        }
        
        // Type-specific validation
        switch ($option->type) {
            case 'number':
            case 'quantity':
                if (!is_numeric($value)) {
                    $errors[] = sprintf(__('%s must be a number.', 'mobooking'), $option->name);
                } else {
                    $num_value = floatval($value);
                    
                    if ($option->min_value !== null && $num_value < $option->min_value) {
                        $errors[] = sprintf(__('%s must be at least %s.', 'mobooking'), $option->name, $option->min_value);
                    }
                    
                    if ($option->max_value !== null && $num_value > $option->max_value) {
                        $errors[] = sprintf(__('%s must be no more than %s.', 'mobooking'), $option->name, $option->max_value);
                    }
                }
                break;
                
            case 'text':
            case 'textarea':
                $length = strlen($value);
                
                if ($option->min_length !== null && $length < $option->min_length) {
                    $errors[] = sprintf(__('%s must be at least %d characters.', 'mobooking'), $option->name, $option->min_length);
                }
                
                if ($option->max_length !== null && $length > $option->max_length) {
                    $errors[] = sprintf(__('%s must be no more than %d characters.', 'mobooking'), $option->name, $option->max_length);
                }
                break;
                
            case 'select':
            case 'radio':
                $choices = $this->parse_choices($option->options);
                $valid_values = array_column($choices, 'value');
                
                if (!in_array($value, $valid_values)) {
                    $errors[] = sprintf(__('Invalid value for %s.', 'mobooking'), $option->name);
                }
                break;
        }
        
        return $errors;
    }
    
    /**
     * AJAX handler to save option - COMPLETELY FIXED to prevent duplicates
     */
    public function ajax_save_option() {
        // Use a more robust approach with a request-specific lock
        $request_id = isset($_POST['request_id']) ? sanitize_text_field($_POST['request_id']) : '';
        $lock_key = 'mobooking_option_lock_' . md5($request_id . json_encode($_POST));
        
        // Check if this exact request is already being processed
        if (get_transient($lock_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Preventing duplicate option processing for request ID: ' . $request_id);
            }
            wp_send_json_error(__('This request is already being processed.', 'mobooking'));
            return;
        }
        
        // Set a short-lived lock (60 seconds should be more than enough)
        set_transient($lock_key, time(), 60);
        
        try {
            // Prevent duplicate processing using constant (fallback)
            if (defined('MOBOOKING_PROCESSING_OPTION')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Preventing duplicate option processing');
                }
                wp_send_json_error(__('Request already being processed.', 'mobooking'));
                return;
            }
            define('MOBOOKING_PROCESSING_OPTION', true);
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: ajax_save_option called with POST data: ' . print_r($_POST, true));
            }
            
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Nonce verification failed for save_option');
                }
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Permission check failed for save_option');
                }
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
                return;
            }
            
            $user_id = get_current_user_id();
            
            // Verify service ownership
            $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
            if (!$service_id) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: No service_id provided for save_option');
                }
                wp_send_json_error(__('Service ID is required.', 'mobooking'));
                return;
            }
            
            $service_manager = new ServiceManager();
            $service = $service_manager->get_service($service_id, $user_id);
            if (!$service) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Service not found or access denied for save_option. Service ID: ' . $service_id . ', User ID: ' . $user_id);
                }
                wp_send_json_error(__('Service not found or access denied.', 'mobooking'));
                return;
            }
            
            // Check if there's already a recent duplicate
            $option_name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $duplicate_guard_key = 'option_saved_' . $service_id . '_' . md5($option_name);
            
            if (get_transient($duplicate_guard_key)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Preventing duplicate option with name: ' . $option_name);
                }
                
                // If this is an update (has ID) rather than a new option, allow it
                if (!isset($_POST['id']) || empty($_POST['id'])) {
                    wp_send_json_error(__('This option was just created. Please refresh the page.', 'mobooking'));
                    return;
                }
            }
            
            // Prepare option data
            $option_data = array(
                'service_id' => $service_id,
                'name' => $option_name,
                'description' => isset($_POST['description']) ? trim($_POST['description']) : '',
                'type' => isset($_POST['type']) ? $_POST['type'] : 'text',
                'is_required' => isset($_POST['is_required']) ? absint($_POST['is_required']) : 0,
                'price_type' => isset($_POST['price_type']) ? $_POST['price_type'] : 'fixed',
                'price_impact' => isset($_POST['price_impact']) ? floatval($_POST['price_impact']) : 0,
                'default_value' => isset($_POST['default_value']) ? trim($_POST['default_value']) : '',
                'placeholder' => isset($_POST['placeholder']) ? trim($_POST['placeholder']) : '',
                'min_value' => isset($_POST['min_value']) && $_POST['min_value'] !== '' ? floatval($_POST['min_value']) : null,
                'max_value' => isset($_POST['max_value']) && $_POST['max_value'] !== '' ? floatval($_POST['max_value']) : null,
                'step' => isset($_POST['step']) ? $_POST['step'] : '1',
                'unit' => isset($_POST['unit']) ? trim($_POST['unit']) : '',
                'min_length' => isset($_POST['min_length']) && $_POST['min_length'] !== '' ? absint($_POST['min_length']) : null,
                'max_length' => isset($_POST['max_length']) && $_POST['max_length'] !== '' ? absint($_POST['max_length']) : null,
                'rows' => isset($_POST['rows']) ? absint($_POST['rows']) : 3,
                'options' => isset($_POST['options']) ? trim($_POST['options']) : '',
                'option_label' => isset($_POST['option_label']) ? trim($_POST['option_label']) : ''
            );
            
            // Add ID if editing - FIXED LOGIC
            if (isset($_POST['id']) && !empty($_POST['id']) && absint($_POST['id']) > 0) {
                $option_data['id'] = absint($_POST['id']);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Updating existing option with ID: ' . $option_data['id']);
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Creating new option');
                }
            }
            
            // Validate data
            if (empty($option_data['name'])) {
                wp_send_json_error(__('Option name is required.', 'mobooking'));
                return;
            }
            
            // Validate choices for select/radio types
            if (in_array($option_data['type'], array('select', 'radio'))) {
                if (empty($option_data['options'])) {
                    wp_send_json_error(__('At least one choice is required for this option type.', 'mobooking'));
                    return;
                }
                
                $choices = $this->parse_choices($option_data['options']);
                if (empty($choices)) {
                    wp_send_json_error(__('Invalid choices format. Please ensure each choice has a value.', 'mobooking'));
                    return;
                }
            }
            
            // Save option
            $option_id = $this->save_option($option_data);
            
            if (!$option_id) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Failed to save option. Data: ' . print_r($option_data, true));
                }
                wp_send_json_error(__('Failed to save option. This may be a duplicate name.', 'mobooking'));
                return;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Option saved successfully with ID: ' . $option_id);
            }
            
            // After successful save, add a transient to prevent immediate duplicates
            set_transient($duplicate_guard_key, $option_id, 30); // 30 seconds guard
            
            wp_send_json_success(array(
                'id' => $option_id,
                'message' => __('Option saved successfully.', 'mobooking')
            ));
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_save_option: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while saving the option.', 'mobooking'));
        } finally {
            // Always clean up the lock when done
            delete_transient($lock_key);
        }
    }
    
    /**
     * AJAX handler to delete option
     */
    public function ajax_delete_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $option_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!$option_id) {
            wp_send_json_error(__('Option ID is required.', 'mobooking'));
        }
        
        // Verify ownership through service
        $option = $this->get_option($option_id);
        if (!$option) {
            wp_send_json_error(__('Option not found.', 'mobooking'));
        }
        
        $service_manager = new ServiceManager();
        $service = $service_manager->get_service($option->service_id, get_current_user_id());
        if (!$service) {
            wp_send_json_error(__('Access denied.', 'mobooking'));
        }
        
        // Delete option
        $result = $this->delete_option($option_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to delete option.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Option deleted successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to get options
     */
    public function ajax_get_options() {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: ajax_get_options called with data: ' . print_r($_POST, true));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Nonce verification failed for get_options');
            }
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Permission check failed for get_options');
            }
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        if (!$service_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: No service_id provided for get_options');
            }
            wp_send_json_error(__('Service ID is required.', 'mobooking'));
        }
        
        // Verify service ownership
        $service_manager = new ServiceManager();
        $service = $service_manager->get_service($service_id, get_current_user_id());
        if (!$service) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Service not found or access denied for get_options. Service ID: ' . $service_id . ', User ID: ' . get_current_user_id());
            }
            wp_send_json_error(__('Service not found or access denied.', 'mobooking'));
        }
        
        $options = $this->get_service_options($service_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Found ' . count($options) . ' options for service ' . $service_id);
        }
        
        wp_send_json_success(array(
            'options' => $options
        ));
    }
    
    /**
     * AJAX handler to get single option
     */
    public function ajax_get_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $option_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        if (!$option_id) {
            wp_send_json_error(__('Option ID is required.', 'mobooking'));
        }
        
        $option = $this->get_option($option_id);
        if (!$option) {
            wp_send_json_error(__('Option not found.', 'mobooking'));
        }
        
        // Verify ownership through service
        $service_manager = new ServiceManager();
        $service = $service_manager->get_service($option->service_id, get_current_user_id());
        if (!$service) {
            wp_send_json_error(__('Access denied.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'option' => $option
        ));
    }
    
    /**
     * AJAX handler to update options order
     */
    public function ajax_update_options_order() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        $order_data = isset($_POST['order_data']) ? json_decode(stripslashes($_POST['order_data']), true) : array();
        
        if (!$service_id || empty($order_data)) {
            wp_send_json_error(__('Invalid data provided.', 'mobooking'));
        }
        
        // Verify service ownership
        $service_manager = new ServiceManager();
        $service = $service_manager->get_service($service_id, get_current_user_id());
        if (!$service) {
            wp_send_json_error(__('Service not found or access denied.', 'mobooking'));
        }
        
        // Update order
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result) {
            wp_send_json_error(__('Failed to update options order.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Options order updated successfully.', 'mobooking')
        ));
    }
}