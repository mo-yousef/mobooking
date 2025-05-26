<?php
/**
 * MoBooking Theme Functions - Fixed Duplicate Script Loading
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('MOBOOKING_VERSION', '1.0.0');
define('MOBOOKING_PATH', get_template_directory());
define('MOBOOKING_URL', get_template_directory_uri());

/**
 * Enhanced autoloader for MoBooking classes
 */
spl_autoload_register(function ($class) {
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }

    $relative_class = str_replace('MoBooking\\', '', $class);
    $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Initialize the MoBooking theme
 */
function mobooking_init() {
    try {
        $loader = new MoBooking\Core\Loader();
        $loader->init();
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking initialization error: ' . $e->getMessage());
        }
    }
}
add_action('after_setup_theme', 'mobooking_init', 5);

/**
 * Theme setup function
 */
function mobooking_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption'
    ));

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'mobooking'),
        'footer' => __('Footer Menu', 'mobooking'),
    ));

    global $content_width;
    if (!isset($content_width)) {
        $content_width = 1200;
    }
}
add_action('after_setup_theme', 'mobooking_theme_setup');

/**
 * Enqueue styles and scripts
 */
function mobooking_enqueue_assets() {
    // Main theme stylesheet
    wp_enqueue_style(
        'mobooking-style',
        get_stylesheet_uri(),
        array(),
        MOBOOKING_VERSION
    );

    // Dashboard styles (only on dashboard pages)
    if (is_dashboard_page()) {
        wp_enqueue_style(
            'mobooking-dashboard',
            MOBOOKING_URL . '/assets/css/dashboard.css',
            array('dashicons'),
            MOBOOKING_VERSION
        );

        wp_enqueue_style(
            'mobooking-service-options',
            MOBOOKING_URL . '/assets/css/service-options.css',
            array('mobooking-dashboard'),
            MOBOOKING_VERSION
        );
        
        wp_enqueue_style(
            'mobooking-services-section',
            MOBOOKING_URL . '/assets/css/services-section.css',
            array('mobooking-dashboard'),
            MOBOOKING_VERSION
        );
    }

    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_assets');

/**
 * Enqueue dashboard-specific scripts - FIXED to prevent duplicates
 */
function mobooking_enqueue_dashboard_scripts() {
    if (!is_dashboard_page() || !is_user_logged_in()) {
        return;
    }

    // Load ONLY ONE JavaScript file to handle all dashboard functionality
    wp_enqueue_script(
        'mobooking-dashboard',
        MOBOOKING_URL . '/assets/js/dashboard.js',
        array('jquery', 'jquery-ui-sortable'),
        MOBOOKING_VERSION,
        true
    );

    // DO NOT load service-form-handler.js as it conflicts with dashboard.js
    // wp_enqueue_script('mobooking-service-form-handler', ...); // REMOVED

    // Localize dashboard scripts
    $dashboard_data = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'userId' => get_current_user_id(),
        'currentSection' => get_query_var('section', 'overview'),
        'nonces' => array(
            'service' => wp_create_nonce('mobooking-service-nonce'),
            'booking' => wp_create_nonce('mobooking-booking-nonce'),
            'area' => wp_create_nonce('mobooking-area-nonce'),
            'discount' => wp_create_nonce('mobooking-discount-nonce'),
        ),
        'currentServiceId' => isset($_GET['service_id']) ? absint($_GET['service_id']) : 0,
        'currentView' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list',
        'activeTab' => isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info',
        'strings' => array(
            'saving' => __('Saving...', 'mobooking'),
            'saved' => __('Saved successfully', 'mobooking'),
            'error' => __('Error occurred', 'mobooking'),
            'deleteConfirm' => __('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'),
        ),
    );

    wp_localize_script('mobooking-dashboard', 'mobookingDashboard', $dashboard_data);
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_dashboard_scripts', 15);

/**
 * Add custom body classes
 */
function mobooking_body_classes($classes) {
    if (is_user_logged_in()) {
        $classes[] = 'logged-in';
        
        $user = wp_get_current_user();
        if (!empty($user->roles)) {
            foreach ($user->roles as $role) {
                $classes[] = 'role-' . sanitize_html_class($role);
            }
        }
    }

    if (is_dashboard_page()) {
        $classes[] = 'mobooking-dashboard-page';
        $section = get_query_var('section', 'overview');
        $classes[] = 'dashboard-section-' . sanitize_html_class($section);
    }

    return $classes;
}
add_filter('body_class', 'mobooking_body_classes');

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
 * Get user's subscription status
 */
function mobooking_get_user_subscription_status($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $has_subscription = get_user_meta($user_id, 'mobooking_has_subscription', true);
    $subscription_type = get_user_meta($user_id, 'mobooking_subscription_type', true);
    $subscription_expiry = get_user_meta($user_id, 'mobooking_subscription_expiry', true);

    $is_expired = false;
    if (!$has_subscription && !empty($subscription_expiry)) {
        $is_expired = strtotime($subscription_expiry) < time();
    }

    return array(
        'has_subscription' => (bool) $has_subscription,
        'type' => $subscription_type ?: '',
        'expiry' => $subscription_expiry ?: '',
        'is_expired' => $is_expired,
        'is_active' => (bool) $has_subscription && !$is_expired
    );
}

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
        echo __('WooCommerce is required for payment processing. Please install and activate WooCommerce.', 'mobooking');
        echo '</p></div>';
    }
}
add_action('admin_notices', 'mobooking_admin_notices');

/**
 * Flush rewrite rules on theme activation
 */
function mobooking_flush_rewrite_rules() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules');

/**
 * Load textdomain for translations
 */
function mobooking_load_textdomain() {
    load_theme_textdomain('mobooking', MOBOOKING_PATH . '/languages');
}
add_action('after_setup_theme', 'mobooking_load_textdomain');

/**
 * Security headers
 */
function mobooking_security_headers() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
add_action('send_headers', 'mobooking_security_headers');

if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}



























/**
 * Add this to your functions.php file to flush rewrite rules
 * This should be done after making changes to the booking form endpoints
 */

// Add this function to flush rewrite rules manually
function mobooking_flush_rewrite_rules_admin() {
    if (current_user_can('administrator') && isset($_GET['mobooking_flush_rules'])) {
        // Register the rewrite rules
        $booking_form_manager = new \MoBooking\BookingForm\Manager();
        $booking_form_manager->register_booking_form_endpoints();
        
        // Flush the rules
        flush_rewrite_rules();
        
        // Show admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>MoBooking: Rewrite rules have been flushed successfully!</p></div>';
        });
    }
}
add_action('admin_init', 'mobooking_flush_rewrite_rules_admin');

// Add admin menu item to flush rules
function mobooking_add_flush_rules_page() {
    if (current_user_can('administrator')) {
        add_submenu_page(
            'tools.php',
            'Flush MoBooking Rules',
            'Flush MoBooking Rules',
            'administrator',
            'mobooking-flush-rules',
            function() {
                echo '<div class="wrap">';
                echo '<h1>Flush MoBooking Rewrite Rules</h1>';
                echo '<p>Click the button below to flush rewrite rules if your booking URLs are not working.</p>';
                echo '<a href="' . admin_url('tools.php?page=mobooking-flush-rules&mobooking_flush_rules=1') . '" class="button-primary">Flush Rewrite Rules</a>';
                echo '</div>';
            }
        );
    }
}
add_action('admin_menu', 'mobooking_add_flush_rules_page');

// Automatically flush rules when the theme is activated or BookingForm\Manager is loaded for the first time
function mobooking_maybe_flush_rules() {
    $rules_flushed = get_option('mobooking_rules_flushed', false);
    
    if (!$rules_flushed || (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['force_flush']))) {
        // Register the rewrite rules
        if (class_exists('\MoBooking\BookingForm\Manager')) {
            $booking_form_manager = new \MoBooking\BookingForm\Manager();
            $booking_form_manager->register_booking_form_endpoints();
            
            // Flush the rules
            flush_rewrite_rules();
            
            // Mark as flushed
            update_option('mobooking_rules_flushed', true);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Rewrite rules flushed automatically');
            }
        }
    }
}
add_action('init', 'mobooking_maybe_flush_rules', 20);

// Debug function to check current rewrite rules
function mobooking_debug_rewrite_rules() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator') && isset($_GET['debug_rewrite'])) {
        global $wp_rewrite;
        
        echo '<pre>';
        echo "=== MoBooking Rewrite Rules Debug ===\n";
        echo "Current rules:\n";
        print_r($wp_rewrite->wp_rewrite_rules());
        echo "\nQuery vars:\n";
        print_r($wp_rewrite->querystring_start);
        echo "\nBooking form query vars should include:\n";
        echo "- mobooking_booking_form\n";
        echo "- mobooking_booking_embed\n";  
        echo "- booking_user\n";
        echo '</pre>';
        
        // Test URL generation
        echo '<h3>Test URLs:</h3>';
        if (class_exists('\MoBooking\BookingForm\Manager')) {
            $booking_form_manager = new \MoBooking\BookingForm\Manager();
            $current_user_id = get_current_user_id();
            if ($current_user_id) {
                echo '<p>Your booking URL: <a href="' . $booking_form_manager->get_booking_form_url($current_user_id) . '" target="_blank">' . $booking_form_manager->get_booking_form_url($current_user_id) . '</a></p>';
                echo '<p>Your embed URL: <a href="' . $booking_form_manager->get_embed_url($current_user_id) . '" target="_blank">' . $booking_form_manager->get_embed_url($current_user_id) . '</a></p>';
            }
        }
        exit;
    }
}
add_action('init', 'mobooking_debug_rewrite_rules', 25);



/**
 * Debug helper for booking forms
 * Add this to your theme's functions.php temporarily to debug issues
 */

// Debug booking form setup
function mobooking_debug_booking_setup() {
    if (!current_user_can('administrator') || !isset($_GET['mobooking_debug'])) {
        return;
    }
    
    echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px; font-family: monospace;">';
    echo '<h2>MoBooking Debug Information</h2>';
    
    // Check if templates directory exists
    $templates_dir = MOBOOKING_PATH . '/templates';
    echo '<h3>Template Files:</h3>';
    echo '<p>Templates directory: ' . $templates_dir . '</p>';
    echo '<p>Directory exists: ' . (is_dir($templates_dir) ? 'YES' : 'NO') . '</p>';
    
    $public_template = $templates_dir . '/booking-form-public.php';
    $embed_template = $templates_dir . '/booking-form-embed.php';
    
    echo '<p>Public template exists: ' . (file_exists($public_template) ? 'YES' : 'NO') . '</p>';
    echo '<p>Embed template exists: ' . (file_exists($embed_template) ? 'YES' : 'NO') . '</p>';
    
    // Check current user
    $current_user = wp_get_current_user();
    echo '<h3>Current User:</h3>';
    echo '<p>User ID: ' . $current_user->ID . '</p>';
    echo '<p>Username: ' . $current_user->user_login . '</p>';
    echo '<p>Roles: ' . implode(', ', $current_user->roles) . '</p>';
    echo '<p>Is business owner: ' . (in_array('mobooking_business_owner', $current_user->roles) ? 'YES' : 'NO') . '</p>';
    
    // Check services and areas
    if (class_exists('\MoBooking\Services\ServicesManager')) {
        $services_manager = new \MoBooking\Services\ServicesManager();
        $services = $services_manager->get_user_services($current_user->ID);
        echo '<h3>Services:</h3>';
        echo '<p>Number of services: ' . count($services) . '</p>';
    }
    
    if (class_exists('\MoBooking\Geography\Manager')) {
        $geography_manager = new \MoBooking\Geography\Manager();
        $areas = $geography_manager->get_user_areas($current_user->ID);
        echo '<h3>Service Areas:</h3>';
        echo '<p>Number of areas: ' . count($areas) . '</p>';
    }
    
    // Check rewrite rules
    global $wp_rewrite;
    echo '<h3>Rewrite Rules:</h3>';
    $rules = get_option('rewrite_rules');
    $booking_rules = array_filter($rules, function($key) {
        return strpos($key, 'booking') !== false;
    }, ARRAY_FILTER_USE_KEY);
    
    if (!empty($booking_rules)) {
        echo '<p>Booking-related rewrite rules found:</p>';
        echo '<pre>' . print_r($booking_rules, true) . '</pre>';
    } else {
        echo '<p style="color: red;">No booking-related rewrite rules found! You may need to flush rewrite rules.</p>';
    }
    
    // Test URLs
    if (class_exists('\MoBooking\BookingForm\Manager')) {
        $booking_form_manager = new \MoBooking\BookingForm\Manager();
        echo '<h3>Generated URLs:</h3>';
        echo '<p>Booking URL: <a href="' . $booking_form_manager->get_booking_form_url($current_user->ID) . '" target="_blank">' . $booking_form_manager->get_booking_form_url($current_user->ID) . '</a></p>';
        echo '<p>Embed URL: <a href="' . $booking_form_manager->get_embed_url($current_user->ID) . '" target="_blank">' . $booking_form_manager->get_embed_url($current_user->ID) . '</a></p>';
    }
    
    echo '<h3>Quick Actions:</h3>';
    echo '<a href="' . admin_url('tools.php?page=mobooking-flush-rules&mobooking_flush_rules=1') . '" style="background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;">Flush Rewrite Rules</a>';
    echo '<a href="' . add_query_arg('debug_rewrite', '1') . '" style="background: #d54e21; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;">Debug Rewrite Rules</a>';
    
    echo '</div>';
}
add_action('wp_head', 'mobooking_debug_booking_setup');

// Quick setup function to create template files if they don't exist
function mobooking_create_template_files() {
    if (!current_user_can('administrator') || !isset($_GET['create_templates'])) {
        return;
    }
    
    $templates_dir = MOBOOKING_PATH . '/templates';
    
    // Create templates directory if it doesn't exist
    if (!is_dir($templates_dir)) {
        wp_mkdir_p($templates_dir);
    }
    
    $public_template = $templates_dir . '/booking-form-public.php';
    $embed_template = $templates_dir . '/booking-form-embed.php';
    
    // Create basic public template if it doesn't exist
    if (!file_exists($public_template)) {
        $public_content = '<?php
// Basic public booking form template
// This is a minimal template - replace with the full template from the artifacts

if (!defined("ABSPATH")) {
    exit;
}

global $mobooking_form_user;

if (!$mobooking_form_user) {
    get_header();
    echo "<h1>Booking form not found</h1>";
    get_footer();
    return;
}

get_header();
echo "<h1>Booking Form for " . esc_html($mobooking_form_user->display_name) . "</h1>";
echo "<p>Template loaded successfully! Replace this with the full template.</p>";
get_footer();
?>';
        file_put_contents($public_template, $public_content);
    }
    
    // Create basic embed template if it doesn't exist
    if (!file_exists($embed_template)) {
        $embed_content = '<?php
// Basic embed booking form template
// This is a minimal template - replace with the full template from the artifacts

if (!defined("ABSPATH")) {
    exit;
}

global $mobooking_form_user;

if (!$mobooking_form_user) {
    echo "<p>Booking form not found</p>";
    return;
}

echo "<!DOCTYPE html><html><head><title>Booking Form</title></head><body>";
echo "<h1>Embed Booking Form for " . esc_html($mobooking_form_user->display_name) . "</h1>";
echo "<p>Embed template loaded successfully! Replace this with the full template.</p>";
echo "</body></html>";
?>';
        file_put_contents($embed_template, $embed_content);
    }
    
    echo '<div class="notice notice-success"><p>Template files have been created!</p></div>';
}
add_action('admin_init', 'mobooking_create_template_files');










































/**
 * MoBooking Debug Helper for Booking Form Issues
 * Add this to your functions.php file temporarily to debug the ZIP code issue
 */

// Debug AJAX requests
function mobooking_debug_ajax_requests() {
    if (!current_user_can('administrator') || !isset($_GET['mobooking_debug_ajax'])) {
        return;
    }
    
    echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px; font-family: monospace;">';
    echo '<h2>MoBooking AJAX Debug Information</h2>';
    
    // Check if AJAX actions are registered
    global $wp_filter;
    
    echo '<h3>Registered AJAX Actions:</h3>';
    $ajax_actions = [
        'wp_ajax_mobooking_check_zip_coverage',
        'wp_ajax_nopriv_mobooking_check_zip_coverage',
        'wp_ajax_mobooking_save_booking',
        'wp_ajax_nopriv_mobooking_save_booking',
        'wp_ajax_mobooking_validate_discount',
        'wp_ajax_nopriv_mobooking_validate_discount'
    ];
    
    foreach ($ajax_actions as $action) {
        $registered = isset($wp_filter[$action]) && !empty($wp_filter[$action]);
        echo '<p>' . $action . ': ' . ($registered ? '✅ REGISTERED' : '❌ NOT REGISTERED') . '</p>';
        
        if ($registered && isset($wp_filter[$action])) {
            echo '<ul>';
            foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function'])) {
                        $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                        $method = $callback['function'][1];
                        echo '<li>Priority ' . $priority . ': ' . $class . '::' . $method . '</li>';
                    } else {
                        echo '<li>Priority ' . $priority . ': ' . $callback['function'] . '</li>';
                    }
                }
            }
            echo '</ul>';
        }
    }
    
    // Check database tables
    echo '<h3>Database Tables:</h3>';
    global $wpdb;
    
    $tables_to_check = [
        $wpdb->prefix . 'mobooking_areas',
        $wpdb->prefix . 'mobooking_services',
        $wpdb->prefix . 'mobooking_bookings',
        $wpdb->prefix . 'mobooking_settings'
    ];
    
    foreach ($tables_to_check as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        echo '<p>' . $table . ': ' . ($exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';
        
        if ($exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            echo '<p style="margin-left: 20px;">Records: ' . $count . '</p>';
            
            // Show sample data for areas table
            if ($table == $wpdb->prefix . 'mobooking_areas') {
                $sample = $wpdb->get_results("SELECT * FROM $table LIMIT 5");
                if ($sample) {
                    echo '<p style="margin-left: 20px;">Sample data:</p>';
                    echo '<pre style="margin-left: 40px; font-size: 11px;">' . print_r($sample, true) . '</pre>';
                }
            }
        }
    }
    
    // Check current user and test data
    echo '<h3>Current User Info:</h3>';
    $current_user = wp_get_current_user();
    echo '<p>User ID: ' . $current_user->ID . '</p>';
    echo '<p>Username: ' . $current_user->user_login . '</p>';
    echo '<p>Roles: ' . implode(', ', $current_user->roles) . '</p>';
    
    // Test nonce generation
    echo '<h3>Nonce Test:</h3>';
    $test_nonce = wp_create_nonce('mobooking-booking-nonce');
    echo '<p>Generated nonce: ' . $test_nonce . '</p>';
    echo '<p>Nonce verification: ' . (wp_verify_nonce($test_nonce, 'mobooking-booking-nonce') ? '✅ VALID' : '❌ INVALID') . '</p>';
    
    // Test ZIP coverage directly
    echo '<h3>Direct ZIP Coverage Test:</h3>';
    if (class_exists('\MoBooking\Geography\Manager')) {
        $geography_manager = new \MoBooking\Geography\Manager();
        
        // Test with a sample ZIP code and current user
        $test_zip = '12345';
        $test_user_id = $current_user->ID;
        
        echo '<p>Testing ZIP: ' . $test_zip . ' for User ID: ' . $test_user_id . '</p>';
        
        $is_covered = $geography_manager->is_zip_covered($test_zip, $test_user_id);
        echo '<p>Coverage result: ' . ($is_covered ? '✅ COVERED' : '❌ NOT COVERED') . '</p>';
        
        // Show user's areas
        $user_areas = $geography_manager->get_user_areas($test_user_id);
        echo '<p>User areas (' . count($user_areas) . '):</p>';
        if ($user_areas) {
            echo '<ul>';
            foreach ($user_areas as $area) {
                echo '<li>ZIP: ' . $area->zip_code . ' (Active: ' . ($area->active ? 'Yes' : 'No') . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p style="color: red;">No areas found for this user. Add some areas in the dashboard first!</p>';
        }
    }
    
    // Quick fix suggestions
    echo '<h3>Quick Fix Actions:</h3>';
    echo '<a href="' . add_query_arg('mobooking_add_test_area', '1') . '" style="background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px;">Add Test Area (12345)</a>';
    echo '<a href="' . add_query_arg('mobooking_flush_debug', '1') . '" style="background: #d54e21; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;">Flush Rewrite Rules</a>';
    
    echo '</div>';
}
add_action('wp_head', 'mobooking_debug_ajax_requests');

// Quick fix: Add test area
function mobooking_add_test_area() {
    if (!current_user_can('administrator') || !isset($_GET['mobooking_add_test_area'])) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_areas';
    $current_user_id = get_current_user_id();
    
    // Check if test area already exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE zip_code = %s AND user_id = %d",
        '12345', $current_user_id
    ));
    
    if (!$exists) {
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $current_user_id,
                'zip_code' => '12345',
                'label' => 'Test Area',
                'active' => 1
            ),
            array('%d', '%s', '%s', '%d')
        );
        
        echo '<div class="notice notice-success"><p>Test area (12345) added successfully!</p></div>';
    } else {
        echo '<div class="notice notice-info"><p>Test area (12345) already exists.</p></div>';
    }
}
add_action('admin_init', 'mobooking_add_test_area');

// AJAX handler to test ZIP coverage directly
function mobooking_test_zip_coverage_direct() {
    // This mimics the exact AJAX call to test it
    add_action('wp_ajax_mobooking_test_zip_direct', function() {
        error_log('MoBooking: Direct ZIP test called');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed.'));
            return;
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        $user_id = absint($_POST['user_id']);
        
        error_log("MoBooking: Testing ZIP: {$zip_code} for User: {$user_id}");
        
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            $is_covered = $geography_manager->is_zip_covered($zip_code, $user_id);
            
            if ($is_covered) {
                wp_send_json_success(array(
                    'message' => 'Great! We provide services in your area.'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Sorry, we don\'t currently service this area.'
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => 'Geography Manager class not found.'
            ));
        }
    });
    
    add_action('wp_ajax_nopriv_mobooking_test_zip_direct', function() {
        // Same as above for non-logged-in users
        error_log('MoBooking: Direct ZIP test called (nopriv)');
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(array('message' => 'Security verification failed.'));
            return;
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        $user_id = absint($_POST['user_id']);
        
        error_log("MoBooking: Testing ZIP: {$zip_code} for User: {$user_id}");
        
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            $is_covered = $geography_manager->is_zip_covered($zip_code, $user_id);
            
            if ($is_covered) {
                wp_send_json_success(array(
                    'message' => 'Great! We provide services in your area.'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Sorry, we don\'t currently service this area.'
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => 'Geography Manager class not found.'
            ));
        }
    });
}
mobooking_test_zip_coverage_direct();

// Add debug console to admin bar
function mobooking_add_debug_console() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    global $wp_admin_bar;
    
    $wp_admin_bar->add_menu(array(
        'id' => 'mobooking-debug',
        'title' => 'MoBooking Debug',
        'href' => add_query_arg('mobooking_debug_ajax', '1', home_url()),
    ));
    
    $wp_admin_bar->add_menu(array(
        'parent' => 'mobooking-debug',
        'id' => 'mobooking-test-ajax',
        'title' => 'Test AJAX',
        'href' => 'javascript:void(0)',
        'meta' => array(
            'onclick' => 'mobookingTestAjax()'
        )
    ));
}
add_action('wp_before_admin_bar_render', 'mobooking_add_debug_console');

// Add debug JavaScript to footer
function mobooking_add_debug_js() {
    if (!current_user_can('administrator')) {
        return;
    }
    ?>
    <script>
    function mobookingTestAjax() {
        console.log('Testing MoBooking AJAX...');
        
        // Get current user ID (you might need to adjust this)
        var userId = <?php echo get_current_user_id(); ?>;
        var nonce = '<?php echo wp_create_nonce('mobooking-booking-nonce'); ?>';
        
        // Test ZIP coverage
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mobooking_test_zip_direct',
                zip_code: '12345',
                user_id: userId,
                nonce: nonce
            },
            success: function(response) {
                console.log('AJAX Test Success:', response);
                alert('AJAX Test Success: ' + JSON.stringify(response));
            },
            error: function(xhr, status, error) {
                console.error('AJAX Test Error:', xhr, status, error);
                console.error('Response Text:', xhr.responseText);
                alert('AJAX Test Error: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }
    
    // Auto-run debug on pages with booking form
    jQuery(document).ready(function($) {
        if ($('.mobooking-booking-form-container').length > 0) {
            console.log('MoBooking Booking Form detected');
            console.log('Config available:', typeof mobookingBooking !== 'undefined');
            
            if (typeof mobookingBooking !== 'undefined') {
                console.log('MoBooking Config:', mobookingBooking);
            } else {
                console.error('MoBooking Config not loaded!');
            }
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'mobooking_add_debug_js');

// Log all AJAX requests for debugging
function mobooking_log_ajax_requests() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    add_action('wp_ajax_mobooking_check_zip_coverage', function() {
        error_log('MoBooking AJAX: mobooking_check_zip_coverage called (logged in)');
        error_log('POST data: ' . print_r($_POST, true));
    }, 1);
    
    add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', function() {
        error_log('MoBooking AJAX: mobooking_check_zip_coverage called (not logged in)');
        error_log('POST data: ' . print_r($_POST, true));
    }, 1);
}
mobooking_log_ajax_requests();

/**
 * Instructions for using this debug helper:
 * 
 * 1. Add this code to your functions.php file temporarily
 * 2. Visit any page and add ?mobooking_debug_ajax=1 to the URL
 * 3. Check the debug information displayed
 * 4. Click "Add Test Area" to create a test ZIP code area
 * 5. Use the admin bar "MoBooking Debug" -> "Test AJAX" to test AJAX calls
 * 6. Check your error logs for detailed debugging information
 * 7. Try the booking form with ZIP code 12345
 * 
 * Common issues and solutions:
 * 
 * 1. If AJAX actions show "NOT REGISTERED":
 *    - Check if the Manager classes are being loaded properly
 *    - Verify the autoloader is working
 *    - Make sure the Manager constructors are being called
 * 
 * 2. If database tables are missing:
 *    - Go to WP Admin -> Tools -> Flush MoBooking Rules
 *    - Check if the Database Manager is creating tables properly
 * 
 * 3. If no areas found for user:
 *    - Go to Dashboard -> Service Areas and add some ZIP codes
 *    - Or click "Add Test Area" button in the debug interface
 * 
 * 4. If nonce verification fails:
 *    - Check if the correct nonce is being passed from JavaScript
 *    - Verify the nonce action name matches between JS and PHP
 * 
 * 5. If 400 Bad Request error:
 *    - Check error logs for specific PHP errors
 *    - Verify all required POST parameters are being sent
 *    - Check if classes are loaded and methods exist
 */