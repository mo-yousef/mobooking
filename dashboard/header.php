<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Enqueue dashboard scripts and styles
wp_enqueue_style('mobooking-dashboard-style', MOBOOKING_URL . '/assets/css/dashboard.css', array(), MOBOOKING_VERSION);
wp_enqueue_script('mobooking-dashboard-script', MOBOOKING_URL . '/assets/js/dashboard.js', array('jquery'), MOBOOKING_VERSION, true);

// Localize dashboard script
wp_localize_script('mobooking-dashboard-script', 'mobooking_dashboard', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking-dashboard-nonce'),
    'current_section' => $current_section
));

// Get primary color from settings
$primary_color = $settings->primary_color ? $settings->primary_color : '#4CAF50';

// Custom inline style for primary color
$custom_css = "
    :root {
        --mobooking-primary-color: {$primary_color};
        --mobooking-primary-color-dark: " . $this->adjust_brightness($primary_color, -20) . ";
        --mobooking-primary-color-light: " . $this->adjust_brightness($primary_color, 20) . ";
    }
";
wp_add_inline_style('mobooking-dashboard-style', $custom_css);
?>

<div class="mobooking-dashboard">
    <div class="mobooking-dashboard-header">
        <div class="mobooking-dashboard-branding">
            <?php if (!empty($settings->logo_url)) : ?>
                <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php echo esc_attr($settings->company_name); ?>" class="dashboard-logo">
            <?php else : ?>
                <h1 class="dashboard-title"><?php echo esc_html($settings->company_name); ?></h1>
            <?php endif; ?>
        </div>
        
        <div class="mobooking-dashboard-user">
            <span class="user-greeting"><?php printf(__('Hello, %s', 'mobooking'), $user->display_name); ?></span>
            <div class="user-actions">
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-link"><?php _e('Log Out', 'mobooking'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="mobooking-dashboard-main">