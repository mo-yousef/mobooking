<?php
/**
 * MoBooking Theme Setup
 * Handles theme support, menus, and basic configuration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme setup function
 */
function mobooking_theme_setup() {
    // Add theme support for various features
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form', 
        'comment-form', 
        'comment-list', 
        'gallery', 
        'caption',
        'style',
        'script'
    ));
    
    // Add theme support for responsive embeds
    add_theme_support('responsive-embeds');
    
    // Add theme support for editor styles
    add_theme_support('editor-styles');
    
    // Add theme support for wide alignment
    add_theme_support('align-wide');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'mobooking'),
        'footer' => esc_html__('Footer Menu', 'mobooking'),
        'dashboard' => esc_html__('Dashboard Menu', 'mobooking'),
    ));
    
    // Set content width for media
    global $content_width;
    if (!isset($content_width)) {
        $content_width = 1200;
    }
    
    // Load textdomain for translations
    load_theme_textdomain('mobooking', MOBOOKING_PATH . '/languages');
    
    // Add custom image sizes
    add_image_size('mobooking-service-thumbnail', 300, 200, true);
    add_image_size('mobooking-service-large', 600, 400, true);
    add_image_size('mobooking-avatar', 100, 100, true);
}
add_action('after_setup_theme', 'mobooking_theme_setup');

/**
 * Register custom post types if needed
 */
function mobooking_register_post_types() {
    // Currently using custom database tables instead of post types
    // This function is available for future expansion
    
    // Example: Register a testimonials post type
    /*
    register_post_type('testimonial', array(
        'labels' => array(
            'name' => __('Testimonials', 'mobooking'),
            'singular_name' => __('Testimonial', 'mobooking'),
        ),
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true,
    ));
    */
}
add_action('init', 'mobooking_register_post_types');

/**
 * Register widget areas
 */
function mobooking_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Primary Sidebar', 'mobooking'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'mobooking'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar(array(
        'name'          => esc_html__('Footer Sidebar', 'mobooking'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Add footer widgets here.', 'mobooking'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="footer-widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'mobooking_widgets_init');

/**
 * Add custom body classes
 */
function mobooking_body_classes($classes) {
    // Add logged in class
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

    // Add dashboard page class
    if (is_dashboard_page()) {
        $classes[] = 'mobooking-dashboard-page';
        $section = get_query_var('section', 'overview');
        $classes[] = 'dashboard-section-' . sanitize_html_class($section);
    }
    
    // Add mobile class for better responsive handling
    if (wp_is_mobile()) {
        $classes[] = 'mobile-device';
    }

    return $classes;
}
add_filter('body_class', 'mobooking_body_classes');

/**
 * Customize excerpt length
 */
function mobooking_excerpt_length($length) {
    return 30;
}
add_filter('excerpt_length', 'mobooking_excerpt_length');

/**
 * Customize excerpt more text
 */
function mobooking_excerpt_more($more) {
    return '&hellip;';
}
add_filter('excerpt_more', 'mobooking_excerpt_more');

/**
 * Security headers
 */
function mobooking_security_headers() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
add_action('send_headers', 'mobooking_security_headers');

/**
 * Clean up WordPress head
 */
function mobooking_cleanup_head() {
    // Remove unnecessary meta tags and links
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    
    // Remove emoji scripts and styles
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
}
add_action('init', 'mobooking_cleanup_head');

/**
 * Remove query strings from static resources
 */
function mobooking_remove_query_strings($src) {
    $parts = explode('?ver', $src);
    return $parts[0];
}
add_filter('script_loader_src', 'mobooking_remove_query_strings', 15, 1);
add_filter('style_loader_src', 'mobooking_remove_query_strings', 15, 1);

/**
 * Disable file editing from admin
 */
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * Custom login logo
 */
function mobooking_login_logo() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($logo) {
            ?>
            <style type="text/css">
                #login h1 a, .login h1 a {
                    background-image: url(<?php echo $logo[0]; ?>);
                    height: 80px;
                    width: 320px;
                    background-size: contain;
                    background-repeat: no-repeat;
                    padding-bottom: 30px;
                }
            </style>
            <?php
        }
    }
}
add_action('login_enqueue_scripts', 'mobooking_login_logo');

/**
 * Custom login logo URL
 */
function mobooking_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'mobooking_login_logo_url');

/**
 * Custom login logo title
 */
function mobooking_login_logo_url_title() {
    return get_bloginfo('name');
}
add_filter('login_headertext', 'mobooking_login_logo_url_title');