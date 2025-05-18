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
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service_with_options'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_nopriv_mobooking_get_services_by_zip', array($this, 'ajax_get_services_by_zip'));
        
        // Options-related AJAX handlers
        add_action('wp_ajax_mobooking_update_options_order', array($this, 'ajax_update_options_order'));
        
        // Enqueue assets for the editor
        add_action('wp_enqueue_scripts', array($this, 'enqueue_editor_assets'));
    }


    /**
     * Enqueue assets for the service editor
     */
    public function enqueue_editor_assets() {
        // Only enqueue on service editor page
        if (isset($_GET['page']) && $_GET['page'] === 'service-editor') {
            wp_enqueue_style('mobooking-service-editor', MOBOOKING_URL . '/assets/css/service-editor.css', array(), MOBOOKING_VERSION);
            wp_enqueue_script('mobooking-service-editor', MOBOOKING_URL . '/assets/js/service-editor.js', array('jquery', 'jquery-ui-sortable'), MOBOOKING_VERSION, true);
            
            wp_localize_script('mobooking-service-editor', 'mobooking_service', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking-service-nonce'),
                'service_id' => isset($_GET['id']) ? absint($_GET['id']) : 0
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
            'unit' => isset($data['unit']) ? sanitize_text_field($data['unit']) : ''
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
    
    /**
     * AJAX handler to save a service with options
     */
    public function ajax_save_service() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
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
        
        // Handle options if passed
        if (isset($_POST['options']) && is_array($_POST['options'])) {
            $service_data['options'] = $_POST['options'];
        }
        
        // Add ID if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $service_data['id'] = $_POST['id'];
        }
        
        // Validate data
        if (empty($service_data['name'])) {
            wp_send_json_error(__('Service name is required.', 'mobooking'));
        }
        
        // Save service
        $service_id = $this->save_service($service_data);
        
        if (!$service_id) {
            wp_send_json_error(__('Failed to save service.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'id' => $service_id,
            'message' => __('Service saved successfully.', 'mobooking'),
            'redirect' => home_url('/dashboard/services/edit/' . $service_id)
        ));
    }
    
    /**
     * AJAX handler to get a service with options
     */
    public function ajax_get_service_with_options() {
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
        
        // Get complete service data
        $service = $this->get_service_with_options($service_id, $user_id);
        
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to edit it.', 'mobooking')));
        }
        
        wp_send_json_success(array('service' => $service));
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
        $order_data = json_decode(stripslashes($_POST['order_data']), true);
        
        // Update order
        $result = $this->update_options_order($service_id, $order_data);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to update options order.', 'mobooking')));
        }
        
        wp_send_json_success(array('message' => __('Options order updated successfully.', 'mobooking')));
    }
    
    /**
     * Render the unified service editor
     */
    public function render_service_editor($service_id = 0) {
        $user_id = get_current_user_id();
        
        // Get service data if editing
        if ($service_id > 0) {
            $service = $this->get_service_with_options($service_id, $user_id);
            if (!$service) {
                return '<div class="error-message">Service not found or you do not have permission to edit it.</div>';
            }
        } else {
            // New service defaults
            $service = (object)[
                'id' => 0,
                'name' => '',
                'description' => '',
                'price' => 0,
                'duration' => 60,
                'icon' => '',
                'category' => '',
                'status' => 'active',
                'options' => []
            ];
        }
        
        // Render the unified editor interface
        ob_start();
        ?>
        <div class="service-editor" data-service-id="<?php echo esc_attr($service_id); ?>">
            <div class="service-editor-tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="service-info">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path></svg>
                        <?php _e('Service Info', 'mobooking'); ?>
                    </button>
                    <button class="tab-button" data-tab="service-options">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        <?php _e('Service Options', 'mobooking'); ?>
                    </button>
                </div>
                
                <div class="tab-content">
                    <!-- Service Basic Info Tab -->
                    <div class="tab-pane active" id="service-info-tab">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="service-name"><?php _e('Service Name', 'mobooking'); ?></label>
                                <input type="text" id="service-name" name="name" value="<?php echo esc_attr($service->name); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row two-col">
                            <div class="form-group">
                                <label for="service-price"><?php _e('Base Price', 'mobooking'); ?></label>
                                <input type="number" id="service-price" name="price" value="<?php echo esc_attr($service->price); ?>" min="0" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-duration"><?php _e('Duration (minutes)', 'mobooking'); ?></label>
                                <input type="number" id="service-duration" name="duration" value="<?php echo esc_attr($service->duration); ?>" min="15" step="15" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                                <textarea id="service-description" name="description" rows="4"><?php echo esc_textarea($service->description); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row two-col">
                            <div class="form-group">
                                <label for="service-category"><?php _e('Category', 'mobooking'); ?></label>
                                <select id="service-category" name="category">
                                    <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                    <option value="residential" <?php selected($service->category, 'residential'); ?>><?php _e('Residential', 'mobooking'); ?></option>
                                    <option value="commercial" <?php selected($service->category, 'commercial'); ?>><?php _e('Commercial', 'mobooking'); ?></option>
                                    <option value="special" <?php selected($service->category, 'special'); ?>><?php _e('Special', 'mobooking'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-status"><?php _e('Status', 'mobooking'); ?></label>
                                <select id="service-status" name="status">
                                    <option value="active" <?php selected($service->status, 'active'); ?>><?php _e('Active', 'mobooking'); ?></option>
                                    <option value="inactive" <?php selected($service->status, 'inactive'); ?>><?php _e('Inactive', 'mobooking'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row two-col">
                            <div class="form-group">
                                <label for="service-icon"><?php _e('Icon', 'mobooking'); ?></label>
                                <select id="service-icon" name="icon">
                                    <option value=""><?php _e('None', 'mobooking'); ?></option>
                                    <option value="dashicons-admin-home" <?php selected($service->icon, 'dashicons-admin-home'); ?>><?php _e('Home', 'mobooking'); ?></option>
                                    <option value="dashicons-admin-tools" <?php selected($service->icon, 'dashicons-admin-tools'); ?>><?php _e('Tools', 'mobooking'); ?></option>
                                    <option value="dashicons-car" <?php selected($service->icon, 'dashicons-car'); ?>><?php _e('Car', 'mobooking'); ?></option>
                                </select>
                                <div class="icon-preview"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-image"><?php _e('Image URL', 'mobooking'); ?></label>
                                <div class="image-upload-container">
                                    <input type="text" id="service-image" name="image_url" value="<?php echo esc_attr($service->image_url); ?>" placeholder="https://...">
                                    <button type="button" class="button select-image"><?php _e('Select', 'mobooking'); ?></button>
                                </div>
                                <div class="image-preview"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service Options Tab -->
                    <div class="tab-pane" id="service-options-tab">
                        <div class="options-container">
                            <div class="options-header">
                                <h3><?php _e('Service Options', 'mobooking'); ?></h3>
                                <button type="button" class="button add-option">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    <?php _e('Add Option', 'mobooking'); ?>
                                </button>
                            </div>
                            
                            <div class="options-list">
                                <?php if (empty($service->options)) : ?>
                                    <div class="no-options-message">
                                        <?php _e('No options added yet. Click "Add Option" to create your first service option.', 'mobooking'); ?>
                                    </div>
                                <?php else : ?>
                                    <?php foreach ($service->options as $option) : ?>
                                        <div class="option-item" data-id="<?php echo esc_attr($option->id); ?>" data-order="<?php echo esc_attr($option->display_order); ?>">
                                            <div class="option-header">
                                                <div class="option-drag-handle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                                                </div>
                                                <div class="option-name"><?php echo esc_html($option->name); ?></div>
                                                <div class="option-type"><?php echo esc_html($option->type); ?></div>
                                                <div class="option-actions">
                                                    <button type="button" class="button-link edit-option">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                    </button>
                                                    <button type="button" class="button-link remove-option">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    </button>
                                                </div>
                                            </div>

</div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Option Edit Form (Shown when editing an option) -->
                        <div class="option-editor" style="display: none;">
                            <div class="option-editor-header">
                                <h3><?php _e('Edit Option', 'mobooking'); ?></h3>
                                <button type="button" class="button-link close-option-editor">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                            </div>
                            
                            <form id="option-form">
                                <input type="hidden" id="option-id" name="id" value="">
                                <input type="hidden" id="option-service-id" name="service_id" value="<?php echo esc_attr($service_id); ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="option-name"><?php _e('Option Name', 'mobooking'); ?></label>
                                        <input type="text" id="option-name" name="name" required>
                                    </div>
                                </div>
                                
                                <div class="form-row two-col">
                                    <div class="form-group">
                                        <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
                                        <select id="option-type" name="type" required>
                                            <option value=""><?php _e('Select Type', 'mobooking'); ?></option>
                                            <option value="checkbox"><?php _e('Checkbox', 'mobooking'); ?></option>
                                            <option value="select"><?php _e('Dropdown Select', 'mobooking'); ?></option>
                                            <option value="radio"><?php _e('Radio Buttons', 'mobooking'); ?></option>
                                            <option value="number"><?php _e('Number Input', 'mobooking'); ?></option>
                                            <option value="text"><?php _e('Text Input', 'mobooking'); ?></option>
                                            <option value="textarea"><?php _e('Text Area', 'mobooking'); ?></option>
                                            <option value="quantity"><?php _e('Quantity Selector', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                                        <select id="option-required" name="is_required">
                                            <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                                            <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                                        <textarea id="option-description" name="description" rows="2"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Dynamic fields based on option type -->
                                <div class="dynamic-fields"></div>
                                
                                <div class="form-row two-col pricing-section">
                                    <div class="form-group">
                                        <label for="option-price-type"><?php _e('Price Impact Type', 'mobooking'); ?></label>
                                        <select id="option-price-type" name="price_type">
                                            <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                                            <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                                            <option value="multiply"><?php _e('Multiply by Value', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="option-price-impact"><?php _e('Price Value', 'mobooking'); ?></label>
                                        <input type="number" id="option-price-impact" name="price_impact" step="0.01" value="0">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="button button-secondary cancel-option"><?php _e('Cancel', 'mobooking'); ?></button>
                                    <button type="submit" class="button button-primary save-option"><?php _e('Save Option', 'mobooking'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="service-editor-actions">
                <button type="button" class="button button-secondary cancel-service"><?php _e('Cancel', 'mobooking'); ?></button>
                <button type="button" class="button button-primary save-service-btn"><?php _e('Save Service', 'mobooking'); ?></button>
            </div>
        </div>
        <script>
            // Initialize Icon Preview
            jQuery(document).ready(function($) {
                const iconSelect = $('#service-icon');
                const iconPreview = $('.icon-preview');
                
                // Show initial icon preview if exists
                if (iconSelect.val()) {
                    iconPreview.html('<span class="dashicons ' + iconSelect.val() + '"></span>');
                }
                
                // Update icon preview on change
                iconSelect.on('change', function() {
                    const iconClass = $(this).val();
                    if (iconClass) {
                        iconPreview.html('<span class="dashicons ' + iconClass + '"></span>');
                    } else {
                        iconPreview.empty();
                    }
                });
                
                // Show initial image preview if exists
                const imageUrl = $('#service-image').val();
                if (imageUrl) {
                    $('.image-preview').html('<img src="' + imageUrl + '" alt="Preview">');
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate dynamic fields for option types
     */
    public function generate_option_fields($option_type, $option_data = null) {
        $html = '';
        
        switch ($option_type) {
            case 'checkbox':
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-default-value">' . __('Default Value', 'mobooking') . '</label>';
                $html .= '<select id="option-default-value" name="default_value">';
                $html .= '<option value="0" ' . selected(!empty($option_data) && $option_data->default_value == '0', true, false) . '>' . __('Unchecked', 'mobooking') . '</option>';
                $html .= '<option value="1" ' . selected(!empty($option_data) && $option_data->default_value == '1', true, false) . '>' . __('Checked', 'mobooking') . '</option>';
                $html .= '</select>';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-label">' . __('Option Label', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-label" name="option_label" value="' . ((!empty($option_data) && $option_data->option_label) ? esc_attr($option_data->option_label) : '') . '" placeholder="' . __('Check this box to add...', 'mobooking') . '">';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'select':
            case 'radio':
                $html .= '<div class="form-group">';
                $html .= '<label>' . __('Choices', 'mobooking') . '</label>';
                $html .= '<div class="choices-container">';
                $html .= '<div class="choices-list">';
                
                if (!empty($option_data) && !empty($option_data->options)) {
                    $choices = $this->parse_option_choices($option_data->options);
                    foreach ($choices as $choice) {
                        $html .= '<div class="choice-row">';
                        $html .= '<div class="choice-drag-handle"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></div>';
                        $html .= '<div class="choice-value"><input type="text" class="choice-value-input" value="' . esc_attr($choice['value']) . '" placeholder="' . __('Value', 'mobooking') . '"></div>';
                        $html .= '<div class="choice-label"><input type="text" class="choice-label-input" value="' . esc_attr($choice['label']) . '" placeholder="' . __('Display Label', 'mobooking') . '"></div>';
                        $html .= '<div class="choice-price"><input type="number" class="choice-price-input" value="' . esc_attr($choice['price']) . '" step="0.01" placeholder="0.00"></div>';
                        $html .= '<div class="choice-actions"><button type="button" class="button-link remove-choice"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>';
                        $html .= '</div>';
                    }
                } else {
                    // Add one empty choice row by default
                    $html .= '<div class="choice-row">';
                    $html .= '<div class="choice-drag-handle"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg></div>';
                    $html .= '<div class="choice-value"><input type="text" class="choice-value-input" placeholder="' . __('Value', 'mobooking') . '"></div>';
                    $html .= '<div class="choice-label"><input type="text" class="choice-label-input" placeholder="' . __('Display Label', 'mobooking') . '"></div>';
                    $html .= '<div class="choice-price"><input type="number" class="choice-price-input" step="0.01" placeholder="0.00"></div>';
                    $html .= '<div class="choice-actions"><button type="button" class="button-link remove-choice"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>';
                    $html .= '</div>';
                }
                
                $html .= '</div>';
                $html .= '<button type="button" class="button add-choice"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> ' . __('Add Choice', 'mobooking') . '</button>';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="form-group">';
                $html .= '<label for="option-default-value">' . __('Default Selected Value', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-default-value" name="default_value" value="' . ((!empty($option_data) && $option_data->default_value) ? esc_attr($option_data->default_value) : '') . '">';
                $html .= '<small class="hint">' . __('Enter the value (not the label) of the default choice', 'mobooking') . '</small>';
                $html .= '</div>';
                break;
                
            case 'number':
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-min-value">' . __('Minimum Value', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-min-value" name="min_value" value="' . ((!empty($option_data) && $option_data->min_value !== null) ? esc_attr($option_data->min_value) : '0') . '">';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-max-value">' . __('Maximum Value', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-max-value" name="max_value" value="' . ((!empty($option_data) && $option_data->max_value !== null) ? esc_attr($option_data->max_value) : '') . '">';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-default-value">' . __('Default Value', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-default-value" name="default_value" value="' . ((!empty($option_data) && $option_data->default_value !== null) ? esc_attr($option_data->default_value) : '') . '">';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-step">' . __('Step', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-step" name="step" value="' . ((!empty($option_data) && $option_data->step) ? esc_attr($option_data->step) : '1') . '" step="0.01">';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="form-row">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-unit">' . __('Unit Label', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-unit" name="unit" value="' . ((!empty($option_data) && $option_data->unit) ? esc_attr($option_data->unit) : '') . '" placeholder="' . __('sq ft, hours, etc.', 'mobooking') . '">';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'quantity':
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-min-value">' . __('Minimum Quantity', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-min-value" name="min_value" value="' . ((!empty($option_data) && $option_data->min_value !== null) ? esc_attr($option_data->min_value) : '0') . '" min="0">';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-max-value">' . __('Maximum Quantity', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-max-value" name="max_value" value="' . ((!empty($option_data) && $option_data->max_value !== null) ? esc_attr($option_data->max_value) : '') . '" min="0">';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-default-value">' . __('Default Quantity', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-default-value" name="default_value" value="' . ((!empty($option_data) && $option_data->default_value !== null) ? esc_attr($option_data->default_value) : '0') . '" min="0">';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-unit">' . __('Unit Label', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-unit" name="unit" value="' . ((!empty($option_data) && $option_data->unit) ? esc_attr($option_data->unit) : '') . '" placeholder="' . __('items, people, etc.', 'mobooking') . '">';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'text':
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-default-value">' . __('Default Value', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-default-value" name="default_value" value="' . ((!empty($option_data) && $option_data->default_value) ? esc_attr($option_data->default_value) : '') . '">';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-placeholder">' . __('Placeholder', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-placeholder" name="placeholder" value="' . ((!empty($option_data) && $option_data->placeholder) ? esc_attr($option_data->placeholder) : '') . '">';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'textarea':
                $html .= '<div class="form-group">';
                $html .= '<label for="option-default-value">' . __('Default Value', 'mobooking') . '</label>';
                $html .= '<textarea id="option-default-value" name="default_value" rows="2">' . ((!empty($option_data) && $option_data->default_value) ? esc_textarea($option_data->default_value) : '') . '</textarea>';
                $html .= '</div>';
                
                $html .= '<div class="form-row two-col">';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-placeholder">' . __('Placeholder', 'mobooking') . '</label>';
                $html .= '<input type="text" id="option-placeholder" name="placeholder" value="' . ((!empty($option_data) && $option_data->placeholder) ? esc_attr($option_data->placeholder) : '') . '">';
                $html .= '</div>';
                $html .= '<div class="form-group">';
                $html .= '<label for="option-rows">' . __('Rows', 'mobooking') . '</label>';
                $html .= '<input type="number" id="option-rows" name="rows" value="' . ((!empty($option_data) && $option_data->rows) ? esc_attr($option_data->rows) : '3') . '" min="2">';
                $html .= '</div>';
                $html .= '</div>';
                break;
        }
        
        return $html;
    }
    
    /**
     * AJAX handler to get dynamic option fields
     */
    public function ajax_get_option_fields() {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'mobooking')));
        }
        
        // Check required data
        if (empty($_POST['type'])) {
            wp_send_json_error(array('message' => __('Missing option type.', 'mobooking')));
        }
        
        $type = sanitize_text_field($_POST['type']);
        $option_data = null;
        
        // If editing an existing option, get its data
        if (!empty($_POST['id'])) {
            global $wpdb;
            $options_table = $wpdb->prefix . 'mobooking_service_options';
            
            $option_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $options_table WHERE id = %d",
                absint($_POST['id'])
            ));
        }
        
        $html = $this->generate_option_fields($type, $option_data);
        
        wp_send_json_success(array('html' => $html));
    }
}