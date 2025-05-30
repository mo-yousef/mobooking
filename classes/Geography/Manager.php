<?php
namespace MoBooking\Geography;

/**
 * Enhanced Geography Manager class with ZIP Code Integration
 * Supports country selection, city management, and automatic ZIP code fetching
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register enhanced AJAX handlers
        add_action('wp_ajax_mobooking_set_service_country', array($this, 'ajax_set_service_country'));
        add_action('wp_ajax_mobooking_reset_service_country', array($this, 'ajax_reset_service_country'));
        add_action('wp_ajax_mobooking_save_area_with_zips', array($this, 'ajax_save_area_with_zips'));
        add_action('wp_ajax_mobooking_get_area_details', array($this, 'ajax_get_area_details'));
        add_action('wp_ajax_mobooking_get_area_zip_codes', array($this, 'ajax_get_area_zip_codes'));
        add_action('wp_ajax_mobooking_refresh_area_zip_codes', array($this, 'ajax_refresh_area_zip_codes'));
        add_action('wp_ajax_mobooking_bulk_area_action', array($this, 'ajax_bulk_area_action'));
        
        // Legacy AJAX handlers (maintain backward compatibility)
        add_action('wp_ajax_mobooking_save_area', array($this, 'ajax_save_area'));
        add_action('wp_ajax_mobooking_delete_area', array($this, 'ajax_delete_area'));
        add_action('wp_ajax_mobooking_toggle_area_status', array($this, 'ajax_toggle_area_status'));
        add_action('wp_ajax_mobooking_get_areas', array($this, 'ajax_get_areas'));
        
        // Add shortcodes
        add_shortcode('mobooking_area_list', array($this, 'area_list_shortcode'));
        
        // Initialize database enhancements
        add_action('init', array($this, 'maybe_upgrade_database'), 5);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking\Geography\Manager: Enhanced constructor called');
        }
    }
    
    /**
     * Maybe upgrade database to support enhanced features
     */
    public function maybe_upgrade_database() {
        $version = get_option('mobooking_geography_db_version', '1.0');
        
        if (version_compare($version, '2.0', '<')) {
            $this->upgrade_areas_table();
            update_option('mobooking_geography_db_version', '2.0');
        }
    }
    
    /**
     * Upgrade areas table to support enhanced features
     */
    private function upgrade_areas_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        // Add new columns if they don't exist
        $columns_to_add = array(
            'city_name' => 'VARCHAR(255) NULL AFTER label',
            'state' => 'VARCHAR(100) NULL AFTER city_name',
            'country' => 'VARCHAR(10) NULL AFTER state',
            'description' => 'TEXT NULL AFTER country',
            'zip_codes' => 'LONGTEXT NULL AFTER description'
        );
        
        foreach ($columns_to_add as $column => $definition) {
            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                    DB_NAME, $table_name, $column
                )
            );
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column $definition");
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Added column $column to $table_name");
                }
            }
        }
        
        // Migrate existing data
        $this->migrate_existing_data();
    }
    
    /**
     * Migrate existing data to new structure
     */
    private function migrate_existing_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        // Migrate single ZIP codes to ZIP codes array
        $areas_to_migrate = $wpdb->get_results(
            "SELECT id, zip_code, label FROM $table_name 
             WHERE zip_codes IS NULL AND zip_code IS NOT NULL"
        );
        
        foreach ($areas_to_migrate as $area) {
            $zip_codes_json = json_encode(array($area->zip_code));
            $city_name = $area->label ?: 'Area ' . $area->id;
            
            $wpdb->update(
                $table_name,
                array(
                    'city_name' => $city_name,
                    'zip_codes' => $zip_codes_json
                ),
                array('id' => $area->id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Get user's selected service country
     */
    public function get_user_service_country($user_id) {
        return get_user_meta($user_id, 'mobooking_service_country', true);
    }
    
    /**
     * Set user's service country
     */
    public function set_user_service_country($user_id, $country_code) {
        return update_user_meta($user_id, 'mobooking_service_country', $country_code);
    }
    
    /**
     * Get areas for a user with enhanced data
     */
    public function get_user_areas($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Getting enhanced areas for user {$user_id}");
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY city_name ASC, label ASC",
            $user_id
        ));
        
        // Process results to ensure backward compatibility
        foreach ($results as $result) {
            // If zip_codes is not set but zip_code is, create zip_codes array
            if (empty($result->zip_codes) && !empty($result->zip_code)) {
                $result->zip_codes = json_encode(array($result->zip_code));
            }
            
            // Ensure city_name is set
            if (empty($result->city_name) && !empty($result->label)) {
                $result->city_name = $result->label;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Found " . count($results) . " enhanced areas for user {$user_id}");
        }
        
        return $results;
    }
    
    /**
     * Get a specific area with enhanced data
     */
    public function get_area($area_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $sql = "SELECT * FROM $table_name WHERE id = %d";
        $params = array($area_id);
        
        if ($user_id) {
            $sql .= " AND user_id = %d";
            $params[] = $user_id;
        }
        
        $result = $wpdb->get_row($wpdb->prepare($sql, $params));
        
        if ($result) {
            // Process for backward compatibility
            if (empty($result->zip_codes) && !empty($result->zip_code)) {
                $result->zip_codes = json_encode(array($result->zip_code));
            }
            
            if (empty($result->city_name) && !empty($result->label)) {
                $result->city_name = $result->label;
            }
        }
        
        return $result;
    }
    
    /**
     * Enhanced ZIP coverage check with area details
     */
    public function is_zip_covered($zip_code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        // Input validation
        if (empty($zip_code) || empty($user_id)) {
            return false;
        }
        
        $zip_code = sanitize_text_field(trim($zip_code));
        $user_id = absint($user_id);
        
        if (empty($zip_code) || $user_id <= 0) {
            return false;
        }
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            return false;
        }
        
        // Check both old zip_code field and new zip_codes JSON field
        $sql = "SELECT * FROM $table_name 
                WHERE user_id = %d AND active = 1 
                AND (
                    zip_code = %s 
                    OR (zip_codes IS NOT NULL AND JSON_CONTAINS(zip_codes, %s))
                )";
        
        $area = $wpdb->get_row($wpdb->prepare(
            $sql, 
            $user_id, 
            $zip_code, 
            json_encode($zip_code)
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: ZIP coverage check - ZIP: {$zip_code}, User: {$user_id}, Result: " . ($area ? 'COVERED' : 'NOT COVERED'));
        }
        
        return $area ? $area : false;
    }
    
    /**
     * Save an area with enhanced ZIP codes support
     */
    public function save_area_with_zips($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        try {
            // Enhanced data validation
            if (empty($data['user_id']) || empty($data['city_name'])) {
                return false;
            }
            
            // Sanitize data
            $area_data = array(
                'user_id' => absint($data['user_id']),
                'city_name' => sanitize_text_field(trim($data['city_name'])),
                'state' => isset($data['state']) ? sanitize_text_field(trim($data['state'])) : '',
                'country' => isset($data['country']) ? sanitize_text_field(trim($data['country'])) : '',
                'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
                'label' => isset($data['city_name']) ? sanitize_text_field(trim($data['city_name'])) : '', // For backward compatibility
                'active' => isset($data['active']) ? (bool) $data['active'] : true
            );
            
            // Process ZIP codes
            if (isset($data['zip_codes'])) {
                $zip_codes = is_string($data['zip_codes']) ? json_decode($data['zip_codes'], true) : $data['zip_codes'];
                if (is_array($zip_codes) && !empty($zip_codes)) {
                    // Clean and validate ZIP codes
                    $clean_zips = array();
                    foreach ($zip_codes as $zip) {
                        $clean_zip = sanitize_text_field(trim($zip));
                        if (!empty($clean_zip)) {
                            $clean_zips[] = $clean_zip;
                        }
                    }
                    
                    if (!empty($clean_zips)) {
                        $area_data['zip_codes'] = json_encode(array_unique($clean_zips));
                        // Set first ZIP as main zip_code for backward compatibility
                        $area_data['zip_code'] = $clean_zips[0];
                    }
                }
            }
            
            // Check if user exists
            $user = get_userdata($area_data['user_id']);
            if (!$user) {
                return false;
            }
            
            // Check for duplicates (by city name and state)
            $existing_query = "SELECT id FROM $table_name WHERE user_id = %d AND city_name = %s";
            $existing_params = array($area_data['user_id'], $area_data['city_name']);
            
            if (!empty($area_data['state'])) {
                $existing_query .= " AND state = %s";
                $existing_params[] = $area_data['state'];
            }
            
            if (!empty($data['id'])) {
                $existing_query .= " AND id != %d";
                $existing_params[] = absint($data['id']);
            }
            
            $existing = $wpdb->get_var($wpdb->prepare($existing_query, $existing_params));
            
            if ($existing) {
                return false; // Duplicate area
            }
            
            // Update or insert
            if (!empty($data['id'])) {
                // Update existing area
                $area_id = absint($data['id']);
                
                $result = $wpdb->update(
                    $table_name,
                    $area_data,
                    array('id' => $area_id),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d'),
                    array('%d')
                );
                
                if ($result === false) {
                    return false;
                }
                
                return $area_id;
            } else {
                // Create new area
                $result = $wpdb->insert(
                    $table_name,
                    $area_data,
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
                );
                
                if ($result === false) {
                    return false;
                }
                
                return $wpdb->insert_id;
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in save_area_with_zips: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Fetch ZIP codes from Zippopotam.us API
     */
    public function fetch_zip_codes_from_api($city_name, $country_code, $state = '') {
        $api_url = 'https://api.zippopotam.us/' . strtoupper($country_code) . '/' . urlencode($city_name);
        
        // Add state for US
        if (!empty($state) && strtoupper($country_code) === 'US') {
            $api_url .= '/' . urlencode($state);
        }
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'MoBooking Service Areas Manager'
            )
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            return array(
                'success' => false,
                'message' => $status_code === 404 ? 'City not found' : 'API error (HTTP ' . $status_code . ')'
            );
        }
        
        $data = json_decode($body, true);
        
        if (!$data || !isset($data['places']) || !is_array($data['places'])) {
            return array(
                'success' => false,
                'message' => 'Invalid API response format'
            );
        }
        
        // Extract ZIP codes
        $zip_codes = array();
        foreach ($data['places'] as $place) {
            if (isset($place['post code']) && !empty($place['post code'])) {
                $zip_codes[] = $place['post code'];
            }
        }
        
        // Remove duplicates and sort
        $zip_codes = array_unique($zip_codes);
        sort($zip_codes);
        
        if (empty($zip_codes)) {
            return array(
                'success' => false,
                'message' => 'No ZIP codes found for this location'
            );
        }
        
        return array(
            'success' => true,
            'zip_codes' => $zip_codes,
            'location_info' => array(
                'place_name' => $data['place name'] ?? $city_name,
                'country' => $data['country'] ?? $country_code,
                'state' => $data['state'] ?? $state
            ),
            'count' => count($zip_codes)
        );
    }
    
    /**
     * Delete an area
     */
    public function delete_area($area_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $area_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Toggle area status
     */
    public function toggle_area_status($area_id, $user_id, $active) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $result = $wpdb->update(
            $table_name,
            array('active' => $active ? 1 : 0),
            array('id' => $area_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * AJAX: Set service country
     */
    public function ajax_set_service_country() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $user_id = get_current_user_id();
            $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
            
            if (empty($country)) {
                wp_send_json_error(__('Country is required.', 'mobooking'));
            }
            
            // Validate country code
            $supported_countries = array(
                'US', 'CA', 'GB', 'DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'CH', 'AT', 'SE', 'NO', 'DK', 'FI',
                'PL', 'CZ', 'HU', 'SK', 'SI', 'HR', 'PT', 'IE', 'LU', 'MT', 'CY', 'EE', 'LV', 'LT',
                'JP', 'AU', 'NZ', 'MX', 'BR', 'AR', 'IN', 'TR', 'RU', 'ZA'
            );
            
            if (!in_array($country, $supported_countries)) {
                wp_send_json_error(__('Invalid country code.', 'mobooking'));
            }
            
            // Save country
            $result = $this->set_user_service_country($user_id, $country);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Service country set successfully.', 'mobooking'),
                    'country' => $country
                ));
            } else {
                wp_send_json_error(__('Failed to set service country.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while setting the country.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Reset service country
     */
    public function ajax_reset_service_country() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $user_id = get_current_user_id();
            
            // Delete all areas first
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_areas';
            $wpdb->delete($table_name, array('user_id' => $user_id), array('%d'));
            
            // Reset country
            delete_user_meta($user_id, 'mobooking_service_country');
            
            wp_send_json_success(array(
                'message' => __('Service country reset successfully. All service areas have been removed.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while resetting the country.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Save area with ZIP codes
     */
    public function ajax_save_area_with_zips() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $user_id = get_current_user_id();
            
            // Prepare data
            $area_data = array(
                'user_id' => $user_id,
                'city_name' => isset($_POST['city_name']) ? $_POST['city_name'] : '',
                'state' => isset($_POST['state']) ? $_POST['state'] : '',
                'country' => isset($_POST['country']) ? $_POST['country'] : '',
                'description' => isset($_POST['description']) ? $_POST['description'] : '',
                'active' => isset($_POST['active']) ? (bool) $_POST['active'] : true,
                'zip_codes' => isset($_POST['zip_codes']) ? $_POST['zip_codes'] : ''
            );
            
            // Add ID if editing
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $area_data['id'] = $_POST['id'];
                
                // Verify ownership
                $area = $this->get_area($area_data['id']);
                if (!$area || $area->user_id != $user_id) {
                    wp_send_json_error(__('You do not have permission to edit this area.', 'mobooking'));
                }
            }
            
            // Validate data
            if (empty($area_data['city_name'])) {
                wp_send_json_error(__('City name is required.', 'mobooking'));
            }
            
            // Save area
            $area_id = $this->save_area_with_zips($area_data);
            
            if (!$area_id) {
                wp_send_json_error(__('Failed to save area. This city may already exist.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'id' => $area_id,
                'message' => __('Service area saved successfully.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while saving the area.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Get area details
     */
    public function ajax_get_area_details() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $area_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $user_id = get_current_user_id();
            
            if (!$area_id) {
                wp_send_json_error(__('Area ID is required.', 'mobooking'));
            }
            
            $area = $this->get_area($area_id, $user_id);
            
            if (!$area) {
                wp_send_json_error(__('Area not found or access denied.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'area' => $area
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while loading area details.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Get area ZIP codes
     */
    public function ajax_get_area_zip_codes() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $area_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $user_id = get_current_user_id();
            
            if (!$area_id) {
                wp_send_json_error(__('Area ID is required.', 'mobooking'));
            }
            
            $area = $this->get_area($area_id, $user_id);
            
            if (!$area) {
                wp_send_json_error(__('Area not found or access denied.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'area' => $area
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while loading ZIP codes.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Refresh area ZIP codes from API
     */
    public function ajax_refresh_area_zip_codes() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $area_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $user_id = get_current_user_id();
            
            if (!$area_id) {
                wp_send_json_error(__('Area ID is required.', 'mobooking'));
            }
            
            $area = $this->get_area($area_id, $user_id);
            
            if (!$area) {
                wp_send_json_error(__('Area not found or access denied.', 'mobooking'));
            }
            
            // Fetch new ZIP codes
            $result = $this->fetch_zip_codes_from_api(
                $area->city_name ?: $area->label,
                $area->country ?: $this->get_user_service_country($user_id),
                $area->state
            );
            
            if (!$result['success']) {
                wp_send_json_error($result['message']);
            }
            
            // Update area with new ZIP codes
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_areas';
            
            $update_result = $wpdb->update(
                $table_name,
                array(
                    'zip_codes' => json_encode($result['zip_codes']),
                    'zip_code' => $result['zip_codes'][0] // First ZIP for backward compatibility
                ),
                array('id' => $area_id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($update_result === false) {
                wp_send_json_error(__('Failed to update ZIP codes.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully refreshed %d ZIP codes.', 'mobooking'), $result['count']),
                'zip_codes' => $result['zip_codes'],
                'count' => $result['count']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while refreshing ZIP codes.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Handle bulk actions
     */
    public function ajax_bulk_area_action() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
            $area_ids = isset($_POST['area_ids']) ? array_map('absint', $_POST['area_ids']) : array();
            $user_id = get_current_user_id();
            
            if (empty($action) || empty($area_ids)) {
                wp_send_json_error(__('Action and area IDs are required.', 'mobooking'));
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_areas';
            
            $success_count = 0;
            
            switch ($action) {
                case 'activate':
                case 'deactivate':
                    $active_value = $action === 'activate' ? 1 : 0;
                    $placeholders = implode(',', array_fill(0, count($area_ids), '%d'));
                    
                    $result = $wpdb->query($wpdb->prepare(
                        "UPDATE $table_name SET active = %d WHERE user_id = %d AND id IN ($placeholders)",
                        array_merge(array($active_value, $user_id), $area_ids)
                    ));
                    
                    $success_count = $result;
                    break;
                    
                case 'delete':
                    $placeholders = implode(',', array_fill(0, count($area_ids), '%d'));
                    
                    $result = $wpdb->query($wpdb->prepare(
                        "DELETE FROM $table_name WHERE user_id = %d AND id IN ($placeholders)",
                        array_merge(array($user_id), $area_ids)
                    ));
                    
                    $success_count = $result;
                    break;
                    
                case 'refresh_zip_codes':
                    $country = $this->get_user_service_country($user_id);
                    
                    foreach ($area_ids as $area_id) {
                        $area = $this->get_area($area_id, $user_id);
                        if ($area) {
                            $result = $this->fetch_zip_codes_from_api(
                                $area->city_name ?: $area->label,
                                $country,
                                $area->state
                            );
                            
                            if ($result['success']) {
                                $wpdb->update(
                                    $table_name,
                                    array(
                                        'zip_codes' => json_encode($result['zip_codes']),
                                        'zip_code' => $result['zip_codes'][0]
                                    ),
                                    array('id' => $area_id),
                                    array('%s', '%s'),
                                    array('%d')
                                );
                                $success_count++;
                            }
                        }
                    }
                    break;
                    
                default:
                    wp_send_json_error(__('Invalid bulk action.', 'mobooking'));
            }
            
            $message = sprintf(
                _n(
                    'Bulk action completed successfully on %d area.',
                    'Bulk action completed successfully on %d areas.',
                    $success_count,
                    'mobooking'
                ),
                $success_count
            );
            
            wp_send_json_success(array(
                'message' => $message,
                'count' => $success_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while performing bulk action.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Toggle area status (legacy support)
     */
    public function ajax_toggle_area_status() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $area_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $active = isset($_POST['active']) ? (bool) $_POST['active'] : false;
            $user_id = get_current_user_id();
            
            if (!$area_id) {
                wp_send_json_error(__('Area ID is required.', 'mobooking'));
            }
            
            $result = $this->toggle_area_status($area_id, $user_id, $active);
            
            if ($result) {
                $message = $active ? __('Area activated successfully.', 'mobooking') : __('Area deactivated successfully.', 'mobooking');
                wp_send_json_success(array('message' => $message));
            } else {
                wp_send_json_error(__('Failed to update area status.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while toggling area status.', 'mobooking'));
        }
    }
    
    /**
     * Legacy AJAX handlers for backward compatibility
     */
    
    public function ajax_save_area() {
        // Convert legacy format to new format
        if (isset($_POST['zip_code']) && !isset($_