<?php
namespace MoBooking\Database;

/**
 * Database Manager class
 */
class Manager {
    /**
     * Tables to be created
     */
    private $tables = array(
        'services',
        'service_options', // New separate table for service options
        'bookings',
        'discounts',
        'areas',
        'settings'
    );
    /**
     * Create all database tables
     */
    public function create_tables() {
        foreach ($this->tables as $table) {
            $method = 'create_' . $table . '_table';
            if (method_exists($this, $method)) {
                $this->$method();
                
                // Log table creation
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Created table using method: {$method}");
                }
            }
        }
        
        // Run migration if needed to move data from unified table to separate tables
        $this->maybe_migrate_to_separate_tables();
        
        // Force create service_options table if it's missing
        // $this->ensure_service_options_table();
    }
    /**
     * Check if we need to migrate from unified table to separate tables
     */
    private function maybe_migrate_to_separate_tables() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Check if both tables exist
        $services_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
        $options_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'") == $options_table;
        
        if ($services_exists && $options_exists) {
            // Check if there are options in the unified services table that need migration
            $options_in_services_table = $wpdb->get_var(
                "SELECT COUNT(*) FROM $services_table WHERE entity_type = 'option'"
            );
            
            if ($options_in_services_table > 0) {
                $migration = new SeparateTablesMigration();
                $migration->run();
            }
        }
    }
    
    /**
     * Create services table (simplified, only for services)
     */
    public function create_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text NULL,
            price decimal(10,2) NOT NULL DEFAULT 0,
            duration int(11) NOT NULL DEFAULT 0,
            icon varchar(255) NULL,
            image_url varchar(255) NULL,
            category varchar(255) NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create service options table (new separate table)
     */
    public function create_service_options_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_service_options';
        
        $charset_collate = $wpdb->get_charset_collate();
        
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
            PRIMARY KEY  (id),
            KEY service_id (service_id),
            KEY display_order (display_order),
            FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}mobooking_services(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create bookings table
     */
    public function create_bookings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(255) NULL,
            customer_address text NOT NULL,
            zip_code varchar(20) NOT NULL,
            service_date datetime NOT NULL,
            services text NOT NULL,
            service_options text NULL,
            total_price decimal(10,2) NOT NULL,
            discount_code varchar(255) NULL,
            discount_amount decimal(10,2) NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY service_date (service_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create discounts table
     */
    public function create_discounts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            code varchar(50) NOT NULL,
            type varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            expiry_date date NULL,
            usage_limit int(11) NULL,
            usage_count int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            UNIQUE KEY user_code (user_id, code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create areas table
     */
    public function create_areas_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            zip_code varchar(20) NOT NULL,
            label varchar(255) NULL,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            UNIQUE KEY user_zip (user_id, zip_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create settings table
     */
    public function create_settings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_settings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            company_name varchar(255) NULL,
            primary_color varchar(20) NULL,
            logo_url varchar(255) NULL,
            phone varchar(50) NULL,
            email_header text NULL,
            email_footer text NULL,
            terms_conditions text NULL,
            booking_confirmation_message text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Force recreation of tables (use with caution)
     */
    public function force_recreate_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'mobooking_services',
            $wpdb->prefix . 'mobooking_service_options'
        ];
        
        // Drop tables if they exist
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Recreate tables
        $this->create_services_table();
        $this->create_service_options_table();
    }
    
/**
     * Utility method to get table info
     */
    public function get_table_info($table_name) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . 'mobooking_' . $table_name;
        
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") == $full_table_name;
        
        if (!$exists) {
            return [
                'exists' => false,
                'columns' => [],
                'row_count' => 0
            ];
        }
        
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $full_table_name");
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        
        return [
            'exists' => true,
            'columns' => array_map(function($col) { return $col->Field; }, $columns),
            'row_count' => intval($row_count)
        ];
    }



}