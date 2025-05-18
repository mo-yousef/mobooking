<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('MOBOOKING_VERSION', '1.0.0');
define('MOBOOKING_PATH', get_template_directory());
define('MOBOOKING_URL', get_template_directory_uri());

// Autoloader
spl_autoload_register(function ($class) {
    // Check if the class uses our namespace
    if (strpos($class, 'MoBooking\\') !== 0) {
        return;
    }
    
    // Remove namespace and replace \ with /
    $relative_class = str_replace('MoBooking\\', '', $class);
    $file = MOBOOKING_PATH . '/classes/' . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, load it
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize the theme
$loader = new MoBooking\Core\Loader();
$loader->init();

function mobooking_load_dashicons_frontend() {
    wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'mobooking_load_dashicons_frontend');

// Add script localization for AJAX functionality
function mobooking_register_scripts() {
    // Enqueue jQuery (already included with WordPress, but we'll make sure it's loaded)
    wp_enqueue_script('jquery');
    
    // Register and localize mobooking scripts
    wp_register_script('mobooking-admin-scripts', MOBOOKING_URL . '/assets/js/admin.js', array('jquery'), MOBOOKING_VERSION, true);
    
    // Localize scripts for various sections
    wp_localize_script('jquery', 'mobooking_services', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-service-nonce'),
        'option_nonce' => wp_create_nonce('mobooking-option-nonce') // Add this line
    ));
    
    wp_localize_script('jquery', 'mobooking_areas', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-area-nonce')
    ));
    
    wp_localize_script('jquery', 'mobooking_discounts', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-discount-nonce')
    ));
    
    wp_localize_script('jquery', 'mobooking_bookings', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking-booking-status-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'mobooking_register_scripts');
add_action('admin_enqueue_scripts', 'mobooking_register_scripts');





// This code should be added to functions.php or a similar loading file

/**
 * Register the Service Options Manager
 * Load the enhanced service options functionality
 */
function mobooking_register_service_options_manager() {
    // Load the OptionsManager class
    require_once MOBOOKING_PATH . '/classes/Services/OptionsManager.php';
    
    // Instantiate the options manager
    new \MoBooking\Services\OptionsManager();
    
    // Enqueue scripts for admin users or business owners
    // if (is_admin() || current_user_can('mobooking_business_owner')) {
        // jQuery UI for sortable functionality
        wp_enqueue_script('jquery-ui-sortable');
        
        // Add our custom styles
        wp_enqueue_style(
            'mobooking-service-options-styles',
            MOBOOKING_URL . '/assets/css/service-options.css', 
            array(), 
            MOBOOKING_VERSION
        );
        
        // Add our enhanced script
        wp_enqueue_script(
            'mobooking-service-options-manager',
            MOBOOKING_URL . '/assets/js/service-options-manager.js',
            array('jquery', 'jquery-ui-sortable'),
            MOBOOKING_VERSION,
            true
        );
    // }
}
add_action('init', 'mobooking_register_service_options_manager');

/**
 * Add the has_service_options method to Services Manager
 * This extends the Services\Manager class with the new functionality
 */
function mobooking_extend_services_manager() {
    add_filter('mobooking_services_manager_methods', 'mobooking_add_has_service_options_method');
}
add_action('init', 'mobooking_extend_services_manager');

/**
 * Add the has_service_options method to the Services Manager
 */
function mobooking_add_has_service_options_method($methods) {
    $methods['has_service_options'] = function($service_id) {
        // Create an instance of OptionsManager
        $options_manager = new \MoBooking\Services\OptionsManager();
        return $options_manager->has_service_options($service_id);
    };
    
    return $methods;
}

/**
 * Create the service options database table during plugin/theme activation
 */
function mobooking_create_service_options_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_service_options';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        service_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        description text NULL,
        type varchar(50) NOT NULL,
        is_required tinyint(1) NOT NULL DEFAULT 0,
        default_value text NULL,
        placeholder text NULL,
        min_value float NULL,
        max_value float NULL,
        price_impact decimal(10,2) NULL,
        price_type varchar(20) DEFAULT 'fixed',
        options text NULL,
        option_label text NULL,
        step varchar(50) NULL,
        unit varchar(50) NULL,
        min_length int(11) NULL,
        max_length int(11) NULL,
        rows int(11) NULL,
        display_order int(11) NOT NULL DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY service_id (service_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
// Register this function to be called during activation
// add_action('mobooking_activation', 'mobooking_create_service_options_table');

/**
 * Implementation note:
 * 
 * To use this enhanced service options manager:
 * 
 * 1. Save the OptionsManager.php class to classes/Services/OptionsManager.php
 * 2. Save the service-options-manager.js to assets/js/service-options-manager.js
 * 3. Save the service-options.css to assets/css/service-options.css
 * 4. Add this code to your functions.php or create a new file like service-options-loader.php
 *    and include it from your main plugin/theme file
 * 
 * The database table will be created during plugin/theme activation. If you need to
 * create it manually, uncomment the add_action line above.
 * 
 * You should also register this table creation in the main Database\Manager class by
 * adding 'service_options' to the $tables array and implementing the create_service_options_table
 * method if it's not already there.
 */





 // Temporary debugging function - REMOVE AFTER DEBUGGING
function check_nonce_validity() {
    if (!is_admin() || !is_user_logged_in()) {
        return;
    }
    
    $option_nonce = wp_create_nonce('mobooking-option-nonce');
    $service_nonce = wp_create_nonce('mobooking-service-nonce');
    
    // Verify both nonces immediately after creating them
    $option_valid = wp_verify_nonce($option_nonce, 'mobooking-option-nonce');
    $service_valid = wp_verify_nonce($service_nonce, 'mobooking-service-nonce');
    
    error_log("NONCE CHECK: option_nonce=$option_nonce, valid=$option_valid");
    error_log("NONCE CHECK: service_nonce=$service_nonce, valid=$service_valid");
}
add_action('admin_init', 'check_nonce_validity');

function log_nonce_life($life) {
    error_log("Nonce life: $life seconds");
    return $life;
}
add_filter('nonce_life', 'log_nonce_life');
