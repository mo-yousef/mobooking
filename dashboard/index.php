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




// Get current section - lightweight
$current_section = get_query_var('section', 'overview');

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
            // NOW load heavy managers AFTER layout is rendered
            $managers_loaded = false;
            $bookings_manager = null;
            $services_manager = null;
            $geography_manager = null;
            
            try {
                // Only load what the current section actually needs
                switch ($current_section) {
                    case 'bookings':
                        $bookings_manager = new \MoBooking\Bookings\Manager();
                        break;
                    case 'services':
                        $services_manager = new \MoBooking\Services\ServicesManager();
                        break;
                    case 'areas':
                        $geography_manager = new \MoBooking\Geography\Manager();
                        break;
                    case 'overview':
                        // Load all for overview, but after layout
                        $bookings_manager = new \MoBooking\Bookings\Manager();
                        $services_manager = new \MoBooking\Services\ServicesManager();
                        $geography_manager = new \MoBooking\Geography\Manager();
                        break;
                }
                
                // Load settings manager only when needed
                if (in_array($current_section, ['settings', 'overview'])) {
                    $settings_manager = new \MoBooking\Database\SettingsManager();
                    $settings = $settings_manager->get_settings($user_id);
                }
                
                $managers_loaded = true;
                
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Error loading managers: ' . $e->getMessage());
                }
                $managers_loaded = false;
            }
            
            // Load the appropriate section
            $section_loaded = false;
            $error_during_section_load = false; // New flag
            $allowed_sections = array('overview', 'services', 'bookings', 'booking-form', 'discounts', 'areas', 'settings');
            
            if (in_array($current_section, $allowed_sections)) {
                $section_file = MOBOOKING_PATH . '/dashboard/sections/' . $current_section . '.php';
                
                if (file_exists($section_file)) {
                    try {
                        include $section_file;
                        $section_loaded = true;
                    } catch (Throwable $e) { // Changed to Throwable
                        $error_during_section_load = true; // Set flag
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('MoBooking: Error loading section ' . $current_section . ': ' . $e->getMessage());
                        }
                        // Updated error message
                        echo '<div class="mobooking-error-message">
       <h3>' . __('Section Temporarily Unavailable', 'mobooking') . '</h3>
       <p>' . __('An unexpected error occurred while trying to load this section. Please try again later or contact support if the issue persists.', 'mobooking') . '</p>';
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            echo '<pre>' . esc_html($e->getMessage()) . '</pre>';
                        }
                        echo '</div>';
                    }
                } else {
                    // File doesn't exist, section_loaded remains false
                     if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Section file not found: ' . $section_file);
                    }
                }
            }
            
            // Fallback if section couldn't be loaded and no error occurred during an attempted load
            if (!$section_loaded && !$error_during_section_load) {
                // Updated fallback content
                echo '<div class="mobooking-fallback-content">
       <h2>' . __('Dashboard Section Not Found', 'mobooking') . '</h2>
       <p>' . sprintf(__('The requested dashboard section "%s" is not valid or could not be loaded. Please select a valid section from the menu.', 'mobooking'), esc_html($current_section)) . '</p>
       <div class="mobooking-quick-links">
           <a href="' . esc_url(remove_query_arg('section')) . '" class="mobooking-btn-primary">
               ' . __('Go to Main Dashboard', 'mobooking') . '
           </a>
       </div>
   </div>';
            }
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