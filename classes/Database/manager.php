<?php
namespace MoBooking\Database;

/**
 * Database Manager class - FIXED VERSION with proper relationships
 */
class Manager {
    /**
     * Tables to be created
     */
    private $tables = array(
        'services',
        'service_options',
        'bookings',
        'booking_services',        // NEW: Junction table
        'booking_service_options', // NEW: Junction table
        'discounts',
        'areas',
        'settings',
        'booking_form_settings'
    );

    /**
     * Create all database tables
     */
    public function create_tables() {
        // Create tables in proper order (parent tables first)
        $this->create_services_table();
        $this->create_service_options_table();
        $this->create_bookings_table();
        $this->create_booking_services_table();        // NEW
        $this->create_booking_service_options_table(); // NEW
        $this->create_discounts_table();
        $this->create_areas_table();
        $this->create_settings_table();
        $this->create_booking_form_settings_table();
        
        // Add foreign keys after all tables exist
        $this->add_foreign_key_constraints();
    }

    /**
     * Create services table - UPDATED with constraints
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
            status enum('active', 'inactive', 'draft', 'archived') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_category (category),
            KEY idx_user_status (user_id, status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create service options table - UPDATED with better indexes
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
            price_type enum('none', 'fixed', 'percentage', 'multiply', 'choice') DEFAULT 'none',
            options text NULL,
            option_label text NULL,
            step varchar(50) NULL,
            unit varchar(50) NULL,
            min_length int(11) NULL,
            max_length int(11) NULL,
            option_rows int(11) NULL,
            display_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_service_id (service_id),
            KEY idx_display_order (display_order),
            KEY idx_service_order (service_id, display_order),
            UNIQUE KEY idx_service_name (service_id, name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create bookings table - FIXED: Remove JSON columns
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
            total_price decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0,
            discount_code varchar(255) NULL,
            discount_amount decimal(10,2) NULL DEFAULT 0,
            status enum('pending', 'confirmed', 'completed', 'cancelled', 'rescheduled') DEFAULT 'pending',
            notes text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            KEY idx_service_date (service_date),
            KEY idx_customer_email (customer_email),
            KEY idx_user_status (user_id, status),
            KEY idx_user_date (user_id, service_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * NEW: Create booking services junction table
     */
    public function create_booking_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_booking_services';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            service_id bigint(20) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL,
            total_price decimal(10,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_booking_id (booking_id),
            KEY idx_service_id (service_id),
            UNIQUE KEY idx_booking_service (booking_id, service_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * NEW: Create booking service options junction table
     */
    public function create_booking_service_options_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_booking_service_options';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) NOT NULL,
            service_option_id bigint(20) NOT NULL,
            option_value text NULL,
            price_impact decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_booking_id (booking_id),
            KEY idx_service_option_id (service_option_id),
            UNIQUE KEY idx_booking_option (booking_id, service_option_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create discounts table - UPDATED with constraints
     */
    public function create_discounts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            code varchar(50) NOT NULL,
            type enum('percentage', 'fixed') NOT NULL,
            amount decimal(10,2) NOT NULL,
            expiry_date date NULL,
            usage_limit int(11) NULL,
            usage_count int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_code (code),
            KEY idx_active (active),
            UNIQUE KEY idx_user_code (user_id, code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create areas table - UPDATED with constraints
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
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_zip_code (zip_code),
            KEY idx_active (active),
            UNIQUE KEY idx_user_zip (user_id, zip_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create settings table - UPDATED with constraints
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
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            UNIQUE KEY idx_user_settings (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create booking form settings table - UPDATED with constraints
     */
    public function create_booking_form_settings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            form_title varchar(255) DEFAULT '',
            form_description text DEFAULT '',
            logo_url varchar(500) DEFAULT '',
            primary_color varchar(20) DEFAULT '#3b82f6',
            secondary_color varchar(20) DEFAULT '#1e40af',
            background_color varchar(20) DEFAULT '#ffffff',
            text_color varchar(20) DEFAULT '#1f2937',
            language varchar(10) DEFAULT 'en',
            show_service_descriptions tinyint(1) DEFAULT 1,
            show_price_breakdown tinyint(1) DEFAULT 1,
            show_form_header tinyint(1) DEFAULT 1,
            show_form_footer tinyint(1) DEFAULT 1,
            enable_zip_validation tinyint(1) DEFAULT 1,
            custom_css longtext DEFAULT '',
            custom_js longtext DEFAULT '',
            form_layout varchar(50) DEFAULT 'modern',
            step_indicator_style varchar(50) DEFAULT 'progress',
            button_style varchar(50) DEFAULT 'rounded',
            form_width varchar(50) DEFAULT 'standard',
            enable_testimonials tinyint(1) DEFAULT 0,
            testimonials_data longtext DEFAULT '',
            contact_info text DEFAULT '',
            social_links text DEFAULT '',
            custom_footer_text text DEFAULT '',
            seo_title varchar(255) DEFAULT '',
            seo_description text DEFAULT '',
            analytics_code text DEFAULT '',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            UNIQUE KEY idx_user_form_settings (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * NEW: Add foreign key constraints
     */
    public function add_foreign_key_constraints() {
        global $wpdb;
        
        // Note: WordPress/MySQL may not support all FK constraints
        // This is mainly for documentation and future database migrations
        
        $constraints = array(
            "fk_service_options_service_id" => "ALTER TABLE {$wpdb->prefix}mobooking_service_options
                ADD CONSTRAINT fk_service_options_service_id
                FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}mobooking_services(id)
                ON DELETE CASCADE ON UPDATE CASCADE",
            
            "fk_booking_services_booking_id" => "ALTER TABLE {$wpdb->prefix}mobooking_booking_services
                ADD CONSTRAINT fk_booking_services_booking_id
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}mobooking_bookings(id)
                ON DELETE CASCADE ON UPDATE CASCADE",
            
            "fk_booking_services_service_id" => "ALTER TABLE {$wpdb->prefix}mobooking_booking_services
                ADD CONSTRAINT fk_booking_services_service_id
                FOREIGN KEY (service_id) REFERENCES {$wpdb->prefix}mobooking_services(id)
                ON DELETE CASCADE ON UPDATE CASCADE",
            
            "fk_booking_options_booking_id" => "ALTER TABLE {$wpdb->prefix}mobooking_booking_service_options
                ADD CONSTRAINT fk_booking_options_booking_id
                FOREIGN KEY (booking_id) REFERENCES {$wpdb->prefix}mobooking_bookings(id)
                ON DELETE CASCADE ON UPDATE CASCADE",
            
            "fk_booking_options_service_option_id" => "ALTER TABLE {$wpdb->prefix}mobooking_booking_service_options
                ADD CONSTRAINT fk_booking_options_service_option_id
                FOREIGN KEY (service_option_id) REFERENCES {$wpdb->prefix}mobooking_service_options(id)
                ON DELETE CASCADE ON UPDATE CASCADE"
        );
        
        foreach ($constraints as $constraint_name => $constraint_sql) {
            // Check if constraint already exists before adding
            $constraint_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = %s AND CONSTRAINT_NAME = %s",
                DB_NAME, $constraint_name
            ));

            if (!$constraint_exists) {
                $wpdb->query($constraint_sql);
            }
        }
    }

    /**
     * NEW: Migration method to convert existing JSON data
     */
    public function migrate_json_data_to_normalized_tables() {
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get all bookings with JSON data
            $bookings = $wpdb->get_results("
                SELECT id, services, service_options 
                FROM {$wpdb->prefix}mobooking_bookings 
                WHERE services IS NOT NULL AND services != ''
            ");
            
            foreach ($bookings as $booking) {
                // Migrate services
                if (!empty($booking->services)) {
                    $services = json_decode($booking->services, true);
                    if (is_array($services)) {
                        foreach ($services as $service_id) {
                            // Get service price for migration
                            $service = $wpdb->get_row($wpdb->prepare(
                                "SELECT price FROM {$wpdb->prefix}mobooking_services WHERE id = %d",
                                $service_id
                            ));
                            
                            if ($service) {
                                $wpdb->insert(
                                    $wpdb->prefix . 'mobooking_booking_services',
                                    array(
                                        'booking_id' => $booking->id,
                                        'service_id' => $service_id,
                                        'quantity' => 1,
                                        'unit_price' => $service->price,
                                        'total_price' => $service->price
                                    ),
                                    array('%d', '%d', '%d', '%f', '%f')
                                );
                            }
                        }
                    }
                }
                
                // Migrate service options
                if (!empty($booking->service_options)) {
                    $options = json_decode($booking->service_options, true);
                    if (is_array($options)) {
                        foreach ($options as $option_id => $option_data) {
                            $wpdb->insert(
                                $wpdb->prefix . 'mobooking_booking_service_options',
                                array(
                                    'booking_id' => $booking->id,
                                    'service_option_id' => $option_id,
                                    'option_value' => is_array($option_data) ? json_encode($option_data) : $option_data,
                                    'price_impact' => 0 // Will need to recalculate
                                ),
                                array('%d', '%d', '%s', '%f')
                            );
                        }
                    }
                }
            }
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Migration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * NEW: Remove JSON columns after migration
     */
    public function remove_json_columns() {
        global $wpdb;
        
        $wpdb->query("ALTER TABLE {$wpdb->prefix}mobooking_bookings DROP COLUMN services");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}mobooking_bookings DROP COLUMN service_options");
    }
}