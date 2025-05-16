<?php
namespace MoBooking\Discounts;

/**
 * Discounts Manager class
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_discount', array($this, 'ajax_save_discount'));
        add_action('wp_ajax_mobooking_delete_discount', array($this, 'ajax_delete_discount'));
        add_action('wp_ajax_mobooking_get_discounts', array($this, 'ajax_get_discounts'));
        add_action('wp_ajax_nopriv_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        
        // Add shortcodes
        add_shortcode('mobooking_discount_list', array($this, 'discount_list_shortcode'));
    }
    
    /**
     * Get discounts for a user
     */
    public function get_user_discounts($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY code ASC",
            $user_id
        ));
    }
    
    /**
     * Get a specific discount
     */
    public function get_discount($discount_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        if ($user_id) {
            // Get discount only if it belongs to the user
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                $discount_id, $user_id
            ));
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $discount_id
        ));
    }
    
    /**
     * Get a discount by code for a specific user
     */
    public function get_discount_by_code($code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s AND user_id = %d AND active = 1",
            $code, $user_id
        ));
    }
    
    /**
     * Validate a discount code
     */
    public function validate_discount($code, $user_id) {
        $discount = $this->get_discount_by_code($code, $user_id);
        
        if (!$discount) {
            return array(
                'valid' => false,
                'message' => __('Invalid discount code.', 'mobooking')
            );
        }
        
        // Check if expired
        if ($discount->expiry_date && strtotime($discount->expiry_date) < time()) {
            return array(
                'valid' => false,
                'message' => __('This discount code has expired.', 'mobooking')
            );
        }
        
        // Check usage limit
        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            return array(
                'valid' => false,
                'message' => __('This discount code has reached its usage limit.', 'mobooking')
            );
        }
        
        return array(
            'valid' => true,
            'discount' => $discount,
            'message' => __('Discount code applied successfully.', 'mobooking')
        );
    }
    
    /**
     * Calculate discount amount
     */
    public function calculate_discount($total, $discount) {
        if ($discount->type == 'percentage') {
            // Percentage discount
            $amount = $total * ($discount->amount / 100);
        } else {
            // Fixed amount discount
            $amount = $discount->amount;
            
            // Don't allow discount to be more than the total
            if ($amount > $total) {
                $amount = $total;
            }
        }
        
        return $amount;
    }
    
    /**
     * Increment usage count for a discount
     */
    public function increment_usage_count($discount_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET usage_count = usage_count + 1 WHERE id = %d",
            $discount_id
        ));
    }
    
    /**
     * Save a discount
     */
    public function save_discount($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        // Sanitize data
        $discount_data = array(
            'user_id' => absint($data['user_id']),
            'code' => sanitize_text_field($data['code']),
            'type' => in_array($data['type'], array('percentage', 'fixed')) ? $data['type'] : 'percentage',
            'amount' => floatval($data['amount']),
            'expiry_date' => !empty($data['expiry_date']) ? sanitize_text_field($data['expiry_date']) : null,
            'usage_limit' => isset($data['usage_limit']) ? absint($data['usage_limit']) : null,
            'active' => isset($data['active']) ? (bool) $data['active'] : true
        );
        
        // Check if this code already exists for this user
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND code = %s AND id != %d",
            $discount_data['user_id'], $discount_data['code'], isset($data['id']) ? absint($data['id']) : 0
        ));
        
        if ($existing) {
            return false; // Code already exists for this user
        }
        
        // Check if we're updating or creating
        if (!empty($data['id'])) {
            // Update existing discount
            $wpdb->update(
                $table_name,
                $discount_data,
                array('id' => absint($data['id'])),
                array('%d', '%s', '%s', '%f', '%s', '%d', '%d'),
                array('%d')
            );
            
            return absint($data['id']);
        } else {
            // Create new discount
            $wpdb->insert(
                $table_name,
                $discount_data,
                array('%d', '%s', '%s', '%f', '%s', '%d', '%d')
            );
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Delete a discount
     */
    public function delete_discount($discount_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->delete(
            $table_name,
            array(
                'id' => $discount_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
    }
    
    /**
     * AJAX handler to save discount
     */
    public function ajax_save_discount() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discount-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Prepare data
        $discount_data = array(
            'user_id' => $user_id,
            'code' => isset($_POST['code']) ? $_POST['code'] : '',
            'type' => isset($_POST['type']) ? $_POST['type'] : 'percentage',
            'amount' => isset($_POST['amount']) ? $_POST['amount'] : 0,
            'expiry_date' => isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
            'usage_limit' => isset($_POST['usage_limit']) ? $_POST['usage_limit'] : null,
            'active' => isset($_POST['active']) ? (bool) $_POST['active'] : true
        );
        
        // Add ID if editing
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $discount_data['id'] = $_POST['id'];
            
            // Verify ownership
            $discount = $this->get_discount($discount_data['id']);
            if (!$discount || $discount->user_id != $user_id) {
                wp_send_json_error(__('You do not have permission to edit this discount.', 'mobooking'));
            }
        }
        
        // Validate data
        if (empty($discount_data['code'])) {
            wp_send_json_error(__('Discount code is required.', 'mobooking'));
        }
        
        if ($discount_data['amount'] <= 0) {
            wp_send_json_error(__('Discount amount must be greater than zero.', 'mobooking'));
        }
        
        // Save discount
        $discount_id = $this->save_discount($discount_data);
        
        if (!$discount_id) {
            wp_send_json_error(__('Failed to save discount. This code may already exist.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'id' => $discount_id,
            'message' => __('Discount saved successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to delete discount
     */
    public function ajax_delete_discount() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discount-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Check discount ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            wp_send_json_error(__('No discount specified.', 'mobooking'));
        }
        
        $discount_id = absint($_POST['id']);
        $user_id = get_current_user_id();
        
        // Verify ownership
        $discount = $this->get_discount($discount_id);
        if (!$discount || $discount->user_id != $user_id) {
            wp_send_json_error(__('You do not have permission to delete this discount.', 'mobooking'));
        }
        
        // Delete discount
        $result = $this->delete_discount($discount_id, $user_id);
        
        if (!$result) {
            wp_send_json_error(__('Failed to delete discount.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'message' => __('Discount deleted successfully.', 'mobooking')
        ));
    }
    
    /**
     * AJAX handler to get discounts
     */
    public function ajax_get_discounts() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discounts-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $discounts = $this->get_user_discounts($user_id);
        
        wp_send_json_success(array(
            'discounts' => $discounts
        ));
    }
    
    /**
     * AJAX handler to validate discount
     */
    public function ajax_validate_discount() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-validate-discount-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check required fields
        if (!isset($_POST['code']) || empty($_POST['code']) || !isset($_POST['user_id']) || empty($_POST['user_id'])) {
            wp_send_json_error(__('Missing required information.', 'mobooking'));
        }
        
        $code = sanitize_text_field($_POST['code']);
        $user_id = absint($_POST['user_id']);
        $total = isset($_POST['total']) ? floatval($_POST['total']) : 0;
        
        $result = $this->validate_discount($code, $user_id);
        
        if (!$result['valid']) {
            wp_send_json_error($result);
        }
        
        // Calculate discount amount if total is provided
        if ($total > 0) {
            $discount_amount = $this->calculate_discount($total, $result['discount']);
            $result['discount_amount'] = $discount_amount;
            $result['new_total'] = $total - $discount_amount;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Discount list shortcode
     */
    public function discount_list_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
            'limit' => -1,
            'show_expired' => false
        ), $atts);
        
        $user_id = $atts['user_id'] ? absint($atts['user_id']) : get_current_user_id();
        
        // If not logged in and no user ID specified, return nothing
        if (!$user_id) {
            return '';
        }
        
        // Get discounts
        $discounts = $this->get_user_discounts($user_id);
        
        if (empty($discounts)) {
            return '<p>' . __('No discount codes available.', 'mobooking') . '</p>';
        }
        
        // Filter out expired discounts if needed
        if (!$atts['show_expired']) {
            $discounts = array_filter($discounts, function($discount) {
                if (!$discount->active) {
                    return false;
                }
                
                if ($discount->expiry_date && strtotime($discount->expiry_date) < time()) {
                    return false;
                }
                
                if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
                    return false;
                }
                
                return true;
            });
        }
        
        // Limit number of discounts if specified
        if ($atts['limit'] > 0 && count($discounts) > $atts['limit']) {
            $discounts = array_slice($discounts, 0, $atts['limit']);
        }
        
        ob_start();
        ?>
        <div class="mobooking-discount-list">
            <?php foreach ($discounts as $discount) : 
                $is_expired = $discount->expiry_date && strtotime($discount->expiry_date) < time();
                $is_used_up = $discount->usage_limit && $discount->usage_count >= $discount->usage_limit;
                $is_inactive = !$discount->active || $is_expired || $is_used_up;
            ?>
                <div class="discount-item <?php echo $is_inactive ? 'inactive' : 'active'; ?>">
                    <div class="discount-code"><?php echo esc_html($discount->code); ?></div>
                    <div class="discount-details">
                        <div class="discount-amount">
                            <?php if ($discount->type == 'percentage') : ?>
                                <?php echo esc_html($discount->amount); ?>% <?php _e('off', 'mobooking'); ?>
                            <?php else : ?>
                                <?php echo wc_price($discount->amount); ?> <?php _e('off', 'mobooking'); ?>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($discount->expiry_date) : ?>
                            <div class="discount-expiry">
                                <?php if ($is_expired) : ?>
                                    <?php _e('Expired', 'mobooking'); ?>
                                <?php else : ?>
                                    <?php _e('Valid until', 'mobooking'); ?> <?php echo date_i18n(get_option('date_format'), strtotime($discount->expiry_date)); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($discount->usage_limit) : ?>
                            <div class="discount-usage">
                                <?php printf(
                                    __('Used %d of %d times', 'mobooking'),
                                    $discount->usage_count,
                                    $discount->usage_limit
                                ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}