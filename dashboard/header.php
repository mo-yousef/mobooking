<?php
/**
 * MoBooking Dashboard Header
 * Clean, responsive, and expandable header component
 * 
 * @package MoBooking
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user and settings
$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Get user settings with error handling
try {
    $settings_manager = new \MoBooking\Database\SettingsManager();
    $settings = $settings_manager->get_settings($user_id);
} catch (Exception $e) {
    // Fallback settings
    $settings = (object) array(
        'company_name' => $current_user->display_name . "'s Business",
        'logo_url' => '',
        'primary_color' => '#3b82f6'
    );
}

// Get current section
$current_section = get_query_var('section', 'overview');

// Get subscription status
$subscription_info = mobooking_get_user_subscription_status($user_id);

// Check for any pending notifications
$pending_bookings_count = 0;
try {
    // Attempt to load and use Bookings Manager
    $bookings_manager = new \MoBooking\Bookings\Manager();
    $pending_bookings_count = $bookings_manager->count_user_bookings($user_id, 'pending');
} catch (Throwable $e) { // Catching Throwable is better for broader errors including ParseError
    // Silently fail or log the error if WP_DEBUG is on
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking Error in dashboard/header.php (BookingsManager): ' . $e->getMessage());
    }
    // $pending_bookings_count remains 0
}

// Determine greeting based on time
$current_hour = date('H');
if ($current_hour < 12) {
    $greeting = __('Good morning', 'mobooking');
} elseif ($current_hour < 18) {
    $greeting = __('Good afternoon', 'mobooking');
} else {
    $greeting = __('Good evening', 'mobooking');
}
?>

<header class="mobooking-dashboard-header" id="dashboard-header">
    <div class="header-container">
        <!-- Left Section: Logo & Navigation Toggle -->
        <div class="header-left">
            <!-- Mobile Menu Toggle -->
            <button type="button" class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="<?php _e('Toggle navigation menu', 'mobooking'); ?>">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <!-- Logo/Brand
            <div class="header-brand">
                <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="brand-link">
                    <?php if (!empty($settings->logo_url)) : ?>
                        <img src="<?php echo esc_url($settings->logo_url); ?>" 
                             alt="<?php echo esc_attr($settings->company_name); ?>" 
                             class="brand-logo">
                    <?php else : ?>
                        <div class="brand-text">
                            <span class="brand-name"><?php echo esc_html($settings->company_name); ?></span>
                            <span class="brand-subtitle"><?php _e('Dashboard', 'mobooking'); ?></span>
                        </div>
                    <?php endif; ?>
                </a>
            </div> -->

            <!-- Breadcrumb Navigation (Desktop Only) -->
            <nav class="header-breadcrumb" aria-label="<?php _e('Page navigation', 'mobooking'); ?>">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="breadcrumb-link">
                            <svg class="breadcrumb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9L12 2L21 9V20C21 20.5523 20.5523 21 20 21H4C3.44772 21 3 20.5523 3 20V9Z"/>
                                <polyline points="9,22 9,12 15,12 15,22"/>
                            </svg>
                            <span><?php _e('Dashboard', 'mobooking'); ?></span>
                        </a>
                    </li>
                    <?php if ($current_section !== 'overview') : ?>
                        <li class="breadcrumb-separator">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9,18 15,12 9,6"/>
                            </svg>
                        </li>
                        <li class="breadcrumb-item current">
                            <span class="breadcrumb-current">
                                <?php
                                $section_titles = array(
                                    'services' => __('Services', 'mobooking'),
                                    'bookings' => __('Bookings', 'mobooking'),
                                    'booking-form' => __('Booking Form', 'mobooking'),
                                    'discounts' => __('Discounts', 'mobooking'),
                                    'areas' => __('Service Areas', 'mobooking'),
                                    'settings' => __('Settings', 'mobooking')
                                );
                                echo esc_html($section_titles[$current_section] ?? ucfirst($current_section));
                                ?>
                            </span>
                        </li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>

        <!-- Center Section: Search & Quick Actions (Desktop) -->
        <div class="header-center">
            <!-- Quick Search -->
            <div class="header-search">
                <div class="search-container">
                    <input type="text" 
                           class="search-input" 
                           placeholder="<?php _e('Search bookings, customers...', 'mobooking'); ?>"
                           id="header-search"
                           autocomplete="off">
                    <button type="button" class="search-button" aria-label="<?php _e('Search', 'mobooking'); ?>">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </button>
                </div>
                <!-- Search Results Dropdown -->
                <div class="search-results" id="search-results" style="display: none;">
                    <div class="search-results-content">
                        <!-- Results will be populated via AJAX -->
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Section: Notifications & User Menu -->
        <div class="header-right">
            <!-- Notifications -->
            <div class="header-notifications">
                <button type="button" 
                        class="notifications-toggle" 
                        id="notifications-toggle"
                        aria-label="<?php _e('View notifications', 'mobooking'); ?>"
                        <?php if ($pending_bookings_count > 0) : ?>data-count="<?php echo $pending_bookings_count; ?>"<?php endif; ?>>
                    <svg class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <?php if ($pending_bookings_count > 0) : ?>
                        <span class="notification-badge"><?php echo $pending_bookings_count; ?></span>
                    <?php endif; ?>
                </button>

                <!-- Notifications Dropdown -->
                <div class="notifications-dropdown" id="notifications-dropdown" style="display: none;">
                    <div class="dropdown-header">
                        <h3 class="dropdown-title"><?php _e('Notifications', 'mobooking'); ?></h3>
                        <?php if ($pending_bookings_count > 0) : ?>
                            <span class="notifications-count"><?php echo $pending_bookings_count; ?> <?php _e('new', 'mobooking'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dropdown-content">
                        <?php if ($pending_bookings_count > 0) : ?>
                            <div class="notification-item">
                                <div class="notification-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                                    </svg>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title">
                                        <?php printf(_n('%d pending booking', '%d pending bookings', $pending_bookings_count, 'mobooking'), $pending_bookings_count); ?>
                                    </div>
                                    <div class="notification-desc">
                                        <?php _e('Review and confirm customer bookings', 'mobooking'); ?>
                                    </div>
                                </div>
                                <a href="<?php echo esc_url(add_query_arg('section', 'bookings')); ?>" class="notification-action">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9,18 15,12 9,6"/>
                                    </svg>
                                </a>
                            </div>
                        <?php else : ?>
                            <div class="notification-empty">
                                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>
                                <p><?php _e('No new notifications', 'mobooking'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dropdown-footer">
                        <a href="<?php echo esc_url(add_query_arg('section', 'bookings')); ?>" class="view-all-link">
                            <?php _e('View all bookings', 'mobooking'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Subscription Status -->
            <div class="header-subscription">
                <div class="subscription-indicator <?php echo $subscription_info['is_active'] ? 'active' : 'inactive'; ?>"
                     title="<?php echo $subscription_info['is_active'] ? __('Subscription Active', 'mobooking') : __('No Active Subscription', 'mobooking'); ?>">
                    <?php if ($subscription_info['is_active']) : ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                        </svg>
                    <?php else : ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Menu -->
            <div class="header-user-menu">
                <button type="button" 
                        class="user-menu-toggle" 
                        id="user-menu-toggle"
                        aria-label="<?php _e('User menu', 'mobooking'); ?>"
                        aria-expanded="false">
                    <div class="user-avatar">
                        <?php
                        $avatar_url = get_avatar_url($current_user->ID, array('size' => 32));
                        if ($avatar_url) :
                        ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" 
                                 alt="<?php echo esc_attr($current_user->display_name); ?>"
                                 class="avatar-image">
                        <?php else : ?>
                            <span class="avatar-initials">
                                <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 2))); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-greeting"><?php echo $greeting; ?></span>
                        <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                    </div>
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 12,15 18,9"/>
                    </svg>
                </button>

                <!-- User Dropdown Menu -->
                <div class="user-dropdown" id="user-dropdown" style="display: none;">
                    <div class="dropdown-header">
                        <div class="user-profile">
                            <div class="profile-avatar">
                                <?php if ($avatar_url) : ?>
                                    <img src="<?php echo esc_url($avatar_url); ?>" 
                                         alt="<?php echo esc_attr($current_user->display_name); ?>">
                                <?php else : ?>
                                    <span class="avatar-initials">
                                        <?php echo esc_html(strtoupper(substr($current_user->display_name, 0, 2))); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <div class="profile-name"><?php echo esc_html($current_user->display_name); ?></div>
                                <div class="profile-email"><?php echo esc_html($current_user->user_email); ?></div>
                                <div class="profile-business"><?php echo esc_html($settings->company_name); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dropdown-menu">
                        <a href="<?php echo esc_url(add_query_arg('section', 'overview')); ?>" class="menu-item">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 3H3V10H10V3Z"/>
                                <path d="M21 3H14V10H21V3Z"/>
                                <path d="M21 14H14V21H21V14Z"/>
                                <path d="M10 14H3V21H10V14Z"/>
                            </svg>
                            <span><?php _e('Dashboard', 'mobooking'); ?></span>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('section', 'settings')); ?>" class="menu-item">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <span><?php _e('Settings', 'mobooking'); ?></span>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('section', 'booking-form')); ?>" class="menu-item">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                            </svg>
                            <span><?php _e('Booking Link', 'mobooking'); ?></span>
                        </a>
                        
                        <div class="menu-separator"></div>
                        
                        <?php if (!$subscription_info['is_active']) : ?>
                            <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="menu-item upgrade">
                                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <span><?php _e('Upgrade Plan', 'mobooking'); ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="menu-item logout">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16,17 21,12 16,7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span><?php _e('Logout', 'mobooking'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav-overlay" id="mobile-nav-overlay" style="display: none;">
        <div class="mobile-nav-content">
            <!-- Mobile Search -->
            <div class="mobile-search">
                <div class="search-container">
                    <input type="text" 
                           class="search-input" 
                           placeholder="<?php _e('Search...', 'mobooking'); ?>"
                           id="mobile-search">
                    <button type="button" class="search-button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Quick Actions -->
            <div class="mobile-quick-actions">
                <a href="<?php echo esc_url(add_query_arg(array('section' => 'services', 'view' => 'add'))); ?>" class="mobile-action">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="16"/>
                        <line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    <span><?php _e('New Service', 'mobooking'); ?></span>
                </a>
            </div>
        </div>
    </div>
</header>

<style>
/* Dashboard Header Styles */
.mobooking-dashboard-header {
    position: sticky;
    top: 0;
    z-index: 2;
    background: hsl(var(--background));
    border-bottom: 1px solid hsl(var(--border));
    backdrop-filter: blur(8px);
    background-color: hsl(var(--background) / 0.95);
}

.header-container {
    display: flex
;
    align-items: center;
    justify-content: space-between;
    padding: 0px;
    max-width: 100%;
    gap: 1rem;
    width: 100%;

}

/* Header Left Section */
.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    padding: 0.5rem;
    background: none;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    gap: 0.25rem;
    transition: all 0.2s ease;
}

.mobile-menu-toggle:hover {
    background: hsl(var(--accent));
}

.hamburger-line {
    display: block;
    width: 1.25rem;
    height: 2px;
    background: hsl(var(--foreground));
    border-radius: 1px;
    transition: all 0.3s ease;
}

.mobile-menu-toggle.active .hamburger-line:nth-child(1) {
    transform: rotate(45deg) translate(0.35rem, 0.35rem);
}

.mobile-menu-toggle.active .hamburger-line:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active .hamburger-line:nth-child(3) {
    transform: rotate(-45deg) translate(0.35rem, -0.35rem);
}

/* Brand */
.header-brand {
    flex-shrink: 0;
}

.brand-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    gap: 0.75rem;
}

.brand-logo {
    height: 2rem;
    width: auto;
    border-radius: calc(var(--radius) - 2px);
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    line-height: 1.2;
}

.brand-subtitle {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1;
}

/* Breadcrumb */
.header-breadcrumb {
    display: none;
}

.breadcrumb-list {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
}

.breadcrumb-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: calc(var(--radius) - 2px);
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.breadcrumb-link:hover {
    background: hsl(var(--accent));
    color: hsl(var(--foreground));
}

.breadcrumb-icon {
    width: 1rem;
    height: 1rem;
}

.breadcrumb-separator {
    width: 1rem;
    height: 1rem;
    color: hsl(var(--muted-foreground));
    opacity: 0.5;
}

.breadcrumb-current {
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    font-weight: 500;
}

/* Header Center */
.header-center {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 2;
    justify-content: center;
    max-width: 32rem;
}

/* Search */
.header-search {
    position: relative;
    flex: 1;
    max-width: 24rem;
}

.search-container {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.search-button {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.25rem;
    background: none;
    border: none;
    cursor: pointer;
    color: hsl(var(--muted-foreground));
    border-radius: calc(var(--radius) - 2px);
    transition: all 0.2s ease;
}

.search-button:hover {
    background: hsl(var(--accent));
    color: hsl(var(--foreground));
}

.search-icon {
    width: 1rem;
    height: 1rem;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 0.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    z-index: 50;
    max-height: 20rem;
    overflow-y: auto;
}

.search-results-content {
    padding: 0.5rem;
}

/* Quick Actions */
.header-quick-actions {
    display: flex;
    gap: 0.5rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.quick-action-btn:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
}

.quick-action-btn svg {
    width: 1rem;
    height: 1rem;
}

.action-text {
    display: none;
}

/* Header Right */
.header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

/* Notifications */
.header-notifications {
    position: relative;
}

.notifications-toggle {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: none;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.notifications-toggle:hover {
    background: hsl(var(--accent));
}

.notification-icon {
    width: 1.25rem;
    height: 1.25rem;
    color: hsl(var(--muted-foreground));
}

.notification-badge {
    position: absolute;
    top: -0.25rem;
    right: -0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.25rem;
    padding: 0 0.25rem;
    background: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
    line-height: 1;
}

.notifications-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    width: 20rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    z-index: 50;
}

.dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
}

.dropdown-title {
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin: 0;
}

.notifications-count {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    background: hsl(var(--muted));
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
}

.dropdown-content {
    max-height: 16rem;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid hsl(var(--border));
    transition: all 0.2s ease;
}

.notification-item:hover {
    background: hsl(var(--accent));
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item .notification-icon {
    flex-shrink: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border-radius: var(--radius);
}
.notification-item .notification-icon svg {
    width: 20px;
    height: 20px;
}
.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--foreground));
    line-height: 1.4;
}

.notification-desc {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.3;
    margin-top: 0.25rem;
}

.notification-action {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    border-radius: calc(var(--radius) - 2px);
    transition: all 0.2s ease;
}

.notification-action:hover {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.notification-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    text-align: center;
}

.empty-icon {
    width: 2.5rem;
    height: 2.5rem;
    color: hsl(var(--muted-foreground));
    opacity: 0.5;
    margin-bottom: 0.75rem;
}

.notification-empty p {
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
    margin: 0;
}

.dropdown-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.3);
}

.view-all-link {
    display: block;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--primary));
    text-decoration: none;
    transition: color 0.2s ease;
}

.view-all-link:hover {
    color: hsl(var(--primary) / 0.8);
}

/* Subscription Status */
.header-subscription {
    display: flex;
    align-items: center;
}

.subscription-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.subscription-indicator.active {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.subscription-indicator.inactive {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.subscription-indicator svg {
    width: 1rem;
    height: 1rem;
}

/* User Menu */
.header-user-menu {
    position: relative;
}

.user-menu-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 5px 10px;
    justify-content: center;
    background: none;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.user-menu-toggle:hover {
    background: hsl(var(--accent));
}

.user-avatar {
    position: relative;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    font-size: 0.75rem;
    font-weight: 600;
}

.user-info {
    display: none;
    flex-direction: column;
    min-width: 0;
}

.user-greeting {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--foreground));
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dropdown-arrow {
    width: 1rem;
    height: 1rem;
    color: hsl(var(--muted-foreground));
    transition: transform 0.2s ease;
    display: none;
}

.user-menu-toggle[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    width: 16rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    z-index: 50;
}

.user-dropdown .dropdown-header {
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.profile-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar .avatar-initials {
    font-size: 0.875rem;
}

.profile-info {
    flex: 1;
    min-width: 0;
}

.profile-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    line-height: 1.2;
}

.profile-email {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.2;
    margin-top: 0.125rem;
}

.profile-business {
    font-size: 0.75rem;
    color: hsl(var(--primary));
    font-weight: 500;
    line-height: 1.2;
    margin-top: 0.125rem;
}

.dropdown-menu {
    padding: 0.5rem;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.5rem 0.75rem;
    color: hsl(var(--foreground));
    text-decoration: none;
    font-size: 0.875rem;
    border-radius: calc(var(--radius) - 2px);
    transition: all 0.2s ease;
}

.menu-item:hover {
    background: hsl(var(--accent));
}

.menu-item.upgrade {
    color: hsl(var(--primary));
}

.menu-item.logout {
    color: hsl(var(--destructive));
}

.menu-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.menu-separator {
    height: 1px;
    background: hsl(var(--border));
    margin: 0.5rem 0;
}

/* Mobile Navigation */
.mobile-nav-overlay {
    position: fixed;
    inset: 0;
    background: rgb(0 0 0 / 0.5);
    backdrop-filter: blur(4px);
    z-index: 999;
}

.mobile-nav-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 20rem;
    height: 100%;
    background: hsl(var(--card));
    padding: 1rem;
    box-shadow: 4px 0 6px -1px rgb(0 0 0 / 0.1);
    overflow-y: auto;
}

.mobile-search {
    margin-bottom: 1.5rem;
}

.mobile-quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mobile-action {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: hsl(var(--accent));
    color: hsl(var(--foreground));
    text-decoration: none;
    border-radius: var(--radius);
    transition: all 0.2s ease;
}

.mobile-action:hover {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.mobile-action svg {
    width: 1.25rem;
    height: 1.25rem;
}

/* Responsive Design */
@media (min-width: 768px) {
    .header-breadcrumb {
        display: block;
    }
    
    .action-text {
        display: inline;
    }
    
    .user-info {
        display: flex;
    }
    
    .dropdown-arrow {
        display: block;
    }
}

@media (min-width: 1024px) {
    .header-center {
        display: flex;
    }
}

@media (max-width: 1023px) {
    .header-center {
        display: none;
    }
}

@media (max-width: 767px) {
    .mobile-menu-toggle {
        display: flex;
    }
    
    .header-container {
        padding: 0.75rem 1rem;
    }
    
    .header-left {
        gap: 0.75rem;
    }
    
    .brand-name {
        font-size: 1rem;
    }
    
    .brand-subtitle {
        display: none;
    }
    
    .user-info {
        display: none;
    }
    
    .dropdown-arrow {
        display: none;
    }
    
    .header-quick-actions {
        display: none;
    }
    
    .notifications-dropdown,
    .user-dropdown {
        width: 16rem;
        max-width: calc(100vw - 2rem);
    }
}

@media (max-width: 480px) {
    .header-container {
        padding: 0.5rem 0.75rem;
        gap: 0.5rem;
    }
    
    .header-right {
        gap: 0.5rem;
    }
    
    .brand-text {
        display: none;
    }
    
    .subscription-indicator {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .notifications-toggle,
    .user-menu-toggle {
        width: 2.25rem;
        height: 2.25rem;
    }
    
    .user-avatar {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .mobile-nav-content {
        width: 100%;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.2s ease-in-out;
}

.slide-up {
    animation: slideUp 0.2s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0; 
        transform: translateY(0.5rem); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

/* Loading States */
.header-loading {
    pointer-events: none;
    opacity: 0.6;
}

.header-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}


/* High Contrast Mode */
@media (prefers-contrast: high) {
    .header-container {
        border-bottom: 2px solid;
    }
    
    .mobile-menu-toggle,
    .notifications-toggle,
    .user-menu-toggle {
        border: 2px solid;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Print Styles */
@media print {
    .mobooking-dashboard-header {
        display: none;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🎯 Dashboard Header initializing...');
    
    // Mobile menu toggle
    $('#mobile-menu-toggle').on('click', function() {
        const $toggle = $(this);
        const $overlay = $('#mobile-nav-overlay');
        
        $toggle.toggleClass('active');
        
        if ($toggle.hasClass('active')) {
            $overlay.fadeIn(200);
            $('body').addClass('mobile-nav-open');
        } else {
            $overlay.fadeOut(200);
            $('body').removeClass('mobile-nav-open');
        }
    });
    
    // Close mobile nav when clicking overlay
    $('#mobile-nav-overlay').on('click', function(e) {
        if (e.target === this) {
            $('#mobile-menu-toggle').removeClass('active');
            $(this).fadeOut(200);
            $('body').removeClass('mobile-nav-open');
        }
    });
    
    // Notifications dropdown
    $('#notifications-toggle').on('click', function(e) {
        e.stopPropagation();
        const $dropdown = $('#notifications-dropdown');
        const $userDropdown = $('#user-dropdown');
        
        // Close user dropdown if open
        $userDropdown.hide();
        $('#user-menu-toggle').attr('aria-expanded', 'false');
        
        // Toggle notifications dropdown
        if ($dropdown.is(':visible')) {
            $dropdown.fadeOut(150);
        } else {
            $dropdown.addClass('fade-in slide-up').fadeIn(150);
            loadNotifications();
        }
    });
    
    // User menu dropdown
    $('#user-menu-toggle').on('click', function(e) {
        e.stopPropagation();
        const $dropdown = $('#user-dropdown');
        const $notificationsDropdown = $('#notifications-dropdown');
        const $toggle = $(this);
        
        // Close notifications dropdown if open
        $notificationsDropdown.hide();
        
        // Toggle user dropdown
        const isOpen = $dropdown.is(':visible');
        
        if (isOpen) {
            $dropdown.fadeOut(150);
            $toggle.attr('aria-expanded', 'false');
        } else {
            $dropdown.addClass('fade-in slide-up').fadeIn(150);
            $toggle.attr('aria-expanded', 'true');
        }
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.header-notifications, .header-user-menu').length) {
            $('#notifications-dropdown, #user-dropdown').fadeOut(150);
            $('#user-menu-toggle').attr('aria-expanded', 'false');
        }
    });
    
    // Search functionality
    let searchTimeout;
    $('#header-search, #mobile-search').on('input', function() {
        const query = $(this).val().trim();
        const $results = $('#search-results');
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            $results.hide();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Search function
    function performSearch(query) {
        $.ajax({
            url: mobookingDashboard?.ajaxUrl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'mobooking_header_search',
                query: query,
                nonce: mobookingDashboard?.nonces?.service || ''
            },
            beforeSend: function() {
                $('#search-results').show().html('<div class="search-loading">Searching...</div>');
            },
            success: function(response) {
                if (response.success && response.data.results) {
                    displaySearchResults(response.data.results);
                } else {
                    $('#search-results').html('<div class="search-no-results">No results found</div>');
                }
            },
            error: function() {
                $('#search-results').html('<div class="search-error">Search unavailable</div>');
            }
        });
    }
    
    // Display search results
    function displaySearchResults(results) {
        const $results = $('#search-results .search-results-content');
        
        if (results.length === 0) {
            $results.html('<div class="search-no-results">No results found</div>');
            return;
        }
        
        let html = '';
        results.forEach(result => {
            html += `
                <a href="${result.url}" class="search-result-item">
                    <div class="result-icon">${result.icon}</div>
                    <div class="result-content">
                        <div class="result-title">${result.title}</div>
                        <div class="result-type">${result.type}</div>
                    </div>
                </a>
            `;
        });
        
        $results.html(html);
    }
    
    // Load notifications
    function loadNotifications() {
        $.ajax({
            url: mobookingDashboard?.ajaxUrl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'mobooking_get_notifications',
                nonce: mobookingDashboard?.nonces?.service || ''
            },
            success: function(response) {
                if (response.success) {
                    updateNotificationCount(response.data.count);
                }
            },
            error: function() {
                console.log('Failed to load notifications');
            }
        });
    }
    
    // Update notification count
    function updateNotificationCount(count) {
        const $toggle = $('#notifications-toggle');
        const $badge = $toggle.find('.notification-badge');
        
        if (count > 0) {
            if ($badge.length) {
                $badge.text(count);
            } else {
                $toggle.append(`<span class="notification-badge">${count}</span>`);
            }
            $toggle.attr('data-count', count);
        } else {
            $badge.remove();
            $toggle.removeAttr('data-count');
        }
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Escape key closes dropdowns and mobile nav
        if (e.key === 'Escape') {
            $('#notifications-dropdown, #user-dropdown').fadeOut(150);
            $('#user-menu-toggle').attr('aria-expanded', 'false');
            $('#search-results').hide();
            
            if ($('#mobile-menu-toggle').hasClass('active')) {
                $('#mobile-menu-toggle').removeClass('active');
                $('#mobile-nav-overlay').fadeOut(200);
                $('body').removeClass('mobile-nav-open');
            }
        }
        
        // Ctrl/Cmd + K for search focus
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            $('#header-search').focus();
        }
    });
    
    // Auto-refresh notifications every 5 minutes
    setInterval(loadNotifications, 300000);
    
    // Initial load
    loadNotifications();
    
    console.log('✅ Dashboard Header initialized');
});
</script>