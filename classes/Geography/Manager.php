<?php
namespace MoBooking\Geography;

/**
 * Geography Manager class
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
        add_action('wp_ajax_nopriv_mobooking_check_zip_coverage', array($this, 'ajax_check_zip_coverage'));
        
        // Add shortcodes
        add_shortcode('mobooking_area_list', array($this, 'area_list_shortcode'));
    }
    
    /**
     * Get areas for a user
     */
    public function get_user_areas($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY zip_code ASC",
            $user_id
        ));
    }
    
    /**
     * Get a specific area
     */
    public function get_area($area_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        if ($user_id) {
            // Get area only if it belongs to the user
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
     * Check if a ZIP code is covered by a user
     */
    public function is_zip_covered($zip_code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE zip_code = %s AND user_id = %d AND active = 1",
            $zip_code, $user_id
        ));
        
        return $result > 0;
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
        
        // Sanitize data
        $area_data = array(
            'user_id' => absint($data['user_id']),
            'zip_code' => sanitize_text_field($data['zip_code']),
            'label' => isset($data['label']) ? sanitize_text_field($data['label']) : '',
            'active' => isset($data['active']) ? (bool) $data['active'] : true
        );
        
        // Check if this ZIP already exists for this user
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND zip_code = %s",
            $area_data['user_id'], $area_data['zip_code']
        ));
        
        if ($existing && (empty($data['id']) || $existing != $data['id'])) {
            return false; // ZIP already exists for this user
        }
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing area
            $wpdb->update(
                $table_name,
                $area_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%d'),
                array('%d')
            );
            
            return absint($data['id']);
        } else {
            // Create new area
            $wpdb->insert(
                $table_name,
                $area_data,
                array('%d', '%s', '%s', '%d')
            );
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete an area
     */
    public function delete_area($area_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_areas';
        
        return $wpdb->delete(
            $table_name,
            array(
                'id' => $area_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
    }
    
    /**
     * AJAX handler to save area
     */
    public function ajax_save_area() {
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
        
        // Save area
        $area_id = $this->save_area($area_data);
        
        if (!$area_id) {
            wp_send_json_error(__('Failed to save area. This ZIP code may already exist.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'id' => $area_id,
            'message' => __('Area saved successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to delete area
     */
    public function ajax_delete_area() {
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
    }
    
    /**
     * AJAX handler to get areas
     */
    public function ajax_get_areas() {
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
    }
    
    /**
     * AJAX handler to check ZIP coverage
     */
    public function ajax_check_zip_coverage() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-zip-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check ZIP code
        if (!isset($_POST['zip_code']) || empty($_POST['zip_code'])) {
            wp_send_json_error(__('ZIP code is required.', 'mobooking'));
        }
        
        $zip_code = sanitize_text_field($_POST['zip_code']);
        $businesses = $this->get_businesses_by_zip($zip_code);
        
        if (empty($businesses)) {
            wp_send_json_error(array(
                'message' => __('Sorry, we don\'t have any service providers in your area yet.', 'mobooking')
            ));
        }
        
        wp_send_json_success(array(
            'businesses' => $businesses,
            'message' => sprintf(
                _n(
                    'We found %d service provider in your area!',
                    'We found %d service providers in your area!',
                    count($businesses),
                    'mobooking'
                ),
                count($businesses)
            )
        ));
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