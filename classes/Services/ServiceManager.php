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
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service'));
        
        // Add shortcodes
        add_shortcode('mobooking_service_list', array($this, 'service_list_shortcode'));
    }
    
    /**
     * Get services for a user
     */
    public function get_user_services($user_id, $args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $defaults = array(
            'status' => '',
            'category' => '',
            'limit' => -1,
            'offset' => 0,
            'order' => 'ASC',
            'orderby' => 'name'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Filter by status
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        // Filter by category
        if (!empty($args['category'])) {
            $sql .= " AND category = %s";
            $params[] = $args['category'];
        }
        
        // Order the results
        $sql .= " ORDER BY " . sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Limit the results
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d, %d";
            $params[] = $args['offset'];
            $params[] = $args['limit'];
        }
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
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
        
        // Sanitize data
        $service_data = array(
            'user_id' => absint($data['user_id']),
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'price' => floatval($data['price']),
            'duration' => absint($data['duration']),
            'icon' => sanitize_text_field($data['icon']),
            'image_url' => esc_url_raw($data['image_url']),
            'category' => sanitize_text_field($data['category']),
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'active'
        );
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing service
            $result = $wpdb->update(
                $table_name,
                $service_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                return absint($data['id']);
            }
        } else {
            // Create new service
            $result = $wpdb->insert(
                $table_name,
                $service_data,
                array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s')
            );
            
            if ($result !== false) {
                return $wpdb->insert_id;
            }
        }
        
        return false;
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
     * Get service categories for a user
     */
    public function get_user_categories($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $categories = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT category FROM $table_name WHERE user_id = %d AND category != '' ORDER BY category",
            $user_id
        ));
        
        return array_filter($categories);
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
            $service_data['id'] = absint($_POST['id']);
            
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
        
        if ($service_data['price'] < 0) {
            wp_send_json_error(__('Price cannot be negative.', 'mobooking'));
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
        
        // Parse args
        $args = array(
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : -1,
            'offset' => isset($_POST['offset']) ? absint($_POST['offset']) : 0
        );
        
        $services = $this->get_user_services($user_id, $args);
        $total = $this->count_user_services($user_id, $args['status']);
        
        wp_send_json_success(array(
            'services' => $services,
            'total' => $total
        ));
    }
    
    /**
     * AJAX handler to get single service
     */
    public function ajax_get_service() {
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
        
        $service = $this->get_service($service_id, $user_id);
        
        if (!$service) {
            wp_send_json_error(__('Service not found.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'service' => $service
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
            'limit' => -1,
            'show_pricing' => true,
            'show_description' => true
        ), $atts);
        
        $user_id = $atts['user_id'] ? absint($atts['user_id']) : get_current_user_id();
        
        // If no user ID specified, return nothing
        if (!$user_id) {
            return '';
        }
        
        // Get services
        $args = array(
            'status' => 'active',
            'category' => $atts['category'],
            'limit' => $atts['limit']
        );
        
        $services = $this->get_user_services($user_id, $args);
        
        if (empty($services)) {
            return '<p>' . __('No services available.', 'mobooking') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="mobooking-service-list">
            <?php foreach ($services as $service) : ?>
                <div class="service-item">
                    <?php if (!empty($service->image_url)) : ?>
                        <div class="service-image">
                            <img src="<?php echo esc_url($service->image_url); ?>" alt="<?php echo esc_attr($service->name); ?>">
                        </div>
                    <?php elseif (!empty($service->icon)) : ?>
                        <div class="service-icon">
                            <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="service-content">
                        <h3 class="service-name"><?php echo esc_html($service->name); ?></h3>
                        
                        <?php if ($atts['show_description'] && !empty($service->description)) : ?>
                            <div class="service-description">
                                <?php echo wpautop(esc_html($service->description)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="service-meta">
                            <span class="service-duration">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo sprintf(_n('%d minute', '%d minutes', $service->duration, 'mobooking'), $service->duration); ?>
                            </span>
                            
                            <?php if ($atts['show_pricing']) : ?>
                                <span class="service-price">
                                    <?php echo wc_price($service->price); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}