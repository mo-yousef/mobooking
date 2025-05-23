<?php
namespace MoBooking\Services;

/**
 * Combined Services and Options Manager - Simplified Version
 */
class ServicesManager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers for services
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        
        // Register AJAX handlers for service options
        add_action('wp_ajax_mobooking_save_service_option', array($this, 'ajax_save_option'));
        add_action('wp_ajax_mobooking_delete_service_option', array($this, 'ajax_delete_option'));
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_options'));
        add_action('wp_ajax_mobooking_get_service_option', array($this, 'ajax_get_option'));
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
    }
    
    // ===========================================
    // SERVICE METHODS
    // ===========================================
    
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
        
        return empty($categories) ? array('residential', 'commercial', 'special') : $categories;
    }
    
    /**
     * Save a service
     */
    public function save_service($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
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
        
        if (!empty($data['id'])) {
            // Update existing service
            $result = $wpdb->update(
                $table_name,
                $service_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            return $result !== false ? absint($data['id']) : false;
        } else {
            // Create new service
            $result = $wpdb->insert(
                $table_name,
                $service_data,
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s')
            );
            
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete a service and its options
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
            array('id' => $service_id, 'user_id' => $user_id),
            array('%d', '%d')
        );
    }
    
    // ===========================================
    // SERVICE OPTIONS METHODS
    // ===========================================
    
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
     * Save an option - Simplified version
     */
    public function save_option($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
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
        
        if (!empty($data['id'])) {
            // Update existing option
            $result = $wpdb->update(
                $table_name,
                $option_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d'),
                array('%d')
            );
            
            return $result !== false ? absint($data['id']) : false;
        } else {
            // Check for duplicate names (simple check)
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE service_id = %d AND name = %s",
                $option_data['service_id'], $option_data['name']
            ));
            
            if ($existing) {
                return false; // Duplicate name
            }
            
            // Create new option
            $result = $wpdb->insert(
                $table_name,
                $option_data,
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
            );
            
            return $result !== false ? $wpdb->insert_id : false;
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
    
    // ===========================================
    // AJAX HANDLERS - SERVICES
    // ===========================================
    
    /**
     * AJAX handler to save service
     */
    public function ajax_save_service() {
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
        }
        
        $user_id = get_current_user_id();
        
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
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
        }
        
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
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $services = $this->get_user_services($user_id);
        
        wp_send_json_success(array(
            'services' => $services
        ));
    }
    
    // ===========================================
    // AJAX HANDLERS - OPTIONS
    // ===========================================
    
    /**
     * AJAX handler to save option
     */
    public function ajax_save_option() {
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(__('Service ID is required.', 'mobooking'));
        }
        
        // Verify service ownership
        $service = $this->get_service($service_id, $user_id);
        if (!$service) {
            wp_send_json_error(__('Service not found or access denied.', 'mobooking'));
        }
        
        $option_data = array(
            'service_id' => $service_id,
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
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
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $option_data['id'] = absint($_POST['id']);
        }
        
        // Validate data
        if (empty($option_data['name'])) {
            wp_send_json_error(__('Option name is required.', 'mobooking'));
        }
        
        // Validate choices for select/radio types
        if (in_array($option_data['type'], array('select', 'radio'))) {
            if (empty($option_data['options'])) {
                wp_send_json_error(__('At least one choice is required for this option type.', 'mobooking'));
            }
        }
        
        $option_id = $this->save_option($option_data);
        
        if (!$option_id) {
            wp_send_json_error(__('Failed to save option. This may be a duplicate name.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'id' => $option_id,
            'message' => __('Option saved successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to delete option
     */
    public function ajax_delete_option() {
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
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
        
        $service = $this->get_service($option->service_id, get_current_user_id());
        if (!$service) {
            wp_send_json_error(__('Access denied.', 'mobooking'));
        }
        
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
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        if (!$service_id) {
            wp_send_json_error(__('Service ID is required.', 'mobooking'));
        }
        
        // Verify service ownership
        $service = $this->get_service($service_id, get_current_user_id());
        if (!$service) {
            wp_send_json_error(__('Service not found or access denied.', 'mobooking'));
        }
        
        $options = $this->get_service_options($service_id);
        
        wp_send_json_success(array(
            'options' => $options
        ));
    }
    
    /**
     * AJAX handler to get single option
     */
    public function ajax_get_option() {
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
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
        $service = $this->get_service($option->service_id, get_current_user_id());
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
        if (!$this->verify_ajax_request('mobooking-service-nonce')) {
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        $order_data = isset($_POST['order_data']) ? json_decode(stripslashes($_POST['order_data']), true) : array();
        
        if (!$service_id || empty($order_data)) {
            wp_send_json_error(__('Invalid data provided.', 'mobooking'));
        }
        
        // Verify service ownership
        $service = $this->get_service($service_id, get_current_user_id());
        if (!$service) {
            wp_send_json_error(__('Service not found or access denied.', 'mobooking'));
        }
        
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result) {
            wp_send_json_error(__('Failed to update options order.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Options order updated successfully.', 'mobooking')
        ));
    }
    
    // ===========================================
    // HELPER METHODS
    // ===========================================
    
    /**
     * Verify AJAX request
     */
    private function verify_ajax_request($nonce_action) {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $nonce_action)) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return false;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return false;
        }
        
        return true;
    }
}