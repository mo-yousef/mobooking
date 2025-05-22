<?php

/**
 * MoBooking Theme Functions
 * 
 * Multi-tenant SaaS application for cleaning booking system
 * 
 * @package MoBooking
 * @version 1.0.0
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
    // Check if the class uses our namespace
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }

    // Remove namespace and replace \ with /
    $relative_class = str_replace('MoBooking\\', '', $class);
    $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';

    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Log missing class file in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Class file not found: {$file}");
        }
    }
});

/**
 * Initialize the MoBooking theme
 */
function mobooking_init()
{
    try {
        $loader = new MoBooking\Core\Loader();
        $loader->init();
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking initialization error: ' . $e->getMessage());
        }

        // Show admin notice for initialization errors
        add_action('admin_notices', function () use ($e) {
            if (current_user_can('administrator')) {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>MoBooking Error:</strong> Failed to initialize theme. ';
                echo defined('WP_DEBUG') && WP_DEBUG ? esc_html($e->getMessage()) : 'Please check error logs.';
                echo '</p></div>';
            }
        });
    }
}
add_action('after_setup_theme', 'mobooking_init', 5);

/**
 * Theme setup function
 */
function mobooking_theme_setup()
{
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption'
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'mobooking'),
        'footer' => __('Footer Menu', 'mobooking'),
        'dashboard' => __('Dashboard Menu', 'mobooking'),
    ));

    // Set content width
    global $content_width;
    if (!isset($content_width)) {
        $content_width = 1200;
    }
}
add_action('after_setup_theme', 'mobooking_theme_setup');

/**
 * Enqueue frontend scripts and styles
 */
function mobooking_enqueue_scripts()
{
    // Load dashicons on frontend
    wp_enqueue_style('dashicons');

    // Main theme stylesheet
    wp_enqueue_style(
        'mobooking-style',
        get_stylesheet_uri(),
        array(),
        MOBOOKING_VERSION
    );

    // Main theme JavaScript
    wp_enqueue_script(
        'mobooking-main',
        MOBOOKING_URL . '/assets/js/main.js',
        array('jquery'),
        MOBOOKING_VERSION,
        true
    );

    // Localize main script
    wp_localize_script('mobooking-main', 'mobookingMain', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-main-nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'currentUserId' => get_current_user_id(),
        'homeUrl' => home_url(),
        'themeUrl' => MOBOOKING_URL,
        'strings' => array(
            'loading' => __('Loading...', 'mobooking'),
            'error' => __('An error occurred. Please try again.', 'mobooking'),
            'success' => __('Success!', 'mobooking'),
            'confirm' => __('Are you sure?', 'mobooking'),
        )
    ));
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_scripts');

/**
 * Enqueue dashboard-specific scripts and styles
 */
function mobooking_enqueue_dashboard_scripts()
{
    // Only load on dashboard pages
    if (!is_page() || !is_user_logged_in()) {
        return;
    }

    global $wp_query;
    $is_dashboard = isset($wp_query->query['pagename']) && $wp_query->query['pagename'] === 'dashboard';

    if (!$is_dashboard) {
        return;
    }

    // Dashboard styles
    wp_enqueue_style(
        'mobooking-dashboard',
        MOBOOKING_URL . '/assets/css/dashboard.css',
        array('dashicons'),
        MOBOOKING_VERSION
    );

    // Service options styles
    wp_enqueue_style(
        'mobooking-service-options',
        MOBOOKING_URL . '/assets/css/service-options.css',
        array('mobooking-dashboard'),
        MOBOOKING_VERSION
    );

    // Dashboard JavaScript
    wp_enqueue_script(
        'mobooking-dashboard',
        MOBOOKING_URL . '/assets/js/dashboard.js',
        array('jquery', 'jquery-ui-sortable'),
        MOBOOKING_VERSION,
        true
    );

    // Services handler
    wp_enqueue_script(
        'mobooking-services-handler',
        MOBOOKING_URL . '/assets/js/service-form-handler.js',
        array('jquery', 'jquery-ui-sortable'),
        MOBOOKING_VERSION,
        true
    );

    // Localize dashboard scripts
    $dashboard_data = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'userId' => get_current_user_id(),
        'currentSection' => get_query_var('section', 'overview'),
        'nonces' => array(
            'dashboard' => wp_create_nonce('mobooking-dashboard-nonce'),
            'service' => wp_create_nonce('mobooking-service-nonce'),
            'option' => wp_create_nonce('mobooking-option-nonce'),
            'booking' => wp_create_nonce('mobooking-booking-nonce'),
            'area' => wp_create_nonce('mobooking-area-nonce'),
            'discount' => wp_create_nonce('mobooking-discount-nonce'),
        ),
        'strings' => array(
            'saving' => __('Saving...', 'mobooking'),
            'saved' => __('Saved successfully', 'mobooking'),
            'error' => __('Error occurred', 'mobooking'),
            'deleteConfirm' => __('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'),
            'requiredField' => __('This field is required', 'mobooking'),
            'invalidEmail' => __('Please enter a valid email address', 'mobooking'),
            'invalidPrice' => __('Price must be greater than zero', 'mobooking'),
            'minDuration' => __('Duration must be at least 15 minutes', 'mobooking'),
        )
    );

    wp_localize_script('mobooking-dashboard', 'mobookingDashboard', $dashboard_data);
    wp_localize_script('mobooking-services-handler', 'mobookingServices', $dashboard_data);
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_dashboard_scripts', 15);

/**
 * Enqueue media uploader when needed
 */
function mobooking_enqueue_media()
{
    if (is_user_logged_in() && (is_page() || is_admin())) {
        wp_enqueue_media();
    }
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_media');

/**
 * Register widget areas
 */
function mobooking_widgets_init()
{
    register_sidebar(array(
        'name' => __('Footer Widget Area', 'mobooking'),
        'id' => 'footer-widgets',
        'description' => __('Add widgets here to appear in your footer.', 'mobooking'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));

    register_sidebar(array(
        'name' => __('Sidebar Widget Area', 'mobooking'),
        'id' => 'sidebar-widgets',
        'description' => __('Add widgets here to appear in your sidebar.', 'mobooking'),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}
add_action('widgets_init', 'mobooking_widgets_init');

/**
 * Add custom body classes
 */
function mobooking_body_classes($classes)
{
    // Add logged-in class
    if (is_user_logged_in()) {
        $classes[] = 'logged-in';

        // Add user role classes
        $user = wp_get_current_user();
        if (!empty($user->roles)) {
            foreach ($user->roles as $role) {
                $classes[] = 'role-' . sanitize_html_class($role);
            }
        }
    }

    // Add dashboard class
    global $wp_query;
    if (isset($wp_query->query['pagename']) && $wp_query->query['pagename'] === 'dashboard') {
        $classes[] = 'mobooking-dashboard-page';

        $section = get_query_var('section', 'overview');
        $classes[] = 'dashboard-section-' . sanitize_html_class($section);
    }

    return $classes;
}
add_filter('body_class', 'mobooking_body_classes');

/**
 * Customize login page
 */
function mobooking_login_styles()
{
    if (!defined('MOBOOKING_CUSTOM_LOGIN') || !MOBOOKING_CUSTOM_LOGIN) {
        return;
    }

    wp_enqueue_style(
        'mobooking-login',
        MOBOOKING_URL . '/assets/css/login.css',
        array(),
        MOBOOKING_VERSION
    );
}
add_action('login_enqueue_scripts', 'mobooking_login_styles');

/**
 * Utility function for logging (only when WP_DEBUG is enabled)
 */
function mobooking_log($message, $data = null)
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $log_message = '[MoBooking] ' . $message;

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log_message .= ': ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            $log_message .= ': ' . $data;
        }
    }

    error_log($log_message);
}

/**
 * Get user's subscription status
 */
function mobooking_get_user_subscription_status($user_id = null)
{
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
 * Check if user can access dashboard
 */
function mobooking_user_can_access_dashboard($user_id = null)
{
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
 * Redirect non-dashboard users away from dashboard
 */
function mobooking_dashboard_access_control()
{
    global $wp_query;

    if (!isset($wp_query->query['pagename']) || $wp_query->query['pagename'] !== 'dashboard') {
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
 * Add admin notices for theme requirements
 */
function mobooking_admin_notices()
{
    if (!current_user_can('administrator')) {
        return;
    }

    // Check for WooCommerce
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>MoBooking:</strong> ';
        echo __('WooCommerce is required for payment processing. Please install and activate WooCommerce.', 'mobooking');
        echo '</p></div>';
    }

    // Check for required PHP extensions
    $required_extensions = array('mysqli', 'json', 'curl');
    $missing_extensions = array();

    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $missing_extensions[] = $extension;
        }
    }

    if (!empty($missing_extensions)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>MoBooking:</strong> ';
        echo sprintf(
            __('Missing required PHP extensions: %s. Please contact your hosting provider.', 'mobooking'),
            implode(', ', $missing_extensions)
        );
        echo '</p></div>';
    }
}
add_action('admin_notices', 'mobooking_admin_notices');

/**
 * Handle AJAX errors gracefully
 */
function mobooking_handle_ajax_errors()
{
    // Set up error handler for AJAX requests
    if (defined('DOING_AJAX') && DOING_AJAX) {
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            mobooking_log("PHP Error during AJAX: {$message} in {$file}:{$line}");

            // Don't break AJAX responses
            return true;
        });
    }
}
add_action('init', 'mobooking_handle_ajax_errors');

/**
 * Optimize database queries
 */
function mobooking_optimize_queries()
{
    // Remove unnecessary queries on frontend
    if (!is_admin() && !is_dashboard_page()) {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
    }
}
add_action('init', 'mobooking_optimize_queries');

/**
 * Helper function to check if current page is dashboard
 */
function is_dashboard_page()
{
    global $wp_query;
    return isset($wp_query->query['pagename']) && $wp_query->query['pagename'] === 'dashboard';
}

/**
 * Add custom rewrite rules for better URLs
 */
function mobooking_rewrite_rules()
{
    // Dashboard rewrite rules are handled by Dashboard\Manager
    // Add any additional rewrite rules here if needed
}
add_action('init', 'mobooking_rewrite_rules');

/**
 * Flush rewrite rules on theme activation
 */
function mobooking_flush_rewrite_rules()
{
    mobooking_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules');

/**
 * Theme deactivation cleanup
 */
function mobooking_theme_deactivation()
{
    // Clean up rewrite rules
    flush_rewrite_rules();

    // Clean up scheduled events if any
    wp_clear_scheduled_hook('mobooking_cleanup_old_bookings');
    wp_clear_scheduled_hook('mobooking_send_reminder_emails');
}
add_action('switch_theme', 'mobooking_theme_deactivation');

/**
 * Prevent file editing from WordPress admin
 */
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * Set up cron jobs for maintenance tasks
 */
function mobooking_setup_cron_jobs()
{
    // Schedule daily cleanup
    if (!wp_next_scheduled('mobooking_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'mobooking_daily_cleanup');
    }

    // Schedule reminder emails
    if (!wp_next_scheduled('mobooking_send_reminders')) {
        wp_schedule_event(time(), 'hourly', 'mobooking_send_reminders');
    }
}
add_action('init', 'mobooking_setup_cron_jobs');

/**
 * Daily cleanup tasks
 */
function mobooking_daily_cleanup()
{
    global $wpdb;

    // Clean up old expired discount codes
    $discounts_table = $wpdb->prefix . 'mobooking_discounts';
    $wpdb->query($wpdb->prepare(
        "UPDATE $discounts_table SET active = 0 WHERE expiry_date < %s AND active = 1",
        current_time('mysql')
    ));

    // Clean up old booking data (if needed)
    // Add any other cleanup tasks here

    mobooking_log('Daily cleanup completed');
}
add_action('mobooking_daily_cleanup', 'mobooking_daily_cleanup');

/**
 * Send reminder emails
 */
function mobooking_send_reminder_emails()
{
    // Implementation for sending booking reminders
    // This would typically check for bookings in the next 24 hours
    // and send reminder emails to customers

    mobooking_log('Reminder emails processed');
}
add_action('mobooking_send_reminders', 'mobooking_send_reminder_emails');

/**
 * Security headers
 */
function mobooking_security_headers()
{
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
add_action('send_headers', 'mobooking_security_headers');

/**
 * Customize excerpt length and more text
 */
function mobooking_excerpt_length($length)
{
    return 20;
}
add_filter('excerpt_length', 'mobooking_excerpt_length');

function mobooking_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'mobooking_excerpt_more');

/**
 * Load textdomain for translations
 */
function mobooking_load_textdomain()
{
    load_theme_textdomain('mobooking', MOBOOKING_PATH . '/languages');
}
add_action('after_setup_theme', 'mobooking_load_textdomain');

/**
 * Disable WordPress core updates for non-admins in production
 */
if (defined('MOBOOKING_PRODUCTION') && MOBOOKING_PRODUCTION) {
    function mobooking_disable_updates()
    {
        if (!current_user_can('administrator')) {
            remove_action('admin_init', '_maybe_update_core');
            remove_action('admin_init', '_maybe_update_plugins');
            remove_action('admin_init', '_maybe_update_themes');
        }
    }
    add_action('admin_init', 'mobooking_disable_updates', 1);
}


// Add this to functions.php to prevent duplicate script loading

function mobooking_conditional_script_loading()
{
    // Only load service-form-handler.js, not both
    if (is_dashboard_page()) {
        // Dequeue the old dashboard.js if it's causing conflicts
        wp_dequeue_script('mobooking-dashboard');

        // Make sure we're only using one handler
        wp_dequeue_script('mobooking-services-handler');

        // Re-enqueue with proper dependencies
        wp_enqueue_script(
            'mobooking-services-handler',
            MOBOOKING_URL . '/assets/js/service-form-handler.js',
            array('jquery', 'jquery-ui-sortable'),
            MOBOOKING_VERSION . '-fix',
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'mobooking_conditional_script_loading', 20);



// Add this temporarily to your functions.php to debug the option save error

add_action('wp_ajax_mobooking_save_service_option', 'debug_option_save_handler', 5);

function debug_option_save_handler()
{
    error_log('=== DEBUG: Option save attempt ===');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('User ID: ' . get_current_user_id());
    error_log('User roles: ' . print_r(wp_get_current_user()->roles, true));

    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
        error_log('DEBUG: Nonce verification failed');
        wp_send_json_error(array('message' => 'Security verification failed.'));
        return;
    }

    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        error_log('DEBUG: Permission check failed');
        wp_send_json_error(array('message' => 'You do not have permission to do this.'));
        return;
    }

    // Check if service options table exists
    global $wpdb;
    $options_table = $wpdb->prefix . 'mobooking_service_options';
    $services_table = $wpdb->prefix . 'mobooking_services';

    $options_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'") == $options_table;
    $services_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
    $has_entity_type = $wpdb->get_var("SHOW COLUMNS FROM $services_table LIKE 'entity_type'");

    error_log("DEBUG: Options table exists: " . ($options_exists ? 'YES' : 'NO'));
    error_log("DEBUG: Services table exists: " . ($services_exists ? 'YES' : 'NO'));
    error_log("DEBUG: Has entity_type column: " . ($has_entity_type ? 'YES (Unified)' : 'NO (Separated)'));

    if (!$options_exists && $has_entity_type) {
        error_log('DEBUG: Using unified table but separate table handler called');
        // For unified table, we need to save to the services table with entity_type = 'option'
        $result = save_option_to_unified_table();
        if ($result) {
            wp_send_json_success(array(
                'id' => $result,
                'message' => 'Option saved successfully to unified table.'
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save option to unified table.'));
        }
        return;
    }

    // Let the normal handler continue
}

function save_option_to_unified_table()
{
    global $wpdb;
    $services_table = $wpdb->prefix . 'mobooking_services';

    // Prepare option data for unified table
    $option_data = array(
        'user_id' => get_current_user_id(),
        'parent_id' => isset($_POST['service_id']) ? absint($_POST['service_id']) : 0,
        'entity_type' => 'option',
        'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'checkbox',
        'is_required' => isset($_POST['is_required']) ? absint($_POST['is_required']) : 0,
        'default_value' => isset($_POST['default_value']) ? sanitize_text_field($_POST['default_value']) : '',
        'placeholder' => isset($_POST['placeholder']) ? sanitize_text_field($_POST['placeholder']) : '',
        'min_value' => isset($_POST['min_value']) && $_POST['min_value'] !== '' ? floatval($_POST['min_value']) : null,
        'max_value' => isset($_POST['max_value']) && $_POST['max_value'] !== '' ? floatval($_POST['max_value']) : null,
        'price_impact' => isset($_POST['price_impact']) ? floatval($_POST['price_impact']) : 0,
        'price_type' => isset($_POST['price_type']) ? sanitize_text_field($_POST['price_type']) : 'fixed',
        'options' => isset($_POST['options']) ? sanitize_textarea_field($_POST['options']) : '',
        'option_label' => isset($_POST['option_label']) ? sanitize_text_field($_POST['option_label']) : '',
        'step' => isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '',
        'unit' => isset($_POST['unit']) ? sanitize_text_field($_POST['unit']) : '',
        'min_length' => isset($_POST['min_length']) && $_POST['min_length'] !== '' ? absint($_POST['min_length']) : null,
        'max_length' => isset($_POST['max_length']) && $_POST['max_length'] !== '' ? absint($_POST['max_length']) : null,
        'rows' => isset($_POST['rows']) && $_POST['rows'] !== '' ? absint($_POST['rows']) : null,
        'display_order' => 0
    );

    // Validate required fields
    if (empty($option_data['name'])) {
        error_log('DEBUG: Option name is empty');
        return false;
    }

    if (empty($option_data['parent_id'])) {
        error_log('DEBUG: Service ID is empty');
        return false;
    }

    // Check if editing existing option
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $option_id = absint($_POST['id']);
        error_log("DEBUG: Updating option ID: $option_id");

        $result = $wpdb->update(
            $services_table,
            $option_data,
            array('id' => $option_id, 'entity_type' => 'option'),
            null,
            array('%d', '%s')
        );

        if ($result !== false) {
            error_log("DEBUG: Option updated successfully");
            return $option_id;
        } else {
            error_log("DEBUG: Failed to update option: " . $wpdb->last_error);
            return false;
        }
    } else {
        // Creating new option
        error_log("DEBUG: Creating new option");

        $result = $wpdb->insert($services_table, $option_data);

        if ($result !== false) {
            $new_id = $wpdb->insert_id;
            error_log("DEBUG: Option created successfully with ID: $new_id");
            return $new_id;
        } else {
            error_log("DEBUG: Failed to create option: " . $wpdb->last_error);
            return false;
        }
    }
}



/**
 * Add this to your functions.php file
 * Compact MoBooking Debug Function
 */

if (!function_exists('mobooking_debug_panel')) {
    function mobooking_debug_panel()
    {
        // Only show for admins with debug enabled
        if (!current_user_can('administrator') || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Check tables
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';

        $services_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
        $options_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'") == $options_table;

        // Get data counts
        $total_services = $services_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $services_table") : 0;
        $user_services = $services_exists ? $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $services_table WHERE user_id = %d", $user_id)) : 0;
        $old_options = $services_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $services_table WHERE entity_type = 'option'") : 0;
        $new_options = $options_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $options_table") : 0;

        // Check for architecture type
        $is_unified = $old_options > 0;
        $is_separated = $options_exists && $new_options > 0;

?>
        <style>
            .mb-debug {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #1a1a1a;
                color: #fff;
                padding: 15px;
                border-radius: 8px;
                font-family: monospace;
                font-size: 12px;
                max-width: 350px;
                z-index: 99999;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                border-left: 4px solid #00ff00;
            }

            .mb-debug h4 {
                margin: 0 0 10px 0;
                color: #00ff00;
                font-size: 14px;
            }

            .mb-debug-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin: 10px 0;
            }

            .mb-debug-item {
                background: #2a2a2a;
                padding: 8px;
                border-radius: 4px;
                text-align: center;
            }

            .mb-debug-value {
                font-size: 16px;
                font-weight: bold;
                color: #00ff00;
            }

            .mb-debug-label {
                font-size: 10px;
                color: #ccc;
            }

            .mb-debug-status {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
            }

            .status-ok {
                background: #4CAF50;
            }

            .status-error {
                background: #f44336;
            }

            .status-warning {
                background: #FF9800;
            }

            .mb-debug-close {
                position: absolute;
                top: 5px;
                right: 8px;
                background: none;
                border: none;
                color: #fff;
                cursor: pointer;
                font-size: 16px;
            }

            .mb-debug-section {
                margin: 8px 0;
                padding: 8px;
                background: #2a2a2a;
                border-radius: 4px;
            }

            .mb-debug-code {
                background: #000;
                padding: 5px;
                border-radius: 3px;
                font-size: 10px;
                margin: 5px 0;
            }
        </style>

        <div class="mb-debug" id="mb-debug-panel">
            <button class="mb-debug-close" onclick="document.getElementById('mb-debug-panel').style.display='none'">×</button>
            <h4>🐛 MoBooking Debug</h4>

            <!-- Quick Stats -->
            <div class="mb-debug-grid">
                <div class="mb-debug-item">
                    <div class="mb-debug-value"><?php echo $user_services; ?></div>
                    <div class="mb-debug-label">Your Services</div>
                </div>
                <div class="mb-debug-item">
                    <div class="mb-debug-value"><?php echo $new_options; ?></div>
                    <div class="mb-debug-label">Options</div>
                </div>
            </div>

            <!-- Table Status -->
            <div class="mb-debug-section">
                <strong>📊 Tables:</strong><br>
                Services: <span class="mb-debug-status <?php echo $services_exists ? 'status-ok' : 'status-error'; ?>">
                    <?php echo $services_exists ? '✅ EXISTS' : '❌ MISSING'; ?>
                </span><br>
                Options: <span class="mb-debug-status <?php echo $options_exists ? 'status-ok' : 'status-error'; ?>">
                    <?php echo $options_exists ? '✅ EXISTS' : '❌ MISSING'; ?>
                </span>
            </div>

            <!-- Architecture Status -->
            <div class="mb-debug-section">
                <strong>🏗️ Architecture:</strong><br>
                <?php if (!$options_exists): ?>
                    <span class="mb-debug-status status-error">❌ SEPARATED TABLES MISSING</span>
                    <div class="mb-debug-code">
                        Run: $db_manager = new \MoBooking\Database\Manager();<br>
                        $db_manager->create_tables();
                    </div>
                <?php elseif ($is_unified && $old_options > 0): ?>
                    <span class="mb-debug-status status-warning">⚠️ MIGRATION NEEDED</span>
                    <div class="mb-debug-code">
                        Found <?php echo $old_options; ?> options in unified table.<br>
                        Run migration to separate tables.
                    </div>
                <?php else: ?>
                    <span class="mb-debug-status status-ok">✅ SEPARATED TABLES</span>
                <?php endif; ?>
            </div>

            <!-- Services Debug -->
            <?php if ($services_exists): ?>
                <div class="mb-debug-section">
                    <strong>🏢 Services Debug:</strong><br>
                    <?php
                    $recent_service = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $services_table WHERE user_id = %d ORDER BY id DESC LIMIT 1",
                        $user_id
                    ));

                    if ($recent_service) {
                        echo "Last: " . esc_html($recent_service->name) . " (ID: {$recent_service->id})<br>";
                        echo "Status: " . esc_html($recent_service->status);
                    } else {
                        echo '<span class="mb-debug-status status-warning">⚠️ NO SERVICES FOUND</span>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Quick Fix Actions -->
            <div class="mb-debug-section">
                <strong>🔧 Quick Actions:</strong><br>
                <button onclick="mbDebugCreateTables()" style="background:#4CAF50;color:white;border:none;padding:4px 8px;border-radius:3px;font-size:10px;margin:2px;">
                    Create Tables
                </button>
                <button onclick="mbDebugMigrate()" style="background:#FF9800;color:white;border:none;padding:4px 8px;border-radius:3px;font-size:10px;margin:2px;">
                    Run Migration
                </button>
                <button onclick="location.reload()" style="background:#2196F3;color:white;border:none;padding:4px 8px;border-radius:3px;font-size:10px;margin:2px;">
                    Refresh
                </button>
            </div>

            <div style="font-size:10px;color:#888;margin-top:10px;">
                Press Ctrl+Shift+D to toggle
            </div>
        </div>

        <script>
            // Toggle with keyboard shortcut
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                    e.preventDefault();
                    const panel = document.getElementById('mb-debug-panel');
                    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
                }
            });

            // Quick actions
            function mbDebugCreateTables() {
                if (confirm('Create/update database tables?')) {
                    jQuery.post(ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'mobooking_debug_create_tables',
                        nonce: '<?php echo wp_create_nonce('mobooking-debug'); ?>'
                    }, function(response) {
                        alert(response.success ? 'Tables created successfully!' : 'Error: ' + response.data);
                        location.reload();
                    });
                }
            }

            function mbDebugMigrate() {
                if (confirm('Run migration from unified to separated tables?')) {
                    jQuery.post(ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'mobooking_debug_migrate',
                        nonce: '<?php echo wp_create_nonce('mobooking-debug'); ?>'
                    }, function(response) {
                        alert(response.success ? 'Migration completed!' : 'Error: ' + response.data);
                        location.reload();
                    });
                }
            }

            console.log('🐛 MoBooking Debug Panel Loaded - Press Ctrl+Shift+D to toggle');
        </script>
<?php
    }
}

/**
 * AJAX handler for creating tables
 */
add_action('wp_ajax_mobooking_debug_create_tables', function () {
    if (!wp_verify_nonce($_POST['nonce'], 'mobooking-debug') || !current_user_can('administrator')) {
        wp_send_json_error('Permission denied');
    }

    try {
        $db_manager = new \MoBooking\Database\Manager();
        $db_manager->create_tables();
        wp_send_json_success('Tables created successfully');
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

/**
 * AJAX handler for migration
 */
add_action('wp_ajax_mobooking_debug_migrate', function () {
    if (!wp_verify_nonce($_POST['nonce'], 'mobooking-debug') || !current_user_can('administrator')) {
        wp_send_json_error('Permission denied');
    }

    try {
        // Check if migration class exists
        if (class_exists('\MoBooking\Database\SeparateTablesMigration')) {
            $migration = new \MoBooking\Database\SeparateTablesMigration();
            $result = $migration->run();
            wp_send_json_success($result ? 'Migration completed' : 'Migration failed');
        } else {
            wp_send_json_error('Migration class not found');
        }
    } catch (Exception $e) {
        wp_send_json_error('Error: ' . $e->getMessage());
    }
});

/**
 * Helper function to diagnose why services aren't showing
 */
if (!function_exists('mobooking_diagnose_services')) {
    function mobooking_diagnose_services()
    {
        global $wpdb;
        $user_id = get_current_user_id();
        $services_table = $wpdb->prefix . 'mobooking_services';

        echo "<div style='background:#fff;padding:20px;margin:20px 0;border:1px solid #ccc;'>";
        echo "<h3>🔍 Services Diagnosis</h3>";

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
        echo "<p><strong>Table exists:</strong> " . ($table_exists ? "✅ Yes" : "❌ No") . "</p>";

        if ($table_exists) {
            // Check total services
            $total = $wpdb->get_var("SELECT COUNT(*) FROM $services_table");
            echo "<p><strong>Total services:</strong> $total</p>";

            // Check user services
            $user_services = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $services_table WHERE user_id = %d", $user_id));
            echo "<p><strong>Your services:</strong> $user_services</p>";

            // Check for entity_type column (unified table)
            $has_entity_type = $wpdb->get_var("SHOW COLUMNS FROM $services_table LIKE 'entity_type'");
            echo "<p><strong>Has entity_type column:</strong> " . ($has_entity_type ? "✅ Yes (Unified)" : "❌ No (Separated)") . "</p>";

            if ($has_entity_type) {
                $actual_services = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $services_table WHERE user_id = %d AND (entity_type = 'service' OR entity_type IS NULL)",
                    $user_id
                ));
                echo "<p><strong>Actual services (filtered):</strong> $actual_services</p>";
            }

            // Show recent services
            $recent = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name, user_id, entity_type FROM $services_table WHERE user_id = %d ORDER BY id DESC LIMIT 3",
                $user_id
            ));

            if ($recent) {
                echo "<p><strong>Recent services:</strong></p><ul>";
                foreach ($recent as $service) {
                    echo "<li>ID: {$service->id}, Name: {$service->name}, Type: " . ($service->entity_type ?? 'NULL') . "</li>";
                }
                echo "</ul>";
            }
        }

        echo "</div>";
    }
}
