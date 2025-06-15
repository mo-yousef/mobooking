<?php
namespace MoBooking\Geography;

/**
 * Enhanced Geography Manager class with External API Integration
 * Fetches real area and ZIP code data from external providers
 */
class Manager {
    
    private $api_providers = array(
        'zippopotam' => 'http://api.zippopotam.us',
        'geonames' => 'http://api.geonames.org',
        'postcode_io' => 'https://api.postcodes.io'
    );
    
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
        
        // NEW: Critical AJAX handlers for real API integration
        add_action('wp_ajax_mobooking_fetch_city_areas', array($this, 'ajax_fetch_city_areas'));
        add_action('wp_ajax_mobooking_save_selected_areas', array($this, 'ajax_save_selected_areas'));
        add_action('wp_ajax_mobooking_get_available_cities', array($this, 'ajax_get_available_cities'));
        
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
            error_log('MoBooking\Geography\Manager: Enhanced constructor with external API integration');
        }
    }
    
    /**
     * AJAX: Fetch areas/neighborhoods for a selected city using external APIs
     */
    public function ajax_fetch_city_areas() {
        try {
            // Check nonce and permissions
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }

            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
                return;
            }
            
            $city_name = sanitize_text_field($_POST['city_name'] ?? '');
            $country_code = sanitize_text_field($_POST['country_code'] ?? '');
            $state = sanitize_text_field($_POST['state'] ?? '');
            
            if (empty($city_name) || empty($country_code)) {
                wp_send_json_error(__('City name and country code are required.', 'mobooking'));
                return;
            }
            
            // Fetch areas from external APIs
            $areas_data = $this->fetch_city_areas_from_api($city_name, $country_code, $state);
            
            if (empty($areas_data)) {
                wp_send_json_error(__('No areas found for this city. Please try a different city or contact support.', 'mobooking'));
                return;
            }
            
            wp_send_json_success(array(
                'areas' => $areas_data,
                'city' => $city_name,
                'country' => $country_code,
                'state' => $state,
                'total_areas' => count($areas_data)
            ));

        } catch (Exception $e) {
            error_log('MoBooking: Exception in ajax_fetch_city_areas: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while fetching city areas.', 'mobooking'),
                'debug' => $e->getMessage()
            ));
        }
    }
    
    /**
     * AJAX: Save selected areas to the database
     */
    public function ajax_save_selected_areas() {
        try {
            // Check nonce and permissions
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
                return;
            }
            
            $user_id = get_current_user_id();
            $areas_data = json_decode(stripslashes($_POST['areas_data'] ?? ''), true);
            $city_name = sanitize_text_field($_POST['city_name'] ?? '');
            $country_code = sanitize_text_field($_POST['country_code'] ?? '');
            $state = sanitize_text_field($_POST['state'] ?? '');
            
            if (empty($areas_data) || !is_array($areas_data)) {
                wp_send_json_error(__('No area data provided.', 'mobooking'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'mobooking_areas';
            
            $saved_count = 0;
            $errors = array();
            $duplicate_count = 0;

            foreach ($areas_data as $area_data) {
                try {
                    // Validate required fields
                    if (empty($area_data['area_name']) || empty($area_data['zip_code'])) {
                        $errors[] = 'Missing area name or ZIP code for one entry';
                        continue;
                    }

                    $area_name = sanitize_text_field($area_data['area_name']);
                    $zip_code = sanitize_text_field($area_data['zip_code']);

                    // Check for duplicates (by area name + ZIP code combination)
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND (
                            (city_name = %s AND JSON_CONTAINS(zip_codes, %s)) OR
                            (zip_code = %s AND city_name = %s)
                        )",
                        $user_id,
                        $area_name,
                        json_encode($zip_code),
                        $zip_code,
                        $area_name
                    ));

                    if ($existing > 0) {
                        $duplicate_count++;
                        continue;
                    }

                    // Prepare area data for insertion
                    $insert_data = array(
                        'user_id' => $user_id,
                        'city_name' => $area_name,
                        'state' => $state,
                        'country' => $country_code,
                        'zip_code' => $zip_code, // Primary ZIP for compatibility
                        'zip_codes' => json_encode(array($zip_code)), // Store as array for future expansion
                        'label' => $area_name, // For backward compatibility
                        'active' => 1,
                        'description' => sprintf(__('Service area in %s', 'mobooking'), $city_name)
                    );

                    // If the area has additional ZIP codes, merge them
                    if (!empty($area_data['additional_zips']) && is_array($area_data['additional_zips'])) {
                        $all_zips = array_merge(array($zip_code), $area_data['additional_zips']);
                        $all_zips = array_unique(array_filter($all_zips)); // Remove duplicates and empty values
                        $insert_data['zip_codes'] = json_encode($all_zips);
                    }

                    $result = $wpdb->insert(
                        $table_name,
                        $insert_data,
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
                    );

                    if ($result !== false) {
                        $saved_count++;
                    } else {
                        $errors[] = 'Failed to save: ' . $area_name . ' (DB Error: ' . $wpdb->last_error . ')';
                    }

                } catch (Exception $e) {
                    $errors[] = 'Error saving ' . ($area_data['area_name'] ?? 'unknown area') . ': ' . $e->getMessage();
                }
            }
            
            // Prepare response
            $total_processed = count($areas_data);
            $skipped_count = $total_processed - $saved_count - count($errors);
            
            if ($saved_count > 0) {
                $message = sprintf(__('Successfully saved %d areas.', 'mobooking'), $saved_count);

                if ($duplicate_count > 0) {
                    $message .= ' ' . sprintf(__('%d duplicates were skipped.', 'mobooking'), $duplicate_count);
                }

                if (!empty($errors)) {
                    $message .= ' ' . sprintf(__('However, %d errors occurred.', 'mobooking'), count($errors));
                }

                wp_send_json_success(array(
                    'message' => $message,
                    'saved_count' => $saved_count,
                    'duplicate_count' => $duplicate_count,
                    'error_count' => count($errors),
                    'errors' => $errors
                ));
            } else {
                $message = __('No new areas were saved.', 'mobooking');
                if ($duplicate_count > 0) {
                    $message .= ' ' . sprintf(__('All %d areas already exist.', 'mobooking'), $duplicate_count);
                }

                wp_send_json_error(array(
                    'message' => $message,
                    'errors' => $errors,
                    'duplicate_count' => $duplicate_count
                ));
            }

        } catch (Exception $e) {
            error_log('MoBooking: Exception in ajax_save_selected_areas: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while saving areas: ', 'mobooking') . $e->getMessage(),
                'debug' => $e->getTraceAsString()
            ));
        }
    }
    
    /**
     * AJAX: Get available cities for a country
     */
    public function ajax_get_available_cities() {
        try {
            // Check nonce and permissions
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
                return;
            }
            
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
                return;
            }
            
            $country_code = sanitize_text_field($_POST['country_code'] ?? '');
            
            if (empty($country_code)) {
                wp_send_json_error(__('Country code is required.', 'mobooking'));
                return;
            }
            
            $cities = $this->get_major_cities_for_country($country_code);
            
            wp_send_json_success(array(
                'cities' => $cities,
                'country' => $country_code,
                'total_cities' => count($cities)
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
     * Fetch areas/neighborhoods for a city using external APIs
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
        $areas = array_slice($areas, 0, 50); // Limit to 50 areas
        
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
            // Zippopotam format: api.zippopotam.us/{country}/{city}
            $url = "http://api.zippopotam.us/{$country_code}/" . urlencode($city_name);
            
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'headers' => array(
                    'User-Agent' => 'MoBooking/1.0'
                )
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
     * Fetch areas from GeoNames API (requires username - free registration)
     */
    private function fetch_from_geonames($city_name, $country_code, $state = '') {
        $areas = array();
        
        try {
            // Get GeoNames username from options (admin should set this)
            $username = get_option('mobooking_geonames_username', 'demo');
            
            // Search for postal codes in the city
            $url = "http://api.geonames.org/postalCodeSearchJSON?" . http_build_query(array(
                'placename' => $city_name,
                'country' => $country_code,
                'maxRows' => 20,
                'username' => $username
            ));
            
            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'headers' => array(
                    'User-Agent' => 'MoBooking/1.0'
                )
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
     * Fetch areas from Postcodes.io (UK specific)
     */
    private function fetch_from_postcode_io($city_name, $country_code, $state = '') {
        $areas = array();

        // Only works for UK
        if ($country_code !== 'GB') {
            return $areas;
        }

        try {
            // Search for postcodes near the city
            $url = "https://api.postcodes.io/places/" . urlencode($city_name);

            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'headers' => array(
                    'User-Agent' => 'MoBooking/1.0'
                )
            ));

            if (is_wp_error($response)) {
                throw new Exception('API request failed: ' . $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!$data || !isset($data['result'])) {
                return $areas;
            }

            // Get nearby postcodes
            if (isset($data['result'][0])) {
                $place = $data['result'][0];
                $lat = $place['latitude'] ?? 0;
                $lon = $place['longitude'] ?? 0;

                if ($lat && $lon) {
                    $nearby_url = "https://api.postcodes.io/postcodes?" . http_build_query(array(
                        'lat' => $lat,
                        'lon' => $lon,
                        'radius' => 5000,
                        'limit' => 20
                    ));

                    $nearby_response = wp_remote_get($nearby_url, array('timeout' => 10));

                    if (!is_wp_error($nearby_response)) {
                        $nearby_body = wp_remote_retrieve_body($nearby_response);
                        $nearby_data = json_decode($nearby_body, true);

                        if ($nearby_data && isset($nearby_data['result'])) {
                            foreach ($nearby_data['result'] as $postcode_data) {
                                $area_name = $postcode_data['ward'] ?? $postcode_data['district'] ?? '';
                                $zip_code = $postcode_data['postcode'] ?? '';

                                if (!empty($area_name) && !empty($zip_code)) {
                                    $areas[] = array(
                                        'area_name' => $area_name,
                                        'zip_code' => $zip_code,
                                        'state' => $postcode_data['region'] ?? '',
                                        'country' => 'GB',
                                        'source' => 'Postcodes.io',
                                        'latitude' => floatval($postcode_data['latitude'] ?? 0),
                                        'longitude' => floatval($postcode_data['longitude'] ?? 0)
                                    );
                                }
                            }
                        }
                    }
                }
            }

        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Postcodes.io API error: ' . $e->getMessage());
            }
        }

        return $areas;
    }

    /**
     * Generate fallback areas when APIs fail
     */
    private function generate_fallback_areas($city_name, $country_code, $state = '') {
        $areas = array();

        // Generate realistic area names based on common patterns
        $area_suffixes = array(
            'US' => array('Downtown', 'North Side', 'South Side', 'East End', 'West End', 'Old Town', 'Midtown', 'Uptown', 'Heights', 'Village'),
            'GB' => array('Centre', 'North', 'South', 'East', 'West', 'Old Town', 'New Town', 'Common', 'Green', 'Park'),
            'CA' => array('Downtown', 'North', 'South', 'East', 'West', 'Centre', 'Heights', 'Park', 'Gardens', 'Square'),
            'DE' => array('Zentrum', 'Nord', 'Süd', 'Ost', 'West', 'Altstadt', 'Neustadt', 'Park', 'Platz', 'Strasse'),
            'FR' => array('Centre', 'Nord', 'Sud', 'Est', 'Ouest', 'Vieux', 'Nouveau', 'Quartier', 'Place', 'Parc')
        );

        $suffixes = $area_suffixes[$country_code] ?? $area_suffixes['US'];

        // Generate mock ZIP codes
        $zip_codes = $this->generate_mock_zip_codes_for_city($city_name, $country_code);

        for ($i = 0; $i < min(count($suffixes), count($zip_codes)); $i++) {
            $area_name = $city_name . ' ' . $suffixes[$i];

            $areas[] = array(
                'area_name' => $area_name,
                'zip_code' => $zip_codes[$i],
                'state' => $state,
                'country' => $country_code,
                'source' => 'Generated',
                'latitude' => 0,
                'longitude' => 0
            );
        }

        return $areas;
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
     * Get major cities for a country
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
                array('name' => 'San Jose', 'state' => 'CA'),
                array('name' => 'Austin', 'state' => 'TX'),
                array('name' => 'Jacksonville', 'state' => 'FL'),
                array('name' => 'Fort Worth', 'state' => 'TX'),
                array('name' => 'Columbus', 'state' => 'OH'),
                array('name' => 'Charlotte', 'state' => 'NC'),
                array('name' => 'San Francisco', 'state' => 'CA'),
                array('name' => 'Indianapolis', 'state' => 'IN'),
                array('name' => 'Seattle', 'state' => 'WA'),
                array('name' => 'Denver', 'state' => 'CO'),
                array('name' => 'Washington', 'state' => 'DC'),
                array('name' => 'Boston', 'state' => 'MA'),
                array('name' => 'El Paso', 'state' => 'TX'),
                array('name' => 'Nashville', 'state' => 'TN'),
                array('name' => 'Detroit', 'state' => 'MI'),
                array('name' => 'Oklahoma City', 'state' => 'OK'),
                array('name' => 'Portland', 'state' => 'OR'),
                array('name' => 'Las Vegas', 'state' => 'NV'),
                array('name' => 'Memphis', 'state' => 'TN'),
                array('name' => 'Louisville', 'state' => 'KY'),
                array('name' => 'Baltimore', 'state' => 'MD'),
                array('name' => 'Milwaukee', 'state' => 'WI'),
                array('name' => 'Albuquerque', 'state' => 'NM'),
                array('name' => 'Tucson', 'state' => 'AZ'),
                array('name' => 'Fresno', 'state' => 'CA'),
                array('name' => 'Sacramento', 'state' => 'CA'),
                array('name' => 'Kansas City', 'state' => 'MO'),
                array('name' => 'Mesa', 'state' => 'AZ'),
                array('name' => 'Atlanta', 'state' => 'GA'),
                array('name' => 'Omaha', 'state' => 'NE'),
                array('name' => 'Colorado Springs', 'state' => 'CO'),
                array('name' => 'Raleigh', 'state' => 'NC'),
                array('name' => 'Miami', 'state' => 'FL'),
                array('name' => 'Virginia Beach', 'state' => 'VA'),
                array('name' => 'Oakland', 'state' => 'CA'),
                array('name' => 'Minneapolis', 'state' => 'MN'),
                array('name' => 'Tulsa', 'state' => 'OK'),
                array('name' => 'Arlington', 'state' => 'TX'),
                array('name' => 'Tampa', 'state' => 'FL'),
                array('name' => 'New Orleans', 'state' => 'LA'),
                array('name' => 'Wichita', 'state' => 'KS')
            ),
            'CA' => array(
                array('name' => 'Toronto', 'state' => 'ON'),
                array('name' => 'Montreal', 'state' => 'QC'),
                array('name' => 'Calgary', 'state' => 'AB'),
                array('name' => 'Ottawa', 'state' => 'ON'),
                array('name' => 'Edmonton', 'state' => 'AB'),
                array('name' => 'Mississauga', 'state' => 'ON'),
                array('name' => 'Winnipeg', 'state' => 'MB'),
                array('name' => 'Vancouver', 'state' => 'BC'),
                array('name' => 'Brampton', 'state' => 'ON'),
                array('name' => 'Hamilton', 'state' => 'ON'),
                array('name' => 'Quebec City', 'state' => 'QC'),
                array('name' => 'Surrey', 'state' => 'BC'),
                array('name' => 'Laval', 'state' => 'QC'),
                array('name' => 'Halifax', 'state' => 'NS'),
                array('name' => 'London', 'state' => 'ON'),
                array('name' => 'Markham', 'state' => 'ON'),
                array('name' => 'Vaughan', 'state' => 'ON'),
                array('name' => 'Gatineau', 'state' => 'QC'),
                array('name' => 'Saskatoon', 'state' => 'SK'),
                array('name' => 'Longueuil', 'state' => 'QC'),
                array('name' => 'Burnaby', 'state' => 'BC'),
                array('name' => 'Regina', 'state' => 'SK'),
                array('name' => 'Richmond', 'state' => 'BC'),
                array('name' => 'Richmond Hill', 'state' => 'ON'),
                array('name' => 'Oakville', 'state' => 'ON'),
                array('name' => 'Burlington', 'state' => 'ON'),
                array('name' => 'Sherbrooke', 'state' => 'QC'),
                array('name' => 'Oshawa', 'state' => 'ON'),
                array('name' => 'Saguenay', 'state' => 'QC'),
                array('name' => 'Levis', 'state' => 'QC')
            ),
            'GB' => array(
                array('name' => 'London', 'state' => 'England'),
                array('name' => 'Birmingham', 'state' => 'England'),
                array('name' => 'Manchester', 'state' => 'England'),
                array('name' => 'Glasgow', 'state' => 'Scotland'),
                array('name' => 'Liverpool', 'state' => 'England'),
                array('name' => 'Leeds', 'state' => 'England'),
                array('name' => 'Sheffield', 'state' => 'England'),
                array('name' => 'Edinburgh', 'state' => 'Scotland'),
                array('name' => 'Bristol', 'state' => 'England'),
                array('name' => 'Cardiff', 'state' => 'Wales'),
                array('name' => 'Belfast', 'state' => 'Northern Ireland'),
                array('name' => 'Leicester', 'state' => 'England'),
                array('name' => 'Coventry', 'state' => 'England'),
                array('name' => 'Bradford', 'state' => 'England'),
                array('name' => 'Nottingham', 'state' => 'England'),
                array('name' => 'Hull', 'state' => 'England'),
                array('name' => 'Newcastle', 'state' => 'England'),
                array('name' => 'Stoke-on-Trent', 'state' => 'England'),
                array('name' => 'Southampton', 'state' => 'England'),
                array('name' => 'Derby', 'state' => 'England'),
                array('name' => 'Portsmouth', 'state' => 'England'),
                array('name' => 'Brighton', 'state' => 'England'),
                array('name' => 'Plymouth', 'state' => 'England'),
                array('name' => 'Northampton', 'state' => 'England'),
                array('name' => 'Reading', 'state' => 'England'),
                array('name' => 'Luton', 'state' => 'England'),
                array('name' => 'Wolverhampton', 'state' => 'England'),
                array('name' => 'Bolton', 'state' => 'England'),
                array('name' => 'Bournemouth', 'state' => 'England'),
                array('name' => 'Norwich', 'state' => 'England')
            ),
            'DE' => array(
                array('name' => 'Berlin', 'state' => 'Berlin'),
                array('name' => 'Hamburg', 'state' => 'Hamburg'),
                array('name' => 'Munich', 'state' => 'Bavaria'),
                array('name' => 'Cologne', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Frankfurt', 'state' => 'Hesse'),
                array('name' => 'Stuttgart', 'state' => 'Baden-Württemberg'),
                array('name' => 'Düsseldorf', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Dortmund', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Essen', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Leipzig', 'state' => 'Saxony'),
                array('name' => 'Bremen', 'state' => 'Bremen'),
                array('name' => 'Dresden', 'state' => 'Saxony'),
                array('name' => 'Hanover', 'state' => 'Lower Saxony'),
                array('name' => 'Nuremberg', 'state' => 'Bavaria'),
                array('name' => 'Duisburg', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Bochum', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Wuppertal', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Bielefeld', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Bonn', 'state' => 'North Rhine-Westphalia'),
                array('name' => 'Münster', 'state' => 'North Rhine-Westphalia')
            ),
            'FR' => array(
                array('name' => 'Paris', 'state' => 'Île-de-France'),
                array('name' => 'Marseille', 'state' => 'Provence-Alpes-Côte d\'Azur'),
                array('name' => 'Lyon', 'state' => 'Auvergne-Rhône-Alpes'),
                array('name' => 'Toulouse', 'state' => 'Occitania'),
                array('name' => 'Nice', 'state' => 'Provence-Alpes-Côte d\'Azur'),
                array('name' => 'Nantes', 'state' => 'Pays de la Loire'),
                array('name' => 'Strasbourg', 'state' => 'Grand Est'),
                array('name' => 'Montpellier', 'state' => 'Occitania'),
                array('name' => 'Bordeaux', 'state' => 'Nouvelle-Aquitaine'),
                array('name' => 'Lille', 'state' => 'Hauts-de-France'),
                array('name' => 'Rennes', 'state' => 'Brittany'),
                array('name' => 'Reims', 'state' => 'Grand Est'),
                array('name' => 'Le Havre', 'state' => 'Normandy'),
                array('name' => 'Saint-Étienne', 'state' => 'Auvergne-Rhône-Alpes'),
                array('name' => 'Toulon', 'state' => 'Provence-Alpes-Côte d\'Azur'),
                array('name' => 'Angers', 'state' => 'Pays de la Loire'),
                array('name' => 'Grenoble', 'state' => 'Auvergne-Rhône-Alpes'),
                array('name' => 'Dijon', 'state' => 'Burgundy-Franche-Comté'),
                array('name' => 'Nîmes', 'state' => 'Occitania'),
                array('name' => 'Aix-en-Provence', 'state' => 'Provence-Alpes-Côte d\'Azur')
            ),
            'ES' => array(
                array('name' => 'Madrid', 'state' => 'Community of Madrid'),
                array('name' => 'Barcelona', 'state' => 'Catalonia'),
                array('name' => 'Valencia', 'state' => 'Valencian Community'),
                array('name' => 'Seville', 'state' => 'Andalusia'),
                array('name' => 'Zaragoza', 'state' => 'Aragon'),
                array('name' => 'Málaga', 'state' => 'Andalusia'),
                array('name' => 'Murcia', 'state' => 'Region of Murcia'),
                array('name' => 'Palma', 'state' => 'Balearic Islands'),
                array('name' => 'Las Palmas', 'state' => 'Canary Islands'),
                array('name' => 'Bilbao', 'state' => 'Basque Country'),
                array('name' => 'Alicante', 'state' => 'Valencian Community'),
                array('name' => 'Córdoba', 'state' => 'Andalusia'),
                array('name' => 'Valladolid', 'state' => 'Castile and León'),
                array('name' => 'Vigo', 'state' => 'Galicia'),
                array('name' => 'Gijón', 'state' => 'Asturias'),
                array('name' => 'Hospitalet', 'state' => 'Catalonia'),
                array('name' => 'La Coruña', 'state' => 'Galicia'),
                array('name' => 'Vitoria-Gasteiz', 'state' => 'Basque Country'),
                array('name' => 'Granada', 'state' => 'Andalusia'),
                array('name' => 'Elche', 'state' => 'Valencian Community')
            ),
            'IT' => array(
                array('name' => 'Rome', 'state' => 'Lazio'),
                array('name' => 'Milan', 'state' => 'Lombardy'),
                array('name' => 'Naples', 'state' => 'Campania'),
                array('name' => 'Turin', 'state' => 'Piedmont'),
                array('name' => 'Palermo', 'state' => 'Sicily'),
                array('name' => 'Genoa', 'state' => 'Liguria'),
                array('name' => 'Bologna', 'state' => 'Emilia-Romagna'),
                array('name' => 'Florence', 'state' => 'Tuscany'),
                array('name' => 'Bari', 'state' => 'Apulia'),
                array('name' => 'Catania', 'state' => 'Sicily'),
                array('name' => 'Venice', 'state' => 'Veneto'),
                array('name' => 'Verona', 'state' => 'Veneto'),
                array('name' => 'Messina', 'state' => 'Sicily'),
                array('name' => 'Padua', 'state' => 'Veneto'),
                array('name' => 'Trieste', 'state' => 'Friuli-Venezia Giulia'),
                array('name' => 'Taranto', 'state' => 'Apulia'),
                array('name' => 'Brescia', 'state' => 'Lombardy'),
                array('name' => 'Parma', 'state' => 'Emilia-Romagna'),
                array('name' => 'Prato', 'state' => 'Tuscany'),
                array('name' => 'Modena', 'state' => 'Emilia-Romagna')
            ),
            'AU' => array(
                array('name' => 'Sydney', 'state' => 'NSW'),
                array('name' => 'Melbourne', 'state' => 'VIC'),
                array('name' => 'Brisbane', 'state' => 'QLD'),
                array('name' => 'Perth', 'state' => 'WA'),
                array('name' => 'Adelaide', 'state' => 'SA'),
                array('name' => 'Gold Coast', 'state' => 'QLD'),
                array('name' => 'Canberra', 'state' => 'ACT'),
                array('name' => 'Newcastle', 'state' => 'NSW'),
                array('name' => 'Wollongong', 'state' => 'NSW'),
                array('name' => 'Logan City', 'state' => 'QLD'),
                array('name' => 'Geelong', 'state' => 'VIC'),
                array('name' => 'Hobart', 'state' => 'TAS'),
                array('name' => 'Townsville', 'state' => 'QLD'),
                array('name' => 'Cairns', 'state' => 'QLD'),
                array('name' => 'Darwin', 'state' => 'NT'),
                array('name' => 'Toowoomba', 'state' => 'QLD'),
                array('name' => 'Ballarat', 'state' => 'VIC'),
                array('name' => 'Bendigo', 'state' => 'VIC'),
                array('name' => 'Albury', 'state' => 'NSW'),
                array('name' => 'Launceston', 'state' => 'TAS')
            ),
            'SE' => array(
                array('name' => 'Stockholm', 'state' => 'Stockholm County'),
                array('name' => 'Gothenburg', 'state' => 'Västra Götaland County'),
                array('name' => 'Malmö', 'state' => 'Skåne County'),
                array('name' => 'Uppsala', 'state' => 'Uppsala County'),
                array('name' => 'Västerås', 'state' => 'Västmanland County'),
                array('name' => 'Örebro', 'state' => 'Örebro County'),
                array('name' => 'Linköping', 'state' => 'Östergötland County'),
                array('name' => 'Helsingborg', 'state' => 'Skåne County'),
                array('name' => 'Jönköping', 'state' => 'Jönköping County'),
                array('name' => 'Norrköping', 'state' => 'Östergötland County'),
                array('name' => 'Lund', 'state' => 'Skåne County'),
                array('name' => 'Umeå', 'state' => 'Västerbotten County'),
                array('name' => 'Gävle', 'state' => 'Gävleborg County'),
                array('name' => 'Borås', 'state' => 'Västra Götaland County'),
                array('name' => 'Eskilstuna', 'state' => 'Södermanland County')
            ),
            'CY' => array(
                array('name' => 'Nicosia', 'state' => 'Nicosia District'),
                array('name' => 'Limassol', 'state' => 'Limassol District'),
                array('name' => 'Larnaca', 'state' => 'Larnaca District'),
                array('name' => 'Paphos', 'state' => 'Paphos District'),
                array('name' => 'Famagusta', 'state' => 'Famagusta District'),
                array('name' => 'Kyrenia', 'state' => 'Kyrenia District'),
                array('name' => 'Protaras', 'state' => 'Famagusta District'),
                array('name' => 'Ayia Napa', 'state' => 'Famagusta District'),
                array('name' => 'Polis', 'state' => 'Paphos District'),
                array('name' => 'Paralimni', 'state' => 'Famagusta District')
            )
        );
        
        return $cities_by_country[$country_code] ?? array();
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
     * Generate mock ZIP codes for fallback
     */
    private function generate_mock_zip_codes_for_city($city_name, $country_code) {
        $mock_patterns = array(
            'US' => function($city) {
                $hash = $this->simple_hash($city);
                $base = 10000 + ($hash % 80000);
                return array(
                    str_pad($base, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 1, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 2, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 3, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 4, 5, '0', STR_PAD_LEFT)
                );
            },
            'GB' => function($city) {
                $areas = array('SW', 'NW', 'SE', 'NE', 'W', 'E');
                $hash = $this->simple_hash($city);
                $area = $areas[$hash % count($areas)];
                $district = ($hash % 20) + 1;
                return array(
                    $area . $district . ' 1AA',
                    $area . $district . ' 2BB',
                    $area . $district . ' 3CC',
                    $area . $district . ' 4DD',
                    $area . $district . ' 5EE'
                );
            },
            'CA' => function($city) {
                $provinces = array('K', 'M', 'V', 'T', 'H');
                $hash = $this->simple_hash($city);
                $province = $provinces[$hash % count($provinces)];
                $district = ($hash % 9) + 1;
                return array(
                    $province . $district . 'A 1B2',
                    $province . $district . 'B 2C3',
                    $province . $district . 'C 3D4',
                    $province . $district . 'D 4E5',
                    $province . $district . 'E 5F6'
                );
            },
            'DE' => function($city) {
                $hash = $this->simple_hash($city);
                $base = 10000 + ($hash % 80000);
                return array(
                    str_pad($base, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 10, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 20, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 30, 5, '0', STR_PAD_LEFT),
                    str_pad($base + 40, 5, '0', STR_PAD_LEFT)
                );
            }
        );
        
        if (isset($mock_patterns[$country_code])) {
            return $mock_patterns[$country_code]($city_name);
        }
        
        // Default fallback
        $hash = $this->simple_hash($city_name);
        $base = 10000 + ($hash % 80000);
        return array(
            str_pad($base, 5, '0', STR_PAD_LEFT),
            str_pad($base + 10, 5, '0', STR_PAD_LEFT),
            str_pad($base + 20, 5, '0', STR_PAD_LEFT)
        );
    }
    
    /**
     * Simple hash function for consistent mock data
     */
    private function simple_hash($string) {
        $hash = 0;
        for ($i = 0; $i < strlen($string); $i++) {
            $hash = (($hash << 5) - $hash) + ord($string[$i]);
            $hash = $hash & 0xFFFFFFFF;
        }
        return abs($hash);
    }
    
    // Include all the existing methods from the previous version...
    // [Rest of the existing methods remain the same]

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

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY city_name ASC, label ASC",
            $user_id
        ));

        // Process results to ensure backward compatibility
        foreach ($results as $result) {
            if (empty($result->zip_codes) && !empty($result->zip_code)) {
                $result->zip_codes = json_encode(array($result->zip_code));
            }
            
            if (empty($result->city_name) && !empty($result->label)) {
                $result->city_name = $result->label;
            }
        }

        return $results;
    }
    
    /**
     * Enhanced ZIP coverage check
     */
    public function is_zip_covered($zip_code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';

        if (empty($zip_code) || empty($user_id)) {
            return false;
        }

        $zip_code = sanitize_text_field(trim($zip_code));
        $user_id = absint($user_id);

        if (empty($zip_code) || $user_id <= 0) {
            return false;
        }

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            return false;
        }

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

        return $area ? $area : false;
    }
    
    // Include remaining AJAX handlers and methods...
    // [All existing AJAX methods remain the same]

    /**
     * AJAX: Set service country
     */
    public function ajax_set_service_country() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $user_id = get_current_user_id();
            $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
            
            if (empty($country)) {
                wp_send_json_error(__('Country is required.', 'mobooking'));
            }
            
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
    
    // [Include all other existing methods...]
}
