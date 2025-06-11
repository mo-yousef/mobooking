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

// Check if form can be published
$can_publish = ($services_count > 0 && $areas_count > 0);
?>

<?php
/**
 * Updated dashboard/sections/booking-form.php
 * Key changes:
 * 1. Remove testimonials fields
 * 2. Add new fields: step_indicator_style, language, custom_js, button_style
 * 3. Replace logo media uploader with generic file upload
 * 4. Improve social links handling with structured repeatable fields
 */

// Get current user and settings
$current_user = wp_get_current_user();
$booking_form_manager = new \MoBooking\BookingForm\Manager();
$settings = $booking_form_manager->get_settings($current_user->ID);
?>


    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <h1 class="page-title">
                    <svg class="title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 2V4H20.0066C20.5552 4 21 4.44495 21 4.9934V21.0066C21 21.5552 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5551 3 21.0066V4.9934C3 4.44476 3.44495 4 3.9934 4H7V2H17ZM7 6H5V20H19V6H17V8H7V6ZM9 16V18H7V16H9ZM9 13V15H7V13H9ZM9 10V12H7V10H9ZM15 4H9V6H15V4Z"></path></svg>
                    <?php _e('Booking Form', 'mobooking'); ?>
                </h1>
                <p class="page-subtitle"><?php _e('Customize your booking form appearance and functionality', 'mobooking'); ?></p>
            </div>

            <div class="header-actions">
                <button type="button" id="reset-settings-btn" class="btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                    <?php _e('Reset to Defaults', 'mobooking'); ?>
                </button>
                <button type="submit" form="booking-form-settings" id="save-settings-btn" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17,21 17,13 7,13 7,21"/>
                        <polyline points="7,3 7,8 15,8"/>
                    </svg>
                    <?php _e('Save Settings', 'mobooking'); ?>
                </button>
            </div>
        </div>
    </div>
    <div class="settings-container">
        <div class="settings-tabs">
            <button type="button" class="tab-button active" data-tab="general">
                <div class="tab-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                    </svg>
                </div>
                <span><?php _e('General', 'mobooking'); ?></span>
            </button>
            <button type="button" class="tab-button" data-tab="design">
                <div class="tab-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                    </svg>
                </div>
                <span><?php _e('Design', 'mobooking'); ?></span>
            </button>
            <button type="button" class="tab-button" data-tab="advanced">
                <div class="tab-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                <span><?php _e('Advanced', 'mobooking'); ?></span>
            </button>
            <button type="button" class="tab-button" data-tab="share">
                <div class="tab-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                        <polyline points="16,6 12,2 8,6"/>
                        <line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                </div>
                <span><?php _e('Share & Embed', 'mobooking'); ?></span>
            </button>
        </div>

        <form id="booking-form-settings" method="post">
            <?php wp_nonce_field('mobooking-booking-form-nonce', 'nonce'); ?>
            
            <div class="form-content">
                <!-- General Tab -->
                <div class="tab-content active" data-tab="general">
                    <div class="content-grid">
                        <!-- Basic Information -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Basic Information', 'mobooking'); ?></h3>
                                <p><?php _e('Configure the basic details of your booking form', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="form-title" class="field-label required"><?php _e('Form Title', 'mobooking'); ?></label>
                                    <input type="text" id="form-title" name="form_title" class="form-control" 
                                           value="<?php echo esc_attr($settings->form_title); ?>" required>
                                    <small class="field-note"><?php _e('This appears as the main heading on your booking form', 'mobooking'); ?></small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="form-description" class="field-label"><?php _e('Form Description', 'mobooking'); ?></label>
                                    <textarea id="form-description" name="form_description" class="form-control" rows="3"
                                              placeholder="<?php _e('Book our professional services quickly and easily...', 'mobooking'); ?>"><?php echo esc_textarea($settings->form_description); ?></textarea>
                                    <small class="field-note"><?php _e('Brief description shown below the title', 'mobooking'); ?></small>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="language" class="field-label"><?php _e('Language', 'mobooking'); ?></label>
                                        <select id="language" name="language" class="form-control">
                                            <option value="en" <?php selected($settings->language, 'en'); ?>><?php _e('English', 'mobooking'); ?></option>
                                            <option value="es" <?php selected($settings->language, 'es'); ?>><?php _e('Spanish', 'mobooking'); ?></option>
                                            <option value="fr" <?php selected($settings->language, 'fr'); ?>><?php _e('French', 'mobooking'); ?></option>
                                            <option value="de" <?php selected($settings->language, 'de'); ?>><?php _e('German', 'mobooking'); ?></option>
                                            <option value="it" <?php selected($settings->language, 'it'); ?>><?php _e('Italian', 'mobooking'); ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="is-active" class="field-label"><?php _e('Form Status', 'mobooking'); ?></label>
                                        <select id="is-active" name="is_active" class="form-control">
                                            <option value="1" <?php selected($settings->is_active, 1); ?>><?php _e('Active', 'mobooking'); ?></option>
                                            <option value="0" <?php selected($settings->is_active, 0); ?>><?php _e('Inactive', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Features -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Form Features', 'mobooking'); ?></h3>
                                <p><?php _e('Enable or disable specific features in your booking form', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="checkbox-grid">
                                    <div class="checkbox-option">
                                        <input type="checkbox" id="show-form-header" name="show_form_header" value="1" 
                                               <?php checked($settings->show_form_header, 1); ?>>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title"><?php _e('Show Form Header', 'mobooking'); ?></div>
                                            <div class="checkbox-desc"><?php _e('Display title, description, and logo at the top', 'mobooking'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="checkbox-option">
                                        <input type="checkbox" id="show-service-descriptions" name="show_service_descriptions" value="1" 
                                               <?php checked($settings->show_service_descriptions, 1); ?>>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title"><?php _e('Show Service Descriptions', 'mobooking'); ?></div>
                                            <div class="checkbox-desc"><?php _e('Display detailed descriptions for each service', 'mobooking'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="checkbox-option">
                                        <input type="checkbox" id="show-price-breakdown" name="show_price_breakdown" value="1" 
                                               <?php checked($settings->show_price_breakdown, 1); ?>>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title"><?php _e('Show Price Breakdown', 'mobooking'); ?></div>
                                            <div class="checkbox-desc"><?php _e('Display detailed pricing information', 'mobooking'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="checkbox-option">
                                        <input type="checkbox" id="enable-zip-validation" name="enable_zip_validation" value="1" 
                                               <?php checked($settings->enable_zip_validation, 1); ?>>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title"><?php _e('ZIP Code Validation', 'mobooking'); ?></div>
                                            <div class="checkbox-desc"><?php _e('Validate ZIP codes for service area coverage', 'mobooking'); ?></div>
                                        </div>
                                    </div>

                                    <div class="checkbox-option">
                                        <input type="checkbox" id="show-form-footer" name="show_form_footer" value="1" 
                                               <?php checked($settings->show_form_footer, 1); ?>>
                                        <div class="checkbox-content">
                                            <div class="checkbox-title"><?php _e('Show Form Footer', 'mobooking'); ?></div>
                                            <div class="checkbox-desc"><?php _e('Display footer content and social links', 'mobooking'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Design Tab -->
                <div class="tab-content" data-tab="design">
                    <div class="content-grid">
                        <!-- Branding & Colors -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Branding & Colors', 'mobooking'); ?></h3>
                                <p><?php _e('Customize the visual appearance of your booking form', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <!-- UPDATED: Generic File Upload for Logo -->
                                <div class="form-group">
                                    <label for="logo-upload" class="field-label"><?php _e('Logo', 'mobooking'); ?></label>
                                    <div class="logo-upload-container">
                                        <!-- Display current logo if exists -->
                                        <?php if (!empty($settings->logo_url)): ?>
                                            <div id="current-logo-display" class="current-logo">
                                                <img src="<?php echo esc_url($settings->logo_url); ?>" alt="Current Logo" style="max-height: 80px; max-width: 200px;">
                                                <div class="logo-actions">
                                                    <button type="button" id="remove-logo-btn" class="btn-secondary">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="3,6 5,6 21,6"/>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                        </svg>
                                                        <?php _e('Remove', 'mobooking'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- File upload field -->
                                        <div class="file-upload-section">
                                            <input type="file" id="logo-upload" name="logo_file" accept="image/*" class="file-input">
                                            <label for="logo-upload" class="file-upload-label">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                                    <circle cx="9" cy="9" r="2"/>
                                                    <path d="M21 15l-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                                </svg>
                                                <span><?php _e('Choose Logo Image', 'mobooking'); ?></span>
                                            </label>
                                            <!-- Hidden URL field for storing the uploaded logo URL -->
                                            <input type="hidden" id="logo-url" name="logo_url" value="<?php echo esc_attr($settings->logo_url); ?>">
                                        </div>
                                        <small class="field-note"><?php _e('Upload PNG, JPG, or SVG. Recommended size: 200x80px', 'mobooking'); ?></small>
                                    </div>
                                </div>
                                
                                <div class="color-fields">
                                    <div class="form-group">
                                        <label for="primary-color" class="field-label"><?php _e('Primary Color', 'mobooking'); ?></label>
                                        <div class="color-input-group">
                                            <input type="color" id="primary-color" name="primary_color" 
                                                   value="<?php echo esc_attr($settings->primary_color); ?>" class="color-picker">
                                            <input type="text" class="color-text" value="<?php echo esc_attr($settings->primary_color); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="secondary-color" class="field-label"><?php _e('Secondary Color', 'mobooking'); ?></label>
                                        <div class="color-input-group">
                                            <input type="color" id="secondary-color" name="secondary_color" 
                                                   value="<?php echo esc_attr($settings->secondary_color); ?>" class="color-picker">
                                            <input type="text" class="color-text" value="<?php echo esc_attr($settings->secondary_color); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="background-color" class="field-label"><?php _e('Background Color', 'mobooking'); ?></label>
                                        <div class="color-input-group">
                                            <input type="color" id="background-color" name="background_color" 
                                                   value="<?php echo esc_attr($settings->background_color); ?>" class="color-picker">
                                            <input type="text" class="color-text" value="<?php echo esc_attr($settings->background_color); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="form-group">
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
                        
                        <!-- Layout & Style -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Layout & Style', 'mobooking'); ?></h3>
                                <p><?php _e('Adjust the layout and presentation of your form', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="form-layout" class="field-label"><?php _e('Form Layout', 'mobooking'); ?></label>
                                        <select id="form-layout" name="form_layout" class="form-control">
                                            <option value="modern" <?php selected($settings->form_layout, 'modern'); ?>><?php _e('Modern', 'mobooking'); ?></option>
                                            <option value="classic" <?php selected($settings->form_layout, 'classic'); ?>><?php _e('Classic', 'mobooking'); ?></option>
                                            <option value="minimal" <?php selected($settings->form_layout, 'minimal'); ?>><?php _e('Minimal', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="form-width" class="field-label"><?php _e('Form Width', 'mobooking'); ?></label>
                                        <select id="form-width" name="form_width" class="form-control">
                                            <option value="narrow" <?php selected($settings->form_width, 'narrow'); ?>><?php _e('Narrow (600px)', 'mobooking'); ?></option>
                                            <option value="standard" <?php selected($settings->form_width, 'standard'); ?>><?php _e('Standard (800px)', 'mobooking'); ?></option>
                                            <option value="wide" <?php selected($settings->form_width, 'wide'); ?>><?php _e('Wide (1000px)', 'mobooking'); ?></option>
                                            <option value="full" <?php selected($settings->form_width, 'full'); ?>><?php _e('Full Width', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="field-row">
                                    <div class="form-group">
                                        <label for="step-indicator-style" class="field-label"><?php _e('Step Indicator Style', 'mobooking'); ?></label>
                                        <select id="step-indicator-style" name="step_indicator_style" class="form-control">
                                            <option value="progress" <?php selected($settings->step_indicator_style, 'progress'); ?>><?php _e('Progress Bar', 'mobooking'); ?></option>
                                            <option value="dots" <?php selected($settings->step_indicator_style, 'dots'); ?>><?php _e('Dots', 'mobooking'); ?></option>
                                            <option value="numbers" <?php selected($settings->step_indicator_style, 'numbers'); ?>><?php _e('Numbers', 'mobooking'); ?></option>
                                            <option value="arrows" <?php selected($settings->step_indicator_style, 'arrows'); ?>><?php _e('Arrows', 'mobooking'); ?></option>
                                            <option value="none" <?php selected($settings->step_indicator_style, 'none'); ?>><?php _e('None', 'mobooking'); ?></option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="button-style" class="field-label"><?php _e('Button Style', 'mobooking'); ?></label>
                                        <select id="button-style" name="button_style" class="form-control">
                                            <option value="rounded" <?php selected($settings->button_style, 'rounded'); ?>><?php _e('Rounded', 'mobooking'); ?></option>
                                            <option value="square" <?php selected($settings->button_style, 'square'); ?>><?php _e('Square', 'mobooking'); ?></option>
                                            <option value="pill" <?php selected($settings->button_style, 'pill'); ?>><?php _e('Pill', 'mobooking'); ?></option>
                                            <option value="outline" <?php selected($settings->button_style, 'outline'); ?>><?php _e('Outline', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMPROVED: Social Links Section -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Social Media Links', 'mobooking'); ?></h3>
                                <p><?php _e('Add your social media profiles to appear in the form footer', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div id="social-links-container" class="social-links-container">
                                    <?php
                                    // Parse existing social links
                                    $social_links = [];
                                    if (!empty($settings->social_links)) {
                                        $lines = explode("\n", $settings->social_links);
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (strpos($line, ':') !== false) {
                                                list($platform, $url) = explode(':', $line, 2);
                                                $social_links[trim($platform)] = trim($url);
                                            }
                                        }
                                    }
                                    
                                    // Predefined social platforms
                                    $platforms = [
                                        'Facebook' => 'https://facebook.com/',
                                        'Instagram' => 'https://instagram.com/',
                                        'Twitter' => 'https://twitter.com/',
                                        'LinkedIn' => 'https://linkedin.com/company/',
                                        'YouTube' => 'https://youtube.com/@',
                                        'TikTok' => 'https://tiktok.com/@'
                                    ];
                                    
                                    foreach ($platforms as $platform => $placeholder): ?>
                                        <div class="social-link-field">
                                            <label class="field-label"><?php echo esc_html($platform); ?></label>
                                            <div class="input-with-icon">
                                                <svg width="16" height="16" class="social-icon">
                                                    <use href="#icon-<?php echo strtolower($platform); ?>"></use>
                                                </svg>
                                                <input type="url" 
                                                       name="social_<?php echo strtolower($platform); ?>" 
                                                       class="form-control social-url-input" 
                                                       placeholder="<?php echo esc_attr($placeholder . 'yourprofile'); ?>"
                                                       value="<?php echo esc_attr($social_links[$platform] ?? ''); ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Hidden field to store the combined social links in the original format -->
                                <input type="hidden" id="social-links-combined" name="social_links" value="<?php echo esc_attr($settings->social_links); ?>">
                                
                                <small class="field-note"><?php _e('Enter your social media profile URLs. Leave blank for platforms you don\'t use.', 'mobooking'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div class="tab-content" data-tab="advanced">
                    <div class="content-grid">
                        <!-- Custom Code -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Custom Code', 'mobooking'); ?></h3>
                                <p><?php _e('Add custom CSS and JavaScript to enhance your form', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="custom-css" class="field-label"><?php _e('Custom CSS', 'mobooking'); ?></label>
                                    <textarea id="custom-css" name="custom_css" class="form-control code-textarea" rows="8" 
                                              placeholder="<?php _e('/* Custom CSS styles */\n.booking-form {\n    /* Your styles here */\n}', 'mobooking'); ?>"><?php echo esc_textarea($settings->custom_css); ?></textarea>
                                    <small class="field-note"><?php _e('Custom CSS will override default styles.', 'mobooking'); ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="custom-js" class="field-label"><?php _e('Custom JavaScript', 'mobooking'); ?></label>
                                    <textarea id="custom-js" name="custom_js" class="form-control code-textarea" rows="8" 
                                              placeholder="<?php _e('// Custom JavaScript code\n// Will be executed when the form loads\nconsole.log(\'Booking form loaded\');', 'mobooking'); ?>"><?php echo esc_textarea($settings->custom_js); ?></textarea>
                                    <small class="field-note warning"><?php _e('Use with caution. Invalid JavaScript can break your form functionality.', 'mobooking'); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Settings -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('SEO Optimization', 'mobooking'); ?></h3>
                                <p><?php _e('Optimize your booking form for search engines', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="seo-title" class="field-label"><?php _e('Page Title', 'mobooking'); ?></label>
                                    <input type="text" id="seo-title" name="seo_title" class="form-control" 
                                           value="<?php echo esc_attr($settings->seo_title); ?>" 
                                           placeholder="<?php _e('Book Our Services - Company Name', 'mobooking'); ?>">
                                    <small class="field-note"><?php _e('Appears in browser title bar and search results', 'mobooking'); ?></small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="seo-description" class="field-label"><?php _e('Meta Description', 'mobooking'); ?></label>
                                    <textarea id="seo-description" name="seo_description" class="form-control" rows="3" 
                                              placeholder="<?php _e('Book our professional services easily online. Fast, reliable, and convenient scheduling...', 'mobooking'); ?>"><?php echo esc_textarea($settings->seo_description); ?></textarea>
                                    <small class="field-note"><?php _e('Brief description for search engines (150-160 characters recommended)', 'mobooking'); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Analytics -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Analytics & Tracking', 'mobooking'); ?></h3>
                                <p><?php _e('Add tracking codes to monitor form performance', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="analytics-code" class="field-label"><?php _e('Analytics Code', 'mobooking'); ?></label>
                                    <textarea id="analytics-code" name="analytics_code" class="form-control code-textarea" rows="6" 
                                              placeholder="<?php _e('<!-- Google Analytics, Facebook Pixel, or other tracking codes -->', 'mobooking'); ?>"><?php echo esc_textarea($settings->analytics_code); ?></textarea>
                                    <small class="field-note"><?php _e('Add Google Analytics, Facebook Pixel, or other tracking codes', 'mobooking'); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Content -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3><?php _e('Form Footer', 'mobooking'); ?></h3>
                                <p><?php _e('Add additional information to the bottom of your form', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="custom-footer-text" class="field-label"><?php _e('Footer Text', 'mobooking'); ?></label>
                                    <textarea id="custom-footer-text" name="custom_footer_text" class="form-control" rows="4" 
                                              placeholder="<?php _e('Contact us at info@yourcompany.com or call (555) 123-4567', 'mobooking'); ?>"><?php echo esc_textarea($settings->custom_footer_text); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact-info" class="field-label"><?php _e('Contact Information', 'mobooking'); ?></label>
                                    <textarea id="contact-info" name="contact_info" class="form-control" rows="3" 
                                              placeholder="<?php _e('Phone: (555) 123-4567\nEmail: info@company.com\nAddress: 123 Main St', 'mobooking'); ?>"><?php echo esc_textarea($settings->contact_info); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Share & Embed Tab -->
                <div class="tab-content" data-tab="share">
                    <div class="content-grid">
                        <div class="share-options">
                            <!-- Direct Link -->
                            <div class="share-option">
                                <div class="share-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                    </svg>
                                </div>
                                <div class="share-content">
                                    <h4><?php _e('Direct Link', 'mobooking'); ?></h4>
                                    <p><?php _e('Share this URL to let customers access your booking form directly', 'mobooking'); ?></p>
                                    <div class="url-input-group">
                                        <input type="text" id="booking-url" class="form-control url-display" readonly 
                                               value="<?php echo esc_url($booking_form_manager->get_booking_form_url($current_user->ID)); ?>">
                                        <button type="button" class="btn-secondary copy-btn" data-target="#booking-url">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                            </svg>
                                            <?php _e('Copy', 'mobooking'); ?>
                                        </button>
                                        <button type="button" class="btn-primary open-btn" data-url-target="#booking-url">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                <polyline points="15,3 21,3 21,9"/>
                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                            </svg>
                                            <?php _e('Open', 'mobooking'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Embed Code -->
                            <div class="share-option">
                                <div class="share-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="16,18 22,12 16,6"/>
                                        <polyline points="8,6 2,12 8,18"/>
                                    </svg>
                                </div>
                                <div class="share-content">
                                    <h4><?php _e('Embed Code', 'mobooking'); ?></h4>
                                    <p><?php _e('Embed the booking form directly into your website', 'mobooking'); ?></p>
                                    <div class="embed-controls">
                                        <div class="embed-settings">
                                            <div class="field-row">
                                                <div class="form-group">
                                                    <label for="embed-width" class="field-label"><?php _e('Width', 'mobooking'); ?></label>
                                                    <input type="text" id="embed-width" class="form-control" value="100%" placeholder="800px or 100%">
                                                </div>
                                                <div class="form-group">
                                                    <label for="embed-height" class="field-label"><?php _e('Height', 'mobooking'); ?></label>
                                                    <input type="text" id="embed-height" class="form-control" value="800" placeholder="600">
                                                </div>
                                            </div>
                                            <button type="button" id="generate-embed-btn" class="btn-secondary">
                                                <?php _e('Generate Embed Code', 'mobooking'); ?>
                                            </button>
                                        </div>
                                        <div class="embed-code-section">
                                            <label for="embed-code" class="field-label"><?php _e('Embed Code', 'mobooking'); ?></label>
                                            <textarea id="embed-code" class="form-control code-textarea" rows="4" readonly 
                                                      placeholder="<?php _e('Click \'Generate Embed Code\' to create the iframe code', 'mobooking'); ?>"></textarea>
                                            <button type="button" class="btn-secondary copy-btn" data-target="#embed-code">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                                </svg>
                                                <?php _e('Copy Code', 'mobooking'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Code -->
                            <div class="share-option">
                                <div class="share-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect width="5" height="5" x="3" y="3" rx="1"/>
                                        <rect width="5" height="5" x="16" y="3" rx="1"/>
                                        <rect width="5" height="5" x="3" y="16" rx="1"/>
                                        <path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
                                        <path d="M21 21v.01"/>
                                        <path d="M12 7v3a2 2 0 0 1-2 2H7"/>
                                        <path d="M3 12h.01"/>
                                        <path d="M12 3h.01"/>
                                        <path d="M12 16v.01"/>
                                        <path d="M16 12h1"/>
                                        <path d="M21 12v.01"/>
                                        <path d="M12 21v-1"/>
                                    </svg>
                                </div>
                                <div class="share-content">
                                    <h4><?php _e('QR Code', 'mobooking'); ?></h4>
                                    <p><?php _e('Generate a QR code that customers can scan to access your booking form', 'mobooking'); ?></p>
                                    <div class="qr-controls">
                                        <button type="button" id="generate-qr-btn" class="btn-secondary">
                                            <?php _e('Generate QR Code', 'mobooking'); ?>
                                        </button>
                                        <div id="qr-display" class="qr-display" style="display: none;">
                                            <!-- QR code will be inserted here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>


<!-- Enhanced CSS for new features -->
<style>
/* Logo upload styles */
.logo-upload-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.current-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--muted) / 0.1);
}

.logo-actions {
    display: flex;
    gap: 0.5rem;
}

.file-upload-section {
    position: relative;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.file-upload-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border: 2px dashed hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    cursor: pointer;
    transition: all 0.2s ease;
}

.file-upload-label:hover {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary) / 0.05);
}

/* Social links styles */
.social-links-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.social-link-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.social-icon {
    position: absolute;
    left: 0.75rem;
    z-index: 1;
    color: hsl(var(--muted-foreground));
}

.input-with-icon input {
    padding-left: 2.5rem;
}

/* Code textarea styles */
.code-textarea {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
    line-height: 1.4;
    font-size: 0.8125rem;
    background: hsl(var(--muted) / 0.1);
}

/* QR code styles */
.qr-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.qr-display {
    text-align: center;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
}

/* Enhanced button styles */
.btn-primary, .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid;
    text-decoration: none;
}

.btn-primary {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

.btn-primary:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.btn-secondary {
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border-color: hsl(var(--border));
}

.btn-secondary:hover {
    background: hsl(var(--secondary) / 0.8);
    border-color: hsl(var(--primary) / 0.3);
}

/* Responsive design */
@media (max-width: 768px) {
    .field-row {
        grid-template-columns: 1fr;
    }
    
    .color-fields {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .social-links-container {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Enhanced JavaScript for new functionality -->
<script>
jQuery(document).ready(function($) {
    
    // Initialize the enhanced booking form manager
    const EnhancedBookingFormManager = {
        
        init: function() {
            this.initFileUpload();
            this.initSocialLinksHandler();
            this.initColorPickers();
            this.initTabs();
            this.initCopyButtons();
            this.initEmbedGeneration();
            this.initQRGeneration();
            this.initFormSaving();
            this.initResetSettings();
        },
        
        // Handle logo file upload
        initFileUpload: function() {
            $('#logo-upload').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, PNG, or SVG).');
                        return;
                    }
                    
                    // Validate file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB.');
                        return;
                    }
                    
                    // Create FormData and upload
                    const formData = new FormData();
                    formData.append('logo_file', file);
                    formData.append('action', 'mobooking_upload_logo');
                    formData.append('nonce', $('#nonce').val());
                    
                    // Show loading state
                    $('.file-upload-label span').text('Uploading...');
                    
                    $.ajax({
                        url: mobookingDashboard.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                // Update the hidden URL field
                                $('#logo-url').val(response.data.url);
                                
                                // Show the uploaded logo
                                const logoHtml = `
                                    <div id="current-logo-display" class="current-logo">
                                        <img src="${response.data.url}" alt="Current Logo" style="max-height: 80px; max-width: 200px;">
                                        <div class="logo-actions">
                                            <button type="button" id="remove-logo-btn" class="btn-secondary">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3,6 5,6 21,6"/>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                `;
                                
                                // Remove existing logo display and add new one
                                $('#current-logo-display').remove();
                                $('.logo-upload-container').prepend(logoHtml);
                                
                                // Re-bind remove button
                                EnhancedBookingFormManager.bindRemoveLogoButton();
                                
                            } else {
                                alert(response.data || 'Upload failed. Please try again.');
                            }
                        },
                        error: function() {
                            alert('Upload failed. Please try again.');
                        },
                        complete: function() {
                            $('.file-upload-label span').text('Choose Logo Image');
                            $('#logo-upload').val(''); // Reset file input
                        }
                    });
                }
            });
            
            // Bind remove logo button if it exists
            this.bindRemoveLogoButton();
        },
        
        bindRemoveLogoButton: function() {
            $(document).off('click', '#remove-logo-btn').on('click', '#remove-logo-btn', function() {
                if (confirm('Are you sure you want to remove the logo?')) {
                    $('#logo-url').val('');
                    $('#current-logo-display').remove();
                }
            });
        },
        
        // Handle social links input combination
        initSocialLinksHandler: function() {
            $('.social-url-input').on('input', function() {
                EnhancedBookingFormManager.updateCombinedSocialLinks();
            });
        },
        
        updateCombinedSocialLinks: function() {
            const socialLinks = [];
            $('.social-url-input').each(function() {
                const $input = $(this);
                const platform = $input.attr('name').replace('social_', '');
                const url = $input.val().trim();
                
                if (url) {
                    // Capitalize platform name
                    const platformName = platform.charAt(0).toUpperCase() + platform.slice(1);
                    socialLinks.push(platformName + ': ' + url);
                }
            });
            
            $('#social-links-combined').val(socialLinks.join('\n'));
        },
        
        // Enhanced color picker functionality
        initColorPickers: function() {
            $('.color-picker').on('input change', function() {
                const $colorPicker = $(this);
                const $textInput = $colorPicker.siblings('.color-text');
                $textInput.val($colorPicker.val());
            });
        },
        
        // Tab functionality
        initTabs: function() {
            $('.tab-button').on('click', function() {
                const tabId = $(this).data('tab');
                
                // Update active tab button
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                // Update active tab content
                $('.tab-content').removeClass('active');
                $(`.tab-content[data-tab="${tabId}"]`).addClass('active');
            });
        },
        
        // Copy to clipboard functionality
        initCopyButtons: function() {
            $('.copy-btn').on('click', function() {
                const targetSelector = $(this).data('target');
                const $target = $(targetSelector);
                
                if ($target.length) {
                    $target.select();
                    document.execCommand('copy');
                    
                    // Visual feedback
                    const originalText = $(this).find('span').text() || $(this).text();
                    const $button = $(this);
                    
                    $button.addClass('copied');
                    if ($button.find('span').length) {
                        $button.find('span').text('Copied!');
                    } else {
                        $button.text('Copied!');
                    }
                    
                    setTimeout(() => {
                        $button.removeClass('copied');
                        if ($button.find('span').length) {
                            $button.find('span').text(originalText);
                        } else {
                            $button.text(originalText);
                        }
                    }, 2000);
                }
            });
        },
        
        // Embed code generation
        initEmbedGeneration: function() {
            $('#generate-embed-btn').on('click', function() {
                const width = $('#embed-width').val() || '100%';
                const height = $('#embed-height').val() || '800';
                
                $.post(mobookingDashboard.ajaxUrl, {
                    action: 'mobooking_generate_embed_code',
                    nonce: $('#nonce').val(),
                    width: width,
                    height: height
                }, function(response) {
                    if (response.success) {
                        $('#embed-code').val(response.data.embed_code);
                    } else {
                        alert(response.data || 'Failed to generate embed code.');
                    }
                });
            });
        },
        
        // QR code generation
        initQRGeneration: function() {
            $('#generate-qr-btn').on('click', function() {
                const bookingUrl = $('#booking-url').val();
                
                // Use a QR code service (you can replace with your preferred service)
                const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(bookingUrl)}`;
                
                const qrHtml = `
                    <img src="${qrCodeUrl}" alt="QR Code" style="max-width: 200px;">
                    <p><small>Scan to open booking form</small></p>
                    <a href="${qrCodeUrl}" download="booking-qr-code.png" class="btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7,10 12,15 17,10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Download QR Code
                    </a>
                `;
                
                $('#qr-display').html(qrHtml).show();
            });
        },
        
        // Enhanced form saving with validation
        initFormSaving: function() {
            $('#booking-form-settings').on('submit', function(e) {
                e.preventDefault();
                
                // Update combined social links before saving
                EnhancedBookingFormManager.updateCombinedSocialLinks();
                
                // Validate required fields
                const formTitle = $('#form-title').val().trim();
                if (!formTitle) {
                    alert('Form title is required.');
                    $('#form-title').focus();
                    return;
                }
                
                // Show loading state
                const $saveBtn = $('#save-settings-btn');
                const originalText = $saveBtn.find('span').text() || $saveBtn.text();
                $saveBtn.prop('disabled', true);
                if ($saveBtn.find('span').length) {
                    $saveBtn.find('span').text('Saving...');
                } else {
                    $saveBtn.text('Saving...');
                }
                
                // Serialize form data
                const formData = $(this).serialize();
                
                $.post(mobookingDashboard.ajaxUrl, formData + '&action=mobooking_save_booking_form_settings')
                    .done(function(response) {
                        if (response.success) {
                            // Show success message
                            EnhancedBookingFormManager.showNotification('Settings saved successfully!', 'success');
                            
                            // Update URLs if they changed
                            if (response.data.booking_url) {
                                $('#booking-url').val(response.data.booking_url);
                            }
                        } else {
                            EnhancedBookingFormManager.showNotification(response.data || 'Failed to save settings.', 'error');
                        }
                    })
                    .fail(function() {
                        EnhancedBookingFormManager.showNotification('An error occurred while saving settings.', 'error');
                    })
                    .always(function() {
                        // Restore button state
                        $saveBtn.prop('disabled', false);
                        if ($saveBtn.find('span').length) {
                            $saveBtn.find('span').text(originalText);
                        } else {
                            $saveBtn.text(originalText);
                        }
                    });
            });
        },
        
        // Reset settings functionality
        initResetSettings: function() {
            $('#reset-settings-btn').on('click', function() {
                if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                    $.post(mobookingDashboard.ajaxUrl, {
                        action: 'mobooking_reset_booking_form_settings',
                        nonce: $('#nonce').val()
                    }, function(response) {
                        if (response.success) {
                            location.reload(); // Reload to show default values
                        } else {
                            alert(response.data || 'Failed to reset settings.');
                        }
                    });
                }
            });
        },
        
        // Notification system
        showNotification: function(message, type = 'info') {
            const notificationHtml = `
                <div class="notification notification-${type}">
                    <span>${message}</span>
                    <button type="button" class="notification-close">&times;</button>
                </div>
            `;
            
            // Remove existing notifications
            $('.notification').remove();
            
            // Add new notification
            $('body').append(notificationHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('.notification').fadeOut();
            }, 5000);
            
            // Close button handler
            $('.notification-close').on('click', function() {
                $(this).parent().fadeOut();
            });
        }
    };
    
    // Initialize enhanced manager
    EnhancedBookingFormManager.init();
    
    // Make globally available
    window.EnhancedBookingFormManager = EnhancedBookingFormManager;
});
</script>

<!-- Additional CSS for notifications and enhanced styling -->
<style>
/* Notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: var(--radius);
    box-shadow: 0 4px 12px hsl(var(--shadow) / 0.15);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-weight: 500;
    animation: slideIn 0.3s ease;
}

.notification-success {
    background: hsl(var(--success));
    color: hsl(var(--success-foreground));
    border: 1px solid hsl(var(--success) / 0.2);
}

.notification-error {
    background: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
    border: 1px solid hsl(var(--destructive) / 0.2);
}

.notification-info {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border: 1px solid hsl(var(--primary) / 0.2);
}

.notification-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
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

/* Button copied state */
.btn-secondary.copied {
    background: hsl(var(--success));
    color: hsl(var(--success-foreground));
    border-color: hsl(var(--success));
}

/* Enhanced form validation */
.form-control.error {
    border-color: hsl(var(--destructive));
    box-shadow: 0 0 0 2px hsl(var(--destructive) / 0.2);
}

/* Loading states */
.btn-primary:disabled,
.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

/* Enhanced hover effects */
.checkbox-option:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--shadow) / 0.1);
}

.share-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px hsl(var(--shadow) / 0.15);
}

/* Focus improvements */
.form-control:focus,
.file-upload-label:focus-within {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}

/* Mobile responsiveness improvements */
@media (max-width: 640px) {
    .settings-tabs {
        flex-direction: column;
    }
    
    .tab-button {
        justify-content: flex-start;
        padding: 1rem;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .notification {
        left: 10px;
        right: 10px;
        top: 10px;
    }
}
</style>

<?php
// UPDATED AJAX handler for saving booking form settings - REMOVED TESTIMONIALS
add_action('wp_ajax_mobooking_save_booking_form_settings', function() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        
        // UPDATED: Map form fields correctly WITHOUT testimonials
        $settings_data = array(
            // Basic Information
            'form_title' => sanitize_text_field($_POST['form_title'] ?? ''),
            'form_description' => sanitize_textarea_field($_POST['form_description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? absint($_POST['is_active']) : 0,
            'language' => sanitize_text_field($_POST['language'] ?? 'en'), // NEW FIELD
            
            // Form Features
            'show_form_header' => isset($_POST['show_form_header']) ? 1 : 0,
            'show_service_descriptions' => isset($_POST['show_service_descriptions']) ? 1 : 0,
            'show_price_breakdown' => isset($_POST['show_price_breakdown']) ? 1 : 0,
            'enable_zip_validation' => isset($_POST['enable_zip_validation']) ? 1 : 0,
            'show_form_footer' => isset($_POST['show_form_footer']) ? 1 : 0,
            
            // Design & Branding
            'primary_color' => sanitize_hex_color($_POST['primary_color'] ?? '#3b82f6'),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color'] ?? '#1e40af'),
            'background_color' => sanitize_hex_color($_POST['background_color'] ?? '#ffffff'),
            'text_color' => sanitize_hex_color($_POST['text_color'] ?? '#1f2937'),
            'logo_url' => esc_url_raw($_POST['logo_url'] ?? ''),
            
            // Layout & Style
            'form_layout' => sanitize_text_field($_POST['form_layout'] ?? 'modern'),
            'form_width' => sanitize_text_field($_POST['form_width'] ?? 'standard'),
            'step_indicator_style' => sanitize_text_field($_POST['step_indicator_style'] ?? 'progress'), // NEW FIELD
            'button_style' => sanitize_text_field($_POST['button_style'] ?? 'rounded'), // NEW FIELD
            
            // SEO Optimization
            'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize_textarea_field($_POST['seo_description'] ?? ''),
            
            // Custom Code
            'analytics_code' => wp_kses_post($_POST['analytics_code'] ?? ''),
            'custom_css' => wp_strip_all_tags($_POST['custom_css'] ?? ''),
            'custom_js' => wp_strip_all_tags($_POST['custom_js'] ?? ''), // NEW FIELD
            
            // Form Footer
            'custom_footer_text' => sanitize_textarea_field($_POST['custom_footer_text'] ?? ''),
            'contact_info' => sanitize_textarea_field($_POST['contact_info'] ?? ''),
            'social_links' => sanitize_textarea_field($_POST['social_links'] ?? '')
            
            // REMOVED: 'enable_testimonials' and 'testimonials_data'
        );
        
        // Save settings using BookingForm Manager
        $booking_form_manager = new \MoBooking\BookingForm\Manager();
        $result = $booking_form_manager->save_settings($user_id, $settings_data);
        
        if ($result) {
            $response_data = array(
                'message' => __('Settings saved successfully!', 'mobooking'),
                'booking_url' => $booking_form_manager->get_booking_form_url($user_id),
                'embed_url' => $booking_form_manager->get_embed_url($user_id)
            );
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error(__('Failed to save settings.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        error_log('MoBooking - Exception in save booking form settings: ' . $e->getMessage());
        wp_send_json_error(__('An error occurred while saving settings.', 'mobooking'));
    }
});

// NEW: AJAX handler for logo upload
add_action('wp_ajax_mobooking_upload_logo', function() {
    try {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('No file uploaded or upload error occurred.', 'mobooking'));
        }
        
        $file = $_FILES['logo_file'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(__('Invalid file type. Please upload JPG, PNG, or SVG files only.', 'mobooking'));
        }
        
        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(__('File size too large. Maximum size is 5MB.', 'mobooking'));
        }
        
        // Handle the upload using WordPress functions
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(array(
                'url' => $movefile['url'],
                'message' => __('Logo uploaded successfully!', 'mobooking')
            ));
        } else {
            wp_send_json_error($movefile['error'] ?? __('Upload failed.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        error_log('MoBooking - Exception in logo upload: ' . $e->getMessage());
        wp_send_json_error(__('An error occurred during upload.', 'mobooking'));
    }
});
?>





<style>
/**
 * Complete Enhanced CSS for MoBooking Form Settings
 * Includes all styles for the updated form with new features
 */

/* ===== CSS VARIABLES & BASE STYLES ===== */
:root {
    /* Color System */
    --primary: 220 88% 56%;
    --primary-foreground: 0 0% 98%;
    --secondary: 220 14% 96%;
    --secondary-foreground: 220 9% 46%;
    --destructive: 0 84% 60%;
    --destructive-foreground: 0 0% 98%;
    --success: 142 71% 45%;
    --success-foreground: 0 0% 98%;
    --warning: 38 92% 50%;
    --warning-foreground: 0 0% 98%;
    --muted: 220 14% 96%;
    --muted-foreground: 220 9% 46%;
    --accent: 220 14% 96%;
    --accent-foreground: 220 9% 39%;
    --card: 0 0% 100%;
    --card-foreground: 222 84% 5%;
    --popover: 0 0% 100%;
    --popover-foreground: 222 84% 5%;
    --border: 220 13% 91%;
    --input: 220 13% 91%;
    --background: 0 0% 100%;
    --foreground: 222 84% 5%;
    --ring: 220 88% 56%;
    
    /* Layout & Spacing */
    --radius: 0.5rem;
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    --spacing-2xl: 3rem;
    
    /* Typography */
    --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --font-mono: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
    
    /* Shadows */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    
    /* Transitions */
    --transition-fast: 150ms ease;
    --transition-normal: 200ms ease;
    --transition-slow: 300ms ease;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    :root {
        --primary: 220 88% 56%;
        --primary-foreground: 220 9% 89%;
        --secondary: 220 3% 11%;
        --secondary-foreground: 220 9% 60%;
        --destructive: 0 63% 31%;
        --destructive-foreground: 0 0% 98%;
        --success: 142 71% 45%;
        --success-foreground: 0 0% 98%;
        --warning: 38 92% 50%;
        --warning-foreground: 0 0% 98%;
        --muted: 220 3% 11%;
        --muted-foreground: 220 9% 60%;
        --accent: 220 3% 11%;
        --accent-foreground: 220 9% 60%;
        --card: 220 3% 7%;
        --card-foreground: 220 9% 89%;
        --popover: 220 3% 7%;
        --popover-foreground: 220 9% 89%;
        --border: 220 3% 11%;
        --input: 220 3% 11%;
        --background: 220 3% 4%;
        --foreground: 220 9% 89%;
        --ring: 220 88% 56%;
    }
}

/* ===== DASHBOARD LAYOUT ===== */


.content-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid hsl(var(--border));
}

.header-left h1 {
    font-size: 2rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin: 0 0 var(--spacing-sm) 0;
    line-height: 1.2;
}

.header-left p {
    font-size: 1rem;
    color: hsl(var(--muted-foreground));
    margin: 0;
    line-height: 1.5;
}

.header-actions {
    display: flex;
    gap: var(--spacing-md);
    align-items: center;
}

/* ===== SETTINGS CONTAINER ===== */
.settings-container {
    display: flex
;
    overflow: hidden;
    flex-direction: column;
    gap: 2rem;

}

/* ===== TABS NAVIGATION ===== */
.settings-tabs {
    display: flex
;
    flex-direction: row;
    background: hsl(var(--muted) / 0.3);
    border-right: 1px solid hsl(var(--border));
    flex-shrink: 0;
    justify-content: space-between;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    border: none;
    background: transparent;
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-fast);
    text-align: left;
    border-bottom: 1px solid hsl(var(--border) / 0.5);
}

.tab-button:hover {
    background: hsl(var(--accent) / 0.5);
    color: hsl(var(--accent-foreground));
}

.tab-button.active {
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    box-shadow: var(--shadow-sm);
    border-right: 2px solid hsl(var(--primary));
}

.tab-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: calc(var(--radius) - 2px);
    background: hsl(var(--muted) / 0.5);
    transition: all var(--transition-fast);
    flex-shrink: 0;
}

.tab-button.active .tab-icon {
    background: hsl(var(--primary));
    color: white;
}

/* ===== FORM CONTENT ===== */
.form-content {
    flex: 1;
    position: relative;
    background: hsl(var(--background));
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.content-grid {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl);
}

/* ===== SETTINGS GROUPS ===== */
.settings-group {
    padding: var(--spacing-lg);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--card));
    box-shadow: var(--shadow-sm);
}

.group-header {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid hsl(var(--border));
}

.group-header h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    position: relative;
}

.group-header p {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

/* ===== FORM FIELDS ===== */
.form-fields {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.field-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.field-label.required::after {
    content: " *";
    color: hsl(var(--destructive));
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    transition: all var(--transition-fast);
    font-family: var(--font-sans);
}

.form-control:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.form-control:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: hsl(var(--muted) / 0.5);
}

.form-control.error {
    border-color: hsl(var(--destructive));
    box-shadow: 0 0 0 2px hsl(var(--destructive) / 0.2);
}

.field-note {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
    margin-top: var(--spacing-xs);
}

.field-note.warning {
    color: hsl(var(--warning));
    font-weight: 500;
}

/* ===== COLOR PICKER STYLES ===== */
.color-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
}

.color-input-group {
    display: flex;
    gap: var(--spacing-sm);
    align-items: center;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    background: hsl(var(--background));
    transition: all var(--transition-fast);
}

.color-input-group:focus-within {
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.color-picker {
    width: 3rem;
    height: 2.5rem;
    padding: 0;
    border: none;
    border-right: 1px solid hsl(var(--border));
    border-radius: 0;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.color-picker:hover {
    transform: scale(1.05);
}

.color-text {
    flex: 1;
    border: none;
    background: transparent;
    font-family: var(--font-mono);
    font-size: 0.8125rem;
    color: hsl(var(--foreground));
    padding: 0.5rem;
}

/* ===== LOGO UPLOAD STYLES ===== */
.logo-upload-container {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.current-logo {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--muted) / 0.1);
    transition: all var(--transition-fast);
}

.current-logo img {
    border-radius: calc(var(--radius) - 2px);
    box-shadow: var(--shadow-sm);
}

.logo-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-left: auto;
}

.file-upload-section {
    position: relative;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    overflow: hidden;
}

.file-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-lg);
    border: 2px dashed hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    cursor: pointer;
    transition: all var(--transition-fast);
    min-height: 80px;
    text-align: center;
    color: hsl(var(--muted-foreground));
}

.file-upload-label:hover {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary) / 0.05);
    color: hsl(var(--primary));
    transform: translateY(-1px);
}

.file-upload-label:focus-within {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}

/* ===== CHECKBOX STYLES ===== */
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-md);
}

.checkbox-option {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: all var(--transition-fast);
    background: hsl(var(--card));
}

.checkbox-option:hover {
    background: hsl(var(--accent) / 0.5);
    border-color: hsl(var(--primary) / 0.3);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.checkbox-option:focus-within {
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.checkbox-option input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    margin: 0;
    flex-shrink: 0;
    accent-color: hsl(var(--primary));
    cursor: pointer;
}

.checkbox-content {
    flex: 1;
}

.checkbox-title {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: var(--spacing-xs);
    font-size: 0.875rem;
}

.checkbox-desc {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

/* ===== SOCIAL LINKS STYLES ===== */
.social-links-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-md);
}

.social-link-field {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.social-icon {
    position: absolute;
    left: 0.75rem;
    z-index: 1;
    color: hsl(var(--muted-foreground));
    transition: all var(--transition-fast);
}

.input-with-icon input {
    padding-left: 2.5rem;
}

.input-with-icon:focus-within .social-icon {
    color: hsl(var(--primary));
}

/* ===== CODE TEXTAREA STYLES ===== */
.code-textarea {
    font-family: var(--font-mono);
    line-height: 1.4;
    font-size: 0.8125rem;
    background: hsl(var(--muted) / 0.1);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: var(--spacing-md);
    color: hsl(var(--foreground));
    white-space: pre;
    word-wrap: break-word;
    resize: vertical;
    min-height: 120px;
}

.code-textarea:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
    background: hsl(var(--background));
}

/* ===== BUTTON STYLES ===== */
.btn-primary,
.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 500;
    font-family: var(--font-sans);
    text-decoration: none;
    border: 1px solid;
    cursor: pointer;
    transition: all var(--transition-fast);
    white-space: nowrap;
    user-select: none;
}

.btn-primary {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

.btn-primary:hover:not(:disabled) {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.btn-secondary {
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border-color: hsl(var(--border));
}

.btn-secondary:hover:not(:disabled) {
    background: hsl(var(--secondary) / 0.8);
    border-color: hsl(var(--primary) / 0.3);
    color: hsl(var(--foreground));
}

.btn-primary:disabled,
.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
    transform: none !important;
    box-shadow: none !important;
}

.btn-secondary.copied {
    background: hsl(var(--success));
    color: hsl(var(--success-foreground));
    border-color: hsl(var(--success));
}

/* ===== SHARE OPTIONS STYLES ===== */
.share-options {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xl);
}

.share-option {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--card));
    transition: all var(--transition-fast);
}

.share-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px hsl(var(--shadow) / 0.15);
    border-color: hsl(var(--primary) / 0.3);
}

.share-icon {
    width: 3rem;
    height: 3rem;
    border-radius: var(--radius);
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all var(--transition-fast);
}

.share-option:hover .share-icon {
    background: hsl(var(--primary));
    color: white;
    transform: scale(1.1);
}

.share-content {
    flex: 1;
}

.share-content h4 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.share-content p {
    margin: 0 0 var(--spacing-md) 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

/* ===== URL AND EMBED STYLES ===== */
.url-input-group {
    display: flex;
    gap: var(--spacing-sm);
    align-items: stretch;
}

.url-display {
    flex: 1;
    font-family: var(--font-mono);
    font-size: 0.8125rem;
    background: hsl(var(--muted) / 0.5);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 0.75rem;
    color: hsl(var(--foreground));
    word-break: break-all;
    user-select: all;
}

.embed-controls {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.embed-settings {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.embed-code-section {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

/* ===== QR CODE STYLES ===== */
.qr-controls {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.qr-display {
    text-align: center;
    padding: var(--spacing-md);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    display: none;
}

.qr-display img {
    border-radius: calc(var(--radius) - 2px);
    box-shadow: var(--shadow-sm);
}

.qr-display.show {
    display: block;
    animation: fadeIn var(--transition-normal);
}

/* ===== NOTIFICATION SYSTEM ===== */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    font-weight: 500;
    max-width: 400px;
    animation: slideIn var(--transition-slow);
}

.notification-success {
    background: hsl(var(--success));
    color: hsl(var(--success-foreground));
    border: 1px solid hsl(var(--success) / 0.2);
}

.notification-error {
    background: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
    border: 1px solid hsl(var(--destructive) / 0.2);
}

.notification-info {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border: 1px solid hsl(var(--primary) / 0.2);
}

.notification-warning {
    background: hsl(var(--warning));
    color: hsl(var(--warning-foreground));
    border: 1px solid hsl(var(--warning) / 0.2);
}

.notification-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    opacity: 0.7;
    transition: opacity var(--transition-fast);
}

.notification-close:hover {
    opacity: 1;
}

/* ===== LOADING STATES ===== */
.form-saving::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, hsl(var(--primary)), hsl(var(--primary) / 0.3), hsl(var(--primary)));
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
    border-radius: var(--radius) var(--radius) 0 0;
}

.loading-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* ===== ANIMATIONS ===== */
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

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* ===== ACCESSIBILITY IMPROVEMENTS ===== */
.tab-button:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: -2px;
}

.form-control:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}

.btn-primary:focus-visible,
.btn-secondary:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1024px) {
    .dashboard-content {
        padding: var(--spacing-md);
    }
    
    .settings-container {
        flex-direction: column;
    }
    
    .settings-tabs {
        width: 100%;
        flex-direction: row;
        overflow-x: auto;
        border-right: none;
        border-bottom: 1px solid hsl(var(--border));
    }
    
    .tab-button {
        flex-shrink: 0;
        min-width: 120px;
        justify-content: center;
        border-bottom: none;
        border-right: 1px solid hsl(var(--border) / 0.5);
    }
    
    .tab-button.active {
        border-right: 1px solid hsl(var(--border) / 0.5);
        border-bottom: 2px solid hsl(var(--primary));
    }
}

@media (max-width: 768px) {
    .content-header {
        flex-direction: column;
        gap: var(--spacing-md);
        align-items: stretch;
    }
    
    .header-actions {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .field-row {
        grid-template-columns: 1fr;
    }
    
    .color-fields {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .social-links-container {
        grid-template-columns: 1fr;
    }
    
    .checkbox-grid {
        grid-template-columns: 1fr;
    }
    
    .url-input-group {
        flex-direction: column;
    }
    
    .share-option {
        flex-direction: column;
        text-align: center;
    }
    
    .share-icon {
        align-self: center;
    }
    
    .tab-content {
        padding: var(--spacing-md);
    }
    
    .notification {
        left: 10px;
        right: 10px;
        top: 10px;
        max-width: none;
    }
}

@media (max-width: 640px) {
    .dashboard-content {
        padding: var(--spacing-sm);
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .settings-tabs {
        flex-direction: column;
    }
    
    .tab-button {
        justify-content: flex-start;
        padding: var(--spacing-md);
        min-width: auto;
        border-right: none;
        border-bottom: 1px solid hsl(var(--border) / 0.5);
    }
    
    .tab-button.active {
        border-right: 2px solid hsl(var(--primary));
        border-bottom: 1px solid hsl(var(--border) / 0.5);
    }
    
    .btn-primary,
    .btn-secondary {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }
    
    .form-control {
        padding: 0.875rem 1rem;
        font-size: 1rem; /* Prevent zoom on iOS */
    }
}

/* ===== ENHANCED INTERACTION STATES ===== */
.tab-button:active {
    transform: scale(0.98);
}

.btn-primary:active,
.btn-secondary:active {
    transform: translateY(0) scale(0.98);
}

.checkbox-option:active {
    transform: translateY(0) scale(0.98);
}

.file-upload-label:active {
    transform: translateY(0) scale(0.98);
}

/* ===== ADVANCED FORM VALIDATION STYLES ===== */
.form-group.has-error .form-control {
    border-color: hsl(var(--destructive));
    box-shadow: 0 0 0 2px hsl(var(--destructive) / 0.2);
    animation: shake 0.3s ease-in-out;
}

.form-group.has-error .field-label {
    color: hsl(var(--destructive));
}

.form-group.has-success .form-control {
    border-color: hsl(var(--success));
    box-shadow: 0 0 0 2px hsl(var(--success) / 0.2);
}

.form-group.has-warning .form-control {
    border-color: hsl(var(--warning));
    box-shadow: 0 0 0 2px hsl(var(--warning) / 0.2);
}

.error-message {
    font-size: 0.75rem;
    color: hsl(var(--destructive));
    margin-top: var(--spacing-xs);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

.success-message {
    font-size: 0.75rem;
    color: hsl(var(--success));
    margin-top: var(--spacing-xs);
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
}

/* ===== PROGRESS INDICATORS ===== */
.upload-progress {
    width: 100%;
    height: 4px;
    background: hsl(var(--muted));
    border-radius: 2px;
    overflow: hidden;
    margin-top: var(--spacing-sm);
}

.upload-progress-bar {
    height: 100%;
    background: hsl(var(--primary));
    border-radius: 2px;
    transition: width var(--transition-normal);
    animation: pulse 1.5s ease-in-out infinite;
}

.step-progress {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

.step-indicator {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    border: 2px solid hsl(var(--border));
    background: hsl(var(--background));
    color: hsl(var(--muted-foreground));
    transition: all var(--transition-fast);
}

.step-indicator.active {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.step-indicator.completed {
    border-color: hsl(var(--success));
    background: hsl(var(--success));
    color: hsl(var(--success-foreground));
}

.step-connector {
    flex: 1;
    height: 2px;
    background: hsl(var(--border));
    position: relative;
}

.step-connector.completed::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: hsl(var(--success));
    width: 100%;
    animation: fillProgress var(--transition-slow) ease-out;
}

/* ===== ENHANCED TOOLTIPS ===== */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: hsl(var(--popover));
    color: hsl(var(--popover-foreground));
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius);
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-fast);
    z-index: 1000;
    box-shadow: var(--shadow-md);
    border: 1px solid hsl(var(--border));
}

.tooltip::after {
    content: '';
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: hsl(var(--popover));
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-fast);
}

.tooltip:hover::before,
.tooltip:hover::after {
    opacity: 1;
    visibility: visible;
}

/* ===== ENHANCED DRAG & DROP STYLES ===== */
.file-upload-label.dragover {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary) / 0.1);
    transform: scale(1.02);
}

.drop-zone {
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    text-align: center;
}

.drop-zone-icon {
    width: 2rem;
    height: 2rem;
    color: hsl(var(--muted-foreground));
    transition: all var(--transition-fast);
}

.file-upload-label:hover .drop-zone-icon,
.file-upload-label.dragover .drop-zone-icon {
    color: hsl(var(--primary));
    transform: scale(1.1);
}

/* ===== ADVANCED ANIMATIONS ===== */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes fillProgress {
    0% { width: 0%; }
    100% { width: 100%; }
}

@keyframes bounce {
    0%, 20%, 53%, 80%, 100% {
        animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
        transform: translateY(0);
    }
    40%, 43% {
        animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
        transform: translateY(-8px);
    }
    70% {
        animation-timing-function: cubic-bezier(0.755, 0.05, 0.855, 0.06);
        transform: translateY(-4px);
    }
    90% {
        transform: translateY(-2px);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== PRINT STYLES ===== */
@media print {
    .dashboard-content {
        background: white;
        box-shadow: none;
        padding: 0;
    }
    
    .settings-tabs,
    .header-actions,
    .btn-primary,
    .btn-secondary,
    .notification {
        display: none;
    }
    
    .settings-container {
        border: none;
        box-shadow: none;
    }
    
    .tab-content {
        display: block !important;
        padding: 0;
    }
    
    .settings-group {
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 1rem;
    }
}

/* ===== HIGH CONTRAST MODE SUPPORT ===== */
@media (prefers-contrast: high) {
    :root {
        --border: 0 0% 20%;
        --input: 0 0% 20%;
        --ring: 220 88% 40%;
    }
    
    .form-control {
        border-width: 2px;
    }
    
    .btn-primary,
    .btn-secondary {
        border-width: 2px;
    }
    
    .tab-button.active {
        border-right-width: 3px;
    }
}

/* ===== REDUCED MOTION SUPPORT ===== */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
    
    .notification {
        animation: none;
    }
    
    .tab-button,
    .btn-primary,
    .btn-secondary,
    .checkbox-option,
    .share-option {
        transition: none;
    }
}

/* ===== UTILITY CLASSES ===== */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.hidden {
    display: none !important;
}

.visible {
    display: block !important;
}

.text-center {
    text-align: center;
}

.text-left {
    text-align: left;
}

.text-right {
    text-align: right;
}

.font-mono {
    font-family: var(--font-mono);
}

.font-sans {
    font-family: var(--font-sans);
}

.text-xs {
    font-size: 0.75rem;
}

.text-sm {
    font-size: 0.875rem;
}

.text-base {
    font-size: 1rem;
}

.text-lg {
    font-size: 1.125rem;
}

.text-xl {
    font-size: 1.25rem;
}

.font-normal {
    font-weight: 400;
}

.font-medium {
    font-weight: 500;
}

.font-semibold {
    font-weight: 600;
}

.font-bold {
    font-weight: 700;
}

.opacity-50 {
    opacity: 0.5;
}

.opacity-75 {
    opacity: 0.75;
}

.opacity-100 {
    opacity: 1;
}

.cursor-pointer {
    cursor: pointer;
}

.cursor-not-allowed {
    cursor: not-allowed;
}

.select-none {
    user-select: none;
}

.select-all {
    user-select: all;
}

.pointer-events-none {
    pointer-events: none;
}

.pointer-events-auto {
    pointer-events: auto;
}

/* ===== COMPONENT SPECIFIC ENHANCEMENTS ===== */
.settings-group:hover {
    box-shadow: var(--shadow-md);
    transition: box-shadow var(--transition-fast);
}

.form-control:placeholder-shown {
    color: hsl(var(--muted-foreground));
}

.tab-content {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted)) transparent;
}

.tab-content::-webkit-scrollbar {
    width: 6px;
}

.tab-content::-webkit-scrollbar-track {
    background: transparent;
}

.tab-content::-webkit-scrollbar-thumb {
    background: hsl(var(--muted));
    border-radius: 3px;
}

.tab-content::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground));
}

/* ===== FINAL POLISH ===== */
.dashboard-content * {
    box-sizing: border-box;
}

.dashboard-content img {
    max-width: 100%;
    height: auto;
}

.dashboard-content code {
    font-family: var(--font-mono);
    font-size: 0.875em;
    background: hsl(var(--muted) / 0.3);
    padding: 0.125rem 0.25rem;
    border-radius: calc(var(--radius) / 2);
}

.dashboard-content pre {
    font-family: var(--font-mono);
    background: hsl(var(--muted) / 0.1);
    padding: var(--spacing-md);
    border-radius: var(--radius);
    border: 1px solid hsl(var(--border));
    overflow-x: auto;
    line-height: 1.4;
}

/* Ensure consistent focus styles across all interactive elements */
.dashboard-content *:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}





</style>