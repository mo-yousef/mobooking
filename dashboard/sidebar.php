<?php
// dashboard/sidebar.php - Fixed sidebar with proper navigation
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current section from URL
$current_section = get_query_var('section', 'overview');

// Define dashboard menu items with proper sections
$menu_items = array(
    'overview' => array(
        'title' => __('Dashboard', 'mobooking'),
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M10 3H3V10H10V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M21 3H14V10H21V3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M21 14H14V21H21V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M10 14H3V21H10V14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>'
    ),
    'bookings' => array(
        'title' => __('Bookings', 'mobooking'),
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>'
    ),
    'booking-form' => array(
        'title' => __('Booking Form', 'mobooking'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 2V4H20.0066C20.5552 4 21 4.44495 21 4.9934V21.0066C21 21.5552 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5551 3 21.0066V4.9934C3 4.44476 3.44495 4 3.9934 4H7V2H17ZM7 6H5V20H19V6H17V8H7V6ZM9 16V18H7V16H9ZM9 13V15H7V13H9ZM9 10V12H7V10H9ZM15 4H9V6H15V4Z"></path></svg>'
    ),
    'services' => array(
        'title' => __('Services', 'mobooking'),
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12M21 16V8.00002C20.9996 7.6493 20.9071 7.30483 20.7315 7.00119C20.556 6.69754 20.3037 6.44539 20 6.27002L13 2.27002C12.696 2.09449 12.3511 2.00208 12 2.00208C11.6489 2.00208 11.304 2.09449 11 2.27002L4 6.27002C3.69626 6.44539 3.44398 6.69754 3.26846 7.00119C3.09294 7.30483 3.00036 7.6493 3 8.00002V16C3.00036 16.3508 3.09294 16.6952 3.26846 16.9989C3.44398 17.3025 3.69626 17.5547 4 17.73L11 21.73C11.304 21.9056 11.6489 21.998 12 21.998C12.3511 21.998 12.696 21.9056 13 21.73L20 17.73C20.3037 17.5547 20.556 17.3025 20.7315 16.9989C20.9071 16.6952 20.9996 16.3508 21 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>'
    ),
    'discounts' => array(
        'title' => __('Discount Codes', 'mobooking'),
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>'
    ),
    'areas' => array(
        'title' => __('Service Areas', 'mobooking'),
        'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8 18L1 22V6L8 2M8 18L16 22M8 18V2M16 22L23 18V2L16 6M16 22V6M16 6L8 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>'
    ),
    'settings' => array(
        'title' => __('Settings', 'mobooking'),
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
<circle cx="12" cy="12" r="3"></circle>
<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
</svg>'
    )
);

// Check for subscription status
$subscription_info = mobooking_get_user_subscription_status($user_id);
?>

<div class="mobooking-dashboard-sidebar">
    <div class="mobooking-dashboard-branding">
        <?php if (!empty($settings->logo_url)) : ?>
            <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php echo esc_attr($settings->company_name); ?>" class="dashboard-logo">
        <?php else : ?>
            <h1 class="dashboard-title"><?php echo esc_html($settings->company_name); ?></h1>
        <?php endif; ?>
    </div>

    <div class="sidebar-nav">
        <ul>
            <?php foreach ($menu_items as $slug => $item) : ?>
                <li class="<?php echo $current_section === $slug ? 'active' : ''; ?>">
                    <a href="<?php echo esc_url($slug === 'overview' ? home_url('/dashboard/') : home_url('/dashboard/' . $slug . '/')); ?>">
                        <?php echo $item['icon']; ?>
                        <span class="menu-title"><?php echo esc_html($item['title']); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="sidebar-subscription expired">
        <?php if ($subscription_info['is_active']) : ?>
            <div class="subscription-status active">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="status-info">
                    <span class="status-label"><?php _e('Subscription Active', 'mobooking'); ?></span>
                    <span class="status-type"><?php echo esc_html(ucfirst($subscription_info['type'])); ?> <?php _e('Plan', 'mobooking'); ?></span>
                </div>
            </div>
        <?php elseif ($subscription_info['is_expired']) : ?>
            <div class="subscription-status expired">
                <span class="dashicons dashicons-warning"></span>
                <div class="status-info">
                    <span class="status-label"><?php _e('Subscription Expired', 'mobooking'); ?></span>
                    <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="renew-link"><?php _e('Renew Now', 'mobooking'); ?></a>
                </div>
            </div>
        <?php else : ?>
            <div class="subscription-status active">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="status-info">
                    <span class="status-label"><?php _e('Subscription Active', 'mobooking'); ?></span>
                    <span class="status-type"><?php echo esc_html(ucfirst($subscription_info['type'])); ?> <?php _e('Plan', 'mobooking'); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>