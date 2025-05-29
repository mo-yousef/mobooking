<?php
// dashboard/header.php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue dashboard scripts and styles
// wp_enqueue_style('mobooking-dashboard-style', MOBOOKING_URL . '/assets/css/dashboard.css', array(), MOBOOKING_VERSION);
// wp_enqueue_style('mobooking-dashboard-service-options-style', MOBOOKING_URL . '/assets/css/service-options.css', array(), MOBOOKING_VERSION);
wp_enqueue_script('mobooking-dashboard-script', MOBOOKING_URL . '/assets/js/dashboard.js', array('jquery'), MOBOOKING_VERSION, true);




// Localize dashboard script
wp_localize_script('mobooking-dashboard-script', 'mobooking_dashboard', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking-dashboard-nonce'),
    'current_section' => $current_section
));

// Get primary color from settings
$primary_color = $settings->primary_color ? $settings->primary_color : '#4CAF50';

// Helper function to adjust brightness
function adjust_brightness($hex, $steps) {
    // Remove # if present
    $hex = ltrim($hex, '#');
    
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    // Adjust brightness
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    // Convert back to hex
    $hex = "#";
    $hex .= str_pad(dechex($r), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($g), 2, "0", STR_PAD_LEFT);
    $hex .= str_pad(dechex($b), 2, "0", STR_PAD_LEFT);
    
    return $hex;
}

// Custom inline style for primary color
$custom_css = "
    :root {
        // --mobooking-primary-color: {$primary_color};
        // --mobooking-primary-color-dark: " . adjust_brightness($primary_color, -20) . ";
        // --mobooking-primary-color-light: " . adjust_brightness($primary_color, 20) . ";
    }
";
wp_add_inline_style('mobooking-dashboard-style', $custom_css);
?>

<?php
// dashboard/header.php - Enhanced Dashboard Header with User Dropdown and Styling
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user information
$current_user       = wp_get_current_user();
$user_avatar_url    = get_avatar_url($current_user->ID, array('size' => 32));
$user_display_name  = $current_user->display_name ?: $current_user->user_login;
$user_email         = $current_user->user_email;

// Get current section for breadcrumb
$current_section = get_query_var('section', 'overview');
$section_titles  = array(
    'overview'      => __('Dashboard Overview', 'mobooking'),
    'bookings'      => __('Bookings Management', 'mobooking'),
    'booking-form'  => __('Booking Form', 'mobooking'),
    'services'      => __('Services & Options', 'mobooking'),
    'discounts'     => __('Discount Codes', 'mobooking'),
    'areas'         => __('Service Areas', 'mobooking'),
    'settings'      => __('Business Settings', 'mobooking'),
    'analytics'     => __('Analytics & Reports', 'mobooking'),
    'customers'     => __('Customer Management', 'mobooking'),
);

$current_title = isset($section_titles[$current_section]) ? $section_titles[$current_section] : __('Dashboard', 'mobooking');

// Get business settings for branding - placeholder example
$settings_manager = new \MoBooking\Database\SettingsManager();
$settings         = $settings_manager->get_settings(get_current_user_id());

// Get notification count (you can implement this based on your notification system)
$notification_count = 0; // Placeholder

// Get current time for greeting
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
    <!-- Left Section: Breadcrumb & Page Title -->
    <div class="header-left">
        <nav class="header-breadcrumb" aria-label="<?php _e('Breadcrumb', 'mobooking'); ?>">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item">
                    <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="breadcrumb-link">
                        <svg aria-hidden="true" class="breadcrumb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9,22 9,12 15,12 15,22"/>
                        </svg>
                        <?php _e('Dashboard', 'mobooking'); ?>
                    </a>
                </li>
                <?php if ($current_section !== 'overview') : ?>
                    <li class="breadcrumb-separator" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"/>
                        </svg>
                    </li>
                    <li class="breadcrumb-item breadcrumb-current" aria-current="page">
                        <span class="breadcrumb-current-text"><?php echo esc_html($current_title); ?></span>
                    </li>
                <?php endif; ?>
            </ol>
        </nav>

        <div class="header-title-section" style="display:none;">
            <h1 class="header-page-title"><?php echo esc_html($current_title); ?></h1>
            <p class="header-greeting">
                <?php echo esc_html($greeting); ?>, <?php echo esc_html($user_display_name); ?>
            </p>
        </div>
    </div>



    <!-- Right Section: Notifications, Help, User Menu -->
    <div class="header-right">
        <!-- Notifications -->
        <div class="header-notifications">
            <button type="button" class="notification-btn" title="<?php _e('Notifications', 'mobooking'); ?>" data-notification-count="<?php echo $notification_count; ?>" aria-haspopup="true" aria-expanded="false" aria-controls="notification-dropdown">
                <svg aria-hidden="true" class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <?php if ($notification_count > 0) : ?>
                    <span class="notification-badge" aria-label="<?php echo esc_attr(sprintf(_n('%d new notification', '%d new notifications', $notification_count, 'mobooking'), $notification_count)); ?>">
                        <?php echo $notification_count; ?>
                    </span>
                <?php endif; ?>
            </button>

            <div class="notification-dropdown" id="notification-dropdown" role="menu" aria-hidden="true">
                <div class="notification-header">
                    <h3><?php _e('Notifications', 'mobooking'); ?></h3>
                    <button type="button" class="mark-all-read" title="<?php _e('Mark All as Read', 'mobooking'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                    </button>
                </div>
                <div class="notification-list">
                    <?php if ($notification_count === 0) : ?>
                        <div class="no-notifications" role="none">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                <path d="M12 2v6"/>
                            </svg>
                            <p><?php _e('No new notifications', 'mobooking'); ?></p>
                        </div>
                    <?php else : ?>
                        <!-- Example notification item -->
                        <div class="notification-item unread" role="menuitem">
                            <div class="notification-icon">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                            </div>
                            <div class="notification-content">
                                <p class="notification-title"><?php _e('New Booking Request', 'mobooking'); ?></p>
                                <p class="notification-message"><?php _e('John Doe requested a cleaning service', 'mobooking'); ?></p>
                                <span class="notification-time"><?php _e('2 minutes ago', 'mobooking'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="notification-footer">
                    <a href="<?php echo esc_url(home_url('/dashboard/notifications/')); ?>" class="view-all-notifications" role="menuitem">
                        <?php _e('View All Notifications', 'mobooking'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Help & Support -->
        <div class="header-help">
            <button type="button" class="help-btn" title="<?php _e('Help & Support', 'mobooking'); ?>" aria-haspopup="true" aria-expanded="false" aria-controls="help-dropdown">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12" y2="17"/>
                </svg>
            </button>
            <div class="help-dropdown" id="help-dropdown" role="menu" aria-hidden="true">
                <div class="help-header">
                    <h3><?php _e('Help & Support', 'mobooking'); ?></h3>
                </div>
                <div class="help-list">
                    <a href="#" class="help-item" role="menuitem">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10,9 9,9 8,9"/>
                        </svg>
                        <span><?php _e('Documentation', 'mobooking'); ?></span>
                    </a>
                    <a href="#" class="help-item" role="menuitem">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            <line x1="12" y1="7" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12" y2="17"/>
                        </svg>
                        <span><?php _e('Contact Support', 'mobooking'); ?></span>
                    </a>
                    <a href="#" class="help-item" role="menuitem">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                        <span><?php _e("What's New", 'mobooking'); ?></span>
                    </a>
                    <a href="#" class="help-item" role="menuitem">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                        <span><?php _e('Settings', 'mobooking'); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- User Avatar & Dropdown -->
        <div class="header-user-menu">
            <button type="button" class="user-menu-trigger" aria-expanded="false" aria-haspopup="true" aria-controls="user-dropdown" title="<?php echo esc_attr(sprintf(__('User menu for %s', 'mobooking'), $user_display_name)); ?>">
                <div class="user-avatar-container">
                    <img src="<?php echo esc_url($user_avatar_url); ?>" alt="<?php echo esc_attr($user_display_name); ?>" class="user-avatar">
                    <span class="user-status-indicator online" title="<?php _e('Online', 'mobooking'); ?>"></span>
                </div>
                <div class="user-info-short">
                    <span class="user-name"><?php echo esc_html($user_display_name); ?></span>
                    <span class="user-role-short">
                        <?php 
                        $user_roles = $current_user->roles;
                        if (in_array('administrator', $user_roles)) {
                            _e('Administrator', 'mobooking');
                        } elseif (in_array('mobooking_business_owner', $user_roles)) {
                            _e('Business Owner', 'mobooking');
                        } else {
                            _e('User', 'mobooking');
                        }
                        ?>
                    </span>
                </div>
                <svg aria-hidden="true" class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </button>

            <!-- User Dropdown Menu -->
            <div class="user-dropdown-menu" id="user-dropdown" role="menu" aria-hidden="true">
                <div class="user-dropdown-header">
                    <div class="user-info-full">
                        <img src="<?php echo esc_url($user_avatar_url); ?>" alt="<?php echo esc_attr($user_display_name); ?>" class="user-avatar-large">
                        <div class="user-details-full">
                            <h4 class="user-name-full"><?php echo esc_html($user_display_name); ?></h4>
                            <p class="user-email"><?php echo esc_html($user_email); ?></p>
                            <span class="user-role-badge">
                                <?php 
                                if (in_array('administrator', $user_roles)) {
                                    echo '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg> ' . __('Administrator', 'mobooking');
                                } elseif (in_array('mobooking_business_owner', $user_roles)) {
                                    echo '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> ' . __('Business Owner', 'mobooking');
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Body -->
                <div class="user-dropdown-body">
                    <!-- Quick Links -->
                    <div class="dropdown-section">
                        <h5 class="dropdown-section-title"><?php _e('Quick Links', 'mobooking'); ?></h5>
                        <div class="dropdown-links">
                            <a href="<?php echo esc_url(home_url('/dashboard/overview/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                                <span><?php _e('Dashboard', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <span><?php _e('My Bookings', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 9.4L7.5 4.21L3.27 6.96L12 12.01L20.73 6.96L16.5 9.4z"/><path d="M12 22.08V12"/></svg>
                                <span><?php _e('My Services', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3"/></svg>
                                <span><?php _e('Business Settings', 'mobooking'); ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Business Tools -->
                    <div class="dropdown-section">
                        <h5 class="dropdown-section-title"><?php _e('Business Tools', 'mobooking'); ?></h5>
                        <div class="dropdown-links">
                            <a href="<?php echo esc_url(home_url('/dashboard/analytics/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M9 17V9M13 17v-6M17 17V5"/></svg>
                                <span><?php _e('Analytics', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/booking/' . $current_user->user_login)); ?>" class="dropdown-link" role="menuitem" target="_blank" rel="noopener noreferrer">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                <span><?php _e('View Public Form', 'mobooking'); ?></span>
                                <svg aria-hidden="true" class="external-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/discounts/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M22 4H2v18h18V4zm-3 3a2 2 0 0 0-2-2"/></svg>
                                <span><?php _e('Discount Codes', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/areas/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                                <span><?php _e('Service Areas', 'mobooking'); ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Account Links -->
                    <div class="dropdown-section">
                        <h5 class="dropdown-section-title"><?php _e('Account', 'mobooking'); ?></h5>
                        <div class="dropdown-links">
                            <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span><?php _e('Edit Profile', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/billing/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                <span><?php _e('Billing & Plans', 'mobooking'); ?></span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/notifications/')); ?>" class="dropdown-link" role="menuitem">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                                <span><?php _e('Notification Settings', 'mobooking'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Footer -->
                <div class="user-dropdown-footer">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-btn" role="menuitem">
                        <span><?php _e('Log Out', 'mobooking'); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M5 22C4.44772 22 4 21.5523 4 21V3C4 2.44772 4.44772 2 5 2H19C19.5523 2 20 2.44772 20 3V6H18V4H6V20H18V18H20V21C20 21.5523 19.5523 22 19 22H5ZM18 16V13H11V11H18V8L23 12L18 16Z"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<?php // --- STYLES -------------------------------------------------------------- ?>
<style>
    :root {
        --mobooking-primary:rgb(49, 46, 230);
        --mobooking-primary-hover: #4338ca;
        --mobooking-gray-50: #f9fafb;
        --mobooking-gray-100: #f3f4f6;
        --mobooking-gray-200: #e5e7eb;
        --mobooking-gray-300: #d1d5db;
        --mobooking-gray-700: #374151;
        --mobooking-gray-900: #111827;
    }

    .mobooking-dashboard-header {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    /* Layout */
    .header-left, .header-center, .header-right {
        display: flex;
        align-items: center;
    }
    .header-left { gap: 1.5rem; }
    .header-center { flex: 1; justify-content: center; }
    .header-right { gap: 1.25rem; }

    /* Breadcrumb */
    .breadcrumb-list {
        display: flex;
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
        color: var(--mobooking-gray-700);
        font-size: 0.875rem;
        line-height: 1.25rem;
    }
    .breadcrumb-link {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: inherit;
        text-decoration: none;
        transition: color 0.15s;
    }
    .breadcrumb-link:hover {
        color: var(--mobooking-primary);
    }
    .breadcrumb-icon {
        width: 1rem;
        height: 1rem;
    }
    .breadcrumb-current-text {
        font-weight: 500;
        color: var(--mobooking-gray-900);
    }

    /* Title & greeting */
    .header-page-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--mobooking-gray-900);
    }
    .header-greeting {
        margin: 0;
        font-size: 0.875rem;
        color: var(--mobooking-gray-700);
    }

    /* Quick Actions */
    .quick-actions { display: flex; gap: 0.75rem; }
    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: var(--mobooking-primary);
        color: #fff;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background 0.15s;
    }
    .quick-action-btn:hover { background: var(--mobooking-primary-hover); }
    .quick-action-btn svg { width: 1rem; height: 1rem; }

    /* Notification */
    .notification-btn { position: relative; background: none; border: none; cursor: pointer; }
    .notification-icon { width: 1.5rem; height: 1.5rem; stroke: var(--mobooking-gray-700); }
    .notification-badge {
        position: absolute;
        top: -0.25rem;
        right: -0.25rem;
        min-width: 1rem;
        height: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--mobooking-primary);
        color: #fff;
        border-radius: 9999px;
        font-size: 0.625rem;
        font-weight: 600;
    }

    /* Dropdown panels */
    .notification-dropdown,
    .help-dropdown,
    .user-dropdown-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 0.5rem);
        min-width: 18rem;
        background: #fff;
        border: 1px solid var(--mobooking-gray-200);
        border-radius: 0.5rem;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        padding: 1rem;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-0.5rem);
        transition: opacity 0.15s, transform 0.15s;
    }

    .notification-btn[aria-expanded="true"] + .notification-dropdown,
    .help-btn[aria-expanded="true"] + .help-dropdown,
    .user-menu-trigger[aria-expanded="true"] + .user-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* Dropdown content */
    .notification-header,
    .help-header,
    .user-dropdown-header { display: none; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
    .notification-header h3,
    .help-header h3,
    .dropdown-section-title { margin: 0; font-size: 0.875rem; font-weight: 600; color: var(--mobooking-gray-900); }
    .mark-all-read { background: none; border: none; cursor: pointer; }

    .notification-list { max-height: 14rem; overflow-y: auto; }
    .notification-item { display: flex; gap: 0.75rem; padding: 0.5rem 0; }
    .notification-item.unread { background: var(--mobooking-gray-50); border-radius: 0.375rem; }
    .notification-item svg { width: 1.25rem; height: 1.25rem; stroke: var(--mobooking-primary); }
    .notification-title { margin: 0; font-weight: 600; font-size: 0.8125rem; }
    .notification-message { margin: 0; font-size: 0.75rem; color: var(--mobooking-gray-700); }
    .notification-time { font-size: 0.6875rem; color: var(--mobooking-gray-300); }

    .view-all-notifications { display: block; text-align: center; font-size: 0.8125rem; color: var(--mobooking-primary); text-decoration: none; margin-top: 0.75rem; }

    /* Help Dropdown */
    .help-list { display: flex; flex-direction: column; gap: 0.5rem; }
    .help-list svg { width: 1.25rem; height: 1.25rem; stroke: var(--mobooking-primary); }
    .help-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: var(--mobooking-gray-700); text-decoration: none; padding: 0.5rem; border-radius: 0.375rem; }
    .help-item:hover { background: var(--mobooking-gray-50); color: var(--mobooking-gray-900); }

    /* User Menu */
    .user-menu-trigger { display: flex; align-items: center; gap: 0.5rem; background: none; border: none; cursor: pointer; }
    .user-avatar-container { position: relative; }
    .user-avatar { width: 2rem; height: 2rem; border-radius: 9999px; }
    .user-status-indicator { position: absolute; bottom: 0; right: 0; width: 0.5rem; height: 0.5rem; border: 2px solid #fff; border-radius: 9999px; background: #10b981; }
    .user-info-short { display: flex; flex-direction: column; align-items: flex-start; }
    .user-name { font-size: 0.875rem; font-weight: 600; color: var(--mobooking-gray-900); }
    .user-role-short { font-size: 0.75rem; color: var(--mobooking-gray-500); }

    .user-dropdown-header { padding-bottom: 0.75rem; border-bottom: 1px solid var(--mobooking-gray-200); margin-bottom: 0.75rem; }
    .user-info-full { display: flex; gap: 0.75rem; }
    .user-avatar-large { width: 3rem; height: 3rem; border-radius: 9999px; }
    .user-name-full { margin: 0 0 0.25rem; font-size: 1rem; font-weight: 600; color: var(--mobooking-gray-900); }
    .user-email { margin: 0; font-size: 0.75rem; color: var(--mobooking-gray-500); }

    .dropdown-section { margin-bottom: 0.75rem; }
    .dropdown-links { display: flex; flex-direction: column; gap: 0.25rem; }
    .dropdown-link { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; padding: 0.5rem; border-radius: 0.375rem; color: var(--mobooking-gray-700); text-decoration: none; }
    .dropdown-link:hover { background: var(--mobooking-gray-50); color: var(--mobooking-gray-900); }
    .dropdown-link svg { width: 1rem; height: 1rem; stroke: var(--mobooking-primary); }

    .user-dropdown-footer { border-top: 1px solid var(--mobooking-gray-200); padding-top: 0.75rem; text-align: center; }
.logout-btn {
    display: flex
;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #ff0000;
    text-decoration: none;
    background: #ff000017;
    padding: 10px;
    text-align: center;
    border-radius: 5px;
    justify-content: center;

}
.logout-btn svg {
    width: 20px;
    height: 20px;
}

    .logout-btn:hover {     color: #9f0505;
    background: #ff000033;
 }

    /* Utility */
    .dropdown-arrow { width: 1rem; height: 1rem; stroke: var(--mobooking-gray-500); transition: transform 0.15s; }
    .user-menu-trigger[aria-expanded="true"] .dropdown-arrow { transform: rotate(180deg); }

    /* Dark Mode (optional) */
    @media (prefers-color-scheme: dark) {
        .mobooking-dashboard-header { background: var(--mobooking-gray-900); border-bottom-color: #1f2937; }
        .breadcrumb-list, .header-greeting, .user-role-short, .notification-icon, .dropdown-link { color: var(--mobooking-gray-300); }
        .header-page-title, .breadcrumb-current-text { color: #fff; }
        .quick-action-btn { background: var(--mobooking-primary-hover); }
        .quick-action-btn:hover { background: var(--mobooking-primary); }
        .notification-dropdown, .help-dropdown, .user-dropdown-menu { background: #1f2937; border-color: #374151; }
        .dropdown-link:hover, .help-item:hover { background: #374151; }
        .dropdown-section-title, .notification-title { color: #fff; }
        .notification-message { color: var(--mobooking-gray-400); }
        .logout-btn { color: var(--mobooking-gray-300); }
        .logout-btn:hover { color: #fff; }
    }
</style>

<?php // --------------------------------------------------------------------- ?>
<script>
    // Basic dropdown toggles – replace with your preferred framework logic
    document.addEventListener('DOMContentLoaded', () => {
        const toggleOnClick = (trigger, dropdown) => {
            trigger.addEventListener('click', () => {
                const expanded = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', String(!expanded));
                dropdown.setAttribute('aria-hidden', String(expanded));
            });
            // Hide dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                    trigger.setAttribute('aria-expanded', 'false');
                    dropdown.setAttribute('aria-hidden', 'true');
                }
            });
        };

        // Notifications
        const notificationTrigger  = document.querySelector('.notification-btn');
        const notificationDropdown = document.getElementById('notification-dropdown');
        if (notificationTrigger && notificationDropdown) {
            toggleOnClick(notificationTrigger, notificationDropdown);
        }

        // Help
        const helpTrigger  = document.querySelector('.help-btn');
        const helpDropdown = document.getElementById('help-dropdown');
        if (helpTrigger && helpDropdown) {
            toggleOnClick(helpTrigger, helpDropdown);
        }

        // User menu
        const userTrigger  = document.querySelector('.user-menu-trigger');
        const userDropdown = document.getElementById('user-dropdown');
        if (userTrigger && userDropdown) {
            toggleOnClick(userTrigger, userDropdown);
        }
    });
</script>
