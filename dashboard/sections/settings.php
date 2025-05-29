<?php
// dashboard/sections/settings.php - Business Settings Management
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize settings manager
$settings_manager = new \MoBooking\Database\SettingsManager();
$settings = $settings_manager->get_settings($user_id);

// Get user data
$user_data = get_userdata($user_id);
?>

<div class="settings-section">
    <div class="settings-header">
        <div class="settings-header-content">
            <div class="settings-title-group">
                <h1 class="settings-main-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <?php _e('Business Settings', 'mobooking'); ?>
                </h1>
                <p class="settings-subtitle"><?php _e('Configure your business information and preferences', 'mobooking'); ?></p>
            </div>
            
            <div class="settings-status">
                <div class="status-indicator">
                    <div class="status-dot active"></div>
                    <span class="status-text"><?php _e('Settings Active', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="settings-header-actions">
            <button type="button" id="preview-settings-btn" class="btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <?php _e('Preview', 'mobooking'); ?>
            </button>
            
            <button type="button" id="reset-settings-btn" class="btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                <?php _e('Reset', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <!-- Settings Navigation Tabs -->
    <div class="settings-tabs">
        <div class="tab-list" role="tablist">
            <button type="button" class="tab-button active" data-tab="business" role="tab" aria-controls="business" aria-selected="true">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                    </svg>
                </div>
                <span><?php _e('Business Info', 'mobooking'); ?></span>
            </button>
            
            <button type="button" class="tab-button" data-tab="branding" role="tab" aria-controls="branding" aria-selected="false">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"></circle>
                        <circle cx="6" cy="12" r="3"></circle>
                        <circle cx="18" cy="19" r="3"></circle>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                </div>
                <span><?php _e('Branding', 'mobooking'); ?></span>
            </button>
            
            <button type="button" class="tab-button" data-tab="notifications" role="tab" aria-controls="notifications" aria-selected="false">
                <div class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
                <span><?php _e('Notifications', 'mobooking'); ?></span>
            </button>
            
            <button type="button" class="tab-button" data-tab="advanced" role="tab" aria-controls="advanced" aria-selected="false">
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
    
    <!-- Settings Form -->
    <form id="settings-form" class="settings-container">
        <?php wp_nonce_field('mobooking-settings-nonce', 'nonce'); ?>
        
        <!-- Business Information Tab -->
        <div id="business" class="tab-pane active" role="tabpanel">
            <div class="settings-section-wrapper">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Business Information', 'mobooking'); ?></h2>
                    <p class="section-description"><?php _e('Configure your basic business details and contact information', 'mobooking'); ?></p>
                </div>
                
                <div class="settings-fields">
                    <div class="field-row">
                        <div class="field-group">
                            <label for="company-name" class="field-label required"><?php _e('Company Name', 'mobooking'); ?></label>
                            <input type="text" id="company-name" name="company_name" class="form-control" 
                                   value="<?php echo esc_attr($settings->company_name); ?>" 
                                   placeholder="<?php _e('Your Company Name', 'mobooking'); ?>" required>
                            <p class="field-help"><?php _e('This name will appear on your booking forms and emails', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="field-group">
                            <label for="company-phone" class="field-label"><?php _e('Phone Number', 'mobooking'); ?></label>
                            <input type="tel" id="company-phone" name="phone" class="form-control" 
                                   value="<?php echo esc_attr($settings->phone); ?>" 
                                   placeholder="<?php _e('(555) 123-4567', 'mobooking'); ?>">
                            <p class="field-help"><?php _e('Contact phone number for customer inquiries', 'mobooking'); ?></p>
                        </div>
                    </div>
                    
                    <div class="field-group">
                        <label for="company-email" class="field-label"><?php _e('Business Email', 'mobooking'); ?></label>
                        <input type="email" id="company-email" name="business_email" class="form-control" 
                               value="<?php echo esc_attr($user_data->user_email); ?>" 
                               placeholder="<?php _e('business@example.com', 'mobooking'); ?>">
                        <p class="field-help"><?php _e('Primary email for business communications', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="field-group">
                        <label for="company-address" class="field-label"><?php _e('Business Address', 'mobooking'); ?></label>
                        <textarea id="company-address" name="business_address" class="form-control" rows="3" 
                                  placeholder="<?php _e('123 Business St, City, State 12345', 'mobooking'); ?>"><?php echo esc_textarea(get_user_meta($user_id, 'business_address', true)); ?></textarea>
                        <p class="field-help"><?php _e('Your business address (optional)', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="field-group">
                        <label for="company-website" class="field-label"><?php _e('Website', 'mobooking'); ?></label>
                        <input type="url" id="company-website" name="website" class="form-control" 
                               value="<?php echo esc_attr(get_user_meta($user_id, 'website', true)); ?>" 
                               placeholder="<?php _e('https://yourwebsite.com', 'mobooking'); ?>">
                        <p class="field-help"><?php _e('Your business website (optional)', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="field-group">
                        <label for="business-description" class="field-label"><?php _e('Business Description', 'mobooking'); ?></label>
                        <textarea id="business-description" name="business_description" class="form-control" rows="4" 
                                  placeholder="<?php _e('Brief description of your business and services...', 'mobooking'); ?>"><?php echo esc_textarea(get_user_meta($user_id, 'business_description', true)); ?></textarea>
                        <p class="field-help"><?php _e('A brief description of your business for customer reference', 'mobooking'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Branding Tab -->
        <div id="branding" class="tab-pane" role="tabpanel">
            <div class="settings-section-wrapper">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Branding & Visual Identity', 'mobooking'); ?></h2>
                    <p class="section-description"><?php _e('Customize the look and feel of your booking forms and communications', 'mobooking'); ?></p>
                </div>
                
                <div class="settings-fields">
                    <!-- Logo Section -->
                    <div class="branding-section">
                        <h3 class="subsection-title"><?php _e('Logo & Visual Identity', 'mobooking'); ?></h3>
                        
                        <div class="logo-upload-section">
                            <div class="logo-preview">
                                <?php if (!empty($settings->logo_url)) : ?>
                                    <img src="<?php echo esc_url($settings->logo_url); ?>" alt="<?php _e('Current Logo', 'mobooking'); ?>" class="current-logo">
                                <?php else : ?>
                                    <div class="logo-placeholder">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                            <circle cx="9" cy="9" r="2"></circle>
                                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                        </svg>
                                        <span><?php _e('No Logo Uploaded', 'mobooking'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="logo-upload-controls">
                                <input type="hidden" id="logo-url" name="logo_url" value="<?php echo esc_attr($settings->logo_url); ?>">
                                <button type="button" class="btn-secondary select-logo-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7,10 12,15 17,10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                    <?php _e('Upload Logo', 'mobooking'); ?>
                                </button>
                                
                                <?php if (!empty($settings->logo_url)) : ?>
                                    <button type="button" class="btn-secondary remove-logo-btn">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m3 6 3 18h12l3-18"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                        </svg>
                                        <?php _e('Remove', 'mobooking'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <p class="field-help"><?php _e('Upload your business logo. Recommended size: 200x80px or similar aspect ratio', 'mobooking'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Color Scheme Section -->
                    <div class="branding-section">
                        <h3 class="subsection-title"><?php _e('Color Scheme', 'mobooking'); ?></h3>
                        
                        <div class="color-scheme-grid">
                            <div class="field-group">
                                <label for="primary-color" class="field-label"><?php _e('Primary Color', 'mobooking'); ?></label>
                                <div class="color-input-group">
                                    <input type="color" id="primary-color" name="primary_color" 
                                           value="<?php echo esc_attr($settings->primary_color); ?>" class="color-picker">
                                    <input type="text" class="color-text" value="<?php echo esc_attr($settings->primary_color); ?>" readonly>
                                    <div class="color-preview" style="background-color: <?php echo esc_attr($settings->primary_color); ?>"></div>
                                </div>
                                <p class="field-help"><?php _e('Main brand color used for buttons and highlights', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="color-presets">
                                <label class="field-label"><?php _e('Quick Color Presets', 'mobooking'); ?></label>
                                <div class="preset-colors">
                                    <button type="button" class="color-preset" data-color="#4CAF50" style="background-color: #4CAF50;" title="Green"></button>
                                    <button type="button" class="color-preset" data-color="#2196F3" style="background-color: #2196F3;" title="Blue"></button>
                                    <button type="button" class="color-preset" data-color="#FF9800" style="background-color: #FF9800;" title="Orange"></button>
                                    <button type="button" class="color-preset" data-color="#9C27B0" style="background-color: #9C27B0;" title="Purple"></button>
                                    <button type="button" class="color-preset" data-color="#F44336" style="background-color: #F44336;" title="Red"></button>
                                    <button type="button" class="color-preset" data-color="#607D8B" style="background-color: #607D8B;" title="Blue Grey"></button>
                                    <button type="button" class="color-preset" data-color="#795548" style="background-color: #795548;" title="Brown"></button>
                                    <button type="button" class="color-preset" data-color="#000000" style="background-color: #000000;" title="Black"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Section -->
                    <div class="branding-section">
                        <h3 class="subsection-title"><?php _e('Preview', 'mobooking'); ?></h3>
                        
                        <div class="branding-preview">
                            <div class="preview-card" style="border-color: <?php echo esc_attr($settings->primary_color); ?>;">
                                <div class="preview-header" style="background-color: <?php echo esc_attr($settings->primary_color); ?>;">
                                    <?php if (!empty($settings->logo_url)) : ?>
                                        <img src="<?php echo esc_url($settings->logo_url); ?>" alt="Logo Preview" class="preview-logo">
                                    <?php endif; ?>
                                    <div class="preview-title"><?php echo esc_html($settings->company_name); ?></div>
                                </div>
                                <div class="preview-content">
                                    <p><?php _e('This is how your branding will appear on booking forms and emails.', 'mobooking'); ?></p>
                                    <button type="button" class="preview-button" style="background-color: <?php echo esc_attr($settings->primary_color); ?>;">
                                        <?php _e('Book Now', 'mobooking'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notifications Tab -->
        <div id="notifications" class="tab-pane" role="tabpanel">
            <div class="settings-section-wrapper">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Email Templates & Notifications', 'mobooking'); ?></h2>
                    <p class="section-description"><?php _e('Customize email templates and notification preferences', 'mobooking'); ?></p>
                </div>
                
                <div class="settings-fields">
                    <!-- Email Templates Section -->
                    <div class="notification-section">
                        <h3 class="subsection-title"><?php _e('Email Templates', 'mobooking'); ?></h3>
                        
                        <div class="field-group">
                            <label for="email-header" class="field-label"><?php _e('Email Header Template', 'mobooking'); ?></label>
                            <div class="email-template-editor">
                                <div class="template-toolbar">
                                    <div class="template-variables">
                                        <label><?php _e('Available Variables:', 'mobooking'); ?></label>
                                        <span class="variable-tag" data-variable="{{company_name}}"><?php _e('Company Name', 'mobooking'); ?></span>
                                        <span class="variable-tag" data-variable="{{current_year}}"><?php _e('Current Year', 'mobooking'); ?></span>
                                        <span class="variable-tag" data-variable="{{phone}}"><?php _e('Phone', 'mobooking'); ?></span>
                                        <span class="variable-tag" data-variable="{{email}}"><?php _e('Email', 'mobooking'); ?></span>
                                    </div>
                                </div>
                                <textarea id="email-header" name="email_header" class="form-control email-template" rows="6"><?php echo esc_textarea($settings->email_header); ?></textarea>
                            </div>
                            <p class="field-help"><?php _e('HTML template for email headers. Click variable tags to insert them.', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="field-group">
                            <label for="email-footer" class="field-label"><?php _e('Email Footer Template', 'mobooking'); ?></label>
                            <div class="email-template-editor">
                                <div class="template-toolbar">
                                    <div class="template-variables">
                                        <label><?php _e('Available Variables:', 'mobooking'); ?></label>
                                        <span class="variable-tag" data-variable="{{company_name}}"><?php _e('Company Name', 'mobooking'); ?></span>
                                        <span class="variable-tag" data-variable="{{current_year}}"><?php _e('Current Year', 'mobooking'); ?></span>
                                        <span class="variable-tag" data-variable="{{phone}}"><?php _e('Phone', 'mobooking'); ?></span>
                                        <span class="variable-tag" data-variable="{{email}}"><?php _e('Email', 'mobooking'); ?></span>
                                    </div>
                                </div>
                                <textarea id="email-footer" name="email_footer" class="form-control email-template" rows="6"><?php echo esc_textarea($settings->email_footer); ?></textarea>
                            </div>
                            <p class="field-help"><?php _e('HTML template for email footers. Click variable tags to insert them.', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="field-group">
                            <label for="booking-confirmation-message" class="field-label"><?php _e('Booking Confirmation Message', 'mobooking'); ?></label>
                            <textarea id="booking-confirmation-message" name="booking_confirmation_message" class="form-control" rows="4" 
                                      placeholder="<?php _e('Thank you for your booking. We will contact you shortly...', 'mobooking'); ?>"><?php echo esc_textarea($settings->booking_confirmation_message); ?></textarea>
                            <p class="field-help"><?php _e('Message sent to customers when they make a booking', 'mobooking'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Notification Preferences -->
                    <div class="notification-section">
                        <h3 class="subsection-title"><?php _e('Notification Preferences', 'mobooking'); ?></h3>
                        
                        <div class="notification-preferences">
                            <div class="preference-item">
                                <div class="preference-header">
                                    <label class="preference-label">
                                        <input type="checkbox" name="notify_new_booking" value="1" 
                                               <?php checked(get_user_meta($user_id, 'notify_new_booking', true), '1'); ?>>
                                        <span class="preference-title"><?php _e('New Booking Notifications', 'mobooking'); ?></span>
                                    </label>
                                </div>
                                <p class="preference-description"><?php _e('Receive email notifications when new bookings are made', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="preference-item">
                                <div class="preference-header">
                                    <label class="preference-label">
                                        <input type="checkbox" name="notify_booking_changes" value="1" 
                                               <?php checked(get_user_meta($user_id, 'notify_booking_changes', true), '1'); ?>>
                                        <span class="preference-title"><?php _e('Booking Changes', 'mobooking'); ?></span>
                                    </label>
                                </div>
                                <p class="preference-description"><?php _e('Get notified when bookings are modified or cancelled', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="preference-item">
                                <div class="preference-header">
                                    <label class="preference-label">
                                        <input type="checkbox" name="notify_reminders" value="1" 
                                               <?php checked(get_user_meta($user_id, 'notify_reminders', true), '1'); ?>>
                                        <span class="preference-title"><?php _e('Booking Reminders', 'mobooking'); ?></span>
                                    </label>
                                </div>
                                <p class="preference-description"><?php _e('Send reminder emails before scheduled services', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email Preview -->
                    <div class="notification-section">
                        <h3 class="subsection-title"><?php _e('Email Preview', 'mobooking'); ?></h3>
                        
                        <div class="email-preview-container">
                            <div class="email-preview" id="email-preview">
                                <div class="email-header-preview">
                                    <!-- Email header preview will be generated by JavaScript -->
                                </div>
                                <div class="email-body-preview">
                                    <h2><?php _e('Booking Confirmation', 'mobooking'); ?></h2>
                                    <p><?php _e('Dear Customer,', 'mobooking'); ?></p>
                                    <div class="email-message-preview">
                                        <!-- Booking confirmation message preview -->
                                    </div>
                                    <p><?php _e('Best regards,', 'mobooking'); ?><br><?php echo esc_html($settings->company_name); ?></p>
                                </div>
                                <div class="email-footer-preview">
                                    <!-- Email footer preview will be generated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="preview-actions">
                                <button type="button" id="refresh-email-preview" class="btn-secondary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="1 4 1 10 7 10"></polyline>
                                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                    </svg>
                                    <?php _e('Refresh Preview', 'mobooking'); ?>
                                </button>
                                
                                <button type="button" id="send-test-email" class="btn-secondary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m3 3 3 9-3 9 19-9Z"></path>
                                        <path d="m6 12 13 0"></path>
                                    </svg>
                                    <?php _e('Send Test Email', 'mobooking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Advanced Tab -->
        <div id="advanced" class="tab-pane" role="tabpanel">
            <div class="settings-section-wrapper">
                <div class="section-header">
                    <h2 class="section-title"><?php _e('Advanced Settings', 'mobooking'); ?></h2>
                    <p class="section-description"><?php _e('Advanced configuration options and integrations', 'mobooking'); ?></p>
                </div>
                
                <div class="settings-fields">
                    <!-- Terms & Conditions -->
                    <div class="advanced-section">
                        <h3 class="subsection-title"><?php _e('Terms & Conditions', 'mobooking'); ?></h3>
                        
                        <div class="field-group">
                            <label for="terms-conditions" class="field-label"><?php _e('Terms & Conditions Text', 'mobooking'); ?></label>
                            <textarea id="terms-conditions" name="terms_conditions" class="form-control" rows="8" 
                                      placeholder="<?php _e('Enter your terms and conditions that customers must agree to...', 'mobooking'); ?>"><?php echo esc_textarea($settings->terms_conditions); ?></textarea>
                            <p class="field-help"><?php _e('Legal terms and conditions that customers must accept when booking', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="require_terms_acceptance" value="1" 
                                       <?php checked(get_user_meta($user_id, 'require_terms_acceptance', true), '1'); ?>>
                                <span class="checkbox-text"><?php _e('Require customers to accept terms & conditions', 'mobooking'); ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Business Hours -->
                    <div class="advanced-section">
                        <h3 class="subsection-title"><?php _e('Business Hours', 'mobooking'); ?></h3>
                        
                        <div class="business-hours-grid">
                            <?php 
                            $days = array(
                                'monday' => __('Monday', 'mobooking'),
                                'tuesday' => __('Tuesday', 'mobooking'),
                                'wednesday' => __('Wednesday', 'mobooking'),
                                'thursday' => __('Thursday', 'mobooking'),
                                'friday' => __('Friday', 'mobooking'),
                                'saturday' => __('Saturday', 'mobooking'),
                                'sunday' => __('Sunday', 'mobooking')
                            );
                            
                            $business_hours = get_user_meta($user_id, 'business_hours', true);
                            if (!is_array($business_hours)) {
                                $business_hours = array();
                            }
                            
                            foreach ($days as $day_key => $day_name) :
                                $is_open = isset($business_hours[$day_key]['open']) ? $business_hours[$day_key]['open'] : true;
                                $start_time = isset($business_hours[$day_key]['start']) ? $business_hours[$day_key]['start'] : '09:00';
                                $end_time = isset($business_hours[$day_key]['end']) ? $business_hours[$day_key]['end'] : '17:00';
                            ?>
                                <div class="business-hour-row">
                                    <div class="day-checkbox">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="business_hours[<?php echo $day_key; ?>][open]" value="1" 
                                                   <?php checked($is_open, true); ?> class="day-toggle">
                                            <span class="day-name"><?php echo esc_html($day_name); ?></span>
                                        </label>
                                    </div>
                                    
                                    <div class="time-inputs <?php echo $is_open ? '' : 'disabled'; ?>">
                                        <input type="time" name="business_hours[<?php echo $day_key; ?>][start]" 
                                               value="<?php echo esc_attr($start_time); ?>" class="time-input"
                                               <?php echo $is_open ? '' : 'disabled'; ?>>
                                        <span class="time-separator">-</span>
                                        <input type="time" name="business_hours[<?php echo $day_key; ?>][end]" 
                                               value="<?php echo esc_attr($end_time); ?>" class="time-input"
                                               <?php echo $is_open ? '' : 'disabled'; ?>>
                                    </div>
                                    
                                    <div class="closed-indicator <?php echo $is_open ? 'hidden' : ''; ?>">
                                        <span class="closed-text"><?php _e('Closed', 'mobooking'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <p class="field-help"><?php _e('Set your business operating hours. This affects booking availability.', 'mobooking'); ?></p>
                    </div>
                    
                    <!-- Booking Settings -->
                    <div class="advanced-section">
                        <h3 class="subsection-title"><?php _e('Booking Settings', 'mobooking'); ?></h3>
                        
                        <div class="field-row">
                            <div class="field-group">
                                <label for="booking-lead-time" class="field-label"><?php _e('Minimum Lead Time (hours)', 'mobooking'); ?></label>
                                <input type="number" id="booking-lead-time" name="booking_lead_time" class="form-control" 
                                       min="0" max="168" step="1"
                                       value="<?php echo esc_attr(get_user_meta($user_id, 'booking_lead_time', true) ?: '24'); ?>">
                                <p class="field-help"><?php _e('Minimum hours in advance customers must book', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="field-group">
                                <label for="max-bookings-per-day" class="field-label"><?php _e('Max Bookings Per Day', 'mobooking'); ?></label>
                                <input type="number" id="max-bookings-per-day" name="max_bookings_per_day" class="form-control" 
                                       min="1" max="50" step="1"
                                       value="<?php echo esc_attr(get_user_meta($user_id, 'max_bookings_per_day', true) ?: '10'); ?>">
                                <p class="field-help"><?php _e('Maximum number of bookings allowed per day', 'mobooking'); ?></p>
                            </div>
                        </div>
                        
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="auto_confirm_bookings" value="1" 
                                       <?php checked(get_user_meta($user_id, 'auto_confirm_bookings', true), '1'); ?>>
                                <span class="checkbox-text"><?php _e('Automatically confirm new bookings', 'mobooking'); ?></span>
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_same_day_booking" value="1" 
                                       <?php checked(get_user_meta($user_id, 'allow_same_day_booking', true), '1'); ?>>
                                <span class="checkbox-text"><?php _e('Allow same-day bookings', 'mobooking'); ?></span>
                            </label>
                            
                            <label class="checkbox-label">
                                <input type="checkbox" name="send_booking_reminders" value="1" 
                                       <?php checked(get_user_meta($user_id, 'send_booking_reminders', true), '1'); ?>>
                                <span class="checkbox-text"><?php _e('Send automatic booking reminders', 'mobooking'); ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Data Export -->
                    <div class="advanced-section">
                        <h3 class="subsection-title"><?php _e('Data Management', 'mobooking'); ?></h3>
                        
                        <div class="data-management-grid">
                            <div class="data-action-card">
                                <div class="action-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17,8 12,3 7,8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <h4><?php _e('Export Data', 'mobooking'); ?></h4>
                                    <p><?php _e('Export your bookings, customers, and settings data', 'mobooking'); ?></p>
                                    <button type="button" id="export-data-btn" class="btn-secondary">
                                        <?php _e('Export All Data', 'mobooking'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="data-action-card">
                                <div class="action-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7,10 12,15 17,10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <h4><?php _e('Import Data', 'mobooking'); ?></h4>
                                    <p><?php _e('Import customer data from CSV files', 'mobooking'); ?></p>
                                    <input type="file" id="import-file" accept=".csv" style="display: none;">
                                    <button type="button" id="import-data-btn" class="btn-secondary">
                                        <?php _e('Import CSV', 'mobooking'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Information -->
                    <div class="advanced-section">
                        <h3 class="subsection-title"><?php _e('System Information', 'mobooking'); ?></h3>
                        
                        <div class="system-info-grid">
                            <div class="info-item">
                                <span class="info-label"><?php _e('User ID:', 'mobooking'); ?></span>
                                <span class="info-value"><?php echo esc_html($user_id); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Account Created:', 'mobooking'); ?></span>
                                <span class="info-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user_data->user_registered))); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Total Bookings:', 'mobooking'); ?></span>
                                <span class="info-value">
                                    <?php 
                                    $bookings_manager = new \MoBooking\Bookings\Manager();
                                    echo esc_html($bookings_manager->count_user_bookings($user_id));
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php _e('Active Services:', 'mobooking'); ?></span>
                                <span class="info-value">
                                    <?php 
                                    $services_manager = new \MoBooking\Services\ServicesManager();
                                    $services = $services_manager->get_user_services($user_id);
                                    echo esc_html(count($services));
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="settings-actions">
            <div class="settings-actions-left">
                <button type="button" id="restore-defaults-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <?php _e('Restore Defaults', 'mobooking'); ?>
                </button>
            </div>
            
            <div class="settings-actions-right">
                <button type="button" id="save-draft-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    <?php _e('Save Draft', 'mobooking'); ?>
                </button>
                
                <button type="submit" id="save-settings-btn" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    <span class="btn-text"><?php _e('Save All Settings', 'mobooking'); ?></span>
                    <span class="btn-loading"><?php _e('Saving...', 'mobooking'); ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div id="settings-preview-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-large">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <div class="modal-header">
            <h3><?php _e('Settings Preview', 'mobooking'); ?></h3>
            <div class="preview-tabs">
                <button type="button" class="preview-tab-btn active" data-preview="branding">
                    <?php _e('Branding', 'mobooking'); ?>
                </button>
                <button type="button" class="preview-tab-btn" data-preview="email">
                    <?php _e('Email', 'mobooking'); ?>
                </button>
            </div>
        </div>
        
        <div class="modal-body">
            <div id="branding-preview" class="preview-content active">
                <div class="branding-preview-container">
                    <div class="preview-booking-form">
                        <!-- Preview content will be generated by JavaScript -->
                    </div>
                </div>
            </div>
            
            <div id="email-preview-full" class="preview-content">
                <div class="email-preview-container">
                    <!-- Email preview content will be generated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="settings-confirmation-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <h3 id="confirmation-title"><?php _e('Confirm Action', 'mobooking'); ?></h3>
        <p id="confirmation-message"><?php _e('Are you sure you want to perform this action?', 'mobooking'); ?></p>
        
        <div class="form-actions">
            <button type="button" class="btn-secondary cancel-confirmation-btn">
                <?php _e('Cancel', 'mobooking'); ?>
            </button>
            <button type="button" class="btn-danger confirm-action-btn">
                <span class="btn-text"><?php _e('Confirm', 'mobooking'); ?></span>
                <span class="btn-loading"><?php _e('Processing...', 'mobooking'); ?></span>
            </button>
        </div>
    </div>
</div>

<script>
// Settings Management JavaScript
jQuery(document).ready(function($) {
    const SettingsManager = {
        config: {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-settings-nonce'); ?>',
            userId: <?php echo $user_id; ?>
        },
        
        state: {
            isProcessing: false,
            hasUnsavedChanges: false,
            currentTab: 'business'
        },
        
        init: function() {
            this.attachEventListeners();
            this.initializeColorPickers();
            this.initializeTabs();
            this.trackChanges();
            this.updateEmailPreview();
            this.initializeBusinessHours();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Tab switching
            $('.tab-button').on('click', function() {
                const tabId = $(this).data('tab');
                self.switchTab(tabId);
            });
            
            // Form submission
            $('#settings-form').on('submit', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
            
            // Save draft
            $('#save-draft-btn').on('click', function() {
                self.saveSettings(true);
            });
            
            // Color presets
            $('.color-preset').on('click', function() {
                const color = $(this).data('color');
                $('#primary-color').val(color).trigger('change');
            });
            
            // Color picker sync
            $('.color-picker').on('input change', function() {
                const color = $(this).val();
                $(this).siblings('.color-text').val(color);
                $(this).siblings('.color-preview').css('background-color', color);
                self.updateBrandingPreview();
            });
            
            // Logo management
            $('.select-logo-btn').on('click', function() {
                self.selectLogo();
            });
            
            $('.remove-logo-btn').on('click', function() {
                self.removeLogo();
            });
            
            // Email template variables
            $('.variable-tag').on('click', function() {
                const variable = $(this).data('variable');
                const textarea = $(this).closest('.email-template-editor').find('textarea');
                self.insertVariable(textarea, variable);
            });
            
            // Email preview
            $('#email-header, #email-footer, #booking-confirmation-message').on('input', function() {
                self.updateEmailPreview();
            });
            
            $('#refresh-email-preview').on('click', function() {
                self.updateEmailPreview();
            });
            
            $('#send-test-email').on('click', function() {
                self.sendTestEmail();
            });
            
            // Business hours
            $('.day-toggle').on('change', function() {
                self.toggleBusinessHours($(this));
            });
            
            // Preview
            $('#preview-settings-btn').on('click', function() {
                self.showPreview();
            });
            
            // Data management
            $('#export-data-btn').on('click', function() {
                self.exportData();
            });
            
            $('#import-data-btn').on('click', function() {
                $('#import-file').click();
            });
            
            $('#import-file').on('change', function() {
                if (this.files.length > 0) {
                    self.importData(this.files[0]);
                }
            });
            
            // Reset/Restore actions
            $('#reset-settings-btn, #restore-defaults-btn').on('click', function() {
                self.showConfirmation(
                    '<?php _e('Reset Settings', 'mobooking'); ?>',
                    '<?php _e('Are you sure you want to reset all settings to defaults? This action cannot be undone.', 'mobooking'); ?>',
                    function() { self.resetSettings(); }
                );
            });
            
            // Modal controls
            $('.modal-close, .cancel-confirmation-btn').on('click', function() {
                self.hideModals();
            });
            
            $('.confirm-action-btn').on('click', function() {
                self.executeConfirmedAction();
            });
            
            // Preview tabs
            $('.preview-tab-btn').on('click', function() {
                const previewType = $(this).data('preview');
                self.switchPreviewTab(previewType);
            });
        },
        
        initializeColorPickers: function() {
            // Sync color inputs
            $('.color-picker').each(function() {
                const $picker = $(this);
                const $text = $picker.siblings('.color-text');
                const $preview = $picker.siblings('.color-preview');
                
                $picker.on('input', function() {
                    const color = $(this).val();
                    $text.val(color);
                    $preview.css('background-color', color);
                });
            });
        },
        
        initializeTabs: function() {
            // Get active tab from URL or default
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'business';
            this.switchTab(activeTab);
        },
        
        switchTab: function(tabId) {
            // Update button states
            $('.tab-button').removeClass('active').attr('aria-selected', 'false');
            $(`.tab-button[data-tab="${tabId}"]`).addClass('active').attr('aria-selected', 'true');
            
            // Update tab panes
            $('.tab-pane').removeClass('active');
            $(`#${tabId}`).addClass('active');
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url);
            
            this.state.currentTab = tabId;
        },
        
        trackChanges: function() {
            const self = this;
            $('#settings-form input, #settings-form textarea, #settings-form select').on('change input', function() {
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
            
            const formData = new FormData($('#settings-form')[0]);
            formData.append('action', 'mobooking_save_settings');
            formData.append('is_draft', isDraft ? '1' : '0');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || 'Settings saved successfully', 'success');
                        this.state.hasUnsavedChanges = false;
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
        
        selectLogo: function() {
            if (typeof wp !== 'undefined' && wp.media) {
                const mediaUploader = wp.media({
                    title: 'Choose Business Logo',
                    button: { text: 'Use This Logo' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                mediaUploader.on('select', () => {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#logo-url').val(attachment.url);
                    this.updateLogoPreview(attachment.url);
                    this.updateBrandingPreview();
                    this.state.hasUnsavedChanges = true;
                });
                
                mediaUploader.open();
            } else {
                const logoUrl = prompt('Enter logo URL:');
                if (logoUrl) {
                    $('#logo-url').val(logoUrl);
                    this.updateLogoPreview(logoUrl);
                    this.updateBrandingPreview();
                    this.state.hasUnsavedChanges = true;
                }
            }
        },
        
        removeLogo: function() {
            $('#logo-url').val('');
            this.updateLogoPreview('');
            this.updateBrandingPreview();
            this.state.hasUnsavedChanges = true;
        },
        
        updateLogoPreview: function(url) {
            const $preview = $('.logo-preview');
            const $removeBtn = $('.remove-logo-btn');
            
            if (url) {
                $preview.html(`<img src="${url}" alt="Logo Preview" class="current-logo">`);
                $removeBtn.show();
            } else {
                $preview.html(`
                    <div class="logo-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                            <circle cx="9" cy="9" r="2"></circle>
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                        </svg>
                        <span>No Logo Uploaded</span>
                    </div>
                `);
                $removeBtn.hide();
            }
        },
        
        updateBrandingPreview: function() {
            const companyName = $('#company-name').val() || 'Your Company';
            const primaryColor = $('#primary-color').val();
            const logoUrl = $('#logo-url').val();
            
            const $preview = $('.branding-preview .preview-card');
            const $header = $preview.find('.preview-header');
            const $title = $preview.find('.preview-title');
            const $button = $preview.find('.preview-button');
            const $logo = $preview.find('.preview-logo');
            
            $header.css('background-color', primaryColor);
            $button.css('background-color', primaryColor);
            $title.text(companyName);
            $preview.css('border-color', primaryColor);
            
            if (logoUrl) {
                if ($logo.length === 0) {
                    $header.prepend(`<img src="${logoUrl}" alt="Logo Preview" class="preview-logo">`);
                } else {
                    $logo.attr('src', logoUrl);
                }
            } else {
                $logo.remove();
            }
        },
        
        insertVariable: function($textarea, variable) {
            const textarea = $textarea[0];
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = $textarea.val();
            
            const newText = text.substring(0, start) + variable + text.substring(end);
            $textarea.val(newText);
            
            // Set cursor position after the inserted variable
            const newPosition = start + variable.length;
            textarea.setSelectionRange(newPosition, newPosition);
            textarea.focus();
            
            this.updateEmailPreview();
            this.state.hasUnsavedChanges = true;
        },
        
        updateEmailPreview: function() {
            const header = $('#email-header').val();
            const footer = $('#email-footer').val();
            const message = $('#booking-confirmation-message').val();
            const companyName = $('#company-name').val() || 'Your Company';
            const phone = $('#company-phone').val() || '';
            const email = $('#company-email').val() || '';
            const currentYear = new Date().getFullYear();
            
            // Replace variables
            let processedHeader = header
                .replace(/\{\{company_name\}\}/g, companyName)
                .replace(/\{\{phone\}\}/g, phone)
                .replace(/\{\{email\}\}/g, email)
                .replace(/\{\{current_year\}\}/g, currentYear);
                
            let processedFooter = footer
                .replace(/\{\{company_name\}\}/g, companyName)
                .replace(/\{\{phone\}\}/g, phone)
                .replace(/\{\{email\}\}/g, email)
                .replace(/\{\{current_year\}\}/g, currentYear);
            
            $('.email-header-preview').html(processedHeader);
            $('.email-footer-preview').html(processedFooter);
            $('.email-message-preview').html(`<p>${message}</p>`);
        },
        
        sendTestEmail: function() {
            const $btn = $('#send-test-email');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_send_test_email',
                email_header: $('#email-header').val(),
                email_footer: $('#email-footer').val(),
                booking_confirmation_message: $('#booking-confirmation-message').val(),
                company_name: $('#company-name').val(),
                phone: $('#company-phone').val(),
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Test email sent successfully', 'success');
                    } else {
                        this.showNotification(response.data?.message || 'Failed to send test email', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error sending test email', 'error');
                },
                complete: () => {
                    this.setLoading($btn, false);
                }
            });
        },
        
        initializeBusinessHours: function() {
            const self = this;
            
            $('.day-toggle').each(function() {
                self.toggleBusinessHours($(this));
            });
        },
        
        toggleBusinessHours: function($checkbox) {
            const $row = $checkbox.closest('.business-hour-row');
            const $timeInputs = $row.find('.time-inputs');
            const $inputs = $row.find('.time-input');
            const $closedIndicator = $row.find('.closed-indicator');
            
            if ($checkbox.is(':checked')) {
                $timeInputs.removeClass('disabled');
                $inputs.prop('disabled', false);
                $closedIndicator.addClass('hidden');
            } else {
                $timeInputs.addClass('disabled');
                $inputs.prop('disabled', true);
                $closedIndicator.removeClass('hidden');
            }
        },
        
        showPreview: function() {
            this.updateBrandingPreview();
            this.updateEmailPreview();
            $('#settings-preview-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },
        
        switchPreviewTab: function(previewType) {
            $('.preview-tab-btn').removeClass('active');
            $(`.preview-tab-btn[data-preview="${previewType}"]`).addClass('active');
            
            $('.preview-content').removeClass('active');
            $(`#${previewType}-preview, #${previewType}-preview-full`).addClass('active');
        },
        
        exportData: function() {
            const $btn = $('#export-data-btn');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_export_data',
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        // Create download link
                        const blob = new Blob([JSON.stringify(response.data, null, 2)], 
                            { type: 'application/json' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = `mobooking-export-${new Date().toISOString().split('T')[0]}.json`;
                        link.click();
                        URL.revokeObjectURL(url);
                        
                        this.showNotification('Data exported successfully', 'success');
                    } else {
                        this.showNotification('Failed to export data', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error exporting data', 'error');
                },
                complete: () => {
                    this.setLoading($btn, false);
                }
            });
        },
        
        importData: function(file) {
            const $btn = $('#import-data-btn');
            this.setLoading($btn, true);
            
            const formData = new FormData();
            formData.append('action', 'mobooking_import_data');
            formData.append('import_file', file);
            formData.append('nonce', this.config.nonce);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || 'Data imported successfully', 'success');
                    } else {
                        this.showNotification(response.data?.message || 'Failed to import data', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error importing data', 'error');
                },
                complete: () => {
                    this.setLoading($btn, false);
                    $('#import-file').val(''); // Clear file input
                }
            });
        },
        
        resetSettings: function() {
            const data = {
                action: 'mobooking_reset_settings',
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Settings reset successfully', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotification('Failed to reset settings', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error resetting settings', 'error');
                }
            });
        },
        
        showConfirmation: function(title, message, callback) {
            $('#confirmation-title').text(title);
            $('#confirmation-message').text(message);
            this.confirmedAction = callback;
            $('#settings-confirmation-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },
        
        executeConfirmedAction: function() {
            if (typeof this.confirmedAction === 'function') {
                this.confirmedAction();
            }
            this.hideModals();
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
            $('.notification').remove();
            
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
    
    SettingsManager.init();
});
</script>

<style>
/* Settings Section Styles */
.settings-section {
    animation: fadeIn 0.4s ease-out;
}

.settings-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.settings-header-content {
    display: flex;
    align-items: flex-start;
    gap: 3rem;
    flex: 1;
}

.settings-title-group {
    flex: 1;
}

.settings-main-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.title-icon {
    width: 2rem;
    height: 2rem;
    color: hsl(var(--primary));
}

.settings-subtitle {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 1rem;
}

.settings-status {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: hsl(var(--accent));
    border-radius: var(--radius);
}

.status-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: hsl(var(--muted-foreground));
}

.status-dot.active {
    background: hsl(var(--success));
}

.settings-header-actions {
    display: flex;
    gap: 1rem;
}

/* Settings Tabs */
.settings-tabs {
    margin-bottom: 2rem;
}


.tab-button {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    background: transparent;
    border: none;
    border-radius: var(--radius);
    color: hsl(var(--muted-foreground));
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    flex: 1;
    justify-content: center;
}

.tab-button:hover {
    background: hsl(var(--background));
    color: hsl(var(--foreground));
}

.tab-button.active {
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tab-icon {
    width: 1.25rem;
    height: 1.25rem;
}

/* Settings Form */
.settings-container {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.tab-pane {
    display: none;
    padding: 2rem;
}

.tab-pane.active {
    display: block;
    animation: fadeInTab 0.3s ease;
}

.settings-section-wrapper {
    max-width: none;
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid hsl(var(--border));
}

.section-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.section-description {
    margin: 0;
    color: hsl(var(--muted-foreground));
}

.settings-fields {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.field-label {
    font-weight: 600;
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.field-label.required::after {
    content: ' *';
    color: hsl(var(--destructive));
}

.form-control {
    padding: 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: hsl(var(--primary));
    box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
}

.field-help {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin: 0;
    line-height: 1.4;
}

/* Branding Section */
.branding-section {
    padding: 1.5rem 0;
    border-bottom: 1px solid hsl(var(--border));
}

.branding-section:last-child {
    border-bottom: none;
}

.subsection-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.logo-upload-section {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}

.logo-preview {
    width: 200px;
    height: 100px;
    border: 2px dashed hsl(var(--border));
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--muted) / 0.3);
}

.current-logo {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.logo-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    text-align: center;
}

.logo-placeholder svg {
    width: 2rem;
    height: 2rem;
}

.logo-upload-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    flex: 1;
}

.color-scheme-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
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
    background: none;
}

.color-text {
    flex: 1;
    font-family: monospace;
    font-size: 0.875rem;
}

.color-preview {
    width: 2.5rem;
    height: 2.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
}

.preset-colors {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.color-preset {
    width: 2rem;
    height: 2rem;
    border: 2px solid transparent;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.color-preset:hover {
    border-color: hsl(var(--foreground));
    transform: scale(1.1);
}

.branding-preview {
    margin-top: 1rem;
}

.preview-card {
    max-width: 400px;
    border: 2px solid hsl(var(--primary));
    border-radius: var(--radius-lg);
    overflow: hidden;
    background: hsl(var(--background));
}

.preview-header {
    padding: 1rem;
    background: hsl(var(--primary));
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.preview-logo {
    height: 2rem;
    width: auto;
}

.preview-title {
    font-weight: 600;
    font-size: 1.125rem;
}

.preview-content {
    padding: 1.5rem;
}

.preview-button {
    background: hsl(var(--primary));
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    cursor: pointer;
    margin-top: 1rem;
}

/* Email Templates */
.notification-section {
    padding: 1.5rem 0;
    border-bottom: 1px solid hsl(var(--border));
}

.notification-section:last-child {
    border-bottom: none;
}

.email-template-editor {
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
}

.template-toolbar {
    background: hsl(var(--muted));
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
}

.template-variables {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}

.template-variables label {
    font-weight: 600;
    margin-right: 0.5rem;
}

.variable-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: hsl(var(--primary));
    color: white;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.variable-tag:hover {
    background: hsl(var(--primary) / 0.8);
}

.email-template {
    border: none;
    border-radius: 0;
    font-family: monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    min-height: 120px;
    resize: vertical;
}

.notification-preferences {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.preference-item {
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
}

.preference-header {
    margin-bottom: 0.5rem;
}

.preference-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
}

.preference-title {
    color: hsl(var(--foreground));
}

.preference-description {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
}

.email-preview-container {
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    margin-top: 1rem;
}

.email-preview {
    background: white;
    color: #333;
    font-family: Arial, sans-serif;
    line-height: 1.6;
}

.email-header-preview,
.email-footer-preview {
    padding: 1rem;
}

.email-body-preview {
    padding: 2rem;
    min-height: 200px;
}

.preview-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    justify-content: flex-end;
}

/* Business Hours */
.business-hours-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.business-hour-row {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
}

.day-checkbox {
    display: flex;
    align-items: center;
}

.day-name {
    font-weight: 500;
    min-width: 100px;
}

.time-inputs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: opacity 0.2s ease;
}

.time-inputs.disabled {
    opacity: 0.5;
}

.time-input {
    padding: 0.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    font-size: 0.875rem;
}

.time-separator {
    color: hsl(var(--muted-foreground));
    font-weight: 500;
}

.closed-indicator {
    text-align: center;
}

.closed-text {
    color: hsl(var(--muted-foreground));
    font-style: italic;
}

.hidden {
    display: none;
}

/* Advanced Sections */
.advanced-section {
    padding: 1.5rem 0;
    border-bottom: 1px solid hsl(var(--border));
}

.advanced-section:last-child {
    border-bottom: none;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.checkbox-text {
    color: hsl(var(--foreground));
}

.data-management-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.data-action-card {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
}

.action-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--primary) / 0.1);
    border-radius: var(--radius);
    color: hsl(var(--primary));
    flex-shrink: 0;
}

.action-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.action-content p {
    margin: 0 0 1rem 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
}

.system-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: var(--radius);
}

.info-label {
    font-weight: 500;
    color: hsl(var(--muted-foreground));
}

.info-value {
    font-weight: 600;
    color: hsl(var(--foreground));
}

/* Form Actions */
.settings-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    background: hsl(var(--muted) / 0.3);
    border-top: 1px solid hsl(var(--border));
}

.settings-actions-left,
.settings-actions-right {
    display: flex;
    gap: 1rem;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .settings-header {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .settings-header-content {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .tab-list {
        flex-direction: column;
    }
    
    .tab-button {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .field-row {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .color-scheme-grid {
        grid-template-columns: 1fr;
    }
    
    .data-management-grid {
        grid-template-columns: 1fr;
    }
    
    .system-info-grid {
        grid-template-columns: 1fr;
    }
    
    .business-hour-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .logo-upload-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .settings-actions {
        flex-direction: column-reverse;
        gap: 1rem;
    }
    
    .settings-actions-left,
    .settings-actions-right {
        width: 100%;
        justify-content: center;
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

@keyframes fadeInTab {
    from {
        opacity: 0;
        transform: translateY(10px);
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

/* High contrast mode support */
@media (prefers-contrast: high) {
    .tab-button.active {
        border: 2px solid hsl(var(--primary));
    }
    
    .color-preset:hover {
        border-width: 3px;
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
    .settings-header-actions,
    .settings-actions,
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
// Enqueue additional scripts and styles for settings
wp_enqueue_media(); // Enable WordPress media uploader
wp_enqueue_script('wp-color-picker');
wp_enqueue_style('wp-color-picker');

// Add any additional PHP logic here if needed
?>