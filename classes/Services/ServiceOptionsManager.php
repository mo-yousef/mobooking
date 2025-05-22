<?php
namespace MoBooking\Services;

/**
 * Service Options Manager class
 * Handles service options in a separate table
 */
class ServiceOptionsManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers for options
        add_action('wp_ajax_mobooking_save_service_option', array($this, 'ajax_save_service_option'));
        add_action('wp_ajax_mobooking_get_service_option', array($this, 'ajax_get_service_option'));
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_service_options'));
        add_action('wp_ajax_mobooking_delete_service_option', array($this, 'ajax_delete_service_option'));
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
    }
    
    /**
     * Get all options for a service
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
            'rows' => isset($data['rows']) && $data['rows'] !== '' ? absint($data['rows']) : null,
            'display_order' => isset($data['display_order']) ? absint($data['display_order']) : 0
        );
        
        // Update or create option
        if (!empty($data['id'])) {
            // Update existing option
            $wpdb->update(
                $table_name,
                $option_data,
                array('id' => absint($data['id'])),
                null,
                array('%d')
            );
            return absint($data['id']);
        } else {
            // Get highest order for this service if no order specified
            if (!isset($data['display_order'])) {
                $highest_order = $wpdb->get_var($wpdb->prepare(
                    "SELECT MAX(display_order) FROM $table_name WHERE service_id = %d",
                    $option_data['service_id']
                ));
                $option_data['display_order'] = ($highest_order !== null) ? intval($highest_order) + 1 : 0;
            }
            
            // Create new option
            $wpdb->insert($table_name, $option_data);
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a service option
     */
    public function delete_service_option($option_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->delete($table_name, array('id' => $option_id), array('%d'));
    }
    
    /**
     * Delete all options for a service
     */
    public function delete_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->delete($table_name, array('service_id' => $service_id), array('%d'));
    }
    
    /**
     * Update the order of service options
     */
    public function update_options_order($service_id, $order_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($order_data as $item) {
                if (empty($item['id']) || !isset($item['order'])) {
                    continue;
                }
                
                $wpdb->update(
                    $table_name,
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
            error_log('Error updating options order: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count options for a service
     */
    public function count_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE service_id = %d",
            $service_id
        ));
    }
    
    /**
     * Parse option choices from string to array
     */
    public function parse_option_choices($options_string) {
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
     * Format choices array as string for storage
     */
    public function format_choices_as_string($choices) {
        if (empty($choices)) {
            return '';
        }
        
        $lines = array();
        
        foreach ($choices as $choice) {
            if (empty($choice['value'])) {
                continue;
            }
            
            $value = sanitize_text_field($choice['value']);
            $label = isset($choice['label']) ? sanitize_text_field($choice['label']) : $value;
            $price = isset($choice['price']) ? floatval($choice['price']) : 0;
            
            if ($price > 0) {
                $lines[] = "$value|$label:$price";
            } else {
                $lines[] = "$value|$label";
            }
        }
        
        return implode("\n", $lines);
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
        
        // Verify service ownership
        $service_id = absint($_POST['service_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $services_table WHERE id = %d AND user_id = %d",
            $service_id, $user_id
        ));
        
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to edit it.', 'mobooking')));
        }
        
        // Prepare option data
        $option_data = array(
            'service_id' => $service_id,
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
        $option_id = $this->save_service_option($option_data);
        
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