<?php
namespace MoBooking\Services;

/**
 * Services Manager class
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service')); // Updated to use class method
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_nopriv_mobooking_get_services_by_zip', array($this, 'ajax_get_services_by_zip'));
        
        // Add shortcodes
        add_shortcode('mobooking_service_list', array($this, 'service_list_shortcode'));
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
     * AJAX handler to get service by ID
     */
    public function ajax_get_service() {
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
        
        // Get service data
        $service = $this->get_service($service_id, $user_id);
        
        if (!$service) {
            wp_send_json_error(array('message' => __('Service not found or you do not have permission to edit it.', 'mobooking')));
        }
        
        wp_send_json_success(array(
            'service' => $service
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
            'description' => sanitize_textarea_field($data['description']),
            'price' => floatval($data['price']),
            'duration' => absint($data['duration']),
            'icon' => sanitize_text_field($data['icon']),
            'category' => sanitize_text_field($data['category'])
        );
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing service
            $wpdb->update(
                $table_name,
                $service_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s'),
                array('%d')
            );
            
            return absint($data['id']);
        } else {
            // Create new service
            $wpdb->insert(
                $table_name,
                $service_data,
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s')
            );
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a service
     */
    public function delete_service($service_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->delete(
            $table_name,
            array(
                'id' => $service_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
    }
    
    /**
     * Get services by ZIP code
     */
    public function get_services_by_zip($zip_code) {
        global $wpdb;
        
        // Find business owners who serve this ZIP
        $area_table = $wpdb->prefix . 'mobooking_areas';
        $services_table = $wpdb->prefix . 'mobooking_services';
        $settings_table = $wpdb->prefix . 'mobooking_settings';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.*, 
                u.display_name AS business_name,
                set.company_name,
                set.primary_color
            FROM 
                $area_table a
            JOIN 
                $services_table s ON a.user_id = s.user_id
            JOIN 
                {$wpdb->users} u ON a.user_id = u.ID
            LEFT JOIN 
                $settings_table set ON a.user_id = set.user_id
            WHERE 
                a.zip_code = %s
                AND a.active = 1
            ORDER BY 
                s.name ASC",
            $zip_code
        ));
        
        // Group by business
        $businesses = array();
        
        foreach ($results as $row) {
            $user_id = $row->user_id;
            $company_name = !empty($row->company_name) ? $row->company_name : $row->business_name;
            
            if (!isset($businesses[$user_id])) {
                $businesses[$user_id] = array(
                    'id' => $user_id,
                    'name' => $company_name,
                    'primary_color' => $row->primary_color,
                    'services' => array()
                );
            }
            
            $businesses[$user_id]['services'][] = array(
                'id' => $row->id,
                'name' => $row->name,
                'description' => $row->description,
                'price' => $row->price,
                'duration' => $row->duration,
                'icon' => $row->icon,
                'category' => $row->category
            );
        }
        
        return $businesses;
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
            'category' => isset($_POST['category']) ? $_POST['category'] : ''
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-services-nonce')) {
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
     * AJAX handler to get services by ZIP
     */
    public function ajax_get_services_by_zip() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-services-zip-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check ZIP code
        if (!isset($_POST['zip_code']) || empty($_POST['zip_code'])) {
            wp_send_json_error(__('ZIP code is required.', 'mobooking'));
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        $businesses = $this->get_services_by_zip($zip_code);
        
        wp_send_json_success(array(
            'businesses' => $businesses
        ));
    }
    
    /**
     * Service list shortcode
     */
    public function service_list_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'category' => '',
            'limit' => -1
        ), $atts);
        
        $user_id = $atts['user_id'] ? absint($atts['user_id']) : get_current_user_id();
        
        // If not logged in and no user ID specified, return nothing
        if (!$user_id) {
            return '';
        }
        
        // Get services
        $services = $this->get_user_services($user_id);
        
        if (empty($services)) {
            return '<p>' . __('No services found.', 'mobooking') . '</p>';
        }
        
        // Filter by category if specified
        if (!empty($atts['category'])) {
            $services = array_filter($services, function($service) use ($atts) {
                return $service->category == $atts['category'];
            });
        }
        
        // Limit number of services if specified
        if ($atts['limit'] > 0 && count($services) > $atts['limit']) {
            $services = array_slice($services, 0, $atts['limit']);
        }
        
        ob_start();
        ?>
        <div class="mobooking-service-list">
            <?php foreach ($services as $service) : ?>
                <div class="service-item">
                    <?php if (!empty($service->icon)) : ?>
                        <div class="service-icon">
                            <i class="<?php echo esc_attr($service->icon); ?>"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="service-details">
                        <h3 class="service-name"><?php echo esc_html($service->name); ?></h3>
                        <div class="service-meta">
                            <span class="service-price"><?php echo wc_price($service->price); ?></span>
                            <span class="service-duration"><?php echo sprintf(_n('%d minute', '%d minutes', $service->duration, 'mobooking'), $service->duration); ?></span>
                        </div>
                        <div class="service-description">
                            <?php echo wpautop(esc_html($service->description)); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}