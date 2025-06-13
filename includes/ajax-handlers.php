<?php
/**
 * MoBooking AJAX Handlers - UPDATED for Normalized Database
 * Centralized AJAX request handling with proper transaction support
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register all AJAX handlers
 */
function mobooking_register_ajax_handlers() {
    // Booking-related AJAX handlers
    add_action('wp_ajax_mobooking_check_zip_coverage', 'mobooking_ajax_check_zip_coverage');
    add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', 'mobooking_ajax_check_zip_coverage');
    
    add_action('wp_ajax_mobooking_get_service_options', 'mobooking_ajax_get_service_options');
    add_action('wp_ajax_nopriv_mobooking_get_service_options', 'mobooking_ajax_get_service_options');
    
    add_action('wp_ajax_mobooking_save_booking', 'mobooking_ajax_save_booking');
    add_action('wp_ajax_nopriv_mobooking_save_booking', 'mobooking_ajax_save_booking');
    
    add_action('wp_ajax_mobooking_validate_discount', 'mobooking_ajax_validate_discount');
    add_action('wp_ajax_nopriv_mobooking_validate_discount', 'mobooking_ajax_validate_discount');
    
    // Dashboard AJAX handlers
    add_action('wp_ajax_mobooking_save_service', 'mobooking_ajax_save_service');
    add_action('wp_ajax_mobooking_delete_service', 'mobooking_ajax_delete_service');
    add_action('wp_ajax_mobooking_save_service_option', 'mobooking_ajax_save_service_option');
    add_action('wp_ajax_mobooking_delete_service_option', 'mobooking_ajax_delete_service_option');
    add_action('wp_ajax_mobooking_update_options_order', 'mobooking_ajax_update_options_order');
    
    // Settings AJAX handlers
    add_action('wp_ajax_mobooking_save_settings', 'mobooking_ajax_save_settings');
    add_action('wp_ajax_mobooking_send_test_email', 'mobooking_ajax_send_test_email');
    add_action('wp_ajax_mobooking_export_data', 'mobooking_ajax_export_data');
    add_action('wp_ajax_mobooking_import_data', 'mobooking_ajax_import_data');
    
    // Areas AJAX handlers
    add_action('wp_ajax_mobooking_save_area', 'mobooking_ajax_save_area');
    add_action('wp_ajax_mobooking_delete_area', 'mobooking_ajax_delete_area');
    
    // Discount AJAX handlers
    add_action('wp_ajax_mobooking_save_discount', 'mobooking_ajax_save_discount');
    add_action('wp_ajax_mobooking_delete_discount', 'mobooking_ajax_delete_discount');
    
    // Auth AJAX handlers
    add_action('wp_ajax_nopriv_mobooking_login', 'mobooking_ajax_login');
    add_action('wp_ajax_nopriv_mobooking_register', 'mobooking_ajax_register');
    add_action('wp_ajax_mobooking_logout', 'mobooking_ajax_logout');
    
    // Utility AJAX handlers
    add_action('wp_ajax_mobooking_upload_image', 'mobooking_ajax_upload_image');
    add_action('wp_ajax_mobooking_get_dashboard_stats', 'mobooking_ajax_get_dashboard_stats');
    
    // NEW: Booking management AJAX handlers for normalized structure
    add_action('wp_ajax_mobooking_get_user_bookings', 'mobooking_ajax_get_user_bookings');
    add_action('wp_ajax_mobooking_update_booking_status', 'mobooking_ajax_update_booking_status');
    add_action('wp_ajax_mobooking_get_booking_details', 'mobooking_ajax_get_booking_details');
    add_action('wp_ajax_mobooking_delete_booking', 'mobooking_ajax_delete_booking');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: AJAX handlers registered successfully with normalized database support');
    }
}

/**
 * ENHANCED: Reset booking form settings with transaction support
 */
function mobooking_ajax_reset_booking_form_settings() {
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Reset settings using BookingForm Manager
        if (class_exists('\MoBooking\BookingForm\Manager')) {
            $booking_form_manager = new \MoBooking\BookingForm\Manager();
            $result = $booking_form_manager->reset_settings($user_id);
        } else {
            // Fallback: Direct database reset
            $result = $wpdb->delete(
                $wpdb->prefix . 'mobooking_booking_form_settings',
                array('user_id' => $user_id),
                array('%d')
            );
        }
        
        if ($result !== false) {
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'message' => __('Settings reset to defaults successfully.', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking - Exception in reset booking form settings: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while resetting settings.', 'mobooking'));
    }
}

add_action('wp_ajax_mobooking_reset_booking_form_settings', 'mobooking_ajax_reset_booking_form_settings');

/**
 * NEW: Database health check AJAX handler
 */
function mobooking_ajax_database_health_check() {
    try {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        global $wpdb;
        
        $health_report = array(
            'database_engine' => $wpdb->get_var("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->prefix}mobooking_bookings' LIMIT 1"),
            'tables_status' => array(),
            'foreign_keys' => array(),
            'indexes' => array(),
            'data_integrity' => array()
        );
        
        // Check table existence and structure
        $required_tables = array(
            'mobooking_services',
            'mobooking_service_options', 
            'mobooking_bookings',
            'mobooking_booking_services',
            'mobooking_booking_service_options',
            'mobooking_areas',
            'mobooking_discounts',
            'mobooking_settings',
            'mobooking_booking_form_settings'
        );
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            $health_report['tables_status'][$table] = array(
                'exists' => $exists,
                'row_count' => $exists ? intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name")) : 0
            );
        }
        
        // Check for orphaned records in junction tables
        if ($health_report['tables_status']['mobooking_booking_services']['exists']) {
            $orphaned_booking_services = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_booking_services bs
                LEFT JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
                WHERE b.id IS NULL
            ");
            
            $health_report['data_integrity']['orphaned_booking_services'] = intval($orphaned_booking_services);
        }
        
        if ($health_report['tables_status']['mobooking_booking_service_options']['exists']) {
            $orphaned_booking_options = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_booking_service_options bso
                LEFT JOIN {$wpdb->prefix}mobooking_bookings b ON bso.booking_id = b.id
                WHERE b.id IS NULL
            ");
            
            $health_report['data_integrity']['orphaned_booking_options'] = intval($orphaned_booking_options);
        }
        
        // Check for old JSON columns (should be removed after migration)
        if ($health_report['tables_status']['mobooking_bookings']['exists']) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}mobooking_bookings");
            $has_json_columns = false;
            
            foreach ($columns as $column) {
                if (in_array($column->Field, array('services', 'service_options'))) {
                    $has_json_columns = true;
                    break;
                }
            }
            
            $health_report['data_integrity']['has_old_json_columns'] = $has_json_columns;
        }
        
        wp_send_json_success(array(
            'health_report' => $health_report,
            'overall_status' => mobooking_calculate_overall_health_status($health_report)
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Database Health Check Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error performing database health check.', 'mobooking'));
    }
}

add_action('wp_ajax_mobooking_database_health_check', 'mobooking_ajax_database_health_check');

/**
 * NEW: Calculate overall database health status
 */
function mobooking_calculate_overall_health_status($health_report) {
    $issues = array();
    $warnings = array();
    
    // Check if all required tables exist
    foreach ($health_report['tables_status'] as $table => $status) {
        if (!$status['exists']) {
            $issues[] = "Missing table: $table";
        }
    }
    
    // Check for data integrity issues
    if (isset($health_report['data_integrity']['orphaned_booking_services']) && 
        $health_report['data_integrity']['orphaned_booking_services'] > 0) {
        $warnings[] = "Found {$health_report['data_integrity']['orphaned_booking_services']} orphaned booking services";
    }
    
    if (isset($health_report['data_integrity']['orphaned_booking_options']) && 
        $health_report['data_integrity']['orphaned_booking_options'] > 0) {
        $warnings[] = "Found {$health_report['data_integrity']['orphaned_booking_options']} orphaned booking options";
    }
    
    if (isset($health_report['data_integrity']['has_old_json_columns']) && 
        $health_report['data_integrity']['has_old_json_columns']) {
        $warnings[] = "Old JSON columns still exist in bookings table";
    }
    
    if (!empty($issues)) {
        return array(
            'status' => 'critical',
            'message' => 'Database has critical issues that need immediate attention',
            'issues' => $issues,
            'warnings' => $warnings
        );
    } elseif (!empty($warnings)) {
        return array(
            'status' => 'warning',
            'message' => 'Database is functional but has some issues',
            'issues' => array(),
            'warnings' => $warnings
        );
    } else {
        return array(
            'status' => 'healthy',
            'message' => 'Database is healthy and properly normalized',
            'issues' => array(),
            'warnings' => array()
        );
    }
}

/**
 * NEW: Clean up orphaned records AJAX handler
 */
function mobooking_ajax_cleanup_orphaned_records() {
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $cleaned_records = 0;
        
        // Clean up orphaned booking services
        $deleted_services = $wpdb->query("
            DELETE bs FROM {$wpdb->prefix}mobooking_booking_services bs
            LEFT JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
            WHERE b.id IS NULL
        ");
        
        if ($deleted_services > 0) {
            $cleaned_records += $deleted_services;
        }
        
        // Clean up orphaned booking service options
        $deleted_options = $wpdb->query("
            DELETE bso FROM {$wpdb->prefix}mobooking_booking_service_options bso
            LEFT JOIN {$wpdb->prefix}mobooking_bookings b ON bso.booking_id = b.id
            WHERE b.id IS NULL
        ");
        
        if ($deleted_options > 0) {
            $cleaned_records += $deleted_options;
        }
        
        // Clean up orphaned service options (services that don't exist)
        $deleted_service_options = $wpdb->query("
            DELETE so FROM {$wpdb->prefix}mobooking_service_options so
            LEFT JOIN {$wpdb->prefix}mobooking_services s ON so.service_id = s.id
            WHERE s.id IS NULL
        ");
        
        if ($deleted_service_options > 0) {
            $cleaned_records += $deleted_service_options;
        }
        
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully cleaned up %d orphaned records.', 'mobooking'), $cleaned_records),
            'cleaned_count' => $cleaned_records
        ));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Cleanup Orphaned Records Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error cleaning up orphaned records.', 'mobooking'));
    }
}

add_action('wp_ajax_mobooking_cleanup_orphaned_records', 'mobooking_ajax_cleanup_orphaned_records');

/**
 * NEW: Migration status check AJAX handler
 */
function mobooking_ajax_check_migration_status() {
    try {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        global $wpdb;
        
        $migration_status = array(
            'migration_completed' => get_option('mobooking_migration_completed', false),
            'junction_tables_exist' => false,
            'json_columns_exist' => false,
            'data_migrated' => false
        );
        
        // Check if junction tables exist
        $booking_services_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mobooking_booking_services'");
        $booking_options_table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mobooking_booking_service_options'");
        
        $migration_status['junction_tables_exist'] = ($booking_services_table && $booking_options_table);
        
        // Check if JSON columns still exist
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}mobooking_bookings'")) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}mobooking_bookings");
            foreach ($columns as $column) {
                if (in_array($column->Field, array('services', 'service_options'))) {
                    $migration_status['json_columns_exist'] = true;
                    break;
                }
            }
        }
        
        // Check if data has been migrated
        if ($migration_status['junction_tables_exist']) {
            $migrated_services = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_booking_services");
            $migrated_options = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_booking_service_options");
            
            $migration_status['data_migrated'] = ($migrated_services > 0 || $migrated_options > 0);
        }
        
        // Determine overall migration status
        if ($migration_status['migration_completed'] && 
            $migration_status['junction_tables_exist'] && 
            !$migration_status['json_columns_exist']) {
            $overall_status = 'completed';
        } elseif ($migration_status['junction_tables_exist'] && $migration_status['data_migrated']) {
            $overall_status = 'partially_completed';
        } elseif ($migration_status['junction_tables_exist']) {
            $overall_status = 'ready_for_migration';
        } else {
            $overall_status = 'not_started';
        }
        
        wp_send_json_success(array(
            'migration_status' => $migration_status,
            'overall_status' => $overall_status,
            'recommendations' => mobooking_get_migration_recommendations($overall_status, $migration_status)
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Check Migration Status Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error checking migration status.', 'mobooking'));
    }
}

add_action('wp_ajax_mobooking_check_migration_status', 'mobooking_ajax_check_migration_status');

/**
 * NEW: Get migration recommendations based on status
 */
function mobooking_get_migration_recommendations($overall_status, $migration_status) {
    $recommendations = array();
    
    switch ($overall_status) {
        case 'not_started':
            $recommendations[] = 'Run the database migration to normalize your data structure';
            $recommendations[] = 'Backup your database before starting migration';
            break;
            
        case 'ready_for_migration':
            $recommendations[] = 'Junction tables are ready, run the data migration process';
            break;
            
        case 'partially_completed':
            if ($migration_status['json_columns_exist']) {
                $recommendations[] = 'Migration appears successful, consider running cleanup to remove old JSON columns';
                $recommendations[] = 'Test your booking system thoroughly before cleanup';
            }
            break;
            
        case 'completed':
            $recommendations[] = 'Migration completed successfully';
            $recommendations[] = 'Your database is now properly normalized';
            $recommendations[] = 'Consider running periodic health checks';
            break;
    }
    
    return $recommendations;
}

/**
 * NEW: Performance optimization AJAX handler
 */
function mobooking_ajax_optimize_database() {
    global $wpdb;
    
    try {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $optimization_results = array();
        
        // Optimize tables
        $tables_to_optimize = array(
            'mobooking_services',
            'mobooking_service_options',
            'mobooking_bookings',
            'mobooking_booking_services',
            'mobooking_booking_service_options',
            'mobooking_areas',
            'mobooking_discounts',
            'mobooking_settings'
        );
        
        foreach ($tables_to_optimize as $table) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $result = $wpdb->query("OPTIMIZE TABLE $table_name");
                $optimization_results[$table] = $result !== false;
            }
        }
        
        // Add missing indexes if they don't exist
        $indexes_to_add = array(
            'mobooking_bookings' => array(
                'idx_user_status_date' => 'ALTER TABLE %s ADD INDEX idx_user_status_date (user_id, status, service_date)',
                'idx_customer_email' => 'ALTER TABLE %s ADD INDEX idx_customer_email (customer_email)',
                'idx_zip_code' => 'ALTER TABLE %s ADD INDEX idx_zip_code (zip_code)'
            ),
            'mobooking_booking_services' => array(
                'idx_booking_service_unique' => 'ALTER TABLE %s ADD UNIQUE INDEX idx_booking_service_unique (booking_id, service_id)'
            ),
            'mobooking_booking_service_options' => array(
                'idx_booking_option_unique' => 'ALTER TABLE %s ADD UNIQUE INDEX idx_booking_option_unique (booking_id, service_option_id)'
            )
        );
        
        $indexes_added = 0;
        foreach ($indexes_to_add as $table => $indexes) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                foreach ($indexes as $index_name => $sql) {
                    // Check if index already exists
                    $existing_index = $wpdb->get_row("SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'");
                    if (!$existing_index) {
                        $result = $wpdb->query(sprintf($sql, $table_name));
                        if ($result !== false) {
                            $indexes_added++;
                        }
                    }
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Database optimization completed successfully.', 'mobooking'),
            'optimization_results' => $optimization_results,
            'indexes_added' => $indexes_added
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Optimize Database Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error optimizing database.', 'mobooking'));
    }
}

add_action('wp_ajax_mobooking_optimize_database', 'mobooking_ajax_optimize_database');
add_action('init', 'mobooking_register_ajax_handlers', 5);

/**
 * UPDATED: ZIP Coverage Check Handler with transaction support
 */
function mobooking_ajax_check_zip_coverage() {
    try {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking ZIP Coverage AJAX Handler Called');
            error_log('POST Data: ' . print_r($_POST, true));
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(array(
                'message' => __('Security verification failed.', 'mobooking'),
                'auto_advance' => false
            ));
            return;
        }
        
        if (!isset($_POST['zip_code']) || !isset($_POST['user_id'])) {
            wp_send_json_error(array(
                'message' => __('Missing required information.', 'mobooking'),
                'auto_advance' => false
            ));
            return;
        }
        
        $zip_code = sanitize_text_field(trim($_POST['zip_code']));
        $user_id = absint($_POST['user_id']);
        
        if (!preg_match('/^\d{5}(-\d{4})?$/', $zip_code)) {
            wp_send_json_error(array(
                'message' => __('Please enter a valid ZIP code format.', 'mobooking'),
                'auto_advance' => false
            ));
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user || (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles))) {
            wp_send_json_error(array(
                'message' => __('Invalid business account.', 'mobooking'),
                'auto_advance' => false
            ));
            return;
        }
        
        if (!class_exists('\MoBooking\Geography\Manager')) {
            wp_send_json_error(array(
                'message' => __('Service temporarily unavailable.', 'mobooking'),
                'auto_advance' => false
            ));
            return;
        }
        
        $geography_manager = new \MoBooking\Geography\Manager();
        $is_covered = $geography_manager->is_zip_covered($zip_code, $user_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: ZIP coverage result for {$zip_code} (User {$user_id}): " . ($is_covered ? 'COVERED' : 'NOT COVERED'));
        }
        
        if ($is_covered) {
            wp_send_json_success(array(
                'message' => __('Great! We provide services in your area.', 'mobooking'),
                'zip_code' => $zip_code,
                'covered' => true,
                'auto_advance' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sorry, we don\'t currently service this area. Please contact us for more information.', 'mobooking'),
                'zip_code' => $zip_code,
                'covered' => false,
                'auto_advance' => false
            ));
        }
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking ZIP Coverage Exception: ' . $e->getMessage());
        }
        
        wp_send_json_error(array(
            'message' => __('An error occurred while checking service availability.', 'mobooking'),
            'auto_advance' => false
        ));
    }
}

/**
 * Get Service Options Handler - UNCHANGED (already normalized)
 */
function mobooking_ajax_get_service_options() {
    try {
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce') || 
                          wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce');
        }

        if (!$nonce_valid) {
            wp_send_json_error(array('message' => 'Security verification failed'));
            return;
        }

        $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Service ID required'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $options = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE service_id = %d ORDER BY display_order ASC",
            $service_id
        ));

        $formatted_options = array();
        foreach ($options as $option) {
            $formatted_options[] = array(
                'id' => intval($option->id),
                'service_id' => intval($option->service_id),
                'name' => $option->name,
                'description' => $option->description,
                'type' => $option->type,
                'is_required' => intval($option->is_required),
                'price_impact' => floatval($option->price_impact),
                'price_type' => $option->price_type,
                'options' => $option->options,
                'default_value' => $option->default_value,
                'placeholder' => $option->placeholder,
                'min_value' => $option->min_value,
                'max_value' => $option->max_value,
                'step' => $option->step,
                'unit' => $option->unit,
                'rows' => intval($option->rows)
            );
        }

        wp_send_json_success(array('options' => $formatted_options));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Get Service Options Exception: ' . $e->getMessage());
        }
        wp_send_json_error(array('message' => __('Error loading service options.', 'mobooking')));
    }
}

/**
 * UPDATED: Save Booking Handler with normalized database structure
 */
function mobooking_ajax_save_booking() {
    global $wpdb;
    
    try {
        // Start transaction immediately for data integrity
        $wpdb->query('START TRANSACTION');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Validate required fields
        $required_fields = array('customer_name', 'customer_email', 'customer_address', 'zip_code', 'service_date', 'selected_services', 'total_price', 'user_id');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('Field %s is required.', 'mobooking'), $field));
                return;
            }
        }
        
        // Validate email
        if (!is_email($_POST['customer_email'])) {
            wp_send_json_error(__('Invalid email address.', 'mobooking'));
            return;
        }
        
        // Process selected services
        $selected_services = array();
        if (isset($_POST['selected_services']) && is_array($_POST['selected_services'])) {
            $selected_services = array_map('absint', $_POST['selected_services']);
            $selected_services = array_filter($selected_services); // Remove zeros
        }
        
        if (empty($selected_services)) {
            wp_send_json_error(__('Please select at least one service.', 'mobooking'));
            return;
        }
        
        // Validate user and services exist
        $user_id = absint($_POST['user_id']);
        $user = get_userdata($user_id);
        if (!$user || (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles))) {
            wp_send_json_error(__('Invalid business account.', 'mobooking'));
            return;
        }
        
        // Validate all selected services exist and belong to user
        $service_placeholders = implode(',', array_fill(0, count($selected_services), '%d'));
        $valid_services = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, price FROM {$wpdb->prefix}mobooking_services 
             WHERE id IN ($service_placeholders) AND user_id = %d AND status = 'active'",
            array_merge($selected_services, array($user_id))
        ));
        
        if (count($valid_services) !== count($selected_services)) {
            wp_send_json_error(__('One or more selected services are invalid.', 'mobooking'));
            return;
        }
        
        // Calculate pricing with validation
        $pricing = mobooking_calculate_booking_pricing($selected_services, $_POST['service_options_data'] ?? '', $_POST['discount_amount'] ?? 0);
        
        // Prepare main booking data (NO MORE JSON!)
        $booking_data = array(
            'user_id' => $user_id,
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
            'customer_address' => sanitize_textarea_field($_POST['customer_address']),
            'zip_code' => sanitize_text_field($_POST['zip_code']),
            'service_date' => sanitize_text_field($_POST['service_date']),
            'subtotal' => $pricing['subtotal'],
            'total_price' => $pricing['total'],
            'discount_code' => isset($_POST['discount_code']) ? sanitize_text_field($_POST['discount_code']) : '',
            'discount_amount' => $pricing['discount_amount'],
            'status' => 'pending',
            'notes' => isset($_POST['booking_notes']) ? sanitize_textarea_field($_POST['booking_notes']) : ''
        );
        
        // Insert main booking record
        $result = $wpdb->insert(
            $wpdb->prefix . 'mobooking_bookings',
            $booking_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%f', '%s', '%s')
        );
        
        if ($result === false) {
            throw new Exception('Failed to create booking record');
        }
        
        $booking_id = $wpdb->insert_id;
        
        // Insert booking services (normalized!)
        foreach ($valid_services as $service) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'mobooking_booking_services',
                array(
                    'booking_id' => $booking_id,
                    'service_id' => $service->id,
                    'quantity' => 1, // Default quantity
                    'unit_price' => $service->price,
                    'total_price' => $service->price
                ),
                array('%d', '%d', '%d', '%f', '%f')
            );
            
            if ($result === false) {
                throw new Exception("Failed to save service {$service->id}");
            }
        }
        
        // Insert booking service options (normalized!)
        if (!empty($_POST['service_options_data'])) {
            mobooking_save_booking_service_options($booking_id, $_POST['service_options_data']);
        }
        
        // Update discount usage if applicable
        if (!empty($_POST['discount_code'])) {
            mobooking_update_discount_usage($_POST['discount_code'], $user_id);
        }
        
        // Commit transaction
        $wpdb->query('COMMIT');
        
        // Send confirmation email
        mobooking_send_booking_confirmation($booking_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Successfully created booking {$booking_id} with normalized structure");
        }
        
        wp_send_json_success(array(
            'id' => $booking_id,
            'message' => __('Booking confirmed successfully!', 'mobooking'),
            'auto_advance' => true
        ));
        
    } catch (Exception $e) {
        // Rollback transaction on any error
        $wpdb->query('ROLLBACK');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Save Booking Exception: ' . $e->getMessage());
        }
        
        wp_send_json_error(__('An error occurred while saving your booking. Please try again.', 'mobooking'));
    }
}

/**
 * NEW: Calculate booking pricing from normalized data
 */
function mobooking_calculate_booking_pricing($selected_services, $options_data = '', $discount_amount = 0) {
    global $wpdb;
    
    $services_total = 0;
    $options_total = 0;
    
    // Calculate services total
    if (!empty($selected_services)) {
        $service_ids = array_map('absint', $selected_services);
        $service_ids = array_filter($service_ids);
        
        if (!empty($service_ids)) {
            $placeholders = implode(',', array_fill(0, count($service_ids), '%d'));
            $services_total = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(price) FROM {$wpdb->prefix}mobooking_services WHERE id IN ($placeholders)",
                ...$service_ids
            ));
            $services_total = floatval($services_total);
        }
    }
    
    // Calculate options total
    if (!empty($options_data)) {
        $options_data = is_string($options_data) 
            ? json_decode($options_data, true) 
            : $options_data;
        
        if (is_array($options_data)) {
            foreach ($options_data as $option_id => $option_value) {
                $option_id = absint($option_id);
                
                if ($option_id <= 0) {
                    continue;
                }
                
                $option = $wpdb->get_row($wpdb->prepare(
                    "SELECT price_impact, price_type FROM {$wpdb->prefix}mobooking_service_options WHERE id = %d",
                    $option_id
                ));
                
                if ($option) {
                    $options_total += mobooking_calculate_option_price_impact($option, $option_value);
                }
            }
        }
    }
    
    $subtotal = $services_total + $options_total;
    $discount_amount = floatval($discount_amount);
    $total = max(0, $subtotal - $discount_amount);
    
    return array(
        'services_total' => $services_total,
        'options_total' => $options_total,
        'subtotal' => $subtotal,
        'discount_amount' => $discount_amount,
        'total' => $total
    );
}

/**
 * NEW: Save booking service options to junction table
 */
function mobooking_save_booking_service_options($booking_id, $options_data) {
    global $wpdb;
    
    // Parse options data
    if (is_string($options_data)) {
        $options_data = json_decode($options_data, true);
    }
    
    if (!is_array($options_data)) {
        return; // No options to save
    }
    
    foreach ($options_data as $option_id => $option_value) {
        $option_id = absint($option_id);
        
        if ($option_id <= 0) {
            continue;
        }
        
        // Get option details
        $option = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, price_impact, price_type FROM {$wpdb->prefix}mobooking_service_options WHERE id = %d",
            $option_id
        ));
        
        if (!$option) {
            continue; // Skip invalid options
        }
        
        // Calculate price impact
        $price_impact = mobooking_calculate_option_price_impact($option, $option_value);
        
        // Insert into junction table
        $result = $wpdb->insert(
            $wpdb->prefix . 'mobooking_booking_service_options',
            array(
                'booking_id' => $booking_id,
                'service_option_id' => $option_id,
                'option_value' => is_array($option_value) ? json_encode($option_value) : (string)$option_value,
                'price_impact' => $price_impact
            ),
            array('%d', '%d', '%s', '%f')
        );
        
        if ($result === false) {
            throw new Exception("Failed to save option {$option_id}");
        }
    }
}

/**
 * NEW: Calculate option price impact
 */
function mobooking_calculate_option_price_impact($option, $option_value) {
    if ($option->price_type === 'none' || $option->price_impact == 0) {
        return 0;
    }
    
    switch ($option->price_type) {
        case 'fixed':
            return floatval($option->price_impact);
            
        case 'percentage':
            return floatval($option->price_impact);
            
        case 'multiply':
            if (is_numeric($option_value)) {
                return floatval($option->price_impact) * floatval($option_value);
            }
            return 0;
            
        case 'choice':
            if (is_string($option_value) && strpos($option_value, ':') !== false) {
                $parts = explode(':', $option_value);
                if (isset($parts[1]) && is_numeric($parts[1])) {
                    return floatval($parts[1]);
                }
            }
            return 0;
            
        default:
            return 0;
    }
}

/**
 * NEW: Update discount usage
 */
function mobooking_update_discount_usage($discount_code, $user_id) {
    global $wpdb;
    
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}mobooking_discounts 
         SET usage_count = usage_count + 1 
         WHERE code = %s AND user_id = %d",
        $discount_code, $user_id
    ));
    
    if ($result === false) {
        throw new Exception('Failed to update discount usage');
    }
}

/**
 * NEW: Send booking confirmation email
 */
function mobooking_send_booking_confirmation($booking_id) {
    try {
        $booking = mobooking_get_booking_with_details($booking_id);
        if (!$booking) {
            throw new Exception('Booking not found');
        }
        
        // Get business owner settings
        if (class_exists('\MoBooking\Database\SettingsManager')) {
            $settings_manager = new \MoBooking\Database\SettingsManager();
            $settings = $settings_manager->get_settings($booking->user_id);
        } else {
            $settings = (object) array(
                'company_name' => get_userdata($booking->user_id)->display_name . "'s Business",
                'email_header' => '<h1>{{company_name}}</h1>',
                'email_footer' => '<p>&copy; {{current_year}} {{company_name}}</p>',
                'booking_confirmation_message' => 'Thank you for your booking!'
            );
        }
        
        // Prepare email content
        $subject = sprintf(__('Booking Confirmation - %s', 'mobooking'), $settings->company_name);
        
        $message = str_replace(
            array('{{company_name}}', '{{current_year}}'),
            array($settings->company_name, date('Y')),
            $settings->email_header
        );
        
        $message .= '<h2>' . __('Booking Confirmation', 'mobooking') . '</h2>';
        $message .= '<p>' . sprintf(__('Dear %s,', 'mobooking'), $booking->customer_name) . '</p>';
        $message .= '<p>' . $settings->booking_confirmation_message . '</p>';
        
        $message .= '<h3>' . __('Booking Details', 'mobooking') . '</h3>';
        $message .= '<p><strong>' . __('Booking ID:', 'mobooking') . '</strong> #' . $booking->id . '</p>';
        $message .= '<p><strong>' . __('Service Date:', 'mobooking') . '</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)) . '</p>';
        $message .= '<p><strong>' . __('Address:', 'mobooking') . '</strong> ' . $booking->customer_address . '</p>';
        
        // Add services list
        if (!empty($booking->services)) {
            $message .= '<h4>' . __('Services:', 'mobooking') . '</h4>';
            $message .= '<ul>';
            foreach ($booking->services as $service) {
                $message .= '<li>' . esc_html($service->service_name) . ' - ' . wc_price($service->unit_price) . '</li>';
            }
            $message .= '</ul>';
        }
        
        $message .= '<p><strong>' . __('Total Amount:', 'mobooking') . '</strong> ' . wc_price($booking->total_price) . '</p>';
        
        if (!empty($booking->notes)) {
            $message .= '<p><strong>' . __('Special Instructions:', 'mobooking') . '</strong> ' . $booking->notes . '</p>';
        }
        
        $message .= str_replace(
            array('{{company_name}}', '{{current_year}}'),
            array($settings->company_name, date('Y')),
            $settings->email_footer
        );
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($booking->customer_email, $subject, $message, $headers);
        
        // Also notify business owner
        $business_user = get_userdata($booking->user_id);
        if ($business_user) {
            $business_subject = sprintf(__('New Booking Received - #%d', 'mobooking'), $booking->id);
            $business_message = sprintf(__('You have received a new booking from %s for %s.', 'mobooking'), 
                $booking->customer_name, 
                date_i18n(get_option('date_format'), strtotime($booking->service_date))
            );
            wp_mail($business_user->user_email, $business_subject, $business_message);
        }
        
        return true;
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Email Confirmation Error: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * NEW: Get booking with full details using normalized structure
 */
function mobooking_get_booking_with_details($booking_id, $user_id = null) {
    global $wpdb;
    
    // Get main booking data
    if ($user_id) {
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mobooking_bookings WHERE id = %d AND user_id = %d",
            $booking_id, $user_id
        ));
    } else {
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mobooking_bookings WHERE id = %d",
            $booking_id
        ));
    }
    
    if (!$booking) {
        return null;
    }
    
    // Get booking services
    $booking->services = $wpdb->get_results($wpdb->prepare(
        "SELECT bs.*, s.name as service_name, s.description as service_description
         FROM {$wpdb->prefix}mobooking_booking_services bs
         JOIN {$wpdb->prefix}mobooking_services s ON bs.service_id = s.id
         WHERE bs.booking_id = %d",
        $booking_id
    ));
    
    // Get booking service options
    $booking->service_options = $wpdb->get_results($wpdb->prepare(
        "SELECT bso.*, so.name as option_name, so.type as option_type
         FROM {$wpdb->prefix}mobooking_booking_service_options bso
         JOIN {$wpdb->prefix}mobooking_service_options so ON bso.service_option_id = so.id
         WHERE bso.booking_id = %d",
        $booking_id
    ));
    
    return $booking;
}

/**
 * UPDATED: Get User Bookings with normalized data
 */
function mobooking_ajax_get_user_bookings() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        global $wpdb;
        
        // Build query
        $where_conditions = array("b.user_id = %d");
        $params = array($user_id);
        
        if (!empty($status)) {
            $where_conditions[] = "b.status = %s";
            $params[] = $status;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $offset = ($page - 1) * $per_page;
        
        // Get bookings with service count
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, 
                    COUNT(bs.id) as service_count,
                    GROUP_CONCAT(s.name SEPARATOR ', ') as service_names
             FROM {$wpdb->prefix}mobooking_bookings b
             LEFT JOIN {$wpdb->prefix}mobooking_booking_services bs ON b.id = bs.booking_id
             LEFT JOIN {$wpdb->prefix}mobooking_services s ON bs.service_id = s.id
             WHERE $where_clause
             GROUP BY b.id
             ORDER BY b.created_at DESC
             LIMIT %d OFFSET %d",
            array_merge($params, array($per_page, $offset))
        ));
        
        // Get total count
        $total_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings b WHERE $where_clause",
            $params
        ));
        
        wp_send_json_success(array(
            'bookings' => $bookings,
            'total' => intval($total_bookings),
            'pages' => ceil($total_bookings / $per_page),
            'current_page' => $page
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Get User Bookings Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error loading bookings.', 'mobooking'));
    }
}

/**
 * NEW: Get booking details with full normalized data
 */
function mobooking_ajax_get_booking_details() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        if (!$booking_id) {
            wp_send_json_error(__('Booking ID required.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        $booking = mobooking_get_booking_with_details($booking_id, $user_id);
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found.', 'mobooking'));
            return;
        }
        
        wp_send_json_success(array(
            'booking' => $booking
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Get Booking Details Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error loading booking details.', 'mobooking'));
    }
}

/**
 * UPDATED: Update booking status with transaction support
 */
function mobooking_ajax_update_booking_status() {
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$booking_id || !$status) {
            wp_send_json_error(__('Missing required information.', 'mobooking'));
            return;
        }
        
        $valid_statuses = array('pending', 'confirmed', 'completed', 'cancelled', 'rescheduled');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(__('Invalid status.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Verify booking ownership
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status as current_status FROM {$wpdb->prefix}mobooking_bookings 
             WHERE id = %d AND user_id = %d",
            $booking_id, $user_id
        ));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found.', 'mobooking'));
            return;
        }
        
        // Update status
        $result = $wpdb->update(
            $wpdb->prefix . 'mobooking_bookings',
            array('status' => $status),
            array('id' => $booking_id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );
        
        if ($result === false) {
            throw new Exception('Failed to update booking status');
        }
        
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => __('Booking status updated successfully.', 'mobooking'),
            'new_status' => $status,
            'old_status' => $booking->current_status
        ));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Update Booking Status Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Failed to update booking status.', 'mobooking'));
    }
}

/**
 * NEW: Delete booking with cascade delete using transactions
 */
function mobooking_ajax_delete_booking() {
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        if (!$booking_id) {
            wp_send_json_error(__('Booking ID required.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Verify booking ownership
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mobooking_bookings 
             WHERE id = %d AND user_id = %d",
            $booking_id, $user_id
        ));
        
        if (!$booking) {
            wp_send_json_error(__('Booking not found.', 'mobooking'));
            return;
        }
        
        // Delete related records first (cascade delete)
        $wpdb->delete(
            $wpdb->prefix . 'mobooking_booking_service_options',
            array('booking_id' => $booking_id),
            array('%d')
        );
        
        $wpdb->delete(
            $wpdb->prefix . 'mobooking_booking_services',
            array('booking_id' => $booking_id),
            array('%d')
        );
        
        // Delete main booking record
        $result = $wpdb->delete(
            $wpdb->prefix . 'mobooking_bookings',
            array('id' => $booking_id, 'user_id' => $user_id),
            array('%d', '%d')
        );
        
        if ($result === false) {
            throw new Exception('Failed to delete booking');
        }
        
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => __('Booking deleted successfully.', 'mobooking')
        ));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Delete Booking Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Failed to delete booking.', 'mobooking'));
    }
}

/**
 * Settings Save Handler - UPDATED with transaction support
 */
function mobooking_ajax_save_settings() {
    global $wpdb;
    
    try {
        $wpdb->query('START TRANSACTION');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-settings-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Use the settings manager if available
        if (class_exists('\MoBooking\Database\SettingsManager')) {
            $settings_manager = new \MoBooking\Database\SettingsManager();
            // This call likely handles both main settings and potentially some user meta.
            // We will add explicit user meta handling after this for fields not covered by SettingsManager.
            $settings_manager->update_settings($user_id, $_POST);
        }

        // Define user meta fields to be saved
        $user_meta_fields = array(
            'business_email',
            'business_address',
            'website',
            'business_description',
            'notify_new_booking',
            'notify_booking_changes',
            'notify_reminders',
            'require_terms_acceptance',
            'booking_lead_time',
            'max_bookings_per_day',
            'auto_confirm_bookings',
            'allow_same_day_booking',
            'send_booking_reminders'
            // 'business_hours' is handled separately
        );

        foreach ($user_meta_fields as $field_key) {
            $value_to_save = null;
            if (in_array($field_key, ['notify_new_booking', 'notify_booking_changes', 'notify_reminders', 'require_terms_acceptance', 'auto_confirm_bookings', 'allow_same_day_booking', 'send_booking_reminders'])) {
                // Checkbox fields
                $value_to_save = isset($_POST[$field_key]) ? '1' : '0';
            } elseif (isset($_POST[$field_key])) {
                // Other field types
                $raw_value = $_POST[$field_key];
                switch ($field_key) {
                    case 'business_email':
                        $value_to_save = sanitize_email($raw_value);
                        break;
                    case 'business_address':
                        $value_to_save = sanitize_textarea_field($raw_value);
                        break;
                    case 'website':
                        $value_to_save = esc_url_raw($raw_value);
                        break;
                    case 'business_description':
                        $value_to_save = wp_kses_post($raw_value);
                        break;
                    case 'booking_lead_time':
                    case 'max_bookings_per_day':
                        $value_to_save = absint($raw_value);
                        break;
                    default:
                        $value_to_save = sanitize_text_field($raw_value);
                }
            }

            if ($value_to_save !== null) {
                update_user_meta($user_id, $field_key, $value_to_save);
            } else {
                // If not set and not a checkbox, consider deleting or setting to empty,
                // depending on desired behavior. For now, we only update if set.
                // For checkboxes, '0' is saved if not set.
            }
        }

        // Special handling for business_hours
        if (isset($_POST['business_hours']) && is_array($_POST['business_hours'])) {
            $sanitized_business_hours = array();
            foreach ($_POST['business_hours'] as $day => $hours) {
                $sanitized_day = sanitize_text_field($day);
                $sanitized_business_hours[$sanitized_day] = array(
                    'open'  => isset($hours['open']) ? '1' : '0',
                    'start' => isset($hours['start']) ? sanitize_text_field(preg_replace('/[^0-9:]/', '', $hours['start'])) : '',
                    'end'   => isset($hours['end']) ? sanitize_text_field(preg_replace('/[^0-9:]/', '', $hours['end'])) : '',
                );
            }
            update_user_meta($user_id, 'business_hours', $sanitized_business_hours);
        } else {
            // If business_hours is not set in POST, decide whether to delete or save empty.
            // For this implementation, let's save an empty array to signify 'cleared'.
            update_user_meta($user_id, 'business_hours', array());
        }
        
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'mobooking')
        ));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Settings Save Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while saving settings.', 'mobooking'));
    }
}

/**
 * UPDATED: Validate Discount Handler with transaction support
 */
function mobooking_ajax_validate_discount() {
    global $wpdb;
    
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
            return;
        }
        
        // Check required fields
        if (!isset($_POST['code']) || !isset($_POST['user_id']) || !isset($_POST['total'])) {
            wp_send_json_error(array('message' => __('Missing required information.', 'mobooking')));
            return;
        }
        
        $code = sanitize_text_field($_POST['code']);
        $user_id = absint($_POST['user_id']);
        $total = floatval($_POST['total']);
        
        // Validate discount code with proper database query
        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mobooking_discounts 
             WHERE code = %s AND user_id = %d AND active = 1 
             AND (expiry_date IS NULL OR expiry_date >= CURDATE())",
            $code, $user_id
        ));
        
        if (!$discount) {
            wp_send_json_error(array(
                'message' => __('Invalid or expired discount code.', 'mobooking')
            ));
            return;
        }
        
        // Check usage limit
        if ($discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit) {
            wp_send_json_error(array(
                'message' => __('This discount code has reached its usage limit.', 'mobooking')
            ));
            return;
        }
        
        // Calculate discount amount
        $discount_amount = 0;
        if ($discount->type === 'percentage') {
            $discount_amount = ($total * $discount->amount) / 100;
        } else {
            $discount_amount = min($discount->amount, $total);
        }
        
        wp_send_json_success(array(
            'discount_amount' => $discount_amount,
            'discount_type' => $discount->type,
            'discount_value' => $discount->amount,
            'message' => sprintf(__('Discount applied! You save %s', 'mobooking'), 
                function_exists('wc_price') ? wc_price($discount_amount) : number_format($discount_amount, 2))
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Discount Validation Exception: ' . $e->getMessage());
        }
        
        wp_send_json_error(array(
            'message' => __('Error processing discount code.', 'mobooking')
        ));
    }
}

/**
 * Upload Image Handler - UPDATED with transaction support for metadata
 */
function mobooking_ajax_upload_image() {
    global $wpdb;
    
    try {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('You do not have permission to upload files.', 'mobooking'));
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No file uploaded or upload error occurred.', 'mobooking'));
            return;
        }
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type. Please upload an image.', 'mobooking'));
            return;
        }
        
        // Validate file size (max 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(__('File too large. Maximum size is 5MB.', 'mobooking'));
            return;
        }
        
        // Handle the upload
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('image', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error($attachment_id->get_error_message());
            return;
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        $image_data = wp_get_attachment_image_src($attachment_id, 'medium');
        
        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'url' => $image_url,
            'thumb_url' => $image_data ? $image_data[0] : $image_url,
            'message' => __('Image uploaded successfully.', 'mobooking')
        ));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Upload Image Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error uploading image.', 'mobooking'));
    }
}

/**
 * UPDATED: Get Dashboard Stats with normalized data
 */
function mobooking_ajax_get_dashboard_stats() {
    try {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-service-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Calculate stats using normalized database structure
        $stats = array(
            'total_bookings' => intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d",
                $user_id
            ))),
            'pending_bookings' => intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d AND status = 'pending'",
                $user_id
            ))),
            'confirmed_bookings' => intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d AND status = 'confirmed'",
                $user_id
            ))),
            'completed_bookings' => intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d AND status = 'completed'",
                $user_id
            ))),
            'cancelled_bookings' => intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d AND status = 'cancelled'",
                $user_id
            ))),
            'total_revenue' => floatval($wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_price) FROM {$wpdb->prefix}mobooking_bookings 
                 WHERE user_id = %d AND status IN ('confirmed', 'completed')",
                $user_id
            )) ?: 0),
            'today_revenue' => floatval($wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_price) FROM {$wpdb->prefix}mobooking_bookings 
                 WHERE user_id = %d AND status IN ('confirmed', 'completed') 
                 AND DATE(created_at) = CURDATE()",
                $user_id
            )) ?: 0),
            'this_week_revenue' => floatval($wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_price) FROM {$wpdb->prefix}mobooking_bookings 
                 WHERE user_id = %d AND status IN ('confirmed', 'completed') 
                 AND YEARWEEK(created_at) = YEARWEEK(NOW())",
                $user_id
            )) ?: 0),
            'this_month_revenue' => floatval($wpdb->get_var($wpdb->prepare(
                "SELECT SUM(total_price) FROM {$wpdb->prefix}mobooking_bookings 
                 WHERE user_id = %d AND status IN ('confirmed', 'completed') 
                 AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())",
                $user_id
            )) ?: 0),
            'most_popular_service' => null
        );
        
        // Get most popular service using normalized structure
        $most_popular_service = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, COUNT(bs.service_id) as booking_count 
             FROM {$wpdb->prefix}mobooking_services s
             JOIN {$wpdb->prefix}mobooking_booking_services bs ON s.id = bs.service_id
             JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
             WHERE s.user_id = %d 
             GROUP BY s.id 
             ORDER BY booking_count DESC 
             LIMIT 1",
            $user_id
        ));
        
        if ($most_popular_service) {
            $stats['most_popular_service'] = array(
                'id' => $most_popular_service->id,
                'name' => $most_popular_service->name,
                'price' => $most_popular_service->price,
                'duration' => $most_popular_service->duration,
                'booking_count' => $most_popular_service->booking_count
            );
        }
        
        wp_send_json_success(array('stats' => $stats));
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Dashboard Stats Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('Error loading dashboard statistics.', 'mobooking'));
    }
}

/**
 * Generic service save handler - delegate to Services Manager
 */
function mobooking_ajax_save_service() {
    try {
        if (class_exists('\MoBooking\Services\ServicesManager')) {
            $services_manager = new \MoBooking\Services\ServicesManager();
            return $services_manager->ajax_save_service();
        } else {
            wp_send_json_error(__('Services manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving service.', 'mobooking'));
    }
}

/**
 * Generic service delete handler - delegate to Services Manager
 */
function mobooking_ajax_delete_service() {
    try {
        if (class_exists('\MoBooking\Services\ServicesManager')) {
            $services_manager = new \MoBooking\Services\ServicesManager();
            return $services_manager->ajax_delete_service();
        } else {
            wp_send_json_error(__('Services manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting service.', 'mobooking'));
    }
}

/**
 * Generic service option save handler - delegate to ServiceOptionsManager
 */
function mobooking_ajax_save_service_option() {
    try {
        if (class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            return $options_manager->ajax_save_option();
        } else {
            wp_send_json_error(__('Service options manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving service option.', 'mobooking'));
    }
}

/**
 * Generic service option delete handler - delegate to ServiceOptionsManager
 */
function mobooking_ajax_delete_service_option() {
    try {
        if (class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            return $options_manager->ajax_delete_option();
        } else {
            wp_send_json_error(__('Service options manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting service option.', 'mobooking'));
    }
}

/**
 * Update options order handler - delegate to ServiceOptionsManager
 */
function mobooking_ajax_update_options_order() {
    try {
        if (class_exists('\MoBooking\Services\ServiceOptionsManager')) {
            $options_manager = new \MoBooking\Services\ServiceOptionsManager();
            return $options_manager->ajax_update_options_order();
        } else {
            wp_send_json_error(__('Service options manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error updating options order.', 'mobooking'));
    }
}

/**
 * Send test email handler - delegate to Settings Manager
 */
function mobooking_ajax_send_test_email() {
    try {
        if (class_exists('SettingsAjaxManager')) {
            $settings_manager = new SettingsAjaxManager();
            return $settings_manager->ajax_send_test_email();
        } else {
            wp_send_json_error(__('Settings manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error sending test email.', 'mobooking'));
    }
}

/**
 * Export data handler - UPDATED for normalized structure
 */
function mobooking_ajax_export_data() {
    try {
        // Check nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-settings-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
            return;
        }
        
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            return;
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Export data from normalized structure
        $export_data = array(
            'export_info' => array(
                'version' => MOBOOKING_VERSION,
                'exported_at' => current_time('mysql'),
                'user_id' => $user_id,
                'database_structure' => 'normalized'
            ),
            'services' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_services WHERE user_id = %d ORDER BY id",
                $user_id
            )),
            'service_options' => $wpdb->get_results($wpdb->prepare(
                "SELECT so.* FROM {$wpdb->prefix}mobooking_service_options so
                 JOIN {$wpdb->prefix}mobooking_services s ON so.service_id = s.id
                 WHERE s.user_id = %d ORDER BY so.id",
                $user_id
            )),
            'bookings' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_bookings WHERE user_id = %d ORDER BY id",
                $user_id
            )),
            'booking_services' => $wpdb->get_results($wpdb->prepare(
                "SELECT bs.* FROM {$wpdb->prefix}mobooking_booking_services bs
                 JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
                 WHERE b.user_id = %d ORDER BY bs.id",
                $user_id
            )),
            'booking_service_options' => $wpdb->get_results($wpdb->prepare(
                "SELECT bso.* FROM {$wpdb->prefix}mobooking_booking_service_options bso
                 JOIN {$wpdb->prefix}mobooking_bookings b ON bso.booking_id = b.id
                 WHERE b.user_id = %d ORDER BY bso.id",
                $user_id
            )),
            'areas' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_areas WHERE user_id = %d ORDER BY id",
                $user_id
            )),
            'discounts' => $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_discounts WHERE user_id = %d ORDER BY id",
                $user_id
            )),
            'settings' => $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mobooking_settings WHERE user_id = %d",
                $user_id
            ))
        );
        
        wp_send_json_success($export_data);
        
    } catch (Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking Export Data Exception: ' . $e->getMessage());
        }
        wp_send_json_error(__('An error occurred while exporting data.', 'mobooking'));
    }
}

/**
 * Import data handler - placeholder for normalized structure
 */
function mobooking_ajax_import_data() {
    wp_send_json_error(__('Import functionality will be implemented in the next version with proper normalization support.', 'mobooking'));
}

/**
 * Area management handlers - delegate to Geography Manager
 */
function mobooking_ajax_save_area() {
    try {
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            return $geography_manager->ajax_save_area();
        } else {
            wp_send_json_error(__('Geography manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving area.', 'mobooking'));
    }
}

function mobooking_ajax_delete_area() {
    try {
        if (class_exists('\MoBooking\Geography\Manager')) {
            $geography_manager = new \MoBooking\Geography\Manager();
            return $geography_manager->ajax_delete_area();
        } else {
            wp_send_json_error(__('Geography manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting area.', 'mobooking'));
    }
}

/**
 * Discount management handlers - delegate to Discounts Manager
 */
function mobooking_ajax_save_discount() {
    try {
        if (class_exists('\MoBooking\Discounts\Manager')) {
            $discounts_manager = new \MoBooking\Discounts\Manager();
            return $discounts_manager->ajax_save_discount();
        } else {
            wp_send_json_error(__('Discounts manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error saving discount.', 'mobooking'));
    }
}

function mobooking_ajax_delete_discount() {
    try {
        if (class_exists('\MoBooking\Discounts\Manager')) {
            $discounts_manager = new \MoBooking\Discounts\Manager();
            return $discounts_manager->ajax_delete_discount();
        } else {
            wp_send_json_error(__('Discounts manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error deleting discount.', 'mobooking'));
    }
}

/**
 * Authentication handlers - delegate to Auth Manager
 */
function mobooking_ajax_login() {
    try {
        if (class_exists('\MoBooking\Auth\Manager')) {
            $auth_manager = new \MoBooking\Auth\Manager();
            return $auth_manager->handle_login();
        } else {
            wp_send_json_error(__('Authentication manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error processing login.', 'mobooking'));
    }
}

function mobooking_ajax_register() {
    try {
        if (class_exists('\MoBooking\Auth\Manager')) {
            $auth_manager = new \MoBooking\Auth\Manager();
            return $auth_manager->handle_registration();
        } else {
            wp_send_json_error(__('Authentication manager not available.', 'mobooking'));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error processing registration.', 'mobooking'));
    }
}

function mobooking_ajax_logout() {
    try {
        if (class_exists('\MoBooking\Auth\Manager')) {
            $auth_manager = new \MoBooking\Auth\Manager();
            return $auth_manager->handle_logout();
        } else {
            wp_logout();
            wp_send_json_success(array('message' => __('Logged out successfully.', 'mobooking')));
        }
    } catch (Exception $e) {
        wp_send_json_error(__('Error processing logout.', 'mobooking'));
    }
}
