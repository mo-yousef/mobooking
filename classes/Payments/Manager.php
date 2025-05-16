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
        // Register hooks
        add_action('init', array($this, 'check_woocommerce'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('woocommerce_subscription_status_active', array($this, 'handle_subscription_active'), 10, 2);
        add_action('woocommerce_subscription_status_cancelled', array($this, 'handle_subscription_cancelled'), 10, 2);
        
        // Add shortcodes
        add_shortcode('mobooking_subscription_plans', array($this, 'subscription_plans_shortcode'));
    }
    
    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('MoBooking requires WooCommerce to be installed and activated.', 'mobooking'); ?></p>
                </div>
                <?php
            });
        }
    }
    
    /**
     * Handle payment complete
     */
    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        // Check if order contains a subscription product
        $is_subscription = false;
        
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            
            if ($product && $product->get_meta('_mobooking_subscription')) {
                $is_subscription = true;
                $subscription_type = $product->get_meta('_mobooking_subscription_type');
                
                // Update user meta with subscription info
                update_user_meta($user_id, 'mobooking_has_subscription', true);
                update_user_meta($user_id, 'mobooking_subscription_type', $subscription_type);
                update_user_meta($user_id, 'mobooking_subscription_expiry', '');
                
                // Create default services for the user
                $this->create_default_services($user_id);
                
                break;
            }
        }
        
        // If not a subscription, might be a one-time purchase
        if (!$is_subscription) {
            // Handle one-time purchases if needed
        }
    }
    
    /**
     * Handle subscription active
     */
    public function handle_subscription_active($subscription, $subscription_id) {
        $user_id = $subscription->get_user_id();
        
        // Update user meta
        update_user_meta($user_id, 'mobooking_has_subscription', true);
        
        // Find the subscription product
        foreach ($subscription->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            
            if ($product && $product->get_meta('_mobooking_subscription')) {
                $subscription_type = $product->get_meta('_mobooking_subscription_type');
                update_user_meta($user_id, 'mobooking_subscription_type', $subscription_type);
                break;
            }
        }
    }
    
    /**
     * Handle subscription cancelled
     */
    public function handle_subscription_cancelled($subscription, $subscription_id) {
        $user_id = $subscription->get_user_id();
        
        // Update user meta
        update_user_meta($user_id, 'mobooking_has_subscription', false);
        
        // Calculate grace period (e.g., 7 days)
        $expiry_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        update_user_meta($user_id, 'mobooking_subscription_expiry', $expiry_date);
    }
    
    /**
     * Create default services for new subscribers
     */
    private function create_default_services($user_id) {
        $services_manager = new \MoBooking\Services\Manager();
        
        // Add some default services
        $default_services = array(
            array(
                'name' => 'Regular Cleaning',
                'description' => 'Standard cleaning service for your home or office.',
                'price' => 99.99,
                'duration' => 120,
                'icon' => 'broom',
                'category' => 'residential'
            ),
            array(
                'name' => 'Deep Cleaning',
                'description' => 'Thorough cleaning of all areas including hard-to-reach spots.',
                'price' => 199.99,
                'duration' => 240,
                'icon' => 'spray-bottle',
                'category' => 'residential'
            ),
            array(
                'name' => 'Move-in/Move-out Cleaning',
                'description' => 'Complete cleaning service for when you move in or out of a property.',
                'price' => 249.99,
                'duration' => 300,
                'icon' => 'house',
                'category' => 'residential'
            )
        );
        
        foreach ($default_services as $service) {
            $services_manager->save_service(array_merge($service, array('user_id' => $user_id)));
        }
    }
    
    /**
     * Subscription plans shortcode
     */
    public function subscription_plans_shortcode() {
        // Get subscription products
        $products = $this->get_subscription_products();
        
        if (empty($products)) {
            return '<p>' . __('No subscription plans available.', 'mobooking') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="mobooking-subscription-plans">
            <div class="plans-container">
                <?php foreach ($products as $product) : ?>
                    <div class="plan">
                        <div class="plan-header">
                            <h3><?php echo esc_html($product->get_name()); ?></h3>
                            <div class="price">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                        </div>
                        
                        <div class="plan-features">
                            <?php echo wpautop($product->get_description()); ?>
                        </div>
                        
                        <div class="plan-footer">
                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="button button-primary"><?php _e('Select Plan', 'mobooking'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get subscription products
     */
    private function get_subscription_products() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mobooking_subscription',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        $products = array();
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = $product;
                }
            }
            wp_reset_postdata();
        }
        
        return $products;
    }
}