<?php
namespace MoBooking\Database;

/**
 * Migration class for moving from unified table to separate services and options tables
 */
class SeparateTablesMigration {
    /**
     * Run the migration
     */
    public function run() {
        global $wpdb;
        
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Check if both tables exist
        $services_exists = $wpdb->get_var("SHOW TABLES LIKE '$services_table'") == $services_table;
        $options_exists = $wpdb->get_var("SHOW TABLES LIKE '$options_table'") == $options_table;
        
        if (!$services_exists || !$options_exists) {
            error_log('Migration failed: Required tables do not exist');
            return false;
        }
        
        try {
            // Begin transaction
            $wpdb->query('START TRANSACTION');
            
            // Step 1: Clean up the services table - remove old unified columns if they exist
            $this->cleanup_services_table();
            
            // Step 2: Migrate options from services table to options table
            $migrated_count = $this->migrate_options();
            
            // Step 3: Remove migrated options from services table
            $this->cleanup_migrated_options();
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            error_log("Migration completed successfully. Migrated $migrated_count options.");
            return true;
            
        } catch (\Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('Migration failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up the services table by removing old unified table columns
     */
    private function cleanup_services_table() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        // Check what columns exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $services_table");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // Columns that should be removed from services table (these belong to options)
        $columns_to_remove = [
            'entity_type',
            'parent_id',
            'type',
            'is_required',
            'default_value',
            'placeholder',
            'min_value',
            'max_value',
            'price_impact',
            'price_type',
            'options',
            'option_label',
            'step',
            'unit',
            'min_length',
            'max_length',
            'rows',
            'display_order'
        ];
        
        foreach ($columns_to_remove as $column) {
            if (in_array($column, $column_names)) {
                // Don't drop columns yet, just mark them for cleanup after migration
                // We'll need them during the migration process
            }
        }
    }
    
    /**
     * Migrate options from services table to options table
     */
    private function migrate_options() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Get all options from the services table
        $options = $wpdb->get_results(
            "SELECT * FROM $services_table WHERE entity_type = 'option' ORDER BY parent_id, display_order"
        );
        
        $migrated_count = 0;
        
        foreach ($options as $option) {
            // Check if this option already exists in the options table
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $options_table WHERE service_id = %d AND name = %s",
                $option->parent_id, $option->name
            ));
            
            if ($existing) {
                continue; // Skip if already migrated
            }
            
            // Prepare option data for insertion
            $option_data = [
                'service_id' => $option->parent_id,
                'name' => $option->name,
                'description' => $option->description ?: '',
                'type' => $option->type ?: 'checkbox',
                'is_required' => $option->is_required ?: 0,
                'default_value' => $option->default_value ?: '',
                'placeholder' => $option->placeholder ?: '',
                'min_value' => $option->min_value,
                'max_value' => $option->max_value,
                'price_impact' => $option->price_impact ?: 0,
                'price_type' => $option->price_type ?: 'fixed',
                'options' => $option->options ?: '',
                'option_label' => $option->option_label ?: '',
                'step' => $option->step ?: '',
                'unit' => $option->unit ?: '',
                'min_length' => $option->min_length,
                'max_length' => $option->max_length,
                'rows' => $option->rows,
                'display_order' => $option->display_order ?: 0,
                'created_at' => $option->created_at ?: current_time('mysql'),
                'updated_at' => $option->updated_at ?: current_time('mysql')
            ];
            
            // Insert into options table
            $result = $wpdb->insert($options_table, $option_data);
            
            if ($result !== false) {
                $migrated_count++;
            } else {
                error_log('Failed to migrate option: ' . $option->name . ' (ID: ' . $option->id . ')');
            }
        }
        
        return $migrated_count;
    }
    
    /**
     * Remove migrated options from services table
     */
    private function cleanup_migrated_options() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        // Delete all options from services table
        $deleted = $wpdb->delete($services_table, ['entity_type' => 'option'], ['%s']);
        
        error_log("Removed $deleted option records from services table");
        
        // Now try to remove the columns we no longer need
        $this->remove_unused_columns();
    }
    
    /**
     * Remove unused columns from services table
     */
    private function remove_unused_columns() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        // Columns to remove (be careful here)
        $columns_to_remove = [
            'entity_type',
            'parent_id',
            'type',
            'is_required',
            'default_value',
            'placeholder',
            'min_value',
            'max_value',
            'price_impact',
            'price_type',
            'options',
            'option_label',
            'step',
            'unit',
            'min_length',
            'max_length',
            'rows',
            'display_order'
        ];
        
        // Check which columns actually exist before trying to drop them
        $existing_columns = $wpdb->get_results("SHOW COLUMNS FROM $services_table");
        $existing_column_names = array_map(function($col) { return $col->Field; }, $existing_columns);
        
        foreach ($columns_to_remove as $column) {
            if (in_array($column, $existing_column_names)) {
                try {
                    $wpdb->query("ALTER TABLE $services_table DROP COLUMN $column");
                    error_log("Dropped column: $column");
                } catch (\Exception $e) {
                    error_log("Failed to drop column $column: " . $e->getMessage());
                    // Continue with other columns even if one fails
                }
            }
        }
    }
    
    /**
     * Rollback migration (if needed)
     */
    public function rollback() {
        // This would be complex and is not implemented
        // It would require moving data back from options table to services table
        error_log('Migration rollback is not implemented. Please restore from backup if needed.');
        return false;
    }
    
    /**
     * Check migration status
     */
    public function check_migration_status() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        $options_table = $wpdb->prefix . 'mobooking_service_options';
        
        // Count options in services table
        $options_in_services = $wpdb->get_var(
            "SELECT COUNT(*) FROM $services_table WHERE entity_type = 'option'"
        );
        
        // Count options in options table
        $options_in_options_table = $wpdb->get_var("SELECT COUNT(*) FROM $options_table");
        
        return [
            'options_in_services_table' => intval($options_in_services),
            'options_in_options_table' => intval($options_in_options_table),
            'migration_needed' => intval($options_in_services) > 0
        ];
    }
}