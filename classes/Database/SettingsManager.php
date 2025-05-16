<?php
namespace MoBooking\Database;

/**
 * Settings Manager class
 */
class SettingsManager {
    /**
     * Get settings for a user
     */
    public function get_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_settings';
        
        $settings = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if (!$settings) {
            return $this->get_default_settings($user_id);
        }
        
        return $settings;
    }
    
    /**
     * Create default settings for a new user
     */
    public function create_default_settings($user_id, $company_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_settings';
        
        // Check if settings already exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            return;
        }
        
        // Insert default settings
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'company_name' => $company_name,
                'primary_color' => '#4CAF50',
                'email_header' => $this->get_default_email_header(),
                'email_footer' => $this->get_default_email_footer(),
                'booking_confirmation_message' => $this->get_default_booking_confirmation_message(),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings($user_id) {
        $user = get_userdata($user_id);
        $company_name = get_user_meta($user_id, 'mobooking_company_name', true);
        
        if (!$company_name && $user) {
            $company_name = $user->display_name . '\'s Cleaning Service';
        }
        
        return (object) array(
            'user_id' => $user_id,
            'company_name' => $company_name,
            'primary_color' => '#4CAF50',
            'logo_url' => '',
            'phone' => '',
            'email_header' => $this->get_default_email_header(),
            'email_footer' => $this->get_default_email_footer(),
            'terms_conditions' => '',
            'booking_confirmation_message' => $this->get_default_booking_confirmation_message(),
        );
    }
    
    /**
     * Get default email header
     */
    private function get_default_email_header() {
        return '<div style="background-color: #f5f5f5; padding: 20px; text-align: center;">
            <h1 style="color: #4CAF50;">{{company_name}}</h1>
        </div>';
    }
    
    /**
     * Get default email footer
     */
    private function get_default_email_footer() {
        return '<div style="background-color: #f5f5f5; padding: 20px; text-align: center; margin-top: 20px;">
            <p>&copy; {{current_year}} {{company_name}}. All rights reserved.</p>
            <p>{{phone}} | {{email}}</p>
        </div>';
    }
    
    /**
     * Get default booking confirmation message
     */
    private function get_default_booking_confirmation_message() {
        return 'Thank you for booking with us. We have received your booking and will confirm it shortly.';
    }
    
    /**
     * Update settings
     */
    public function update_settings($user_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_settings';
        
        // Sanitize data
        $sanitized_data = array(
            'company_name' => sanitize_text_field($data['company_name']),
            'primary_color' => sanitize_hex_color($data['primary_color']),
            'phone' => sanitize_text_field($data['phone']),
            'logo_url' => esc_url_raw($data['logo_url']),
            'email_header' => wp_kses_post($data['email_header']),
            'email_footer' => wp_kses_post($data['email_footer']),
            'terms_conditions' => wp_kses_post($data['terms_conditions']),
            'booking_confirmation_message' => wp_kses_post($data['booking_confirmation_message']),
        );
        
        // Check if settings exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            // Update existing settings
            $wpdb->update(
                $table_name,
                $sanitized_data,
                array('user_id' => $user_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new settings
            $wpdb->insert(
                $table_name,
                array_merge($sanitized_data, array('user_id' => $user_id)),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
}