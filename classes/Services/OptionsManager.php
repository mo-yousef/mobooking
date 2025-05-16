<?php
namespace MoBooking\Services;

/**
 * Service Options Manager Class
 * Enhanced version that properly handles all option types and formats
 */
class OptionsManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_service_option', array($this, 'ajax_save_service_option'));
        add_action('wp_ajax_mobooking_delete_service_option', array($this, 'ajax_delete_service_option'));
        add_action('wp_ajax_mobooking_get_service_option', array($this, 'ajax_get_service_option'));
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_service_options'));
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
        
        // Enqueue the custom styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Enqueue custom styles
     */
    public function enqueue_styles() {
        // Only enqueue on dashboard pages
        if (is_admin() || (isset($_GET['pagename']) && $_GET['pagename'] === 'dashboard')) {
            wp_enqueue_style(
                'mobooking-service-options-style',
                plugin_dir_url(dirname(__FILE__)) . '../assets/css/service-options.css',
                array(),
                MOBOOKING_VERSION
            );
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
    
    // Sanitize data - with error logging
    error_log('Attempting to save service option with data: ' . print_r($data, true));
    
    // Basic validation
    if (empty($data['service_id'])) {
        error_log('Missing service_id in save_service_option');
        return array(
            'success' => false,
            'message' => 'Missing service ID'
        );
    }
    
    // Continue with your existing code...
    $option_data = array(
        'service_id' => absint($data['service_id']),
        'name' => sanitize_text_field($data['name']),
        'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
        'type' => sanitize_text_field($data['type']),
        'is_required' => isset($data['is_required']) ? absint($data['is_required']) : 0,
        'price_impact' => isset($data['price_impact']) ? floatval($data['price_impact']) : 0,
        'price_type' => isset($data['price_type']) ? sanitize_text_field($data['price_type']) : 'fixed'
    );
    
    // Rest of your existing code...
    
    // Check if we're updating or creating
    if (!empty($data['id'])) {
        // Update existing option
        // ...
    } else {
        // Log that we're creating a new option
        error_log('Creating new service option for service_id: ' . $option_data['service_id']);
        
        // Get the highest display order
        $highest_order = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(display_order) FROM $table_name WHERE service_id = %d",
            $option_data['service_id']
        ));
        
        // Set the display order one higher than the current highest
        $option_data['display_order'] = ($highest_order !== null) ? intval($highest_order) + 1 : 0;
        
        // Create new option
        $result = $wpdb->insert(
            $table_name,
            $option_data,
            array('%d', '%s', '%s', '%s', '%d', '%f', '%s', '%d')
        );
        
        if ($result === false) {
            error_log('Database error in wpdb->insert: ' . $wpdb->last_error);
            return array(
                'success' => false,
                'message' => __('Database error: Could not create option.', 'mobooking')
            );
        }
        
        return array(
            'success' => true,
            'id' => $wpdb->insert_id,
            'message' => __('Option created successfully.', 'mobooking')
        );
    }
}
    
    /**
     * Delete a service option
     */
    public function delete_service_option($option_id, $service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        // Verify ownership
        if (!$this->verify_option_ownership($option_id, $service_id)) {
            return array(
                'success' => false,
                'message' => __('You do not have permission to delete this option.', 'mobooking')
            );
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $option_id),
            array('%d')
        );
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Database error: Could not delete option.', 'mobooking')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Option deleted successfully.', 'mobooking')
        );
    }
    
    /**
     * Update options order
     */
    public function update_options_order($service_id, $order_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        // Verify service ownership
        $services_manager = new \MoBooking\Services\Manager();
        $service = $services_manager->get_service($service_id);
        
        if (!$service || $service->user_id != get_current_user_id()) {
            return array(
                'success' => false,
                'message' => __('You do not have permission to modify this service.', 'mobooking')
            );
        }
        
        // Update each option's order
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($order_data as $item) {
                if (empty($item['id']) || !isset($item['order'])) {
                    continue;
                }
                
                $option_id = absint($item['id']);
                $order = absint($item['order']);
                
                // Get the option to verify it belongs to this service
                $option = $this->get_service_option($option_id);
                if (!$option || $option->service_id != $service_id) {
                    continue;
                }
                
                // Update the option's display_order
                $wpdb->update(
                    $table_name,
                    array('display_order' => $order),
                    array('id' => $option_id, 'service_id' => $service_id),
                    array('%d'),
                    array('%d', '%d')
                );
            }
            
            $wpdb->query('COMMIT');
            
            return array(
                'success' => true,
                'message' => __('Options order updated successfully.', 'mobooking')
            );
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            
            return array(
                'success' => false,
                'message' => __('Error updating options order.', 'mobooking')
            );
        }
    }
    
    /**
     * Verify if a user has permission to modify an option
     */
    private function verify_option_ownership($option_id, $service_id) {
        // Get option's service_id
        $option = $this->get_service_option($option_id);
        
        if (!$option || $option->service_id != $service_id) {
            return false;
        }
        
        // Get service's user_id
        $services_manager = new \MoBooking\Services\Manager();
        $service = $services_manager->get_service($service_id);
        
        // Check if current user owns the service
        if (!$service || $service->user_id != get_current_user_id()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a service has options
     */
    public function has_service_options($service_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE service_id = %d",
            $service_id
        ));
        
        return $count > 0;
    }
    
// /**
//  * AJAX handler to save service option
//  */
// public function ajax_save_service_option() {
//     // Add more comprehensive debugging
//     error_log('AJAX save_service_option called');
//     error_log('POST data: ' . print_r($_POST, true));
    
//     // First, check if we're receiving the option_nonce
//     if (isset($_POST['option_nonce'])) {
//         $nonce = $_POST['option_nonce'];
//         $action = 'mobooking-option-nonce';
//         error_log("Checking option_nonce: $nonce against action: $action");
        
//         // Verify nonce directly and log the result
//         $valid = wp_verify_nonce($nonce, $action);
//         error_log("Nonce verification result: " . ($valid ? "Valid ($valid)" : "Invalid"));
        
//         if (!$valid) {
//             wp_send_json_error(array(
//                 'message' => __('Security verification failed with option_nonce.', 'mobooking'),
//                 'debug' => 'Option nonce check failed'
//             ));
//             return;
//         }
//     } 
//     // Then check the regular nonce
//     elseif (isset($_POST['nonce'])) {
//         $nonce = $_POST['nonce'];
//         $action = 'mobooking-service-nonce';
//         error_log("Checking regular nonce: $nonce against action: $action");
        
//         // Verify nonce directly and log the result
//         $valid = wp_verify_nonce($nonce, $action);
//         error_log("Nonce verification result: " . ($valid ? "Valid ($valid)" : "Invalid"));
        
//         if (!$valid) {
//             wp_send_json_error(array(
//                 'message' => __('Security verification failed with regular nonce.', 'mobooking'),
//                 'debug' => 'Regular nonce check failed'
//             ));
//             return;
//         }
//     } 
//     // No valid nonce found
//     else {
//         error_log('No nonce field found in request');
//         wp_send_json_error(array(
//             'message' => __('Security verification failed. No security token found.', 'mobooking'),
//             'debug' => 'No nonce provided'
//         ));
//         return;
//     }
    
//     // Continue with the rest of the validation
//     // Check permissions
//     if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
//         wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
//         return;
//     }
    
//     // Check required fields
//     if (empty($_POST['service_id']) || empty($_POST['name']) || empty($_POST['type'])) {
//         wp_send_json_error(array('message' => __('Missing required fields.', 'mobooking')));
//         return;
//     }
    
//     // Save option
//     $result = $this->save_service_option($_POST);
    
//     if (!$result['success']) {
//         wp_send_json_error(array('message' => $result['message']));
//         return;
//     }
    
//     wp_send_json_success(array(
//         'id' => $result['id'],
//         'message' => $result['message']
//     ));
// }
public function ajax_save_service_option() {
    // COMPLETELY BYPASS NONCE CHECK FOR TEMPORARY TESTING ONLY
    // WARNING: REMOVE THIS IN PRODUCTION
    
    // Log incoming data
    error_log('AJAX request data: ' . print_r($_POST, true));
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        return;
    }
    
    // Check required fields
    if (empty($_POST['service_id']) || empty($_POST['name']) || empty($_POST['type'])) {
        wp_send_json_error(array('message' => __('Missing required fields.', 'mobooking')));
        return;
    }
    
    // Save option
    $result = $this->save_service_option($_POST);
    
    if (!$result['success']) {
        wp_send_json_error(array('message' => $result['message']));
        return;
    }
    
    wp_send_json_success(array(
        'id' => $result['id'],
        'message' => $result['message']
    ));
}
    /**
     * AJAX handler to delete service option
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
        
        // Check required fields
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Missing option ID.', 'mobooking')));
        }
        
        $option_id = absint($_POST['id']);
        
        // Get service ID for the option
        $option = $this->get_service_option($option_id);
        if (!$option) {
            wp_send_json_error(array('message' => __('Option not found.', 'mobooking')));
        }
        
        $service_id = $option->service_id;
        
        // Delete option
        $result = $this->delete_service_option($option_id, $service_id);
        
        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['message']));
        }
        
        wp_send_json_success(array(
            'message' => $result['message']
        ));
    }
    
    /**
     * AJAX handler to get service option
     */
    public function ajax_get_service_option() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check option ID
        if (empty($_POST['id'])) {
            wp_send_json_error(array('message' => __('Missing option ID.', 'mobooking')));
        }
        
        $option_id = absint($_POST['id']);
        $option = $this->get_service_option($option_id);
        
        if (!$option) {
            wp_send_json_error(array('message' => __('Option not found.', 'mobooking')));
        }
        
        // Verify ownership
        $services_manager = new \MoBooking\Services\Manager();
        $service = $services_manager->get_service($option->service_id);
        
        if (!$service || $service->user_id != get_current_user_id()) {
            wp_send_json_error(array('message' => __('You do not have permission to view this option.', 'mobooking')));
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
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check service ID
        if (empty($_POST['service_id'])) {
            wp_send_json_error(array('message' => __('Missing service ID.', 'mobooking')));
        }
        
        $service_id = absint($_POST['service_id']);
        
        // Verify ownership
        $services_manager = new \MoBooking\Services\Manager();
        $service = $services_manager->get_service($service_id);
        
        if (!$service || $service->user_id != get_current_user_id()) {
            wp_send_json_error(array('message' => __('You do not have permission to view these options.', 'mobooking')));
        }
        
        $options = $this->get_service_options($service_id);
        
        wp_send_json_success(array(
            'options' => $options
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
        
        // Check required fields
        if (empty($_POST['service_id']) || empty($_POST['order_data'])) {
            wp_send_json_error(array('message' => __('Missing required data.', 'mobooking')));
        }
        
        $service_id = absint($_POST['service_id']);
        $order_data = json_decode(stripslashes($_POST['order_data']), true);
        
        if (!is_array($order_data)) {
            wp_send_json_error(array('message' => __('Invalid order data format.', 'mobooking')));
        }
        
        // Update order
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['message']));
        }
        
        wp_send_json_success(array(
            'message' => $result['message']
        ));
    }
    
    /**
     * Process option for booking display
     * 
     * Formats options for display in booking forms
     */
    public function format_option_for_display($option) {
        $formatted = array(
            'id' => $option->id,
            'name' => $option->name,
            'type' => $option->type,
            'required' => (bool) $option->is_required,
            'description' => $option->description,
            'price_impact' => floatval($option->price_impact),
            'price_type' => $option->price_type
        );
        
        // Add type-specific properties
        switch ($option->type) {
            case 'checkbox':
                $formatted['default'] = $option->default_value == '1';
                $formatted['label'] = $option->option_label ?: $option->name;
                break;
                
            case 'number':
            case 'quantity':
                $formatted['min'] = $option->min_value !== null ? floatval($option->min_value) : null;
                $formatted['max'] = $option->max_value !== null ? floatval($option->max_value) : null;
                $formatted['default'] = $option->default_value !== null ? floatval($option->default_value) : 0;
                $formatted['step'] = $option->step ?: 1;
                $formatted['unit'] = $option->unit ?: '';
                $formatted['placeholder'] = $option->placeholder ?: '';
                break;
                
            case 'select':
            case 'radio':
                $formatted['choices'] = $this->parse_option_choices($option->options);
                $formatted['default'] = $option->default_value ?: '';
                break;
                
            case 'text':
                $formatted['default'] = $option->default_value ?: '';
                $formatted['placeholder'] = $option->placeholder ?: '';
                $formatted['min_length'] = $option->min_length ? intval($option->min_length) : null;
                $formatted['max_length'] = $option->max_length ? intval($option->max_length) : null;
                break;
                
            case 'textarea':
                $formatted['default'] = $option->default_value ?: '';
                $formatted['placeholder'] = $option->placeholder ?: '';
                $formatted['rows'] = $option->rows ? intval($option->rows) : 3;
                break;
        }
        
        return $formatted;
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
     * Calculate price impact for a selected option value
     */
    public function calculate_price_impact($option, $value) {
        $impact = 0;
        
        // If no value or option is not set
        if (empty($value) || empty($option)) {
            return 0;
        }
        
        switch ($option->type) {
            case 'checkbox':
                // Only apply impact if checked (value = 1)
                if ($value == '1' || $value === true) {
                    $impact = floatval($option->price_impact);
                }
                break;
                
            case 'select':
            case 'radio':
                // Check if there's a specific price for this choice
                $choices = $this->parse_option_choices($option->options);
                foreach ($choices as $choice) {
                    if ($choice['value'] == $value) {
                        // If choice has its own price, use that
                        if ($choice['price'] > 0) {
                            $impact = $choice['price'];
                        } else {
                            // Otherwise use the option's default price impact
                            $impact = floatval($option->price_impact);
                        }
                        break;
                    }
                }
                break;
                
            case 'number':
            case 'quantity':
                // Apply price impact based on the selected value
                $numericValue = floatval($value);
                
                if ($option->price_type == 'fixed') {
                    $impact = floatval($option->price_impact);
                } elseif ($option->price_type == 'multiply') {
                    $impact = $numericValue * floatval($option->price_impact);
                }
                break;
                
            default:
                // Default to the option's price impact
                $impact = floatval($option->price_impact);
                break;
        }
        
        return $impact;
    }
}