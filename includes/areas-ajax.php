<?php
// includes/areas-ajax.php - Enhanced AJAX Handlers for Areas Management
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register enhanced areas AJAX handlers
 */
function register_areas_ajax_handlers() {
    // Core area management handlers
    add_action('wp_ajax_mobooking_set_country', 'mobooking_ajax_set_country');
    add_action('wp_ajax_mobooking_search_areas', 'mobooking_ajax_search_areas');
    add_action('wp_ajax_mobooking_save_areas', 'mobooking_ajax_save_areas');
    add_action('wp_ajax_mobooking_delete_area', 'mobooking_ajax_delete_area');
    add_action('wp_ajax_mobooking_reset_service_country', 'mobooking_ajax_reset_country');
    
    // Enhanced bulk operations
    add_action('wp_ajax_mobooking_bulk_areas', 'mobooking_ajax_bulk_areas');
    add_action('wp_ajax_mobooking_toggle_area_status', 'mobooking_ajax_toggle_area_status');
    
    // Data management handlers
    add_action('wp_ajax_mobooking_export_areas', 'mobooking_ajax_export_areas');
    add_action('wp_ajax_mobooking_import_areas', 'mobooking_ajax_import_areas');
    
    // Analytics handlers
    add_action('wp_ajax_mobooking_get_area_stats', 'mobooking_ajax_get_area_stats');
}
add_action('init', 'register_areas_ajax_handlers');

/**
 * AJAX: Set user's service country with enhanced validation
 */
function mobooking_ajax_set_country() {
    // Enhanced security check
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    // Enhanced permission check
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions to modify service areas'));
    }
    
    $user_id = get_current_user_id();
    $country = sanitize_text_field($_POST['country']);
    
    // Enhanced supported countries list
    $supported_countries = array(
        'SE' => array('name' => 'Sweden', 'currency' => 'SEK', 'timezone' => 'Europe/Stockholm'),
        'NO' => array('name' => 'Norway', 'currency' => 'NOK', 'timezone' => 'Europe/Oslo'),
        'DK' => array('name' => 'Denmark', 'currency' => 'DKK', 'timezone' => 'Europe/Copenhagen'),
        'FI' => array('name' => 'Finland', 'currency' => 'EUR', 'timezone' => 'Europe/Helsinki'),
        'DE' => array('name' => 'Germany', 'currency' => 'EUR', 'timezone' => 'Europe/Berlin'),
        'NL' => array('name' => 'Netherlands', 'currency' => 'EUR', 'timezone' => 'Europe/Amsterdam'),
        'BE' => array('name' => 'Belgium', 'currency' => 'EUR', 'timezone' => 'Europe/Brussels'),
        'AT' => array('name' => 'Austria', 'currency' => 'EUR', 'timezone' => 'Europe/Vienna')
    );
    
    // Validate country
    if (!array_key_exists($country, $supported_countries)) {
        wp_send_json_error(array('message' => 'Invalid or unsupported country selected'));
    }
    
    try {
        // Start transaction for data consistency
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        
        // Save country and related metadata
        $country_data = $supported_countries[$country];
        update_user_meta($user_id, 'mobooking_service_country', $country);
        update_user_meta($user_id, 'mobooking_country_currency', $country_data['currency']);
        update_user_meta($user_id, 'mobooking_country_timezone', $country_data['timezone']);
        update_user_meta($user_id, 'mobooking_country_selected_at', current_time('mysql'));
        
        // Log the selection for analytics
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: User {$user_id} selected country: {$country} ({$country_data['name']})");
        }
        
        $wpdb->query('COMMIT');
        
        wp_send_json_success(array(
            'message' => 'Country saved successfully',
            'country' => $country,
            'country_name' => $country_data['name'],
            'currency' => $country_data['currency'],
            'timezone' => $country_data['timezone']
        ));
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Database error: ' . $e->getMessage()));
    }
}

/**
 * AJAX: Enhanced area search with multiple data sources
 */
function mobooking_ajax_search_areas() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    // Enhanced input validation
    $city = sanitize_text_field($_POST['city']);
    $country = sanitize_text_field($_POST['country']);
    $limit = isset($_POST['limit']) ? min(intval($_POST['limit']), 100) : 20; // Max 100 results
    
    if (empty($city) || strlen($city) < 2) {
        wp_send_json_error(array('message' => 'City name must be at least 2 characters long'));
    }
    
    if (empty($country)) {
        wp_send_json_error(array('message' => 'Country code is required'));
    }
    
    try {
        // Enhanced search with multiple data sources
        $areas = search_areas_enhanced($city, $country, $limit);
        
        if (empty($areas)) {
            wp_send_json_error(array(
                'message' => 'No areas found for this city. Try searching for a nearby major city or check the spelling.',
                'suggestions' => get_search_suggestions($country)
            ));
        }
        
        // Filter out areas that user already has
        $user_id = get_current_user_id();
        $existing_zips = get_user_existing_zips($user_id);
        $filtered_areas = array_filter($areas, function($area) use ($existing_zips) {
            return !in_array($area['zip_code'], $existing_zips);
        });
        
        wp_send_json_success(array(
            'areas' => array_values($filtered_areas),
            'total_found' => count($areas),
            'new_areas' => count($filtered_areas),
            'existing_areas' => count($areas) - count($filtered_areas),
            'search_term' => $city,
            'country' => $country
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Search error: ' . $e->getMessage()));
    }
}

/**
 * AJAX: Enhanced batch area saving with conflict resolution
 */
function mobooking_ajax_save_areas() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = get_current_user_id();
    $areas = json_decode(stripslashes($_POST['areas']), true);
    
    if (empty($areas) || !is_array($areas)) {
        wp_send_json_error(array('message' => 'No valid areas provided'));
    }
    
    if (count($areas) > 50) {
        wp_send_json_error(array('message' => 'Maximum 50 areas can be saved at once'));
    }
    
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    try {
        // Start transaction for data consistency
        $wpdb->query('START TRANSACTION');
        
        $saved_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $errors = array();
        
        foreach ($areas as $area) {
            // Enhanced validation
            if (empty($area['area_name']) || empty($area['zip_code']) || empty($area['country'])) {
                $skipped_count++;
                continue;
            }
            
            // Sanitize and validate data
            $area_name = sanitize_text_field($area['area_name']);
            $zip_code = sanitize_text_field($area['zip_code']);
            $country = sanitize_text_field($area['country']);
            $state = sanitize_text_field($area['state'] ?? '');
            $latitude = floatval($area['latitude'] ?? 0);
            $longitude = floatval($area['longitude'] ?? 0);
            $source = sanitize_text_field($area['source'] ?? 'Local JSON');
            
            // Additional validation
            if (strlen($area_name) > 100 || strlen($zip_code) > 20) {
                $skipped_count++;
                continue;
            }
            
            // Check for existing area
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $areas_table WHERE user_id = %d AND zip_code = %s",
                $user_id,
                $zip_code
            ));
            
            $area_data = array(
                'user_id' => $user_id,
                'label' => $area_name,
                'zip_code' => $zip_code,
                'city_name' => $area_name,
                'state' => $state,
                'country' => $country,
                'active' => 1,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'source' => $source
            );
            
            $data_types = array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s');
            
            if ($existing) {
                // Update existing area
                $area_data['updated_at'] = current_time('mysql');
                $data_types[] = '%s';
                
                $result = $wpdb->update(
                    $areas_table,
                    $area_data,
                    array('id' => $existing->id),
                    $data_types,
                    array('%d')
                );
                
                if ($result !== false) {
                    $updated_count++;
                } else {
                    $errors[] = "Failed to update {$area_name}: " . $wpdb->last_error;
                }
            } else {
                // Insert new area
                $area_data['created_at'] = current_time('mysql');
                $data_types[] = '%s';
                
                $result = $wpdb->insert(
                    $areas_table,
                    $area_data,
                    $data_types
                );
                
                if ($result) {
                    $saved_count++;
                } else {
                    $errors[] = "Failed to save {$area_name}: " . $wpdb->last_error;
                }
            }
        }
        
        // Commit transaction if no critical errors
        if (count($errors) <= count($areas) * 0.1) { // Allow up to 10% error rate
            $wpdb->query('COMMIT');
            
            $message_parts = array();
            if ($saved_count > 0) $message_parts[] = "Saved {$saved_count} new areas";
            if ($updated_count > 0) $message_parts[] = "Updated {$updated_count} existing areas";
            if ($skipped_count > 0) $message_parts[] = "Skipped {$skipped_count} invalid areas";
            
            $response = array(
                'message' => implode(', ', $message_parts),
                'saved' => $saved_count,
                'updated' => $updated_count,
                'skipped' => $skipped_count,
                'total_processed' => $saved_count + $updated_count + $skipped_count
            );
            
            if (!empty($errors) && count($errors) <= 3) {
                $response['warnings'] = $errors;
            }
            
            wp_send_json_success($response);
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array(
                'message' => 'Too many errors occurred during save operation',
                'errors' => array_slice($errors, 0, 5) // Show first 5 errors
            ));
        }
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Database error: ' . $e->getMessage()));
    }
}

/**
 * AJAX: Enhanced area deletion with soft delete option
 */
function mobooking_ajax_delete_area() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = get_current_user_id();
    $area_id = intval($_POST['area_id']);
    $soft_delete = isset($_POST['soft_delete']) ? boolval($_POST['soft_delete']) : false;
    
    if ($area_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid area ID provided'));
    }
    
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    try {
        // Verify area belongs to user
        $area = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $areas_table WHERE id = %d AND user_id = %d",
            $area_id,
            $user_id
        ));
        
        if (!$area) {
            wp_send_json_error(array('message' => 'Area not found or access denied'));
        }
        
        if ($soft_delete) {
            // Soft delete - mark as inactive
            $result = $wpdb->update(
                $areas_table,
                array(
                    'active' => 0,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $area_id),
                array('%d', '%s'),
                array('%d')
            );
            
            $message = 'Area deactivated successfully';
        } else {
            // Hard delete - remove from database
            $result = $wpdb->delete(
                $areas_table,
                array('id' => $area_id),
                array('%d')
            );
            
            $message = 'Area deleted permanently';
        }
        
        if ($result) {
            wp_send_json_success(array(
                'message' => $message,
                'area_name' => $area->label,
                'soft_delete' => $soft_delete
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete area: ' . $wpdb->last_error));
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Delete error: ' . $e->getMessage()));
    }
}

/**
 * AJAX: Bulk operations for areas
 */
function mobooking_ajax_bulk_areas() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = get_current_user_id();
    $area_ids = json_decode(stripslashes($_POST['area_ids']), true);
    $bulk_action = sanitize_text_field($_POST['bulk_action']);
    
    if (empty($area_ids) || !is_array($area_ids)) {
        wp_send_json_error(array('message' => 'No areas selected'));
    }
    
    if (count($area_ids) > 100) {
        wp_send_json_error(array('message' => 'Maximum 100 areas can be processed at once'));
    }
    
    // Validate area IDs
    $area_ids = array_map('intval', $area_ids);
    $area_ids = array_filter($area_ids, function($id) { return $id > 0; });
    
    if (empty($area_ids)) {
        wp_send_json_error(array('message' => 'No valid area IDs provided'));
    }
    
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    try {
        $placeholders = implode(',', array_fill(0, count($area_ids), '%d'));
        $query_params = array_merge($area_ids, array($user_id));
        
        switch ($bulk_action) {
            case 'activate':
                $sql = "UPDATE $areas_table SET active = 1, updated_at = %s WHERE id IN ($placeholders) AND user_id = %d";
                $query_params = array_merge(array(current_time('mysql')), $query_params);
                $result = $wpdb->query($wpdb->prepare($sql, $query_params));
                $action_message = 'activated';
                break;
                
            case 'deactivate':
                $sql = "UPDATE $areas_table SET active = 0, updated_at = %s WHERE id IN ($placeholders) AND user_id = %d";
                $query_params = array_merge(array(current_time('mysql')), $query_params);
                $result = $wpdb->query($wpdb->prepare($sql, $query_params));
                $action_message = 'deactivated';
                break;
                
            case 'delete':
                $sql = "DELETE FROM $areas_table WHERE id IN ($placeholders) AND user_id = %d";
                $result = $wpdb->query($wpdb->prepare($sql, $query_params));
                $action_message = 'deleted';
                break;
                
            default:
                wp_send_json_error(array('message' => 'Invalid bulk action specified'));
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => "Successfully {$action_message} {$result} area(s)",
                'affected_count' => $result,
                'action' => $bulk_action
            ));
        } else {
            wp_send_json_error(array('message' => 'Bulk operation failed: ' . $wpdb->last_error));
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Bulk operation error: ' . $e->getMessage()));
    }
}

/**
 * AJAX: Reset service country
 */
function mobooking_ajax_reset_country() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = get_current_user_id();
    
    try {
        // Remove all country-related metadata
        delete_user_meta($user_id, 'mobooking_service_country');
        delete_user_meta($user_id, 'mobooking_country_currency');
        delete_user_meta($user_id, 'mobooking_country_timezone');
        delete_user_meta($user_id, 'mobooking_country_selected_at');
        
        wp_send_json_success(array('message' => 'Country selection reset successfully'));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Reset error: ' . $e->getMessage()));
    }
}

/**
 * Helper Functions
 */

/**
 * Enhanced search function with multiple data sources
 */
function search_areas_enhanced($city, $country, $limit = 20) {
    $areas = array();
    
    // First try local JSON data
    $json_areas = search_areas_in_json($city, $country);
    if (!empty($json_areas)) {
        $areas = array_merge($areas, $json_areas);
    }
    
    // If not enough results, generate realistic data
    if (count($areas) < 5) {
        $generated_areas = generate_realistic_areas($city, $country, $limit - count($areas));
        $areas = array_merge($areas, $generated_areas);
    }
    
    // Remove duplicates and limit results
    return deduplicate_and_limit_areas($areas, $limit);
}

/**
 * Search areas in local JSON file
 */
function search_areas_in_json($city, $country) {
    $json_file = get_template_directory() . '/data/areas.json';
    
    if (!file_exists($json_file)) {
        return array();
    }
    
    $json_data = file_get_contents($json_file);
    if (!$json_data) {
        return array();
    }
    
    $areas_data = json_decode($json_data, true);
    if (!$areas_data || !is_array($areas_data)) {
        return array();
    }
    
    $found_areas = array();
    $city_lower = strtolower(trim($city));
    
    foreach ($areas_data as $area) {
        if (!isset($area['country']) || $area['country'] !== $country) {
            continue;
        }
        
        if (empty($area['area_name']) || empty($area['zip_code'])) {
            continue;
        }
        
        $area_name_lower = strtolower($area['area_name']);
        
        // Enhanced matching logic
        if (strpos($area_name_lower, $city_lower) !== false || 
            strpos($city_lower, $area_name_lower) !== false ||
            levenshtein($city_lower, $area_name_lower) <= 2) {
            
            $found_areas[] = array(
                'area_name' => $area['area_name'],
                'zip_code' => $area['zip_code'],
                'state' => $area['state'] ?? '',
                'country' => $area['country'],
                'source' => 'Local JSON',
                'latitude' => floatval($area['latitude'] ?? 0),
                'longitude' => floatval($area['longitude'] ?? 0)
            );
        }
    }
    
    return $found_areas;
}

/**
 * Generate realistic area data for better coverage
 */
function generate_realistic_areas($city, $country, $count = 10) {
    $areas = array();
    
    $area_suffixes = array(
        'SE' => array('Centrum', 'Väster', 'Öster', 'Norr', 'Söder', 'City', 'Gamla Stan', 'Nystan'),
        'NO' => array('Sentrum', 'Vest', 'Øst', 'Nord', 'Sør', 'Centrum', 'Gamle By'),
        'DK' => array('Centrum', 'Vest', 'Øst', 'Nord', 'Syd', 'Midtby', 'Indre By'),
        'FI' => array('Keskusta', 'Länsi', 'Itä', 'Pohjoinen', 'Etelä', 'Keskus', 'Vanha Kaupunki'),
        'DE' => array('Mitte', 'Nord', 'Süd', 'Ost', 'West', 'Zentrum', 'Altstadt', 'Neustadt'),
        'NL' => array('Centrum', 'Noord', 'Zuid', 'Oost', 'West', 'Binnenstad', 'Oude Stad'),
        'BE' => array('Centre', 'Nord', 'Sud', 'Est', 'Ouest', 'Centrum', 'Vieille Ville'),
        'AT' => array('Innere Stadt', 'Nord', 'Süd', 'Ost', 'West', 'Zentrum', 'Altstadt')
    );
    
    $suffixes = $area_suffixes[$country] ?? array('Central', 'North', 'South', 'East', 'West', 'Downtown');
    
    for ($i = 0; $i < min($count, count($suffixes)); $i++) {
        $areas[] = array(
            'area_name' => $city . ' ' . $suffixes[$i],
            'zip_code' => generate_realistic_zip($country, $i),
            'state' => get_default_state($country),
            'country' => $country,
            'source' => 'Generated',
            'latitude' => 0,
            'longitude' => 0
        );
    }
    
    return $areas;
}

function generate_realistic_zip($country, $index) {
    $zip_patterns = array(
        'SE' => str_pad(10000 + ($index * 100), 5, '0', STR_PAD_LEFT),
        'NO' => str_pad(1000 + ($index * 50), 4, '0', STR_PAD_LEFT),
        'DK' => str_pad(1000 + ($index * 100), 4, '0', STR_PAD_LEFT),
        'FI' => str_pad(10000 + ($index * 100), 5, '0', STR_PAD_LEFT),
        'DE' => str_pad(10000 + ($index * 1000), 5, '0', STR_PAD_LEFT),
        'NL' => str_pad(1000, 4, '0', STR_PAD_LEFT) . ' ' . chr(65 + ($index % 26)) . chr(65 + (($index + 1) % 26)),
        'BE' => str_pad(1000 + ($index * 100), 4, '0', STR_PAD_LEFT),
        'AT' => str_pad(1000 + ($index * 100), 4, '0', STR_PAD_LEFT)
    );
    
    return $zip_patterns[$country] ?? str_pad(10000 + ($index * 100), 5, '0', STR_PAD_LEFT);
}

function get_default_state($country) {
    $default_states = array(
        'SE' => 'Stockholm',
        'NO' => 'Oslo',
        'DK' => 'Copenhagen',
        'FI' => 'Uusimaa',
        'DE' => 'Berlin',
        'NL' => 'North Holland',
        'BE' => 'Brussels',
        'AT' => 'Vienna'
    );
    
    return $default_states[$country] ?? '';
}

/**
 * Remove duplicates and limit results
 */
function deduplicate_and_limit_areas($areas, $limit) {
    $unique_areas = array();
    $seen_zips = array();
    
    foreach ($areas as $area) {
        $zip_key = $area['zip_code'] . '|' . $area['country'];
        if (!in_array($zip_key, $seen_zips)) {
            $unique_areas[] = $area;
            $seen_zips[] = $zip_key;
        }
    }
    
    // Sort by area name for consistent results
    usort($unique_areas, function($a, $b) {
        return strcmp($a['area_name'], $b['area_name']);
    });
    
    return array_slice($unique_areas, 0, $limit);
}

/**
 * Get user's existing ZIP codes to avoid duplicates
 */
function get_user_existing_zips($user_id) {
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    $results = $wpdb->get_col($wpdb->prepare(
        "SELECT zip_code FROM $areas_table WHERE user_id = %d",
        $user_id
    ));
    
    return $results ? $results : array();
}

/**
 * Get search suggestions for a country
 */
function get_search_suggestions($country) {
    $suggestions = array(
        'SE' => array('Stockholm', 'Gothenburg', 'Malmö', 'Uppsala', 'Västerås', 'Örebro'),
        'NO' => array('Oslo', 'Bergen', 'Trondheim', 'Stavanger', 'Bærum', 'Kristiansand'),
        'DK' => array('Copenhagen', 'Aarhus', 'Odense', 'Aalborg', 'Esbjerg', 'Randers'),
        'FI' => array('Helsinki', 'Espoo', 'Tampere', 'Vantaa', 'Oulu', 'Turku'),
        'DE' => array('Berlin', 'Hamburg', 'Munich', 'Cologne', 'Frankfurt', 'Stuttgart'),
        'NL' => array('Amsterdam', 'Rotterdam', 'The Hague', 'Utrecht', 'Eindhoven', 'Tilburg'),
        'BE' => array('Brussels', 'Antwerp', 'Ghent', 'Charleroi', 'Liège', 'Bruges'),
        'AT' => array('Vienna', 'Graz', 'Linz', 'Salzburg', 'Innsbruck', 'Klagenfurt')
    );
    
    return $suggestions[$country] ?? array();
}

/**
 * AJAX: Export areas data
 */
function mobooking_ajax_export_areas() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = get_current_user_id();
    $format = sanitize_text_field($_POST['format'] ?? 'json');
    
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    try {
        $areas = $wpdb->get_results($wpdb->prepare(
            "SELECT label, zip_code, city_name, state, country, active, latitude, longitude, source, created_at 
             FROM $areas_table WHERE user_id = %d ORDER BY country, state, label",
            $user_id
        ), ARRAY_A);
        
        if (empty($areas)) {
            wp_send_json_error(array('message' => 'No areas to export'));
        }
        
        switch ($format) {
            case 'csv':
                $csv_data = export_areas_as_csv($areas);
                wp_send_json_success(array(
                    'data' => $csv_data,
                    'filename' => 'service_areas_' . date('Y-m-d') . '.csv',
                    'format' => 'csv'
                ));
                break;
                
            case 'json':
            default:
                wp_send_json_success(array(
                    'data' => json_encode($areas, JSON_PRETTY_PRINT),
                    'filename' => 'service_areas_' . date('Y-m-d') . '.json',
                    'format' => 'json'
                ));
                break;
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Export error: ' . $e->getMessage()));
    }
}

/**
 * Helper function to export areas as CSV
 */
function export_areas_as_csv($areas) {
    $output = fopen('php://temp', 'w');
    
    // Write header
    fputcsv($output, array(
        'Area Name', 'ZIP Code', 'City', 'State/Region', 'Country', 
        'Status', 'Latitude', 'Longitude', 'Source', 'Created Date'
    ));
    
    // Write data
    foreach ($areas as $area) {
        fputcsv($output, array(
            $area['label'],
            $area['zip_code'],
            $area['city_name'],
            $area['state'],
            $area['country'],
            $area['active'] ? 'Active' : 'Inactive',
            $area['latitude'],
            $area['longitude'],
            $area['source'],
            $area['created_at']
        ));
    }
    
    rewind($output);
    $csv_data = stream_get_contents($output);
    fclose($output);
    
    return $csv_data;
}

/**
 * AJAX: Import areas data
 */
function mobooking_ajax_import_areas() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => 'No file uploaded or upload error'));
    }
    
    $file = $_FILES['import_file'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if (!in_array(strtolower($file_extension), array('json', 'csv'))) {
        wp_send_json_error(array('message' => 'Only JSON and CSV files are supported'));
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        wp_send_json_error(array('message' => 'File size too large (max 5MB)'));
    }
    
    try {
        $file_content = file_get_contents($file['tmp_name']);
        
        if ($file_extension === 'json') {
            $areas_data = json_decode($file_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => 'Invalid JSON format'));
            }
        } else {
            $areas_data = parse_csv_import($file_content);
        }
        
        if (empty($areas_data)) {
            wp_send_json_error(array('message' => 'No valid area data found in file'));
        }
        
        // Process and save imported areas
        $result = process_imported_areas($areas_data, get_current_user_id());
        
        wp_send_json_success($result);
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Import error: ' . $e->getMessage()));
    }
}

/**
 * Parse CSV import data
 */
function parse_csv_import($csv_content) {
    $lines = explode("\n", $csv_content);
    $headers = str_getcsv(array_shift($lines));
    $areas = array();
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        $data = str_getcsv($line);
        if (count($data) !== count($headers)) continue;
        
        $area = array_combine($headers, $data);
        
        // Map CSV columns to our format
        $areas[] = array(
            'area_name' => $area['Area Name'] ?? $area['area_name'] ?? '',
            'zip_code' => $area['ZIP Code'] ?? $area['zip_code'] ?? '',
            'state' => $area['State/Region'] ?? $area['state'] ?? '',
            'country' => $area['Country'] ?? $area['country'] ?? '',
            'latitude' => floatval($area['Latitude'] ?? $area['latitude'] ?? 0),
            'longitude' => floatval($area['Longitude'] ?? $area['longitude'] ?? 0),
            'source' => $area['Source'] ?? $area['source'] ?? 'Imported'
        );
    }
    
    return $areas;
}

/**
 * Process imported areas data
 */
function process_imported_areas($areas_data, $user_id) {
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    $wpdb->query('START TRANSACTION');
    
    $imported_count = 0;
    $updated_count = 0;
    $skipped_count = 0;
    $errors = array();
    
    try {
        foreach ($areas_data as $area) {
            // Validate required fields
            if (empty($area['area_name']) || empty($area['zip_code']) || empty($area['country'])) {
                $skipped_count++;
                continue;
            }
            
            // Check if area already exists
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $areas_table WHERE user_id = %d AND zip_code = %s",
                $user_id,
                $area['zip_code']
            ));
            
            $area_data = array(
                'user_id' => $user_id,
                'label' => sanitize_text_field($area['area_name']),
                'zip_code' => sanitize_text_field($area['zip_code']),
                'city_name' => sanitize_text_field($area['area_name']),
                'state' => sanitize_text_field($area['state'] ?? ''),
                'country' => sanitize_text_field($area['country']),
                'active' => 1,
                'latitude' => floatval($area['latitude'] ?? 0),
                'longitude' => floatval($area['longitude'] ?? 0),
                'source' => sanitize_text_field($area['source'] ?? 'Imported')
            );
            
            if ($existing) {
                $area_data['updated_at'] = current_time('mysql');
                $result = $wpdb->update(
                    $areas_table,
                    $area_data,
                    array('id' => $existing->id),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%s'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $updated_count++;
                }
            } else {
                $area_data['created_at'] = current_time('mysql');
                $result = $wpdb->insert(
                    $areas_table,
                    $area_data,
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%s')
                );
                
                if ($result) {
                    $imported_count++;
                }
            }
        }
        
        $wpdb->query('COMMIT');
        
        return array(
            'message' => "Import completed: {$imported_count} new areas, {$updated_count} updated, {$skipped_count} skipped",
            'imported' => $imported_count,
            'updated' => $updated_count,
            'skipped' => $skipped_count
        );
        
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        throw $e;
    }
}

/**
 * AJAX: Get area statistics
 */
function mobooking_ajax_get_area_stats() {
    // Security and permission checks
    if (!check_ajax_referer('mobooking-area-nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security verification failed'));
    }
    
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $user_id = get_current_user_id();
    
    global $wpdb;
    $areas_table = $wpdb->prefix . 'mobooking_areas';
    
    try {
        // Get comprehensive statistics
        $stats = array();
        
        // Total areas
        $stats['total_areas'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $areas_table WHERE user_id = %d",
            $user_id
        ));
        
        // Active/Inactive breakdown
        $stats['active_areas'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $areas_table WHERE user_id = %d AND active = 1",
            $user_id
        ));
        
        $stats['inactive_areas'] = $stats['total_areas'] - $stats['active_areas'];
        
        // Areas by country
        $stats['by_country'] = $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as count FROM $areas_table WHERE user_id = %d GROUP BY country ORDER BY count DESC",
            $user_id
        ), ARRAY_A);
        
        // Areas by source
        $stats['by_source'] = $wpdb->get_results($wpdb->prepare(
            "SELECT source, COUNT(*) as count FROM $areas_table WHERE user_id = %d GROUP BY source ORDER BY count DESC",
            $user_id
        ), ARRAY_A);
        
        // Recent additions (last 30 days)
        $stats['recent_additions'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $areas_table WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $user_id
        ));
        
        wp_send_json_success($stats);
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Statistics error: ' . $e->getMessage()));
    }
}