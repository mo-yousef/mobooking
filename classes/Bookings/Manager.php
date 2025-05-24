<?php
namespace MoBooking\Bookings;

/**
 * Bookings Manager class
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_nopriv_mobooking_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_mobooking_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_mobooking_get_bookings', array($this, 'ajax_get_bookings'));
        
        // Add shortcodes
        add_shortcode('mobooking_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('mobooking_booking_list', array($this, 'booking_list_shortcode'));
    }
    
    /**
     * Get bookings for a user
     */
    public function get_user_bookings($user_id, $args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $defaults = array(
            'status' => '',
            'limit' => -1,
            'offset' => 0,
            'order' => 'DESC',
            'orderby' => 'service_date'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        // Filter by status if specified
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
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
     * Get a specific booking
     */
    public function get_booking($booking_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        if ($user_id) {
            // Get booking only if it belongs to the user
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $booking_id, $user_id
            ));
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $booking_id
        ));
    }
    
/**
     * Save a booking
     */
    public function save_booking($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        // Sanitize data
        $booking_data = array(
            'user_id' => absint($data['user_id']),
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => isset($data['customer_phone']) ? sanitize_text_field($data['customer_phone']) : '',
            'customer_address' => sanitize_textarea_field($data['customer_address']),
            'zip_code' => sanitize_text_field($data['zip_code']),
            'service_date' => sanitize_text_field($data['service_date']),
            'services' => is_array($data['services']) ? wp_json_encode($data['services']) : $data['services'],
            'total_price' => floatval($data['total_price']),
            'discount_code' => isset($data['discount_code']) ? sanitize_text_field($data['discount_code']) : null,
            'discount_amount' => isset($data['discount_amount']) ? floatval($data['discount_amount']) : null,
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'pending',
            'notes' => isset($data['notes']) ? sanitize_textarea_field($data['notes']) : ''
        );
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing booking
            $wpdb->update(
                $table_name,
                $booking_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%f', '%s', '%s'),
                array('%d')
            );
            
            return absint($data['id']);
        } else {
            // Create new booking
            $wpdb->insert(
                $table_name,
                $booking_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%f', '%s', '%s')
            );
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Update booking status
     */
    public function update_booking_status($booking_id, $status, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        // Check if booking exists and belongs to user
        $booking = $this->get_booking($booking_id, $user_id);
        if (!$booking) {
            return false;
        }
        
        // Update status
        return $wpdb->update(
            $table_name,
            array('status' => sanitize_text_field($status)),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Delete a booking
     */
    public function delete_booking($booking_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        return $wpdb->delete(
            $table_name,
            array(
                'id' => $booking_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
    }
    
    /**
     * Count bookings for a user
     */
    public function count_user_bookings($user_id, $status = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $sql = "SELECT COUNT(*) FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($status)) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        
        return $wpdb->get_var($wpdb->prepare($sql, $params));
    }
    
    /**
     * Calculate total revenue for a user
     */
    public function calculate_user_revenue($user_id, $period = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $sql = "SELECT SUM(total_price) FROM $table_name WHERE user_id = %d AND status != 'cancelled'";
        $params = array($user_id);
        
        if ($period == 'today') {
            $sql .= " AND DATE(service_date) = CURDATE()";
        } elseif ($period == 'this_week') {
            $sql .= " AND YEARWEEK(service_date, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($period == 'this_month') {
            $sql .= " AND MONTH(service_date) = MONTH(CURDATE()) AND YEAR(service_date) = YEAR(CURDATE())";
        } elseif ($period == 'this_year') {
            $sql .= " AND YEAR(service_date) = YEAR(CURDATE())";
        }
        
        $revenue = $wpdb->get_var($wpdb->prepare($sql, $params));
        
        return $revenue ? $revenue : 0;
    }
    
    /**
     * Get most popular service for a user
     */
    public function get_most_popular_service($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT services FROM $table_name WHERE user_id = %d AND status != 'cancelled'",
            $user_id
        ));
        
        if (empty($bookings)) {
            return null;
        }
        
        // Count service occurrences
        $service_counts = array();
        
        foreach ($bookings as $booking) {
            $services = json_decode($booking->services, true);
            
            if (is_array($services)) {
                foreach ($services as $service) {
                    $service_id = $service['id'];
                    
                    if (!isset($service_counts[$service_id])) {
                        $service_counts[$service_id] = 0;
                    }
                    
                    $service_counts[$service_id]++;
                }
            }
        }
        
        if (empty($service_counts)) {
            return null;
        }
        
        // Find the most popular service
        arsort($service_counts);
        $most_popular_id = key($service_counts);
        
        // Get service details
        $services_manager = new \MoBooking\Services\ServiceManager();
        return $services_manager->get_service($most_popular_id);
    }
    
    /**
     * AJAX handler to save booking
     */
    public function ajax_save_booking() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Prepare data
        $booking_data = array(
            'user_id' => isset($_POST['user_id']) ? absint($_POST['user_id']) : 0,
            'customer_name' => isset($_POST['customer_name']) ? $_POST['customer_name'] : '',
            'customer_email' => isset($_POST['customer_email']) ? $_POST['customer_email'] : '',
            'customer_phone' => isset($_POST['customer_phone']) ? $_POST['customer_phone'] : '',
            'customer_address' => isset($_POST['customer_address']) ? $_POST['customer_address'] : '',
            'zip_code' => isset($_POST['zip_code']) ? $_POST['zip_code'] : '',
            'service_date' => isset($_POST['service_date']) ? $_POST['service_date'] : '',
            'services' => isset($_POST['services']) ? $_POST['services'] : array(),
            'total_price' => isset($_POST['total_price']) ? $_POST['total_price'] : 0,
            'discount_code' => isset($_POST['discount_code']) ? $_POST['discount_code'] : '',
            'discount_amount' => isset($_POST['discount_amount']) ? $_POST['discount_amount'] : 0,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : ''
        );
        
        // Validate data
        if (empty($booking_data['user_id']) || empty($booking_data['customer_name']) || 
            empty($booking_data['customer_email']) || empty($booking_data['customer_address']) || 
            empty($booking_data['zip_code']) || empty($booking_data['service_date']) || 
            empty($booking_data['services']) || $booking_data['total_price'] <= 0) {
            
            wp_send_json_error(__('Please fill in all required fields.', 'mobooking'));
        }
        
        // Validate email
        if (!is_email($booking_data['customer_email'])) {
            wp_send_json_error(__('Please enter a valid email address.', 'mobooking'));
        }
        
        // Validate services format
        if (is_array($booking_data['services'])) {
            $booking_data['services'] = wp_json_encode($booking_data['services']);
        } elseif (!is_string($booking_data['services'])) {
            wp_send_json_error(__('Invalid services format.', 'mobooking'));
        }
        
        // Save booking
        $booking_id = $this->save_booking($booking_data);
        
        if (!$booking_id) {
            wp_send_json_error(__('Failed to save booking.', 'mobooking'));
        }
        
        // If there's a discount code, increment its usage count
        if (!empty($booking_data['discount_code'])) {
            $discounts_manager = new \MoBooking\Discounts\Manager();
            $discount = $discounts_manager->get_discount_by_code($booking_data['discount_code'], $booking_data['user_id']);
            
            if ($discount) {
                $discounts_manager->increment_usage_count($discount->id);
            }
        }
        
        // Send notification emails
        $notifications_manager = new \MoBooking\Notifications\Manager();
        $notifications_manager->send_booking_confirmation($booking_id);
        $notifications_manager->send_admin_notification($booking_id);
        
        wp_send_json_success(array(
            'id' => $booking_id,
            'message' => __('Booking saved successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to update booking status
     */
    public function ajax_update_booking_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-status-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Check required fields
        if (!isset($_POST['booking_id']) || empty($_POST['booking_id']) || !isset($_POST['status']) || empty($_POST['status'])) {
            wp_send_json_error(__('Missing required information.', 'mobooking'));
        }
        
        $booking_id = absint($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        $user_id = get_current_user_id();
        
        // Valid statuses
        $valid_statuses = array('pending', 'confirmed', 'completed', 'cancelled');
        
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(__('Invalid status.', 'mobooking'));
        }
        
        // Update status
        $result = $this->update_booking_status($booking_id, $status, $user_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to update booking status.', 'mobooking'));
        }
        
        // Send status update notification
        if ($status == 'confirmed' || $status == 'cancelled') {
            $notifications_manager = new \MoBooking\Notifications\Manager();
            $notifications_manager->send_status_update($booking_id);
        }
        
        wp_send_json_success(array(
            'message' => __('Booking status updated successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to get bookings
     */
    public function ajax_get_bookings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-bookings-nonce')) {
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
            'limit' => isset($_POST['limit']) ? absint($_POST['limit']) : -1,
            'offset' => isset($_POST['offset']) ? absint($_POST['offset']) : 0,
            'order' => isset($_POST['order']) && in_array(strtoupper($_POST['order']), array('ASC', 'DESC')) ? strtoupper($_POST['order']) : 'DESC',
            'orderby' => isset($_POST['orderby']) ? sanitize_sql_orderby($_POST['orderby']) : 'service_date'
        );
        
        $bookings = $this->get_user_bookings($user_id, $args);
        $total = $this->count_user_bookings($user_id, $args['status']);
        
        wp_send_json_success(array(
            'bookings' => $bookings,
            'total' => $total
        ));
    }


    /**
     * Enhanced Multi-Step Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'style' => 'modern' // modern, classic
        ), $atts);
        
        // If no user ID specified, return nothing
        if (empty($atts['user_id'])) {
            return '<p>' . __('Please specify a service provider.', 'mobooking') . '</p>';
        }
        
        $user_id = absint($atts['user_id']);
        
        // Verify user exists and is a business owner
        $user = get_userdata($user_id);
        if (!$user || !in_array('mobooking_business_owner', $user->roles)) {
            return '<p>' . __('Invalid service provider.', 'mobooking') . '</p>';
        }
        
        // Get user's services
        $services_manager = new \MoBooking\Services\ServicesManager();
        $services = $services_manager->get_user_services($user_id);
        
        if (empty($services)) {
            return '<p>' . __('No services available for this provider.', 'mobooking') . '</p>';
        }
        
        // Get user settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($user_id);
        
        // Get service options for each service
        $options_manager = new \MoBooking\Services\ServiceOptionsManager();
        foreach ($services as $service) {
            $service->options = $options_manager->get_service_options($service->id);
        }
        
        // Enqueue necessary scripts and styles
        wp_enqueue_script('jquery');
        wp_enqueue_style('mobooking-booking-form', MOBOOKING_URL . '/assets/css/booking-form.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-booking-form', MOBOOKING_URL . '/assets/js/booking-form.js', array('jquery'), MOBOOKING_VERSION, true);
        
        // Localize script
        wp_localize_script('mobooking-booking-form', 'mobookingBooking', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'userId' => $user_id,
            'nonces' => array(
                'zip_check' => wp_create_nonce('mobooking-zip-nonce'),
                'booking' => wp_create_nonce('mobooking-booking-nonce'),
                'discount' => wp_create_nonce('mobooking-validate-discount-nonce')
            ),
            'strings' => array(
                'loading' => __('Loading...', 'mobooking'),
                'error' => __('An error occurred. Please try again.', 'mobooking'),
                'selectService' => __('Please select at least one service.', 'mobooking'),
                'fillRequired' => __('Please fill in all required fields.', 'mobooking'),
                'invalidEmail' => __('Please enter a valid email address.', 'mobooking'),
                'processingBooking' => __('Processing your booking...', 'mobooking'),
                'bookingSuccess' => __('Booking successful!', 'mobooking')
            ),
            'currency' => array(
                'symbol' => get_woocommerce_currency_symbol(),
                'position' => get_option('woocommerce_currency_pos', 'left')
            )
        ));
        
        ob_start();
        ?>
        <div class="mobooking-booking-form-container" data-user-id="<?php echo esc_attr($user_id); ?>" data-style="<?php echo esc_attr($atts['style']); ?>">
            <!-- Progress Indicator -->
            <div class="booking-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 20%;"></div>
                </div>
                <div class="progress-steps">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label"><?php _e('Location', 'mobooking'); ?></div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label"><?php _e('Services', 'mobooking'); ?></div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label"><?php _e('Options', 'mobooking'); ?></div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label"><?php _e('Details', 'mobooking'); ?></div>
                    </div>
                    <div class="step" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-label"><?php _e('Confirm', 'mobooking'); ?></div>
                    </div>
                </div>
            </div>
            
            <form id="mobooking-booking-form" class="booking-form">
                <!-- Step 1: ZIP Code Check -->
                <div class="booking-step step-1 active">
                    <div class="step-header">
                        <h2><?php _e('Check Service Availability', 'mobooking'); ?></h2>
                        <p><?php _e('Enter your ZIP code to see if we service your area', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="step-content">
                        <div class="zip-input-group">
                            <label for="customer_zip_code"><?php _e('ZIP Code', 'mobooking'); ?></label>
                            <div class="input-with-button">
                                <input type="text" 
                                    id="customer_zip_code" 
                                    name="zip_code" 
                                    class="zip-input" 
                                    placeholder="<?php _e('Enter your ZIP code', 'mobooking'); ?>" 
                                    required>
                                <button type="button" class="check-zip-btn">
                                    <span class="btn-text"><?php _e('Check Availability', 'mobooking'); ?></span>
                                    <span class="btn-loading"><?php _e('Checking...', 'mobooking'); ?></span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="zip-result" style="display: none;">
                            <div class="result-message"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Service Selection -->
                <div class="booking-step step-2">
                    <div class="step-header">
                        <h2><?php _e('Choose Your Services', 'mobooking'); ?></h2>
                        <p><?php _e('Select the services you need', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="step-content">
                        <div class="services-grid">
                            <?php foreach ($services as $service) : ?>
                                <div class="service-card" 
                                    data-service-id="<?php echo esc_attr($service->id); ?>"
                                    data-service-price="<?php echo esc_attr($service->price); ?>"
                                    data-service-duration="<?php echo esc_attr($service->duration); ?>">
                                    
                                    <div class="service-header">
                                        <div class="service-visual">
                                            <?php if (!empty($service->image_url)) : ?>
                                                <div class="service-image">
                                                    <img src="<?php echo esc_url($service->image_url); ?>" alt="<?php echo esc_attr($service->name); ?>">
                                                </div>
                                            <?php elseif (!empty($service->icon)) : ?>
                                                <div class="service-icon">
                                                    <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                                </div>
                                            <?php else : ?>
                                                <div class="service-icon service-icon-default">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="service-selector">
                                                <input type="checkbox" 
                                                    id="service_<?php echo esc_attr($service->id); ?>" 
                                                    name="selected_services[]" 
                                                    value="<?php echo esc_attr($service->id); ?>"
                                                    data-has-options="<?php echo !empty($service->options) ? '1' : '0'; ?>">
                                                <label for="service_<?php echo esc_attr($service->id); ?>" class="service-checkbox"></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="service-content">
                                        <h3 class="service-name"><?php echo esc_html($service->name); ?></h3>
                                        
                                        <?php if (!empty($service->description)) : ?>
                                            <p class="service-description"><?php echo esc_html($service->description); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="service-meta">
                                            <div class="service-price">
                                                <span class="price-amount"><?php echo wc_price($service->price); ?></span>
                                            </div>
                                            <div class="service-duration">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12,6 12,12 16,14"></polyline>
                                                </svg>
                                                <span><?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($service->options)) : ?>
                                            <div class="service-options-indicator">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="3"/>
                                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                                </svg>
                                                <?php printf(_n('%d option', '%d options', count($service->options), 'mobooking'), count($service->options)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step"><?php _e('Continue', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 3: Service Options -->
                <div class="booking-step step-3">
                    <div class="step-header">
                        <h2><?php _e('Customize Your Services', 'mobooking'); ?></h2>
                        <p><?php _e('Configure options for your selected services', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="step-content">
                        <div class="service-options-container">
                            <!-- Service options will be dynamically loaded here -->
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step"><?php _e('Continue', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 4: Customer Information -->
                <div class="booking-step step-4">
                    <div class="step-header">
                        <h2><?php _e('Your Information', 'mobooking'); ?></h2>
                        <p><?php _e('Please provide your contact details', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="step-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="customer_name"><?php _e('Full Name', 'mobooking'); ?> *</label>
                                <input type="text" id="customer_name" name="customer_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_email"><?php _e('Email Address', 'mobooking'); ?> *</label>
                                <input type="email" id="customer_email" name="customer_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="customer_phone"><?php _e('Phone Number', 'mobooking'); ?></label>
                                <input type="tel" id="customer_phone" name="customer_phone">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="customer_address"><?php _e('Service Address', 'mobooking'); ?> *</label>
                                <textarea id="customer_address" name="customer_address" rows="3" required placeholder="<?php _e('Enter the full address where service will be provided', 'mobooking'); ?>"></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="service_date"><?php _e('Preferred Date & Time', 'mobooking'); ?> *</label>
                                <input type="datetime-local" id="service_date" name="service_date" required min="<?php echo date('Y-m-d\TH:i', strtotime('+1 day')); ?>">
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="booking_notes"><?php _e('Additional Notes', 'mobooking'); ?></label>
                                <textarea id="booking_notes" name="notes" rows="3" placeholder="<?php _e('Any special instructions or requirements...', 'mobooking'); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step"><?php _e('Review Order', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 5: Review & Confirm -->
                <div class="booking-step step-5">
                    <div class="step-header">
                        <h2><?php _e('Review Your Booking', 'mobooking'); ?></h2>
                        <p><?php _e('Please review your order details before confirming', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="step-content">
                        <div class="booking-summary">
                            <div class="summary-section">
                                <h3><?php _e('Service Details', 'mobooking'); ?></h3>
                                <div class="service-address"></div>
                                <div class="service-datetime"></div>
                            </div>
                            
                            <div class="summary-section">
                                <h3><?php _e('Selected Services', 'mobooking'); ?></h3>
                                <div class="selected-services-list"></div>
                            </div>
                            
                            <div class="summary-section">
                                <h3><?php _e('Customer Information', 'mobooking'); ?></h3>
                                <div class="customer-info"></div>
                            </div>
                            
                            <div class="summary-section discount-section" style="display: none;">
                                <h3><?php _e('Discount Code', 'mobooking'); ?></h3>
                                <div class="discount-input-group">
                                    <input type="text" id="discount_code" name="discount_code" placeholder="<?php _e('Enter discount code', 'mobooking'); ?>">
                                    <button type="button" class="apply-discount-btn"><?php _e('Apply', 'mobooking'); ?></button>
                                </div>
                                <div class="discount-message"></div>
                            </div>
                        </div>
                        
                        <div class="pricing-summary">
                            <div class="pricing-line subtotal">
                                <span class="label"><?php _e('Subtotal', 'mobooking'); ?></span>
                                <span class="amount">$0.00</span>
                            </div>
                            <div class="pricing-line discount" style="display: none;">
                                <span class="label"><?php _e('Discount', 'mobooking'); ?></span>
                                <span class="amount">-$0.00</span>
                            </div>
                            <div class="pricing-line total">
                                <span class="label"><?php _e('Total', 'mobooking'); ?></span>
                                <span class="amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="submit" class="btn-primary confirm-booking-btn">
                            <span class="btn-text"><?php _e('Confirm Booking', 'mobooking'); ?></span>
                            <span class="btn-loading"><?php _e('Processing...', 'mobooking'); ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Success Step -->
                <div class="booking-step step-success">
                    <div class="success-content">
                        <div class="success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <h2><?php _e('Booking Confirmed!', 'mobooking'); ?></h2>
                        <p class="success-message"><?php echo esc_html($settings->booking_confirmation_message); ?></p>
                        <div class="booking-reference">
                            <strong><?php _e('Booking Reference:', 'mobooking'); ?></strong>
                            <span class="reference-number"></span>
                        </div>
                        <div class="next-steps">
                            <p><?php _e('What happens next:', 'mobooking'); ?></p>
                            <ul>
                                <li><?php _e('You will receive a confirmation email shortly', 'mobooking'); ?></li>
                                <li><?php _e('We will contact you to confirm the appointment', 'mobooking'); ?></li>
                                <li><?php _e('Our team will arrive at the scheduled time', 'mobooking'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden fields -->
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="total_price" id="total_price" value="0">
                <input type="hidden" name="discount_amount" id="discount_amount" value="0">
                <input type="hidden" name="service_options_data" id="service_options_data" value="">
                
                <?php wp_nonce_field('mobooking-booking-nonce', 'booking_nonce'); ?>
            </form>
            
            <!-- Service Options Template (Hidden) -->
            <div id="service-options-template" style="display: none;">
                <?php foreach ($services as $service) : ?>
                    <?php if (!empty($service->options)) : ?>
                        <div class="service-options-section" data-service-id="<?php echo esc_attr($service->id); ?>">
                            <h3 class="service-options-title"><?php echo esc_html($service->name); ?></h3>
                            
                            <?php foreach ($service->options as $option) : ?>
                                <div class="option-field" data-option-id="<?php echo esc_attr($option->id); ?>" data-price-type="<?php echo esc_attr($option->price_type); ?>" data-price-impact="<?php echo esc_attr($option->price_impact); ?>">
                                    <label class="option-label">
                                        <?php echo esc_html($option->name); ?>
                                        <?php if ($option->is_required) : ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                        <?php if ($option->price_impact > 0) : ?>
                                            <span class="price-impact">(+<?php echo wc_price($option->price_impact); ?>)</span>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php if (!empty($option->description)) : ?>
                                        <p class="option-description"><?php echo esc_html($option->description); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="option-input">
                                        <?php
                                        $field_name = "option_{$option->id}";
                                        $field_id = "option_{$option->id}_{$service->id}";
                                        
                                        switch ($option->type) :
                                            case 'checkbox':
                                        ?>
                                                <input type="checkbox" 
                                                    id="<?php echo esc_attr($field_id); ?>" 
                                                    name="<?php echo esc_attr($field_name); ?>" 
                                                    value="1"
                                                    <?php echo $option->is_required ? 'data-required="true"' : ''; ?>
                                                    <?php echo $option->default_value == '1' ? 'checked' : ''; ?>>
                                                <label for="<?php echo esc_attr($field_id); ?>" class="checkbox-label">
                                                    <?php echo !empty($option->option_label) ? esc_html($option->option_label) : __('Yes', 'mobooking'); ?>
                                                </label>
                                        <?php
                                                break;
                                            case 'text':
                                        ?>
                                                <input type="text" 
                                                    id="<?php echo esc_attr($field_id); ?>" 
                                                    name="<?php echo esc_attr($field_name); ?>" 
                                                    placeholder="<?php echo esc_attr($option->placeholder); ?>"
                                                    value="<?php echo esc_attr($option->default_value); ?>"
                                                    <?php echo $option->is_required ? 'required' : ''; ?>
                                                    <?php echo $option->min_length ? 'minlength="' . esc_attr($option->min_length) . '"' : ''; ?>
                                                    <?php echo $option->max_length ? 'maxlength="' . esc_attr($option->max_length) . '"' : ''; ?>>
                                        <?php
                                                break;
                                            case 'number':
                                            case 'quantity':
                                        ?>
                                                <input type="number" 
                                                    id="<?php echo esc_attr($field_id); ?>" 
                                                    name="<?php echo esc_attr($field_name); ?>" 
                                                    value="<?php echo esc_attr($option->default_value); ?>"
                                                    <?php echo $option->is_required ? 'required' : ''; ?>
                                                    <?php echo $option->min_value !== null ? 'min="' . esc_attr($option->min_value) . '"' : ''; ?>
                                                    <?php echo $option->max_value !== null ? 'max="' . esc_attr($option->max_value) . '"' : ''; ?>
                                                    step="<?php echo esc_attr($option->step); ?>">
                                        <?php
                                                break;
                                            case 'textarea':
                                        ?>
                                                <textarea id="<?php echo esc_attr($field_id); ?>" 
                                                        name="<?php echo esc_attr($field_name); ?>" 
                                                        rows="<?php echo esc_attr($option->rows); ?>"
                                                        placeholder="<?php echo esc_attr($option->placeholder); ?>"
                                                        <?php echo $option->is_required ? 'required' : ''; ?>
                                                        <?php echo $option->min_length ? 'minlength="' . esc_attr($option->min_length) . '"' : ''; ?>
                                                        <?php echo $option->max_length ? 'maxlength="' . esc_attr($option->max_length) . '"' : ''; ?>><?php echo esc_textarea($option->default_value); ?></textarea>
                                        <?php
                                                break;
                                            case 'select':
                                                $choices = $this->parse_option_choices($option->options);
                                        ?>
                                                <select id="<?php echo esc_attr($field_id); ?>" 
                                                        name="<?php echo esc_attr($field_name); ?>" 
                                                        <?php echo $option->is_required ? 'required' : ''; ?>>
                                                    <?php if (!$option->is_required) : ?>
                                                        <option value=""><?php _e('-- Select --', 'mobooking'); ?></option>
                                                    <?php endif; ?>
                                                    <?php foreach ($choices as $choice) : ?>
                                                        <option value="<?php echo esc_attr($choice['value']); ?>" 
                                                                data-price="<?php echo esc_attr($choice['price']); ?>"
                                                                <?php echo $choice['value'] == $option->default_value ? 'selected' : ''; ?>>
                                                            <?php echo esc_html($choice['label']); ?>
                                                            <?php if ($choice['price'] > 0) : ?>
                                                                (+<?php echo wc_price($choice['price']); ?>)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                        <?php
                                                break;
                                            case 'radio':
                                                $choices = $this->parse_option_choices($option->options);
                                        ?>
                                                <div class="radio-group">
                                                    <?php foreach ($choices as $index => $choice) : ?>
                                                        <label class="radio-label">
                                                            <input type="radio" 
                                                                name="<?php echo esc_attr($field_name); ?>" 
                                                                value="<?php echo esc_attr($choice['value']); ?>"
                                                                data-price="<?php echo esc_attr($choice['price']); ?>"
                                                                <?php echo $choice['value'] == $option->default_value ? 'checked' : ''; ?>
                                                                <?php echo $option->is_required && $index === 0 ? 'required' : ''; ?>>
                                                            <?php echo esc_html($choice['label']); ?>
                                                            <?php if ($choice['price'] > 0) : ?>
                                                                <span class="choice-price">(+<?php echo wc_price($choice['price']); ?>)</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                        <?php
                                                break;
                                        endswitch;
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Parse option choices from string format
     */
    private function parse_option_choices($options_string) {
        if (empty($options_string)) {
            return array();
        }
        
        $choices = array();
        $lines = explode("\n", $options_string);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            $parts = explode('|', $line);
            $value = trim($parts[0]);
            
            if (empty($value)) {
                continue;
            }
            
            $label = $value;
            $price = 0;
            
            if (isset($parts[1])) {
                $label_price = explode(':', $parts[1]);
                $label = trim($label_price[0]);
                if (isset($label_price[1])) {
                    $price = floatval($label_price[1]);
                }
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
     * Booking list shortcode
     */
    public function booking_list_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'status' => '',
            'limit' => 10
        ), $atts);
        
        $user_id = $atts['user_id'] ? absint($atts['user_id']) : get_current_user_id();
        
        // If not logged in and no user ID specified, return nothing
        if (!$user_id) {
            return '';
        }
        
        // Check if current user has permission to view these bookings
        if (!current_user_can('administrator') && get_current_user_id() != $user_id) {
            return '<p>' . __('You do not have permission to view these bookings.', 'mobooking') . '</p>';
        }
        
        // Get bookings
        $args = array(
            'status' => $atts['status'],
            'limit' => $atts['limit']
        );
        
        $bookings = $this->get_user_bookings($user_id, $args);
        
        if (empty($bookings)) {
            return '<p>' . __('No bookings found.', 'mobooking') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="mobooking-booking-list">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'mobooking'); ?></th>
                        <th><?php _e('Customer', 'mobooking'); ?></th>
                        <th><?php _e('Date', 'mobooking'); ?></th>
                        <th><?php _e('Services', 'mobooking'); ?></th>
                        <th><?php _e('Total', 'mobooking'); ?></th>
                        <th><?php _e('Status', 'mobooking'); ?></th>
                        <th><?php _e('Actions', 'mobooking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking) : 
                        $booking_services = json_decode($booking->services, true);
                    ?>
                        <tr class="booking-row status-<?php echo esc_attr($booking->status); ?>">
                            <td class="booking-id"><?php echo esc_html($booking->id); ?></td>
                            <td class="booking-customer">
                                <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                <div class="customer-email"><?php echo esc_html($booking->customer_email); ?></div>
                            </td>
                            <td class="booking-date">
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)); ?>
                            </td>
                            <td class="booking-services">
                                <?php if (is_array($booking_services)) : ?>
                                    <ul>
                                        <?php foreach ($booking_services as $service) : ?>
                                            <li><?php echo esc_html($service['name']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td class="booking-total">
                                <?php echo wc_price($booking->total_price); ?>
                            </td>
                            <td class="booking-status">
                                <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                    <?php 
                                    switch ($booking->status) {
                                        case 'pending':
                                            _e('Pending', 'mobooking');
                                            break;
                                        case 'confirmed':
                                            _e('Confirmed', 'mobooking');
                                            break;
                                        case 'completed':
                                            _e('Completed', 'mobooking');
                                            break;
                                        case 'cancelled':
                                            _e('Cancelled', 'mobooking');
                                            break;
                                        default:
                                            echo esc_html($booking->status);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="booking-actions">
                                <a href="#" class="view-booking" data-id="<?php echo esc_attr($booking->id); ?>"><?php _e('View', 'mobooking'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}