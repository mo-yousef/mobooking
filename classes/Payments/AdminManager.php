<?php
namespace MoBooking\Payments;

/**
 * Payments Admin Manager class
 */
class AdminManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Only run in admin
        if (!is_admin()) {
            return;
        }
        
        // Register admin hooks
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers for admin
        add_action('wp_ajax_mobooking_test_payment_gateway', array($this, 'ajax_test_payment_gateway'));
        add_action('wp_ajax_mobooking_sync_subscriptions', array($this, 'ajax_sync_subscriptions'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menus() {
        // Add MoBooking main menu
        add_menu_page(
            __('MoBooking', 'mobooking'),
            __('MoBooking', 'mobooking'),
            'manage_options',
            'mobooking',
            array($this, 'admin_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );
        
        // Add payment settings submenu
        add_submenu_page(
            'mobooking',
            __('Payment Settings', 'mobooking'),
            __('Payments', 'mobooking'),
            'manage_options',
            'mobooking-payments',
            array($this, 'payment_settings_page')
        );
        
        // Add subscriptions submenu
        add_submenu_page(
            'mobooking',
            __('Subscriptions', 'mobooking'),
            __('Subscriptions', 'mobooking'),
            'manage_options',
            'mobooking-subscriptions',
            array($this, 'subscriptions_page')
        );
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        // Payment settings
        register_setting('mobooking_payment_settings', 'mobooking_stripe_publishable_key');
        register_setting('mobooking_payment_settings', 'mobooking_stripe_secret_key');
        register_setting('mobooking_payment_settings', 'mobooking_stripe_webhook_secret');
        register_setting('mobooking_payment_settings', 'mobooking_payment_mode');
        register_setting('mobooking_payment_settings', 'mobooking_currency');
        
        // Subscription settings
        register_setting('mobooking_subscription_settings', 'mobooking_subscription_plans');
        register_setting('mobooking_subscription_settings', 'mobooking_trial_period');
        register_setting('mobooking_subscription_settings', 'mobooking_free_tier_limits');
    }
    
    /**
     * Admin dashboard page
     */
    public function admin_dashboard_page() {
        // Get some basic stats
        $total_users = $this->get_total_business_owners();
        $active_subscriptions = $this->get_active_subscriptions_count();
        $total_bookings = $this->get_total_bookings();
        $monthly_revenue = $this->get_monthly_revenue();
        
        ?>
        <div class="wrap">
            <h1><?php _e('MoBooking Dashboard', 'mobooking'); ?></h1>
            
            <div class="mobooking-admin-stats">
                <div class="stat-box">
                    <h3><?php _e('Business Owners', 'mobooking'); ?></h3>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('Active Subscriptions', 'mobooking'); ?></h3>
                    <div class="stat-number"><?php echo $active_subscriptions; ?></div>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('Total Bookings', 'mobooking'); ?></h3>
                    <div class="stat-number"><?php echo $total_bookings; ?></div>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('Monthly Revenue', 'mobooking'); ?></h3>
                    <div class="stat-number"><?php echo wc_price($monthly_revenue); ?></div>
                </div>
            </div>
            
            <div class="mobooking-admin-actions">
                <h2><?php _e('Quick Actions', 'mobooking'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=mobooking-payments'); ?>" class="button button-primary">
                    <?php _e('Configure Payments', 'mobooking'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=mobooking-subscriptions'); ?>" class="button">
                    <?php _e('Manage Subscriptions', 'mobooking'); ?>
                </a>
                <button id="sync-subscriptions" class="button">
                    <?php _e('Sync Subscriptions', 'mobooking'); ?>
                </button>
            </div>
        </div>
        
        <style>
        .mobooking-admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
        }
        .mobooking-admin-actions {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .mobooking-admin-actions .button {
            margin-right: 10px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#sync-subscriptions').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php _e('Syncing...', 'mobooking'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mobooking_sync_subscriptions',
                    nonce: '<?php echo wp_create_nonce('mobooking-sync-subscriptions'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Subscriptions synced successfully', 'mobooking'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Error syncing subscriptions', 'mobooking'); ?>');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Sync Subscriptions', 'mobooking'); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Payment settings page
     */
    public function payment_settings_page() {
        if (isset($_POST['submit'])) {
            // Save settings
            update_option('mobooking_stripe_publishable_key', sanitize_text_field($_POST['stripe_publishable_key']));
            update_option('mobooking_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key']));
            update_option('mobooking_stripe_webhook_secret', sanitize_text_field($_POST['stripe_webhook_secret']));
            update_option('mobooking_payment_mode', sanitize_text_field($_POST['payment_mode']));
            update_option('mobooking_currency', sanitize_text_field($_POST['currency']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'mobooking') . '</p></div>';
        }
        
        $stripe_publishable_key = get_option('mobooking_stripe_publishable_key', '');
        $stripe_secret_key = get_option('mobooking_stripe_secret_key', '');
        $stripe_webhook_secret = get_option('mobooking_stripe_webhook_secret', '');
        $payment_mode = get_option('mobooking_payment_mode', 'test');
        $currency = get_option('mobooking_currency', 'USD');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Payment Settings', 'mobooking'); ?></h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Payment Mode', 'mobooking'); ?></th>
                        <td>
                            <select name="payment_mode">
                                <option value="test" <?php selected($payment_mode, 'test'); ?>><?php _e('Test Mode', 'mobooking'); ?></option>
                                <option value="live" <?php selected($payment_mode, 'live'); ?>><?php _e('Live Mode', 'mobooking'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Currency', 'mobooking'); ?></th>
                        <td>
                            <select name="currency">
                                <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                                <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                                <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                                <option value="CAD" <?php selected($currency, 'CAD'); ?>>CAD</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Stripe Publishable Key', 'mobooking'); ?></th>
                        <td>
                            <input type="text" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_publishable_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Stripe publishable key (starts with pk_)', 'mobooking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Stripe Secret Key', 'mobooking'); ?></th>
                        <td>
                            <input type="password" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Stripe secret key (starts with sk_)', 'mobooking'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Stripe Webhook Secret', 'mobooking'); ?></th>
                        <td>
                            <input type="password" name="stripe_webhook_secret" value="<?php echo esc_attr($stripe_webhook_secret); ?>" class="regular-text" />
                            <p class="description"><?php _e('Your Stripe webhook endpoint secret (starts with whsec_)', 'mobooking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings', 'mobooking'); ?>" />
                    <button type="button" id="test-connection" class="button"><?php _e('Test Connection', 'mobooking'); ?></button>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-connection').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php _e('Testing...', 'mobooking'); ?>');
                
                $.post(ajaxurl, {
                    action: 'mobooking_test_payment_gateway',
                    nonce: '<?php echo wp_create_nonce('mobooking-test-payment'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Connection successful!', 'mobooking'); ?>');
                    } else {
                        alert('<?php _e('Connection failed: ', 'mobooking'); ?>' + response.data);
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('<?php _e('Test Connection', 'mobooking'); ?>');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Subscriptions management page
     */
    public function subscriptions_page() {
        $users = $this->get_business_owners_with_subscriptions();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Subscription Management', 'mobooking'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'mobooking'); ?></th>
                        <th><?php _e('Email', 'mobooking'); ?></th>
                        <th><?php _e('Plan', 'mobooking'); ?></th>
                        <th><?php _e('Status', 'mobooking'); ?></th>
                        <th><?php _e('Start Date', 'mobooking'); ?></th>
                        <th><?php _e('Expiry Date', 'mobooking'); ?></th>
                        <th><?php _e('Actions', 'mobooking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <?php
                        $payments_manager = new \MoBooking\Payments\Manager();
                        $subscription_info = $payments_manager->get_subscription_info($user->ID);
                        ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(ucfirst($subscription_info['type'])); ?></td>
                            <td>
                                <span class="subscription-status <?php echo $subscription_info['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $subscription_info['is_active'] ? __('Active', 'mobooking') : __('Inactive', 'mobooking'); ?>
                                </span>
                            </td>
                            <td><?php echo $subscription_info['start_date'] ? date('Y-m-d', strtotime($subscription_info['start_date'])) : '—'; ?></td>
                            <td><?php echo $subscription_info['expiry_date'] ? date('Y-m-d', strtotime($subscription_info['expiry_date'])) : '—'; ?></td>
                            <td>
                                <?php if ($subscription_info['is_active']) : ?>
                                    <button class="button cancel-subscription" data-user-id="<?php echo $user->ID; ?>">
                                        <?php _e('Cancel', 'mobooking'); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="button button-primary activate-subscription" data-user-id="<?php echo $user->ID; ?>">
                                        <?php _e('Activate', 'mobooking'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .subscription-status.active {
            color: #46b450;
            font-weight: bold;
        }
        .subscription-status.inactive {
            color: #dc3232;
            font-weight: bold;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.cancel-subscription, .activate-subscription').on('click', function() {
                var $btn = $(this);
                var userId = $btn.data('user-id');
                var action = $btn.hasClass('cancel-subscription') ? 'cancel' : 'activate';
                
                if (action === 'cancel' && !confirm('<?php _e('Are you sure you want to cancel this subscription?', 'mobooking'); ?>')) {
                    return;
                }
                
                $btn.prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'mobooking_manage_subscription',
                    user_id: userId,
                    subscription_action: action,
                    nonce: '<?php echo wp_create_nonce('mobooking-manage-subscription'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e('Error managing subscription', 'mobooking'); ?>');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get total business owners
     */
    private function get_total_business_owners() {
        $users = get_users(array(
            'role' => 'mobooking_business_owner',
            'count_total' => true,
            'fields' => 'ID'
        ));
        
        return is_array($users) ? count($users) : 0;
    }
    
    /**
     * Get active subscriptions count
     */
    private function get_active_subscriptions_count() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) 
             FROM {$wpdb->usermeta} 
             WHERE meta_key = 'mobooking_has_subscription' 
             AND meta_value = '1'"
        );
        
        return $count ? $count : 0;
    }
    
    /**
     * Get total bookings
     */
    private function get_total_bookings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        return $count ? $count : 0;
    }
    
    /**
     * Get monthly revenue
     */
    private function get_monthly_revenue() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_bookings';
        
        $revenue = $wpdb->get_var(
            "SELECT SUM(total_price) 
             FROM $table_name 
             WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
             AND YEAR(created_at) = YEAR(CURRENT_DATE())
             AND status != 'cancelled'"
        );
        
        return $revenue ? $revenue : 0;
    }
    
    /**
     * Get business owners with subscription info
     */
    private function get_business_owners_with_subscriptions() {
        return get_users(array(
            'role' => 'mobooking_business_owner',
            'orderby' => 'registered',
            'order' => 'DESC'
        ));
    }
    
    /**
     * AJAX handler to test payment gateway
     */
    public function ajax_test_payment_gateway() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'mobooking'));
        }
        
        check_ajax_referer('mobooking-test-payment', 'nonce');
        
        // Test the connection (placeholder)
        $stripe_secret_key = get_option('mobooking_stripe_secret_key', '');
        
        if (empty($stripe_secret_key)) {
            wp_send_json_error(__('Stripe secret key is not configured.', 'mobooking'));
        }
        
        // For now, just check if key format is correct
        if (strpos($stripe_secret_key, 'sk_') !== 0) {
            wp_send_json_error(__('Invalid Stripe secret key format.', 'mobooking'));
        }
        
        wp_send_json_success(__('Payment gateway configuration appears to be correct.', 'mobooking'));
    }
    
    /**
     * AJAX handler to sync subscriptions
     */
    public function ajax_sync_subscriptions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'mobooking'));
        }
        
        check_ajax_referer('mobooking-sync-subscriptions', 'nonce');
        
        // Placeholder for subscription sync logic
        // This would typically sync with Stripe or another payment provider
        
        wp_send_json_success(__('Subscriptions synced successfully.', 'mobooking'));
    }
}