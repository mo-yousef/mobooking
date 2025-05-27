<?php
/**
 * Public Booking Form Template - FIXED VERSION
 * File: templates/booking-form-public.php
 * 
 * This template displays the public booking form for customers
 * Fixed issues: proper template structure, service options loading, form validation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the user data set by the Manager
global $mobooking_form_user, $mobooking_is_embed;

if (!$mobooking_form_user) {
    get_header();
    ?>
    <div class="mobooking-error-container" style="padding: 2rem; text-align: center;">
        <h2><?php _e('Booking Form Not Available', 'mobooking'); ?></h2>
        <p><?php _e('This booking form is not available or has been disabled.', 'mobooking'); ?></p>
        <a href="<?php echo home_url(); ?>" class="btn-primary"><?php _e('Go Home', 'mobooking'); ?></a>
    </div>
    <?php
    get_footer();
    return;
}

// Get user's booking form settings
$booking_form_manager = new \MoBooking\BookingForm\Manager();
$settings = $booking_form_manager->get_settings($mobooking_form_user->ID);

// Check if form is active
if (!$settings->is_active) {
    get_header();
    ?>
    <div class="mobooking-notice-container" style="padding: 2rem; text-align: center;">
        <h2><?php _e('Booking Temporarily Unavailable', 'mobooking'); ?></h2>
        <p><?php _e('This booking form is currently unavailable. Please try again later or contact us directly.', 'mobooking'); ?></p>
        <a href="<?php echo home_url(); ?>" class="btn-primary"><?php _e('Go Home', 'mobooking'); ?></a>
    </div>
    <?php
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
    ?>
    <div class="mobooking-setup-container" style="padding: 2rem; text-align: center;">
        <h2><?php _e('Booking Setup In Progress', 'mobooking'); ?></h2>
        <p><?php _e('This booking form is being configured. Please check back soon.', 'mobooking'); ?></p>
        <a href="<?php echo home_url(); ?>" class="btn-primary"><?php _e('Go Home', 'mobooking'); ?></a>
    </div>
    <?php
    get_footer();
    return;
}

// Include header
get_header();

// Enqueue necessary styles and scripts
wp_enqueue_style('mobooking-booking-form', get_template_directory_uri() . '/assets/css/booking-form.css', array(), '1.0.0');
wp_enqueue_script('mobooking-booking-form', get_template_directory_uri() . '/assets/js/booking-form.js', array('jquery'), '1.0.0', true);

// Localize script with enhanced configuration
$localize_data = array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'userId' => strval($mobooking_form_user->ID),
    'nonces' => array(
        'booking' => wp_create_nonce('mobooking-booking-nonce'),
    ),
    'strings' => array(
        'error' => __('An error occurred', 'mobooking'),
        'selectService' => __('Please select at least one service', 'mobooking'),
        'fillRequired' => __('Please fill in all required fields', 'mobooking'),
        'invalidEmail' => __('Please enter a valid email address', 'mobooking'),
        'bookingSuccess' => __('Booking confirmed successfully!', 'mobooking'),
        'zipRequired' => __('Please enter a ZIP code', 'mobooking'),
        'zipInvalid' => __('Please enter a valid ZIP code', 'mobooking'),
        'zipNotCovered' => __('Sorry, we don\'t service this area', 'mobooking'),
        'zipCovered' => __('Great! We service your area', 'mobooking'),
        'discountInvalid' => __('Invalid discount code', 'mobooking'),
        'discountApplied' => __('Discount applied successfully', 'mobooking'),
        'selectOptions' => __('Please configure your service options', 'mobooking'),
        'fillCustomerInfo' => __('Please fill in your contact information', 'mobooking'),
        'processing' => __('Processing...', 'mobooking'),
        'continue' => __('Continue', 'mobooking')
    ),
    'currency' => array(
        'symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
        'position' => get_option('woocommerce_currency_pos', 'left')
    ),
    'debug' => defined('WP_DEBUG') && WP_DEBUG,
    'user' => array(
        'name' => $mobooking_form_user->display_name,
        'email' => $mobooking_form_user->user_email
    ),
    'settings' => array(
        'primaryColor' => $settings->primary_color,
        'secondaryColor' => $settings->secondary_color,
        'showServiceDescriptions' => $settings->show_service_descriptions,
        'showPriceBreakdown' => $settings->show_price_breakdown,
        'enableZipValidation' => $settings->enable_zip_validation
    )
);

wp_localize_script('mobooking-booking-form', 'mobookingBooking', $localize_data);

// Get service options manager
$options_manager = new \MoBooking\Services\ServiceOptionsManager();
?>

<div class="mobooking-booking-form-page">
    <?php if ($settings->show_form_header) : ?>
        <div class="booking-form-page-header" style="text-align: center; padding: 2rem 1rem; margin-bottom: 2rem;">
            <?php if (!empty($settings->logo_url)) : ?>
                <div class="form-logo" style="margin-bottom: 1rem;">
                    <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php echo esc_attr($settings->form_title); ?>" style="max-height: 80px; width: auto;">
                </div>
            <?php endif; ?>
            
            <h1 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin: 0 0 0.5rem 0; font-size: 2rem; font-weight: 700;">
                <?php echo esc_html($settings->form_title); ?>
            </h1>
            
            <?php if (!empty($settings->form_description)) : ?>
                <p style="font-size: 1.125rem; margin: 0; opacity: 0.8; color: #6b7280;">
                    <?php echo esc_html($settings->form_description); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Main Booking Form Container -->
    <div class="mobooking-booking-form-container">
        <!-- Enhanced Progress Indicator -->
        <div class="booking-progress">
            <div class="progress-bar">
                <div class="progress-fill" style="width: 16.66%;"></div>
            </div>
            <div class="progress-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label"><?php _e('Location', 'mobooking'); ?></div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label"><?php _e('Services', 'mobooking'); ?></div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label"><?php _e('Options', 'mobooking'); ?></div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-label"><?php _e('Details', 'mobooking'); ?></div>
                </div>
                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-label"><?php _e('Review', 'mobooking'); ?></div>
                </div>
                <div class="step">
                    <div class="step-number">6</div>
                    <div class="step-label"><?php _e('Complete', 'mobooking'); ?></div>
                </div>
            </div>
        </div>
        
        <form id="mobooking-booking-form" class="booking-form">
            <!-- Hidden fields -->
            <input type="hidden" name="user_id" value="<?php echo esc_attr($mobooking_form_user->ID); ?>">
            <input type="hidden" name="total_price" id="total_price" value="0">
            <input type="hidden" name="discount_amount" id="discount_amount" value="0">
            <input type="hidden" name="service_options_data" id="service_options_data" value="">
            <?php wp_nonce_field('mobooking-booking-nonce', 'nonce'); ?>
            
            <!-- Step 1: ZIP Code Validation -->
            <div class="booking-step step-1 active">
                <div class="step-header">
                    <h2><?php _e('Check Service Availability', 'mobooking'); ?></h2>
                    <p><?php _e('Enter your ZIP code to see if we service your area', 'mobooking'); ?></p>
                </div>
                
                <div class="zip-input-group">
                    <label for="customer_zip_code"><?php _e('ZIP Code', 'mobooking'); ?></label>
                    <div class="zip-input-wrapper">
                        <input type="text" id="customer_zip_code" name="zip_code" class="zip-input" 
                               placeholder="<?php _e('Enter ZIP code', 'mobooking'); ?>" required
                               pattern="[0-9]{5}(-[0-9]{4})?" 
                               title="<?php _e('Please enter a valid ZIP code (e.g., 12345 or 12345-6789)', 'mobooking'); ?>">
                        <div class="zip-validation-icon"></div>
                    </div>
                    <p class="zip-help"><?php _e('Enter your ZIP code to check service availability', 'mobooking'); ?></p>
                </div>
                
                <div class="zip-result"></div>
                
                <div class="step-actions">
                    <button type="button" class="btn-primary next-step" disabled>
                        <?php _e('Enter ZIP Code', 'mobooking'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Step 2: Service Selection -->
            <div class="booking-step step-2">
                <div class="step-header">
                    <h2><?php _e('Select Services', 'mobooking'); ?></h2>
                    <p><?php _e('Choose the services you need', 'mobooking'); ?></p>
                </div>
                
                <div class="services-grid services-container">
                    <?php foreach ($services as $service) : 
                        $service_options = $options_manager->get_service_options($service->id);
                        $has_options = !empty($service_options);
                    ?>
                        <div class="service-card" data-service-id="<?php echo esc_attr($service->id); ?>" data-service-price="<?php echo esc_attr($service->price); ?>">
                            <div class="service-header">
                                <div class="service-visual">
                                    <?php if (!empty($service->image_url)) : ?>
                                        <div class="service-image">
                                            <img src="<?php echo esc_url($service->image_url); ?>" alt="<?php echo esc_attr($service->name); ?>">
                                        </div>
                                    <?php elseif (!empty($service->icon)) : ?>
                                        <div class="service-icon">
                                            <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                        </div>
                                    <?php else : ?>
                                        <div class="service-icon service-icon-default">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="service-content">
                                        <h3><?php echo esc_html($service->name); ?></h3>
                                        <?php if (!empty($service->description) && $settings->show_service_descriptions) : ?>
                                            <p class="service-description"><?php echo esc_html($service->description); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="service-selector">
                                    <input type="checkbox" name="selected_services[]" value="<?php echo esc_attr($service->id); ?>" 
                                           id="service_<?php echo esc_attr($service->id); ?>" 
                                           data-has-options="<?php echo $has_options ? 1 : 0; ?>"
                                           style="position: absolute; opacity: 0; pointer-events: none;">
                                    <label for="service_<?php echo esc_attr($service->id); ?>" class="service-checkbox"></label>
                                </div>
                            </div>
                            
                            <div class="service-meta">
                                <div class="service-price">
                                    <?php 
                                    if (function_exists('wc_price')) {
                                        echo wc_price($service->price);
                                    } else {
                                        echo '$' . number_format($service->price, 2);
                                    }
                                    ?>
                                </div>
                                <div class="service-duration">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                    <?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?>
                                </div>
                            </div>
                            
                            <?php if ($has_options) : ?>
                                <div class="service-options-indicator">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                    </svg>
                                    <span><?php _e('Customizable options available', 'mobooking'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                    <button type="button" class="btn-primary next-step" disabled><?php _e('Select Services', 'mobooking'); ?></button>
                </div>
            </div>
            
            <!-- Step 3: Service Options -->
            <div class="booking-step step-3">
                <div class="step-header">
                    <h2><?php _e('Customize Your Services', 'mobooking'); ?></h2>
                    <p><?php _e('Configure your selected services', 'mobooking'); ?></p>
                </div>
                
                <div class="service-options-container">
                    <!-- Service options will be loaded dynamically via JavaScript -->
                </div>
                
                <div class="no-options-message" style="display: none;">
                    <div class="no-options-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3><?php _e('No Additional Options Needed', 'mobooking'); ?></h3>
                    <p><?php _e('Your selected services are ready to book. Click "Continue" to proceed.', 'mobooking'); ?></p>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                    <button type="button" class="btn-primary next-step"><?php _e('Continue', 'mobooking'); ?></button>
                </div>
            </div>
            
            <!-- Step 4: Customer Information -->
            <div class="booking-step step-4">
                <div class="step-header">
                    <h2><?php _e('Your Information', 'mobooking'); ?></h2>
                    <p><?php _e('Please provide your contact details', 'mobooking'); ?></p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="customer_name"><?php _e('Full Name', 'mobooking'); ?> *</label>
                        <input type="text" id="customer_name" name="customer_name" required 
                               placeholder="<?php _e('Enter your full name', 'mobooking'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_email"><?php _e('Email Address', 'mobooking'); ?> *</label>
                        <input type="email" id="customer_email" name="customer_email" required 
                               placeholder="<?php _e('Enter your email address', 'mobooking'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_phone"><?php _e('Phone Number', 'mobooking'); ?></label>
                        <input type="tel" id="customer_phone" name="customer_phone" 
                               placeholder="<?php _e('Enter your phone number', 'mobooking'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="service_date"><?php _e('Preferred Date & Time', 'mobooking'); ?> *</label>
                        <input type="datetime-local" id="service_date" name="service_date" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="customer_address"><?php _e('Service Address', 'mobooking'); ?> *</label>
                        <textarea id="customer_address" name="customer_address" rows="3" required 
                                  placeholder="<?php _e('Enter the full address where service will be provided', 'mobooking'); ?>"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="booking_notes"><?php _e('Special Instructions', 'mobooking'); ?></label>
                        <textarea id="booking_notes" name="booking_notes" rows="3" 
                                  placeholder="<?php _e('Any special instructions or requests...', 'mobooking'); ?>"></textarea>
                    </div>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                    <button type="button" class="btn-primary next-step"><?php _e('Review Booking', 'mobooking'); ?></button>
                </div>
            </div>
            
            <!-- Step 5: Review & Confirm -->
            <div class="booking-step step-5">
                <div class="step-header">
                    <h2><?php _e('Review Your Booking', 'mobooking'); ?></h2>
                    <p><?php _e('Please review your booking details before confirming', 'mobooking'); ?></p>
                </div>
                
                <div class="booking-summary">
                    <div class="summary-section">
                        <h3><?php _e('Selected Services', 'mobooking'); ?></h3>
                        <div class="selected-services-list">
                            <!-- Services will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <div class="summary-section">
                        <h3><?php _e('Service Details', 'mobooking'); ?></h3>
                        <div class="service-address"></div>
                        <div class="service-datetime"></div>
                    </div>
                    
                    <div class="summary-section">
                        <h3><?php _e('Contact Information', 'mobooking'); ?></h3>
                        <div class="customer-info"></div>
                    </div>
                    
                    <div class="summary-section discount-section">
                        <h3><?php _e('Discount Code', 'mobooking'); ?></h3>
                        <div class="discount-input-group">
                            <input type="text" id="discount_code" name="discount_code" 
                                   placeholder="<?php _e('Enter discount code', 'mobooking'); ?>">
                            <button type="button" class="apply-discount-btn"><?php _e('Apply', 'mobooking'); ?></button>
                        </div>
                        <div class="discount-message"></div>
                    </div>
                    
                    <?php if ($settings->show_price_breakdown) : ?>
                    <div class="summary-section">
                        <h3><?php _e('Pricing', 'mobooking'); ?></h3>
                        <div class="pricing-summary">
                            <div class="pricing-line">
                                <span class="label"><?php _e('Subtotal', 'mobooking'); ?></span>
                                <span class="amount subtotal">$0.00</span>
                            </div>
                            <div class="pricing-line discount" style="display: none;">
                                <span class="label"><?php _e('Discount', 'mobooking'); ?></span>
                                <span class="amount">-$0.00</span>
                            </div>
                            <div class="pricing-line total">
                                <span class="label"><?php _e('Total', 'mobooking'); ?></span>
                                <span class="amount">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="step-actions">
                    <button type="button" class="btn-secondary prev-step"><?php _e('Back', 'mobooking'); ?></button>
                    <button type="submit" class="btn-primary confirm-booking-btn">
                        <span class="btn-text"><?php _e('Confirm Booking', 'mobooking'); ?></span>
                        <span class="btn-loading" style="display: none;"><?php _e('Processing...', 'mobooking'); ?></span>
                    </button>
                </div>
            </div>
            
            <!-- Step 6: Success -->
            <div class="booking-step step-6 step-success">
                <div class="success-content">
                    <div class="success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                        </svg>
                    </div>
                    
                    <h2><?php _e('Booking Confirmed!', 'mobooking'); ?></h2>
                    <p class="success-message"><?php _e('Thank you for your booking. We\'ll contact you shortly to confirm the details.', 'mobooking'); ?></p>
                    
                    <div class="booking-reference">
                        <strong><?php _e('Your booking reference:', 'mobooking'); ?></strong>
                        <span class="reference-number">#0000</span>
                    </div>
                    
                    <div class="next-steps">
                        <p><?php _e('What happens next?', 'mobooking'); ?></p>
                        <ul>
                            <li><?php _e('You\'ll receive a confirmation email shortly', 'mobooking'); ?></li>
                            <li><?php _e('We\'ll contact you to confirm the appointment details', 'mobooking'); ?></li>
                            <li><?php _e('Our team will arrive at the scheduled time', 'mobooking'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="success-actions">
                        <button type="button" class="btn-primary new-booking-btn" onclick="location.reload();">
                            <?php _e('Book Another Service', 'mobooking'); ?>
                        </button>
                        <button type="button" class="btn-secondary print-booking-btn" onclick="window.print();">
                            <?php _e('Print Confirmation', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php if ($settings->show_form_footer && !empty($settings->custom_footer_text)) : ?>
        <div class="booking-form-page-footer" style="text-align: center; padding: 2rem 1rem; margin-top: 2rem; border-top: 1px solid #e5e7eb;">
            <?php echo wp_kses_post($settings->custom_footer_text); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Custom styling based on form settings -->
<style>
:root {
    --booking-primary: <?php echo esc_attr($settings->primary_color); ?>;
    --booking-primary-dark: <?php echo esc_attr($settings->secondary_color); ?>;
    --booking-text: <?php echo esc_attr($settings->text_color); ?>;
    --booking-bg: <?php echo esc_attr($settings->background_color); ?>;
}

.mobooking-booking-form-page {
    background-color: var(--booking-bg);
    color: var(--booking-text);
    min-height: 100vh;
    padding: 1rem 0;
}

.booking-form-page-header h1 {
    color: var(--booking-primary) !important;
}

.mobooking-booking-form-container .btn-primary {
    background: linear-gradient(135deg, var(--booking-primary), var(--booking-primary-dark)) !important;
    border-color: var(--booking-primary) !important;
}

.mobooking-booking-form-container .btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--booking-primary-dark), var(--booking-primary)) !important;
}

.mobooking-booking-form-container .progress-fill {
    background: linear-gradient(90deg, var(--booking-primary), var(--booking-primary-dark)) !important;
}

.mobooking-booking-form-container .step.active .step-number {
    background-color: var(--booking-primary) !important;
    border-color: var(--booking-primary-dark) !important;
}

.mobooking-booking-form-container .service-card.selected {
    border-color: var(--booking-primary) !important;
    background: rgba(<?php echo implode(',', sscanf($settings->primary_color, "#%02x%02x%02x")); ?>, 0.05) !important;
}

.mobooking-booking-form-container .service-card:hover {
    border-color: var(--booking-primary) !important;
}

.mobooking-booking-form-container input:focus,
.mobooking-booking-form-container select:focus,
.mobooking-booking-form-container textarea:focus {
    border-color: var(--booking-primary) !important;
    box-shadow: 0 0 0 3px rgba(<?php echo implode(',', sscanf($settings->primary_color, "#%02x%02x%02x")); ?>, 0.1) !important;
}

.zip-validation-icon.success {
    color: #10b981 !important;
}

.zip-validation-icon.error {
    color: #ef4444 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .mobooking-booking-form-container {
        padding: 0 0.5rem;
        margin: 1rem auto;
    }
    
    .booking-step {
        padding: 1.5rem;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .step-actions {
        flex-direction: column-reverse;
        gap: 0.75rem;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
    }
    
    .success-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .discount-input-group {
        flex-direction: column;
        gap: 0.75rem;
    }
}

@media (max-width: 480px) {
    .booking-form-page-header {
        padding: 1rem !important;
    }
    
    .booking-form-page-header h1 {
        font-size: 1.5rem !important;
    }
    
    .step-header h2 {
        font-size: 1.25rem;
    }
    
    .booking-step {
        padding: 1rem;
    }
    
    .booking-progress {
        padding: 1rem;
    }
    
    .service-card {
        padding: 1rem;
    }
}

/* Loading states */
.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: inline-flex !important;
    align-items: center;
    gap: 0.5rem;
}

.loading .btn-loading::before {
    content: "";
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Print styles */
@media print {
    .step-actions,
    .booking-progress {
        display: none;
    }
    
    .booking-step {
        display: block !important;
    }
    
    .mobooking-booking-form-page {
        background: white !important;
        color: black !important;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .service-card {
        border-width: 3px;
    }
    
    .btn-primary {
        border: 2px solid var(--booking-primary-dark);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<?php if (!empty($settings->custom_css)) : ?>
<style type="text/css">
<?php echo wp_strip_all_tags($settings->custom_css); ?>
</style>
<?php endif; ?>

<?php if (!empty($settings->custom_js)) : ?>
<script type="text/javascript">
<?php echo wp_strip_all_tags($settings->custom_js); ?>
</script>
<?php endif; ?>

<?php if (!empty($settings->analytics_code)) : ?>
<!-- Analytics Code -->
<?php echo wp_strip_all_tags($settings->analytics_code); ?>
<?php endif; ?>

<?php
// Include footer
get_footer();
?>