<?php
// Add this to your SettingsManager class or create a new Settings AJAX handler

/**
 * Enhanced Settings Manager with AJAX handlers
 */
class SettingsAjaxManager {
    
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_mobooking_send_test_email', array($this, 'ajax_send_test_email'));
        add_action('wp_ajax_mobooking_export_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_mobooking_import_data', array($this, 'ajax_import_data'));
        add_action('wp_ajax_mobooking_reset_settings', array($this, 'ajax_reset_settings'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Settings AJAX handlers registered');
        }
    }
    
    /**
     * AJAX handler to save all settings
     */
    public function ajax_save_settings() {
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
            $is_draft = isset($_POST['is_draft']) && $_POST['is_draft'] === '1';
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Saving settings for user ' . $user_id . ' (Draft: ' . ($is_draft ? 'Yes' : 'No') . ')');
            }
            
            // Save business settings
            $this->save_business_settings($user_id, $_POST);
            
            // Save branding settings
            $this->save_branding_settings($user_id, $_POST);
            
            // Save email settings
            $this->save_email_settings($user_id, $_POST);
            
            // Save advanced settings
            $this->save_advanced_settings($user_id, $_POST);
            
            // Save notification preferences
            $this->save_notification_preferences($user_id, $_POST);
            
            // Save business hours
            $this->save_business_hours($user_id, $_POST);
            
            $message = $is_draft 
                ? __('Settings saved as draft successfully.', 'mobooking')
                : __('All settings saved successfully.', 'mobooking');
            
            wp_send_json_success(array(
                'message' => $message,
                'is_draft' => $is_draft
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_save_settings: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while saving settings.', 'mobooking'));
        }
    }
    
    /**
     * Save business settings
     */
    private function save_business_settings($user_id, $data) {
        // Save to settings table
        $settings_manager = new \MoBooking\Database\SettingsManager();
        
        $settings_data = array(
            'company_name' => isset($data['company_name']) ? sanitize_text_field($data['company_name']) : '',
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : '',
        );
        
        $settings_manager->update_settings($user_id, $settings_data);
        
        // Save additional business info to user meta
        if (isset($data['business_email'])) {
            update_user_meta($user_id, 'business_email', sanitize_email($data['business_email']));
        }
        
        if (isset($data['business_address'])) {
            update_user_meta($user_id, 'business_address', sanitize_textarea_field($data['business_address']));
        }
        
        if (isset($data['website'])) {
            update_user_meta($user_id, 'website', esc_url_raw($data['website']));
        }
        
        if (isset($data['business_description'])) {
            update_user_meta($user_id, 'business_description', sanitize_textarea_field($data['business_description']));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Business settings saved for user ' . $user_id);
        }
    }
    
    /**
     * Save branding settings
     */
    private function save_branding_settings($user_id, $data) {
        $settings_manager = new \MoBooking\Database\SettingsManager();
        
        $branding_data = array(
            'logo_url' => isset($data['logo_url']) ? esc_url_raw($data['logo_url']) : '',
            'primary_color' => isset($data['primary_color']) ? sanitize_hex_color($data['primary_color']) : '#4CAF50',
        );
        
        $settings_manager->update_settings($user_id, $branding_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Branding settings saved for user ' . $user_id);
        }
    }
    
    /**
     * Save email settings
     */
    private function save_email_settings($user_id, $data) {
        $settings_manager = new \MoBooking\Database\SettingsManager();
        
        $email_data = array(
            'email_header' => isset($data['email_header']) ? wp_kses_post($data['email_header']) : '',
            'email_footer' => isset($data['email_footer']) ? wp_kses_post($data['email_footer']) : '',
            'booking_confirmation_message' => isset($data['booking_confirmation_message']) ? 
                wp_kses_post($data['booking_confirmation_message']) : '',
        );
        
        $settings_manager->update_settings($user_id, $email_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Email settings saved for user ' . $user_id);
        }
    }
    
    /**
     * Save advanced settings
     */
    private function save_advanced_settings($user_id, $data) {
        $settings_manager = new \MoBooking\Database\SettingsManager();
        
        $advanced_data = array(
            'terms_conditions' => isset($data['terms_conditions']) ? wp_kses_post($data['terms_conditions']) : '',
        );
        
        $settings_manager->update_settings($user_id, $advanced_data);
        
        // Save advanced user meta settings
        $advanced_meta_fields = array(
            'require_terms_acceptance',
            'booking_lead_time',
            'max_bookings_per_day',
            'auto_confirm_bookings',
            'allow_same_day_booking',
            'send_booking_reminders'
        );
        
        foreach ($advanced_meta_fields as $field) {
            if (isset($data[$field])) {
                $value = $field === 'booking_lead_time' || $field === 'max_bookings_per_day' 
                    ? absint($data[$field]) 
                    : sanitize_text_field($data[$field]);
                update_user_meta($user_id, $field, $value);
            } else {
                // For checkboxes, if not set, save as empty
                if (in_array($field, ['require_terms_acceptance', 'auto_confirm_bookings', 'allow_same_day_booking', 'send_booking_reminders'])) {
                    update_user_meta($user_id, $field, '');
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Advanced settings saved for user ' . $user_id);
        }
    }
    
    /**
     * Save notification preferences
     */
    private function save_notification_preferences($user_id, $data) {
        $notification_fields = array(
            'notify_new_booking',
            'notify_booking_changes',
            'notify_reminders'
        );
        
        foreach ($notification_fields as $field) {
            $value = isset($data[$field]) ? '1' : '';
            update_user_meta($user_id, $field, $value);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Notification preferences saved for user ' . $user_id);
        }
    }
    
    /**
     * Save business hours
     */
    private function save_business_hours($user_id, $data) {
        if (isset($data['business_hours']) && is_array($data['business_hours'])) {
            $business_hours = array();
            
            foreach ($data['business_hours'] as $day => $hours) {
                $business_hours[sanitize_key($day)] = array(
                    'open' => isset($hours['open']) && $hours['open'] === '1',
                    'start' => isset($hours['start']) ? sanitize_text_field($hours['start']) : '09:00',
                    'end' => isset($hours['end']) ? sanitize_text_field($hours['end']) : '17:00'
                );
            }
            
            update_user_meta($user_id, 'business_hours', $business_hours);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Business hours saved for user ' . $user_id . ': ' . print_r($business_hours, true));
            }
        }
    }
    
    /**
     * AJAX handler to send test email
     */
    public function ajax_send_test_email() {
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
            $user = get_userdata($user_id);
            
            // Get email content from POST data
            $email_header = isset($_POST['email_header']) ? wp_kses_post($_POST['email_header']) : '';
            $email_footer = isset($_POST['email_footer']) ? wp_kses_post($_POST['email_footer']) : '';
            $confirmation_message = isset($_POST['booking_confirmation_message']) ? 
                wp_kses_post($_POST['booking_confirmation_message']) : '';
            $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
            $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
            
            // Replace variables
            $current_year = date('Y');
            $business_email = $user->user_email;
            
            $processed_header = str_replace(
                array('{{company_name}}', '{{phone}}', '{{email}}', '{{current_year}}'),
                array($company_name, $phone, $business_email, $current_year),
                $email_header
            );
            
            $processed_footer = str_replace(
                array('{{company_name}}', '{{phone}}', '{{email}}', '{{current_year}}'),
                array($company_name, $phone, $business_email, $current_year),
                $email_footer
            );
            
            // Build test email
            $subject = sprintf(__('Test Email from %s', 'mobooking'), $company_name);
            
            $message = $processed_header;
            $message .= '<div style="padding: 20px;">';
            $message .= '<h2>' . __('Test Email', 'mobooking') . '</h2>';
            $message .= '<p>' . __('This is a test email to preview your email templates.', 'mobooking') . '</p>';
            $message .= '<h3>' . __('Sample Booking Confirmation', 'mobooking') . '</h3>';
            $message .= '<p>' . $confirmation_message . '</p>';
            $message .= '<p>' . sprintf(__('Sent at: %s', 'mobooking'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'))) . '</p>';
            $message .= '</div>';
            $message .= $processed_footer;
            
            // Send email
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $sent = wp_mail($business_email, $subject, $message, $headers);
            
            if ($sent) {
                wp_send_json_success(array(
                    'message' => sprintf(__('Test email sent successfully to %s', 'mobooking'), $business_email)
                ));
            } else {
                wp_send_json_error(__('Failed to send test email. Please check your email configuration.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_send_test_email: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while sending test email.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to export data
     */
    public function ajax_export_data() {
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
            
            // Gather all user data
            $export_data = array(
                'export_info' => array(
                    'version' => MOBOOKING_VERSION,
                    'exported_at' => current_time('mysql'),
                    'user_id' => $user_id
                ),
                'settings' => $this->get_all_user_settings($user_id),
                'services' => $this->get_user_services_data($user_id),
                'areas' => $this->get_user_areas_data($user_id),
                'bookings' => $this->get_user_bookings_data($user_id),
                'discounts' => $this->get_user_discounts_data($user_id)
            );
            
            wp_send_json_success($export_data);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_export_data: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while exporting data.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to import data
     */
    public function ajax_import_data() {
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
            
            // Check if file was uploaded
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(__('No file uploaded or upload error occurred.', 'mobooking'));
                return;
            }
            
            $file = $_FILES['import_file'];
            
            // Validate file type
            $allowed_types = array('text/csv', 'application/csv', 'text/plain');
            if (!in_array($file['type'], $allowed_types) && !str_ends_with($file['name'], '.csv')) {
                wp_send_json_error(__('Invalid file type. Please upload a CSV file.', 'mobooking'));
                return;
            }
            
            // Read file content
            $file_content = file_get_contents($file['tmp_name']);
            if ($file_content === false) {
                wp_send_json_error(__('Failed to read uploaded file.', 'mobooking'));
                return;
            }
            
            // Parse CSV
            $lines = array_map('str_getcsv', explode("\n", $file_content));
            $header = array_shift($lines);
            
            if (empty($header)) {
                wp_send_json_error(__('Invalid CSV format - no header row found.', 'mobooking'));
                return;
            }
            
            $imported_count = 0;
            $user_id = get_current_user_id();
            
            // Process CSV data (example for customer import)
            foreach ($lines as $line) {
                if (empty($line) || count($line) !== count($header)) {
                    continue;
                }
                
                $row_data = array_combine($header, $line);
                
                // Process row data based on your requirements
                // This is a basic example - you can expand based on your needs
                if (isset($row_data['email']) && is_email($row_data['email'])) {
                    // Store customer data or process as needed
                    $imported_count++;
                }
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully imported %d records.', 'mobooking'), $imported_count)
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_import_data: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while importing data.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to reset settings
     */
    public function ajax_reset_settings() {
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
            
            // Reset settings in database
            global $wpdb;
            $settings_table = $wpdb->prefix . 'mobooking_settings';
            
            $wpdb->delete($settings_table, array('user_id' => $user_id), array('%d'));
            
            // Reset user meta settings
            $meta_keys_to_reset = array(
                'business_email', 'business_address', 'website', 'business_description',
                'require_terms_acceptance', 'booking_lead_time', 'max_bookings_per_day',
                'auto_confirm_bookings', 'allow_same_day_booking', 'send_booking_reminders',
                'notify_new_booking', 'notify_booking_changes', 'notify_reminders',
                'business_hours'
            );
            
            foreach ($meta_keys_to_reset as $meta_key) {
                delete_user_meta($user_id, $meta_key);
            }
            
            // Create default settings
            $settings_manager = new \MoBooking\Database\SettingsManager();
            $user = get_userdata($user_id);
            $company_name = $user->display_name . "'s Cleaning Service";
            
            $settings_manager->create_default_settings($user_id, $company_name);
            
            wp_send_json_success(array(
                'message' => __('All settings have been reset to defaults.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_reset_settings: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while resetting settings.', 'mobooking'));
        }
    }
    
    /**
     * Get all user settings for export
     */
    private function get_all_user_settings($user_id) {
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($user_id);
        
        // Get additional user meta
        $additional_settings = array(
            'business_email' => get_user_meta($user_id, 'business_email', true),
            'business_address' => get_user_meta($user_id, 'business_address', true),
            'website' => get_user_meta($user_id, 'website', true),
            'business_description' => get_user_meta($user_id, 'business_description', true),
            'business_hours' => get_user_meta($user_id, 'business_hours', true),
            'notification_preferences' => array(
                'notify_new_booking' => get_user_meta($user_id, 'notify_new_booking', true),
                'notify_booking_changes' => get_user_meta($user_id, 'notify_booking_changes', true),
                'notify_reminders' => get_user_meta($user_id, 'notify_reminders', true)
            )
        );
        
        return array_merge((array) $settings, $additional_settings);
    }
    
    /**
     * Get user services data for export
     */
    private function get_user_services_data($user_id) {
        if (!class_exists('\MoBooking\Services\ServicesManager')) {
            return array();
        }
        
        $services_manager = new \MoBooking\Services\ServicesManager();
        return $services_manager->get_user_services($user_id);
    }
    
    /**
     * Get user areas data for export
     */
    private function get_user_areas_data($user_id) {
        if (!class_exists('\MoBooking\Geography\Manager')) {
            return array();
        }
        
        $geography_manager = new \MoBooking\Geography\Manager();
        return $geography_manager->get_user_areas($user_id);
    }
    
    /**
     * Get user bookings data for export
     */
    private function get_user_bookings_data($user_id) {
        if (!class_exists('\MoBooking\Bookings\Manager')) {
            return array();
        }
        
        $bookings_manager = new \MoBooking\Bookings\Manager();
        return $bookings_manager->get_user_bookings($user_id, array('limit' => -1));
    }
    
    /**
     * Get user discounts data for export
     */
    private function get_user_discounts_data($user_id) {
        if (!class_exists('\MoBooking\Discounts\Manager')) {
            return array();
        }
        
        // Assuming you have a discounts manager
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ));
    }
}

// Initialize the Settings AJAX Manager
new SettingsAjaxManager();