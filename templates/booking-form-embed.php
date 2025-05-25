<?php
/**
 * Embed Booking Form Template
 * File: templates/booking-form-embed.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the user data set by the Manager
global $mobooking_form_user, $mobooking_is_embed;

if (!$mobooking_form_user) {
    echo '<div style="padding: 2rem; text-align: center; border: 1px solid #ccc;"><h3>Booking form not found</h3><p>This booking form is not available.</p></div>';
    return;
}

// Get user's booking form settings
$booking_form_manager = new \MoBooking\BookingForm\Manager();
$settings = $booking_form_manager->get_settings($mobooking_form_user->ID);

// Check if form is active
if (!$settings->is_active) {
    echo '<div style="padding: 2rem; text-align: center; border: 1px solid #ccc;"><h3>Booking Temporarily Unavailable</h3><p>This booking form is currently unavailable.</p></div>';
    return;
}

// Get user's services and areas
$services_manager = new \MoBooking\Services\ServicesManager();
$services = $services_manager->get_user_services($mobooking_form_user->ID);

$geography_manager = new \MoBooking\Geography\Manager();
$areas = $geography_manager->get_user_areas($mobooking_form_user->ID);

// Check if services and areas are set up
if (empty($services) || empty($areas)) {
    echo '<div style="padding: 2rem; text-align: center; border: 1px solid #ccc;"><h3>Booking Setup In Progress</h3><p>This booking form is being set up.</p></div>';
    return;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($settings->seo_title ?: $settings->form_title); ?></title>
    
    <?php if ($settings->seo_description) : ?>
        <meta name="description" content="<?php echo esc_attr($settings->seo_description); ?>">
    <?php endif; ?>
    
    <!-- Load WordPress styles and scripts -->
    <?php wp_head(); ?>
    
    <!-- Custom CSS -->
    <?php if ($settings->custom_css) : ?>
        <style type="text/css">
            <?php echo wp_strip_all_tags($settings->custom_css); ?>
        </style>
    <?php endif; ?>
    
    <!-- Analytics -->
    <?php if ($settings->analytics_code) : ?>
        <?php echo wp_strip_all_tags($settings->analytics_code); ?>
    <?php endif; ?>
    
    <style>
        /* Embed-specific styles */
        body {
            margin: 0;
            padding: 0;
            background-color: <?php echo esc_attr($settings->background_color); ?>;
            color: <?php echo esc_attr($settings->text_color); ?>;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .embed-container {
            min-height: 100vh;
            padding: 1rem;
        }
        
        /* Override default form styles with user's custom colors */
        :root {
            --booking-primary: <?php echo esc_attr($settings->primary_color); ?>;
            --booking-primary-dark: <?php echo esc_attr($settings->secondary_color); ?>;
        }
        
        .mobooking-booking-form-container {
            margin: 0;
            max-width: 100%;
        }
        
        .mobooking-booking-form-container .btn-primary {
            background: linear-gradient(135deg, var(--booking-primary), var(--booking-primary-dark)) !important;
            border-color: var(--booking-primary) !important;
        }
        
        .mobooking-booking-form-container .btn-primary:hover {
            background: linear-gradient(135deg, var(--booking-primary-dark), var(--booking-primary)) !important;
        }
        
        .mobooking-booking-form-container .progress-fill {
            background: linear-gradient(90deg, var(--booking-primary), var(--booking-primary-dark)) !important;
        }
        
        .mobooking-booking-form-container .step.active .step-number {
            background-color: var(--booking-primary) !important;
        }
        
        <?php if ($settings->button_style === 'square') : ?>
        .mobooking-booking-form-container .btn-primary,
        .mobooking-booking-form-container .btn-secondary {
            border-radius: 4px !important;
        }
        <?php elseif ($settings->button_style === 'pill') : ?>
        .mobooking-booking-form-container .btn-primary,
        .mobooking-booking-form-container .btn-secondary {
            border-radius: 50px !important;
        }
        <?php endif; ?>
    </style>
</head>
<body class="mobooking-embed-body">
    <div class="embed-container">
        <?php if ($settings->show_form_header) : ?>
            <div class="booking-form-header" style="text-align: center; margin-bottom: 2rem;">
                <?php if (!empty($settings->logo_url)) : ?>
                    <div class="form-logo" style="margin-bottom: 1rem;">
                        <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php echo esc_attr($settings->form_title); ?>" style="max-height: 80px; width: auto;">
                    </div>
                <?php endif; ?>
                
                <h1 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin: 0 0 0.5rem 0; font-size: 1.875rem; font-weight: 700;">
                    <?php echo esc_html($settings->form_title); ?>
                </h1>
                
                <?php if (!empty($settings->form_description)) : ?>
                    <p style="font-size: 1rem; margin: 0; opacity: 0.8;">
                        <?php echo esc_html($settings->form_description); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Render the booking form -->
        <?php
        // Use the bookings manager to render the form
        $bookings_manager = new \MoBooking\Bookings\Manager();
        echo $bookings_manager->booking_form_shortcode(array(
            'user_id' => $mobooking_form_user->ID,
            'show_header' => false
        ));
        ?>
        
        <?php if ($settings->show_form_footer && !empty($settings->custom_footer_text)) : ?>
            <div class="booking-form-footer" style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.1); font-size: 0.875rem; opacity: 0.8;">
                <?php echo wp_kses_post($settings->custom_footer_text); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Custom JavaScript -->
    <?php if ($settings->custom_js) : ?>
        <script type="text/javascript">
            <?php echo wp_strip_all_tags($settings->custom_js); ?>
        </script>
    <?php endif; ?>
    
    <?php wp_footer(); ?>
</body>
</html>