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

    // New rule for query parameter based sections (e.g., /dashboard?section=services or /dashboard/?section=services)
    // This will internally rewrite to index.php with a custom query var `mob_page_redirect`
    add_rewrite_rule('^dashboard(?:/)?\?section=([^&]+)', 'index.php?mob_page_redirect=$matches[1]', 'top');
}
add_action('init', 'mobooking_custom_rewrite_rules', 10, 0); // Added priority

/**
 * Register custom query variables.
 */
function mobooking_register_query_vars($vars) {
    $vars[] = 'section'; // Already handled by add_rewrite_tag for WP_Query, but good for filter.
    $vars[] = 'mob_page_redirect';
    return $vars;
}
add_filter('query_vars', 'mobooking_register_query_vars');

/**
 * Handle the actual redirection or content loading for mob_page_redirect.
 */
function mobooking_handle_page_redirect() {
    $redirect_section = get_query_var('mob_page_redirect');

    if ($redirect_section) {
        $file_name = 'page-' . sanitize_key($redirect_section) . '.php';
        $file_path = MOBOOKING_PATH . '/' . $file_name; // Files are in the root

        if (file_exists($file_path)) {
            // Before including, ensure necessary global variables are available if these files expect them
            // For example, if they need WordPress user context:
            global $current_user, $user_id;
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $user_id = $current_user->ID;
            }

            // If these page-*.php files expect to be within the dashboard's visual structure,
            // dashboard/header.php and dashboard/footer.php (and sidebar) should be included here.
            // This mimics how dashboard/index.php would have loaded them.

            // Check if header/sidebar/footer are needed based on how page-*.php files are structured.
            // For now, assuming they might need the dashboard context.

            $dashboard_header_path = MOBOOKING_PATH . '/dashboard/header.php';
            if (file_exists($dashboard_header_path)) {
                include $dashboard_header_path;
            }

            // It's assumed page-*.php files might need $current_section to be set.
            // For direct loading via mob_page_redirect, $current_section should match $redirect_section.
            $current_section = $redirect_section;


            // Initialize managers if they are used directly in page-*.php files
            // This mirrors what was done in dashboard/index.php for overview
            // These should ideally be singletons or managed more centrally in a real app.
            if (!isset($GLOBALS['bookings_manager'])) {
                 $GLOBALS['bookings_manager'] = new \MoBooking\Bookings\Manager();
            }
            if (!isset($GLOBALS['services_manager'])) {
                 $GLOBALS['services_manager'] = new \MoBooking\Services\ServicesManager();
            }
            if (!isset($GLOBALS['geography_manager'])) {
                 $GLOBALS['geography_manager'] = new \MoBooking\Geography\Manager();
            }
            if (!isset($GLOBALS['settings_manager'])) {
                 $GLOBALS['settings_manager'] = new \MoBooking\Database\SettingsManager();
                 // $settings might also be needed if page-*.php uses it directly.
                 // $GLOBALS['settings'] = $GLOBALS['settings_manager']->get_settings($user_id);
            }


            include $file_path;

            $dashboard_footer_path = MOBOOKING_PATH . '/dashboard/footer.php';
            if (file_exists($dashboard_footer_path)) {
                include $dashboard_footer_path;
            }
            exit; // Important to stop WordPress from loading the original query's template
        } else {
            // File not found, redirect to main dashboard or a 404 page
            // For now, redirect to the main dashboard page
            wp_redirect(home_url('/dashboard/'));
            exit;
        }
    }
}
add_action('template_redirect', 'mobooking_handle_page_redirect');

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
