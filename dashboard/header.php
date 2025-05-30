<?php
/**
 * Modern Dashboard Header
 * Clean and simplified header with essential user menu items
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue dashboard scripts
wp_enqueue_script('mobooking-dashboard-script', MOBOOKING_URL . '/assets/js/dashboard.js', array('jquery'), MOBOOKING_VERSION, true);

// Localize dashboard script
wp_localize_script('mobooking-dashboard-script', 'mobooking_dashboard', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking-dashboard-nonce'),
    'current_section' => $current_section
));

// Get current user information
$current_user = wp_get_current_user();
$user_avatar_url = get_avatar_url($current_user->ID, array('size' => 40));
$user_display_name = $current_user->display_name ?: $current_user->user_login;
$user_email = $current_user->user_email;

// Get current section
$current_section = get_query_var('section', 'overview');
$section_titles = array(
    'overview' => __('Dashboard', 'mobooking'),
    'bookings' => __('Bookings', 'mobooking'),
    'booking-form' => __('Booking Form', 'mobooking'),
    'services' => __('Services', 'mobooking'),
    'discounts' => __('Discounts', 'mobooking'),
    'areas' => __('Service Areas', 'mobooking'),
    'settings' => __('Settings', 'mobooking'),
    'analytics' => __('Analytics', 'mobooking'),
    'customers' => __('Customers', 'mobooking'),
);

$current_title = isset($section_titles[$current_section]) ? $section_titles[$current_section] : __('Dashboard', 'mobooking');

// Get user role
$user_roles = $current_user->roles;
$user_role_display = 'User';
if (in_array('administrator', $user_roles)) {
    $user_role_display = 'Administrator';
} elseif (in_array('mobooking_business_owner', $user_roles)) {
    $user_role_display = 'Business Owner';
}

// Get greeting based on time
$current_hour = (int) date('H');
if ($current_hour < 12) {
    $greeting = __('Good morning', 'mobooking');
} elseif ($current_hour < 17) {
    $greeting = __('Good afternoon', 'mobooking');
} else {
    $greeting = __('Good evening', 'mobooking');
}
?>

<header class="mobooking-dashboard-header">
    <!-- Left Section: Title & Breadcrumb -->
    <div class="header-left">
        <div class="header-title-section">
            <?php if ($current_section !== 'overview') : ?>
                <nav class="header-breadcrumb" aria-label="<?php _e('Breadcrumb', 'mobooking'); ?>">
                    <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="breadcrumb-link">
                        <?php _e('Dashboard', 'mobooking'); ?>
                    </a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current"><?php echo esc_html($current_title); ?></span>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Section: User Menu & Actions -->
    <div class="header-right">
        <!-- Quick Action Button -->
        <a href="<?php echo esc_url(home_url('/booking/' . $current_user->user_login)); ?>" 
           class="quick-action-btn" 
           target="_blank" 
           rel="noopener noreferrer"
           title="<?php _e('View your public booking form', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 17L17 7"/>
                <path d="M7 7h10v10"/>
            </svg>
            <span><?php _e('View Form', 'mobooking'); ?></span>
        </a>

        <!-- Notifications -->
        <div class="header-notifications">
            <button type="button" class="notification-btn" 
                    title="<?php _e('Notifications', 'mobooking'); ?>"
                    aria-expanded="false" 
                    aria-haspopup="true">
                <svg class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="notification-badge">3</span>
            </button>
            
            <!-- Notification Dropdown -->
            <div class="notification-dropdown" role="menu" aria-hidden="true">
                <div class="notification-header">
                    <h3><?php _e('Recent Notifications', 'mobooking'); ?></h3>
                    <button type="button" class="mark-all-read" title="<?php _e('Mark all as read', 'mobooking'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="notification-list">
                    <!-- New Booking Notification -->
                    <div class="notification-item unread">
                        <div class="notification-icon-wrapper booking">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php _e('New Booking Request', 'mobooking'); ?></div>
                            <div class="notification-message"><?php _e('Sarah Johnson requested house cleaning for Dec 15', 'mobooking'); ?></div>
                            <div class="notification-time"><?php _e('2 minutes ago', 'mobooking'); ?></div>
                        </div>
                    </div>

                    <!-- Payment Notification -->
                    <div class="notification-item unread">
                        <div class="notification-icon-wrapper payment">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                <line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php _e('Payment Received', 'mobooking'); ?></div>
                            <div class="notification-message"><?php _e('$125.00 payment confirmed for booking #1247', 'mobooking'); ?></div>
                            <div class="notification-time"><?php _e('1 hour ago', 'mobooking'); ?></div>
                        </div>
                    </div>

                    <!-- System Notification -->
                    <div class="notification-item">
                        <div class="notification-icon-wrapper system">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?php _e('Subscription Renewed', 'mobooking'); ?></div>
                            <div class="notification-message"><?php _e('Your Pro plan has been renewed until Jan 15, 2025', 'mobooking'); ?></div>
                            <div class="notification-time"><?php _e('3 hours ago', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="notification-footer">
                    <a href="<?php echo esc_url(home_url('/dashboard/notifications/')); ?>" class="view-all-link">
                        <?php _e('View All Notifications', 'mobooking'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="header-user-menu">
            <button type="button" class="user-menu-trigger" 
                    aria-expanded="false" 
                    aria-haspopup="true" 
                    title="<?php echo esc_attr(sprintf(__('User menu for %s', 'mobooking'), $user_display_name)); ?>">
                <img src="<?php echo esc_url($user_avatar_url); ?>" 
                     alt="<?php echo esc_attr($user_display_name); ?>" 
                     class="user-avatar">
                <div class="user-info">
                    <span class="user-name"><?php echo esc_html($user_display_name); ?></span>
                    <span class="user-role"><?php echo esc_html($user_role_display); ?></span>
                </div>
                <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </button>

            <!-- User Dropdown Menu (Simplified) -->
            <div class="user-dropdown-menu" role="menu" aria-hidden="true">
                <!-- User Info Header -->
                <div class="user-dropdown-header">
                    <div class="user-info-full">
                        <img src="<?php echo esc_url($user_avatar_url); ?>" 
                             alt="<?php echo esc_attr($user_display_name); ?>" 
                             class="user-avatar-large">
                        <div class="user-details">
                            <h4 class="user-name-full"><?php echo esc_html($user_display_name); ?></h4>
                            <p class="user-email"><?php echo esc_html($user_email); ?></p>
                        </div>
                    </div>

                </div>

                <!-- Essential Menu Items -->
                <div class="user-dropdown-body">
                    <a href="<?php echo esc_url(home_url('/dashboard/overview/')); ?>" class="dropdown-link" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9,22 9,12 15,12 15,22"/>
                        </svg>
                        <span><?php _e('Dashboard', 'mobooking'); ?></span>
                    </a>

                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="dropdown-link" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span><?php _e('My Bookings', 'mobooking'); ?></span>
                    </a>

                    <a href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>" class="dropdown-link" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <span><?php _e('Settings', 'mobooking'); ?></span>
                    </a>

                    <a href="<?php echo esc_url(home_url('/dashboard/billing/')); ?>" class="dropdown-link" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        <span><?php _e('Billing', 'mobooking'); ?></span>
                    </a>

                    <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="dropdown-link" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span><?php _e('Profile', 'mobooking'); ?></span>
                    </a>
                </div>

                <!-- Logout -->
                <div class="user-dropdown-footer">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-btn" role="menuitem">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 22C4.44772 22 4 21.5523 4 21V3C4 2.44772 4.44772 2 5 2H19C19.5523 2 20 2.44772 20 3V6H18V4H6V20H18V18H20V21C20 21.5523 19.5523 22 19 22H5ZM18 16V13H11V11H18V8L23 12L18 16Z"/>
                        </svg>
                        <span><?php _e('Sign Out', 'mobooking'); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
:root {
    --header-height: 3.5rem;
    --primary-color: #4f46e5;
    --primary-hover: #4338ca;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
}

/* Header Base */
.mobooking-dashboard-header {
    position: fixed;
    top: 0;
    right: 0;
    left: var(--sidebar-width, 16rem);
    height: var(--header-height);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    z-index: 40;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.admin-bar .mobooking-dashboard-header {
    top: 32px;
}

/* Header Sections */
.header-left,
.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Title Section */
.header-title-section {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.header-page-title {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1.2;
}

.header-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--gray-600);
}

.breadcrumb-link {
    color: var(--gray-600);
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb-link:hover {
    color: hsl(var(--primary));
}

.breadcrumb-separator {
    color: var(--gray-400);
}

.breadcrumb-current {
    color: var(--gray-900);
    font-weight: 500;
}

/* Quick Action Button */
.quick-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    background: transparent;
    color: var(--gray-700);
    text-decoration: none;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.quick-action-btn:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary));
    color: hsl(var(--primary));
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.quick-action-btn svg {
    width: 0.875rem;
    height: 0.875rem;
}

/* Notifications */
.header-notifications {
    position: relative;
}

.notification-btn {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    background: var(--gray-100);
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-btn:hover {
    background: var(--gray-200);
}

.notification-icon {
    width: 1.125rem;
    height: 1.125rem;
    stroke: var(--gray-600);
}

.notification-badge {
    position: absolute;
    top: -0.25rem;
    right: -0.25rem;
    min-width: 1.125rem;
    height: 1.125rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--danger-color);
    color: white;
    border: 2px solid white;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1;
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    width: 22rem;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-0.5rem);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 50;
}

.notification-btn[aria-expanded="true"] + .notification-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem 0.75rem;
    border-bottom: 1px solid var(--gray-200);
}

.notification-header h3 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--gray-900);
}

.mark-all-read {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background 0.2s ease;
}

.mark-all-read:hover {
    background: var(--gray-100);
}

.mark-all-read svg {
    width: 1rem;
    height: 1rem;
    stroke: var(--gray-500);
}

.notification-list {
    max-height: 20rem;
    overflow-y: auto;
    padding: 0.5rem 0;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    transition: background 0.2s ease;
    cursor: pointer;
}

.notification-item:hover {
    background: hsl(var(--accent));
}

.notification-item.unread {
    background: rgba(79, 70, 229, 0.02);
    border-left: 3px solid hsl(var(--primary));
}

.notification-icon-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    flex-shrink: 0;
}

.notification-icon-wrapper.booking {
    background: rgba(79, 70, 229, 0.1);
    color: hsl(var(--primary));
}

.notification-icon-wrapper.payment {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.notification-icon-wrapper.system {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info-color);
}

.notification-icon-wrapper svg {
    width: 1rem;
    height: 1rem;
    stroke: currentColor;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 0.125rem;
    line-height: 1.3;
}

.notification-message {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.notification-time {
    font-size: 0.6875rem;
    color: var(--gray-400);
    line-height: 1;
}

.notification-footer {
    padding: 0.75rem 1.25rem;
    border-top: 1px solid var(--gray-200);
}

.view-all-link {
    display: block;
    text-align: center;
    font-size: 0.8125rem;
    font-weight: 500;
    color: hsl(var(--primary));
    text-decoration: none;
    padding: 0.25rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.view-all-link:hover {
    background: hsl(var(--accent));
    color: var(--primary-hover);
}

/* User Menu */
.header-user-menu {
    position: relative;
}

.user-menu-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem;
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.user-menu-trigger:hover {
    background: var(--gray-100);
}

.user-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 9999px;
    border: 2px solid var(--gray-200);
    object-fit: cover;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.user-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--gray-900);
    line-height: 1.2;
}

.user-role {
    font-size: 0.6875rem;
    color: var(--gray-500);
    line-height: 1.2;
}

.dropdown-arrow {
    width: 0.875rem;
    height: 0.875rem;
    stroke: var(--gray-500);
    transition: transform 0.2s ease;
}

.user-menu-trigger[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

/* User Dropdown */
.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    min-width: 18rem;
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-0.5rem);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 50;
}

.user-menu-trigger[aria-expanded="true"] + .user-dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Dropdown Header */
.user-dropdown-header {
    padding: 1.25rem;
    border-bottom: 1px solid var(--gray-200);
}

.user-info-full {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    margin-bottom: 0px;
}

.user-avatar-large {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 9999px;
    border: 2px solid var(--gray-200);
    object-fit: cover;
}

.user-details {
    flex: 1;
}

.user-name-full {
    margin: 0 0 0.125rem 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--gray-900);
}

.user-email {
    margin: 0 0 0 0;
    font-size: 0.8125rem;
    color: var(--gray-600);
}

.user-role-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.1875rem 0.625rem;
    background: hsl(var(--primary));
    color: white;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 500;
}

/* Dropdown Body */
.user-dropdown-body {
    padding: 0.75rem;
}

.dropdown-link {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.625rem 0.875rem;
    color: var(--gray-700);
    text-decoration: none;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 0.125rem;
}

.dropdown-link:hover {
    background: hsl(var(--accent));
    color: var(--gray-900);
}

.dropdown-link svg {
    width: 1rem;
    height: 1rem;
    stroke: hsl(var(--primary));
    flex-shrink: 0;
}

/* Dropdown Footer */
.user-dropdown-footer {
    padding: 0.75rem 1.25rem;
    border-top: 1px solid var(--gray-200);
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem 0.875rem;
    background: var(--danger-color);
    color: white;
    text-decoration: none;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.logout-btn:hover {
    background: #dc2626;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

.logout-btn svg {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .mobooking-dashboard-header {
        left: 0;
        padding: 0 1rem;
    }
    
    .header-left,
    .header-right {
        gap: 1rem;
    }
    
    .user-info {
        display: none;
    }
    
    .user-dropdown-menu {
        min-width: 18rem;
        right: -1rem;
    }
    
    .quick-action-btn span {
        display: none;
    }
    
    .header-page-title {
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .user-dropdown-menu {
        position: fixed;
        top: var(--header-height);
        left: 1rem;
        right: 1rem;
        min-width: auto;
        max-width: none;
    }
    
    .admin-bar .user-dropdown-menu {
        top: calc(var(--header-height) + 32px);
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .mobooking-dashboard-header {
        background: rgba(17, 24, 39, 0.95);
        border-bottom-color: var(--gray-700);
    }
    
    .header-page-title {
        color: white;
    }
    
    .breadcrumb-current {
        color: var(--gray-200);
    }
    
    .notification-btn {
        background: var(--gray-800);
    }
    
    .notification-btn:hover {
        background: var(--gray-700);
    }
    
    .user-menu-trigger:hover {
        background: var(--gray-800);
    }
    
    .user-dropdown-menu {
        background: var(--gray-800);
        border-color: var(--gray-700);
    }
    
    .user-dropdown-header {
        border-bottom-color: var(--gray-700);
    }
    
    .user-name-full {
        color: white;
    }
    
    .dropdown-link {
        color: var(--gray-300);
    }
    
    .dropdown-link:hover {
        background: var(--gray-700);
        color: white;
    }
    
    .user-dropdown-footer {
        border-top-color: var(--gray-700);
    }
}

/* Smooth animations */
.mobooking-dashboard-header * {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Focus states for accessibility */
/* .notification-btn:focus,
.user-menu-trigger:focus,
.quick-action-btn:focus {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

.dropdown-link:focus,
.logout-btn:focus {
    outline: 2px solid hsl(var(--primary));
    outline-offset: -2px;
} */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User menu toggle
    const userMenuTrigger = document.querySelector('.user-menu-trigger');
    const userDropdownMenu = document.querySelector('.user-dropdown-menu');
    
    if (userMenuTrigger && userDropdownMenu) {
        userMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // Toggle menu
            this.setAttribute('aria-expanded', !isExpanded);
            userDropdownMenu.setAttribute('aria-hidden', isExpanded);
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuTrigger.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userMenuTrigger.setAttribute('aria-expanded', 'false');
                userDropdownMenu.setAttribute('aria-hidden', 'true');
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userMenuTrigger.setAttribute('aria-expanded', 'false');
                userDropdownMenu.setAttribute('aria-hidden', 'true');
            }
        });
    }
    
    // Notification button (placeholder functionality)
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // Add your notification logic here
            console.log('Notifications clicked');
        });
    }
});
</script>