<?php
namespace MoBooking\Services;

/**
 * Service Manager class
 */
class ServiceManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers for services
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        
        // Register AJAX handlers for service options
        add_action('wp_ajax_mobooking_save_service_option', array($this, 'ajax_save_service_option'));
        add_action('wp_ajax_mobooking_delete_service_option', array($this, 'ajax_delete_service_option'));
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_service_options'));
        add_action('wp_ajax_mobooking_get_service_option', array($this, 'ajax_get_service_option'));
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
    }
    
    /**
     * Get services for a user
     */
    public function get_user_services($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY name ASC",
            $user_id
        ));
    }
    
    /**
     * Get a specific service
     */
    public function get_service($service_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        if ($user_id) {
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
     * Get user categories
     */
    public function get_user_categories($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $categories = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT category FROM $table_name WHERE user_id = %d AND category IS NOT NULL AND category != '' ORDER BY category ASC",
            $user_id
        ));
        
        // Add default categories if none exist
        if (empty($categories)) {
            return array('residential', 'commercial', 'special');
        }
        
        return $categories;
    }
    
    /**
     * Count services for a user
     */
    public function count_user_services($user_id, $status = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $sql = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($status)) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        
        return $wpdb->get_var($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get services with options count
     */
    public function get_services_with_options_count($user_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, 
                    COALESCE(o.options_count, 0) as options_count
             FROM $services_table s
             LEFT JOIN (
                 SELECT service_id, COUNT(*) as options_count 
                 FROM $options_table 
                 GROUP BY service_id
             ) o ON s.id = o.service_id
             WHERE s.user_id = %d
             ORDER BY s.name ASC",
            $user_id
        ));
    }
    
    /**
     * Save a service
     */
    public function save_service($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Sanitize data
        $service_data = array(
            'user_id' => absint($data['user_id']),
            'name' => sanitize_text_field($data['name']),
            'description' => wp_kses_post($data['description']),
            'price' => floatval($data['price']),
            'duration' => absint($data['duration']),
            'icon' => sanitize_text_field($data['icon']),
            'image_url' => esc_url_raw($data['image_url']),
            'category' => sanitize_text_field($data['category']),
            'status' => in_array($data['status'], array('active', 'inactive')) ? $data['status'] : 'active'
        );
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing service
            $wpdb->update(
                $table_name,
                $service_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            return absint($data['id']);
        } else {
            // Create new service
            $wpdb->insert(
                $table_name,
                $service_data,
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s')
            );
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a service
     */
    public function delete_service($service_id, $user_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // First delete all options for this service
        $wpdb->delete(
            $options_table,
            array('service_id' => $service_id),
            array('%d')
        );
        
        // Then delete the service
        return $wpdb->delete(
            $services_table,
            array(
                'id' => $service_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
    }
    
    /**
     * Get service options
     */
    public function get_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_id = %d ORDER BY display_order ASC",
            $service_id
        ));
    }
    
    /**
     * Get a specific service option
     */
    public function get_service_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $option_id
        ));
    }
    
    /**
     * Save a service option
     */
    public function save_service_option($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        // Sanitize data
        $option_data = array(
            'service_id' => absint($data['service_id']),
            'name' => sanitize_text_field($data['name']),
            'description' => wp_kses_post($data['description']),
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
            'step' => isset($data['step']) ? sanitize_text_field($data['step']) : '',
            'unit' => isset($data['unit']) ? sanitize_text_field($data['unit']) : '',
            'min_length' => isset($data['min_length']) && $data['min_length'] !== '' ? absint($data['min_length']) : null,
            'max_length' => isset($data['max_length']) && $data['max_length'] !== '' ? absint($data['max_length']) : null,
            'rows' => isset($data['rows']) ? absint($data['rows']) : null,
            'display_order' => isset($data['display_order']) ? absint($data['display_order']) : 0
        );
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing option
            $wpdb->update(
                $table_name,
                $option_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d'),
                array('%d')
            );
            
            return absint($data['id']);
        } else {
            // Get next display order
            $max_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(display_order) FROM $table_name WHERE service_id = %d",
                $option_data['service_id']
            ));
            $option_data['display_order'] = ($max_order !== null) ? $max_order + 1 : 0;
            
            // Create new option
            $wpdb->insert(
                $table_name,
                $option_data,
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
            );
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a service option
     */
    public function delete_service_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $option_id),
            array('%d')
        );
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
                array(
                    'id' => absint($item['id']),
                    'service_id' => absint($service_id)
                ),
                array('%d'),
                array('%d', '%d')
            );
        }
        
        return true;
    }
    
    /**
     * AJAX handler to save service
     */
    public function ajax_save_service() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Prepare data
        $service_data = array(
            'user_id' => $user_id,
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
            'price' => isset($_POST['price']) ? $_POST['price'] : 0,
            'duration' => isset($_POST['duration']) ? $_POST['duration'] : 60,
            'icon' => isset($_POST['icon']) ? $_POST['icon'] : '',
            'image_url' => isset($_POST['image_url']) ? $_POST['image_url'] : '',
            'category' => isset($_POST['category']) ? $_POST['category'] : '',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'active'
        );
        
        // Add ID if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $service_data['id'] = $_POST['id'];
            
            // Verify ownership
            $service = $this->get_service($service_data['id']);
            if (!$service || $service->user_id != $user_id) {
                wp_send_json_error(__('You do not have permission to edit this service.', 'mobooking'));
            }
        }
        
        // Validate data
        if (empty($service_data['name'])) {
            wp_send_json_error(__('Service name is required.', 'mobooking'));
        }
        
        if ($service_data['price'] <= 0) {
            wp_send_json_error(__('Price must be greater than zero.', 'mobooking'));
        }
        
        if ($service_data['duration'] < 15) {
            wp_send_json_error(__('Duration must be at least 15 minutes.', 'mobooking'));
        }
        
        // Save service
        $service_id = $this->save_service($service_data);
        
        if (!$service_id) {
            wp_send_json_error(__('Failed to save service.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'id' => $service_id,
            'message' => __('Service saved successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to delete service
     */
    public function ajax_delete_service() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Check service ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(__('No service specified.', 'mobooking'));
        }
        
        $service_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Verify ownership
        $service = $this->get_service($service_id);
        if (!$service || $service->user_id != $user_id) {
            wp_send_json_error(__('You do not have permission to delete this service.', 'mobooking'));
        }
        
        // Delete service
        $result = $this->delete_service($service_id, $user_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to delete service.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Service deleted successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to get services
     */
    public function ajax_get_services() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $services = $this->get_user_services($user_id);
        
        wp_send_json_success(array(
            'services' => $services
        ));
    }
    
    /**
     * AJAX handler to save service option
     */
    public function ajax_save_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        
        // Prepare data
        $option_data = array(
            'service_id' => isset($_POST['service_id']) ? absint($_POST['service_id']) : 0,
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
            'type' => isset($_POST['type']) ? $_POST['type'] : 'checkbox',
            'is_required' => isset($_POST['is_required']) ? $_POST['is_required'] : 0,
            'default_value' => isset($_POST['default_value']) ? $_POST['default_value'] : '',
            'placeholder' => isset($_POST['placeholder']) ? $_POST['placeholder'] : '',
            'min_value' => isset($_POST['min_value']) ? $_POST['min_value'] : '',
            'max_value' => isset($_POST['max_value']) ? $_POST['max_value'] : '',
            'price_impact' => isset($_POST['price_impact']) ? $_POST['price_impact'] : 0,
            'price_type' => isset($_POST['price_type']) ? $_POST['price_type'] : 'fixed',
            'options' => isset($_POST['options']) ? $_POST['options'] : '',
            'option_label' => isset($_POST['option_label']) ? $_POST['option_label'] : '',
            'step' => isset($_POST['step']) ? $_POST['step'] : '',
            'unit' => isset($_POST['unit']) ? $_POST['unit'] : '',
            'min_length' => isset($_POST['min_length']) ? $_POST['min_length'] : '',
            'max_length' => isset($_POST['max_length']) ? $_POST['max_length'] : '',
            'rows' => isset($_POST['rows']) ? $_POST['rows'] : ''
        );
        
        // Add ID if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $option_data['id'] = $_POST['id'];
        }
        
        // Validate service ownership
        if ($option_data['service_id']) {
            $service = $this->get_service($option_data['service_id']);
            if (!$service || $service->user_id != $user_id) {
                wp_send_json_error(__('You do not have permission to modify this service.', 'mobooking'));
            }
        }
        
        // Validate data
        if (empty($option_data['name'])) {
            wp_send_json_error(__('Option name is required.', 'mobooking'));
        }
        
        if (empty($option_data['service_id'])) {
            wp_send_json_error(__('Service ID is required.', 'mobooking'));
        }
        
        // Save option
        $option_id = $this->save_service_option($option_data);
        
        if (!$option_id) {
            wp_send_json_error(__('Failed to save option.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'id' => $option_id,
            'message' => __('Option saved successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to delete service option
     */
    public function ajax_delete_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Check option ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(__('No option specified.', 'mobooking'));
        }
        
        $option_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Get option and verify service ownership
        $option = $this->get_service_option($option_id);
        if (!$option) {
            wp_send_json_error(__('Option not found.', 'mobooking'));
        }
        
        $service = $this->get_service($option->service_id);
        if (!$service || $service->user_id != $user_id) {
            wp_send_json_error(__('You do not have permission to delete this option.', 'mobooking'));
        }
        
        // Delete option
        $result = $this->delete_service_option($option_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to delete option.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Option deleted successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to get service options
     */
    public function ajax_get_service_options() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
            wp_send_json_error(__('Service ID is required.', 'mobooking'));
        }
        
        $service_id = absint($_POST['service_id']);
        $user_id = get_current_user_id();
        
        // Verify service ownership
        $service = $this->get_service($service_id);
        if (!$service || $service->user_id != $user_id) {
            wp_send_json_error(__('You do not have permission to view these options.', 'mobooking'));
        }
        
        $options = $this->get_service_options($service_id);
        
        wp_send_json_success(array(
            'options' => $options
        ));
    }
    
    /**
     * AJAX handler to get single service option
     */
    public function ajax_get_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(__('Option ID is required.', 'mobooking'));
        }
        
        $option_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        $option = $this->get_service_option($option_id);
        if (!$option) {
            wp_send_json_error(__('Option not found.', 'mobooking'));
        }
        
        // Verify service ownership
        $service = $this->get_service($option->service_id);
        if (!$service || $service->user_id != $user_id) {
            wp_send_json_error(__('You do not have permission to view this option.', 'mobooking'));
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
        
        if (!isset($_POST['service_id']) || !isset($_POST['order_data'])) {
            wp_send_json_error(__('Missing required data.', 'mobooking'));
        }
        
        $service_id = absint($_POST['service_id']);
        $order_data = json_decode(stripslashes($_POST['order_data']), true);
        $user_id = get_current_user_id();
        
        // Verify service ownership
        $service = $this->get_service($service_id);
        if (!$service || $service->user_id != $user_id) {
            wp_send_json_error(__('You do not have permission to modify this service.', 'mobooking'));
        }
        
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result) {
            wp_send_json_error(__('Failed to update options order.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Options order updated successfully.', 'mobooking')
        ));
    }
}