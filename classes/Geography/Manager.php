<?php
namespace MoBooking\Geography;

/**
 * Enhanced Geography Manager class with Local JSON Data Integration
 * Replaces external APIs with local JSON file for Nordic countries
 * Version: 3.0 - Local JSON Implementation
 */
class Manager {
    
    // Hardcoded list of supported Nordic countries
    private $supported_nordic_countries = array(
        'SE' => array('name' => 'Sweden', 'has_local_data' => true),
        'NO' => array('name' => 'Norway', 'has_local_data' => true),
        'DK' => array('name' => 'Denmark', 'has_local_data' => true),
        'FI' => array('name' => 'Finland', 'has_local_data' => true)
    );
    
    // Fallback API providers for non-Nordic countries
    private $api_providers = array(
        'zippopotam' => 'http://api.zippopotam.us',
        'geonames' => 'http://api.geonames.org',
        'postcode_io' => 'https://api.postcodes.io'
    );
    
    private $local_data_cache = null;
    
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
        
        // AJAX handlers for data fetching
        add_action('wp_ajax_mobooking_fetch_city_areas', array($this, 'ajax_fetch_city_areas'));
        add_action('wp_ajax_mobooking_save_selected_areas', array($this, 'ajax_save_selected_areas'));
        add_action('wp_ajax_mobooking_get_available_cities', array($this, 'ajax_get_available_cities'));
        
        // Legacy AJAX handlers (maintain backward compatibility)
        add_action('wp_ajax_mobooking_save_area', array($this, 'ajax_save_area'));
        add_action('wp_ajax_mobooking_delete_area', array($this, 'ajax_delete_area'));
        add_action('wp_ajax_mobooking_toggle_area_status', array($this, 'ajax_toggle_area_status'));
        add_action('wp_ajax_mobooking_get_areas', array($this, 'ajax_get_areas'));
        add_action('wp_ajax_mobooking_check_zip_coverage', array($this, 'ajax_check_zip_coverage'));
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_areas_scripts'));


        // Add shortcodes
        add_shortcode('mobooking_area_list', array($this, 'area_list_shortcode'));
        
        // Initialize database enhancements
        add_action('init', array($this, 'maybe_upgrade_database'), 5);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking\Geography\Manager: Enhanced constructor with local JSON data integration');
        }
    }
    
        /**
     * Enqueue areas scripts and localize data
     */
    public function enqueue_areas_scripts($hook) {
        // Only load on areas admin page
        if ($hook !== 'toplevel_page_mobooking-areas' && $hook !== 'mobooking_page_mobooking-areas') {
            return;
        }
        
        $user_id = get_current_user_id();
        $selected_country = get_user_meta($user_id, 'mobooking_service_country', true);
        
        // Localize script data
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking-area-nonce'),
            'booking_nonce' => wp_create_nonce('mobooking-booking-nonce'),
            'current_country' => $selected_country,
            'user_id' => $user_id,
            'is_nordic' => $this->is_nordic_country($selected_country),
            'strings' => array(
                'loading' => __('Loading...', 'mobooking'),
                'error' => __('An error occurred', 'mobooking'),
                'success' => __('Success!', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this area?', 'mobooking'),
                'confirm_reset' => __('Are you sure? This will reset your country selection and remove all current areas.', 'mobooking'),
                'select_country' => __('Please select a country first.', 'mobooking'),
                'enter_city' => __('Please enter a city name', 'mobooking'),
                'network_error' => __('Network error occurred', 'mobooking'),
                'no_areas_found' => __('No areas found for this city', 'mobooking'),
                'select_areas' => __('Please select at least one area', 'mobooking'),
                'country_not_set' => __('Country not set. Please refresh the page.', 'mobooking'),
                'from_local_db' => __('From local database', 'mobooking'),
                'from_external_apis' => __('From external APIs', 'mobooking'),
                'high_quality_local' => __('High-Quality Local Data', 'mobooking'),
                'external_api_data' => __('External API Data', 'mobooking'),
                'local_data_desc' => __('This country uses our comprehensive local database with accurate ZIP codes and area information. Data is stored locally for fast access and high reliability.', 'mobooking'),
                'api_data_desc' => __('This country uses data from external APIs including Zippopotam and GeoNames. Data quality is good but depends on external service availability.', 'mobooking'),
                'deselect_all' => __('Deselect All', 'mobooking'),
                'select_all' => __('Select All', 'mobooking'),
                'active' => __('Active', 'mobooking'),
                'inactive' => __('Inactive', 'mobooking'),
                'areas_selected' => __('areas selected', 'mobooking'),
                'areas' => __('areas', 'mobooking'),
                'are_you_sure' => __('Are you sure you want to', 'mobooking'),
            )
        );
        
        // Output the JavaScript variables
        // wp_add_inline_script('jquery', 'window.mobooking_area_vars = ' . json_encode($localize_data) . '; window.mobooking_current_country = "' . esc_js($selected_country) . '";', 'before');
    }

    public function get_areas_script_data() {
        $user_id = get_current_user_id();
        $selected_country = get_user_meta($user_id, 'mobooking_service_country', true);

        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking-area-nonce'),
            'booking_nonce' => wp_create_nonce('mobooking-booking-nonce'),
            'current_country' => $selected_country,
            'user_id' => $user_id,
            'is_nordic' => $this->is_nordic_country($selected_country),
            'strings' => array(
                'loading' => __('Loading...', 'mobooking'),
                'error' => __('An error occurred', 'mobooking'),
                'success' => __('Success!', 'mobooking'),
                'confirm_delete' => __('Are you sure you want to delete this area?', 'mobooking'),
                'confirm_reset' => __('Are you sure? This will reset your country selection and remove all current areas.', 'mobooking'),
                'select_country' => __('Please select a country first.', 'mobooking'),
                'enter_city' => __('Please enter a city name', 'mobooking'),
                'network_error' => __('Network error occurred', 'mobooking'),
                'no_areas_found' => __('No areas found for this city', 'mobooking'),
                'select_areas' => __('Please select at least one area', 'mobooking'),
                'country_not_set' => __('Country not set. Please refresh the page.', 'mobooking'),
                'from_local_db' => __('From local database', 'mobooking'),
                'from_external_apis' => __('From external APIs', 'mobooking'),
                'high_quality_local' => __('High-Quality Local Data', 'mobooking'),
                'external_api_data' => __('External API Data', 'mobooking'),
                'local_data_desc' => __('This country uses our comprehensive local database with accurate ZIP codes and area information. Data is stored locally for fast access and high reliability.', 'mobooking'),
                'api_data_desc' => __('This country uses data from external APIs including Zippopotam and GeoNames. Data quality is good but depends on external service availability.', 'mobooking'),
                'deselect_all' => __('Deselect All', 'mobooking'),
                'select_all' => __('Select All', 'mobooking'),
                'active' => __('Active', 'mobooking'),
                'inactive' => __('Inactive', 'mobooking'),
                'areas_selected' => __('areas selected', 'mobooking'),
                'areas' => __('areas', 'mobooking'),
                'are_you_sure' => __('Are you sure you want to', 'mobooking'),
            )
        );
        return $localize_data;
    }
    /**
     * Get supported countries (updated to prioritize Nordic countries)
     */
    public function get_supported_countries() {
        // Merge Nordic countries with other supported countries
        $other_countries = array(
            'US' => array('name' => 'United States', 'has_api' => true),
            'CA' => array('name' => 'Canada', 'has_api' => true),
            'GB' => array('name' => 'United Kingdom', 'has_api' => true),
            'DE' => array('name' => 'Germany', 'has_api' => true),
            'FR' => array('name' => 'France', 'has_api' => true),
            'ES' => array('name' => 'Spain', 'has_api' => true),
            'IT' => array('name' => 'Italy', 'has_api' => true),
            'AU' => array('name' => 'Australia', 'has_api' => true),
            'CY' => array('name' => 'Cyprus', 'has_api' => true),
            'NL' => array('name' => 'Netherlands', 'has_api' => true),
            'BE' => array('name' => 'Belgium', 'has_api' => true),
            'CH' => array('name' => 'Switzerland', 'has_api' => true),
            'AT' => array('name' => 'Austria', 'has_api' => true)
        );
        
        return array_merge($this->supported_nordic_countries, $other_countries);
    }
    
    /**
     * Check if country uses local JSON data
     */
    private function is_nordic_country($country_code) {
        return isset($this->supported_nordic_countries[$country_code]);
    }
    
    /**
     * Load local JSON data from theme directory
     */
    private function load_local_data() {
        if ($this->local_data_cache !== null) {
            return $this->local_data_cache;
        }
        
        // Try to load from active theme directory first
        $theme_data_path = get_template_directory() . '/data/nordic-postal-data.json';
        
        // Fallback to child theme if exists
        if (!file_exists($theme_data_path) && is_child_theme()) {
            $theme_data_path = get_stylesheet_directory() . '/data/nordic-postal-data.json';
        }
        
        // Fallback to plugin directory
        if (!file_exists($theme_data_path)) {
            $theme_data_path = plugin_dir_path(__FILE__) . '../../data/nordic-postal-data.json';
        }
        
        if (!file_exists($theme_data_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Nordic postal data file not found at: ' . $theme_data_path);
            }
            $this->local_data_cache = array();
            return $this->local_data_cache;
        }
        
        $json_data = file_get_contents($theme_data_path);
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: JSON decode error: ' . json_last_error_msg());
            }
            $this->local_data_cache = array();
            return $this->local_data_cache;
        }
        
        $this->local_data_cache = is_array($data) ? $data : array();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Loaded ' . count($this->local_data_cache) . ' postal code records from local JSON');
        }
        
        return $this->local_data_cache;
    }
    
    /**
     * Parse JSON entry and extract meaningful data
     * Based on the provided JSON structure: 
     * { "4": "4", "SE": "SE", "624 66": "624 67", "Fårö": "Fårö", "Gotland": "Gotland", "05": "05", ... }
     */
    private function parse_json_entry($entry) {
        if (!is_array($entry)) {
            return null;
        }

        $potential_values = array_values($entry);
        // Filter out empty strings and known non-data values like "4", "05" etc. if they are consistent.
        // For now, let's focus on identifying based on format.
        $potential_values = array_filter(array_map('trim', $potential_values), function($val) {
            return $val !== '' && !in_array($val, ['4', '05']); // Example: filter out known noise
        });
        // Remove duplicate values to simplify processing, as keys are noisy
        $potential_values = array_values(array_unique($potential_values));


        $parsed = [
            'country_code' => '',
            'zip_code' => '',
            'area_name' => '', // City
            'state' => '',     // Region
            'latitude' => '',
            'longitude' => ''
        ];

        $alpha_candidates = [];
        $numeric_coords = [];

        foreach ($potential_values as $value) {
            // Country code
            if (in_array($value, ['SE', 'NO', 'DK', 'FI']) && empty($parsed['country_code'])) {
                $parsed['country_code'] = $value;
                continue;
            }

            // ZIP code
            if (preg_match('/^\d+[\s\-]?\d*\s?\d*$/', $value) && strlen($value) >= 3 && empty($parsed['zip_code'])) {
                // Normalize ZIP: remove spaces for consistency if needed, though original format might be preferred for display
                $parsed['zip_code'] = str_replace(' ', '', $value); // Example normalization
                continue;
            }

            // Coordinates (latitude and longitude)
            if (is_numeric($value) && strpos($value, '.') !== false) {
                $coord = floatval($value);
                if ($coord >= -90 && $coord <= 90 && empty($parsed['latitude'])) {
                    $parsed['latitude'] = $coord;
                } elseif ($coord >= -180 && $coord <= 180 && empty($parsed['longitude'])) {
                    $parsed['longitude'] = $coord;
                }
                continue;
            }

            // Collect all other non-numeric, non-country-code strings as potential city/region
            if (preg_match('/^[a-zA-ZÀ-ÿ\s\-\.\(\)]+$/u', $value) && !in_array($value, ['SE', 'NO', 'DK', 'FI'])) {
                 if (strlen($value) > 1) { // Avoid single characters unless specifically needed
                    $alpha_candidates[] = $value;
                }
            }
        }
        
        // Post-process alpha candidates for area_name and state
        // This is heuristic. Given the JSON, it's hard to be certain.
        // Assumption: Longer strings are more likely to be unique names.
        // Or, if there are two, the first is city, second is region.
        // The original JSON structure is problematic because "Fårö" and "Gotland" are values of keys that are *also* "Fårö" and "Gotland" in the first record.
        // We need to rely on the values themselves.

        $unique_alpha = array_values(array_unique($alpha_candidates));
        if (count($unique_alpha) > 0) {
            $parsed['area_name'] = $unique_alpha[0]; // Assign the first unique alpha string as area_name (city)
            if (count($unique_alpha) > 1) {
                $parsed['state'] = $unique_alpha[1]; // Assign the second unique alpha string as state (region)
            } else {
                // If only one alpha candidate, it might be a city that is also a region, or just a city.
                // Depending on requirements, you might leave 'state' empty or assign area_name to it.
                // For now, leave state empty if only one candidate.
            }
        }


        // Validate required fields
        if (empty($parsed['country_code']) || empty($parsed['zip_code']) || empty($parsed['area_name'])) {
            // Log problematic entry for review if possible
            // error_log("Failed to parse entry: " . print_r($entry, true) . " -- Parsed: " . print_r($parsed, true));
            return null;
        }

        return [
            'area_name' => $parsed['area_name'],
            'zip_code' => $parsed['zip_code'], // Use the normalized zip
            'state' => $parsed['state'],
            'country' => $parsed['country_code'],
            'source' => 'Local JSON',
            'latitude' => $parsed['latitude'] ? floatval($parsed['latitude']) : 0,
            'longitude' => $parsed['longitude'] ? floatval($parsed['longitude']) : 0
        ];
    }
    
    /**
     * Fetch areas from local JSON data for Nordic countries
     */
    private function fetch_from_local_json($city_name, $country_code, $state = '') {
        $areas = array();
        $local_data = $this->load_local_data();

        if (empty($local_data)) {
            return $areas;
        }

        $city_name_lower = strtolower(trim($city_name));
        $country_code_upper = strtoupper(trim($country_code)); // Ensure consistent casing for country code

        $found_areas_map = array(); // Use a map to avoid duplicates based on a unique key

        foreach ($local_data as $entry_index => $raw_entry) {
            $parsed_area = $this->parse_json_entry($raw_entry);

            if (!$parsed_area) {
                // Optionally log parsing failures for this entry
                // error_log("Skipping unparsable entry at index {$entry_index}: " . print_r($raw_entry, true));
                continue;
            }

            // 1. Filter by country code (exact match)
            if (strtoupper($parsed_area['country']) !== $country_code_upper) {
                continue;
            }

            // 2. Filter by city name (case-insensitive, partial match on area_name or state)
            $area_name_lower = strtolower($parsed_area['area_name']);
            $state_name_lower = !empty($parsed_area['state']) ? strtolower($parsed_area['state']) : '';

            $city_match = false;
            if (strpos($area_name_lower, $city_name_lower) !== false) {
                $city_match = true;
            } elseif (!empty($state_name_lower) && strpos($state_name_lower, $city_name_lower) !== false) {
                $city_match = true;
            }
            // Optional: consider matching if the search term is part of the area name too
            // e.g., search "Fårö" matches area "Fårö"
            elseif (strpos($city_name_lower, $area_name_lower) !== false) {
                 $city_match = true;
            }


            if (!$city_match) {
                continue;
            }
            
            // Ensure source is correctly set
            $parsed_area['source'] = 'Local JSON';

            // Create a unique key for the area to prevent duplicates if multiple raw entries resolve to the same area.
            // For example, if area name and zip code define uniqueness.
            $unique_key = $parsed_area['area_name'] . '|' . $parsed_area['zip_code'] . '|' . $parsed_area['country'];
            
            if (!isset($found_areas_map[$unique_key])) {
                $found_areas_map[$unique_key] = $parsed_area;
            }
        }

        $areas = array_values($found_areas_map);

        // Sort by area name alphabetically
        usort($areas, function($a, $b) {
            return strcmp($a['area_name'], $b['area_name']);
        });

        // Limit results
        return array_slice($areas, 0, 50);
    }
    
    /**
     * Get available cities/regions for a Nordic country from local data
     */
    private function get_cities_from_local_json($country_code) {
        $local_data = $this->load_local_data();
        $cities = array();
        
        if (empty($local_data)) {
            return $cities;
        }
        
        $found_cities = array();
        
        foreach ($local_data as $entry) {
            $parsed = $this->parse_json_entry($entry);
            
            if (!$parsed || $parsed['country'] !== $country_code) {
                continue;
            }
            
            // Add area name as city
            if (!empty($parsed['area_name'])) {
                $found_cities[$parsed['area_name']] = array(
                    'name' => $parsed['area_name'],
                    'state' => $parsed['state']
                );
            }
            
            // Add region/state as city if different from area name
            if (!empty($parsed['state']) && $parsed['state'] !== $parsed['area_name']) {
                $found_cities[$parsed['state']] = array(
                    'name' => $parsed['state'],
                    'state' => ''
                );
            }
        }
        
        // Convert to indexed array and limit results
        $cities = array_values($found_cities);
        
        // Sort alphabetically
        usort($cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return array_slice($cities, 0, 100);
    }
    
    /**
     * Main method to fetch city areas - routes to local JSON or API based on country
     */
    private function fetch_city_areas_from_data_source($city_name, $country_code, $state = '') {
        // Use local JSON data for Nordic countries
        if ($this->is_nordic_country($country_code)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Using local JSON data for {$country_code}: {$city_name}");
            }
            return $this->fetch_from_local_json($city_name, $country_code, $state);
        }
        
        // Fall back to API for other countries
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Using external APIs for {$country_code}: {$city_name}");
        }
        return $this->fetch_city_areas_from_api($city_name, $country_code, $state);
    }
    
    /**
     * Get available cities for a country - routes to local JSON or API
     */
    private function get_available_cities_for_country($country_code) {
        // Use local JSON data for Nordic countries
        if ($this->is_nordic_country($country_code)) {
            return $this->get_cities_from_local_json($country_code);
        }
        
        // Fall back to predefined city lists for other countries
        return $this->get_major_cities_for_country($country_code);
    }
    
    /**
     * AJAX: Fetch areas/neighborhoods for a selected city
     */
    public function ajax_fetch_city_areas() {
        try {
            // Check nonce and permissions
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $city_name = sanitize_text_field($_POST['city_name'] ?? '');
            $country_code = sanitize_text_field($_POST['country_code'] ?? '');
            $state = sanitize_text_field($_POST['state'] ?? '');
            
            if (empty($city_name) || empty($country_code)) {
                wp_send_json_error(__('City name and country code are required.', 'mobooking'));
                return;
            }
            
            // Fetch areas using appropriate data source
            $areas = $this->fetch_city_areas_from_data_source($city_name, $country_code, $state);
            
            $data_source = $this->is_nordic_country($country_code) ? 'Local JSON' : 'External API';
            
            wp_send_json_success(array(
                'areas' => $areas,
                'city' => $city_name,
                'country' => $country_code,
                'total_found' => count($areas),
                'data_source' => $data_source,
                'is_nordic' => $this->is_nordic_country($country_code)
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_fetch_city_areas: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while fetching areas.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Get available cities for a country
     */
    public function ajax_get_available_cities() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $country_code = sanitize_text_field($_POST['country_code'] ?? '');
            
            if (empty($country_code)) {
                wp_send_json_error(__('Country code is required.', 'mobooking'));
                return;
            }
            
            $cities = $this->get_available_cities_for_country($country_code);
            $data_source = $this->is_nordic_country($country_code) ? 'Local JSON' : 'Predefined List';
            
            wp_send_json_success(array(
                'cities' => $cities,
                'country' => $country_code,
                'total_cities' => count($cities),
                'data_source' => $data_source,
                'is_nordic' => $this->is_nordic_country($country_code)
            ));
            
        } catch (Exception $e) {
            error_log('MoBooking: Exception in ajax_get_available_cities: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while fetching cities.', 'mobooking'),
                'debug' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX: Check ZIP coverage - Essential for booking form
     */
    public function ajax_check_zip_coverage() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            $zip_code = sanitize_text_field($_POST['zip_code'] ?? '');
            $user_id = get_current_user_id();
            
            if (empty($zip_code)) {
                wp_send_json_error(__('ZIP code is required.', 'mobooking'));
                return;
            }
            
            // Check coverage in user's service areas
            $coverage = $this->check_zip_coverage($zip_code, $user_id);
            
            wp_send_json_success($coverage);
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while checking coverage.', 'mobooking'));
        }
    }
    
    /**
     * Check if a ZIP code is covered by user's service areas
     */
    public function check_zip_coverage($zip_code, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mobooking_service_areas';
        
        // Check direct ZIP match first
        $direct_match = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE user_id = %d AND active = 1 
             AND (zip_codes LIKE %s OR zip_codes = %s)",
            $user_id,
            '%' . $zip_code . '%',
            $zip_code
        ));
        
        if ($direct_match) {
            return array(
                'covered' => true,
                'area_name' => $direct_match->area_name,
                'area_id' => $direct_match->id,
                'match_type' => 'direct'
            );
        }
        
        // For Nordic countries, check local JSON data
        $user_country = get_user_meta($user_id, 'mobooking_service_country', true);
        if ($this->is_nordic_country($user_country)) {
            $local_data = $this->load_local_data();
            
            foreach ($local_data as $entry) {
                $parsed = $this->parse_json_entry($entry);
                
                if ($parsed && $parsed['country'] === $user_country && 
                    (strpos($parsed['zip_code'], $zip_code) !== false || 
                     strpos($zip_code, $parsed['zip_code']) !== false)) {
                    
                    return array(
                        'covered' => true,
                        'area_name' => $parsed['area_name'],
                        'area_id' => null,
                        'match_type' => 'local_json',
                        'suggested_area' => $parsed
                    );
                }
            }
        }
        
        return array(
            'covered' => false,
            'area_name' => null,
            'area_id' => null,
            'match_type' => 'none'
        );
    }
    
    // === EXISTING METHODS (API FALLBACK FOR NON-NORDIC COUNTRIES) ===
    
    /**
     * Fetch areas/neighborhoods for a city using external APIs (fallback)
     */
    private function fetch_city_areas_from_api($city_name, $country_code, $state = '') {
        $areas = array();
        
        // Try different API providers in order of preference
        $providers = array(
            'zippopotam' => array($this, 'fetch_from_zippopotam'),
            'geonames' => array($this, 'fetch_from_geonames'),
            'postcode_io' => array($this, 'fetch_from_postcode_io')
        );
        
        foreach ($providers as $provider_name => $provider_method) {
            try {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Trying provider {$provider_name} for {$city_name}, {$country_code}");
                }
                
                $result = call_user_func($provider_method, $city_name, $country_code, $state);
                
                if (!empty($result) && is_array($result)) {
                    $areas = array_merge($areas, $result);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MoBooking: Provider {$provider_name} returned " . count($result) . " areas");
                    }
                    
                    // If we have enough results, break
                    if (count($areas) >= 10) {
                        break;
                    }
                }
                
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Provider {$provider_name} failed: " . $e->getMessage());
                }
                continue;
            }
        }
        
        // Remove duplicates and limit results
        $areas = $this->deduplicate_areas($areas);
        $areas = array_slice($areas, 0, 50);
        
        // If no real API data, fall back to mock data
        if (empty($areas)) {
            $areas = $this->generate_fallback_areas($city_name, $country_code, $state);
        }
        
        return $areas;
    }
    
    /**
     * Fetch areas from Zippopotam API
     */
    private function fetch_from_zippopotam($city_name, $country_code, $state = '') {
        $areas = array();
        
        try {
            $url = "http://api.zippopotam.us/{$country_code}/" . urlencode($city_name);
            
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'headers' => array('User-Agent' => 'MoBooking/1.0')
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('API request failed: ' . $response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['places'])) {
                return $areas;
            }
            
            foreach ($data['places'] as $place) {
                $area_name = $place['place name'] ?? '';
                $zip_code = $data['post code'] ?? '';
                $state_name = $place['state'] ?? $state;
                
                if (!empty($area_name) && !empty($zip_code)) {
                    $areas[] = array(
                        'area_name' => $area_name,
                        'zip_code' => $zip_code,
                        'state' => $state_name,
                        'country' => $country_code,
                        'source' => 'Zippopotam',
                        'latitude' => floatval($place['latitude'] ?? 0),
                        'longitude' => floatval($place['longitude'] ?? 0)
                    );
                }
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Zippopotam API error: ' . $e->getMessage());
            }
        }
        
        return $areas;
    }
    
    /**
     * Fetch areas from GeoNames API
     */
    private function fetch_from_geonames($city_name, $country_code, $state = '') {
        $areas = array();
        
        try {
            $username = get_option('mobooking_geonames_username', 'demo');
            
            $url = "http://api.geonames.org/postalCodeSearchJSON?" . http_build_query(array(
                'placename' => $city_name,
                'country' => $country_code,
                'maxRows' => 20,
                'username' => $username
            ));
            
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'headers' => array('User-Agent' => 'MoBooking/1.0')
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('API request failed: ' . $response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['postalCodes'])) {
                return $areas;
            }
            
            foreach ($data['postalCodes'] as $postal) {
                $area_name = $postal['placeName'] ?? '';
                $zip_code = $postal['postalCode'] ?? '';
                $state_name = $postal['adminName1'] ?? $state;
                
                if (!empty($area_name) && !empty($zip_code)) {
                    $areas[] = array(
                        'area_name' => $area_name,
                        'zip_code' => $zip_code,
                        'state' => $state_name,
                        'country' => $country_code,
                        'source' => 'GeoNames',
                        'latitude' => floatval($postal['lat'] ?? 0),
                        'longitude' => floatval($postal['lng'] ?? 0)
                    );
                }
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: GeoNames API error: ' . $e->getMessage());
            }
        }
        
        return $areas;
    }
    
    /**
     * Stub for Postcode.io API (mainly for UK)
     */
    private function fetch_from_postcode_io($city_name, $country_code, $state = '') {
        // Implementation would go here for UK postcodes
        return array();
    }
    
    /**
     * Remove duplicate areas
     */
    private function deduplicate_areas($areas) {
        $unique_areas = array();
        $seen_combinations = array();
        
        foreach ($areas as $area) {
            $key = $area['area_name'] . '|' . $area['zip_code'];
            
            if (!in_array($key, $seen_combinations)) {
                $seen_combinations[] = $key;
                $unique_areas[] = $area;
            }
        }
        
        return $unique_areas;
    }
    
    /**
     * Generate fallback mock areas if APIs fail
     */
    private function generate_fallback_areas($city_name, $country_code, $state = '') {
        return array(
            array(
                'area_name' => $city_name . ' Center',
                'zip_code' => '00000',
                'state' => $state,
                'country' => $country_code,
                'source' => 'Fallback',
                'latitude' => 0,
                'longitude' => 0
            )
        );
    }
    
    /**
     * Get major cities for non-Nordic countries (fallback)
     */
    private function get_major_cities_for_country($country_code) {
        $cities_by_country = array(
            'US' => array(
                array('name' => 'New York', 'state' => 'NY'),
                array('name' => 'Los Angeles', 'state' => 'CA'),
                array('name' => 'Chicago', 'state' => 'IL'),
                array('name' => 'Houston', 'state' => 'TX'),
                array('name' => 'Phoenix', 'state' => 'AZ'),
                array('name' => 'Philadelphia', 'state' => 'PA'),
                array('name' => 'San Antonio', 'state' => 'TX'),
                array('name' => 'San Diego', 'state' => 'CA'),
                array('name' => 'Dallas', 'state' => 'TX'),
                array('name' => 'San Jose', 'state' => 'CA')
            ),
            'CA' => array(
                array('name' => 'Toronto', 'state' => 'ON'),
                array('name' => 'Montreal', 'state' => 'QC'),
                array('name' => 'Vancouver', 'state' => 'BC'),
                array('name' => 'Calgary', 'state' => 'AB'),
                array('name' => 'Edmonton', 'state' => 'AB'),
                array('name' => 'Ottawa', 'state' => 'ON'),
                array('name' => 'Winnipeg', 'state' => 'MB'),
                array('name' => 'Quebec City', 'state' => 'QC')
            ),
            'GB' => array(
                array('name' => 'London', 'state' => 'England'),
                array('name' => 'Birmingham', 'state' => 'England'),
                array('name' => 'Manchester', 'state' => 'England'),
                array('name' => 'Glasgow', 'state' => 'Scotland'),
                array('name' => 'Liverpool', 'state' => 'England'),
                array('name' => 'Edinburgh', 'state' => 'Scotland'),
                array('name' => 'Leeds', 'state' => 'England'),
                array('name' => 'Cardiff', 'state' => 'Wales')
            ),
            'DE' => array(
                array('name' => 'Berlin', 'state' => ''),
                array('name' => 'Hamburg', 'state' => ''),
                array('name' => 'Munich', 'state' => 'Bavaria'),
                array('name' => 'Cologne', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Frankfurt', 'state' => 'Hesse'),
                array('name' => 'Stuttgart', 'state' => 'Baden-Württemberg'),
                array('name' => 'Düsseldorf', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Dortmund', 'state' => 'North Rhine-Westphalia')
            ),
            'FR' => array(
                array('name' => 'Paris', 'state' => 'Île-de-France'),
                array('name' => 'Marseille', 'state' => 'Provence-Alpes-Côte d\'Azur'),
                array('name' => 'Lyon', 'state' => 'Auvergne-Rhône-Alpes'),
                array('name' => 'Toulouse', 'state' => 'Occitanie'),
                array('name' => 'Nice', 'state' => 'Provence-Alpes-Côte d\'Azur'),
                array('name' => 'Nantes', 'state' => 'Pays de la Loire'),
                array('name' => 'Strasbourg', 'state' => 'Grand Est'),
                array('name' => 'Montpellier', 'state' => 'Occitanie')
            )
        );
        
        return $cities_by_country[$country_code] ?? array();
    }
    
    // === EXISTING AJAX METHODS (MAINTAINED FOR COMPATIBILITY) ===
    
    /**
     * AJAX: Save selected areas
     */
    public function ajax_save_selected_areas() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $user_id = get_current_user_id();
            $selected_areas = $_POST['selected_areas'] ?? array();
            
            if (empty($selected_areas) || !is_array($selected_areas)) {
                wp_send_json_error(__('No areas selected.', 'mobooking'));
                return;
            }
            
            $saved_count = 0;
            
            foreach ($selected_areas as $area_data) {
                $area_data = array_map('sanitize_text_field', $area_data);
                
                if (empty($area_data['area_name']) || empty($area_data['zip_code'])) {
                    continue;
                }
                
                $result = $this->save_area($user_id, $area_data);
                if ($result) {
                    $saved_count++;
                }
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    _n(
                        'Successfully saved %d area.',
                        'Successfully saved %d areas.',
                        $saved_count,
                        'mobooking'
                    ),
                    $saved_count
                ),
                'saved_count' => $saved_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while saving areas.', 'mobooking'));
        }
    }
    
    /**
     * Save area to database
     */
    private function save_area($user_id, $area_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mobooking_service_areas';
        
        return $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'area_name' => $area_data['area_name'],
                'zip_codes' => $area_data['zip_code'],
                'state' => $area_data['state'] ?? '',
                'country' => $area_data['country'] ?? '',
                'latitude' => floatval($area_data['latitude'] ?? 0),
                'longitude' => floatval($area_data['longitude'] ?? 0),
                'active' => 1,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s')
        );
    }
    
    /**
     * Get user's service areas
     */
    public function get_user_areas($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mobooking_service_areas';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY area_name ASC",
            $user_id
        ));
        
        return $results ?? array();
    }
    
    /**
     * AJAX: Set service country
     */
    public function ajax_set_service_country() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $country_code = sanitize_text_field($_POST['country_code'] ?? '');
            $user_id = get_current_user_id();
            
            if (empty($country_code)) {
                wp_send_json_error(__('Country code is required.', 'mobooking'));
                return;
            }
            
            // Validate country code
            $supported_countries = $this->get_supported_countries();
            if (!isset($supported_countries[$country_code])) {
                wp_send_json_error(__('Unsupported country selected.', 'mobooking'));
                return;
            }
            
            update_user_meta($user_id, 'mobooking_service_country', $country_code);
            
            wp_send_json_success(array(
                'message' => __('Service country updated successfully.', 'mobooking'),
                'country_code' => $country_code,
                'country_name' => $supported_countries[$country_code]['name'],
                'is_nordic' => $this->is_nordic_country($country_code),
                'data_source' => $this->is_nordic_country($country_code) ? 'Local JSON' : 'External APIs'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while updating country.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Reset service country
     */
    public function ajax_reset_service_country() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $user_id = get_current_user_id();
            delete_user_meta($user_id, 'mobooking_service_country');
            
            wp_send_json_success(array(
                'message' => __('Service country reset successfully.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while resetting country.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Delete area
     */
    public function ajax_delete_area() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $area_id = intval($_POST['area_id'] ?? 0);
            $user_id = get_current_user_id();
            
            if (!$area_id) {
                wp_send_json_error(__('Invalid area ID.', 'mobooking'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_service_areas';
            
            $result = $wpdb->delete(
                $table_name,
                array('id' => $area_id, 'user_id' => $user_id),
                array('%d', '%d')
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Area deleted successfully.', 'mobooking')
                ));
            } else {
                wp_send_json_error(__('Failed to delete area.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while deleting area.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Toggle area status
     */
    public function ajax_toggle_area_status() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'mobooking'));
                return;
            }
            
            $area_id = intval($_POST['area_id'] ?? 0);
            $user_id = get_current_user_id();
            
            if (!$area_id) {
                wp_send_json_error(__('Invalid area ID.', 'mobooking'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_service_areas';
            
            // Get current status
            $current_status = $wpdb->get_var($wpdb->prepare(
                "SELECT active FROM {$table_name} WHERE id = %d AND user_id = %d",
                $area_id,
                $user_id
            ));
            
            if ($current_status === null) {
                wp_send_json_error(__('Area not found.', 'mobooking'));
                return;
            }
            
            $new_status = $current_status ? 0 : 1;
            
            $result = $wpdb->update(
                $table_name,
                array('active' => $new_status),
                array('id' => $area_id, 'user_id' => $user_id),
                array('%d'),
                array('%d', '%d')
            );
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => $new_status ? 
                        __('Area activated successfully.', 'mobooking') : 
                        __('Area deactivated successfully.', 'mobooking'),
                    'new_status' => $new_status
                ));
            } else {
                wp_send_json_error(__('Failed to update area status.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while updating area status.', 'mobooking'));
        }
    }
    
    /**
     * AJAX: Get areas (for frontend)
     */
    public function ajax_get_areas() {
        try {
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                wp_send_json_error(__('User not authenticated.', 'mobooking'));
                return;
            }
            
            $areas = $this->get_user_areas($user_id);
            
            wp_send_json_success(array(
                'areas' => $areas,
                'total' => count($areas)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('An error occurred while fetching areas.', 'mobooking'));
        }
    }
    
    /**
     * Initialize/upgrade database tables
     */
    public function maybe_upgrade_database() {
        $version = get_option('mobooking_geography_db_version', '0');
        
        if (version_compare($version, '3.0', '<')) {
            $this->create_database_tables();
            update_option('mobooking_geography_db_version', '3.0');
        }
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mobooking_service_areas';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            area_name varchar(255) NOT NULL,
            zip_codes text,
            state varchar(100),
            country varchar(10),
            latitude decimal(10,8),
            longitude decimal(11,8),
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY active (active),
            KEY country (country)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Area list shortcode
     */
    public function area_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_inactive' => false
        ), $atts);
        
        $areas = $this->get_user_areas($atts['user_id']);
        
        if (!$atts['show_inactive']) {
            $areas = array_filter($areas, function($area) {
                return $area->active;
            });
        }
        
        if (empty($areas)) {
            return __('No service areas configured.', 'mobooking');
        }
        
        $output = '<div class="mobooking-area-list">';
        foreach ($areas as $area) {
            $output .= '<div class="area-item">';
            $output .= '<strong>' . esc_html($area->area_name) . '</strong>';
            if (!empty($area->zip_codes)) {
                $output .= '<span class="zip-codes"> (' . esc_html($area->zip_codes) . ')</span>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        
        return $output;
    }
    
    // === PLACEHOLDER METHODS FOR OTHER AJAX HANDLERS ===
    
    public function ajax_save_area_with_zips() {
        wp_send_json_error(__('Method not implemented.', 'mobooking'));
    }
    
    public function ajax_get_area_details() {
        wp_send_json_error(__('Method not implemented.', 'mobooking'));
    }
    
    public function ajax_get_area_zip_codes() {
        wp_send_json_error(__('Method not implemented.', 'mobooking'));
    }
    
    public function ajax_refresh_area_zip_codes() {
        wp_send_json_error(__('Method not implemented.', 'mobooking'));
    }
    
    public function ajax_bulk_area_action() {
        wp_send_json_error(__('Method not implemented.', 'mobooking'));
    }
    
    public function ajax_save_area() {
        wp_send_json_error(__('Method deprecated. Use ajax_save_selected_areas instead.', 'mobooking'));
    }

    
}
?>