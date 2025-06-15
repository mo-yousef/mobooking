<?php
/**
 * Functions related to the dashboard.
 */

/**
 * Dashboard access control
 */
function mobooking_dashboard_access_control() {
    if (!is_dashboard_page()) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_redirect(home_url('/login/?redirect_to=' . urlencode(home_url('/dashboard/'))));
        exit;
    }

    if (!mobooking_user_can_access_dashboard()) {
        wp_redirect(home_url('/?error=access_denied'));
        exit;
    }
}
add_action('template_redirect', 'mobooking_dashboard_access_control');

/**
 * Admin Debug Utilities for MoBooking Role
 */

// 1. Dashboard Widget for Role Status
// function mobooking_debug_role_status() {
//     if (!current_user_can('administrator')) {
//         echo '<p>' . esc_html__('You do not have permission to view this information.', 'mobooking') . '</p>';
//         return;
//     }

//     echo '<h4>' . esc_html__('MoBooking Business Owner Role Status', 'mobooking') . '</h4>';
//     $role_name = 'mobooking_business_owner';
//     $role = get_role($role_name);

//     if ($role) {
//         echo '<p style="color: green;">' . sprintf(esc_html__('Role "%s" exists.', 'mobooking'), $role_name) . '</p>';
//         echo '<h5>' . esc_html__('Capabilities:', 'mobooking') . '</h5>';
//         echo '<ul>';
//         foreach ($role->capabilities as $cap => $value) {
//             if ($value) {
//                 echo '<li>' . esc_html($cap) . '</li>';
//             }
//         }
//         echo '</ul>';
//     } else {
//         echo '<p style="color: red;">' . sprintf(esc_html__('Role "%s" does NOT exist.', 'mobooking'), $role_name) . '</p>';
//         echo '<button id="mobooking-recreate-role-btn" class="button button-primary">' . esc_html__('Recreate Role', 'mobooking') . '</button>';
//         echo '<div id="mobooking-recreate-role-message" style="margin-top:10px;"></div>';
//     }

//     echo '<hr>';
//     echo '<h4>' . esc_html__('Auth Manager Class Status', 'mobooking') . '</h4>';
//     if (class_exists('\MoBooking\Auth\Manager')) {
//         echo '<p style="color: green;">' . esc_html__('\MoBooking\Auth\Manager class exists.', 'mobooking') . '</p>';
//     } else {
//         echo '<p style="color: red;">' . esc_html__('\MoBooking\Auth\Manager class does NOT exist.', 'mobooking') . '</p>';
//     }


// }
// add_action('wp_dashboard_setup', function() {
//     if (current_user_can('administrator')) {
//         wp_add_dashboard_widget(
//             'mobooking_role_debug_widget', // Changed ID to be more specific
//             __('MoBooking Role Status', 'mobooking'),
//             'mobooking_debug_role_status'
//         );
//     }
// });

/**
 * Enqueue and localize scripts for Areas Dashboard
 * Add this to your Geography Manager class constructor or call it from functions.php
 */
function mobooking_enqueue_areas_scripts() {
    // // Only load on areas page
    // if (!is_admin() || !isset($_GET['page']) || $_GET['page'] !== 'mobooking-areas') {
    //     return;
    // }
    
    // Enqueue the script (if you have a separate JS file)
    // wp_enqueue_script('mobooking-areas', get_template_directory_uri() . '/js/areas.js', array('jquery'), '1.0.0', true);
    
    // Get current user and country info
    $user_id = get_current_user_id();
    $selected_country = get_user_meta($user_id, 'mobooking_service_country', true);
    
    // Localize script with necessary data
    $localize_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-area-nonce'),
        'booking_nonce' => wp_create_nonce('mobooking-booking-nonce'),
        'current_country' => $selected_country,
        'user_id' => $user_id,
        'strings' => array(
            'loading' => __('Loading...', 'mobooking'),
            'error' => __('An error occurred', 'mobooking'),
            'success' => __('Success!', 'mobooking'),
            'confirm_delete' => __('Are you sure you want to delete this area?', 'mobooking'),
            'confirm_reset' => __('Are you sure? This will reset your country selection and remove all current areas.', 'mobooking'),
            'select_country' => __('Please select a country first.', 'mobooking'),
            'enter_city' => __('Please enter a city name', 'mobooking'),
            'network_error' => __('Network error occurred', 'mobooking'),
            'no_areas_found' => __('No areas found for this city', 'mobooking'),
            'select_areas' => __('Please select at least one area', 'mobooking'),
            'country_not_set' => __('Country not set. Please refresh the page.', 'mobooking'),
        )
    );
    
    // If using inline script (as in your current setup), output the variables
    ?>
    <script type="text/javascript">
        window.mobooking_area_vars = <?php echo json_encode($localize_data); ?>;
        window.ajax_object = window.mobooking_area_vars; // Backward compatibility
        
        // Also set current country globally
        window.mobooking_current_country = '<?php echo esc_js($selected_country); ?>';
    </script>
    <?php
}
add_action('admin_enqueue_scripts', 'mobooking_enqueue_areas_scripts');
