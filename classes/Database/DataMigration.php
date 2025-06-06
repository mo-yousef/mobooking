<?php
/**
 * TEMPORARY MIGRATION RUNNER
 * Add this to the END of your functions.php file
 * 
 * IMPORTANT: Remove this code after migration is complete!
 */

// Only run for administrators and only once
add_action('wp_loaded', function() {
    // Only run for logged-in administrators
    if (!is_admin() || !current_user_can('administrator')) {
        return;
    }
    
    // Check if migration should run (add ?run_mobooking_migration=1 to any admin URL)
    if (!isset($_GET['run_mobooking_migration']) || $_GET['run_mobooking_migration'] !== '1') {
        return;
    }
    
    // Prevent running multiple times
    if (get_option('mobooking_migration_completed')) {
        wp_die('Migration already completed. Remove the migration code from functions.php');
    }
    
    try {
        // Load the migration class
        require_once MOBOOKING_PATH . '/classes/Database/DataMigration.php';
        
        echo '<h1>MoBooking Data Migration</h1>';
        echo '<p>Starting migration process...</p>';
        
        // Run the migration
        $migration = new \MoBooking\Database\DataMigration();
        $result = $migration->run_migration();
        
        if ($result['success']) {
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h2 style="color: #155724;">✅ Migration Successful!</h2>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '<p><strong>Migrated:</strong> ' . intval($result['migrated_count']) . ' bookings</p>';
            echo '</div>';
            
            // Mark migration as completed
            update_option('mobooking_migration_completed', true);
            
            echo '<h3>Next Steps:</h3>';
            echo '<ol>';
            echo '<li><strong>Test your booking system</strong> to ensure everything works</li>';
            echo '<li><strong>Remove this migration code</strong> from functions.php</li>';
            echo '<li>Optional: Run cleanup to remove old JSON columns</li>';
            echo '</ol>';
            
            echo '<p><a href="' . admin_url() . '" class="button button-primary">Go to Admin Dashboard</a></p>';
            
        } else {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h2 style="color: #721c24;">❌ Migration Failed</h2>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            
            if (!empty($result['errors'])) {
                echo '<h3>Errors:</h3>';
                echo '<ul>';
                foreach ($result['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
            
            echo '<p><strong>Please check your error logs and try again.</strong></p>';
        }
        
    } catch (Exception $e) {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h2 style="color: #721c24;">❌ Migration Exception</h2>';
        echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
        echo '</div>';
    }
    
    exit; // Stop processing after migration
});

/**
 * Add admin notice to remind about migration
 */
add_action('admin_notices', function() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    if (get_option('mobooking_migration_completed')) {
        return;
    }
    
    echo '<div class="notice notice-warning is-dismissible">';
    echo '<p><strong>MoBooking Migration Required:</strong> ';
    echo 'You need to run the database migration. ';
    echo '<a href="' . admin_url('?run_mobooking_migration=1') . '" class="button button-secondary">Run Migration Now</a>';
    echo '</p>';
    echo '</div>';
});

/**
 * Optional: Add cleanup function (run AFTER testing migration success)
 */
add_action('wp_loaded', function() {
    if (!is_admin() || !current_user_can('administrator')) {
        return;
    }
    
    if (!isset($_GET['cleanup_mobooking_json']) || $_GET['cleanup_mobooking_json'] !== '1') {
        return;
    }
    
    if (!get_option('mobooking_migration_completed')) {
        wp_die('Run migration first before cleanup');
    }
    
    try {
        require_once MOBOOKING_PATH . '/classes/Database/DataMigration.php';
        
        $migration = new \MoBooking\Database\DataMigration();
        $result = $migration->cleanup_json_columns();
        
        echo '<h1>MoBooking JSON Cleanup</h1>';
        
        if ($result['success']) {
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h2 style="color: #155724;">✅ Cleanup Successful!</h2>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px;">';
            echo '<h2 style="color: #721c24;">❌ Cleanup Failed</h2>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<p>Error: ' . esc_html($e->getMessage()) . '</p>';
    }
    
    exit;
});
?>