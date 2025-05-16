<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current section
$current_section = get_query_var('section');
if (empty($current_section)) {
    $current_section = 'overview';
}

// Get current user
$user_id = get_current_user_id();
$user = get_userdata($user_id);

// Get user settings
$settings_manager = new \MoBooking\Database\SettingsManager();
$settings = $settings_manager->get_settings($user_id);

// Dashboard header
include MOBOOKING_PATH . '/dashboard/header.php';
?>

<div class="mobooking-dashboard-container">
    <?php 
    // Dashboard sidebar
    include MOBOOKING_PATH . '/dashboard/sidebar.php';
    ?>
    
    <div class="mobooking-dashboard-content">
        <?php
        // Load the appropriate section template
        $section_template = MOBOOKING_PATH . '/dashboard/sections/' . $current_section . '.php';
        
        if (file_exists($section_template)) {
            include $section_template;
        } else {
            // Default to overview if section doesn't exist
            include MOBOOKING_PATH . '/dashboard/sections/overview.php';
        }
        ?>
    </div>
</div>

<?php
// Dashboard footer
include MOBOOKING_PATH . '/dashboard/footer.php';