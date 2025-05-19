<?php
namespace MoBooking\Database;

/**
 * Migration class for moving data from separate services and options tables to the combined table
 */
class ServicesTableMigration {
    /**
     * Run the migration
     */
    public function run() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Step 1: Update the services table schema
        $this->update_services_table_schema();
        
        // Step 2: Add entity_type to existing services
        $wpdb->query("UPDATE $services_table SET entity_type = 'service', parent_id = NULL WHERE entity_type IS NULL");
        
        // Step 3: Migrate options to the services table
        $options = $wpdb->get_results("SELECT * FROM $options_table");
        
        foreach ($options as $option) {
            // Get the user_id from the parent service
            $user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM $services_table WHERE id = %d",
                $option->service_id
            ));
            
            // Insert the option into the services table
            $wpdb->insert(
                $services_table,
                array(
                    'user_id' => $user_id,
                    'parent_id' => $option->service_id,
                    'entity_type' => 'option',
                    'name' => $option->name,
                    'description' => $option->description,
                    'price' => 0, // Options don't have a base price
                    'duration' => 0, // Options don't have a duration
                    'type' => $option->type,
                    'is_required' => $option->is_required,
                    'default_value' => $option->default_value,
                    'placeholder' => $option->placeholder,
                    'min_value' => $option->min_value,
                    'max_value' => $option->max_value,
                    'price_impact' => $option->price_impact,
                    'price_type' => $option->price_type,
                    'options' => $option->options,
                    'option_label' => $option->option_label,
                    'step' => $option->step,
                    'unit' => $option->unit,
                    'min_length' => $option->min_length,
                    'max_length' => $option->max_length,
                    'rows' => $option->rows,
                    'display_order' => $option->display_order,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at
                )
            );
        }
        
        // Step 4: Optionally drop the options table (commented out for safety)
        // $wpdb->query("DROP TABLE IF EXISTS $options_table");
    }
    
    /**
     * Update the services table schema to include option-related fields
     */
    private function update_services_table_schema() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") != $services_table) {
            // Table doesn't exist, create it with all necessary columns
            $this->create_services_table();
            return;
        }
        
        // Add new columns to existing table if they don't exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $services_table");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // Add entity_type column
        if (!in_array('entity_type', $column_names)) {
            $wpdb->query("ALTER TABLE $services_table ADD COLUMN entity_type VARCHAR(20) DEFAULT 'service' AFTER user_id");
        }
        
        // Add parent_id column
        if (!in_array('parent_id', $column_names)) {
            $wpdb->query("ALTER TABLE $services_table ADD COLUMN parent_id BIGINT(20) NULL AFTER entity_type");
        }
        
        // Add option-specific columns
        $option_columns = array(
            'type' => "VARCHAR(50) NULL",
            'is_required' => "TINYINT(1) DEFAULT 0",
            'default_value' => "TEXT NULL",
            'placeholder' => "TEXT NULL",
            'min_value' => "FLOAT NULL",
            'max_value' => "FLOAT NULL",
            'price_impact' => "DECIMAL(10,2) DEFAULT 0",
            'price_type' => "VARCHAR(20) DEFAULT 'fixed'",
            'options' => "TEXT NULL",
            'option_label' => "TEXT NULL",
            'step' => "VARCHAR(50) NULL",
            'unit' => "VARCHAR(50) NULL",
            'min_length' => "INT(11) NULL",
            'max_length' => "INT(11) NULL",
            'rows' => "INT(11) NULL",
            'display_order' => "INT(11) DEFAULT 0"
        );
        
        foreach ($option_columns as $column => $definition) {
            if (!in_array($column, $column_names)) {
                $wpdb->query("ALTER TABLE $services_table ADD COLUMN $column $definition");
            }
        }
        
        // Add indexes
        $wpdb->query("ALTER TABLE $services_table ADD INDEX entity_type (entity_type)");
        $wpdb->query("ALTER TABLE $services_table ADD INDEX parent_id (parent_id)");
    }

    /**
     * Create the services table from scratch
     */
    private function create_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            entity_type varchar(20) DEFAULT 'service',
            parent_id bigint(20) NULL,
            name varchar(255) NOT NULL,
            description text NULL,
            price decimal(10,2) NOT NULL DEFAULT 0,
            duration int(11) NOT NULL DEFAULT 0,
            icon varchar(255) NULL,
            image_url varchar(255) NULL,
            category varchar(255) NULL,
            status varchar(20) DEFAULT 'active',
            type varchar(50) NULL,
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
            KEY user_id (user_id),
            KEY entity_type (entity_type),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}