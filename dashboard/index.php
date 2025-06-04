<?php
/**
 * Dashboard Main Template - Simple Layout Shift Fix
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

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
            $allowed_sections = array('overview', 'services', 'bookings', 'booking-form', 'discounts', 'areas', 'settings');
            
            if (in_array($current_section, $allowed_sections)) {
                $section_file = MOBOOKING_PATH . '/dashboard/sections/' . $current_section . '.php';
                
                if (file_exists($section_file)) {
                    try {
                        include $section_file;
                        $section_loaded = true;
                    } catch (Exception $e) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('MoBooking: Error loading section ' . $current_section . ': ' . $e->getMessage());
                        }
                        echo '<div class="error-message">
                            <h3>' . __('Section temporarily unavailable', 'mobooking') . '</h3>
                            <p>' . __('Please try again later.', 'mobooking') . '</p>
                        </div>';
                    }
                }
            }
            
            // Fallback if section couldn't be loaded
            if (!$section_loaded) {
                echo '<div class="fallback-content">
                    <h2>' . __('Dashboard', 'mobooking') . '</h2>
                    <p>' . __('Welcome to your MoBooking dashboard.', 'mobooking') . '</p>
                    <div class="quick-links">
                        <a href="' . esc_url(add_query_arg('section', 'services')) . '" class="btn-primary">
                            ' . __('Manage Services', 'mobooking') . '
                        </a>
                        <a href="' . esc_url(add_query_arg('section', 'bookings')) . '" class="btn-secondary">
                            ' . __('View Bookings', 'mobooking') . '
                        </a>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>