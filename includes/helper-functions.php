<?php
/**
 * MoBooking Helper Functions
 * Utility functions used throughout the theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if current page is dashboard
 */
function is_dashboard_page() {
    global $wp_query;
    return isset($wp_query->query['pagename']) && $wp_query->query['pagename'] === 'dashboard';
}

/**
 * Check if user can access dashboard
 */
function mobooking_user_can_access_dashboard($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }

    return in_array('mobooking_business_owner', $user->roles) ||
           in_array('administrator', $user->roles);
}

/**
 * Get user's subscription status
 */
function mobooking_get_user_subscription_status($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return array(
            'has_subscription' => false,
            'type' => '',
            'expiry' => '',
            'is_expired' => false,
            'is_active' => false
        );
    }

    $has_subscription = get_user_meta($user_id, 'mobooking_has_subscription', true);
    $subscription_type = get_user_meta($user_id, 'mobooking_subscription_type', true);
    $subscription_expiry = get_user_meta($user_id, 'mobooking_subscription_expiry', true);

    $is_expired = false;
    if (!empty($subscription_expiry)) {
        // If there's an expiry date, check it.
        $is_expired = strtotime($subscription_expiry) < time();
    } else {
        // No expiry date is set.
        // If there's also no 'has_subscription' flag, then it's not an active subscription.
        // If 'has_subscription' is true and no expiry date, it's a non-expiring subscription.
        if (!$has_subscription) {
            $is_expired = true;
        }
        // if $has_subscription is true and no expiry, $is_expired remains false (non-expiring active sub)
    }

    $is_active = (bool) $has_subscription && !$is_expired;

    return array(
        'has_subscription' => (bool) $has_subscription,
        'type' => $subscription_type ?: 'free',
        'expiry' => $subscription_expiry ?: '',
        'is_expired' => $is_expired,
        'is_active' => $is_active
    );
}

/**
 * Format price with currency
 */
function mobooking_format_price($amount, $currency = 'USD') {
    if (function_exists('wc_price')) {
        return wc_price($amount);
    }
    
    $currency_symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'C$',
        'AUD' => 'A$',
        'JPY' => '¥',
    );
    
    $symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : '$';
    return $symbol . number_format($amount, 2);
}

/**
 * Get formatted duration
 */
function mobooking_format_duration($minutes) {
    if ($minutes < 60) {
        return sprintf(_n('%d minute', '%d minutes', $minutes, 'mobooking'), $minutes);
    }
    
    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    if ($remaining_minutes === 0) {
        return sprintf(_n('%d hour', '%d hours', $hours, 'mobooking'), $hours);
    }
    
    return sprintf(
        __('%d hours %d minutes', 'mobooking'),
        $hours,
        $remaining_minutes
    );
}

/**
 * Get service status label
 */
function mobooking_get_service_status_label($status) {
    $statuses = array(
        'active' => __('Active', 'mobooking'),
        'inactive' => __('Inactive', 'mobooking'),
        'draft' => __('Draft', 'mobooking'),
        'archived' => __('Archived', 'mobooking'),
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * Get booking status label
 */
function mobooking_get_booking_status_label($status) {
    $statuses = array(
        'pending' => __('Pending', 'mobooking'),
        'confirmed' => __('Confirmed', 'mobooking'),
        'completed' => __('Completed', 'mobooking'),
        'cancelled' => __('Cancelled', 'mobooking'),
        'rescheduled' => __('Rescheduled', 'mobooking'),
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * Get service category options
 */
function mobooking_get_service_categories() {
    return array(
        'residential' => __('Residential', 'mobooking'),
        'commercial' => __('Commercial', 'mobooking'),
        'industrial' => __('Industrial', 'mobooking'),
        'specialized' => __('Specialized', 'mobooking'),
        'emergency' => __('Emergency', 'mobooking'),
        'maintenance' => __('Maintenance', 'mobooking'),
    );
}

/**
 * Get dashboard menu items
 */
function mobooking_get_dashboard_menu_items() {
    return array(
        'overview' => array(
            'title' => __('Dashboard', 'mobooking'),
            'icon' => 'dashicons-dashboard',
            'capability' => 'mobooking_business_owner',
        ),
        'bookings' => array(
            'title' => __('Bookings', 'mobooking'),
            'icon' => 'dashicons-calendar-alt',
            'capability' => 'mobooking_business_owner',
        ),
        'services' => array(
            'title' => __('Services', 'mobooking'),
            'icon' => 'dashicons-admin-tools',
            'capability' => 'mobooking_business_owner',
        ),
        'booking-form' => array(
            'title' => __('Booking Form', 'mobooking'),
            'icon' => 'dashicons-feedback',
            'capability' => 'mobooking_business_owner',
        ),
        'discounts' => array(
            'title' => __('Discount Codes', 'mobooking'),
            'icon' => 'dashicons-tag',
            'capability' => 'mobooking_business_owner',
        ),
        'areas' => array(
            'title' => __('Service Areas', 'mobooking'),
            'icon' => 'dashicons-location-alt',
            'capability' => 'mobooking_business_owner',
        ),
        'settings' => array(
            'title' => __('Settings', 'mobooking'),
            'icon' => 'dashicons-admin-generic',
            'capability' => 'mobooking_business_owner',
        ),
    );
}

/**
 * Sanitize hex color
 */
function mobooking_sanitize_hex_color($color) {
    if ('' === $color) {
        return '';
    }

    // 3 or 6 hex digits, or the empty string.
    if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
        return $color;
    }

    return '';
}

/**
 * Get user's business settings
 */
function mobooking_get_user_business_settings($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return array();
    }

    // Try to get from settings table first
    if (class_exists('\MoBooking\Database\SettingsManager')) {
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($user_id);
        
        if ($settings) {
            return (array) $settings;
        }
    }

    // Fallback to user meta
    return array(
        'company_name' => get_user_meta($user_id, 'mobooking_company_name', true),
        'phone' => get_user_meta($user_id, 'mobooking_phone', true),
        'primary_color' => get_user_meta($user_id, 'mobooking_primary_color', true) ?: '#4CAF50',
        'logo_url' => get_user_meta($user_id, 'mobooking_logo_url', true),
        'business_email' => get_user_meta($user_id, 'mobooking_business_email', true),
        'business_address' => get_user_meta($user_id, 'mobooking_business_address', true),
    );
}

/**
 * Get available time slots
 */
function mobooking_get_available_time_slots($date = null, $duration = 60) {
    if (!$date) {
        $date = date('Y-m-d');
    }

    $slots = array();
    $start_hour = 9; // 9 AM
    $end_hour = 17;  // 5 PM
    $interval = 30;  // 30 minutes

    for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += $interval) {
            $time = sprintf('%02d:%02d', $hour, $minute);
            $display_time = date('g:i A', strtotime($time));
            
            $slots[] = array(
                'value' => $time,
                'label' => $display_time,
                'available' => true // TODO: Check against existing bookings
            );
        }
    }

    return $slots;
}

/**
 * Generate booking reference number
 */
function mobooking_generate_booking_reference($booking_id = null) {
    if ($booking_id) {
        return 'MB-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    }
    
    return 'MB-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
}

/**
 * Check if ZIP code is valid
 */
function mobooking_is_valid_zip_code($zip_code, $country = 'US') {
    $patterns = array(
        'US' => '/^\d{5}(-\d{4})?$/',
        'CA' => '/^[A-Z]\d[A-Z] \d[A-Z]\d$/',
        'UK' => '/^[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}$/i',
    );
    
    $pattern = isset($patterns[$country]) ? $patterns[$country] : $patterns['US'];
    return preg_match($pattern, $zip_code);
}

/**
 * Get days of week
 */
function mobooking_get_days_of_week() {
    return array(
        'monday' => __('Monday', 'mobooking'),
        'tuesday' => __('Tuesday', 'mobooking'),
        'wednesday' => __('Wednesday', 'mobooking'),
        'thursday' => __('Thursday', 'mobooking'),
        'friday' => __('Friday', 'mobooking'),
        'saturday' => __('Saturday', 'mobooking'),
        'sunday' => __('Sunday', 'mobooking'),
    );
}

/**
 * Get business hours for a user
 */
function mobooking_get_user_business_hours($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $default_hours = array();
    $days = mobooking_get_days_of_week();
    
    foreach ($days as $day => $label) {
        $default_hours[$day] = array(
            'open' => in_array($day, ['saturday', 'sunday']) ? false : true,
            'start' => '09:00',
            'end' => '17:00'
        );
    }

    $business_hours = get_user_meta($user_id, 'business_hours', true);
    
    if (is_array($business_hours)) {
        return array_merge($default_hours, $business_hours);
    }
    
    return $default_hours;
}

/**
 * Get service option types
 */
function mobooking_get_service_option_types() {
    return array(
        'checkbox' => array(
            'label' => __('Checkbox', 'mobooking'),
            'description' => __('Yes/No option', 'mobooking'),
            'icon' => 'dashicons-yes-alt'
        ),
        'text' => array(
            'label' => __('Text Input', 'mobooking'),
            'description' => __('Single line text', 'mobooking'),
            'icon' => 'dashicons-edit'
        ),
        'textarea' => array(
            'label' => __('Text Area', 'mobooking'),
            'description' => __('Multi-line text', 'mobooking'),
            'icon' => 'dashicons-text'
        ),
        'number' => array(
            'label' => __('Number', 'mobooking'),
            'description' => __('Numeric input', 'mobooking'),
            'icon' => 'dashicons-calculator'
        ),
        'quantity' => array(
            'label' => __('Quantity', 'mobooking'),
            'description' => __('Quantity selector', 'mobooking'),
            'icon' => 'dashicons-plus-alt2'
        ),
        'select' => array(
            'label' => __('Dropdown', 'mobooking'),
            'description' => __('Select from options', 'mobooking'),
            'icon' => 'dashicons-arrow-down-alt2'
        ),
        'radio' => array(
            'label' => __('Radio Buttons', 'mobooking'),
            'description' => __('Choose one option', 'mobooking'),
            'icon' => 'dashicons-marker'
        ),
    );
}

/**
 * Format bytes to human readable format
 */
function mobooking_format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get current user's company name
 */
function mobooking_get_current_user_company_name() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return '';
    }
    
    $settings = mobooking_get_user_business_settings($user_id);
    
    if (!empty($settings['company_name'])) {
        return $settings['company_name'];
    }
    
    $user = get_userdata($user_id);
    return $user ? $user->display_name . "'s Business" : '';
}

/**
 * Generate nonce for AJAX requests
 */
function mobooking_get_ajax_nonce($action = 'mobooking-nonce') {
    return wp_create_nonce($action);
}

/**
 * Verify AJAX nonce
 */
function mobooking_verify_ajax_nonce($nonce, $action = 'mobooking-nonce') {
    return wp_verify_nonce($nonce, $action);
}

/**
 * Get plugin version for cache busting
 */
function mobooking_get_version() {
    return defined('MOBOOKING_VERSION') ? MOBOOKING_VERSION : '1.0.0';
}

/**
 * Check if user is on mobile device
 */
function mobooking_is_mobile() {
    return wp_is_mobile();
}

/**
 * Get avatar URL for user
 */
function mobooking_get_user_avatar_url($user_id = null, $size = 96) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return get_avatar_url($user_id, array('size' => $size));
}

/**
 * Log debug message
 */
function mobooking_log($message, $type = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = sprintf('[MoBooking] [%s] %s', strtoupper($type), $message);
        error_log($log_message);
    }
}

/**
 * Get allowed HTML for wp_kses
 */
function mobooking_get_allowed_html() {
    return array(
        'div' => array('class' => array(), 'id' => array(), 'style' => array()),
        'span' => array('class' => array(), 'id' => array(), 'style' => array()),
        'p' => array('class' => array(), 'style' => array()),
        'h1' => array('class' => array(), 'style' => array()),
        'h2' => array('class' => array(), 'style' => array()),
        'h3' => array('class' => array(), 'style' => array()),
        'h4' => array('class' => array(), 'style' => array()),
        'h5' => array('class' => array(), 'style' => array()),
        'h6' => array('class' => array(), 'style' => array()),
        'strong' => array(),
        'em' => array(),
        'b' => array(),
        'i' => array(),
        'u' => array(),
        'br' => array(),
        'a' => array('href' => array(), 'title' => array(), 'target' => array()),
        'ul' => array('class' => array()),
        'ol' => array('class' => array()),
        'li' => array('class' => array()),
        'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'width' => array(), 'height' => array()),
        'table' => array('class' => array()),
        'tr' => array('class' => array()),
        'td' => array('class' => array(), 'colspan' => array()),
        'th' => array('class' => array(), 'colspan' => array()),
    );
}

/**
 * Sanitize HTML content
 */
function mobooking_sanitize_html($content) {
    return wp_kses($content, mobooking_get_allowed_html());
}

/**
 * Get file extension from URL
 */
function mobooking_get_file_extension($url) {
    $path = parse_url($url, PHP_URL_PATH);
    return pathinfo($path, PATHINFO_EXTENSION);
}

/**
 * Check if file is an image
 */
function mobooking_is_image($file_url) {
    $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
    $extension = strtolower(mobooking_get_file_extension($file_url));
    return in_array($extension, $image_extensions);
}

/**
 * Generate random string
 */
function mobooking_generate_random_string($length = 10) {
    return wp_generate_password($length, false);
}

/**
 * Get timezone string
 */
function mobooking_get_timezone_string() {
    $timezone_string = get_option('timezone_string');
    
    if (!empty($timezone_string)) {
        return $timezone_string;
    }
    
    $offset = get_option('gmt_offset');
    $hours = (int) $offset;
    $minutes = ($offset - $hours);
    
    $sign = ($offset < 0) ? '-' : '+';
    $abs_hour = abs($hours);
    $abs_mins = abs($minutes * 60);
    
    return sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);
}

/**
 * Convert time to user timezone
 */
function mobooking_convert_to_user_timezone($datetime, $format = 'Y-m-d H:i:s') {
    $timezone = mobooking_get_timezone_string();
    $date = new DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone($timezone));
    return $date->format($format);
}

/**
 * Get WordPress admin URL with fallback
 */
function mobooking_get_admin_url($path = '') {
    return admin_url($path);
}

/**
 * Check if current request is AJAX
 */
function mobooking_is_ajax_request() {
    return wp_doing_ajax();
}

/**
 * Get current page URL
 */
function mobooking_get_current_url() {
    $protocol = is_ssl() ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Truncate text with ellipsis
 */
function mobooking_truncate_text($text, $length = 100, $ending = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length - strlen($ending)) . $ending;
}

/**
 * Clean phone number for storage
 */
function mobooking_clean_phone_number($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Format phone number for display
 */
function mobooking_format_phone_number($phone) {
    $cleaned = mobooking_clean_phone_number($phone);
    
    // US phone number format
    if (strlen($cleaned) === 10) {
        return sprintf('(%s) %s-%s', 
            substr($cleaned, 0, 3),
            substr($cleaned, 3, 3),
            substr($cleaned, 6, 4)
        );
    }
    
    // International format (basic)
    if (strlen($cleaned) > 10 && substr($cleaned, 0, 1) === '+') {
        return $cleaned;
    }
    
    return $phone; // Return original if can't format
}