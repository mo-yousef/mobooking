<?php
/**
 * MoBooking AJAX Handlers
 * Centralized AJAX request handling
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all AJAX handlers
 */
function mobooking_register_ajax_handlers() {
    // Booking-related AJAX handlers
    add_action('wp_ajax_mobooking_check_zip_coverage', 'mobooking_ajax_check_zip_coverage');
    add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', 'mobooking_ajax_check_zip_coverage');
    
    add_action('wp_ajax_mobooking_get_service_options', 'mobooking_ajax_get_service_options');
    add_action('wp_ajax_nopriv_mobooking_get_service_options', 'mobooking_ajax_get_service_options');
    
    add_action('wp_ajax_mobooking_save_booking', 'mobooking_ajax_save_booking');
    add_action('wp_ajax_nopriv_mobooking_save_booking', 'mobooking_ajax_save_booking');
    
    add_action('wp_ajax_mobooking_validate_discount', 'mobooking_ajax_validate_discount');
    add_action('wp_ajax_nopriv_mobooking_validate_discount', 'mobooking_ajax_validate_discount');
    
    // Dashboard AJAX handlers
    add_action('wp_ajax_mobooking_save_service', 'mobooking_ajax_save_service');
    add_action('wp_ajax_mobooking_delete_service', 'mobooking_ajax_delete_service');
    add_action('wp_ajax_mobooking_save_service_option', 'mobooking_ajax_save_service_option');
    add_action('wp_ajax_mobooking_delete_service_option', 'mobooking_ajax_delete_service_option');
    add_action('wp_ajax_mobooking_update_options_order', 'mobooking_ajax_update_options_order');
    
    // Settings AJAX handlers
    add_action('wp_ajax_mobooking_save_settings', 'mobooking_ajax_save_settings');
    add_action('wp_ajax_mobooking_send_test_email', 'mobooking_ajax_send_test_email');
    add_action('wp_ajax_mobooking_export_data', 'mobooking_ajax_export_data');
    add_action('wp_ajax_mobooking_import_data', 'mobooking_ajax_import_data');
    
    // Areas AJAX handlers
    add_action('wp_ajax_mobooking_save_area', 'mobooking_ajax_save_area');
    add_action('wp_ajax_mobooking_delete_area', 'mobooking_ajax_delete_area');
    
    // Discount AJAX handlers
    add_action('wp_ajax_mobooking_save_discount', 'mobooking_ajax_save_discount');
    add_action('wp_ajax_mobooking_delete_discount', 'mobooking_ajax_delete_discount');
    
    // Auth AJAX handlers
    add_action('wp_ajax_nopriv_mobooking_login', 'mobooking_ajax_login');
    add_action('wp_ajax_nopriv_mobooking_register', 'mobooking_ajax_register');
    add_action('wp_ajax_mobooking_logout', 'mobooking_ajax_logout');
    
    // Utility AJAX handlers
    add_action('wp_ajax_mobooking_upload_image', 'mobooking_ajax_upload_image');
    add_action('wp_ajax_mobooking_get_dashboard_stats', 'mobooking_ajax_get_dashboard_stats');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: AJAX handlers registered successfully');
    }
}
add_action('init', 'mobooking_register_ajax_handlers', 5);

/**
 * ZIP Coverage Check Handler
 */
function mobooking_ajax_check_zip_coverage() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
            return;
        }
        
        // Validate parameters
        if (!isset($_POST['zip_code']) || !isset($_POST['user_id'])) {
            wp_send_json_error(array('message' => __('Missing required information.', 'mobooking')));
            return;
        }
        
        $zip_code = sanitize_text_field(trim($_POST['zip_code']));
        $user_id = absint($_POST['user_id']);
        
        // Validate ZIP code format
        if (!preg_match('/^\d{5}(-\d{4})?$/', $zip_code)) {
            wp_send_json_error(array('message' => __('Please enter a valid ZIP code format.', 'mobooking')));
            return;
        }
        
        // Check coverage
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            $is_covered = $geography_manager->is_zip_covered($zip_code, $user_id);
        } else {
            // Fallback database check
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_areas';
            $is_covered = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE zip_code = %s AND user_id = %d AND active = 1",
                $zip_code, $user_id
            )) > 0;
        }
        
        if ($is_covered) {
            wp_send_json_success(array(
                'message' => __('Great! We provide services in your area.', 'mobooking'),
                'auto_advance' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sorry, we don\'t currently service this area.', 'mobooking'),
                'auto_advance' => false
            ));
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking ZIP Coverage Exception: ' . $e->getMessage());
        }
        wp_send_json_error(array('message' => __('An error occurred while checking service availability.', 'mobooking')));
    }
}

/**
 * Get Service Options Handler
 */
function mobooking_ajax_get_service_options() {
    try {
        // Check nonce
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce') || 
                          wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce');
        }

        if (!$nonce_valid) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
            return;
        }

        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        if (!$service_id) {
            wp_send_json_error(array('message' => __('Service ID required.', 'mobooking')));
            return;
        }

        // Get options from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_id = %d ORDER BY display_order ASC",
            $service_id
        ));

        // Format options for frontend
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
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Get Service Options Exception: ' . $e->getMessage());
        }
        wp_send_json_error(array('message' => __('Error loading service options.', 'mobooking')));
    }
}

/**
 * Save Booking Handler
 */
function mobooking_ajax_save_booking() {
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
        
        // Use the Bookings Manager to save
        if (class_exists('\MoBooking\Bookings\Manager')) {
            $bookings_manager = new \MoBooking\Bookings\Manager();
            $booking_id = $bookings_manager->save_booking($_POST);
            
            if ($booking_id) {
                wp_send_json_success(array(
                    'id' => $booking_id,
                    'message' => __('Booking confirmed successfully!', 'mobooking'),
                    'auto_advance' => true
                ));
            } else {
                wp_send_json_error(__('Failed to save booking. Please try again.', 'mobooking'));
            }
        } else {
            wp_send_json_error(__('Booking system not available.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Save Booking Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while saving your booking.', 'mobooking'));
    }
}

/**
 * Settings Save Handler
 */
function mobooking_ajax_save_settings() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-settings-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Initialize the settings AJAX manager if not already done
        if (class_exists('MoBookingSettingsAjaxManager')) {
            $settings_manager = new MoBookingSettingsAjaxManager();
            $settings_manager->ajax_save_settings();
        } else {
            // Fallback direct save
            mobooking_fallback_save_settings($user_id, $_POST);
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Settings Save Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while saving settings.', 'mobooking'));
    }
}

/**
 * Fallback settings save function
 */
function mobooking_fallback_save_settings($user_id, $data) {
    $saved_count = 0;
    
    // Save basic settings to user meta
    $settings_fields = array(
        'company_name' => 'sanitize_text_field',
        'phone' => 'sanitize_text_field',
        'primary_color' => 'sanitize_hex_color',
        'logo_url' => 'esc_url_raw',
        'business_email' => 'sanitize_email',
        'business_address' => 'sanitize_textarea_field',
    );
    
    foreach ($settings_fields as $field => $sanitize_func) {
        if (isset($data[$field])) {
            $value = $sanitize_func($data[$field]);
            if (update_user_meta($user_id, 'mobooking_' . $field, $value)) {
                $saved_count++;
            }
        }
    }
    
    if ($saved_count > 0) {
        wp_send_json_success(array('message' => __('Settings saved successfully!', 'mobooking')));
    } else {
        wp_send_json_error(__('No settings were updated.', 'mobooking'));
    }
}

/**
 * Upload Image Handler
 */
function mobooking_ajax_upload_image() {
    // Check nonce and permissions
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(__('Security verification failed.', 'mobooking'));
        return;
    }
    
    if (!current_user_can('upload_files')) {
        wp_send_json_error(__('You do not have permission to upload files.', 'mobooking'));
        return;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(__('No file uploaded or upload error occurred.', 'mobooking'));
        return;
    }
    
    // Validate file type
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        wp_send_json_error(__('Invalid file type. Please upload an image.', 'mobooking'));
        return;
    }
    
    // Handle the upload
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_upload('image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error($attachment_id->get_error_message());
        return;
    }
    
    $image_url = wp_get_attachment_url($attachment_id);
    $image_data = wp_get_attachment_image_src($attachment_id, 'medium');
    
    wp_send_json_success(array(
        'attachment_id' => $attachment_id,
        'url' => $image_url,
        'thumb_url' => $image_data ? $image_data[0] : $image_url,
        'message' => __('Image uploaded successfully.', 'mobooking')
    ));
}

/**
 * Get Dashboard Stats Handler
 */
function mobooking_ajax_get_dashboard_stats() {
    // Check nonce and permissions
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(__('Security verification failed.', 'mobooking'));
        return;
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        return;
    }
    
    try {
        $user_id = get_current_user_id();
        
        if (class_exists('\MoBooking\Dashboard\Manager')) {
            $dashboard_manager = new \MoBooking\Dashboard\Manager();
            $stats = $dashboard_manager->get_dashboard_stats($user_id);
            wp_send_json_success(array('stats' => $stats));
        } else {
            wp_send_json_error(__('Dashboard manager not available.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Dashboard Stats Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error loading dashboard statistics.', 'mobooking'));
    }
}

/**
 * Generic service save handler
 */
function mobooking_ajax_save_service() {
    try {
        // Delegate to Services Manager
        if (class_exists('\MoBooking\Services\ServicesManager')) {
            $services_manager = new \MoBooking\Services\ServicesManager();
            return $services_manager->ajax_save_service();
        } else {
            wp_send_json_error(__('Services manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving service.', 'mobooking'));
    }
}

/**
 * Generic service delete handler
 */
function mobooking_ajax_delete_service() {
    try {
        if (class_exists('\MoBooking\Services\ServicesManager')) {
            $services_manager = new \MoBooking\Services\ServicesManager();
            return $services_manager->ajax_delete_service();
        } else {
            wp_send_json_error(__('Services manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting service.', 'mobooking'));
    }
}

/**
 * Generic service option save handler
 */
function mobooking_ajax_save_service_option() {
    try {
        if (class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            return $options_manager->ajax_save_service_option();
        } else {
            wp_send_json_error(__('Service options manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving service option.', 'mobooking'));
    }
}

/**
 * Generic service option delete handler
 */
function mobooking_ajax_delete_service_option() {
    try {
        if (class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            return $options_manager->ajax_delete_service_option();
        } else {
            wp_send_json_error(__('Service options manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting service option.', 'mobooking'));
    }
}

/**
 * Update options order handler
 */
function mobooking_ajax_update_options_order() {
    try {
        if (class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            return $options_manager->ajax_update_options_order();
        } else {
            wp_send_json_error(__('Service options manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error updating options order.', 'mobooking'));
    }
}

/**
 * Validate discount handler
 */
function mobooking_ajax_validate_discount() {
    try {
        if (class_exists('\MoBooking\Discounts\Manager')) {
            $discounts_manager = new \MoBooking\Discounts\Manager();
            return $discounts_manager->ajax_validate_discount();
        } else {
            wp_send_json_error(__('Discounts manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error validating discount.', 'mobooking'));
    }
}

/**
 * Send test email handler
 */
function mobooking_ajax_send_test_email() {
    try {
        if (class_exists('MoBookingSettingsAjaxManager')) {
            $settings_manager = new MoBookingSettingsAjaxManager();
            return $settings_manager->ajax_send_test_email();
        } else {
            wp_send_json_error(__('Settings manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error sending test email.', 'mobooking'));
    }
}

/**
 * Export data handler
 */
function mobooking_ajax_export_data() {
    wp_send_json_error(__('Export functionality not yet implemented.', 'mobooking'));
}

/**
 * Import data handler
 */
function mobooking_ajax_import_data() {
    wp_send_json_error(__('Import functionality not yet implemented.', 'mobooking'));
}

/**
 * Save area handler
 */
function mobooking_ajax_save_area() {
    try {
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            return $geography_manager->ajax_save_area();
        } else {
            wp_send_json_error(__('Geography manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving area.', 'mobooking'));
    }
}

/**
 * Delete area handler
 */
function mobooking_ajax_delete_area() {
    try {
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            return $geography_manager->ajax_delete_area();
        } else {
            wp_send_json_error(__('Geography manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting area.', 'mobooking'));
    }
}

/**
 * Save discount handler
 */
function mobooking_ajax_save_discount() {
    try {
        if (class_exists('\MoBooking\Discounts\Manager')) {
            $discounts_manager = new \MoBooking\Discounts\Manager();
            return $discounts_manager->ajax_save_discount();
        } else {
            wp_send_json_error(__('Discounts manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving discount.', 'mobooking'));
    }
}

/**
 * Delete discount handler
 */
function mobooking_ajax_delete_discount() {
    try {
        if (class_exists('\MoBooking\Discounts\Manager')) {
            $discounts_manager = new \MoBooking\Discounts\Manager();
            return $discounts_manager->ajax_delete_discount();
        } else {
            wp_send_json_error(__('Discounts manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting discount.', 'mobooking'));
    }
}

/**
 * Login handler
 */
function mobooking_ajax_login() {
    try {
        if (class_exists('\MoBooking\Auth\Manager')) {
            $auth_manager = new \MoBooking\Auth\Manager();
            return $auth_manager->handle_login();
        } else {
            wp_send_json_error(__('Authentication manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error processing login.', 'mobooking'));
    }
}

/**
 * Registration handler
 */
function mobooking_ajax_register() {
    try {
        if (class_exists('\MoBooking\Auth\Manager')) {
            $auth_manager = new \MoBooking\Auth\Manager();
            return $auth_manager->handle_registration();
        } else {
            wp_send_json_error(__('Authentication manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error processing registration.', 'mobooking'));
    }
}

/**
 * Logout handler
 */
function mobooking_ajax_logout() {
    try {
        if (class_exists('\MoBooking\Auth\Manager')) {
            $auth_manager = new \MoBooking\Auth\Manager();
            return $auth_manager->handle_logout();
        } else {
            wp_logout();
            wp_send_json_success(array('message' => __('Logged out successfully.', 'mobooking')));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error processing logout.', 'mobooking'));
    }
}


// ISSUE 3: Fixed AJAX Handler - Add this to your includes/ajax-handlers.php
function mobooking_ajax_save_booking_form_settings() {
    try {
        // Enhanced debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - Booking Form Settings AJAX called');
            error_log('POST data: ' . print_r($_POST, true));
        }
        
        // Check nonce first
        if (!isset($_POST['nonce'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking - No nonce in POST data');
            }
            wp_send_json_error(__('Security nonce is missing.', 'mobooking'));
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking - Nonce verification failed');
                error_log('Expected nonce action: mobooking-booking-form-nonce');
                error_log('Received nonce: ' . $_POST['nonce']);
            }
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        $is_draft = isset($_POST['is_draft']) && $_POST['is_draft'] === '1';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - User ID: ' . $user_id . ', Is Draft: ' . ($is_draft ? 'Yes' : 'No'));
        }
        
        // Check if BookingForm Manager exists
        if (!class_exists('\MoBooking\BookingForm\Manager')) {
            wp_send_json_error(__('Booking form manager not available.', 'mobooking'));
            return;
        }
        
        // Prepare settings data
        $settings_data = array(
            'form_title' => sanitize_text_field($_POST['form_title'] ?? ''),
            'form_description' => sanitize_textarea_field($_POST['form_description'] ?? ''),
            'is_active' => !$is_draft && isset($_POST['is_active']) ? absint($_POST['is_active']) : 0,
            'show_form_header' => isset($_POST['show_form_header']) ? 1 : 0,
            'show_service_descriptions' => isset($_POST['show_service_descriptions']) ? 1 : 0,
            'show_price_breakdown' => isset($_POST['show_price_breakdown']) ? 1 : 0,
            'enable_zip_validation' => isset($_POST['enable_zip_validation']) ? 1 : 0,
            'primary_color' => sanitize_hex_color($_POST['primary_color'] ?? '#3b82f6'),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color'] ?? '#1e40af'),
            'logo_url' => esc_url_raw($_POST['logo_url'] ?? ''),
            'form_layout' => sanitize_text_field($_POST['form_layout'] ?? 'modern'),
            'form_width' => sanitize_text_field($_POST['form_width'] ?? 'standard'),
            'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize_textarea_field($_POST['seo_description'] ?? ''),
            'analytics_code' => wp_kses_post($_POST['analytics_code'] ?? ''),
            'custom_css' => wp_strip_all_tags($_POST['custom_css'] ?? ''),
            'custom_footer_text' => sanitize_textarea_field($_POST['custom_footer_text'] ?? ''),
            'contact_info' => sanitize_textarea_field($_POST['contact_info'] ?? ''),
            'social_links' => sanitize_textarea_field($_POST['social_links'] ?? '')
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - Settings data prepared: ' . print_r($settings_data, true));
        }
        
        // Save settings using BookingForm Manager
        $booking_form_manager = new \MoBooking\BookingForm\Manager();
        $result = $booking_form_manager->save_settings($user_id, $settings_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        }
        
        if ($result) {
            $response_data = array(
                'message' => $is_draft ? 
                    __('Settings saved as draft.', 'mobooking') : 
                    __('Settings saved successfully!', 'mobooking'),
                'booking_url' => $booking_form_manager->get_booking_form_url($user_id),
                'embed_url' => $booking_form_manager->get_embed_url($user_id)
            );
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error(__('Failed to save settings. Database error.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - Exception in booking form settings: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while saving settings: ' . $e->getMessage(), 'mobooking'));
    }
}

// ISSUE 4: Register the AJAX handler properly
add_action('wp_ajax_mobooking_save_booking_form_settings', 'mobooking_ajax_save_booking_form_settings');





// ISSUE 5: Add reset handler
function mobooking_ajax_reset_booking_form_settings() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Reset settings using BookingForm Manager
        $booking_form_manager = new \MoBooking\BookingForm\Manager();
        $result = $booking_form_manager->reset_settings($user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Settings reset to defaults successfully.', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - Exception in reset booking form settings: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while resetting settings.', 'mobooking'));
    }
}

add_action('wp_ajax_mobooking_reset_booking_form_settings', 'mobooking_ajax_reset_booking_form_settings');
