<?php
/**
 * Admin-related functions.
 */

/**
 * Admin notices for theme requirements
 */
function mobooking_admin_notices() {
    if (!current_user_can('administrator')) {
        return;
    }

    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>MoBooking:</strong> ';
        echo __('WooCommerce is recommended for payment processing. Please install and activate WooCommerce.', 'mobooking');
        echo '</p></div>';
    }
    
    // Check if database tables exist
    global $wpdb;
    $required_tables = array(
        'mobooking_services',
        'mobooking_service_options',
        'mobooking_bookings',
        'mobooking_areas',
        'mobooking_settings'
    );
    
    $missing_tables = array();
    foreach ($required_tables as $table) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>MoBooking:</strong> ';
        echo sprintf(__('Missing database tables: %s. Please deactivate and reactivate the theme.', 'mobooking'), implode(', ', $missing_tables));
        echo '</p></div>';
    }
}
add_action('admin_notices', 'mobooking_admin_notices');
