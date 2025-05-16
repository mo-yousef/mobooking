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
        $services_manager = new \MoBooking\Services\Manager();
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
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0
        ), $atts);
        
        // If no user ID specified, return nothing
        if (empty($atts['user_id'])) {
            return '<p>' . __('Please specify a service provider.', 'mobooking') . '</p>';
        }
        
        $user_id = absint($atts['user_id']);
        
        // Get user's services
        $services_manager = new \MoBooking\Services\Manager();
        $services = $services_manager->get_user_services($user_id);
        
        if (empty($services)) {
            return '<p>' . __('No services available for this provider.', 'mobooking') . '</p>';
        }
        
        // Get user settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($user_id);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        ob_start();
        ?>
        <div class="mobooking-booking-form" data-user-id="<?php echo esc_attr($user_id); ?>">
            <form id="mobooking-booking" method="post">
                <!-- Step 1: Check ZIP code -->
                <div class="booking-step step-1 active">
                    <h3><?php _e('Step 1: Check Availability', 'mobooking'); ?></h3>
                    
                    <div class="form-group">
                        <label for="zip_code"><?php _e('Enter your ZIP code', 'mobooking'); ?></label>
                        <input type="text" name="zip_code" id="zip_code" required>
                        <button type="button" class="check-zip-button"><?php _e('Check Availability', 'mobooking'); ?></button>
                    </div>
                    
                    <div class="zip-message"></div>
                </div>
                
                <!-- Step 2: Select services -->
                <div class="booking-step step-2">
                    <h3><?php _e('Step 2: Select Services', 'mobooking'); ?></h3>
                    
                    <div class="services-list">
                        <?php foreach ($services as $service) : ?>
                            <div class="service-item" data-id="<?php echo esc_attr($service->id); ?>" data-price="<?php echo esc_attr($service->price); ?>" data-duration="<?php echo esc_attr($service->duration); ?>">
                                <div class="service-header">
                                    <?php if (!empty($service->icon)) : ?>
                                        <div class="service-icon">
                                            <i class="<?php echo esc_attr($service->icon); ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="service-name"><?php echo esc_html($service->name); ?></div>
                                    <div class="service-price"><?php echo wc_price($service->price); ?></div>
                                </div>
                                
                                <div class="service-description">
                                    <?php echo wpautop(esc_html($service->description)); ?>
                                </div>
                                
                                <div class="service-select">
                                    <label>
                                        <input type="checkbox" name="selected_services[]" value="<?php echo esc_attr($service->id); ?>">
                                        <?php _e('Select this service', 'mobooking'); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="booking-buttons">
                        <button type="button" class="prev-step" data-step="1"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="next-step" data-step="3"><?php _e('Next', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 3: Choose date and time -->
                <div class="booking-step step-3">
                    <h3><?php _e('Step 3: Choose Date and Time', 'mobooking'); ?></h3>
                    
                    <div class="form-group">
                        <label for="service_date"><?php _e('Select Date', 'mobooking'); ?></label>
                        <input type="text" name="service_date" id="service_date" class="datepicker" required>
                    </div>
                    
                    <div class="form-group time-slots" style="display: none;">
                        <label><?php _e('Select Time', 'mobooking'); ?></label>
                        <div class="time-slots-container"></div>
                    </div>
                    
                    <div class="booking-buttons">
                        <button type="button" class="prev-step" data-step="2"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="next-step" data-step="4"><?php _e('Next', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 4: Enter customer details -->
                <div class="booking-step step-4">
                    <h3><?php _e('Step 4: Your Information', 'mobooking'); ?></h3>
                    
                    <div class="form-group">
                        <label for="customer_name"><?php _e('Full Name', 'mobooking'); ?></label>
                        <input type="text" name="customer_name" id="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email"><?php _e('Email', 'mobooking'); ?></label>
                        <input type="email" name="customer_email" id="customer_email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone"><?php _e('Phone', 'mobooking'); ?></label>
                        <input type="tel" name="customer_phone" id="customer_phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_address"><?php _e('Address', 'mobooking'); ?></label>
                        <textarea name="customer_address" id="customer_address" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes"><?php _e('Additional Notes', 'mobooking'); ?></label>
                        <textarea name="notes" id="notes"></textarea>
                    </div>
                    
                    <div class="booking-buttons">
                        <button type="button" class="prev-step" data-step="3"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="next-step" data-step="5"><?php _e('Next', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 5: Review and confirm -->
                <div class="booking-step step-5">
                    <h3><?php _e('Step 5: Review and Confirm', 'mobooking'); ?></h3>
                    
                    <div class="booking-summary">
                        <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                        <div class="selected-services-summary"></div>
                        
                        <h4><?php _e('Date and Time', 'mobooking'); ?></h4>
                        <div class="selected-datetime-summary"></div>
                        
                        <h4><?php _e('Your Information', 'mobooking'); ?></h4>
                        <div class="customer-info-summary"></div>
                    </div>
                    
                    <div class="discount-section">
                        <div class="form-group">
                            <label for="discount_code"><?php _e('Discount Code', 'mobooking'); ?></label>
                            <input type="text" name="discount_code" id="discount_code">
                            <button type="button" class="apply-discount-button"><?php _e('Apply', 'mobooking'); ?></button>
                        </div>
                        <div class="discount-message"></div>
                    </div>
                    
                    <div class="booking-total">
                        <div class="subtotal">
                            <span class="label"><?php _e('Subtotal', 'mobooking'); ?>:</span>
                            <span class="amount"></span>
                        </div>
                        <div class="discount" style="display: none;">
                            <span class="label"><?php _e('Discount', 'mobooking'); ?>:</span>
                            <span class="amount"></span>
                        </div>
                        <div class="total">
                            <span class="label"><?php _e('Total', 'mobooking'); ?>:</span>
                            <span class="amount"></span>
                        </div>
                    </div>
                    
                    <div class="booking-buttons">
                        <button type="button" class="prev-step" data-step="4"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="submit" class="confirm-booking-button"><?php _e('Confirm Booking', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Success message after booking -->
                <div class="booking-step step-success">
                    <div class="success-message">
                        <h3><?php _e('Booking Successful!', 'mobooking'); ?></h3>
                        <p><?php echo esc_html($settings->booking_confirmation_message); ?></p>
                        <div class="booking-reference"></div>
                    </div>
                </div>
                
                <?php wp_nonce_field('mobooking-booking-nonce', 'mobooking_booking_nonce'); ?>
                <input type="hidden" name="action" value="mobooking_save_booking">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="total_price" id="total_price" value="0">
                <input type="hidden" name="discount_amount" id="discount_amount" value="0">
            </form>
        </div>
        <?php
        return ob_get_clean();
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