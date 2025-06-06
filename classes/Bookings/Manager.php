<?php
namespace MoBooking\Bookings;

/**
 * Enhanced Bookings Manager - FIXED to use normalized database structure
 * NO MORE JSON STORAGE - Uses proper junction tables
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
            error_log('MoBooking\Bookings\Manager: Enhanced constructor with normalized database support');
        }
    }

    /**
     * FIXED: Save booking using normalized database structure
     */
    public function save_booking($data) {
        global $wpdb;
        
        // Start transaction for data integrity
        $wpdb->query('START TRANSACTION');
        
        try {
            // Validate required data
            $this->validate_booking_data($data);
            
            // Calculate pricing
            $pricing = $this->calculate_booking_pricing($data);
            
            // Prepare main booking data (NO MORE JSON!)
            $booking_data = array(
                'user_id' => absint($data['user_id']),
                'customer_name' => sanitize_text_field($data['customer_name']),
                'customer_email' => sanitize_email($data['customer_email']),
                'customer_phone' => sanitize_text_field($data['customer_phone']),
                'customer_address' => sanitize_textarea_field($data['customer_address']),
                'zip_code' => sanitize_text_field($data['zip_code']),
                'service_date' => sanitize_text_field($data['service_date']),
                'subtotal' => $pricing['subtotal'],
                'total_price' => $pricing['total'],
                'discount_code' => isset($data['discount_code']) ? sanitize_text_field($data['discount_code']) : '',
                'discount_amount' => isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0,
                'status' => 'pending',
                'notes' => isset($data['booking_notes']) ? sanitize_textarea_field($data['booking_notes']) : ''
            );
            
            // Insert main booking record
            $result = $wpdb->insert(
                $wpdb->prefix . 'mobooking_bookings',
                $booking_data,
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%f', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception('Failed to create booking record');
            }
            
            $booking_id = $wpdb->insert_id;
            
            // Insert booking services (normalized!)
            $this->save_booking_services($booking_id, $data['selected_services']);
            
            // Insert booking service options (normalized!)
            if (!empty($data['service_options_data'])) {
                $this->save_booking_service_options($booking_id, $data['service_options_data']);
            }
            
            // Update discount usage if applicable
            if (!empty($data['discount_code'])) {
                $this->update_discount_usage($data['discount_code'], $data['user_id']);
            }
            
            $wpdb->query('COMMIT');
            
            // Send confirmation email
            $this->send_booking_confirmation($booking_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Successfully created booking {$booking_id} with normalized structure");
            }
            
            return $booking_id;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Booking save failed: ' . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * NEW: Save booking services to junction table
     */
    private function save_booking_services($booking_id, $selected_services) {
        global $wpdb;
        
        if (empty($selected_services) || !is_array($selected_services)) {
            throw new Exception('No services selected');
        }
        
        foreach ($selected_services as $service_id) {
            $service_id = absint($service_id);
            
            if ($service_id <= 0) {
                continue;
            }
            
            // Get service details
            $service = $wpdb->get_row($wpdb->prepare(
                "SELECT id, name, price FROM {$wpdb->prefix}mobooking_services WHERE id = %d",
                $service_id
            ));
            
            if (!$service) {
                throw new Exception("Service {$service_id} not found");
            }
            
            // Insert into junction table
            $result = $wpdb->insert(
                $wpdb->prefix . 'mobooking_booking_services',
                array(
                    'booking_id' => $booking_id,
                    'service_id' => $service_id,
                    'quantity' => 1,
                    'unit_price' => $service->price,
                    'total_price' => $service->price
                ),
                array('%d', '%d', '%d', '%f', '%f')
            );
            
            if ($result === false) {
                throw new Exception("Failed to save service {$service_id}");
            }
        }
    }
    
    /**
     * NEW: Save booking service options to junction table
     */
    private function save_booking_service_options($booking_id, $options_data) {
        global $wpdb;
        
        // Parse options data
        if (is_string($options_data)) {
            $options_data = json_decode($options_data, true);
        }
        
        if (!is_array($options_data)) {
            return; // No options to save
        }
        
        foreach ($options_data as $option_id => $option_value) {
            $option_id = absint($option_id);
            
            if ($option_id <= 0) {
                continue;
            }
            
            // Get option details
            $option = $wpdb->get_row($wpdb->prepare(
                "SELECT id, name, price_impact, price_type FROM {$wpdb->prefix}mobooking_service_options WHERE id = %d",
                $option_id
            ));
            
            if (!$option) {
                continue; // Skip invalid options
            }
            
            // Calculate price impact
            $price_impact = $this->calculate_option_price_impact($option, $option_value);
            
            // Insert into junction table
            $wpdb->insert(
                $wpdb->prefix . 'mobooking_booking_service_options',
                array(
                    'booking_id' => $booking_id,
                    'service_option_id' => $option_id,
                    'option_value' => is_array($option_value) ? json_encode($option_value) : (string)$option_value,
                    'price_impact' => $price_impact
                ),
                array('%d', '%d', '%s', '%f')
            );
        }
    }
    
    /**
     * NEW: Calculate booking pricing from normalized data
     */
    private function calculate_booking_pricing($data) {
        global $wpdb;
        
        $subtotal = 0;
        $services_total = 0;
        $options_total = 0;
        
        // Calculate services total
        if (!empty($data['selected_services']) && is_array($data['selected_services'])) {
            $service_ids = array_map('absint', $data['selected_services']);
            $service_ids = array_filter($service_ids);
            
            if (!empty($service_ids)) {
                $placeholders = implode(',', array_fill(0, count($service_ids), '%d'));
                $services_total = $wpdb->get_var($wpdb->prepare(
                    "SELECT SUM(price) FROM {$wpdb->prefix}mobooking_services WHERE id IN ($placeholders)",
                    ...$service_ids
                ));
                $services_total = floatval($services_total);
            }
        }
        
        // Calculate options total
        if (!empty($data['service_options_data'])) {
            $options_data = is_string($data['service_options_data']) 
                ? json_decode($data['service_options_data'], true) 
                : $data['service_options_data'];
            
            if (is_array($options_data)) {
                foreach ($options_data as $option_id => $option_value) {
                    $option_id = absint($option_id);
                    
                    if ($option_id <= 0) {
                        continue;
                    }
                    
                    $option = $wpdb->get_row($wpdb->prepare(
                        "SELECT price_impact, price_type FROM {$wpdb->prefix}mobooking_service_options WHERE id = %d",
                        $option_id
                    ));
                    
                    if ($option) {
                        $options_total += $this->calculate_option_price_impact($option, $option_value);
                    }
                }
            }
        }
        
        $subtotal = $services_total + $options_total;
        $discount_amount = isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0;
        $total = max(0, $subtotal - $discount_amount);
        
        return array(
            'services_total' => $services_total,
            'options_total' => $options_total,
            'subtotal' => $subtotal,
            'discount_amount' => $discount_amount,
            'total' => $total
        );
    }
    
    /**
     * Calculate option price impact
     */
    private function calculate_option_price_impact($option, $option_value) {
        if ($option->price_type === 'none' || $option->price_impact == 0) {
            return 0;
        }
        
        switch ($option->price_type) {
            case 'fixed':
                return floatval($option->price_impact);
                
            case 'percentage':
                // Note: For percentage, we return the base percentage
                // The actual percentage calculation should be done against the service price
                return floatval($option->price_impact);
                
            case 'multiply':
                if (is_numeric($option_value)) {
                    return floatval($option->price_impact) * floatval($option_value);
                }
                return 0;
                
            case 'choice':
                // For choice-based pricing, try to extract price from the value
                if (is_string($option_value) && strpos($option_value, ':') !== false) {
                    $parts = explode(':', $option_value);
                    if (isset($parts[1]) && is_numeric($parts[1])) {
                        return floatval($parts[1]);
                    }
                }
                return 0;
                
            default:
                return 0;
        }
    }
    
    /**
     * FIXED: Get booking by ID with normalized data
     */
    public function get_booking($booking_id, $user_id = null) {
        global $wpdb;
        
        // Get main booking data
        if ($user_id) {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_bookings WHERE id = %d AND user_id = %d",
                $booking_id, $user_id
            ));
        } else {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_bookings WHERE id = %d",
                $booking_id
            ));
        }
        
        if (!$booking) {
            return null;
        }
        
        // Get booking services
        $booking->services = $wpdb->get_results($wpdb->prepare(
            "SELECT bs.*, s.name as service_name, s.description as service_description
             FROM {$wpdb->prefix}mobooking_booking_services bs
             JOIN {$wpdb->prefix}mobooking_services s ON bs.service_id = s.id
             WHERE bs.booking_id = %d",
            $booking_id
        ));
        
        // Get booking service options
        $booking->service_options = $wpdb->get_results($wpdb->prepare(
            "SELECT bso.*, so.name as option_name, so.type as option_type
             FROM {$wpdb->prefix}mobooking_booking_service_options bso
             JOIN {$wpdb->prefix}mobooking_service_options so ON bso.service_option_id = so.id
             WHERE bso.booking_id = %d",
            $booking_id
        ));
        
        return $booking;
    }
    
    /**
     * FIXED: Get user bookings with normalized data
     */
    public function get_user_bookings($user_id, $args = array()) {
        global $wpdb;
        
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
        
        $sql = "SELECT * FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d";
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
        
        $bookings = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // Enhance each booking with services and options data
        foreach ($bookings as $booking) {
            $booking->services = $wpdb->get_results($wpdb->prepare(
                "SELECT bs.*, s.name as service_name
                 FROM {$wpdb->prefix}mobooking_booking_services bs
                 JOIN {$wpdb->prefix}mobooking_services s ON bs.service_id = s.id
                 WHERE bs.booking_id = %d",
                $booking->id
            ));
            
            $booking->service_options = $wpdb->get_results($wpdb->prepare(
                "SELECT bso.*, so.name as option_name
                 FROM {$wpdb->prefix}mobooking_booking_service_options bso
                 JOIN {$wpdb->prefix}mobooking_service_options so ON bso.service_option_id = so.id
                 WHERE bso.booking_id = %d",
                $booking->id
            ));
        }
        
        return $bookings;
    }
    
    /**
     * FIXED: Count user bookings
     */
    public function count_user_bookings($user_id, $status = '') {
        global $wpdb;
        
        if (!empty($status)) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d AND status = %s",
                $user_id, $status
            ));
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * FIXED: Calculate user revenue
     */
    public function calculate_user_revenue($user_id, $period = 'all') {
        global $wpdb;
        
        $sql = "SELECT SUM(total_price) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d AND status IN ('confirmed', 'completed')";
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
     * FIXED: Get most popular service using normalized data
     */
    public function get_most_popular_service($user_id) {
        global $wpdb;
        
        $sql = "SELECT s.*, COUNT(bs.service_id) as booking_count 
                FROM {$wpdb->prefix}mobooking_services s
                JOIN {$wpdb->prefix}mobooking_booking_services bs ON s.id = bs.service_id
                JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
                WHERE s.user_id = %d 
                GROUP BY s.id 
                ORDER BY booking_count DESC 
                LIMIT 1";
        
        return $wpdb->get_row($wpdb->prepare($sql, $user_id));
    }
    
    /**
     * Validate booking data
     */
    private function validate_booking_data($data) {
        $required_fields = array('user_id', 'customer_name', 'customer_email', 'customer_address', 'zip_code', 'service_date', 'selected_services');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        if (!is_email($data['customer_email'])) {
            throw new Exception('Invalid email address');
        }
        
        if (empty($data['selected_services']) || !is_array($data['selected_services'])) {
            throw new Exception('No services selected');
        }
        
        // Validate service date
        $service_date = strtotime($data['service_date']);
        if ($service_date === false || $service_date < time()) {
            throw new Exception('Invalid service date');
        }
    }
    
    /**
     * Update discount usage
     */
    private function update_discount_usage($discount_code, $user_id) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}mobooking_discounts 
             SET usage_count = usage_count + 1 
             WHERE code = %s AND user_id = %d",
            $discount_code, $user_id
        ));
    }
    
    /**
     * AJAX handler to save booking - UPDATED for normalized structure
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
                    'auto_advance' => true
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
    
    // Keep existing AJAX handlers unchanged
    public function ajax_check_zip_coverage() {
        try {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking ZIP Coverage AJAX Handler Called');
                error_log('POST Data: ' . print_r($_POST, true));
            }

            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            if (!isset($_POST['zip_code']) || !isset($_POST['user_id'])) {
                wp_send_json_error(array(
                    'message' => __('Missing required information.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            $zip_code = sanitize_text_field(trim($_POST['zip_code']));
            $user_id = absint($_POST['user_id']);
            
            if (!preg_match('/^\d{5}(-\d{4})?$/', $zip_code)) {
                wp_send_json_error(array(
                    'message' => __('Please enter a valid ZIP code format.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
            $user = get_userdata($user_id);
            if (!$user || (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles))) {
                wp_send_json_error(array(
                    'message' => __('Invalid business account.', 'mobooking'),
                    'auto_advance' => false
                ));
                return;
            }
            
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
                    'auto_advance' => true
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

    public function ajax_get_service_options() {
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce') || 
                          wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce');
        }

        if (!$nonce_valid) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Service ID required'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_id = %d ORDER BY display_order ASC",
            $service_id
        ));

        $formatted_options = array();
        foreach ($options as $option) {
            $formatted_options[] = array(
                'id' => intval($option->id),
                'service_id' => intval($option->service_id),
                'name' => $option->name,
                'description' => $option->description,
                'type' => $option->type,
                'is_required' => intval($option->is_required),
                'price_impact' => floatval($option->price_impact),
                'price_type' => $option->price_type,
                'options' => $option->options,
                'default_value' => $option->default_value,
                'placeholder' => $option->placeholder,
                'min_value' => $option->min_value,
                'max_value' => $option->max_value,
                'step' => $option->step,
                'unit' => $option->unit,
                'rows' => intval($option->rows)
            );
        }

        wp_send_json_success(array('options' => $formatted_options));
    }

    // Keep other existing methods unchanged...
    public function register_booking_endpoints() {}
    public function add_booking_query_vars($vars) { return $vars; }
    public function ajax_validate_discount() {}
    public function ajax_get_user_bookings() {}
    public function ajax_update_booking_status() {}
    public function update_booking_status($booking_