<?php
namespace MoBooking\Bookings;

/**
 * Bookings Manager - Handles all booking-related functionality
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action('init', array($this, 'register_booking_endpoints'));
        add_filter('query_vars', array($this, 'add_booking_query_vars'));
        
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_nopriv_mobooking_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_mobooking_check_zip_coverage', array($this, 'ajax_check_zip_coverage'));
        add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', array($this, 'ajax_check_zip_coverage'));
        add_action('wp_ajax_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        add_action('wp_ajax_nopriv_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        add_action('wp_ajax_mobooking_get_user_bookings', array($this, 'ajax_get_user_bookings'));
        add_action('wp_ajax_mobooking_update_booking_status', array($this, 'ajax_update_booking_status'));
        
        // Add shortcodes
        add_shortcode('mobooking_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('mobooking_business_owner', array($this, 'business_owner_shortcode'));
    }
    
    /**
     * Register booking endpoints
     */
    public function register_booking_endpoints() {
        // Public booking endpoints if needed
    }
    
    /**
     * Add booking query vars
     */
    public function add_booking_query_vars($vars) {
        $vars[] = 'booking_id';
        return $vars;
    }
    
    /**
     * Get user bookings
     */
    public function get_user_bookings($user_id, $args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $defaults = array(
            'limit' => -1,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $params[] = $args['limit'];
            
            if ($args['offset'] > 0) {
                $sql .= " OFFSET %d";
                $params[] = $args['offset'];
            }
        }
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Count user bookings
     */
    public function count_user_bookings($user_id, $status = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        if (!empty($status)) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = %s",
                $user_id, $status
            ));
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Calculate user revenue
     */
    public function calculate_user_revenue($user_id, $period = 'all') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $sql = "SELECT SUM(total_price) FROM $table_name WHERE user_id = %d AND status IN ('confirmed', 'completed')";
        $params = array($user_id);
        
        switch ($period) {
            case 'today':
                $sql .= " AND DATE(created_at) = CURDATE()";
                break;
            case 'this_week':
                $sql .= " AND YEARWEEK(created_at) = YEARWEEK(NOW())";
                break;
            case 'this_month':
                $sql .= " AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())";
                break;
        }
        
        $result = $wpdb->get_var($wpdb->prepare($sql, $params));
        return $result ? floatval($result) : 0;
    }
    
    /**
     * Get most popular service
     */
    public function get_most_popular_service($user_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        $sql = "SELECT s.*, COUNT(b.id) as booking_count 
                FROM $services_table s
                LEFT JOIN $bookings_table b ON FIND_IN_SET(s.id, REPLACE(REPLACE(b.services, '[', ''), ']', ''))
                WHERE s.user_id = %d 
                GROUP BY s.id 
                ORDER BY booking_count DESC 
                LIMIT 1";
        
        return $wpdb->get_row($wpdb->prepare($sql, $user_id));
    }
    
    /**
     * Save booking
     */
    public function save_booking($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        // Sanitize data
        $booking_data = array(
            'user_id' => absint($data['user_id']),
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'customer_address' => sanitize_textarea_field($data['customer_address']),
            'zip_code' => sanitize_text_field($data['zip_code']),
            'service_date' => sanitize_text_field($data['service_date']),
            'services' => wp_json_encode($data['selected_services']),
            'service_options' => isset($data['service_options_data']) ? wp_json_encode($data['service_options_data']) : '',
            'total_price' => floatval($data['total_price']),
            'discount_code' => isset($data['discount_code']) ? sanitize_text_field($data['discount_code']) : '',
            'discount_amount' => isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0,
            'status' => 'pending',
            'notes' => isset($data['booking_notes']) ? sanitize_textarea_field($data['booking_notes']) : ''
        );
        
        // Insert booking
        $result = $wpdb->insert(
            $table_name,
            $booking_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%f', '%s', '%s')
        );
        
        if ($result) {
            $booking_id = $wpdb->insert_id;
            
            // Send confirmation email
            $this->send_booking_confirmation($booking_id);
            
            return $booking_id;
        }
        
        return false;
    }
    
    /**
     * Update booking status
     */
    public function update_booking_status($booking_id, $status, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $where = array('id' => $booking_id);
        $where_format = array('%d');
        
        if ($user_id) {
            $where['user_id'] = $user_id;
            $where_format[] = '%d';
        }
        
        return $wpdb->update(
            $table_name,
            array('status' => $status),
            $where,
            array('%s'),
            $where_format
        );
    }
    
    /**
     * Get booking by ID
     */
    public function get_booking($booking_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        if ($user_id) {
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
     * Send booking confirmation email
     */
    private function send_booking_confirmation($booking_id) {
        $booking = $this->get_booking($booking_id);
        if (!$booking) return false;
        
        // Get business owner settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking->user_id);
        
        // Prepare email content
        $subject = sprintf(__('Booking Confirmation - %s', 'mobooking'), $settings->company_name);
        
        $message = $settings->email_header;
        $message .= '<h2>' . __('Booking Confirmation', 'mobooking') . '</h2>';
        $message .= '<p>' . sprintf(__('Dear %s,', 'mobooking'), $booking->customer_name) . '</p>';
        $message .= '<p>' . $settings->booking_confirmation_message . '</p>';
        
        $message .= '<h3>' . __('Booking Details', 'mobooking') . '</h3>';
        $message .= '<p><strong>' . __('Booking ID:', 'mobooking') . '</strong> #' . $booking->id . '</p>';
        $message .= '<p><strong>' . __('Service Date:', 'mobooking') . '</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)) . '</p>';
        $message .= '<p><strong>' . __('Address:', 'mobooking') . '</strong> ' . $booking->customer_address . '</p>';
        $message .= '<p><strong>' . __('Total Amount:', 'mobooking') . '</strong> ' . wc_price($booking->total_price) . '</p>';
        
        if (!empty($booking->notes)) {
            $message .= '<p><strong>' . __('Special Instructions:', 'mobooking') . '</strong> ' . $booking->notes . '</p>';
        }
        
        $message .= $settings->email_footer;
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($booking->customer_email, $subject, $message, $headers);
        
        // Also notify business owner
        $business_user = get_userdata($booking->user_id);
        if ($business_user) {
            $business_subject = sprintf(__('New Booking Received - #%d', 'mobooking'), $booking->id);
            $business_message = sprintf(__('You have received a new booking from %s for %s.', 'mobooking'), 
                $booking->customer_name, 
                date_i18n(get_option('date_format'), strtotime($booking->service_date))
            );
            wp_mail($business_user->user_email, $business_subject, $business_message);
        }
        
        return true;
    }
    
    /**
     * AJAX handler to save booking
     */
    public function ajax_save_booking() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Validate required fields
        $required_fields = array('customer_name', 'customer_email', 'customer_address', 'zip_code', 'service_date', 'selected_services', 'total_price', 'user_id');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('Field %s is required.', 'mobooking'), $field));
            }
        }
        
        // Validate email
        if (!is_email($_POST['customer_email'])) {
            wp_send_json_error(__('Invalid email address.', 'mobooking'));
        }
        
        // Process selected services
        $selected_services = array();
        if (isset($_POST['selected_services']) && is_array($_POST['selected_services'])) {
            $selected_services = array_map('absint', $_POST['selected_services']);
        }
        
        if (empty($selected_services)) {
            wp_send_json_error(__('Please select at least one service.', 'mobooking'));
        }
        
        // Prepare booking data
        $booking_data = array(
            'user_id' => absint($_POST['user_id']),
            'customer_name' => $_POST['customer_name'],
            'customer_email' => $_POST['customer_email'],
            'customer_phone' => isset($_POST['customer_phone']) ? $_POST['customer_phone'] : '',
            'customer_address' => $_POST['customer_address'],
            'zip_code' => $_POST['zip_code'],
            'service_date' => $_POST['service_date'],
            'selected_services' => $selected_services,
            'service_options_data' => isset($_POST['service_options_data']) ? $_POST['service_options_data'] : '',
            'total_price' => $_POST['total_price'],
            'discount_code' => isset($_POST['discount_code']) ? $_POST['discount_code'] : '',
            'discount_amount' => isset($_POST['discount_amount']) ? $_POST['discount_amount'] : 0,
            'booking_notes' => isset($_POST['booking_notes']) ? $_POST['booking_notes'] : ''
        );
        
        // Save booking
        $booking_id = $this->save_booking($booking_data);
        
        if ($booking_id) {
            wp_send_json_success(array(
                'id' => $booking_id,
                'message' => __('Booking confirmed successfully!', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to save booking. Please try again.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to check ZIP coverage
     */
    public function ajax_check_zip_coverage() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-zip-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check ZIP code
        if (!isset($_POST['zip_code']) || empty($_POST['zip_code'])) {
            wp_send_json_error(__('ZIP code is required.', 'mobooking'));
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(__('Invalid request.', 'mobooking'));
        }
        
        // Check if ZIP is covered
        $geography_manager = new \MoBooking\Geography\Manager();
        $is_covered = $geography_manager->is_zip_covered($zip_code, $user_id);
        
        if ($is_covered) {
            wp_send_json_success(array(
                'message' => __('Great! We provide services in your area.', 'mobooking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sorry, we don\'t currently service this area.', 'mobooking')
            ));
        }
    }
    
    /**
     * AJAX handler to validate discount
     */
    public function ajax_validate_discount() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discount-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check required fields
        if (!isset($_POST['code']) || !isset($_POST['user_id']) || !isset($_POST['total'])) {
            wp_send_json_error(__('Missing required information.', 'mobooking'));
        }
        
        $code = sanitize_text_field($_POST['code']);
        $user_id = absint($_POST['user_id']);
        $total = floatval($_POST['total']);
        
        // Validate discount code
        $discounts_manager = new \MoBooking\Discounts\Manager();
        $discount = $discounts_manager->validate_discount_code($code, $user_id);
        
        if (!$discount) {
            wp_send_json_error(array(
                'message' => __('Invalid or expired discount code.', 'mobooking')
            ));
        }
        
        // Check usage limit
        if ($discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit) {
            wp_send_json_error(array(
                'message' => __('This discount code has reached its usage limit.', 'mobooking')
            ));
        }
        
        // Calculate discount amount
        $discount_amount = 0;
        if ($discount->type === 'percentage') {
            $discount_amount = ($total * $discount->amount) / 100;
        } else {
            $discount_amount = min($discount->amount, $total);
        }
        
        wp_send_json_success(array(
            'discount_amount' => $discount_amount,
            'message' => sprintf(__('Discount applied! You save %s', 'mobooking'), wc_price($discount_amount))
        ));
    }
    
    /**
     * AJAX handler to get user bookings
     */
    public function ajax_get_user_bookings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'status' => $status
        );
        
        $bookings = $this->get_user_bookings($user_id, $args);
        $total_bookings = $this->count_user_bookings($user_id, $status);
        
        wp_send_json_success(array(
            'bookings' => $bookings,
            'total' => $total_bookings,
            'pages' => ceil($total_bookings / $per_page)
        ));
    }
    
    /**
     * AJAX handler to update booking status
     */
    public function ajax_update_booking_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$booking_id || !$status) {
            wp_send_json_error(__('Missing required information.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $result = $this->update_booking_status($booking_id, $status, $user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Booking status updated successfully.', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to update booking status.', 'mobooking'));
        }
    }
    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'title' => __('Book Our Services', 'mobooking'),
            'show_header' => true
        ), $atts);
        
        $user_id = absint($atts['user_id']);
        
        if (!$user_id) {
            return '<p>' . __('Invalid booking form configuration.', 'mobooking') . '</p>';
        }
        
        // Check if user exists and is a business owner
        $user = get_userdata($user_id);
        if (!$user || !in_array('mobooking_business_owner', $user->roles)) {
            return '<p>' . __('Invalid business owner.', 'mobooking') . '</p>';
        }
        
        // Get user's services and areas
        $services_manager = new \MoBooking\Services\ServicesManager();
        $services = $services_manager->get_user_services($user_id);
        
        $geography_manager = new \MoBooking\Geography\Manager();
        $areas = $geography_manager->get_user_areas($user_id);
        
        if (empty($services)) {
            return '<p>' . __('No services available for booking at this time.', 'mobooking') . '</p>';
        }
        
        if (empty($areas)) {
            return '<p>' . __('Service areas not configured yet.', 'mobooking') . '</p>';
        }
        
        // Enqueue booking form assets
        wp_enqueue_style('mobooking-booking-form', MOBOOKING_URL . '/assets/css/booking-form.css', array(), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-booking-form', MOBOOKING_URL . '/assets/js/booking-form.js', array('jquery'), MOBOOKING_VERSION, true);
        
        // Localize script
        wp_localize_script('mobooking-booking-form', 'mobookingBooking', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'userId' => $user_id,
            'nonces' => array(
                'booking' => wp_create_nonce('mobooking-booking-nonce'),
                'zip_check' => wp_create_nonce('mobooking-zip-nonce'),
                'discount' => wp_create_nonce('mobooking-discount-nonce')
            ),
            'strings' => array(
                'error' => __('An error occurred', 'mobooking'),
                'selectService' => __('Please select at least one service', 'mobooking'),
                'fillRequired' => __('Please fill in all required fields', 'mobooking'),
                'invalidEmail' => __('Please enter a valid email address', 'mobooking'),
                'bookingSuccess' => __('Booking confirmed successfully!', 'mobooking')
            ),
            'currency' => array(
                'symbol' => get_woocommerce_currency_symbol(),
                'position' => get_option('woocommerce_currency_pos', 'left')
            )
        ));
        
        // Generate booking form HTML
        return $this->render_booking_form($user_id, $services, $atts);
    }
    
    /**
     * Render booking form HTML
     */
    private function render_booking_form($user_id, $services, $atts) {
        $options_manager = new \MoBooking\Services\ServiceOptionsManager();
        
        ob_start();
        ?>
        <div class="mobooking-booking-form-container">
            <!-- Progress Indicator -->
            <div class="booking-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 20%;"></div>
                </div>
                <div class="progress-steps">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-label"><?php _e('Location', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-label"><?php _e('Services', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-label"><?php _e('Options', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-label"><?php _e('Details', 'mobooking'); ?></div>
                    </div>
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-label"><?php _e('Review', 'mobooking'); ?></div>
                    </div>
                </div>
            </div>
            
            <form id="mobooking-booking-form" class="booking-form">
                <!-- Hidden fields -->
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="total_price" id="total_price" value="0">
                <input type="hidden" name="discount_amount" id="discount_amount" value="0">
                <input type="hidden" name="service_options_data" id="service_options_data" value="">
                <?php wp_nonce_field('mobooking-booking-nonce', 'nonce'); ?>
                
                <!-- Step 1: ZIP Code -->
                <div class="booking-step step-1 active">
                    <div class="step-header">
                        <h2><?php _e('Check Service Availability', 'mobooking'); ?></h2>
                        <p><?php _e('Enter your ZIP code to see if we service your area', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="zip-input-group">
                        <label for="customer_zip_code"><?php _e('ZIP Code', 'mobooking'); ?></label>
                        <div class="input-with-button">
                            <input type="text" id="customer_zip_code" name="zip_code" class="zip-input" 
                                   placeholder="<?php _e('Enter ZIP code', 'mobooking'); ?>" required>
                            <button type="button" class="check-zip-btn">
                                <span class="btn-text"><?php _e('Check Availability', 'mobooking'); ?></span>
                                <span class="btn-loading"><?php _e('Checking...', 'mobooking'); ?></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="zip-result"></div>
                </div>
                
                <!-- Step 2: Services -->
                <div class="booking-step step-2">
                    <div class="step-header">
                        <h2><?php _e('Select Services', 'mobooking'); ?></h2>
                        <p><?php _e('Choose the services you need', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="services-grid">
                        <?php foreach ($services as $service) : 
                            $service_options = $options_manager->get_service_options($service->id);
                            $has_options = !empty($service_options);
                        ?>
                            <div class="service-card" data-service-id="<?php echo esc_attr($service->id); ?>" data-service-price="<?php echo esc_attr($service->price); ?>">
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
                                    </div>
                                    
                                    <div class="service-selector">
                                        <input type="checkbox" name="selected_services[]" value="<?php echo esc_attr($service->id); ?>" 
                                               id="service_<?php echo esc_attr($service->id); ?>" 
                                               data-has-options="<?php echo $has_options ? 1 : 0; ?>">
                                        <label for="service_<?php echo esc_attr($service->id); ?>" class="service-checkbox"></label>
                                    </div>
                                </div>
                                
                                <div class="service-content">
                                    <h3><?php echo esc_html($service->name); ?></h3>
                                    
                                    <?php if (!empty($service->description)) : ?>
                                        <p class="service-description"><?php echo esc_html($service->description); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="service-meta">
                                        <div class="service-price"><?php echo wc_price($service->price); ?></div>
                                        <div class="service-duration">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12,6 12,12 16,14"></polyline>
                                            </svg>
                                            <?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($has_options) : ?>
                                        <div class="service-options-indicator">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="3"/>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                            </svg>
                                            <?php _e('Customizable options available', 'mobooking'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                        <p><?php _e('Configure your selected services', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="service-options-container">
                        <!-- Service options will be loaded dynamically -->
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
                        
                        <div class="form-group">
                            <label for="service_date"><?php _e('Preferred Date & Time', 'mobooking'); ?> *</label>
                            <input type="datetime-local" id="service_date" name="service_date" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="customer_address"><?php _e('Service Address', 'mobooking'); ?> *</label>
                            <textarea id="customer_address" name="customer_address" rows="3" required 
                                      placeholder="<?php _e('Enter the full address where service will be provided', 'mobooking'); ?>"></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="booking_notes"><?php _e('Special Instructions', 'mobooking'); ?></label>
                            <textarea id="booking_notes" name="booking_notes" rows="3" 
                                      placeholder="<?php _e('Any special instructions or requests...', 'mobooking'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="button" class="btn-primary next-step"><?php _e('Review Booking', 'mobooking'); ?></button>
                    </div>
                </div>
                
                <!-- Step 5: Review & Confirm -->
                <div class="booking-step step-5">
                    <div class="step-header">
                        <h2><?php _e('Review Your Booking', 'mobooking'); ?></h2>
                        <p><?php _e('Please review your booking details before confirming', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="booking-summary">
                        <div class="summary-section">
                            <h3><?php _e('Selected Services', 'mobooking'); ?></h3>
                            <div class="selected-services-list">
                                <!-- Services will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><?php _e('Service Details', 'mobooking'); ?></h3>
                            <div class="service-address"></div>
                            <div class="service-datetime"></div>
                        </div>
                        
                        <div class="summary-section">
                            <h3><?php _e('Contact Information', 'mobooking'); ?></h3>
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
                        
                        <div class="summary-section">
                            <h3><?php _e('Pricing', 'mobooking'); ?></h3>
                            <div class="pricing-summary">
                                <div class="pricing-line">
                                    <span class="label"><?php _e('Subtotal', 'mobooking'); ?></span>
                                    <span class="amount subtotal">$0.00</span>
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
                    </div>
                    
                    <div class="step-actions">
                        <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                        <button type="submit" class="btn-primary confirm-booking-btn">
                            <span class="btn-text"><?php _e('Confirm Booking', 'mobooking'); ?></span>
                            <span class="btn-loading"><?php _e('Processing...', 'mobooking'); ?></span>
                        </button>
                    </div>
                </div>
                
                <!-- Step 6: Success -->
                <div class="booking-step step-6 step-success">
                    <div class="success-content">
                        <div class="success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        
                        <h2><?php _e('Booking Confirmed!', 'mobooking'); ?></h2>
                        <p class="success-message"><?php _e('Thank you for your booking. We\'ll contact you shortly to confirm the details.', 'mobooking'); ?></p>
                        
                        <div class="booking-reference">
                            <strong><?php _e('Your booking reference:', 'mobooking'); ?></strong>
                            <span class="reference-number">#0000</span>
                        </div>
                        
                        <div class="next-steps">
                            <p><?php _e('What happens next?', 'mobooking'); ?></p>
                            <ul>
                                <li><?php _e('You\'ll receive a confirmation email shortly', 'mobooking'); ?></li>
                                <li><?php _e('We\'ll contact you to confirm the appointment details', 'mobooking'); ?></li>
                                <li><?php _e('Our team will arrive at the scheduled time', 'mobooking'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Service Options Template (Hidden) -->
            <div id="service-options-template" style="display: none;">
                <!-- Service options templates will be populated here -->
                <?php foreach ($services as $service) :
                    $service_options = $options_manager->get_service_options($service->id);
                    if (!empty($service_options)) :
                ?>
                    <div class="service-options-section" data-service-id="<?php echo esc_attr($service->id); ?>">
                        <h3 class="service-options-title"><?php echo esc_html($service->name); ?> - <?php _e('Options', 'mobooking'); ?></h3>
                        
                        <?php foreach ($service_options as $option) : ?>
                            <div class="option-field" data-option-id="<?php echo esc_attr($option->id); ?>" 
                                 data-price-type="<?php echo esc_attr($option->price_type); ?>" 
                                 data-price-impact="<?php echo esc_attr($option->price_impact); ?>">
                                
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
                                    // Render different input types based on option type
                                    switch ($option->type) {
                                        case 'checkbox':
                                            $label = !empty($option->option_label) ? $option->option_label : $option->name;
                                            $checked = $option->default_value == '1' ? 'checked' : '';
                                            echo '<label class="checkbox-label">';
                                            echo '<input type="checkbox" name="option_' . $option->id . '" value="1" ' . $checked . ($option->is_required ? ' data-required="true"' : '') . '>';
                                            echo '<span class="checkbox-text">' . esc_html($label) . '</span>';
                                            echo '</label>';
                                            break;
                                        
                                        case 'text':
                                            $placeholder = !empty($option->placeholder) ? $option->placeholder : '';
                                            $default = !empty($option->default_value) ? $option->default_value : '';
                                            $minlength = $option->min_length ? ' minlength="' . $option->min_length . '"' : '';
                                            $maxlength = $option->max_length ? ' maxlength="' . $option->max_length . '"' : '';
                                            echo '<input type="text" name="option_' . $option->id . '" value="' . esc_attr($default) . '" placeholder="' . esc_attr($placeholder) . '"' . $minlength . $maxlength . ($option->is_required ? ' required data-required="true"' : '') . '>';
                                            break;
                                        
                                        case 'number':
                                        case 'quantity':
                                            $min = $option->min_value !== null ? ' min="' . $option->min_value . '"' : '';
                                            $max = $option->max_value !== null ? ' max="' . $option->max_value . '"' : '';
                                            $step = !empty($option->step) ? ' step="' . $option->step . '"' : ' step="1"';
                                            $default = !empty($option->default_value) ? $option->default_value : '';
                                            echo '<input type="number" name="option_' . $option->id . '" value="' . esc_attr($default) . '"' . $min . $max . $step . ($option->is_required ? ' required data-required="true"' : '') . '>';
                                            break;
                                        
                                        case 'textarea':
                                            $placeholder = !empty($option->placeholder) ? $option->placeholder : '';
                                            $default = !empty($option->default_value) ? $option->default_value : '';
                                            $rows = !empty($option->rows) ? $option->rows : 3;
                                            echo '<textarea name="option_' . $option->id . '" rows="' . $rows . '" placeholder="' . esc_attr($placeholder) . '"' . ($option->is_required ? ' required data-required="true"' : '') . '>' . esc_textarea($default) . '</textarea>';
                                            break;
                                        
                                        case 'select':
                                            echo '<select name="option_' . $option->id . '"' . ($option->is_required ? ' required data-required="true"' : '') . '>';
                                            if (!$option->is_required) {
                                                echo '<option value="">' . __('Select an option', 'mobooking') . '</option>';
                                            }
                                            
                                            if (!empty($option->options)) {
                                                $choices = explode("\n", trim($option->options));
                                                foreach ($choices as $choice) {
                                                    $choice = trim($choice);
                                                    if (empty($choice)) continue;
                                                    
                                                    $parts = explode('|', $choice);
                                                    $value = trim($parts[0]);
                                                    $label = isset($parts[1]) ? trim(explode(':', $parts[1])[0]) : $value;
                                                    $price = 0;
                                                    
                                                    if (isset($parts[1]) && strpos($parts[1], ':') !== false) {
                                                        $price_part = explode(':', $parts[1]);
                                                        $price = isset($price_part[1]) ? floatval($price_part[1]) : 0;
                                                    }
                                                    
                                                    echo '<option value="' . esc_attr($value) . '" data-price="' . $price . '">' . esc_html($label);
                                                    if ($price > 0) {
                                                        echo ' (+' . wc_price($price) . ')';
                                                    }
                                                    echo '</option>';
                                                }
                                            }
                                            echo '</select>';
                                            break;
                                        
                                        case 'radio':
                                            echo '<div class="radio-group">';
                                            if (!empty($option->options)) {
                                                $choices = explode("\n", trim($option->options));
                                                foreach ($choices as $i => $choice) {
                                                    $choice = trim($choice);
                                                    if (empty($choice)) continue;
                                                    
                                                    $parts = explode('|', $choice);
                                                    $value = trim($parts[0]);
                                                    $label = isset($parts[1]) ? trim(explode(':', $parts[1])[0]) : $value;
                                                    $price = 0;
                                                    
                                                    if (isset($parts[1]) && strpos($parts[1], ':') !== false) {
                                                        $price_part = explode(':', $parts[1]);
                                                        $price = isset($price_part[1]) ? floatval($price_part[1]) : 0;
                                                    }
                                                    
                                                    echo '<label class="radio-label">';
                                                    echo '<input type="radio" name="option_' . $option->id . '" value="' . esc_attr($value) . '" data-price="' . $price . '"' . ($option->is_required && $i === 0 ? ' required data-required="true"' : '') . '>';
                                                    echo '<span class="radio-text">' . esc_html($label);
                                                    if ($price > 0) {
                                                        echo ' <span class="choice-price">(+' . wc_price($price) . ')</span>';
                                                    }
                                                    echo '</span>';
                                                    echo '</label>';
                                                }
                                            }
                                            echo '</div>';
                                            break;
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Business owner shortcode (for dashboard bookings section)
     */
    public function business_owner_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'view' => 'list'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view bookings.', 'mobooking') . '</p>';
        }
        
        $user = wp_get_current_user();
        if (!in_array('mobooking_business_owner', $user->roles) && !current_user_can('administrator')) {
            return '<p>' . __('You do not have permission to view bookings.', 'mobooking') . '</p>';
        }
        
        $user_id = get_current_user_id();
        
        // Get bookings
        $bookings = $this->get_user_bookings($user_id, array('limit' => 20));
        $total_bookings = $this->count_user_bookings($user_id);
        $pending_bookings = $this->count_user_bookings($user_id, 'pending');
        $confirmed_bookings = $this->count_user_bookings($user_id, 'confirmed');
        $completed_bookings = $this->count_user_bookings($user_id, 'completed');
        
        ob_start();
        ?>
        <div class="bookings-management">
            <!-- Bookings Stats -->
            <div class="bookings-stats">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $total_bookings; ?></div>
                            <div class="stat-label"><?php _e('Total Bookings', 'mobooking'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $pending_bookings; ?></div>
                            <div class="stat-label"><?php _e('Pending', 'mobooking'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card confirmed">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $confirmed_bookings; ?></div>
                            <div class="stat-label"><?php _e('Confirmed', 'mobooking'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card completed">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-appearance"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $completed_bookings; ?></div>
                            <div class="stat-label"><?php _e('Completed', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bookings Filter -->
            <div class="bookings-filter">
                <select id="booking-status-filter">
                    <option value=""><?php _e('All Bookings', 'mobooking'); ?></option>
                    <option value="pending"><?php _e('Pending', 'mobooking'); ?></option>
                    <option value="confirmed"><?php _e('Confirmed', 'mobooking'); ?></option>
                    <option value="completed"><?php _e('Completed', 'mobooking'); ?></option>
                    <option value="cancelled"><?php _e('Cancelled', 'mobooking'); ?></option>
                </select>
            </div>
            
            <!-- Bookings List -->
            <div class="bookings-list">
                <?php if (empty($bookings)) : ?>
                    <div class="no-bookings">
                        <div class="no-bookings-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <h3><?php _e('No bookings yet', 'mobooking'); ?></h3>
                        <p><?php _e('Your bookings will appear here once customers start booking your services.', 'mobooking'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="bookings-table-container">
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Booking ID', 'mobooking'); ?></th>
                                    <th><?php _e('Customer', 'mobooking'); ?></th>
                                    <th><?php _e('Service Date', 'mobooking'); ?></th>
                                    <th><?php _e('Services', 'mobooking'); ?></th>
                                    <th><?php _e('Total', 'mobooking'); ?></th>
                                    <th><?php _e('Status', 'mobooking'); ?></th>
                                    <th><?php _e('Actions', 'mobooking'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking) : 
                                    $services_data = json_decode($booking->services, true);
                                    $services_names = array();
                                    
                                    if (is_array($services_data)) {
                                        $services_manager = new \MoBooking\Services\ServicesManager();
                                        foreach ($services_data as $service_id) {
                                            $service = $services_manager->get_service($service_id);
                                            if ($service) {
                                                $services_names[] = $service->name;
                                            }
                                        }
                                    }
                                ?>
                                    <tr class="booking-row status-<?php echo esc_attr($booking->status); ?>">
                                        <td class="booking-id">#<?php echo $booking->id; ?></td>
                                        <td class="booking-customer">
                                            <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                            <div class="customer-email"><?php echo esc_html($booking->customer_email); ?></div>
                                            <?php if (!empty($booking->customer_phone)) : ?>
                                                <div class="customer-phone"><?php echo esc_html($booking->customer_phone); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="booking-date">
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)); ?>
                                        </td>
                                        <td class="booking-services">
                                            <?php echo implode(', ', array_map('esc_html', $services_names)); ?>
                                        </td>
                                        <td class="booking-total"><?php echo wc_price($booking->total_price); ?></td>
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
                                            <div class="action-buttons">
                                                <?php if ($booking->status === 'pending') : ?>
                                                    <button type="button" class="btn-small btn-success confirm-booking-btn" 
                                                            data-booking-id="<?php echo $booking->id; ?>" 
                                                            title="<?php _e('Confirm Booking', 'mobooking'); ?>">
                                                        <span class="dashicons dashicons-yes-alt"></span>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking->status === 'confirmed') : ?>
                                                    <button type="button" class="btn-small btn-primary complete-booking-btn" 
                                                            data-booking-id="<?php echo $booking->id; ?>" 
                                                            title="<?php _e('Mark as Completed', 'mobooking'); ?>">
                                                        <span class="dashicons dashicons-admin-appearance"></span>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn-small btn-secondary view-booking-btn" 
                                                        data-booking-id="<?php echo $booking->id; ?>" 
                                                        title="<?php _e('View Details', 'mobooking'); ?>">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </button>
                                                
                                                <?php if (in_array($booking->status, ['pending', 'confirmed'])) : ?>
                                                    <button type="button" class="btn-small btn-danger cancel-booking-btn" 
                                                            data-booking-id="<?php echo $booking->id; ?>" 
                                                            title="<?php _e('Cancel Booking', 'mobooking'); ?>">
                                                        <span class="dashicons dashicons-dismiss"></span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Booking status updates
            $('.confirm-booking-btn, .complete-booking-btn, .cancel-booking-btn').on('click', function() {
                const bookingId = $(this).data('booking-id');
                let status = '';
                
                if ($(this).hasClass('confirm-booking-btn')) {
                    status = 'confirmed';
                } else if ($(this).hasClass('complete-booking-btn')) {
                    status = 'completed';
                } else if ($(this).hasClass('cancel-booking-btn')) {
                    status = 'cancelled';
                }
                
                if (status && confirm('<?php _e('Are you sure you want to update this booking status?', 'mobooking'); ?>')) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'mobooking_update_booking_status',
                            booking_id: bookingId,
                            status: status,
                            nonce: '<?php echo wp_create_nonce('mobooking-booking-nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data || '<?php _e('Error updating booking status', 'mobooking'); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e('Error updating booking status', 'mobooking'); ?>');
                        }
                    });
                }
            });
            
            // Filter bookings
            $('#booking-status-filter').on('change', function() {
                const status = $(this).val();
                
                $('.booking-row').each(function() {
                    if (!status || $(this).hasClass('status-' + status)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}