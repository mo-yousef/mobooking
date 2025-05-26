<?php
namespace MoBooking\Bookings;

/**
 * Enhanced Bookings Manager with Auto-Progression Support
 * Properly handles service options and auto-step advancement
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action('init', array($this, 'register_booking_endpoints'));
        add_filter('query_vars', array($this, 'add_booking_query_vars'));
        
        // Register AJAX handlers with enhanced error handling
        add_action('wp_ajax_mobooking_save_booking', array($this, 'ajax_save_booking'));
        add_action('wp_ajax_nopriv_mobooking_save_booking', array($this, 'ajax_save_booking'));
        
        add_action('wp_ajax_mobooking_check_zip_coverage', array($this, 'ajax_check_zip_coverage'));
        add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', array($this, 'ajax_check_zip_coverage'));
        
        // FIXED: Add proper service options AJAX handler
        add_action('wp_ajax_mobooking_get_service_options', array($this, 'ajax_get_service_options'));
        add_action('wp_ajax_nopriv_mobooking_get_service_options', array($this, 'ajax_get_service_options'));
        
        add_action('wp_ajax_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        add_action('wp_ajax_nopriv_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        
        add_action('wp_ajax_mobooking_get_user_bookings', array($this, 'ajax_get_user_bookings'));
        add_action('wp_ajax_mobooking_update_booking_status', array($this, 'ajax_update_booking_status'));
        
        // Add shortcodes
        add_shortcode('mobooking_booking_form', array($this, 'booking_form_shortcode'));
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking\Bookings\Manager: Enhanced constructor with auto-progression support');
        }
    }
    
    /**
     * AJAX handler to get service options - FIXED for auto-progression
     */
    public function ajax_get_service_options() {
        try {
            // Enhanced debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: ajax_get_service_options called');
                error_log('POST data: ' . print_r($_POST, true));
            }

            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
                return;
            }
            
            // Check service ID parameter
            if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
                wp_send_json_error(array('message' => __('Service ID is required.', 'mobooking')));
                return;
            }
            
            $service_id = absint($_POST['service_id']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Getting options for service ID: {$service_id}");
            }
            
            // Check if ServiceOptionsManager exists
            if (!class_exists('\MoBooking\Services\ServiceOptionsManager')) {
                wp_send_json_error(array('message' => __('Service options system not available.', 'mobooking')));
                return;
            }
            
            // Get service options
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            $options = $options_manager->get_service_options($service_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Found " . count($options) . " options for service {$service_id}");
            }
            
            // Format options for frontend consumption
            $formatted_options = array();
            foreach ($options as $option) {
                $formatted_options[] = array(
                    'id' => $option->id,
                    'name' => $option->name,
                    'description' => $option->description,
                    'type' => $option->type,
                    'is_required' => intval($option->is_required),
                    'default_value' => $option->default_value,
                    'placeholder' => $option->placeholder,
                    'min_value' => $option->min_value,
                    'max_value' => $option->max_value,
                    'price_impact' => floatval($option->price_impact),
                    'price_type' => $option->price_type,
                    'options' => $option->options, // For select/radio choices
                    'option_label' => $option->option_label,
                    'step' => $option->step,
                    'unit' => $option->unit,
                    'min_length' => $option->min_length,
                    'max_length' => $option->max_length,
                    'rows' => $option->rows,
                    'display_order' => intval($option->display_order)
                );
            }
            
            wp_send_json_success(array(
                'options' => $formatted_options,
                'service_id' => $service_id
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_get_service_options: ' . $e->getMessage());
            }
            wp_send_json_error(array(
                'message' => __('Error loading service options.', 'mobooking')
            ));
        }
    }
    
    /**
     * AJAX handler to check ZIP coverage - ENHANCED for auto-progression
     */
    public function ajax_check_zip_coverage() {
        try {
            // Enhanced debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking ZIP Coverage AJAX Handler Called');
                error_log('POST Data: ' . print_r($_POST, true));
            }

            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            // Check parameters
            if (!isset($_POST['zip_code']) || !isset($_POST['user_id'])) {
                wp_send_json_error(array(
                    'message' => __('Missing required information.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            $zip_code = sanitize_text_field(trim($_POST['zip_code']));
            $user_id = absint($_POST['user_id']);
            
            // Validate ZIP code format
            if (!preg_match('/^\d{5}(-\d{4})?$/', $zip_code)) {
                wp_send_json_error(array(
                    'message' => __('Please enter a valid ZIP code format.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            // Verify user exists and is a business owner
            $user = get_userdata($user_id);
            if (!$user || (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles))) {
                wp_send_json_error(array(
                    'message' => __('Invalid business account.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            // Check coverage using Geography Manager
            if (!class_exists('\MoBooking\Geography\Manager')) {
                wp_send_json_error(array(
                    'message' => __('Service temporarily unavailable.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            $geography_manager = new \MoBooking\Geography\Manager();
            $is_covered = $geography_manager->is_zip_covered($zip_code, $user_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: ZIP coverage result for {$zip_code} (User {$user_id}): " . ($is_covered ? 'COVERED' : 'NOT COVERED'));
            }
            
            if ($is_covered) {
                wp_send_json_success(array(
                    'message' => __('Great! We provide services in your area.', 'mobooking'),
                    'zip_code' => $zip_code,
                    'covered' => true,
                    'auto_advance' => true // Enable auto-advance
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Sorry, we don\'t currently service this area. Please contact us for more information.', 'mobooking'),
                    'zip_code' => $zip_code,
                    'covered' => false,
                    'auto_advance' => false
                ));
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking ZIP Coverage Exception: ' . $e->getMessage());
            }
            
            wp_send_json_error(array(
                'message' => __('An error occurred while checking service availability.', 'mobooking'),
                'auto_advance' => false
            ));
        }
    }
    
    /**
     * AJAX handler to save booking - ENHANCED for auto-progression
     */
    public function ajax_save_booking() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            // Validate required fields
            $required_fields = array('customer_name', 'customer_email', 'customer_address', 'zip_code', 'service_date', 'selected_services', 'total_price', 'user_id');
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    wp_send_json_error(sprintf(__('Field %s is required.', 'mobooking'), $field));
                    return;
                }
            }
            
            // Validate email
            if (!is_email($_POST['customer_email'])) {
                wp_send_json_error(__('Invalid email address.', 'mobooking'));
                return;
            }
            
            // Process selected services
            $selected_services = array();
            if (isset($_POST['selected_services']) && is_array($_POST['selected_services'])) {
                $selected_services = array_map('absint', $_POST['selected_services']);
            }
            
            if (empty($selected_services)) {
                wp_send_json_error(__('Please select at least one service.', 'mobooking'));
                return;
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
                    'message' => __('Booking confirmed successfully!', 'mobooking'),
                    'auto_advance' => true // Enable auto-advance to success step
                ));
            } else {
                wp_send_json_error(__('Failed to save booking. Please try again.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking Save Booking Exception: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while saving your booking.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to validate discount - ENHANCED
     */
    public function ajax_validate_discount() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
                return;
            }
            
            // Check required fields
            if (!isset($_POST['code']) || !isset($_POST['user_id']) || !isset($_POST['total'])) {
                wp_send_json_error(array('message' => __('Missing required information.', 'mobooking')));
                return;
            }
            
            $code = sanitize_text_field($_POST['code']);
            $user_id = absint($_POST['user_id']);
            $total = floatval($_POST['total']);
            
            // Check if Discounts Manager exists
            if (!class_exists('\MoBooking\Discounts\Manager')) {
                wp_send_json_error(array('message' => __('Discount system not available.', 'mobooking')));
                return;
            }
            
            // Validate discount code
            $discounts_manager = new \MoBooking\Discounts\Manager();
            $discount = $discounts_manager->validate_discount_code($code, $user_id);
            
            if (!$discount) {
                wp_send_json_error(array(
                    'message' => __('Invalid or expired discount code.', 'mobooking')
                ));
                return;
            }
            
            // Check usage limit
            if ($discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit) {
                wp_send_json_error(array(
                    'message' => __('This discount code has reached its usage limit.', 'mobooking')
                ));
                return;
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
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking Discount Validation Exception: ' . $e->getMessage());
            }
            
            wp_send_json_error(array(
                'message' => __('Error processing discount code.', 'mobooking')
            ));
        }
    }
    
    /**
     * Save booking - ENHANCED with better service options handling
     */
    public function save_booking($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        // Process service options data
        $service_options_json = '';
        if (!empty($data['service_options_data'])) {
            if (is_string($data['service_options_data'])) {
                $service_options_json = $data['service_options_data'];
            } else {
                $service_options_json = wp_json_encode($data['service_options_data']);
            }
        }
        
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
            'service_options' => $service_options_json,
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
     * Get user bookings with enhanced filtering
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
            'date_from' => '',
            'date_to' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $sql = "SELECT * FROM $table_name WHERE user_id = %d";
        $params = array($user_id);
        
        if (!empty($args['status'])) {
            $sql .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        if (!empty($args['date_from'])) {
            $sql .= " AND service_date >= %s";
            $params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $sql .= " AND service_date <= %s";
            $params[] = $args['date_to'];
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
     * Booking form shortcode - ENHANCED with auto-progression support
     */
    public function booking_form_shortcode($atts) {
        try {
            // Parse attributes
            $atts = shortcode_atts(array(
                'user_id' => 0,
                'title' => __('Book Our Services', 'mobooking'),
                'show_header' => true
            ), $atts);
            
            $user_id = absint($atts['user_id']);
            
            if (!$user_id) {
                return '<p class="mobooking-error">' . __('Invalid booking form configuration.', 'mobooking') . '</p>';
            }
            
            // Check if user exists and is a business owner
            $user = get_userdata($user_id);
            if (!$user || !in_array('mobooking_business_owner', $user->roles)) {
                return '<p class="mobooking-error">' . __('Invalid business owner.', 'mobooking') . '</p>';
            }
            
            // Check if required classes exist
            if (!class_exists('\MoBooking\Services\ServicesManager')) {
                return '<p class="mobooking-error">' . __('Services manager not available.', 'mobooking') . '</p>';
            }
            
            if (!class_exists('\MoBooking\Geography\Manager')) {
                return '<p class="mobooking-error">' . __('Geography manager not available.', 'mobooking') . '</p>';
            }
            
            // Get user's services and areas
            $services_manager = new \MoBooking\Services\ServicesManager();
            $services = $services_manager->get_user_services($user_id);
            
            $geography_manager = new \MoBooking\Geography\Manager();
            $areas = $geography_manager->get_user_areas($user_id);
            
            if (empty($services)) {
                return '<p class="mobooking-notice">' . __('No services available for booking at this time.', 'mobooking') . '</p>';
            }
            
            if (empty($areas)) {
                return '<p class="mobooking-notice">' . __('Service areas not configured yet.', 'mobooking') . '</p>';
            }
            
            // Enqueue booking form assets
            wp_enqueue_style('mobooking-booking-form', MOBOOKING_URL . '/assets/css/booking-form.css', array(), MOBOOKING_VERSION);
            wp_enqueue_script('mobooking-booking-form', MOBOOKING_URL . '/assets/js/booking-form.js', array('jquery'), MOBOOKING_VERSION, true);
            
            // Enhanced localization with auto-progression support
            $localize_data = array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'userId' => strval($user_id),
                'nonces' => array(
                    'booking' => wp_create_nonce('mobooking-booking-nonce'),
                ),
                'strings' => array(
                    'error' => __('An error occurred', 'mobooking'),
                    'selectService' => __('Please select at least one service', 'mobooking'),
                    'fillRequired' => __('Please fill in all required fields', 'mobooking'),
                    'invalidEmail' => __('Please enter a valid email address', 'mobooking'),
                    'bookingSuccess' => __('Booking confirmed successfully!', 'mobooking'),
                    'zipRequired' => __('Please enter a ZIP code', 'mobooking'),
                    'zipInvalid' => __('Please enter a valid ZIP code', 'mobooking'),
                    'zipNotCovered' => __('Sorry, we don\'t service this area', 'mobooking'),
                    'zipCovered' => __('Great! We service your area', 'mobooking'),
                    'discountInvalid' => __('Invalid discount code', 'mobooking'),
                    'discountApplied' => __('Discount applied successfully', 'mobooking'),
                    'autoAdvancing' => __('Moving to next step...', 'mobooking'),
                    'selectOptions' => __('Please configure your service options', 'mobooking'),
                    'fillCustomerInfo' => __('Please fill in your contact information', 'mobooking')
                ),
                'currency' => array(
                    'symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '',
                    'position' => get_option('woocommerce_currency_pos', 'left')
                ),
                'autoAdvance' => array(
                    'enabled' => true,
                    'delay' => 1500, // 1.5 seconds
                    'zipSuccess' => true,
                    'serviceSelection' => true,
                    'optionsComplete' => true,
                    'customerComplete' => true
                ),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'user' => array(
                    'name' => $user->display_name,
                    'email' => $user->user_email
                )
            );

            wp_localize_script('mobooking-booking-form', 'mobookingBooking', $localize_data);
            
            // Generate booking form HTML
            return $this->render_booking_form($user_id, $services, $atts);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in booking_form_shortcode: ' . $e->getMessage());
            }
            return '<p class="mobooking-error">' . __('Booking form temporarily unavailable.', 'mobooking') . '</p>';
        }
    }
    
    /**
     * Render booking form HTML - ENHANCED with auto-progression support
     */
    private function render_booking_form($user_id, $services, $atts) {
        try {
            if (!class_exists('\MoBooking\Services\ServiceOptionsManager')) {
                throw new Exception('ServiceOptionsManager class not found');
            }
            
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            
            ob_start();
            ?>
            <div class="mobooking-booking-form-container">
                <!-- Enhanced Progress Indicator -->
                <div class="booking-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 16.66%;"></div>
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
                        <div class="step">
                            <div class="step-number">6</div>
                            <div class="step-label"><?php _e('Complete', 'mobooking'); ?></div>
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
                            <div class="zip-input-wrapper">
                                <input type="text" id="customer_zip_code" name="zip_code" class="zip-input" 
                                       placeholder="<?php _e('Enter ZIP code', 'mobooking'); ?>" required
                                       pattern="[0-9]{5}(-[0-9]{4})?" 
                                       title="<?php _e('Please enter a valid ZIP code (e.g., 12345 or 12345-6789)', 'mobooking'); ?>">
                                <div class="zip-validation-icon"></div>

                            </div>
                            <p class="zip-help"><?php _e('Enter your ZIP code to check service availability', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="zip-result"></div>
                        
                        <div class="step-actions">
                            <button type="button" class="btn-primary next-step" disabled>
                                <?php _e('Enter ZIP Code', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Services -->
                    <div class="booking-step step-2">
                        <div class="step-header">
                            <h2><?php _e('Select Services', 'mobooking'); ?></h2>
                            <p><?php _e('Choose the services you need', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="services-grid services-container">
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
                                            
                                            <div class="service-content">
                                                <h3><?php echo esc_html($service->name); ?></h3>
                                                <?php if (!empty($service->description)) : ?>
                                                    <p class="service-description"><?php echo esc_html($service->description); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="service-selector">
                                            <input type="checkbox" name="selected_services[]" value="<?php echo esc_attr($service->id); ?>" 
                                                   id="service_<?php echo esc_attr($service->id); ?>" 
                                                   data-has-options="<?php echo $has_options ? 1 : 0; ?>">
                                            <div class="service-checkbox"></div>
                                        </div>
                                    </div>
                                    
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
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="step-actions">
                            <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                            <button type="button" class="btn-primary next-step" disabled><?php _e('Select Services', 'mobooking'); ?></button>
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
                        
                        <div class="no-options-message" style="display: none;">
                            <div class="auto-advance-notice">
                                <div class="notice-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                    </svg>
                                </div>
                                <p><?php _e('No additional options needed. Moving to next step...', 'mobooking'); ?></p>
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
                            
                            <div class="success-actions">
                                <button type="button" class="btn-primary new-booking-btn" onclick="location.reload();">
                                    <?php _e('Book Another Service', 'mobooking'); ?>
                                </button>
                                <button type="button" class="btn-secondary print-booking-btn" onclick="window.print();">
                                    <?php _e('Print Confirmation', 'mobooking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Auto-Progression Status Indicator -->
                <div class="auto-progress-indicator" style="display: none;">
                    <div class="progress-content">
                        <div class="progress-spinner">
                            <div class="spinner"></div>
                        </div>
                        <div class="progress-message">
                            <span class="progress-text"><?php _e('Processing...', 'mobooking'); ?></span>
                            <div class="progress-dots">
                                <span class="dot"></span>
                                <span class="dot"></span>
                                <span class="dot"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
            /* Enhanced styles for auto-progression */
            .auto-advance-notice {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                padding: 2rem;
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
                border: 1px solid rgba(16, 185, 129, 0.2);
                border-radius: 0.5rem;
                color: #059669;
                font-weight: 500;
            }
            
            .notice-icon svg {
                width: 2rem;
                height: 2rem;
                color: #10b981;
            }
            
            .auto-progress-indicator {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(8px);
                border-radius: 1rem;
                padding: 2rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                text-align: center;
                min-width: 200px;
            }
            
            .progress-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .progress-spinner .spinner {
                width: 2rem;
                height: 2rem;
                border: 3px solid rgba(59, 130, 246, 0.2);
                border-top-color: #3b82f6;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            .progress-message {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            
            .progress-text {
                font-weight: 600;
                color: #374151;
            }
            
            .progress-dots {
                display: flex;
                gap: 0.25rem;
            }
            
            .progress-dots .dot {
                width: 0.5rem;
                height: 0.5rem;
                background: #3b82f6;
                border-radius: 50%;
                animation: dotPulse 1.5s infinite;
            }
            
            .progress-dots .dot:nth-child(2) {
                animation-delay: 0.2s;
            }
            
            .progress-dots .dot:nth-child(3) {
                animation-delay: 0.4s;
            }
            
            @keyframes dotPulse {
                0%, 20%, 80%, 100% {
                    opacity: 0.3;
                    transform: scale(1);
                }
                50% {
                    opacity: 1;
                    transform: scale(1.2);
                }
            }
            
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
            
            /* Enhanced step transitions */
            .booking-step {
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                transform: translateX(0);
                opacity: 1;
            }
            
            .booking-step:not(.active) {
                transform: translateX(-20px);
                opacity: 0;
                pointer-events: none;
            }
            
            .booking-step.entering {
                transform: translateX(20px);
                opacity: 0;
            }
            
            .booking-step.entering.active {
                transform: translateX(0);
                opacity: 1;
            }
            
            /* Enhanced progress bar animation */
            .progress-fill {
                transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }
            
            .progress-fill::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                animation: shimmer 2s infinite;
            }
            
            @keyframes shimmer {
                0% {
                    transform: translateX(-100%);
                }
                100% {
                    transform: translateX(100%);
                }
            }
            
            /* Enhanced service card selection */
            .service-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .service-card.selected {
                transform: translateY(-4px) scale(1.02);
                box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            }
            
            .service-card.selecting {
                animation: cardPulse 0.6s ease-out;
            }
            
            @keyframes cardPulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
                100% {
                    transform: scale(1.02);
                }
            }
            
            /* Enhanced button states */
            .btn-primary, .btn-secondary {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }
            
            .btn-primary:not(:disabled):hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
            }
            
            .btn-primary:not(:disabled):active {
                transform: translateY(0);
            }
            
            /* Success step enhancements */
            .step-success {
                text-align: center;
                animation: successFadeIn 0.8s ease-out;
            }
            
            @keyframes successFadeIn {
                0% {
                    opacity: 0;
                    transform: translateY(20px) scale(0.95);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
            
            .success-icon {
                animation: successBounce 1s ease-out 0.3s both;
            }
            
            @keyframes successBounce {
                0% {
                    transform: scale(0);
                }
                50% {
                    transform: scale(1.2);
                }
                100% {
                    transform: scale(1);
                }
            }
            
            .success-actions {
                display: flex;
                gap: 1rem;
                justify-content: center;
                margin-top: 2rem;
            }
            
            @media (max-width: 768px) {
                .success-actions {
                    flex-direction: column;
                }
                
                .auto-progress-indicator {
                    margin: 0 1rem;
                    min-width: auto;
                    width: calc(100% - 2rem);
                }
            }
            </style>
            <?php
            return ob_get_clean();
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in render_booking_form: ' . $e->getMessage());
            }
            return '<p class="mobooking-error">' . __('Error rendering booking form.', 'mobooking') . '</p>';
        }
    }
}
