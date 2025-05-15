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
            }
        }
    }
    
    /**
     * Create services table
     */
    private function create_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            description text NULL,
            price decimal(10,2) NOT NULL,
            duration int(11) NOT NULL,
            icon varchar(255) NULL,
            category varchar(255) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create bookings table
     */
    private function create_bookings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(255) NULL,
            customer_address text NOT NULL,
            zip_code varchar(20) NOT NULL,
            service_date datetime NOT NULL,
            services text NOT NULL,
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
    private function create_discounts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
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
    private function create_areas_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
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
    private function create_settings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_settings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
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
}