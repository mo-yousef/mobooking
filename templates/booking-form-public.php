<?php
/**
 * Public Booking Form Template
 * File: templates/booking-form-public.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the user data set by the Manager
global $mobooking_form_user, $mobooking_is_embed;

if (!$mobooking_form_user) {
    get_header();
    echo '<div class="container"><h1>Booking form not found</h1><p>This booking form is not available.</p></div>';
    get_footer();
    return;
}

// Get user's booking form settings
$booking_form_manager = new \MoBooking\BookingForm\Manager();
$settings = $booking_form_manager->get_settings($mobooking_form_user->ID);

// Check if form is active
if (!$settings->is_active) {
    get_header();
    echo '<div class="container"><h1>Booking Temporarily Unavailable</h1><p>This booking form is currently unavailable. Please check back later.</p></div>';
    get_footer();
    return;
}

// Get user's services and areas
$services_manager = new \MoBooking\Services\ServicesManager();
$services = $services_manager->get_user_services($mobooking_form_user->ID);

$geography_manager = new \MoBooking\Geography\Manager();
$areas = $geography_manager->get_user_areas($mobooking_form_user->ID);

// Check if services and areas are set up
if (empty($services) || empty($areas)) {
    get_header();
    echo '<div class="container"><h1>Booking Setup In Progress</h1><p>This booking form is being set up. Please check back soon.</p></div>';
    get_footer();
    return;
}

// Set page title
add_filter('wp_title', function($title) use ($settings) {
    return $settings->seo_title ?: $settings->form_title . ' - Booking';
});

// Set meta description
add_action('wp_head', function() use ($settings) {
    if ($settings->seo_description) {
        echo '<meta name="description" content="' . esc_attr($settings->seo_description) . '">' . "\n";
    }
    
    // Add custom CSS
    if ($settings->custom_css) {
        echo '<style type="text/css">' . wp_strip_all_tags($settings->custom_css) . '</style>' . "\n";
    }
    
    // Add analytics code
    if ($settings->analytics_code) {
        echo wp_strip_all_tags($settings->analytics_code) . "\n";
    }
});

get_header();
?>

<div class="mobooking-public-booking-page" style="
    background-color: <?php echo esc_attr($settings->background_color); ?>;
    color: <?php echo esc_attr($settings->text_color); ?>;
    min-height: 100vh;
    padding: 2rem 0;
">
    <div class="container" style="max-width: <?php echo $settings->form_width === 'narrow' ? '600px' : ($settings->form_width === 'wide' ? '1000px' : '800px'); ?>; margin: 0 auto; padding: 0 1rem;">
        
        <?php if ($settings->show_form_header) : ?>
            <div class="booking-form-header" style="text-align: center; margin-bottom: 2rem;">
                <?php if (!empty($settings->logo_url)) : ?>
                    <div class="form-logo" style="margin-bottom: 1rem;">
                        <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php echo esc_attr($settings->form_title); ?>" style="max-height: 100px; width: auto;">
                    </div>
                <?php endif; ?>
                
                <h1 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin: 0 0 0.5rem 0; font-size: 2.5rem; font-weight: 700;">
                    <?php echo esc_html($settings->form_title); ?>
                </h1>
                
                <?php if (!empty($settings->form_description)) : ?>
                    <p style="font-size: 1.125rem; margin: 0; opacity: 0.8; max-width: 600px; margin: 0 auto;">
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
            'show_header' => false // We're showing our own header above
        ));
        ?>
        
        <?php if ($settings->show_form_footer) : ?>
            <div class="booking-form-footer" style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(0,0,0,0.1);">
                <?php if (!empty($settings->custom_footer_text)) : ?>
                    <div class="custom-footer-content" style="margin-bottom: 1.5rem;">
                        <?php echo wp_kses_post($settings->custom_footer_text); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($settings->contact_info)) : ?>
                    <div class="contact-info" style="margin-bottom: 1rem; font-size: 0.875rem; opacity: 0.8;">
                        <?php echo nl2br(esc_html($settings->contact_info)); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($settings->social_links)) : ?>
                    <div class="social-links" style="margin-bottom: 1rem;">
                        <?php
                        $social_lines = explode("\n", $settings->social_links);
                        foreach ($social_lines as $line) {
                            $line = trim($line);
                            if (empty($line)) continue;
                            
                            $parts = explode(':', $line, 2);
                            if (count($parts) === 2) {
                                $platform = trim($parts[0]);
                                $url = trim($parts[1]);
                                if (filter_var($url, FILTER_VALIDATE_URL)) {
                                    echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener" style="margin: 0 0.5rem; color: ' . esc_attr($settings->primary_color) . '; text-decoration: none;">' . esc_html($platform) . '</a>';
                                }
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="powered-by" style="font-size: 0.75rem; opacity: 0.6;">
                    <?php _e('Powered by', 'mobooking'); ?> <strong>MoBooking</strong>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Override default form styles with user's custom colors */
:root {
    --booking-primary: <?php echo esc_attr($settings->primary_color); ?>;
    --booking-primary-dark: <?php echo esc_attr($settings->secondary_color); ?>;
    --booking-background: <?php echo esc_attr($settings->background_color); ?>;
    --booking-text: <?php echo esc_attr($settings->text_color); ?>;
}

.mobooking-booking-form-container {
    --booking-primary: <?php echo esc_attr($settings->primary_color); ?>;
    --booking-primary-dark: <?php echo esc_attr($settings->secondary_color); ?>;
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

.mobooking-booking-form-container .service-card:hover,
.mobooking-booking-form-container .service-card.selected {
    border-color: var(--booking-primary) !important;
}

.mobooking-booking-form-container input:focus,
.mobooking-booking-form-container select:focus,
.mobooking-booking-form-container textarea:focus {
    border-color: var(--booking-primary) !important;
    box-shadow: 0 0 0 3px rgba(<?php 
        // Convert hex to RGB for opacity
        $hex = ltrim($settings->primary_color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        echo "$r, $g, $b";
    ?>, 0.1) !important;
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .mobooking-public-booking-page {
        padding: 1rem 0;
    }
    
    .booking-form-header h1 {
        font-size: 2rem !important;
    }
}
</style>

<?php
// Add custom JavaScript
if ($settings->custom_js) {
    echo '<script type="text/javascript">' . wp_strip_all_tags($settings->custom_js) . '</script>';
}

get_footer();
?>