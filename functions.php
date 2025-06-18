<?php
/**
 * MoBooking Theme Functions - Organized & Clean
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('MOBOOKING_VERSION', '1.0.0');
define('MOBOOKING_PATH', get_template_directory());
define('MOBOOKING_URL', get_template_directory_uri());

// Load organized include files
require_once MOBOOKING_PATH . '/includes/autoloader.php';
require_once MOBOOKING_PATH . '/includes/theme-setup.php';
require_once MOBOOKING_PATH . '/includes/enqueue-scripts.php';
require_once MOBOOKING_PATH . '/includes/ajax-handlers.php';
require_once MOBOOKING_PATH . '/includes/helper-functions.php';

// /**
//  * Initialize the MoBooking theme
//  */
// function mobooking_init() {
//     try {
//         $loader = new MoBooking\Core\Loader();
//         $loader->init();
        
//         if (defined('WP_DEBUG') && WP_DEBUG) {
//             mobooking_log('Theme initialized successfully');
//         }
//     } catch (Exception $e) {
//         if (defined('WP_DEBUG') && WP_DEBUG) {
//             mobooking_log('Theme initialization error: ' . $e->getMessage(), 'error');
//         }
//     }
// }
// add_action('after_setup_theme', 'mobooking_init', 5);

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
 * Admin notices for theme requirements
 */
function mobooking_admin_notices() {
    if (!current_user_can('administrator')) {
        return;
    }

    // Display success message after attempting DB fix
    if (isset($_GET['mobooking_db_fixed']) && $_GET['mobooking_db_fixed'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Database table creation process was triggered. Please check if the missing table errors are resolved.', 'mobooking') . '</p></div>';
    }

    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>MoBooking:</strong> ';
        echo __('WooCommerce is recommended for payment processing. Please install and activate WooCommerce.', 'mobooking');
        echo '</p></div>';
    }
    
    // Check if database tables exist
    global $wpdb;
    $required_tables = array(
        'mobooking_services',
        'mobooking_service_options',
        'mobooking_bookings',
        'mobooking_areas',
        'mobooking_settings'
    );
    
    $missing_tables = array();
    foreach ($required_tables as $table) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>MoBooking:</strong> ';
        echo sprintf(__('Missing database tables: %s.', 'mobooking'), implode(', ', $missing_tables));

        // Add button to attempt fix
        $fix_url = wp_nonce_url(admin_url('?mobooking_fix_db=1'), 'mobooking_fix_db_nonce', 'mobooking_fix_db_security');
        echo ' <a href="' . esc_url($fix_url) . '" class="button button-primary">' . __('Attempt to Fix Database Tables', 'mobooking') . '</a>';

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
 * Initialize database on theme activation
 */
function mobooking_theme_activation() {
    try {
        // Create database tables
        if (class_exists('\MoBooking\Database\Manager')) {
            $db_manager = new \MoBooking\Database\Manager();
            $db_manager->create_tables();
        }
        
        // Create default pages if they don't exist
        mobooking_create_default_pages();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        mobooking_log('Theme activated successfully');
    } catch (Exception $e) {
        mobooking_log('Theme activation error: ' . $e->getMessage(), 'error');
    }
}
add_action('after_switch_theme', 'mobooking_theme_activation');

/**
 * Create default pages
 */
function mobooking_create_default_pages() {
    $pages = array(
        'login' => array(
            'title' => 'Login',
            'content' => '[mobooking_login_form]'
        ),
        'register' => array(
            'title' => 'Register',
            'content' => '[mobooking_registration_form]'
        ),
        'dashboard' => array(
            'title' => 'Dashboard',
            'content' => 'Dashboard content is handled by the theme.'
        )
    );
    
    foreach ($pages as $slug => $page_data) {
        $existing_page = get_page_by_path($slug);
        
        if (!$existing_page) {
            wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page'
            ));
        }
    }
}

/**
 * Development and debugging helpers
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    
    /**
     * Debug helper for development
     */
    function mobooking_debug_info() {
        if (!current_user_can('administrator') || !isset($_GET['mobooking_debug'])) {
            return;
        }
        
        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; font-family: monospace;">';
        echo '<h3>MoBooking Debug Information</h3>';
        
        // Theme info
        echo '<h4>Theme Information:</h4>';
        echo '<p>Version: ' . MOBOOKING_VERSION . '</p>';
        echo '<p>Path: ' . MOBOOKING_PATH . '</p>';
        echo '<p>URL: ' . MOBOOKING_URL . '</p>';
        
        // Database tables
        echo '<h4>Database Tables:</h4>';
        global $wpdb;
        $tables = array('services', 'service_options', 'bookings', 'areas', 'settings', 'discounts');
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . 'mobooking_' . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            echo '<p>' . $table . ': ' . ($exists ? '✅ EXISTS' : '❌ MISSING') . '</p>';
        }
        
        // Loaded classes
        echo '<h4>Loaded Classes:</h4>';
        $classes = get_declared_classes();
        $mobooking_classes = array_filter($classes, function($class) {
            return strpos($class, 'MoBooking') === 0;
        });
        foreach ($mobooking_classes as $class) {
            echo '<p>✅ ' . $class . '</p>';
        }
        
        echo '</div>';
    }
    add_action('wp_head', 'mobooking_debug_info');
    
    /**
     * Quick database fix button
     */
    function mobooking_debug_admin_bar($wp_admin_bar) {
        if (!current_user_can('administrator')) {
            return;
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'mobooking-debug',
            'title' => 'MoBooking Debug',
            'href' => add_query_arg('mobooking_debug', '1'),
        ));
        
        $wp_admin_bar->add_node(array(
            'id' => 'mobooking-fix-db',
            'parent' => 'mobooking-debug',
            'title' => 'Fix Database',
            'href' => add_query_arg('mobooking_fix_db', '1'),
        ));
    }
    add_action('admin_bar_menu', 'mobooking_debug_admin_bar', 999);
    
    /**
     * Quick database fix
     */
    function mobooking_debug_fix_database() {
        if (!isset($_GET['mobooking_fix_db'])) {
            return;
        }

        // Security check: Nonce verification
        if (!isset($_GET['mobooking_fix_db_security']) || !wp_verify_nonce($_GET['mobooking_fix_db_security'], 'mobooking_fix_db_nonce')) {
            wp_die(__('Security check failed. Please try again from the admin notices.', 'mobooking'));
        }

        if (!current_user_can('administrator')) {
            wp_die(__('You do not have permission to perform this action.', 'mobooking'));
        }
        
        try {
            if (class_exists('\MoBooking\Database\Manager')) {
                $db_manager = new \MoBooking\Database\Manager();
                $db_manager->create_tables();
                
                // Redirect with a success query arg
                $redirect_url = admin_url('index.php'); // Or any relevant admin page
                wp_redirect(add_query_arg('mobooking_db_fixed', '1', $redirect_url));
                exit;
            }
        } catch (Exception $e) {
            // Log the error
            if (function_exists('mobooking_log')) {
                mobooking_log('Database fix error: ' . $e->getMessage(), 'error');
            } elseif (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[MoBooking DB Setup] Database fix error: ' . $e->getMessage());
            }
            // Optionally, add an error message to the redirect URL
            $redirect_url = admin_url('index.php');
            wp_redirect(add_query_arg('mobooking_db_fix_error', '1', $redirect_url));
            exit;
        }
    }
    add_action('admin_init', 'mobooking_debug_fix_database'); // Changed to admin_init to ensure it runs before headers are sent for redirect and for admin context
}

/**
 * Performance optimizations
 */
function mobooking_performance_optimizations() {
    // Remove query strings for better caching
    add_filter('script_loader_src', 'remove_query_strings_from_static_resources', 15);
    add_filter('style_loader_src', 'remove_query_strings_from_static_resources', 15);
    
    // Disable file editing
    if (!defined('DISALLOW_FILE_EDIT')) {
        define('DISALLOW_FILE_EDIT', true);
    }
    
    // Limit post revisions
    if (!defined('WP_POST_REVISIONS')) {
        define('WP_POST_REVISIONS', 3);
    }
}
add_action('init', 'mobooking_performance_optimizations');

/**
 * Remove query strings from static resources
 */
function remove_query_strings_from_static_resources($src) {
    $parts = explode('?ver', $src);
    return $parts[0];
}

/**
 * Theme deactivation cleanup
 */
function mobooking_theme_deactivation() {
    // Clean up any temporary data or caches
    delete_transient('mobooking_cache');
    
    // Log deactivation
    mobooking_log('Theme deactivated');
}
add_action('switch_theme', 'mobooking_theme_deactivation');

/**
 * Prevent theme updates from repository
 */
function mobooking_prevent_theme_updates($r, $url) {
    if (0 !== strpos($url, 'https://api.wordpress.org/themes/update-check/1.1/')) {
        return $r;
    }
    
    $themes = json_decode($r['body']['themes'], true);
    unset($themes[get_template()]);
    $r['body']['themes'] = json_encode($themes);
    
    return $r;
}
add_filter('http_request_args', 'mobooking_prevent_theme_updates', 5, 2);






