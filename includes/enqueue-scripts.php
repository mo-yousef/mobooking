<?php
/**
 * MoBooking Asset Loading
 * Handles CSS and JavaScript enqueuing
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue main theme styles and scripts
 */
function mobooking_enqueue_assets() {
    // Main theme stylesheet
    wp_enqueue_style(
        'mobooking-style',
        get_stylesheet_uri(),
        array(),
        MOBOOKING_VERSION
    );
    
    // Dashicons for frontend use
    wp_enqueue_style('dashicons');
    
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
        'homeUrl' => home_url('/'),
        'themePath' => MOBOOKING_URL,
        'nonce' => wp_create_nonce('mobooking-main-nonce'),
        'strings' => array(
            'loading' => __('Loading...', 'mobooking'),
            'error' => __('An error occurred', 'mobooking'),
            'success' => __('Success!', 'mobooking'),
        )
    ));
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_assets');

/**
 * Enqueue dashboard-specific assets
 */
function mobooking_enqueue_dashboard_assets() {
    // Only load on dashboard pages
    if (!is_dashboard_page() || !is_user_logged_in()) {
        return;
    }
    
    // Check user permissions
    if (!mobooking_user_can_access_dashboard()) {
        return;
    }
    
    // Single unified dashboard CSS
    wp_enqueue_style(
        'mobooking-dashboard-complete',
        MOBOOKING_URL . '/assets/css/dashboard-complete.css',
        array('dashicons'),
        MOBOOKING_VERSION
    );
    

    // Single unified dashboard CSS
    wp_enqueue_style(
        'mobooking-dashboard-overview',
        MOBOOKING_URL . '/assets/css/dashboard-overview.css',
        array('dashicons'),
        MOBOOKING_VERSION
    );
    

    // Dashboard JavaScript with dependencies
    wp_enqueue_script(
        'mobooking-dashboard',
        MOBOOKING_URL . '/assets/js/dashboard.js',
        array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'),
        MOBOOKING_VERSION,
        true
    );
    
    // Media library for image uploads
    wp_enqueue_media();
    
    // Color picker for branding
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Enhanced dashboard localization
    $dashboard_data = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'userId' => get_current_user_id(),
        'currentSection' => get_query_var('section', 'overview'),
        'currentView' => isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list',
        'currentServiceId' => isset($_GET['service_id']) ? absint($_GET['service_id']) : 0,
        'activeTab' => isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info',
        'nonces' => array(
            'service' => wp_create_nonce('mobooking-service-nonce'),
            'booking' => wp_create_nonce('mobooking-booking-nonce'),
            'area' => wp_create_nonce('mobooking-area-nonce'),
            'discount' => wp_create_nonce('mobooking-discount-nonce'),
            'settings' => wp_create_nonce('mobooking-settings-nonce'),
        ),
        'strings' => array(
            'saving' => __('Saving...', 'mobooking'),
            'saved' => __('Saved successfully', 'mobooking'),
            'error' => __('Error occurred', 'mobooking'),
            'deleteConfirm' => __('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'),
            'selectService' => __('Please select at least one service', 'mobooking'),
            'fillRequired' => __('Please fill in all required fields', 'mobooking'),
            'invalidEmail' => __('Please enter a valid email address', 'mobooking'),
            'bookingSuccess' => __('Booking confirmed successfully!', 'mobooking'),
            'uploadImage' => __('Choose Image', 'mobooking'),
            'removeImage' => __('Remove Image', 'mobooking'),
        ),
        'urls' => array(
            'dashboard' => home_url('/dashboard/'),
            'services' => home_url('/dashboard/services/'),
            'bookings' => home_url('/dashboard/bookings/'),
            'settings' => home_url('/dashboard/settings/'),
        ),
        'config' => array(
            'autoSave' => true,
            'autoSaveInterval' => 30000, // 30 seconds
            'maxFileSize' => wp_max_upload_size(),
            'allowedImageTypes' => array('image/jpeg', 'image/png', 'image/gif', 'image/webp'),
        )
    );
    
    wp_localize_script('mobooking-dashboard', 'mobookingDashboard', $dashboard_data);
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_dashboard_assets', 15);

/**
 * Enqueue booking form assets
 */
function mobooking_enqueue_booking_form_assets() {
    // Only load on booking form pages
    if (!is_page() && !is_singular()) {
        return;
    }
    
    // Check if shortcode is present in content
    global $post;
    if (!$post || !has_shortcode($post->post_content, 'mobooking_booking_form')) {
        return;
    }
    
    // Booking form styles
    wp_enqueue_style(
        'mobooking-booking-form',
        MOBOOKING_URL . '/assets/css/booking-form.css',
        array(),
        MOBOOKING_VERSION
    );
    
    // Booking form JavaScript
    wp_enqueue_script(
        'mobooking-booking-form',
        MOBOOKING_URL . '/assets/js/booking-form.js',
        array('jquery'),
        MOBOOKING_VERSION,
        true
    );
    
    // Get user ID from shortcode attributes or URL
    $user_id = 0;
    if (isset($_GET['user']) && is_numeric($_GET['user'])) {
        $user_id = absint($_GET['user']);
    }
    
    // Localize booking form script
    wp_localize_script('mobooking-booking-form', 'mobookingBooking', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'userId' => $user_id,
        'nonces' => array(
            'booking' => wp_create_nonce('mobooking-booking-nonce'),
        ),
        'strings' => array(
            'error' => __('An error occurred', 'mobooking'),
            'selectService' => __('Please select at least one service', 'mobooking'),
            'fillRequired' => __('Please fill in all required fields', 'mobooking'),
            'invalidEmail' => __('Please enter a valid email address', 'mobooking'),
            'bookingSuccess' => __('Booking confirmed successfully!', 'mobooking'),
            'zipRequired' => __('Please enter a ZIP code', 'mobooking'),
            'zipInvalid' => __('Please enter a valid ZIP code', 'mobooking'),
            'zipNotCovered' => __('Sorry, we don\'t service this area', 'mobooking'),
            'zipCovered' => __('Great! We service your area', 'mobooking'),
            'discountInvalid' => __('Invalid discount code', 'mobooking'),
            'discountApplied' => __('Discount applied successfully', 'mobooking'),
        ),
        'currency' => array(
            'symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
            'position' => get_option('woocommerce_currency_pos', 'left')
        ),
        'autoAdvance' => array(
            'enabled' => true,
            'delay' => 1500,
        ),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
    ));
}
add_action('wp_enqueue_scripts', 'mobooking_enqueue_booking_form_assets');

/**
 * Enqueue admin-specific assets
 */
function mobooking_enqueue_admin_assets($hook) {
    // Only load on MoBooking admin pages
    if (strpos($hook, 'mobooking') === false) {
        return;
    }
    
    // Admin styles
    wp_enqueue_style(
        'mobooking-admin',
        MOBOOKING_URL . '/assets/css/admin.css',
        array('wp-color-picker'),
        MOBOOKING_VERSION
    );
    
    // Admin JavaScript
    wp_enqueue_script(
        'mobooking-admin',
        MOBOOKING_URL . '/assets/js/admin.js',
        array('jquery', 'wp-color-picker'),
        MOBOOKING_VERSION,
        true
    );
    
    // Localize admin script
    wp_localize_script('mobooking-admin', 'mobookingAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-admin-nonce'),
        'strings' => array(
            'confirmDelete' => __('Are you sure you want to delete this?', 'mobooking'),
            'saving' => __('Saving...', 'mobooking'),
            'saved' => __('Saved!', 'mobooking'),
            'error' => __('Error occurred', 'mobooking'),
        )
    ));
}
add_action('admin_enqueue_scripts', 'mobooking_enqueue_admin_assets');

/**
 * Optimize asset loading
 */
function mobooking_optimize_assets() {
    // Remove query strings from static resources for better caching
    add_filter('script_loader_src', 'mobooking_remove_query_strings', 15);
    add_filter('style_loader_src', 'mobooking_remove_query_strings', 15);
    
    // Defer non-critical JavaScript
    add_filter('script_loader_tag', 'mobooking_defer_scripts', 10, 3);
}
add_action('init', 'mobooking_optimize_assets');

// /**
//  * Remove query strings from URLs
//  */
// function mobooking_remove_query_strings($src) {
//     if (strpos($src, 'ver=')) {
//         $parts = explode('?ver', $src);
//         return $parts[0];
//     }
//     return $src;
// }

/**
 * Defer non-critical scripts
 */
function mobooking_defer_scripts($tag, $handle, $src) {
    // Scripts to defer
    $defer_scripts = array(
        'mobooking-main',
        'mobooking-booking-form'
    );
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace('<script ', '<script defer ', $tag);
    }
    
    return $tag;
}

/**
 * Preload critical assets
 */
function mobooking_preload_assets() {
    if (is_dashboard_page()) {
        echo '<link rel="preload" href="' . MOBOOKING_URL . '/assets/css/dashboard-complete.css" as="style">';
        echo '<link rel="preload" href="' . MOBOOKING_URL . '/assets/js/dashboard.js" as="script">';
    }
}
add_action('wp_head', 'mobooking_preload_assets', 1);

/**
 * Add critical CSS inline for better performance
 */
function mobooking_critical_css() {
    // Only add critical CSS to non-dashboard pages to avoid conflicts
    if (is_dashboard_page()) {
        return;
    }
    
    ?>
    <style id="mobooking-critical-css">
        /* Critical CSS for above-the-fold content */
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .mobooking-booking-form-container { max-width: 800px; margin: 0 auto; padding: 1rem; }
        .booking-progress { background: white; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .btn-primary { background: #3b82f6; color: white; padding: 0.75rem 1.25rem; border-radius: 0.5rem; border: none; cursor: pointer; }
    </style>
    <?php
}
add_action('wp_head', 'mobooking_critical_css', 5);

/**
 * Conditionally load assets based on page content
 */
function mobooking_conditional_assets() {
    global $post;
    
    // Load specific assets based on page content
    if ($post && is_a($post, 'WP_Post')) {
        // Check for specific shortcodes and load related assets
        if (has_shortcode($post->post_content, 'mobooking_login_form')) {
            wp_enqueue_style('mobooking-auth', MOBOOKING_URL . '/assets/css/auth.css', array(), MOBOOKING_VERSION);
            wp_enqueue_script('mobooking-auth', MOBOOKING_URL . '/assets/js/auth.js', array('jquery'), MOBOOKING_VERSION, true);
        }
        
        if (has_shortcode($post->post_content, 'mobooking_registration_form')) {
            wp_enqueue_style('mobooking-auth', MOBOOKING_URL . '/assets/css/auth.css', array(), MOBOOKING_VERSION);
            wp_enqueue_script('mobooking-auth', MOBOOKING_URL . '/assets/js/auth.js', array('jquery'), MOBOOKING_VERSION, true);
        }
    }
}
add_action('wp_enqueue_scripts', 'mobooking_conditional_assets', 20);

/**
 * Development mode asset loading
 */
function mobooking_dev_assets() {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Load unminified versions in development
    add_filter('script_loader_src', function($src) {
        if (strpos($src, 'mobooking') !== false && strpos($src, '.min.js') !== false) {
            return str_replace('.min.js', '.js', $src);
        }
        return $src;
    });
    
    add_filter('style_loader_src', function($src) {
        if (strpos($src, 'mobooking') !== false && strpos($src, '.min.css') !== false) {
            return str_replace('.min.css', '.css', $src);
        }
        return $src;
    });
}
add_action('wp_enqueue_scripts', 'mobooking_dev_assets', 1);