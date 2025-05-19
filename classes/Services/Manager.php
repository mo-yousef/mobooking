<?php
namespace MoBooking\Services;

/**
 * Services Manager class
 * Handles services and their options
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register basic AJAX handlers
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
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
     * Check if a service has any options
     */
    public function has_service_options($service_id) {
        // Basic implementation
        return false;
    }

    /**
     * Placeholder for AJAX save service handler
     */
    public function ajax_save_service() {
        wp_send_json_error(array('message' => 'Not implemented yet'));
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
        'options' => isset($_POST['choices']) ? $_POST['choices'] : '',
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
    
    // Validate data
    if (empty($option_data['name'])) {
        wp_send_json_error(array('message' => __('Option name is required.', 'mobooking')));
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