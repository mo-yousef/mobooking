<?php
// dashboard/sections/booking-form.php - Booking Form Management
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize booking form manager
$booking_form_manager = new \MoBooking\BookingForm\Manager();
$settings = $booking_form_manager->get_settings($user_id);
$booking_url = $booking_form_manager->get_booking_form_url($user_id);
$embed_url = $booking_form_manager->get_embed_url($user_id);

// Get user's services count for validation
$services_manager = new \MoBooking\Services\ServicesManager();
$services = $services_manager->get_user_services($user_id);
$services_count = count($services);

// Get geography areas count
$geography_manager = new \MoBooking\Geography\Manager();
$areas = $geography_manager->get_user_areas($user_id);
$areas_count = count($areas);
?>

<div class="booking-form-section">
    <div class="booking-form-header">
        <div class="booking-form-header-content">
            <div class="booking-form-title-group">
                <h1 class="booking-form-main-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10,9 9,9 8,9"></polyline>
                    </svg>
                    <?php _e('Booking Form', 'mobooking'); ?>
                </h1>
                <p class="booking-form-subtitle"><?php _e('Customize and manage your public booking form', 'mobooking'); ?></p>
            </div>
            
            <div class="booking-form-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $services_count; ?></span>
                    <span class="stat-label"><?php _e('Services', 'mobooking'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $areas_count; ?></span>
                    <span class="stat-label"><?php _e('Service Areas', 'mobooking'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">
                        <span class="status-indicator <?php echo $settings->is_active ? 'active' : 'inactive'; ?>"></span>
                    </span>
                    <span class="stat-label"><?php echo $settings->is_active ? __('Active', 'mobooking') : __('Inactive', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="booking-form-header-actions">
            <button type="button" id="preview-form-btn" class="btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <?php _e('Preview', 'mobooking'); ?>
            </button>
            
            <button type="button" id="copy-link-btn" class="btn-secondary" data-url="<?php echo esc_attr($booking_url); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                </svg>
                <?php _e('Copy Link', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <?php if ($services_count === 0 || $areas_count === 0) : ?>
        <!-- Setup Requirements Warning -->
        <div class="booking-form-warning">
            <div class="warning-content">
                <div class="warning-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <path d="M12 9v4"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                </div>
                <div class="warning-message">
                    <h3><?php _e('Setup Required', 'mobooking'); ?></h3>
                    <p><?php _e('Your booking form needs some setup before it can be published:', 'mobooking'); ?></p>
                    <ul>
                        <?php if ($services_count === 0) : ?>
                            <li>
                                <span class="status-missing">✗</span>
                                <?php _e('Add at least one service', 'mobooking'); ?>
                                <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="setup-link"><?php _e('Add Services', 'mobooking'); ?></a>
                            </li>
                        <?php else : ?>
                            <li><span class="status-complete">✓</span> <?php printf(_n('%d service configured', '%d services configured', $services_count, 'mobooking'), $services_count); ?></li>
                        <?php endif; ?>
                        
                        <?php if ($areas_count === 0) : ?>
                            <li>
                                <span class="status-missing">✗</span>
                                <?php _e('Define service areas (ZIP codes)', 'mobooking'); ?>
                                <a href="<?php echo esc_url(home_url('/dashboard/areas/')); ?>" class="setup-link"><?php _e('Add Areas', 'mobooking'); ?></a>
                            </li>
                        <?php else : ?>
                            <li><span class="status-complete">✓</span> <?php printf(_n('%d service area configured', '%d service areas configured', $areas_count, 'mobooking'), $areas_count); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Form Configuration Tabs -->
    <div class="booking-form-tabs">
        <div class="tab-list" role="tablist">
            <button type="button" class="tab-button active" data-tab="general" role="tab">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
                <span><?php _e('General', 'mobooking'); ?></span>
            </button>
            
            <button type="button" class="tab-button" data-tab="design" role="tab">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"></circle>
                        <circle cx="6" cy="12" r="3"></circle>
                        <circle cx="18" cy="19" r="3"></circle>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                </div>
                <span><?php _e('Design', 'mobooking'); ?></span>
            </button>
            
            <button type="button" class="tab-button" data-tab="sharing" role="tab">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                        <polyline points="16,6 12,2 8,6"></polyline>
                        <line x1="12" y1="2" x2="12" y2="15"></line>
                    </svg>
                </div>
                <span><?php _e('Sharing', 'mobooking'); ?></span>
            </button>
            
            <button type="button" class="tab-button" data-tab="advanced" role="tab">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                </div>
                <span><?php _e('Advanced', 'mobooking'); ?></span>
            </button>
        </div>
    </div>
    
    <!-- Form Settings -->
    <form id="booking-form-settings" class="booking-form-container">
        <?php wp_nonce_field('mobooking-booking-form-nonce', 'nonce'); ?>
        
        <!-- General Tab -->
        <div id="general" class="tab-pane active">
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Basic Information', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Configure the basic details of your booking form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-row">
                        <div class="field-group">
                            <label for="form-title" class="field-label required"><?php _e('Form Title', 'mobooking'); ?></label>
                            <input type="text" id="form-title" name="form_title" class="form-control" 
                                   value="<?php echo esc_attr($settings->form_title); ?>" 
                                   placeholder="<?php _e('Book Our Services', 'mobooking'); ?>" required>
                        </div>
                        
                        <div class="field-group">
                            <label for="form-active" class="field-label"><?php _e('Status', 'mobooking'); ?></label>
                            <select id="form-active" name="is_active" class="form-control">
                                <option value="1" <?php selected($settings->is_active, 1); ?>><?php _e('Active (Published)', 'mobooking'); ?></option>
                                <option value="0" <?php selected($settings->is_active, 0); ?>><?php _e('Inactive (Draft)', 'mobooking'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label for="form-description" class="field-label"><?php _e('Form Description', 'mobooking'); ?></label>
                        <textarea id="form-description" name="form_description" class="form-control" rows="3" 
                                  placeholder="<?php _e('Book our professional services quickly and easily...', 'mobooking'); ?>"><?php echo esc_textarea($settings->form_description); ?></textarea>
                    </div>
                    
                    <div class="field-group">
                        <label for="logo-url" class="field-label"><?php _e('Logo URL', 'mobooking'); ?></label>
                        <div class="input-with-button">
                            <input type="url" id="logo-url" name="logo_url" class="form-control" 
                                   value="<?php echo esc_attr($settings->logo_url); ?>" 
                                   placeholder="<?php _e('https://example.com/logo.png', 'mobooking'); ?>">
                            <button type="button" class="btn-secondary select-logo-btn"><?php _e('Select Logo', 'mobooking'); ?></button>
                        </div>
                        <p class="field-help"><?php _e('Logo will appear at the top of your booking form', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Form Options', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Configure what information to show on your form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="checkbox-grid">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_form_header" value="1" <?php checked($settings->show_form_header, 1); ?>>
                            <span class="checkbox-text"><?php _e('Show form header with title and logo', 'mobooking'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_service_descriptions" value="1" <?php checked($settings->show_service_descriptions, 1); ?>>
                            <span class="checkbox-text"><?php _e('Show service descriptions', 'mobooking'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_price_breakdown" value="1" <?php checked($settings->show_price_breakdown, 1); ?>>
                            <span class="checkbox-text"><?php _e('Show detailed price breakdown', 'mobooking'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="enable_zip_validation" value="1" <?php checked($settings->enable_zip_validation, 1); ?>>
                            <span class="checkbox-text"><?php _e('Enable ZIP code validation', 'mobooking'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_form_footer" value="1" <?php checked($settings->show_form_footer, 1); ?>>
                            <span class="checkbox-text"><?php _e('Show form footer', 'mobooking'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Design Tab -->
        <div id="design" class="tab-pane">
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Colors & Styling', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Customize the appearance of your booking form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="color-fields-grid">
                        <div class="field-group">
                            <label for="primary-color" class="field-label"><?php _e('Primary Color', 'mobooking'); ?></label>
                            <div class="color-input-group">
                                <input type="color" id="primary-color" name="primary_color" 
                                       value="<?php echo esc_attr($settings->primary_color); ?>" class="color-picker">
                                <input type="text" class="color-text" value="<?php echo esc_attr($settings->primary_color); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="field-group">
                            <label for="secondary-color" class="field-label"><?php _e('Secondary Color', 'mobooking'); ?></label>
                            <div class="color-input-group">
                                <input type="color" id="secondary-color" name="secondary_color" 
                                       value="<?php echo esc_attr($settings->secondary_color); ?>" class="color-picker">
                                <input type="text" class="color-text" value="<?php echo esc_attr($settings->secondary_color); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="field-group">
                            <label for="background-color" class="field-label"><?php _e('Background Color', 'mobooking'); ?></label>
                            <div class="color-input-group">
                                <input type="color" id="background-color" name="background_color" 
                                       value="<?php echo esc_attr($settings->background_color); ?>" class="color-picker">
                                <input type="text" class="color-text" value="<?php echo esc_attr($settings->background_color); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="field-group">
                            <label for="text-color" class="field-label"><?php _e('Text Color', 'mobooking'); ?></label>
                            <div class="color-input-group">
                                <input type="color" id="text-color" name="text_color" 
                                       value="<?php echo esc_attr($settings->text_color); ?>" class="color-picker">
                                <input type="text" class="color-text" value="<?php echo esc_attr($settings->text_color); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Layout & Style', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Choose the layout and styling options for your form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-row">
                        <div class="field-group">
                            <label for="form-layout" class="field-label"><?php _e('Form Layout', 'mobooking'); ?></label>
                            <select id="form-layout" name="form_layout" class="form-control">
                                <option value="modern" <?php selected($settings->form_layout, 'modern'); ?>><?php _e('Modern', 'mobooking'); ?></option>
                                <option value="classic" <?php selected($settings->form_layout, 'classic'); ?>><?php _e('Classic', 'mobooking'); ?></option>
                                <option value="minimal" <?php selected($settings->form_layout, 'minimal'); ?>><?php _e('Minimal', 'mobooking'); ?></option>
                            </select>
                        </div>
                        
                        <div class="field-group">
                            <label for="form-width" class="field-label"><?php _e('Form Width', 'mobooking'); ?></label>
                            <select id="form-width" name="form_width" class="form-control">
                                <option value="narrow" <?php selected($settings->form_width, 'narrow'); ?>><?php _e('Narrow (600px)', 'mobooking'); ?></option>
                                <option value="standard" <?php selected($settings->form_width, 'standard'); ?>><?php _e('Standard (800px)', 'mobooking'); ?></option>
                                <option value="wide" <?php selected($settings->form_width, 'wide'); ?>><?php _e('Wide (1000px)', 'mobooking'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="field-row">
                        <div class="field-group">
                            <label for="step-indicator-style" class="field-label"><?php _e('Step Indicator Style', 'mobooking'); ?></label>
                            <select id="step-indicator-style" name="step_indicator_style" class="form-control">
                                <option value="progress" <?php selected($settings->step_indicator_style, 'progress'); ?>><?php _e('Progress Bar', 'mobooking'); ?></option>
                                <option value="steps" <?php selected($settings->step_indicator_style, 'steps'); ?>><?php _e('Step Numbers', 'mobooking'); ?></option>
                                <option value="dots" <?php selected($settings->step_indicator_style, 'dots'); ?>><?php _e('Dots', 'mobooking'); ?></option>
                            </select>
                        </div>
                        
                        <div class="field-group">
                            <label for="button-style" class="field-label"><?php _e('Button Style', 'mobooking'); ?></label>
                            <select id="button-style" name="button_style" class="form-control">
                                <option value="rounded" <?php selected($settings->button_style, 'rounded'); ?>><?php _e('Rounded', 'mobooking'); ?></option>
                                <option value="square" <?php selected($settings->button_style, 'square'); ?>><?php _e('Square', 'mobooking'); ?></option>
                                <option value="pill" <?php selected($settings->button_style, 'pill'); ?>><?php _e('Pill', 'mobooking'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sharing Tab -->
        <div id="sharing" class="tab-pane">
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Public URL', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Share your booking form with customers using these links', 'mobooking'); ?></p>
                </div>
                
                <div class="url-sharing-group">
                    <div class="url-item">
                        <label class="url-label"><?php _e('Direct Link', 'mobooking'); ?></label>
                        <div class="url-input-group">
                            <input type="text" class="url-display" value="<?php echo esc_attr($booking_url); ?>" readonly>
                            <button type="button" class="btn-secondary copy-url-btn" data-url="<?php echo esc_attr($booking_url); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                                </svg>
                                <?php _e('Copy', 'mobooking'); ?>
                            </button>
                            <button type="button" class="btn-secondary open-url-btn" data-url="<?php echo esc_attr($booking_url); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                    <polyline points="15,3 21,3 21,9"></polyline>
                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                </svg>
                                <?php _e('Open', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Embed Code', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Embed your booking form on your website or other platforms', 'mobooking'); ?></p>
                </div>
                
                <div class="embed-section">
                    <div class="embed-options">
                        <div class="field-row">
                            <div class="field-group">
                                <label for="embed-width" class="field-label"><?php _e('Width', 'mobooking'); ?></label>
                                <input type="text" id="embed-width" class="form-control" value="100%" placeholder="100%">
                            </div>
                            <div class="field-group">
                                <label for="embed-height" class="field-label"><?php _e('Height', 'mobooking'); ?></label>
                                <input type="text" id="embed-height" class="form-control" value="800" placeholder="800">
                            </div>
                        </div>
                        <button type="button" id="generate-embed-btn" class="btn-secondary">
                            <?php _e('Generate Embed Code', 'mobooking'); ?>
                        </button>
                    </div>
                    
                    <div class="embed-code-container">
                        <label class="field-label"><?php _e('Embed Code', 'mobooking'); ?></label>
                        <textarea id="embed-code-display" class="form-control embed-code" rows="4" readonly 
                                  placeholder="<?php _e('Click "Generate Embed Code" to create your embed code', 'mobooking'); ?>"></textarea>
                        <button type="button" id="copy-embed-btn" class="btn-secondary" style="display: none;">
                            <?php _e('Copy Embed Code', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('QR Code', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Generate a QR code for easy access to your booking form', 'mobooking'); ?></p>
                </div>
                
                <div class="qr-code-section">
                    <div class="qr-code-display">
                        <div id="qr-code-container"></div>
                    </div>
                    <div class="qr-code-actions">
                        <button type="button" id="generate-qr-btn" class="btn-secondary">
                            <?php _e('Generate QR Code', 'mobooking'); ?>
                        </button>
                        <button type="button" id="download-qr-btn" class="btn-secondary" style="display: none;">
                            <?php _e('Download QR Code', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Advanced Tab -->
        <div id="advanced" class="tab-pane">
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('SEO Settings', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Optimize your booking form for search engines', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="seo-title" class="field-label"><?php _e('Page Title', 'mobooking'); ?></label>
                        <input type="text" id="seo-title" name="seo_title" class="form-control" 
                               value="<?php echo esc_attr($settings->seo_title); ?>" 
                               placeholder="<?php _e('Book Our Professional Services - Your Company Name', 'mobooking'); ?>">
                        <p class="field-help"><?php _e('Recommended length: 50-60 characters', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="field-group">
                        <label for="seo-description" class="field-label"><?php _e('Meta Description', 'mobooking'); ?></label>
                        <textarea id="seo-description" name="seo_description" class="form-control" rows="3" 
                                  placeholder="<?php _e('Book our professional services online. Quick, easy, and secure booking process...', 'mobooking'); ?>"><?php echo esc_textarea($settings->seo_description); ?></textarea>
                        <p class="field-help"><?php _e('Recommended length: 150-160 characters', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Analytics & Tracking', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Add tracking codes to monitor your booking form performance', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="analytics-code" class="field-label"><?php _e('Analytics Code', 'mobooking'); ?></label>
                        <textarea id="analytics-code" name="analytics_code" class="form-control code-input" rows="6" 
                                  placeholder="<!-- Google Analytics, Facebook Pixel, or other tracking codes -->"><?php echo esc_textarea($settings->analytics_code); ?></textarea>
                        <p class="field-help"><?php _e('Add Google Analytics, Facebook Pixel, or other tracking codes here', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Custom CSS', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Add custom styling to further customize your booking form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="custom-css" class="field-label"><?php _e('Custom CSS', 'mobooking'); ?></label>
                        <textarea id="custom-css" name="custom_css" class="form-control code-input" rows="8" 
                                  placeholder="/* Add your custom CSS here */
.mobooking-booking-form-container {
    /* Your styles */
}"><?php echo esc_textarea($settings->custom_css); ?></textarea>
                        <p class="field-help"><?php _e('Advanced users only. Custom CSS will be applied to your booking form.', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Custom JavaScript', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Add custom JavaScript functionality to your booking form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="custom-js" class="field-label"><?php _e('Custom JavaScript', 'mobooking'); ?></label>
                        <textarea id="custom-js" name="custom_js" class="form-control code-input" rows="8" 
                                  placeholder="// Add your custom JavaScript here
// Example: Custom form validation or third-party integrations"><?php echo esc_textarea($settings->custom_js); ?></textarea>
                        <p class="field-help"><?php _e('Advanced users only. JavaScript will be executed on your booking form page.', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="section-header">
                    <h3 class="section-title"><?php _e('Footer Content', 'mobooking'); ?></h3>
                    <p class="section-description"><?php _e('Add custom content to the footer of your booking form', 'mobooking'); ?></p>
                </div>
                
                <div class="form-fields">
                    <div class="field-group">
                        <label for="custom-footer-text" class="field-label"><?php _e('Footer Text', 'mobooking'); ?></label>
                        <textarea id="custom-footer-text" name="custom_footer_text" class="form-control" rows="4" 
                                  placeholder="<?php _e('Contact us at info@yourcompany.com or call (555) 123-4567', 'mobooking'); ?>"><?php echo esc_textarea($settings->custom_footer_text); ?></textarea>
                        <p class="field-help"><?php _e('This content will appear at the bottom of your booking form', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="field-group">
                        <label for="contact-info" class="field-label"><?php _e('Contact Information', 'mobooking'); ?></label>
                        <textarea id="contact-info" name="contact_info" class="form-control" rows="3" 
                                  placeholder="<?php _e('Phone: (555) 123-4567\nEmail: info@company.com\nAddress: 123 Main St', 'mobooking'); ?>"><?php echo esc_textarea($settings->contact_info); ?></textarea>
                    </div>
                    
                    <div class="field-group">
                        <label for="social-links" class="field-label"><?php _e('Social Media Links', 'mobooking'); ?></label>
                        <textarea id="social-links" name="social_links" class="form-control" rows="3" 
                                  placeholder="<?php _e('Facebook: https://facebook.com/yourpage\nInstagram: https://instagram.com/yourprofile', 'mobooking'); ?>"><?php echo esc_textarea($settings->social_links); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions">
            <div class="form-actions-left">
                <button type="button" id="reset-settings-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <?php _e('Reset to Defaults', 'mobooking'); ?>
                </button>
            </div>
            
            <div class="form-actions-right">
                <button type="button" id="save-draft-btn" class="btn-secondary">
                    <?php _e('Save Draft', 'mobooking'); ?>
                </button>
                <button type="submit" id="save-settings-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    <span class="btn-text"><?php _e('Save Settings', 'mobooking'); ?></span>
                    <span class="btn-loading"><?php _e('Saving...', 'mobooking'); ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div id="form-preview-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-large">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <div class="modal-header">
            <h3><?php _e('Form Preview', 'mobooking'); ?></h3>
            <div class="preview-actions">
                <button type="button" id="refresh-preview-btn" class="btn-secondary btn-small">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <?php _e('Refresh', 'mobooking'); ?>
                </button>
                <button type="button" id="open-in-new-tab-btn" class="btn-secondary btn-small" data-url="<?php echo esc_attr($booking_url); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15,3 21,3 21,9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <?php _e('Open in New Tab', 'mobooking'); ?>
                </button>
            </div>
        </div>
        
        <div class="modal-body">
            <div class="preview-container">
                <iframe id="form-preview-iframe" src="<?php echo esc_url($booking_url); ?>" 
                        style="width: 100%; height: 600px; border: none; border-radius: 8px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// Booking Form Settings JavaScript
jQuery(document).ready(function($) {
    const BookingFormSettings = {
        config: {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-booking-form-nonce'); ?>',
            bookingUrl: '<?php echo esc_js($booking_url); ?>',
            embedUrl: '<?php echo esc_js($embed_url); ?>'
        },
        
        state: {
            isProcessing: false,
            hasUnsavedChanges: false
        },
        
        init: function() {
            this.attachEventListeners();
            this.initializeColorPickers();
            this.initializeTabs();
            this.trackChanges();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Tab switching
            $('.tab-button').on('click', function() {
                const tabId = $(this).data('tab');
                self.switchTab(tabId);
            });
            
            // Form submission
            $('#booking-form-settings').on('submit', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
            
            // Save draft
            $('#save-draft-btn').on('click', function() {
                self.saveSettings(true);
            });
            
            // Preview
            $('#preview-form-btn').on('click', function() {
                self.showPreview();
            });
            
            // Copy link
            $('#copy-link-btn, .copy-url-btn').on('click', function() {
                const url = $(this).data('url');
                self.copyToClipboard(url);
            });
            
            // Open URL
            $('.open-url-btn').on('click', function() {
                const url = $(this).data('url');
                window.open(url, '_blank');
            });
            
            // Logo selection
            $('.select-logo-btn').on('click', function() {
                self.selectLogo();
            });
            
            // Embed code generation
            $('#generate-embed-btn').on('click', function() {
                self.generateEmbedCode();
            });
            
            // Copy embed code
            $('#copy-embed-btn').on('click', function() {
                const embedCode = $('#embed-code-display').val();
                self.copyToClipboard(embedCode);
            });
            
            // QR Code generation
            $('#generate-qr-btn').on('click', function() {
                self.generateQRCode();
            });
            
            // Reset settings
            $('#reset-settings-btn').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to reset all settings to defaults?', 'mobooking'); ?>')) {
                    self.resetSettings();
                }
            });
            
            // Modal controls
            $('.modal-close').on('click', function() {
                self.hideModals();
            });
            
            // Preview controls
            $('#refresh-preview-btn').on('click', function() {
                self.refreshPreview();
            });
            
            $('#open-in-new-tab-btn').on('click', function() {
                const url = $(this).data('url');
                window.open(url, '_blank');
            });
            
            // Color picker sync
            $('.color-picker').on('input', function() {
                $(this).siblings('.color-text').val($(this).val());
            });
        },
        
        initializeColorPickers: function() {
            // Sync color pickers with text inputs
            $('.color-picker').each(function() {
                const $picker = $(this);
                const $text = $picker.siblings('.color-text');
                
                $picker.on('input', function() {
                    $text.val($(this).val());
                });
                
                $text.on('input', function() {
                    const color = $(this).val();
                    if (/^#[0-9A-F]{6}$/i.test(color)) {
                        $picker.val(color);
                    }
                });
            });
        },
        
        initializeTabs: function() {
            // Get active tab from URL or default to general
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'general';
            this.switchTab(activeTab);
        },
        
        switchTab: function(tabId) {
            // Update button states
            $('.tab-button').removeClass('active');
            $(`.tab-button[data-tab="${tabId}"]`).addClass('active');
            
            // Update tab panes
            $('.tab-pane').removeClass('active');
            $(`#${tabId}`).addClass('active');
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
        },
        
        trackChanges: function() {
            const self = this;
            $('#booking-form-settings input, #booking-form-settings textarea, #booking-form-settings select').on('change', function() {
                self.state.hasUnsavedChanges = true;
            });
            
            // Warn before leaving with unsaved changes
            $(window).on('beforeunload', function() {
                if (self.state.hasUnsavedChanges) {
                    return '<?php _e('You have unsaved changes. Are you sure you want to leave?', 'mobooking'); ?>';
                }
            });
        },
        
        saveSettings: function(isDraft = false) {
            if (this.state.isProcessing) return;
            
            this.state.isProcessing = true;
            const $btn = isDraft ? $('#save-draft-btn') : $('#save-settings-btn');
            this.setLoading($btn, true);
            
            const formData = new FormData($('#booking-form-settings')[0]);
            formData.append('action', 'mobooking_save_booking_form_settings');
            
            if (isDraft) {
                formData.set('is_active', '0');
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.state.hasUnsavedChanges = false;
                        
                        // Update URLs if they changed
                        if (response.data.booking_url) {
                            this.config.bookingUrl = response.data.booking_url;
                            $('.url-display').val(response.data.booking_url);
                            $('#copy-link-btn, .copy-url-btn').data('url', response.data.booking_url);
                        }
                    } else {
                        this.showNotification(response.data?.message || 'Failed to save settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error saving settings', 'error');
                },
                complete: () => {
                    this.state.isProcessing = false;
                    this.setLoading($btn, false);
                }
            });
        },
        
        showPreview: function() {
            $('#form-preview-modal').fadeIn(300);
            $('body').addClass('modal-open');
            this.refreshPreview();
        },
        
        refreshPreview: function() {
            const iframe = document.getElementById('form-preview-iframe');
            if (iframe) {
                iframe.src = iframe.src; // Reload iframe
            }
        },
        
        selectLogo: function() {
            if (typeof wp !== 'undefined' && wp.media) {
                const mediaUploader = wp.media({
                    title: 'Choose Logo',
                    button: { text: 'Use This Logo' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                mediaUploader.on('select', () => {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#logo-url').val(attachment.url);
                    this.state.hasUnsavedChanges = true;
                });
                
                mediaUploader.open();
            } else {
                const logoUrl = prompt('Enter logo URL:');
                if (logoUrl) {
                    $('#logo-url').val(logoUrl);
                    this.state.hasUnsavedChanges = true;
                }
            }
        },
        
        generateEmbedCode: function() {
            const width = $('#embed-width').val() || '100%';
            const height = $('#embed-height').val() || '800';
            
            const data = {
                action: 'mobooking_generate_embed_code',
                width: width,
                height: height,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        $('#embed-code-display').val(response.data.embed_code);
                        $('#copy-embed-btn').show();
                    } else {
                        this.showNotification('Failed to generate embed code', 'error');
                    }
                }
            });
        },
        
        generateQRCode: function() {
            // Simple QR code generation using a public API
            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(this.config.bookingUrl)}`;
            
            $('#qr-code-container').html(`
                <img src="${qrCodeUrl}" alt="QR Code" style="max-width: 200px; height: auto;">
            `);
            
            $('#download-qr-btn').show().off('click').on('click', () => {
                const link = document.createElement('a');
                link.download = 'booking-form-qr-code.png';
                link.href = qrCodeUrl;
                link.click();
            });
        },
        
        resetSettings: function() {
            // Reset form to default values
            location.reload(); // Simple approach - reload page
        },
        
        copyToClipboard: function(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Copied to clipboard', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showNotification('Copied to clipboard', 'success');
            }
        },
        
        hideModals: function() {
            $('.mobooking-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        },
        
        setLoading: function($btn, loading) {
            if (loading) {
                $btn.addClass('loading').prop('disabled', true);
            } else {
                $btn.removeClass('loading').prop('disabled', false);
            }
        },
        
        showNotification: function(message, type = 'info') {
            const colors = {
                success: '#22c55e',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6',
            };
            
            const notification = $(`
                <div class="notification notification-${type}" style="
                    position: fixed; top: 24px; right: 24px; z-index: 1000;
                    padding: 16px 20px; border-radius: 8px;
                    background: ${colors[type]}; color: white;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    font-weight: 500; max-width: 400px;
                    animation: slideIn 0.3s ease;
                ">${message}</div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }
    };
    
    BookingFormSettings.init();
});
</script>

<style>
/* Additional CSS for booking form section */
.booking-form-section {
    animation: fadeIn 0.4s ease-out;
}

.booking-form-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.booking-form-header-content {
    display: flex
;
    align-items: flex-start;
    gap: 30px;
    flex: 1;
    flex-direction: column;
}

.booking-form-title-group {
    flex: 1;
}

.booking-form-main-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.booking-form-subtitle {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 1rem;
}

.booking-form-stats {
    display: flex;
    gap: 2rem;
}

.booking-form-header-actions {
    display: flex;
    gap: 1rem;
}

.booking-form-warning {
    background: linear-gradient(135deg, hsl(var(--warning) / 0.1), hsl(var(--warning) / 0.05));
    border: 1px solid hsl(var(--warning) / 0.3);
    border-radius: var(--radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.warning-content {
    display: flex;
    gap: 1rem;
}

.warning-icon svg {
    width: 2rem;
    height: 2rem;
    color: hsl(var(--warning));
}

.warning-message h3 {
    margin: 0 0 0.5rem 0;
    color: hsl(var(--warning));
}

.warning-message ul {
    margin: 1rem 0 0 0;
    padding-left: 0;
    list-style: none;
}

.warning-message li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.status-complete {
    color: hsl(var(--success));
    font-weight: bold;
}

.status-missing {
    color: hsl(var(--destructive));
    font-weight: bold;
}

.setup-link {
    margin-left: auto;
    color: hsl(var(--primary));
    text-decoration: underline;
    font-weight: 600;
}

.status-indicator {
    display: inline-block;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

.status-indicator.active {
    background-color: hsl(var(--success));
}

.status-indicator.inactive {
    background-color: hsl(var(--muted-foreground));
}

.color-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.color-picker {
    width: 3rem;
    height: 2.5rem;
    padding: 0;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
}

.color-text {
    flex: 1;
    font-family: monospace;
}

.color-fields-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.checkbox-label:hover {
    background-color: hsl(var(--accent));
    border-color: hsl(var(--primary) / 0.3);
}

.checkbox-label input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
}

.url-sharing-group {
    margin-bottom: 2rem;
}

.url-item {
    margin-bottom: 1rem;
}

.url-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: hsl(var(--foreground));
}

.url-input-group {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
}

.url-display {
    flex: 1;
    font-family: monospace;
    background-color: hsl(var(--muted) / 0.5);
}

.embed-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.embed-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.embed-code {
    font-family: monospace;
    background-color: hsl(var(--muted) / 0.5);
    min-height: 100px;
    resize: vertical;
}

.qr-code-section {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

.qr-code-display {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dashed hsl(var(--border));
    border-radius: var(--radius);
    padding: 1rem;
}

.qr-code-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.code-input {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
    background-color: hsl(var(--muted) / 0.5);
    border: 1px solid hsl(var(--border));
    font-size: 0.875rem;
    line-height: 1.5;
}

.input-with-button {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
}

.input-with-button input {
    flex: 1;
}

.modal-large {
    width: 90vw;
    max-width: 1200px;
    max-height: 90vh;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem 0 2rem;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.preview-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
}

.modal-body {
    padding: 1.5rem 2rem 2rem;
}

.preview-container {
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    background: white;
}

.embed-code-container {
    position: relative;
}

.embed-code-container button {
    margin-top: 0.75rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .booking-form-header {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .booking-form-header-content {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .booking-form-stats {
        align-self: center;
    }
    
    .booking-form-header-actions {
        align-self: stretch;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .booking-form-tabs .tab-list {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .tab-button {
        justify-content: flex-start;
    }
    
    .field-row {
        grid-template-columns: 1fr;
    }
    
    .color-fields-grid {
        grid-template-columns: 1fr;
    }
    
    .checkbox-grid {
        grid-template-columns: 1fr;
    }
    
    .url-input-group {
        flex-direction: column;
    }
    
    .qr-code-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column-reverse;
        gap: 1rem;
    }
    
    .form-actions-left,
    .form-actions-right {
        width: 100%;
        justify-content: center;
    }
    
    .modal-large {
        width: 95vw;
        height: 90vh;
    }
    
    .modal-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .preview-actions {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 480px) {
    .booking-form-main-title {
        font-size: 1.5rem;
    }
    
    .booking-form-stats {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
    }
    
    .warning-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .warning-message li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .setup-link {
        margin-left: 0;
        align-self: flex-start;
    }
    
    .tab-button {
        padding: 0.75rem 1rem;
    }
    
    .tab-icon {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .btn-small {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }
}

/* Animation keyframes */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Loading states */
.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: inline-flex;
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

/* Form validation states */
.form-control.error {
    border-color: hsl(var(--destructive));
    box-shadow: 0 0 0 2px hsl(var(--destructive) / 0.2);
}

.field-help {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.25rem;
    line-height: 1.4;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .status-indicator.active {
        border: 2px solid hsl(var(--success));
    }
    
    .status-indicator.inactive {
        border: 2px solid hsl(var(--muted-foreground));
    }
    
    .checkbox-label {
        border-width: 2px;
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

/* Print styles */
@media print {
    .booking-form-header-actions,
    .form-actions,
    .preview-actions {
        display: none;
    }
    
    .tab-list {
        display: none;
    }
    
    .tab-pane {
        display: block !important;
    }
}
</style>

<?php
// Add any additional PHP logic or includes here if needed

// Enqueue additional scripts if required
wp_enqueue_script('wp-color-picker');
wp_enqueue_style('wp-color-picker');

// Add media uploader support
wp_enqueue_media();

// Localize script for AJAX calls and translations
$localize_data = array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking-booking-form-nonce'),
    'bookingUrl' => $booking_url,
    'embedUrl' => $embed_url,
    'strings' => array(
        'saving' => __('Saving...', 'mobooking'),
        'saved' => __('Settings saved successfully', 'mobooking'),
        'error' => __('An error occurred', 'mobooking'),
        'copied' => __('Copied to clipboard', 'mobooking'),
        'confirmReset' => __('Are you sure you want to reset all settings to defaults? This action cannot be undone.', 'mobooking'),
        'unsavedChanges' => __('You have unsaved changes. Are you sure you want to leave?', 'mobooking'),
        'generateEmbed' => __('Generating embed code...', 'mobooking'),
        'generateQR' => __('Generating QR code...', 'mobooking'),
        'selectLogo' => __('Select Logo Image', 'mobooking'),
        'chooseFile' => __('Choose File', 'mobooking'),
        'noFile' => __('No file selected', 'mobooking'),
        'invalidUrl' => __('Please enter a valid URL', 'mobooking'),
        'required' => __('This field is required', 'mobooking'),
        'maxLength' => __('Text is too long', 'mobooking'),
        'invalidColor' => __('Please enter a valid color code', 'mobooking')
    ),
    'settings' => array(
        'servicesCount' => $services_count,
        'areasCount' => $areas_count,
        'isActive' => $settings->is_active,
        'canPublish' => ($services_count > 0 && $areas_count > 0)
    )
);

wp_localize_script('mobooking-dashboard', 'mobookingBookingForm', $localize_data);
?>