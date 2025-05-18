<?php
namespace MoBooking\Services;

/**
 * Unified Service Manager
 * Handles services and their options in a single component
 */
class ServiceManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers for both services and options
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_nopriv_mobooking_get_services_by_zip', array($this, 'ajax_get_services_by_zip'));
        
        // Options-related AJAX handlers
        add_action('wp_ajax_mobooking_save_service_option', array($this, 'ajax_save_service_option'));
        add_action('wp_ajax_mobooking_get_service_option', array($this, 'ajax_get_service_option'));
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_service_options'));
        add_action('wp_ajax_mobooking_delete_service_option', array($this, 'ajax_delete_service_option'));
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
        
        // Enqueue assets for the service options
        add_action('wp_enqueue_scripts', array($this, 'enqueue_service_assets'));
    }

    /**
     * Enqueue assets for the service editor
     */
    public function enqueue_service_assets() {
        // Only enqueue on dashboard pages that need it
        if (is_page('dashboard') || strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
            wp_enqueue_script('mobooking-service-options-manager', MOBOOKING_URL . '/assets/js/service-options-manager.js', array('jquery', 'jquery-ui-sortable'), MOBOOKING_VERSION, true);
            
            wp_localize_script('mobooking-service-options-manager', 'mobooking_services', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking-service-nonce')
            ));
        }
    }
    
    /**
     * Get complete service data with all options
     */
    public function get_service_with_options($service_id, $user_id = null) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Get the service
        if ($user_id) {
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $services_table WHERE id = %d AND user_id = %d",
                $service_id, $user_id
            ));
        } else {
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $services_table WHERE id = %d",
                $service_id
            ));
        }
        
        if (!$service) {
            return null;
        }
        
        // Get service options
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $options_table WHERE service_id = %d ORDER BY display_order ASC",
            $service_id
        ));
        
        // Process options data (parse choice options for select/radio)
        foreach ($options as $key => $option) {
            if (in_array($option->type, ['select', 'radio']) && !empty($option->options)) {
                $options[$key]->choices = $this->parse_option_choices($option->options);
            }
        }
        
        // Attach options to service
        $service->options = $options;
        
        return $service;
    }
    
    /**
     * Get services for a user
     */
    public function get_user_services($user_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $services_table WHERE user_id = %d ORDER BY name ASC",
            $user_id
        ));
    }

    /**
     * Get a specific service (original method from Manager)
     */
    public function get_service($service_id, $user_id = null) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        if ($user_id) {
            // Get service only if it belongs to the user
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $services_table WHERE id = %d AND user_id = %d",
                $service_id, $user_id
            ));
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d",
            $service_id
        ));
    }

    /**
     * Check if a service has any options (original method from OptionsManager)
     */
    public function has_service_options($service_id) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $options_table WHERE service_id = %d",
            $service_id
        ));
        
        return $count > 0;
    }

    /**
     * Get service options (original method from OptionsManager)
     */
    public function get_service_options($service_id) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $options_table WHERE service_id = %d ORDER BY display_order ASC, id ASC",
            $service_id
        ));
    }

    /**
     * Get a specific service option (original method from OptionsManager)
     */
    public function get_service_option($option_id) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $options_table WHERE id = %d",
            $option_id
        ));
    }

    /**
     * Save complete service with options
     */
    public function save_service($data) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Sanitize base service data
            $service_data = array(
                'user_id' => absint($data['user_id']),
                'name' => sanitize_text_field($data['name']),
                'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
                'price' => floatval($data['price']),
                'duration' => absint($data['duration']),
                'icon' => isset($data['icon']) ? sanitize_text_field($data['icon']) : '',
                'category' => isset($data['category']) ? sanitize_text_field($data['category']) : '',
                'image_url' => isset($data['image_url']) ? esc_url_raw($data['image_url']) : '',
                'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
            );
            
            // Update or create service
            if (!empty($data['id'])) {
                $wpdb->update(
                    $services_table,
                    $service_data,
                    array('id' => absint($data['id'])),
                    array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s'),
                    array('%d')
                );
                $service_id = absint($data['id']);
            } else {
                $wpdb->insert($services_table, $service_data);
                $service_id = $wpdb->insert_id;
            }
            
            // Process options if included
            if (isset($data['options']) && is_array($data['options'])) {
                foreach ($data['options'] as $option) {
                    $option['service_id'] = $service_id;
                    $this->save_option($option);
                }
            }
            
            $wpdb->query('COMMIT');
            return $service_id;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Save a service option
     */
    public function save_option($data) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Sanitize option data
        $option_data = array(
            'service_id' => absint($data['service_id']),
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'type' => sanitize_text_field($data['type']),
            'is_required' => isset($data['is_required']) ? absint($data['is_required']) : 0,
            'default_value' => isset($data['default_value']) ? sanitize_text_field($data['default_value']) : '',
            'placeholder' => isset($data['placeholder']) ? sanitize_text_field($data['placeholder']) : '',
            'min_value' => isset($data['min_value']) && $data['min_value'] !== '' ? floatval($data['min_value']) : null,
            'max_value' => isset($data['max_value']) && $data['max_value'] !== '' ? floatval($data['max_value']) : null,
            'price_impact' => isset($data['price_impact']) ? floatval($data['price_impact']) : 0,
            'price_type' => isset($data['price_type']) ? sanitize_text_field($data['price_type']) : 'fixed',
            'options' => isset($data['options']) ? sanitize_textarea_field($data['options']) : '',
            'option_label' => isset($data['option_label']) ? sanitize_text_field($data['option_label']) : '',
            'step' => isset($data['step']) ? sanitize_text_field($data['step']) : '',
            'unit' => isset($data['unit']) ? sanitize_text_field($data['unit']) : '',
            'min_length' => isset($data['min_length']) && $data['min_length'] !== '' ? absint($data['min_length']) : null,
            'max_length' => isset($data['max_length']) && $data['max_length'] !== '' ? absint($data['max_length']) : null,
            'rows' => isset($data['rows']) && $data['rows'] !== '' ? absint($data['rows']) : null
        );
        
        // Update or create option
        if (!empty($data['id'])) {
            $wpdb->update(
                $options_table,
                $option_data,
                array('id' => absint($data['id'])),
                null,
                array('%d')
            );
            return absint($data['id']);
        } else {
            // Get highest order for this service
            $highest_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(display_order) FROM $options_table WHERE service_id = %d",
                $option_data['service_id']
            ));
            
            $option_data['display_order'] = ($highest_order !== null) ? intval($highest_order) + 1 : 0;
            
            $wpdb->insert($options_table, $option_data);
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a service and all its options
     */
    public function delete_service($service_id, $user_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Verify ownership
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d AND user_id = %d",
            $service_id, $user_id
        ));
        
        if (!$service) {
            return false;
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete all service options first
            $wpdb->delete($options_table, array('service_id' => $service_id));
            
            // Then delete the service
            $wpdb->delete($services_table, array('id' => $service_id, 'user_id' => $user_id));
            
            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Delete a service option
     */
    public function delete_service_option($option_id) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->delete($options_table, array('id' => $option_id));
    }
    
    /**
     * Update the order of service options
     */
    public function update_options_order($service_id, $order_data) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($order_data as $item) {
                if (empty($item['id']) || !isset($item['order'])) {
                    continue;
                }
                
                $wpdb->update(
                    $options_table,
                    array('display_order' => absint($item['order'])),
                    array('id' => absint($item['id']), 'service_id' => $service_id),
                    array('%d'),
                    array('%d', '%d')
                );
            }
            
            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Calculate total price for a service with selected options
     */
    public function calculate_total_price($service_id, $option_values = []) {
        $service = $this->get_service_with_options($service_id);
        
        if (!$service) {
            return 0;
        }
        
        $total = $service->price;
        
        // Add option price impacts
        foreach ($service->options as $option) {
            if (isset($option_values[$option->id])) {
                $value = $option_values[$option->id];
                
                switch ($option->type) {
                    case 'checkbox':
                        if ($value) {
                            $total += floatval($option->price_impact);
                        }
                        break;
                        
                    case 'select':
                    case 'radio':
                        $choices = $this->parse_option_choices($option->options);
                        foreach ($choices as $choice) {
                            if ($choice['value'] == $value) {
                                $total += floatval($choice['price'] ?: $option->price_impact);
                                break;
                            }
                        }
                        break;
                        
                    case 'number':
                    case 'quantity':
                        $value = floatval($value);
                        if ($option->price_type == 'fixed') {
                            $total += floatval($option->price_impact);
                        } elseif ($option->price_type == 'multiply') {
                            $total += $value * floatval($option->price_impact);
                        } elseif ($option->price_type == 'percentage') {
                            $total += ($service->price * $value * floatval($option->price_impact)) / 100;
                        }
                        break;
                }
            }
        }
        
        return $total;
    }
    
    /**
     * Parse option choices from string to array
     */
    private function parse_option_choices($options_string) {
        if (!$options_string) {
            return array();
        }
        
        $choices = array();
        $lines = explode("\n", $options_string);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode('|', $line);
            $value = trim($parts[0]);
            
            if (isset($parts[1])) {
                $label_price_parts = explode(':', trim($parts[1]));
                $label = trim($label_price_parts[0]);
                $price = isset($label_price_parts[1]) ? floatval(trim($label_price_parts[1])) : 0;
            } else {
                $label = $value;
                $price = 0;
            }
            
            $choices[] = array(
                'value' => $value,
                'label' => $label,
                'price' => $price
            );
        }
        
        return $choices;
    }
    
    // ========================= AJAX Handlers =========================

    /**
     * AJAX handler to save a service
     */
    public function ajax_save_service() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Prepare service data
        $service_data = array(
            'user_id' => $user_id,
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
            'price' => isset($_POST['price']) ? $_POST['price'] : 0,
            'duration' => isset($_POST['duration']) ? $_POST['duration'] : 60,
            'icon' => isset($_POST['icon']) ? $_POST['icon'] : '',
            'category' => isset($_POST['category']) ? $_POST['category'] : '',
            'image_url' => isset($_POST['image_url']) ? $_POST['image_url'] : '',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
        );
        
        // Add ID if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $service_data['id'] = absint($_POST['id']);
            
            // Check ownership if editing
            $existing_service = $this->get_service($service_data['id']);
            if (!$existing_service || $existing_service->user_id != $user_id) {
                wp_send_json_error(array('message' => __('You do not have permission to edit this service.', 'mobooking')));
            }
        }
        
        // Validate data
        if (empty($service_data['name'])) {
            wp_send_json_error(array('message' => __('Service name is required.', 'mobooking')));
        }
        
        if (empty($service_data['price']) || $service_data['price'] <= 0) {
            wp_send_json_error(array('message' => __('Service price must be greater than zero.', 'mobooking')));
        }
        
        if (empty($service_data['duration']) || $service_data['duration'] < 15) {
            wp_send_json_error(array('message' => __('Service duration must be at least 15 minutes.', 'mobooking')));
        }
        
        // Save service
        $service_id = $this->save_service($service_data);
        
        if (!$service_id) {
            wp_send_json_error(array('message' => __('Failed to save service.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'id' => $service_id,
            'message' => __('Service saved successfully.', 'mobooking')
        ));
    }

    /**
     * AJAX handler to delete a service
     */
    public function ajax_delete_service() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        $service_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Delete the service
        $result = $this->delete_service($service_id, $user_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete service.', 'mobooking')));
        }
        
        wp_send_json_success(array('message' => __('Service deleted successfully.', 'mobooking')));
    }

    /**
     * AJAX handler to get a service
     */
    public function ajax_get_service() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        $service_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Get service data
        $service = $this->get_service($service_id, $user_id);
        
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to view it.', 'mobooking')));
        }
        
        wp_send_json_success(array('service' => $service));
    }

    /**
     * AJAX handler to get services
     */
    public function ajax_get_services() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        $user_id = get_current_user_id();
        
        // Get services
        $services = $this->get_user_services($user_id);
        
        wp_send_json_success(array('services' => $services));
    }

    /**
     * AJAX handler to get services by ZIP code
     */
    public function ajax_get_services_by_zip() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-zip-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check ZIP code
        if (!isset($_POST['zip_code']) || empty($_POST['zip_code'])) {
            wp_send_json_error(array('message' => __('ZIP code is required.', 'mobooking')));
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        
        // Get businesses that serve this ZIP
        $geography_manager = new \MoBooking\Geography\Manager();
        $businesses = $geography_manager->get_businesses_by_zip($zip_code);
        
        if (empty($businesses)) {
            wp_send_json_error(array('message' => __('No service providers found for this ZIP code.', 'mobooking')));
        }
        
        // Get services for each business
        $services_by_business = array();
        
        foreach ($businesses as $business) {
            $user_id = $business->user_id;
            $services = $this->get_user_services($user_id);
            
            if (!empty($services)) {
                $services_by_business[] = array(
                    'business' => $business,
                    'services' => $services
                );
            }
        }
        
        wp_send_json_success(array(
            'services_by_business' => $services_by_business,
            'message' => sprintf(
                _n(
                    'Found %d service provider in your area!',
                    'Found %d service providers in your area!',
                    count($services_by_business),
                    'mobooking'
                ),
                count($services_by_business)
            )
        ));
    }

    /**
     * AJAX handler to save a service option
     */
    public function ajax_save_service_option() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check if we have required fields
        if (!isset($_POST['name']) || empty($_POST['name']) || 
            !isset($_POST['type']) || empty($_POST['type']) || 
            !isset($_POST['service_id']) || empty($_POST['service_id'])) {
            wp_send_json_error(array('message' => __('Missing required fields.', 'mobooking')));
        }
        
        $user_id = get_current_user_id();
        $service_id = absint($_POST['service_id']);
        
        // Verify service ownership
        $service = $this->get_service($service_id, $user_id);
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to modify it.', 'mobooking')));
        }
        
        // Prepare option data
        $option_data = $_POST;
        
        // If editing, verify option exists and belongs to the service
        if (!empty($_POST['id'])) {
            $option = $this->get_service_option(absint($_POST['id']));
            if (!$option || $option->service_id != $service_id) {
                wp_send_json_error(array('message' => __('Option not found or does not belong to this service.', 'mobooking')));
            }
        }
        
        // Save the option
        $option_id = $this->save_option($option_data);
        
        if (!$option_id) {
            wp_send_json_error(array('message' => __('Failed to save option.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'id' => $option_id,
            'message' => __('Option saved successfully.', 'mobooking')
        ));
    }

    /**
     * AJAX handler to get a service option
     */
    public function ajax_get_service_option() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check option ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No option specified.', 'mobooking')));
        }
        
        $option_id = absint($_POST['id']);
        
        // Get option data
        $option = $this->get_service_option($option_id);
        
        if (!$option) {
            wp_send_json_error(array('message' => __('Option not found.', 'mobooking')));
        }
        
        // Verify service ownership
        $user_id = get_current_user_id();
        $service = $this->get_service($option->service_id, $user_id);
        if (!$service) {
            wp_send_json_error(array('message' => __('You do not have permission to view this option.', 'mobooking')));
        }
        
        wp_send_json_success(array('option' => $option));
    }

    /**
     * AJAX handler to get service options
     */
    public function ajax_get_service_options() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        $service_id = absint($_POST['service_id']);
        $user_id = get_current_user_id();
        
        // Verify service ownership
        $service = $this->get_service($service_id, $user_id);
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to view it.', 'mobooking')));
        }
        
        // Get options
        $options = $this->get_service_options($service_id);
        
        wp_send_json_success(array('options' => $options));
    }

    /**
     * AJAX handler to delete a service option
     */
    public function ajax_delete_service_option() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check option ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No option specified.', 'mobooking')));
        }
        
        $option_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Get the option to verify ownership
        $option = $this->get_service_option($option_id);
        if (!$option) {
            wp_send_json_error(array('message' => __('Option not found.', 'mobooking')));
        }
        
        // Verify service ownership
        $service = $this->get_service($option->service_id, $user_id);
        if (!$service) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this option.', 'mobooking')));
        }
        
        // Delete the option
        $result = $this->delete_service_option($option_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete option.', 'mobooking')));
        }
        
        wp_send_json_success(array('message' => __('Option deleted successfully.', 'mobooking')));
    }

    /**
     * AJAX handler to update options order
     */
    public function ajax_update_options_order() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check required data
        if (empty($_POST['service_id']) || empty($_POST['order_data'])) {
            wp_send_json_error(array('message' => __('Missing required data.', 'mobooking')));
        }
        
        $service_id = absint($_POST['service_id']);
        $user_id = get_current_user_id();
        
        // Verify service ownership
        $service = $this->get_service($service_id, $user_id);
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to modify it.', 'mobooking')));
        }
        
        // Parse order data
        $order_data = json_decode(stripslashes($_POST['order_data']), true);
        if (!$order_data || !is_array($order_data)) {
            wp_send_json_error(array('message' => __('Invalid order data format.', 'mobooking')));
        }
        
        // Update options order
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update options order.', 'mobooking')));
        }
        
        wp_send_json_success(array('message' => __('Options order updated successfully.', 'mobooking')));
    }
}