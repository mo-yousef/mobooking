<?php
namespace MoBooking\Payments;

/**
 * Payments Manager class
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks for payment processing
        add_action('wp_ajax_mobooking_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_mobooking_process_payment', array($this, 'ajax_process_payment'));
        
        // WooCommerce integration hooks
        add_action('woocommerce_checkout_create_order', array($this, 'create_order_from_booking'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
    }
    
    /**
     * Process payment for a booking
     */
    public function process_payment($booking_id, $payment_method = 'stripe') {
        $bookings_manager = new \MoBooking\Bookings\Manager();
        $booking = $bookings_manager->get_booking($booking_id);
        
        if (!$booking) {
            return array('success' => false, 'message' => __('Booking not found.', 'mobooking'));
        }
        
        // For now, we'll integrate with WooCommerce
        if (class_exists('WooCommerce')) {
            return $this->process_woocommerce_payment($booking);
        }
        
        // Fallback to direct Stripe integration (if needed)
        if ($payment_method === 'stripe') {
            return $this->process_stripe_payment($booking);
        }
        
        return array('success' => false, 'message' => __('No payment method available.', 'mobooking'));
    }
    
    /**
     * Process payment through WooCommerce
     */
    private function process_woocommerce_payment($booking) {
        try {
            // Create a WooCommerce order from the booking
            $order = $this->create_wc_order_from_booking($booking);
            
            if (!$order) {
                return array('success' => false, 'message' => __('Failed to create order.', 'mobooking'));
            }
            
            // Return checkout URL
            return array(
                'success' => true,
                'checkout_url' => $order->get_checkout_payment_url(),
                'order_id' => $order->get_id()
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('Payment processing failed: ', 'mobooking') . $e->getMessage()
            );
        }
    }
    
    /**
     * Create WooCommerce order from booking
     */
    private function create_wc_order_from_booking($booking) {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Create new order
        $order = wc_create_order();
        
        if (!$order) {
            return false;
        }
        
        // Set customer information
        $order->set_billing_first_name($booking->customer_name);
        $order->set_billing_email($booking->customer_email);
        $order->set_billing_phone($booking->customer_phone);
        $order->set_billing_address_1($booking->customer_address);
        $order->set_billing_postcode($booking->zip_code);
        
        // Add services as order items
        $services = json_decode($booking->services, true);
        if (is_array($services)) {
            foreach ($services as $service) {
                $item = new \WC_Order_Item_Product();
                $item->set_name($service['name']);
                $item->set_quantity(1);
                $item->set_subtotal($service['price']);
                $item->set_total($service['price']);
                $order->add_item($item);
            }
        }
        
        // Apply discount if any
        if ($booking->discount_amount > 0) {
            $discount = new \WC_Order_Item_Coupon();
            $discount->set_name($booking->discount_code);
            $discount->set_discount($booking->discount_amount);
            $order->add_item($discount);
        }
        
        // Set order total
        $order->set_total($booking->total_price);
        
        // Add booking reference
        $order->add_meta_data('mobooking_booking_id', $booking->id);
        $order->add_meta_data('mobooking_service_date', $booking->service_date);
        
        // Set status
        $order->set_status('pending');
        
        // Save order
        $order->save();
        
        return $order;
    }
    
    /**
     * Process Stripe payment directly (fallback)
     */
    private function process_stripe_payment($booking) {
        // This would integrate with Stripe directly
        // For now, return a placeholder response
        return array(
            'success' => false,
            'message' => __('Direct Stripe integration not yet implemented. Please use WooCommerce.', 'mobooking')
        );
    }
    
    /**
     * Handle WooCommerce payment completion
     */
    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Get booking ID from order meta
        $booking_id = $order->get_meta('mobooking_booking_id');
        
        if (!$booking_id) {
            return;
        }
        
        // Update booking status
        $bookings_manager = new \MoBooking\Bookings\Manager();
        $booking = $bookings_manager->get_booking($booking_id);
        
        if ($booking) {
            // Update booking status to confirmed
            $bookings_manager->update_booking_status($booking_id, 'confirmed', $booking->user_id);
            
            // Send confirmation notifications
            $notifications_manager = new \MoBooking\Notifications\Manager();
            $notifications_manager->send_status_update($booking_id);
        }
    }
    
    /**
     * Get payment methods available
     */
    public function get_available_payment_methods() {
        $methods = array();
        
        // Check if WooCommerce is available
        if (class_exists('WooCommerce')) {
            $methods['woocommerce'] = array(
                'name' => __('WooCommerce Checkout', 'mobooking'),
                'description' => __('Process payments through WooCommerce with all available payment gateways.', 'mobooking'),
                'enabled' => true
            );
        }
        
        // Check for Stripe (placeholder)
        $methods['stripe'] = array(
            'name' => __('Stripe', 'mobooking'),
            'description' => __('Direct Stripe integration (coming soon).', 'mobooking'),
            'enabled' => false
        );
        
        return $methods;
    }
    
    /**
     * AJAX handler for payment processing
     */
    public function ajax_process_payment() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-payment-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Get booking ID
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID.', 'mobooking'));
        }
        
        // Get payment method
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'woocommerce';
        
        // Process payment
        $result = $this->process_payment($booking_id, $payment_method);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Create subscription for business owner
     */
    public function create_subscription($user_id, $plan_type = 'basic') {
        // This would handle subscription creation
        // For now, just update user meta
        update_user_meta($user_id, 'mobooking_has_subscription', true);
        update_user_meta($user_id, 'mobooking_subscription_type', $plan_type);
        update_user_meta($user_id, 'mobooking_subscription_start', current_time('mysql'));
        
        // Set expiry date (1 year from now)
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        update_user_meta($user_id, 'mobooking_subscription_expiry', $expiry_date);
        
        return true;
    }
    
    /**
     * Cancel subscription
     */
    public function cancel_subscription($user_id) {
        update_user_meta($user_id, 'mobooking_has_subscription', false);
        update_user_meta($user_id, 'mobooking_subscription_cancelled', current_time('mysql'));
        
        return true;
    }
    
    /**
     * Check if subscription is active
     */
    public function is_subscription_active($user_id) {
        $has_subscription = get_user_meta($user_id, 'mobooking_has_subscription', true);
        $expiry_date = get_user_meta($user_id, 'mobooking_subscription_expiry', true);
        
        if (!$has_subscription) {
            return false;
        }
        
        if ($expiry_date && strtotime($expiry_date) < time()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get subscription info
     */
    public function get_subscription_info($user_id) {
        return array(
            'has_subscription' => get_user_meta($user_id, 'mobooking_has_subscription', true),
            'type' => get_user_meta($user_id, 'mobooking_subscription_type', true),
            'start_date' => get_user_meta($user_id, 'mobooking_subscription_start', true),
            'expiry_date' => get_user_meta($user_id, 'mobooking_subscription_expiry', true),
            'is_active' => $this->is_subscription_active($user_id)
        );
    }
}