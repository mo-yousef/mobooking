<?php
// Temporary error reporting for diagnostics - uncomment to use
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// ---- End of temporary error reporting

/**
 * Template Name: Dashboard
 * Dashboard Main Template - Simple Layout Shift Fix
 */
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();
ob_start();

// Quick auth check without heavy loading
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode(home_url('/dashboard/'))));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Quick permission check
if (!in_array('mobooking_business_owner', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
    wp_redirect(home_url('/?error=permission_denied'));
    exit;
}




global $wp_query; // Ensure $wp_query is available

// Get current section
$current_section = 'overview'; // Default

if (!empty($_GET['section'])) {
    $current_section = sanitize_text_field($_GET['section']);
} elseif (!empty($wp_query->query_vars['section'])) {
    $current_section = sanitize_text_field($wp_query->query_vars['section']);
}

// Further sanitize: remove any backslashes that might have been manually added or passed
$current_section = str_replace('\\', '', $current_section); // Keep this sanitization

// Settings are now loaded via mobooking_setup_dashboard_globals() before section content.

// MOVED: Call mobooking_setup_dashboard_globals() here to ensure all managers and $settings are loaded
// before any dashboard HTML (including sidebar and header) is rendered.
if (function_exists('mobooking_setup_dashboard_globals')) {
    if (!mobooking_setup_dashboard_globals()) {
        // The function returned false, likely due to user not being logged in
        // and already handled a redirect. Or some other critical setup failure.
        // We should probably exit here if it returns false, as it implies a redirect or failure.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Info: mobooking_setup_dashboard_globals() returned false. Halting further dashboard rendering in template.');
        }
        // Depending on how mobooking_setup_dashboard_globals handles failed auth (e.g. if it always exits),
        // this exit might be redundant or a safeguard.
        exit;
    }
} else {
    // Fallback or error if the crucial setup function is missing
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking Error: mobooking_setup_dashboard_globals() function not found. Dashboard cannot be properly rendered.');
    }
    // Display a user-friendly error and exit, as the dashboard will be broken.
    wp_die(__('A critical setup function is missing. The dashboard cannot be displayed. Please contact support.', 'mobooking'));
    exit;
}
?>

<!-- RENDER LAYOUT IMMEDIATELY -->
<div class="mobooking-dashboard-container">
    <?php 
    // Load sidebar immediately - it's lightweight
    $sidebar_path = MOBOOKING_PATH . '/dashboard/sidebar.php';
    if (file_exists($sidebar_path)) {
        include $sidebar_path;
    }
    ?>
    
    <div class="mobooking-dashboard-main">
        <?php 
        // Load header immediately
        $header_path = MOBOOKING_PATH . '/dashboard/header.php';
        if (file_exists($header_path)) {
            include $header_path;
        }
        ?>
        
        <div class="dashboard-content">
            <?php
            // Dynamically load the section content
            $section_file_name = 'page-' . $current_section . '.php';
            $section_file_path = MOBOOKING_PATH . '/' . $section_file_name;

            if (file_exists($section_file_path)) {
                // Managers and $settings are already loaded globally now by the call
                // to mobooking_setup_dashboard_globals() near the top of this file.
                include $section_file_path;

            } else {
                // Fallback if the specific section page (e.g., page-services.php) is missing
                echo '<div class="mobooking-fallback-content">
<h2>' . sprintf(__('Section Not Found: %s', 'mobooking'), esc_html($current_section)) . '</h2>
<p>' . __('The requested dashboard section could not be loaded. Please ensure all plugin/theme files are correctly installed or contact support.', 'mobooking') . '</p>
</div>';
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Section file not found: ' . $section_file_path);
                }
            }
        </div>
    </div>
</div>

<?php
    if (empty(ob_get_contents()) && !headers_sent() && isset($section_loaded) && isset($error_during_section_load) && !$section_loaded && !$error_during_section_load) {
        // This condition suggests that no section was loaded, no specific error was caught during a section load attempt,
        // and the output buffer for the main content area is still empty.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<div class="mobooking-error-message" style="padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin: 20px;">
                   <h3>' . __('Critical Display Error Detected', 'mobooking') . '</h3>
                   <p>' . __('The dashboard content area is unexpectedly empty. No specific section loading errors were reported by the template. This may indicate a fatal PHP error occurring before content generation, an issue with WordPress hooks, or a problem with the base theme structure (e.g., header/footer). Please check PHP error logs for details.', 'mobooking') . '</p>
                   <p><i>Diagnostic: Main output buffer was empty prior to get_footer() and no section reported as loaded or errored during its specific load attempt.</i></p>
                   </div>';
        } else {
            // Generic message for non-debug environments
            echo '<div class="mobooking-error-message" style="padding: 20px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; margin: 20px;">
                   <h3>' . __('Content Currently Unavailable', 'mobooking') . '</h3>
                   <p>' . __('The dashboard content could not be displayed at this moment. Please try refreshing the page, or contact support if the issue persists.', 'mobooking') . '</p>
                   </div>';
        }
    }
    ob_end_flush(); // Flush the main content output buffer
?>
<?php get_footer(); ?>