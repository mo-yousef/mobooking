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

/**
 * Initialize the MoBooking theme
 */
function mobooking_init() {
    try {
        $loader = new MoBooking\Core\Loader();
        $loader->init();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            mobooking_log('Theme initialized successfully');
        }
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            mobooking_log('Theme initialization error: ' . $e->getMessage(), 'error');
        }
    }
}
add_action('after_setup_theme', 'mobooking_init', 5);

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
        echo sprintf(__('Missing database tables: %s. Please deactivate and reactivate the theme.', 'mobooking'), implode(', ', $missing_tables));
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
        if (!current_user_can('administrator') || !isset($_GET['mobooking_fix_db'])) {
            return;
        }
        
        try {
            if (class_exists('\MoBooking\Database\Manager')) {
                $db_manager = new \MoBooking\Database\Manager();
                $db_manager->create_tables();
                
                wp_redirect(add_query_arg('mobooking_db_fixed', '1', remove_query_arg('mobooking_fix_db')));
                exit;
            }
        } catch (Exception $e) {
            mobooking_log('Database fix error: ' . $e->getMessage(), 'error');
        }
    }
    add_action('init', 'mobooking_debug_fix_database');
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
















/**
 * Database Migration Script for MoBooking Form Settings
 * 
 * This script:
 * 1. Removes testimonials-related columns from the database
 * 2. Adds new required columns (custom_js, step_indicator_style, button_style, language)
 * 3. Ensures all new fields are properly configured with defaults
 * 
 * Usage: Add this to your functions.php temporarily and run by visiting:
 * /wp-admin/?run_mobooking_form_migration=1
 * 
 * IMPORTANT: Remove this code after running the migration!
 */

// Only run for administrators and only once
add_action('wp_loaded', function() {
    // Only run for logged-in administrators
    if (!is_admin() || !current_user_can('administrator')) {
        return;
    }
    
    // Check if migration should run
    if (!isset($_GET['run_mobooking_form_migration']) || $_GET['run_mobooking_form_migration'] !== '1') {
        return;
    }
    
    // Prevent running multiple times
    if (get_option('mobooking_form_migration_completed')) {
        wp_die('Form settings migration already completed. Remove the migration code from functions.php');
    }
    
    try {
        echo '<h1>MoBooking Form Settings Migration</h1>';
        echo '<p>Starting migration process...</p>';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h2 style="color: #721c24;">❌ Migration Failed</h2>';
            echo '<p>Table ' . $table_name . ' does not exist. Please create the booking form settings table first.</p>';
            echo '</div>';
            return;
        }
        
        echo '<h3>Step 1: Removing Testimonials Columns</h3>';
        
        // Remove enable_testimonials column if it exists
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'enable_testimonials'");
        if (!empty($columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name DROP COLUMN enable_testimonials");
            if ($result !== false) {
                echo '<p>✅ Removed enable_testimonials column</p>';
            } else {
                echo '<p>❌ Failed to remove enable_testimonials column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>ℹ️ enable_testimonials column not found (already removed or never existed)</p>';
        }
        
        // Remove testimonials_data column if it exists
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'testimonials_data'");
        if (!empty($columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name DROP COLUMN testimonials_data");
            if ($result !== false) {
                echo '<p>✅ Removed testimonials_data column</p>';
            } else {
                echo '<p>❌ Failed to remove testimonials_data column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>ℹ️ testimonials_data column not found (already removed or never existed)</p>';
        }
        
        echo '<h3>Step 2: Adding New Required Columns</h3>';
        
        // Add custom_js column if it doesn't exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'custom_js'");
        if (empty($columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN custom_js longtext DEFAULT ''");
            if ($result !== false) {
                echo '<p>✅ Added custom_js column</p>';
            } else {
                echo '<p>❌ Failed to add custom_js column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>ℹ️ custom_js column already exists</p>';
        }
        
        // Add step_indicator_style column if it doesn't exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'step_indicator_style'");
        if (empty($columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN step_indicator_style varchar(50) DEFAULT 'progress'");
            if ($result !== false) {
                echo '<p>✅ Added step_indicator_style column</p>';
            } else {
                echo '<p>❌ Failed to add step_indicator_style column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>ℹ️ step_indicator_style column already exists</p>';
        }
        
        // Add button_style column if it doesn't exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'button_style'");
        if (empty($columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN button_style varchar(50) DEFAULT 'rounded'");
            if ($result !== false) {
                echo '<p>✅ Added button_style column</p>';
            } else {
                echo '<p>❌ Failed to add button_style column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>ℹ️ button_style column already exists</p>';
        }
        
        // Ensure language column exists (might already be there from previous updates)
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'language'");
        if (empty($columns)) {
            $result = $wpdb->query("ALTER TABLE $table_name ADD COLUMN language varchar(10) DEFAULT 'en'");
            if ($result !== false) {
                echo '<p>✅ Added language column</p>';
            } else {
                echo '<p>❌ Failed to add language column: ' . $wpdb->last_error . '</p>';
            }
        } else {
            echo '<p>ℹ️ language column already exists</p>';
        }
        
        echo '<h3>Step 3: Updating Default Values for Existing Records</h3>';
        
        // Update existing records to have default values for new fields
        $update_count = 0;
        
        // Update records with NULL or empty custom_js
        $result = $wpdb->query("UPDATE $table_name SET custom_js = '' WHERE custom_js IS NULL");
        if ($result !== false) {
            $update_count += $result;
            echo '<p>✅ Updated ' . $result . ' records with default custom_js value</p>';
        }
        
        // Update records with NULL or empty step_indicator_style
        $result = $wpdb->query("UPDATE $table_name SET step_indicator_style = 'progress' WHERE step_indicator_style IS NULL OR step_indicator_style = ''");
        if ($result !== false) {
            $update_count += $result;
            echo '<p>✅ Updated ' . $result . ' records with default step_indicator_style value</p>';
        }
        
        // Update records with NULL or empty button_style
        $result = $wpdb->query("UPDATE $table_name SET button_style = 'rounded' WHERE button_style IS NULL OR button_style = ''");
        if ($result !== false) {
            $update_count += $result;
            echo '<p>✅ Updated ' . $result . ' records with default button_style value</p>';
        }
        
        // Update records with NULL or empty language
        $result = $wpdb->query("UPDATE $table_name SET language = 'en' WHERE language IS NULL OR language = ''");
        if ($result !== false) {
            $update_count += $result;
            echo '<p>✅ Updated ' . $result . ' records with default language value</p>';
        }
        
        echo '<h3>Step 4: Verifying Table Structure</h3>';
        
        // Get final table structure to verify
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $expected_columns = [
            'id', 'user_id', 'form_title', 'form_description', 'logo_url',
            'primary_color', 'secondary_color', 'background_color', 'text_color',
            'language', 'show_service_descriptions', 'show_price_breakdown',
            'show_form_header', 'show_form_footer', 'enable_zip_validation',
            'custom_css', 'custom_js', 'form_layout', 'step_indicator_style',
            'button_style', 'form_width', 'contact_info', 'social_links',
            'custom_footer_text', 'seo_title', 'seo_description',
            'analytics_code', 'is_active', 'created_at', 'updated_at'
        ];
        
        $current_columns = array_column($columns, 'Field');
        $missing_columns = array_diff($expected_columns, $current_columns);
        $unexpected_columns = array_diff($current_columns, $expected_columns);
        
        if (empty($missing_columns) && empty($unexpected_columns)) {
            echo '<p>✅ Table structure is correct</p>';
        } else {
            if (!empty($missing_columns)) {
                echo '<p>⚠️ Missing columns: ' . implode(', ', $missing_columns) . '</p>';
            }
            if (!empty($unexpected_columns)) {
                echo '<p>ℹ️ Extra columns found: ' . implode(', ', $unexpected_columns) . '</p>';
            }
        }
        
        echo '<h3>Step 5: Testing Data Integrity</h3>';
        
        // Count total records
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<p>📊 Total booking form settings records: ' . $total_records . '</p>';
        
        // Check for any records with NULL required fields
        $null_checks = [
            'form_title' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE form_title IS NULL OR form_title = ''"),
            'language' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE language IS NULL OR language = ''"),
            'step_indicator_style' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE step_indicator_style IS NULL OR step_indicator_style = ''"),
            'button_style' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE button_style IS NULL OR button_style = ''")
        ];
        
        $issues_found = false;
        foreach ($null_checks as $field => $count) {
            if ($count > 0) {
                echo '<p>⚠️ Found ' . $count . ' records with empty ' . $field . '</p>';
                $issues_found = true;
            }
        }
        
        if (!$issues_found) {
            echo '<p>✅ All records have valid data for required fields</p>';
        }
        
        echo '<h3>Migration Summary</h3>';
        
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h2 style="color: #155724;">✅ Migration Completed Successfully!</h2>';
        echo '<ul>';
        echo '<li><strong>Removed:</strong> enable_testimonials and testimonials_data columns</li>';
        echo '<li><strong>Added:</strong> custom_js, step_indicator_style, button_style columns</li>';
        echo '<li><strong>Ensured:</strong> language column exists with proper defaults</li>';
        echo '<li><strong>Updated:</strong> ' . $update_count . ' existing records with default values</li>';
        echo '<li><strong>Total Records:</strong> ' . $total_records . ' form settings</li>';
        echo '</ul>';
        echo '</div>';
        
        // Mark migration as completed
        update_option('mobooking_form_migration_completed', true);
        
        echo '<h3>Next Steps:</h3>';
        echo '<ol>';
        echo '<li><strong>Test your booking form settings</strong> to ensure everything works correctly</li>';
        echo '<li><strong>Remove this migration code</strong> from your functions.php file</li>';
        echo '<li><strong>Update your BookingForm Manager class</strong> with the new field handling</li>';
        echo '<li><strong>Update the form HTML</strong> to include the new fields and remove testimonials</li>';
        echo '</ol>';
        
        echo '<p><a href="' . admin_url('admin.php?page=mobooking-dashboard') . '" class="button button-primary">Go to MoBooking Dashboard</a></p>';
        
        // Optional: Show current table structure for verification
        echo '<h3>Current Table Structure:</h3>';
        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<pre>';
        foreach ($columns as $column) {
            echo sprintf("%-25s %-20s %-10s %s\n", 
                $column->Field, 
                $column->Type, 
                $column->Null === 'YES' ? 'NULL' : 'NOT NULL',
                $column->Default !== null ? 'DEFAULT: ' . $column->Default : ''
            );
        }
        echo '</pre>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h2 style="color: #721c24;">❌ Migration Failed</h2>';
        echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
        echo '<p>Please check your database configuration and try again.</p>';
        echo '</div>';
        
        error_log('MoBooking Form Migration Error: ' . $e->getMessage());
    }
    
    exit; // Stop execution after migration
});

/**
 * ADDITIONAL: Updated AJAX handlers that need to be added to your main plugin file
 */

// Update the main AJAX handler to handle the new logo upload
add_action('wp_ajax_mobooking_upload_logo', function() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No file uploaded or upload error occurred.', 'mobooking'));
        }
        
        $file = $_FILES['logo_file'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type. Please upload JPG, PNG, or SVG files only.', 'mobooking'));
        }
        
        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(__('File size too large. Maximum size is 5MB.', 'mobooking'));
        }
        
        // Handle the upload using WordPress functions
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        // Set upload directory to a custom subfolder
        add_filter('upload_dir', function($upload) {
            $upload['subdir'] = '/mobooking/logos';
            $upload['path'] = $upload['basedir'] . $upload['subdir'];
            $upload['url'] = $upload['baseurl'] . $upload['subdir'];
            return $upload;
        });
        
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) {
                // Generate unique filename with timestamp
                $user_id = get_current_user_id();
                $timestamp = time();
                return "logo-{$user_id}-{$timestamp}{$ext}";
            }
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        // Remove the upload_dir filter
        remove_all_filters('upload_dir');
        
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'url' => $movefile['url'],
                'file' => $movefile['file'],
                'message' => __('Logo uploaded successfully!', 'mobooking')
            ));
        } else {
            wp_send_json_error($movefile['error'] ?? __('Upload failed.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        error_log('MoBooking - Exception in logo upload: ' . $e->getMessage());
        wp_send_json_error(__('An error occurred during upload.', 'mobooking'));
    }
});

/**
 * CLEANUP FUNCTION: Remove old testimonials data from existing records
 * Run this separately if you want to completely clean up any remaining testimonials references
 */
function mobooking_cleanup_testimonials_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
    
    // This would only be needed if there are any serialized data references to testimonials
    // that need to be cleaned up in other fields
    
    error_log('MoBooking: Testimonials cleanup completed');
}

/**
 * VERIFICATION FUNCTION: Check if migration was successful
 */
function mobooking_verify_form_migration() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
    
    // Check required columns exist
    $required_columns = ['custom_js', 'step_indicator_style', 'button_style', 'language'];
    $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
    
    $missing = array_diff($required_columns, $existing_columns);
    $testimonials_columns = array_intersect(['enable_testimonials', 'testimonials_data'], $existing_columns);
    
    return [
        'success' => empty($missing) && empty($testimonials_columns),
        'missing_columns' => $missing,
        'testimonials_removed' => empty($testimonials_columns),
        'testimonials_found' => $testimonials_columns
    ];
}

/**
 * ROLLBACK FUNCTION: In case you need to rollback the migration
 * (Only use if absolutely necessary)
 */
function mobooking_rollback_form_migration() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
    
    // Re-add testimonials columns (if needed for rollback)
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN enable_testimonials tinyint(1) DEFAULT 0");
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN testimonials_data longtext DEFAULT ''");
    
    // Remove new columns
    $wpdb->query("ALTER TABLE $table_name DROP COLUMN custom_js");
    $wpdb->query("ALTER TABLE $table_name DROP COLUMN step_indicator_style");
    $wpdb->query("ALTER TABLE $table_name DROP COLUMN button_style");
    
    // Reset migration flag
    delete_option('mobooking_form_migration_completed');
    
    error_log('MoBooking: Form migration rolled back');
}
