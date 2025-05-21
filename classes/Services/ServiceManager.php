<?php
namespace MoBooking\Services;

/**
 * Unified Service Manager
 * Handles services and their options in a single table
 */
class ServiceManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
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
    }
    
    /**
     * Get service with all options
     */
    public function get_service_with_options($service_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Get the service
        if ($user_id) {
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND entity_type = 'service'",
                $service_id, $user_id
            ));
        } else {
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND entity_type = 'service'",
                $service_id
            ));
        }
        
        if (!$service) {
            return null;
        }
        
        // Get service options
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE parent_id = %d AND entity_type = 'option' ORDER BY display_order ASC",
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
        
        // Set the has_options flag
        $service->has_options = !empty($options);
        
        return $service;
    }
    
    /**
     * Get services for a user
     */
    public function get_user_services($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $services = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, 
                (SELECT COUNT(*) FROM $table_name o WHERE o.parent_id = s.id AND o.entity_type = 'option') as options_count 
            FROM $table_name s 
            WHERE s.user_id = %d AND s.entity_type = 'service' 
            ORDER BY s.name ASC",
            $user_id
        ));
        
        // Add has_options flag to each service
        foreach ($services as $service) {
            $service->has_options = (int)$service->options_count > 0;
        }
        
        return $services;
    }

    /**
     * Get a specific service
     */
    public function get_service($service_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        if ($user_id) {
            // Get service only if it belongs to the user
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND entity_type = 'service'",
                $service_id, $user_id
            ));
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND entity_type = 'service'",
            $service_id
        ));
    }

    /**
     * Check if a service has any options
     */
    public function has_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE parent_id = %d AND entity_type = 'option'",
            $service_id
        ));
        
        return $count > 0;
    }

    /**
     * Get service options
     */
    public function get_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE parent_id = %d AND entity_type = 'option' ORDER BY display_order ASC, id ASC",
            $service_id
        ));
    }

    /**
     * Get a specific service option
     */
    public function get_service_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND entity_type = 'option'",
            $option_id
        ));
    }

    /**
     * Save complete service with options
     */
    public function save_service($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Sanitize base service data
            $service_data = array(
                'user_id' => absint($data['user_id']),
                'entity_type' => 'service',
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
                    $table_name,
                    $service_data,
                    array('id' => absint($data['id'])),
                    array('%d', '%s', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s'),
                    array('%d')
                );
                $service_id = absint($data['id']);
            } else {
                $wpdb->insert($table_name, $service_data);
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
            error_log('Error saving service: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save a service option
     */
    public function save_option($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Get user_id from parent service
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE id = %d AND entity_type = 'service'",
            $data['service_id']
        ));
        
        if (!$user_id) {
            return false;
        }
        
        // Sanitize option data
        $option_data = array(
            'user_id' => $user_id,
            'parent_id' => absint($data['service_id']),
            'entity_type' => 'option',
            'name' => sanitize_text_field($data['name']),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'price' => 0, // Options don't have a base price
            'duration' => 0, // Options don't have a duration
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
                $table_name,
                $option_data,
                array('id' => absint($data['id'])),
                null,
                array('%d')
            );
            return absint($data['id']);
        } else {
            // Get highest order for this service
            $highest_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(display_order) FROM $table_name WHERE parent_id = %d AND entity_type = 'option'",
                $option_data['parent_id']
            ));
            
            $option_data['display_order'] = ($highest_order !== null) ? intval($highest_order) + 1 : 0;
            
            $wpdb->insert($table_name, $option_data);
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a service and all its options
     */
    public function delete_service($service_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Verify ownership
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d AND entity_type = 'service'",
            $service_id, $user_id
        ));
        
        if (!$service) {
            return false;
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete all service options first
            $wpdb->delete($table_name, array('parent_id' => $service_id, 'entity_type' => 'option'));
            
            // Then delete the service
            $wpdb->delete($table_name, array('id' => $service_id, 'user_id' => $user_id, 'entity_type' => 'service'));
            
            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Error deleting service: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a service option
     */
    public function delete_service_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->delete($table_name, array('id' => $option_id, 'entity_type' => 'option'));
    }
    
    /**
     * Update the order of service options
     */
    public function update_options_order($service_id, $order_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($order_data as $item) {
                if (empty($item['id']) || !isset($item['order'])) {
                    continue;
                }
                
                $wpdb->update(
                    $table_name,
                    array('display_order' => absint($item['order'])),
                    array('id' => absint($item['id']), 'parent_id' => $service_id, 'entity_type' => 'option'),
                    array('%d'),
                    array('%d', '%d', '%s')
                );
            }
            
            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Error updating options order: ' . $e->getMessage());
            return false;
        }
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
        
        // Prepare service data
        $service_data = array(
            'user_id' => isset($_POST['user_id']) ? absint($_POST['user_id']) : get_current_user_id(),
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
        }
        
        // Validate required fields
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
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        $service_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Delete service
        $result = $this->delete_service($service_id, $user_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete service.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'message' => __('Service deleted successfully.', 'mobooking')
        ));
    }

    /**
     * AJAX handler to get a service
     */
    public function ajax_get_service() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        $service_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Get service with options
        $service = $this->get_service_with_options($service_id, $user_id);
        
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to view it.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'service' => $service
        ));
    }

    /**
     * AJAX handler to get services
     */
    public function ajax_get_services() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        $user_id = get_current_user_id();
        
        // Get services
        $services = $this->get_user_services($user_id);
        
        wp_send_json_success(array(
            'services' => $services
        ));
    }
    
    /**
     * AJAX handler to get a service option
     */
    public function ajax_get_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check option ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No option specified.', 'mobooking')));
        }
        
        $option_id = absint($_POST['id']);
        
        // Get option
        $option = $this->get_service_option($option_id);
        
        if (!$option) {
            wp_send_json_error(array('message' => __('Option not found.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'option' => $option
        ));
    }

    /**
     * AJAX handler to get service options
     */
    public function ajax_get_service_options() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        $service_id = absint($_POST['service_id']);
        
        // Get options
        $options = $this->get_service_options($service_id);
        
        wp_send_json_success(array(
            'options' => $options
        ));
    }

    /**
     * AJAX handler to save a service option
     */
    public function ajax_save_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID
        if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
            wp_send_json_error(array('message' => __('No service specified.', 'mobooking')));
        }
        
        // Prepare option data
        $option_data = array(
            'service_id' => absint($_POST['service_id']),
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
            'type' => isset($_POST['type']) ? $_POST['type'] : 'checkbox',
            'is_required' => isset($_POST['is_required']) ? $_POST['is_required'] : 0,
            'default_value' => isset($_POST['default_value']) ? $_POST['default_value'] : '',
            'placeholder' => isset($_POST['placeholder']) ? $_POST['placeholder'] : '',
            'min_value' => isset($_POST['min_value']) ? $_POST['min_value'] : null,
            'max_value' => isset($_POST['max_value']) ? $_POST['max_value'] : null,
            'price_impact' => isset($_POST['price_impact']) ? $_POST['price_impact'] : 0,
            'price_type' => isset($_POST['price_type']) ? $_POST['price_type'] : 'fixed',
            'options' => isset($_POST['options']) ? $_POST['options'] : '',
            'option_label' => isset($_POST['option_label']) ? $_POST['option_label'] : '',
            'step' => isset($_POST['step']) ? $_POST['step'] : '',
            'unit' => isset($_POST['unit']) ? $_POST['unit'] : '',
            'min_length' => isset($_POST['min_length']) ? $_POST['min_length'] : null,
            'max_length' => isset($_POST['max_length']) ? $_POST['max_length'] : null,
            'rows' => isset($_POST['rows']) ? $_POST['rows'] : null
        );
        
        // Add ID if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $option_data['id'] = absint($_POST['id']);
        }
        
        // Validate required fields
        if (empty($option_data['name'])) {
            wp_send_json_error(array('message' => __('Option name is required.', 'mobooking')));
        }
        
        // Special validation for select/radio types
        if (in_array($option_data['type'], array('select', 'radio')) && empty($option_data['options'])) {
            wp_send_json_error(array('message' => __('At least one choice is required for this option type.', 'mobooking')));
        }
        
        // Save option
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
     * AJAX handler to delete a service option
     */
    public function ajax_delete_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check option ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('No option specified.', 'mobooking')));
        }
        
        $option_id = absint($_POST['id']);
        
        // Delete option
        $result = $this->delete_service_option($option_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete option.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'message' => __('Option deleted successfully.', 'mobooking')
        ));
    }

    /**
     * AJAX handler to update options order
     */
    public function ajax_update_options_order() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID and order data
        if (!isset($_POST['service_id']) || empty($_POST['service_id']) || !isset($_POST['order_data']) || empty($_POST['order_data'])) {
            wp_send_json_error(array('message' => __('Missing required data.', 'mobooking')));
        }
        
        $service_id = absint($_POST['service_id']);
        $order_data = json_decode(stripslashes($_POST['order_data']), true);
        
        if (!is_array($order_data)) {
            wp_send_json_error(array('message' => __('Invalid order data.', 'mobooking')));
        }
        
        // Update options order
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update options order.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'message' => __('Options order updated successfully.', 'mobooking')
        ));
    }
}