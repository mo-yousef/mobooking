<?php
/**
 * Custom rewrite rules.
 */

/**
 * Add custom rewrite rules for the dashboard sections and query-based redirects.
 */
function mobooking_custom_rewrite_rules() {
    // Existing rule for path-based sections (e.g., /dashboard/services/)
    add_rewrite_tag('%section%', '([^&]+)'); // Makes 'section' a query var for WP_Query
    add_rewrite_rule('^dashboard/([^/]*)/?$', 'index.php?pagename=dashboard&section=$matches[1]', 'top');

    // Rule for /dashboard/ or /dashboard (to default to overview)
    // This might be handled by dashboard/index.php itself, but an explicit rule can be good.
    // However, since page-overview.php is now the default in dashboard/index.php, this specific rule might not be strictly necessary
    // if /dashboard/ correctly loads dashboard/index.php.
    // add_rewrite_rule('^dashboard/?$', 'index.php?pagename=dashboard&section=overview', 'top');

    // Rule for query parameter based sections (e.g., /dashboard?section=services or /dashboard/?section=services)
    // is now handled directly by dashboard/index.php using $_GET['section']
    // No specific rewrite rule is needed here for that, as WordPress will pass query vars.
}
add_action('init', 'mobooking_custom_rewrite_rules', 10, 0); // Added priority

/**
 * Register custom query variables.
 */
function mobooking_register_query_vars($vars) {
    $vars[] = 'section'; // 'section' is used by the main dashboard page template
    // $vars[] = 'mob_page_redirect'; // Removed
    return $vars;
}
add_filter('query_vars', 'mobooking_register_query_vars');

/**
 * Sets up global variables and managers needed for dashboard pages.
 * @return bool True on success, false if user not logged in (or other critical failure).
 */
function mobooking_setup_dashboard_globals() {
    global $current_user, $user_id, $settings, // Added $settings
           $bookings_manager, $services_manager, $geography_manager,
           $settings_manager, $discounts_manager, $booking_form_manager;

    if (!is_user_logged_in()) {
        // Redirect to login if trying to access dashboard pages while logged out
        // Consider checking if is_admin() is false to avoid issues in admin context if this runs too early
        if (!is_admin()) {
            wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
            exit;
        }
        return false;
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Initialize managers - using $GLOBALS to ensure they are truly global
    // for the included page-*.php files.
    if (class_exists('\MoBooking\Database\SettingsManager') && !isset($GLOBALS['settings_manager'])) {
        $GLOBALS['settings_manager'] = new \MoBooking\Database\SettingsManager();
    }
    // Ensure $settings is populated after SettingsManager is available
    if (isset($GLOBALS['settings_manager']) && is_callable([$GLOBALS['settings_manager'], 'get_settings']) && !isset($settings)) {
        $settings = $GLOBALS['settings_manager']->get_settings($user_id);
        $GLOBALS['settings'] = $settings; // Also ensure $settings is in $GLOBALS if page-*.php files expect it globally
    }


    if (class_exists('\MoBooking\Bookings\Manager') && !isset($GLOBALS['bookings_manager'])) {
        $GLOBALS['bookings_manager'] = new \MoBooking\Bookings\Manager();
    }
    if (class_exists('\MoBooking\Services\ServicesManager') && !isset($GLOBALS['services_manager'])) {
        $GLOBALS['services_manager'] = new \MoBooking\Services\ServicesManager();
    }
    if (class_exists('\MoBooking\Geography\Manager') && !isset($GLOBALS['geography_manager'])) {
        $GLOBALS['geography_manager'] = new \MoBooking\Geography\Manager();
    }
    if (class_exists('\MoBooking\Discounts\Manager') && !isset($GLOBALS['discounts_manager'])) {
        $GLOBALS['discounts_manager'] = new \MoBooking\Discounts\Manager();
    }
    if (class_exists('\MoBooking\BookingForm\BookingFormManager') && !isset($GLOBALS['booking_form_manager'])) {
        $GLOBALS['booking_form_manager'] = new \MoBooking\BookingForm\BookingFormManager();
    }

    // Ensure local variables are also set for direct use in this function's scope if needed later
    // and for any files included directly by this handler that might not use $GLOBALS.
    if(isset($GLOBALS['settings_manager'])) $settings_manager = $GLOBALS['settings_manager'];
    if(isset($GLOBALS['bookings_manager'])) $bookings_manager = $GLOBALS['bookings_manager'];
    if(isset($GLOBALS['services_manager'])) $services_manager = $GLOBALS['services_manager'];
    if(isset($GLOBALS['geography_manager'])) $geography_manager = $GLOBALS['geography_manager'];
    if(isset($GLOBALS['discounts_manager'])) $discounts_manager = $GLOBALS['discounts_manager'];
    if(isset($GLOBALS['booking_form_manager'])) $booking_form_manager = $GLOBALS['booking_form_manager'];


    return true;
}


// Removed mobooking_handle_page_redirect function and its add_action hook.
// The functionality of loading sections based on a query variable is now
// handled directly within dashboard/index.php using $_GET['section'],
// and mobooking_setup_dashboard_globals() is called from there.

/**
 * Flush rewrite rules on theme activation.
 * It's recommended to visit the Permalinks settings page in WP Admin
 * if you manually change rewrite rules to ensure they are applied.
 */
function mobooking_flush_rewrite_rules() {
    // Call mobooking_custom_rewrite_rules to ensure rules are added before flushing
    mobooking_custom_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules');
