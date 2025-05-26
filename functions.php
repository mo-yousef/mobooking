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
 * Add this to your functions.php file to debug and fix the AJAX issue
 * This will help identify why the ZIP coverage check is returning a 400 error
 */

// Debug function to check AJAX action registration
function mobooking_debug_ajax_registration() {
    if (!current_user_can('administrator') || !isset($_GET['debug_ajax'])) {
        return;
    }
    
    echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border-radius: 5px; font-family: monospace;">';
    echo '<h2>MoBooking AJAX Registration Debug</h2>';
    
    // Check if the classes exist
    echo '<h3>Class Existence Check:</h3>';
    $classes_to_check = [
        '\MoBooking\Bookings\Manager',
        '\MoBooking\Geography\Manager',
        '\MoBooking\Services\ServicesManager',
        '\MoBooking\Discounts\Manager'
    ];
    
    foreach ($classes_to_check as $class) {
        $exists = class_exists($class);
        echo '<p>' . $class . ': ' . ($exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';
        
        if ($exists) {
            try {
                $reflection = new ReflectionClass($class);
                $methods = $reflection->getMethods();
                $ajax_methods = array_filter($methods, function($method) {
                    return strpos($method->getName(), 'ajax_') === 0;
                });
                echo '<p style="margin-left: 20px;">AJAX methods: ' . count($ajax_methods) . '</p>';
                foreach ($ajax_methods as $method) {
                    echo '<p style="margin-left: 40px;">- ' . $method->getName() . '</p>';
                }
            } catch (Exception $e) {
                echo '<p style="margin-left: 20px; color: red;">Error: ' . $e->getMessage() . '</p>';
            }
        }
    }
    
    // Check global $wp_filter for AJAX actions
    echo '<h3>Registered AJAX Actions:</h3>';
    global $wp_filter;
    
    $ajax_actions_to_check = [
        'wp_ajax_mobooking_check_zip_coverage',
        'wp_ajax_nopriv_mobooking_check_zip_coverage',
        'wp_ajax_mobooking_save_booking',
        'wp_ajax_nopriv_mobooking_save_booking',
        'wp_ajax_mobooking_validate_discount',
        'wp_ajax_nopriv_mobooking_validate_discount'
    ];
    
    foreach ($ajax_actions_to_check as $action) {
        $registered = isset($wp_filter[$action]) && !empty($wp_filter[$action]);
        echo '<p>' . $action . ': ' . ($registered ? '✅ REGISTERED' : '❌ NOT REGISTERED') . '</p>';
        
        if ($registered && isset($wp_filter[$action])) {
            foreach ($wp_filter[$action]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function'])) {
                        $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                        $method = $callback['function'][1];
                        echo '<p style="margin-left: 20px;">→ ' . $class . '::' . $method . ' (priority: ' . $priority . ')</p>';
                    } else {
                        echo '<p style="margin-left: 20px;">→ ' . $callback['function'] . ' (priority: ' . $priority . ')</p>';
                    }
                }
            }
        }
    }
    
    // Manual registration test
    echo '<h3>Manual Registration Test:</h3>';
    echo '<p><a href="' . add_query_arg('force_register_ajax', '1') . '" style="background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;">Force Register AJAX Handlers</a></p>';
    
    // Test AJAX endpoint directly
    echo '<h3>Direct AJAX Test:</h3>';
    echo '<button onclick="testAjaxDirect()" style="background: #d54e21; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer;">Test ZIP Coverage AJAX</button>';
    echo '<div id="ajax-test-result" style="margin-top: 10px; padding: 10px; background: white; border: 1px solid #ccc;"></div>';
    
    echo '</div>';
    
    // Add JavaScript for testing
    ?>
    <script>
    function testAjaxDirect() {
        const resultDiv = document.getElementById('ajax-test-result');
        resultDiv.innerHTML = 'Testing...';
        
        const formData = new FormData();
        formData.append('action', 'mobooking_check_zip_coverage');
        formData.append('zip_code', '12345');
        formData.append('user_id', '<?php echo get_current_user_id(); ?>');
        formData.append('nonce', '<?php echo wp_create_nonce('mobooking-booking-nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(data => {
            console.log('Response data:', data);
            resultDiv.innerHTML = '<h4>Response:</h4><pre>' + data + '</pre>';
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<h4>Error:</h4><pre>' + error.message + '</pre>';
        });
    }
    </script>
    <?php
}
add_action('wp_head', 'mobooking_debug_ajax_registration');

// Force register AJAX handlers
function mobooking_force_register_ajax() {
    if (!current_user_can('administrator') || !isset($_GET['force_register_ajax'])) {
        return;
    }
    
    // Manually register the AJAX handlers
    add_action('wp_ajax_mobooking_check_zip_coverage', 'mobooking_manual_zip_coverage_handler');
    add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', 'mobooking_manual_zip_coverage_handler');
    
    echo '<div class="notice notice-success"><p>AJAX handlers have been manually registered!</p></div>';
}
add_action('admin_init', 'mobooking_force_register_ajax');

// Manual AJAX handler for ZIP coverage
function mobooking_manual_zip_coverage_handler() {
    error_log('MoBooking: Manual ZIP coverage handler called');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
        wp_send_json_error(array('message' => 'Security verification failed.'));
        return;
    }
    
    // Check required parameters
    if (!isset($_POST['zip_code']) || !isset($_POST['user_id'])) {
        wp_send_json_error(array('message' => 'Missing required parameters.'));
        return;
    }
    
    $zip_code = sanitize_text_field($_POST['zip_code']);
    $user_id = absint($_POST['user_id']);
    
    // Simple database check
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_areas';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE zip_code = %s AND user_id = %d AND active = 1",
        $zip_code, $user_id
    ));
    
    if ($result > 0) {
        wp_send_json_success(array(
            'message' => 'Great! We provide services in your area.',
            'coverage' => true
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Sorry, we don\'t currently service this area.',
            'coverage' => false
        ));
    }
}

// Fix the Geography Manager method to ensure it's working
function mobooking_fix_geography_manager() {
    // Add a test method to check ZIP coverage
    if (class_exists('\MoBooking\Geography\Manager')) {
        add_action('wp_ajax_test_geography_manager', function() {
            if (!current_user_can('administrator')) {
                wp_die('Unauthorized');
            }
            
            $geography_manager = new \MoBooking\Geography\Manager();
            $test_zip = '12345';
            $test_user = get_current_user_id();
            
            echo '<h3>Geography Manager Test</h3>';
            echo '<p>Testing ZIP: ' . $test_zip . ' for User: ' . $test_user . '</p>';
            
            $is_covered = $geography_manager->is_zip_covered($test_zip, $test_user);
            echo '<p>Result: ' . ($is_covered ? 'COVERED' : 'NOT COVERED') . '</p>';
            
            // Show user areas
            $areas = $geography_manager->get_user_areas($test_user);
            echo '<p>User areas (' . count($areas) . '):</p>';
            if ($areas) {
                echo '<ul>';
                foreach ($areas as $area) {
                    echo '<li>ZIP: ' . $area->zip_code . ' (Active: ' . ($area->active ? 'Yes' : 'No') . ')</li>';
                }
                echo '</ul>';
            }
            
            wp_die();
        });
    }
}
mobooking_fix_geography_manager();

// Ensure proper initialization order
function mobooking_ensure_proper_init() {
    // Hook into init with high priority to ensure managers are loaded
    add_action('init', function() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Ensuring proper initialization...');
        }
        
        // Force instantiate the Bookings Manager if it hasn't been created
        if (class_exists('\MoBooking\Bookings\Manager')) {
            static $bookings_manager_instance = null;
            if ($bookings_manager_instance === null) {
                $bookings_manager_instance = new \MoBooking\Bookings\Manager();
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Bookings Manager instantiated manually');
                }
            }
        }
        
        // Force instantiate the Geography Manager if it hasn't been created
        if (class_exists('\MoBooking\Geography\Manager')) {
            static $geography_manager_instance = null;
            if ($geography_manager_instance === null) {
                $geography_manager_instance = new \MoBooking\Geography\Manager();
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Geography Manager instantiated manually');
                }
            }
        }
    }, 5); // High priority
}
mobooking_ensure_proper_init();

// Quick fix for the immediate issue - direct AJAX handler registration
function mobooking_quick_ajax_fix() {
    // Register handlers directly in functions.php as backup
    add_action('wp_ajax_mobooking_check_zip_coverage', 'mobooking_direct_zip_check');
    add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', 'mobooking_direct_zip_check');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Direct AJAX handlers registered as backup');
    }
}

function mobooking_direct_zip_check() {
    // Enhanced error reporting
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking Direct ZIP Check Handler Called');
        error_log('POST: ' . print_r($_POST, true));
    }
    
    // Detailed error response
    header('Content-Type: application/json');
    
    try {
        // Nonce check
        if (!isset($_POST['nonce'])) {
            echo json_encode(['success' => false, 'data' => ['message' => 'No nonce provided']]);
            wp_die();
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            echo json_encode(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
            wp_die();
        }
        
        // Parameter checks
        if (!isset($_POST['zip_code']) || empty($_POST['zip_code'])) {
            echo json_encode(['success' => false, 'data' => ['message' => 'ZIP code required']]);
            wp_die();
        }
        
        if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
            echo json_encode(['success' => false, 'data' => ['message' => 'User ID required']]);
            wp_die();
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        $user_id = absint($_POST['user_id']);
        
        // Database check
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo json_encode(['success' => false, 'data' => ['message' => 'Service areas not configured']]);
            wp_die();
        }
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE zip_code = %s AND user_id = %d AND active = 1",
            $zip_code, $user_id
        ));
        
        if ($count > 0) {
            echo json_encode([
                'success' => true, 
                'data' => ['message' => 'Great! We provide services in your area.']
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'data' => ['message' => 'Sorry, we don\'t currently service this area.']
            ]);
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Direct ZIP Check Exception: ' . $e->getMessage());
        }
        echo json_encode(['success' => false, 'data' => ['message' => 'Service error occurred']]);
    }
    
    wp_die();
}

// Call the quick fix
mobooking_quick_ajax_fix();

// Database table creation helper
function mobooking_ensure_areas_table() {
    if (!current_user_can('administrator') || !isset($_GET['create_areas_table'])) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_areas';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        zip_code varchar(20) NOT NULL,
        label varchar(255) NULL,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        UNIQUE KEY user_zip (user_id, zip_code)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add test data
    $current_user_id = get_current_user_id();
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND zip_code = %s",
        $current_user_id, '12345'
    ));
    
    if (!$existing) {
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
    }
    
    echo '<div class="notice notice-success"><p>Areas table created and test data added!</p></div>';
}
add_action('admin_init', 'mobooking_ensure_areas_table');

/**
 * Instructions for using this debug helper:
 * 
 * 1. Add this code to your functions.php file
 * 2. Visit any page with ?debug_ajax=1 in the URL
 * 3. Check the debug information and click "Test ZIP Coverage AJAX"
 * 4. If needed, click "Force Register AJAX Handlers"
 * 5. If the areas table is missing, add ?create_areas_table=1 to the URL
 * 6. Test the booking form again
 * 
 * The most likely causes of the 400 error:
 * 1. AJAX handlers not properly registered (fixed by this code)
 * 2. Missing database tables (create with ?create_areas_table=1)
 * 3. Nonce verification issues (handled in direct handler)
 * 4. Class autoloading problems (fixed by manual instantiation)
 * 
 * This code provides multiple fallback mechanisms to ensure the ZIP 
 * coverage check works regardless of the underlying issue.
 */