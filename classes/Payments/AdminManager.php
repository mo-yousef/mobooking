<?php
namespace MoBooking\Payments;

/**
 * Admin Payments Manager
 */
class AdminManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Only initialize in admin area
        if (!is_admin()) {
            return;
        }
        
        // Add custom product tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_mobooking_product_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_mobooking_product_panel'));
        
        // Save custom product data
        add_action('woocommerce_process_product_meta', array($this, 'save_mobooking_product_data'));
        
        // Add custom column to products list
        add_filter('manage_edit-product_columns', array($this, 'add_mobooking_product_column'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_mobooking_product_column'), 10, 2);
    }
    
    /**
     * Add custom product tab
     */
    public function add_mobooking_product_tab($tabs) {
        $tabs['mobooking'] = array(
            'label' => __('MoBooking', 'mobooking'),
            'target' => 'mobooking_product_data',
            'class' => array('show_if_simple', 'show_if_subscription'),
        );
        
        return $tabs;
    }
    
    /**
     * Add custom product panel
     */
    public function add_mobooking_product_panel() {
        ?>
        <div id="mobooking_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                // Is MoBooking Subscription
                woocommerce_wp_checkbox(array(
                    'id' => '_mobooking_subscription',
                    'label' => __('MoBooking Subscription', 'mobooking'),
                    'description' => __('Is this product a MoBooking subscription plan?', 'mobooking')
                ));
                
                // Subscription Type
                woocommerce_wp_select(array(
                    'id' => '_mobooking_subscription_type',
                    'label' => __('Subscription Type', 'mobooking'),
                    'description' => __('Select the subscription type', 'mobooking'),
                    'options' => array(
                        '' => __('Select type', 'mobooking'),
                        'basic' => __('Basic', 'mobooking'),
                        'premium' => __('Premium', 'mobooking'),
                        'professional' => __('Professional', 'mobooking')
                    ),
                    'desc_tip' => true
                ));
                
                // Features List
                woocommerce_wp_textarea_input(array(
                    'id' => '_mobooking_features',
                    'label' => __('Features', 'mobooking'),
                    'description' => __('Enter features, one per line', 'mobooking'),
                    'desc_tip' => true
                ));
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save custom product data
     */
    public function save_mobooking_product_data($post_id) {
        // Is MoBooking Subscription
        $is_subscription = isset($_POST['_mobooking_subscription']) ? 'yes' : 'no';
        update_post_meta($post_id, '_mobooking_subscription', $is_subscription);
        
        // Subscription Type
        if (isset($_POST['_mobooking_subscription_type'])) {
            update_post_meta($post_id, '_mobooking_subscription_type', sanitize_text_field($_POST['_mobooking_subscription_type']));
        }
        
        // Features List
        if (isset($_POST['_mobooking_features'])) {
            update_post_meta($post_id, '_mobooking_features', sanitize_textarea_field($_POST['_mobooking_features']));
        }
    }
    
    /**
     * Add custom column to products list
     */
    public function add_mobooking_product_column($columns) {
        $columns['mobooking_subscription'] = __('MoBooking', 'mobooking');
        return $columns;
    }
    
    /**
     * Populate custom column
     */
    public function populate_mobooking_product_column($column, $post_id) {
        if ($column == 'mobooking_subscription') {
            $is_subscription = get_post_meta($post_id, '_mobooking_subscription', true);
            
            if ($is_subscription == 'yes') {
                $subscription_type = get_post_meta($post_id, '_mobooking_subscription_type', true);
                echo '<span class="mobooking-subscription-badge ' . esc_attr($subscription_type) . '">' . esc_html(ucfirst($subscription_type)) . '</span>';
            } else {
                echo '–';
            }
        }
    }
}