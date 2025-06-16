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

// Quick settings load - only what's needed for layout
$settings = (object) array(
    'company_name' => get_user_meta($user_id, 'mobooking_company_name', true) ?: $current_user->display_name . "'s Business",
    'primary_color' => '#4CAF50',
    'logo_url' => '',
    'phone' => '',
    'email_header' => '',
    'email_footer' => '',
    'terms_conditions' => '',
    'booking_confirmation_message' => 'Thank you for your booking.'
);
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
            <?php
            // Include page-overview.php by default
            $overview_file = MOBOOKING_PATH . '/page-overview.php';
            if (file_exists($overview_file)) {
                // Initialize managers needed for overview.php, if not already done
                // This ensures that variables like $bookings_manager are available in page-overview.php
                if (!isset($bookings_manager)) {
                    $bookings_manager = new \MoBooking\Bookings\Manager();
                }
                if (!isset($services_manager)) {
                    $services_manager = new \MoBooking\Services\ServicesManager();
                }
                if (!isset($geography_manager)) {
                    $geography_manager = new \MoBooking\Geography\Manager();
                }
                if(!isset($settings_manager)) {
                    $settings_manager = new \MoBooking\Database\SettingsManager();
                    // $settings is already defined above, but if overview needs its own, adjust here
                    // For now, we assume $settings from above is sufficient or page-overview.php handles its own settings if needed.
                }
                include $overview_file;
            } else {
                // Fallback if page-overview.php is missing
                echo '<div class="mobooking-fallback-content">
       <h2>' . __('Welcome to your Dashboard', 'mobooking') . '</h2>
       <p>' . __('The main overview page could not be loaded. Please ensure all plugin files are correctly installed.', 'mobooking') . '</p>
       </div>';
                 if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: page-overview.php file not found at: ' . $overview_file);
                }
            }
            ?>
            ?>
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