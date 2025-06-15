<?php
/**
 * Debugging related functions.
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
