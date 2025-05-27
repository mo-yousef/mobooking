<?php
namespace MoBooking\Geography;

/**
 * Geography Manager class - Enhanced with Area Name Support
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_area', array($this, 'ajax_save_area'));
        add_action('wp_ajax_mobooking_delete_area', array($this, 'ajax_delete_area'));
        add_action('wp_ajax_mobooking_get_areas', array($this, 'ajax_get_areas'));
        
        // Add shortcodes
        add_shortcode('mobooking_area_list', array($this, 'area_list_shortcode'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking\Geography\Manager: Constructor called with area name support');
        }
    }
    
    /**
     * Get areas for a user
     */
    public function get_user_areas($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Getting areas for user {$user_id} from table {$table_name}");
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY zip_code ASC",
            $user_id
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Found " . count($results) . " areas for user {$user_id}");
        }
        
        return $results;
    }
    
    /**
     * Get a specific area
     */
    public function get_area($area_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        if ($user_id) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $area_id, $user_id
            ));
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $area_id
        ));
    }
    
    /**
     * Check if a ZIP code is covered by a user - ENHANCED with area details
     */
    public function is_zip_covered($zip_code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        // Input validation
        if (empty($zip_code) || empty($user_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Invalid parameters - ZIP: '{$zip_code}', User ID: '{$user_id}'");
            }
            return false;
        }
        
        // Sanitize inputs
        $zip_code = sanitize_text_field(trim($zip_code));
        $user_id = absint($user_id);
        
        if (empty($zip_code) || $user_id <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Invalid sanitized parameters - ZIP: '{$zip_code}', User ID: '{$user_id}'");
            }
            return false;
        }
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Checking ZIP coverage for ZIP: '{$zip_code}', User ID: {$user_id}");
        }
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Table {$table_name} does not exist!");
            }
            return false;
        }
        
        // Get the area details (not just count)
        $sql = "SELECT * FROM $table_name WHERE zip_code = %s AND user_id = %d AND active = 1";
        $prepared_query = $wpdb->prepare($sql, $zip_code, $user_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Executing query: {$prepared_query}");
        }
        
        $area = $wpdb->get_row($prepared_query);
        
        // Check for database errors
        if ($wpdb->last_error) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Database error in ZIP coverage check: " . $wpdb->last_error);
            }
            return false;
        }
        
        $is_covered = !empty($area);
        
        // Debug logging with additional context
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: ZIP coverage result: " . ($is_covered ? 'COVERED' : 'NOT COVERED'));
            if ($area) {
                error_log("MoBooking: Found area - ID: {$area->id}, ZIP: {$area->zip_code}, Label: " . ($area->label ?: 'none'));
            }
        }
        
        return $is_covered ? $area : false;
    }
    
    /**
     * Get area details by ZIP code - NEW method for enhanced response
     */
    public function get_area_by_zip($zip_code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $zip_code = sanitize_text_field(trim($zip_code));
        $user_id = absint($user_id);
        
        if (empty($zip_code) || $user_id <= 0) {
            return false;
        }
        
        $area = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE zip_code = %s AND user_id = %d AND active = 1",
            $zip_code, $user_id
        ));
        
        return $area ?: false;
    }
    
    /**
     * Get businesses that cover a ZIP code
     */
    public function get_businesses_by_zip($zip_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        $settings_table = $wpdb->prefix . 'mobooking_settings';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                a.user_id,
                a.label as area_label,
                u.display_name,
                s.company_name,
                s.primary_color
            FROM 
                $table_name a
            JOIN 
                {$wpdb->users} u ON a.user_id = u.ID
            LEFT JOIN 
                $settings_table s ON a.user_id = s.user_id
            WHERE 
                a.zip_code = %s
                AND a.active = 1",
            $zip_code
        ));
    }
    
    /**
     * Save an area
     */
    public function save_area($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        try {
            // Enhanced data validation
            if (empty($data['user_id']) || empty($data['zip_code'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Missing required data for save_area');
                }
                return false;
            }
            
            // Sanitize data
            $area_data = array(
                'user_id' => absint($data['user_id']),
                'zip_code' => sanitize_text_field(trim($data['zip_code'])),
                'label' => isset($data['label']) ? sanitize_text_field(trim($data['label'])) : '',
                'active' => isset($data['active']) ? (bool) $data['active'] : true
            );
            
            // Validate ZIP code format
            if (!preg_match('/^\d{5}(-\d{4})?$/', $area_data['zip_code'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Invalid ZIP code format: ' . $area_data['zip_code']);
                }
                return false;
            }
            
            // Check if user exists and is valid
            $user = get_userdata($area_data['user_id']);
            if (!$user) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: User ID ' . $area_data['user_id'] . ' does not exist');
                }
                return false;
            }
            
            // Check if this ZIP already exists for this user (excluding current record if updating)
            $existing_query = "SELECT id FROM $table_name WHERE user_id = %d AND zip_code = %s";
            $existing_params = array($area_data['user_id'], $area_data['zip_code']);
            
            if (!empty($data['id'])) {
                $existing_query .= " AND id != %d";
                $existing_params[] = absint($data['id']);
            }
            
            $existing = $wpdb->get_var($wpdb->prepare($existing_query, $existing_params));
            
            if ($existing) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: ZIP code {$area_data['zip_code']} already exists for user {$area_data['user_id']}");
                }
                return false; // ZIP already exists for this user
            }
            
            // Check if we're updating or creating
            if (!empty($data['id'])) {
                // Update existing area
                $area_id = absint($data['id']);
                
                // Verify ownership before updating
                $existing_area = $this->get_area($area_id, $area_data['user_id']);
                if (!$existing_area) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MoBooking: Area {$area_id} not found or access denied for user {$area_data['user_id']}");
                    }
                    return false;
                }
                
                $result = $wpdb->update(
                    $table_name,
                    $area_data,
                    array('id' => $area_id),
                    array('%d', '%s', '%s', '%d'),
                    array('%d')
                );
                
                if ($result === false) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Failed to update area. Error: ' . $wpdb->last_error);
                    }
                    return false;
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Successfully updated area {$area_id} for user {$area_data['user_id']}");
                }
                
                return $area_id;
            } else {
                // Create new area
                $result = $wpdb->insert(
                    $table_name,
                    $area_data,
                    array('%d', '%s', '%s', '%d')
                );
                
                if ($result === false) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Failed to create area. Error: ' . $wpdb->last_error);
                    }
                    return false;
                }
                
                $new_id = $wpdb->insert_id;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Successfully created area {$new_id} for user {$area_data['user_id']} with ZIP {$area_data['zip_code']}");
                }
                
                return $new_id;
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in save_area: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Delete an area
     */
    public function delete_area($area_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Deleting area {$area_id} for user {$user_id}");
        }
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $area_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Delete area result: " . ($result ? 'SUCCESS' : 'FAILED'));
            if ($wpdb->last_error) {
                error_log("MoBooking: Delete area error: " . $wpdb->last_error);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * AJAX handler to save area
     */
    public function ajax_save_area() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            // Get current user ID
            $user_id = get_current_user_id();
            
            // Prepare data
            $area_data = array(
                'user_id' => $user_id,
                'zip_code' => isset($_POST['zip_code']) ? $_POST['zip_code'] : '',
                'label' => isset($_POST['label']) ? $_POST['label'] : '',
                'active' => isset($_POST['active']) ? (bool) $_POST['active'] : true
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
            if (empty($area_data['zip_code'])) {
                wp_send_json_error(__('ZIP code is required.', 'mobooking'));
            }
            
            // Enhanced ZIP code validation
            if (!preg_match('/^\d{5}(-\d{4})?$/', $area_data['zip_code'])) {
                wp_send_json_error(__('Please enter a valid ZIP code (e.g., 12345 or 12345-6789).', 'mobooking'));
            }
            
            // Save area
            $area_id = $this->save_area($area_data);
            
            if (!$area_id) {
                wp_send_json_error(__('Failed to save area. This ZIP code may already exist.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'id' => $area_id,
                'message' => __('Area saved successfully.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_save_area: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while saving the area.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to delete area
     */
    public function ajax_delete_area() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-area-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            // Check area ID
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                wp_send_json_error(__('No area specified.', 'mobooking'));
            }
            
            $area_id = absint($_POST['id']);
            $user_id = get_current_user_id();
            
            // Verify ownership
            $area = $this->get_area($area_id);
            if (!$area || $area->user_id != $user_id) {
                wp_send_json_error(__('You do not have permission to delete this area.', 'mobooking'));
            }
            
            // Delete area
            $result = $this->delete_area($area_id, $user_id);
            
            if (!$result) {
                wp_send_json_error(__('Failed to delete area.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'message' => __('Area deleted successfully.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_delete_area: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while deleting the area.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to get areas
     */
    public function ajax_get_areas() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-areas-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $user_id = get_current_user_id();
            $areas = $this->get_user_areas($user_id);
            
            wp_send_json_success(array(
                'areas' => $areas
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_get_areas: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while retrieving areas.', 'mobooking'));
        }
    }
    
    /**
     * Area list shortcode
     */
    public function area_list_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'limit' => -1
        ), $atts);
        
        $user_id = $atts['user_id'] ? absint($atts['user_id']) : get_current_user_id();
        
        // If not logged in and no user ID specified, return nothing
        if (!$user_id) {
            return '';
        }
        
        // Get areas
        $areas = $this->get_user_areas($user_id);
        
        if (empty($areas)) {
            return '<p>' . __('No service areas defined.', 'mobooking') . '</p>';
        }
        
        // Limit number of areas if specified
        if ($atts['limit'] > 0 && count($areas) > $atts['limit']) {
            $areas = array_slice($areas, 0, $atts['limit']);
        }
        
        ob_start();
        ?>
        <div class="mobooking-area-list">
            <ul>
            <?php foreach ($areas as $area) : ?>
                <li class="area-item <?php echo $area->active ? 'active' : 'inactive'; ?>">
                    <span class="area-zip"><?php echo esc_html($area->zip_code); ?></span>
                    <?php if (!empty($area->label)) : ?>
                        <span class="area-label"><?php echo esc_html($area->label); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}