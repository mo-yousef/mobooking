<?php
namespace MoBooking\Services;

/**
 * Service Manager class - Updated for separate tables
 * Handles services only (options are handled by ServiceOptionsManager)
 */
class ServiceManager {
    /**
     * @var ServiceOptionsManager
     */
    private $options_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the options manager
        $this->options_manager = new ServiceOptionsManager();
        
        // Register AJAX handlers for services
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_nopriv_mobooking_get_services_by_zip', array($this, 'ajax_get_services_by_zip'));
        
        // Unified save handler for service with options
        add_action('wp_ajax_mobooking_save_service_with_options', array($this, 'ajax_save_service_with_options'));
    }
    
    /**
     * Get services for a user
     */
    public function get_user_services($user_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        $services = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, 
                (SELECT COUNT(*) FROM $options_table o WHERE o.service_id = s.id) as options_count 
            FROM $services_table s 
            WHERE s.user_id = %d 
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
     * Get service with all options
     */
    public function get_service_with_options($service_id, $user_id = null) {
        // Get the service
        $service = $this->get_service($service_id, $user_id);
        
        if (!$service) {
            return null;
        }
        
        // Get service options using the options manager
        $options = $this->options_manager->get_service_options($service_id);
        
        // Process options data (parse choice options for select/radio)
        foreach ($options as $key => $option) {
            if (in_array($option->type, ['select', 'radio']) && !empty($option->options)) {
                $options[$key]->choices = $this->options_manager->parse_option_choices($option->options);
            }
        }
        
        // Attach options to service
        $service->options = $options;
        $service->has_options = !empty($options);
        
        return $service;
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
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $service_id, $user_id
            ));
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $service_id
        ));
    }
    
    /**
     * Save a service
     */
    public function save_service($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Sanitize service data
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
        
        // Update existing service or create new one
        if (!empty($data['id'])) {
            // Verify ownership
            $existing_service = $this->get_service(absint($data['id']));
            if (!$existing_service || $existing_service->user_id != $data['user_id']) {
                return false;
            }
            
            // Update service
            $result = $wpdb->update(
                $table_name,
                $service_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            return $result !== false ? absint($data['id']) : false;
        } else {
            // Insert new service
            $result = $wpdb->insert(
                $table_name,
                $service_data,
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s')
            );
            
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Save service with options in one transaction
     */
    public function save_service_with_options($service_data, $options_data = array()) {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Save the service first
            $service_id = $this->save_service($service_data);
            
            if (!$service_id) {
                throw new \Exception('Failed to save service');
            }
            
            // If this is a new service, update the service_data with the new ID
            if (empty($service_data['id'])) {
                $service_data['id'] = $service_id;
            }
            
            // Save options if provided
            if (!empty($options_data)) {
                // Delete existing options for this service
                $this->options_manager->delete_service_options($service_id);
                
                // Save new options
                foreach ($options_data as $index => $option) {
                    $option['service_id'] = $service_id;
                    $option['display_order'] = $index;
                    
                    // Process choices if this is a select/radio option
                    if (in_array($option['type'], array('select', 'radio')) && 
                        isset($option['choices']) && 
                        is_array($option['choices'])) {
                        
                        $option['options'] = $this->options_manager->format_choices_as_string($option['choices']);
                        unset($option['choices']);
                    }
                    
                    $option_id = $this->options_manager->save_service_option($option);
                    
                    if (!$option_id) {
                        throw new \Exception('Failed to save option: ' . $option['name']);
                    }
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            return $service_id;
            
        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('Failed to save service with options: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a service and all its options
     */
    public function delete_service($service_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Verify ownership
        $service = $this->get_service($service_id, $user_id);
        if (!$service) {
            return false;
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Delete all service options first (this will happen automatically due to foreign key constraint)
            $this->options_manager->delete_service_options($service_id);
            
            // Then delete the service
            $result = $wpdb->delete($table_name, array('id' => $service_id, 'user_id' => $user_id), array('%d', '%d'));
            
            if ($result === false) {
                throw new \Exception('Failed to delete service');
            }
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Error deleting service: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a service has any options
     */
    public function has_service_options($service_id) {
        return $this->options_manager->count_service_options($service_id) > 0;
    }
    
    /**
     * Get service options (delegates to options manager)
     */
    public function get_service_options($service_id) {
        return $this->options_manager->get_service_options($service_id);
    }
    
    /**
     * AJAX handler to save a service
     */
    public function ajax_save_service() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
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
     * AJAX handler to save service with options
     */
    public function ajax_save_service_with_options() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
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
        }
        
        // Get options data
        $options_data = isset($_POST['options']) && is_array($_POST['options']) ? $_POST['options'] : array();
        
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
        
        // Save service with options
        $service_id = $this->save_service_with_options($service_data, $options_data);
        
        if (!$service_id) {
            wp_send_json_error(array('message' => __('Failed to save service.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'id' => $service_id,
            'message' => __('Service and options saved successfully.', 'mobooking')
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
     * AJAX handler to get services by ZIP code
     */
    public function ajax_get_services_by_zip() {
        // Implementation depends on your specific requirements
        wp_send_json_error(array('message' => 'Not implemented'));
    }
}