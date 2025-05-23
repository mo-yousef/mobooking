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

    // // Main theme JavaScript
    // wp_enqueue_script(
    //     'mobooking-main',
    //     MOBOOKING_URL . '/assets/js/main.js',
    //     array('jquery'),
    //     MOBOOKING_VERSION,
    //     true
    // );

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
        ),
        // Add services-specific configuration
        'serviceNonce' => wp_create_nonce('mobooking-service-nonce'),
        'currentServiceId' => isset($_GET['service_id']) ? absint($_GET['service_id']) : 0,
        'currentView' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list',
        'activeTab' => isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info',
        'endpoints' => array(
            'saveService' => 'mobooking_save_service',
            'deleteService' => 'mobooking_delete_service',
            'getService' => 'mobooking_get_service',
            'saveOption' => 'mobooking_save_service_option',
            'deleteOption' => 'mobooking_delete_service_option',
            'getOptions' => 'mobooking_get_service_options',
            'getOption' => 'mobooking_get_service_option',
            'updateOptionsOrder' => 'mobooking_update_options_order'
        )
    );

    wp_localize_script('mobooking-dashboard', 'mobookingDashboard', $dashboard_data);
    wp_localize_script('mobooking-services-handler', 'mobookingServices', $dashboard_data);
wp_localize_script('mobooking-dashboard', 'mobookingConfig', array(
    'primaryHandler' => 'dashboard' // or 'service-handler'
));
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
























/**
 * Debug functions for MoBooking Services
 * Add these to your functions.php file temporarily for debugging
 */

// Test AJAX handler to verify things are working
add_action('wp_ajax_mobooking_test', 'mobooking_test_ajax');
function mobooking_test_ajax() {
    wp_send_json_success(array(
        'message' => 'AJAX is working!',
        'time' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ));
}

// Debug function to check if services classes exist
add_action('wp_ajax_mobooking_debug_services', 'mobooking_debug_services');
function mobooking_debug_services() {
    $debug_info = array();
    
    // Check if classes exist
    $debug_info['ServiceManager_exists'] = class_exists('\MoBooking\Services\ServiceManager');
    $debug_info['ServiceOptionsManager_exists'] = class_exists('\MoBooking\Services\ServiceOptionsManager');
    
    // Check if actions are registered
    global $wp_filter;
    $debug_info['ajax_actions'] = array();
    
    $actions_to_check = array(
        'wp_ajax_mobooking_get_service_options',
        'wp_ajax_mobooking_save_service_option',
        'wp_ajax_mobooking_test'
    );
    
    foreach ($actions_to_check as $action) {
        $debug_info['ajax_actions'][$action] = isset($wp_filter[$action]);
    }
    
    // Check database tables
    global $wpdb;
    $services_table = $wpdb->prefix . 'mobooking_services';
    $options_table = $wpdb->prefix . 'mobooking_service_options';
    
    $debug_info['tables'] = array(
        'services' => $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table,
        'options' => $wpdb->get_var("SHOW TABLES LIKE '$options_table'") == $options_table
    );
    
    // Check current user capabilities
    $debug_info['user_can_manage'] = current_user_can('mobooking_business_owner') || current_user_can('administrator');
    $debug_info['current_user_id'] = get_current_user_id();
    $debug_info['current_user_roles'] = wp_get_current_user()->roles;
    
    wp_send_json_success($debug_info);
}

// Function to manually create the missing service options table
add_action('wp_ajax_mobooking_fix_tables', 'mobooking_fix_tables');
function mobooking_fix_tables() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_service_options';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Force create the service options table
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        service_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        description text NULL,
        type varchar(50) NOT NULL DEFAULT 'checkbox',
        is_required tinyint(1) DEFAULT 0,
        default_value text NULL,
        placeholder text NULL,
        min_value float NULL,
        max_value float NULL,
        price_impact decimal(10,2) DEFAULT 0,
        price_type varchar(20) DEFAULT 'fixed',
        options text NULL,
        option_label text NULL,
        step varchar(50) NULL,
        unit varchar(50) NULL,
        min_length int(11) NULL,
        max_length int(11) NULL,
        rows int(11) NULL,
        display_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY service_id (service_id),
        KEY display_order (display_order)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    // Verify creation
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    wp_send_json_success(array(
        'table_exists' => $exists,
        'dbdelta_result' => $result,
        'message' => $exists ? 'Service options table created successfully!' : 'Failed to create table'
    ));
}


















// Add these debugging functions to your functions.php file

/**
 * Database diagnostic AJAX handler
 */
add_action('wp_ajax_mobooking_db_diagnostic', 'mobooking_db_diagnostic');
function mobooking_db_diagnostic() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
    }
    
    global $wpdb;
    
    $diagnostic = array(
        'tables' => array(),
        'user' => array(
            'id' => get_current_user_id(),
            'can_manage' => current_user_can('mobooking_business_owner') || current_user_can('administrator'),
            'roles' => wp_get_current_user()->roles
        ),
        'ajax_actions' => array(),
        'database_info' => array()
    );
    
    // Check tables
    $tables_to_check = array('services', 'service_options', 'bookings', 'discounts', 'areas', 'settings');
    
    foreach ($tables_to_check as $table) {
        $full_table_name = $wpdb->prefix . 'mobooking_' . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
        
        $diagnostic['tables'][$table] = array(
            'exists' => $exists,
            'name' => $full_table_name
        );
        
        if ($exists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $full_table_name");
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
            
            $diagnostic['tables'][$table]['columns'] = array_map(function($col) {
                return $col->Field;
            }, $columns);
            $diagnostic['tables'][$table]['row_count'] = intval($row_count);
        }
    }
    
    // Check AJAX actions
    global $wp_filter;
    $actions_to_check = array(
        'wp_ajax_mobooking_save_service_option',
        'wp_ajax_mobooking_get_service_options',
        'wp_ajax_mobooking_save_service',
        'wp_ajax_mobooking_test'
    );
    
    foreach ($actions_to_check as $action) {
        $diagnostic['ajax_actions'][$action] = isset($wp_filter[$action]) && !empty($wp_filter[$action]->callbacks);
    }
    
    // Database info
    $diagnostic['database_info'] = array(
        'mysql_version' => $wpdb->get_var('SELECT VERSION()'),
        'charset' => $wpdb->charset,
        'collate' => $wpdb->collate,
        'prefix' => $wpdb->prefix
    );
    
    wp_send_json_success($diagnostic);
}

/**
 * Force create missing tables
 */
add_action('wp_ajax_mobooking_create_tables', 'mobooking_create_tables');
function mobooking_create_tables() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Access denied');
    }
    
    try {
        $db_manager = new \MoBooking\Database\Manager();
        $db_manager->create_tables();
        
        wp_send_json_success('Tables created successfully');
    } catch (Exception $e) {
        wp_send_json_error('Error creating tables: ' . $e->getMessage());
    }
}