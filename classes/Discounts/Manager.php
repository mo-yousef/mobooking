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
        add_action('wp_ajax_mobooking_toggle_discount_status', array($this, 'ajax_toggle_discount_status'));
        add_action('wp_ajax_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        add_action('wp_ajax_mobooking_bulk_generate_discounts', array($this, 'ajax_bulk_generate_discounts'));
        
        // Public AJAX handlers for frontend
        add_action('wp_ajax_nopriv_mobooking_validate_discount', array($this, 'ajax_validate_discount'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking\Discounts\Manager: Constructor called');
        }
    }
    
    /**
     * Get discounts for a user
     */
    public function get_user_discounts($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Getting discounts for user {$user_id} from table {$table_name}");
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Found " . count($results) . " discounts for user {$user_id}");
        }
        
        return $results;
    }
    
    /**
     * Get a specific discount
     */
    public function get_discount($discount_id, $user_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        if ($user_id) {
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
     * Save a discount
     */
    public function save_discount($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        try {
            // Enhanced data validation
            if (empty($data['user_id']) || empty($data['code'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Missing required data for save_discount');
                }
                return false;
            }
            
            // Sanitize data
            $discount_data = array(
                'user_id' => absint($data['user_id']),
                'code' => strtoupper(sanitize_text_field(trim($data['code']))),
                'type' => sanitize_text_field($data['type']),
                'amount' => floatval($data['amount']),
                'expiry_date' => !empty($data['expiry_date']) ? sanitize_text_field($data['expiry_date']) : null,
                'usage_limit' => !empty($data['usage_limit']) ? absint($data['usage_limit']) : null,
                'active' => isset($data['active']) ? (bool) $data['active'] : true
            );
            
            // Validate discount code format
            if (!preg_match('/^[A-Z0-9]{3,20}$/', $discount_data['code'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Invalid discount code format: ' . $discount_data['code']);
                }
                return false;
            }
            
            // Validate discount type
            if (!in_array($discount_data['type'], array('percentage', 'fixed'))) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Invalid discount type: ' . $discount_data['type']);
                }
                return false;
            }
            
            // Validate amount
            if ($discount_data['amount'] <= 0) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Invalid discount amount: ' . $discount_data['amount']);
                }
                return false;
            }
            
            if ($discount_data['type'] === 'percentage' && $discount_data['amount'] > 100) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Percentage discount cannot exceed 100%');
                }
                return false;
            }
            
            // Check if user exists and is valid
            $user = get_userdata($discount_data['user_id']);
            if (!$user) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: User ID ' . $discount_data['user_id'] . ' does not exist');
                }
                return false;
            }
            
            // Check if this code already exists for this user (excluding current record if updating)
            $existing_query = "SELECT id FROM $table_name WHERE user_id = %d AND code = %s";
            $existing_params = array($discount_data['user_id'], $discount_data['code']);
            
            if (!empty($data['id'])) {
                $existing_query .= " AND id != %d";
                $existing_params[] = absint($data['id']);
            }
            
            $existing = $wpdb->get_var($wpdb->prepare($existing_query, $existing_params));
            
            if ($existing) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Discount code {$discount_data['code']} already exists for user {$discount_data['user_id']}");
                }
                return false; // Code already exists for this user
            }
            
            // Check if we're updating or creating
            if (!empty($data['id'])) {
                // Update existing discount
                $discount_id = absint($data['id']);
                
                // Verify ownership before updating
                $existing_discount = $this->get_discount($discount_id, $discount_data['user_id']);
                if (!$existing_discount) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MoBooking: Discount {$discount_id} not found or access denied for user {$discount_data['user_id']}");
                    }
                    return false;
                }
                
                $result = $wpdb->update(
                    $table_name,
                    $discount_data,
                    array('id' => $discount_id),
                    array('%d', '%s', '%s', '%f', '%s', '%d', '%d'),
                    array('%d')
                );
                
                if ($result === false) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Failed to update discount. Error: ' . $wpdb->last_error);
                    }
                    return false;
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Successfully updated discount {$discount_id} for user {$discount_data['user_id']}");
                }
                
                return $discount_id;
            } else {
                // Create new discount
                $result = $wpdb->insert(
                    $table_name,
                    $discount_data,
                    array('%d', '%s', '%s', '%f', '%s', '%d', '%d')
                );
                
                if ($result === false) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('MoBooking: Failed to create discount. Error: ' . $wpdb->last_error);
                    }
                    return false;
                }
                
                $new_id = $wpdb->insert_id;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Successfully created discount {$new_id} for user {$discount_data['user_id']} with code {$discount_data['code']}");
                }
                
                return $new_id;
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in save_discount: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Delete a discount
     */
    public function delete_discount($discount_id, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Deleting discount {$discount_id} for user {$user_id}");
        }
        
        $result = $wpdb->delete(
            $table_name,
            array(
                'id' => $discount_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MoBooking: Delete discount result: " . ($result ? 'SUCCESS' : 'FAILED'));
            if ($wpdb->last_error) {
                error_log("MoBooking: Delete discount error: " . $wpdb->last_error);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Toggle discount status
     */
    public function toggle_discount_status($discount_id, $user_id, $active) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->update(
            $table_name,
            array('active' => $active ? 1 : 0),
            array('id' => $discount_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * Validate discount code
     */
    public function validate_discount_code($code, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        $discount = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s AND user_id = %d AND active = 1",
            strtoupper($code), $user_id
        ));
        
        if (!$discount) {
            return false;
        }
        
        // Check if expired
        if ($discount->expiry_date && strtotime($discount->expiry_date) < time()) {
            return false;
        }
        
        // Check usage limit
        if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
            return false;
        }
        
        return $discount;
    }
    
    /**
     * Use discount code (increment usage count)
     */
    public function use_discount_code($discount_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_discounts';
        
        return $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET usage_count = usage_count + 1 WHERE id = %d",
            $discount_id
        ));
    }
    
    /**
     * Generate random discount code
     */
    public function generate_random_code($prefix = '', $length = 8) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = $prefix;
        $remaining_length = $length - strlen($prefix);
        
        for ($i = 0; $i < $remaining_length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Bulk generate discount codes
     */
    public function bulk_generate_discounts($user_id, $count, $prefix, $type, $amount, $expiry_date = null, $usage_limit = null) {
        $generated_codes = array();
        $failed_codes = array();
        
        for ($i = 0; $i < $count; $i++) {
            $attempts = 0;
            $max_attempts = 10;
            
            do {
                $code = $this->generate_random_code($prefix, max(8, strlen($prefix) + 4));
                $attempts++;
                
                $discount_data = array(
                    'user_id' => $user_id,
                    'code' => $code,
                    'type' => $type,
                    'amount' => $amount,
                    'expiry_date' => $expiry_date,
                    'usage_limit' => $usage_limit,
                    'active' => true
                );
                
                $discount_id = $this->save_discount($discount_data);
                
                if ($discount_id) {
                    $generated_codes[] = $code;
                    break;
                } else if ($attempts >= $max_attempts) {
                    $failed_codes[] = $code;
                    break;
                }
            } while ($attempts < $max_attempts);
        }
        
        return array(
            'generated' => $generated_codes,
            'failed' => $failed_codes
        );
    }
    
    /**
     * AJAX handler to save discount
     */
    public function ajax_save_discount() {
        try {
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
                'expiry_date' => isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '',
                'usage_limit' => isset($_POST['usage_limit']) ? $_POST['usage_limit'] : '',
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
            
            if (!preg_match('/^[A-Z0-9]{3,20}$/', strtoupper($discount_data['code']))) {
                wp_send_json_error(__('Discount code must be 3-20 characters, letters and numbers only.', 'mobooking'));
            }
            
            if ($discount_data['amount'] <= 0) {
                wp_send_json_error(__('Discount amount must be greater than zero.', 'mobooking'));
            }
            
            if ($discount_data['type'] === 'percentage' && $discount_data['amount'] > 100) {
                wp_send_json_error(__('Percentage discount cannot exceed 100%.', 'mobooking'));
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
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_save_discount: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while saving the discount.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to delete discount
     */
    public function ajax_delete_discount() {
        try {
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
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_delete_discount: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while deleting the discount.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to toggle discount status
     */
    public function ajax_toggle_discount_status() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discount-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $discount_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
            $active = isset($_POST['active']) ? (bool) $_POST['active'] : false;
            $user_id = get_current_user_id();
            
            if (!$discount_id) {
                wp_send_json_error(__('No discount specified.', 'mobooking'));
            }
            
            // Verify ownership
            $discount = $this->get_discount($discount_id);
            if (!$discount || $discount->user_id != $user_id) {
                wp_send_json_error(__('You do not have permission to modify this discount.', 'mobooking'));
            }
            
            $result = $this->toggle_discount_status($discount_id, $user_id, $active);
            
            if (!$result) {
                wp_send_json_error(__('Failed to update discount status.', 'mobooking'));
            }
            
            wp_send_json_success(array(
                'message' => $active ? __('Discount activated.', 'mobooking') : __('Discount deactivated.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_toggle_discount_status: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while updating the discount status.', 'mobooking'));
        }
    }
    
    /**
     * AJAX handler to validate discount code
     */
    public function ajax_validate_discount() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-nonce')) {
                wp_send_json_error(array('message' => __('Security verification failed.', 'mobooking')));
            }
            
            // Check required fields
            if (!isset($_POST['code']) || !isset($_POST['user_id']) || !isset($_POST['total'])) {
                wp_send_json_error(array('message' => __('Missing required information.', 'mobooking')));
            }
            
            $code = sanitize_text_field($_POST['code']);
            $user_id = absint($_POST['user_id']);
            $total = floatval($_POST['total']);
            
            // Validate discount code
            $discount = $this->validate_discount_code($code, $user_id);
            
            if (!$discount) {
                wp_send_json_error(array(
                    'message' => __('Invalid or expired discount code.', 'mobooking')
                ));
            }
            
            // Calculate discount amount
            $discount_amount = 0;
            if ($discount->type === 'percentage') {
                $discount_amount = ($total * $discount->amount) / 100;
            } else {
                $discount_amount = min($discount->amount, $total);
            }
            
            wp_send_json_success(array(
                'discount_amount' => $discount_amount,
                'message' => sprintf(__('Discount applied! You save %s', 'mobooking'), 
                    function_exists('wc_price') ? wc_price($discount_amount) : '$' . number_format($discount_amount, 2))
            ));
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_validate_discount: ' . $e->getMessage());
            }
            wp_send_json_error(array(
                'message' => __('Error processing discount code.', 'mobooking')
            ));
        }
    }
    
    /**
     * AJAX handler to bulk generate discounts
     */
    public function ajax_bulk_generate_discounts() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discount-nonce')) {
                wp_send_json_error(__('Security verification failed.', 'mobooking'));
            }
            
            // Check permissions
            if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
                wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
            }
            
            $user_id = get_current_user_id();
            $count = isset($_POST['count']) ? absint($_POST['count']) : 1;
            $prefix = isset($_POST['prefix']) ? sanitize_text_field($_POST['prefix']) : '';
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'percentage';
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            $expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null;
            $usage_limit = isset($_POST['usage_limit']) ? absint($_POST['usage_limit']) : null;
            
            // Validate inputs
            if ($count <= 0 || $count > 100) {
                wp_send_json_error(__('Count must be between 1 and 100.', 'mobooking'));
            }
            
            if ($amount <= 0) {
                wp_send_json_error(__('Amount must be greater than zero.', 'mobooking'));
            }
            
            if ($type === 'percentage' && $amount > 100) {
                wp_send_json_error(__('Percentage cannot exceed 100%.', 'mobooking'));
            }
            
            if ($prefix && !preg_match('/^[A-Z]{2,10}$/', $prefix)) {
                wp_send_json_error(__('Prefix must be 2-10 uppercase letters.', 'mobooking'));
            }
            
            // Generate discounts
            $result = $this->bulk_generate_discounts($user_id, $count, $prefix, $type, $amount, $expiry_date, $usage_limit);
            
            $generated_count = count($result['generated']);
            $failed_count = count($result['failed']);
            
            if ($generated_count > 0) {
                $message = sprintf(
                    _n('%d discount code generated successfully.', '%d discount codes generated successfully.', $generated_count, 'mobooking'),
                    $generated_count
                );
                
                if ($failed_count > 0) {
                    $message .= ' ' . sprintf(
                        _n('%d code failed to generate.', '%d codes failed to generate.', $failed_count, 'mobooking'),
                        $failed_count
                    );
                }
                
                wp_send_json_success(array(
                    'message' => $message,
                    'generated' => $result['generated'],
                    'failed' => $result['failed']
                ));
            } else {
                wp_send_json_error(__('Failed to generate discount codes.', 'mobooking'));
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_bulk_generate_discounts: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while generating discount codes.', 'mobooking'));
        }
    }
    
    /**
     * Get discounts AJAX handler
     */
    public function ajax_get_discounts() {
        try {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-discount-nonce')) {
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
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Exception in ajax_get_discounts: ' . $e->getMessage());
            }
            wp_send_json_error(__('An error occurred while retrieving discounts.', 'mobooking'));
        }
    }
}