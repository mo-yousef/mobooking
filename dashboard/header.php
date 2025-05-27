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

<div class="mobooking-dashboard">
    <div class="mobooking-dashboard-header">
        <!-- <div class="mobooking-dashboard-branding">
            <?php if (!empty($settings->logo_url)) : ?>
                <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php echo esc_attr($settings->company_name); ?>" class="dashboard-logo">
            <?php else : ?>
                <h1 class="dashboard-title"><?php echo esc_html($settings->company_name); ?></h1>
            <?php endif; ?>
        </div> -->
        <div></div>
        <div class="mobooking-dashboard-user">
            <span class="user-greeting"><?php printf(__('Hello, %s', 'mobooking'), $user->display_name); ?></span>
            <div class="user-actions">
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-link"><?php _e('Log Out', 'mobooking'); ?></a>
            </div>
        </div>
    </div>
    
    <div class="mobooking-dashboard-main">