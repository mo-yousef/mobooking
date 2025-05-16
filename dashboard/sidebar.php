<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define dashboard menu items
$menu_items = array(
    'overview' => array(
        'title' => __('Dashboard', 'mobooking'),
        'icon' => 'dashicons-dashboard'
    ),
    'bookings' => array(
        'title' => __('Bookings', 'mobooking'),
        'icon' => 'dashicons-calendar-alt'
    ),
    'services' => array(
        'title' => __('Services', 'mobooking'),
        'icon' => 'dashicons-admin-tools'
    ),
    'discounts' => array(
        'title' => __('Discount Codes', 'mobooking'),
        'icon' => 'dashicons-tag'
    ),
    'areas' => array(
        'title' => __('Service Areas', 'mobooking'),
        'icon' => 'dashicons-location-alt'
    ),
    'settings' => array(
        'title' => __('Settings', 'mobooking'),
        'icon' => 'dashicons-admin-settings'
    )
);

// Check for subscription status
$has_subscription = get_user_meta($user_id, 'mobooking_has_subscription', true);
$subscription_type = get_user_meta($user_id, 'mobooking_subscription_type', true);
$subscription_expiry = get_user_meta($user_id, 'mobooking_subscription_expiry', true);

// If subscription has expired and we have an expiry date
$subscription_expired = false;
if (!$has_subscription && !empty($subscription_expiry) && strtotime($subscription_expiry) < time()) {
    $subscription_expired = true;
}
?>

<div class="mobooking-dashboard-sidebar">
    <div class="sidebar-nav">
        <ul>
            <?php foreach ($menu_items as $slug => $item) : ?>
                <li class="<?php echo $current_section === $slug ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url(home_url('/dashboard/' . $slug . '/')); ?>">
                        <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                        <span class="menu-title"><?php echo esc_html($item['title']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="sidebar-subscription">
        <?php if ($has_subscription) : ?>
            <div class="subscription-status active">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="status-info">
                    <span class="status-label"><?php _e('Subscription Active', 'mobooking'); ?></span>
                    <span class="status-type"><?php echo esc_html(ucfirst($subscription_type)); ?> <?php _e('Plan', 'mobooking'); ?></span>
                </div>
            </div>
        <?php elseif ($subscription_expired) : ?>
            <div class="subscription-status expired">
                <span class="dashicons dashicons-warning"></span>
                <div class="status-info">
                    <span class="status-label"><?php _e('Subscription Expired', 'mobooking'); ?></span>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="renew-link"><?php _e('Renew Now', 'mobooking'); ?></a>
                </div>
            </div>
        <?php else : ?>
            <div class="subscription-status inactive">
                <span class="dashicons dashicons-info"></span>
                <div class="status-info">
                    <span class="status-label"><?php _e('No Active Subscription', 'mobooking'); ?></span>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="signup-link"><?php _e('Choose a Plan', 'mobooking'); ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>