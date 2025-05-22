<?php
namespace MoBooking\Services;

/**
 * Service Manager class - Only handles services, not options
 */
class ServiceManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers ONLY for services
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        
        // DO NOT register option handlers here - they're handled by ServiceOptionsManager
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
        
        if (empty($categories)) {
            return array('residential', 'commercial', 'special');
        }
        
        return $categories;
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
}