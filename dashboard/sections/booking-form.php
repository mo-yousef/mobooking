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

<div class="booking-form-section">
    <!-- Page Header -->
    <div class="section-header">
        <div class="header-content">
            <div class="header-main">
                <div class="title-group">
                    <h1 class="page-title">
                        <svg class="title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                        </svg>
                        <?php _e('Booking Form', 'mobooking'); ?>
                    </h1>
                    <p class="page-subtitle"><?php _e('Create and customize your public booking form for customers', 'mobooking'); ?></p>
                </div>
                
                <div class="header-stats">
                    <div class="stat-card">
                        <div class="stat-icon services <?php echo $services_count > 0 ? 'success' : 'warning'; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect width="7" height="7" x="3" y="3" rx="1"/>
                                <rect width="7" height="7" x="14" y="3" rx="1"/>
                                <rect width="7" height="7" x="14" y="14" rx="1"/>
                                <rect width="7" height="7" x="3" y="14" rx="1"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $services_count; ?></div>
                            <div class="stat-label"><?php _e('Services', 'mobooking'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon areas <?php echo $areas_count > 0 ? 'success' : 'warning'; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $areas_count; ?></div>
                            <div class="stat-label"><?php _e('Service Areas', 'mobooking'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon status <?php echo $settings->is_active ? 'success' : 'inactive'; ?>">
                            <?php if ($settings->is_active): ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                </svg>
                            <?php else: ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M15 9l-6 6M9 9l6 6"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label"><?php echo $settings->is_active ? __('Published', 'mobooking') : __('Draft', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="header-actions">
                <button type="button" class="btn-secondary" id="preview-form-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <?php _e('Preview', 'mobooking'); ?>
                </button>
                
                <button type="button" class="btn-secondary" id="copy-link-btn" data-url="<?php echo esc_attr($booking_url); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                    <?php _e('Copy Link', 'mobooking'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <?php if (!$can_publish): ?>
        <!-- Setup Requirements Alert -->
        <div class="setup-alert">
            <div class="alert-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <path d="M12 9v4"/>
                    <path d="M12 17h.01"/>
                </svg>
            </div>
            <div class="alert-content">
                <h3><?php _e('Setup Required', 'mobooking'); ?></h3>
                <p><?php _e('Complete these steps before publishing your booking form:', 'mobooking'); ?></p>
                <div class="requirements-list">
                    <div class="requirement-item <?php echo $services_count > 0 ? 'completed' : 'pending'; ?>">
                        <?php if ($services_count > 0): ?>
                            <svg class="check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                            <span><?php printf(_n('%d service configured', '%d services configured', $services_count, 'mobooking'), $services_count); ?></span>
                        <?php else: ?>
                            <svg class="cross-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M15 9l-6 6M9 9l6 6"/>
                            </svg>
                            <span><?php _e('Add at least one service', 'mobooking'); ?></span>
                            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="setup-link">
                                <?php _e('Add Services', 'mobooking'); ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="requirement-item <?php echo $areas_count > 0 ? 'completed' : 'pending'; ?>">
                        <?php if ($areas_count > 0): ?>
                            <svg class="check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                            <span><?php printf(_n('%d service area configured', '%d service areas configured', $areas_count, 'mobooking'), $areas_count); ?></span>
                        <?php else: ?>
                            <svg class="cross-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M15 9l-6 6M9 9l6 6"/>
                            </svg>
                            <span><?php _e('Define service areas (ZIP codes)', 'mobooking'); ?></span>
                            <a href="<?php echo esc_url(home_url('/dashboard/areas/')); ?>" class="setup-link">
                                <?php _e('Add Areas', 'mobooking'); ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Form Container -->
    <div class="form-container">
        <!-- Tab Navigation -->
        <div class="form-tabs">
            <div class="tab-list">
                <button type="button" class="tab-button active" data-tab="general">
                    <div class="tab-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </div>
                    <span><?php _e('General Settings', 'mobooking'); ?></span>
                </button>
                
                <button type="button" class="tab-button" data-tab="design">
                    <div class="tab-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="18" cy="5" r="3"/>
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="19" r="3"/>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                        </svg>
                    </div>
                    <span><?php _e('Design & Layout', 'mobooking'); ?></span>
                </button>
                
                <button type="button" class="tab-button" data-tab="sharing">
                    <div class="tab-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                            <polyline points="16,6 12,2 8,6"/>
                            <line x1="12" y1="2" x2="12" y2="15"/>
                        </svg>
                    </div>
                    <span><?php _e('Share & Embed', 'mobooking'); ?></span>
                </button>
                
                <button type="button" class="tab-button" data-tab="advanced">
                    <div class="tab-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                    </div>
                    <span><?php _e('Advanced', 'mobooking'); ?></span>
                </button>
            </div>
        </div>
        
        <!-- Form Content -->
        <form id="booking-form-settings" class="form-content">
            <?php wp_nonce_field('mobooking-booking-form-nonce', 'nonce'); ?>
            
            <!-- General Settings Tab -->
            <div id="general" class="tab-content active">
                <div class="content-grid">
                    <!-- Basic Information -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('Basic Information', 'mobooking'); ?></h3>
                            <p><?php _e('Configure the essential details for your booking form', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="field-row">
                                <div class="form-group">
                                    <label for="form-title" class="field-label required"><?php _e('Form Title', 'mobooking'); ?></label>
                                    <input type="text" id="form-title" name="form_title" class="form-control" 
                                           value="<?php echo esc_attr($settings->form_title); ?>" 
                                           placeholder="<?php _e('Book Our Services', 'mobooking'); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="form-active" class="field-label"><?php _e('Publication Status', 'mobooking'); ?></label>
                                    <select id="form-active" name="is_active" class="form-control" <?php echo !$can_publish ? 'disabled' : ''; ?>>
                                        <option value="1" <?php selected($settings->is_active, 1); ?>><?php _e('Published (Live)', 'mobooking'); ?></option>
                                        <option value="0" <?php selected($settings->is_active, 0); ?>><?php _e('Draft (Hidden)', 'mobooking'); ?></option>
                                    </select>
                                    <?php if (!$can_publish): ?>
                                        <small class="field-note warning"><?php _e('Complete setup requirements above to publish', 'mobooking'); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="form-description" class="field-label"><?php _e('Description', 'mobooking'); ?></label>
                                <textarea id="form-description" name="form_description" class="form-control" rows="3" 
                                          placeholder="<?php _e('Book our professional services quickly and easily. Get instant quotes and schedule your appointment online.', 'mobooking'); ?>"><?php echo esc_textarea($settings->form_description); ?></textarea>
                                <small class="field-note"><?php _e('This description appears below your form title', 'mobooking'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Options -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('Form Features', 'mobooking'); ?></h3>
                            <p><?php _e('Control what elements are displayed on your booking form', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="checkbox-grid">
                                <label class="checkbox-option">
                                    <input type="checkbox" name="show_form_header" value="1" <?php checked($settings->show_form_header, 1); ?>>
                                    <div class="checkbox-content">
                                        <div class="checkbox-title"><?php _e('Show Form Header', 'mobooking'); ?></div>
                                        <div class="checkbox-desc"><?php _e('Display title, logo and description at the top', 'mobooking'); ?></div>
                                    </div>
                                </label>
                                
                                <label class="checkbox-option">
                                    <input type="checkbox" name="show_service_descriptions" value="1" <?php checked($settings->show_service_descriptions, 1); ?>>
                                    <div class="checkbox-content">
                                        <div class="checkbox-title"><?php _e('Service Descriptions', 'mobooking'); ?></div>
                                        <div class="checkbox-desc"><?php _e('Show detailed descriptions for each service', 'mobooking'); ?></div>
                                    </div>
                                </label>
                                
                                <label class="checkbox-option">
                                    <input type="checkbox" name="show_price_breakdown" value="1" <?php checked($settings->show_price_breakdown, 1); ?>>
                                    <div class="checkbox-content">
                                        <div class="checkbox-title"><?php _e('Price Breakdown', 'mobooking'); ?></div>
                                        <div class="checkbox-desc"><?php _e('Display detailed pricing information', 'mobooking'); ?></div>
                                    </div>
                                </label>
                                
                                <label class="checkbox-option">
                                    <input type="checkbox" name="enable_zip_validation" value="1" <?php checked($settings->enable_zip_validation, 1); ?>>
                                    <div class="checkbox-content">
                                        <div class="checkbox-title"><?php _e('ZIP Code Validation', 'mobooking'); ?></div>
                                        <div class="checkbox-desc"><?php _e('Validate service area coverage', 'mobooking'); ?></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Design Tab -->
            <div id="design" class="tab-content">
                <div class="content-grid">
                    <!-- Colors & Branding -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('Colors & Branding', 'mobooking'); ?></h3>
                            <p><?php _e('Customize the visual appearance of your booking form', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="color-fields">
                                <div class="form-group">
                                    <label for="primary-color" class="field-label"><?php _e('Primary Color', 'mobooking'); ?></label>
                                    <div class="color-input-group">
                                        <input type="color" id="primary-color" name="primary_color" 
                                               value="<?php echo esc_attr($settings->primary_color); ?>" class="color-picker">
                                        <input type="text" class="color-text" value="<?php echo esc_attr($settings->primary_color); ?>" readonly>
                                    </div>
                                    <small class="field-note"><?php _e('Main accent color for buttons and highlights', 'mobooking'); ?></small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="secondary-color" class="field-label"><?php _e('Secondary Color', 'mobooking'); ?></label>
                                    <div class="color-input-group">
                                        <input type="color" id="secondary-color" name="secondary_color" 
                                               value="<?php echo esc_attr($settings->secondary_color); ?>" class="color-picker">
                                        <input type="text" class="color-text" value="<?php echo esc_attr($settings->secondary_color); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="logo-url" class="field-label"><?php _e('Logo URL', 'mobooking'); ?></label>
                                <div class="input-with-action">
                                    <input type="url" id="logo-url" name="logo_url" class="form-control" 
                                           value="<?php echo esc_attr($settings->logo_url); ?>" 
                                           placeholder="<?php _e('https://example.com/logo.png', 'mobooking'); ?>">
                                    <button type="button" class="btn-secondary select-logo-btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                            <circle cx="9" cy="9" r="2"/>
                                            <path d="M21 15l-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                        </svg>
                                        <?php _e('Choose', 'mobooking'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Layout Options -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('Layout & Style', 'mobooking'); ?></h3>
                            <p><?php _e('Adjust the layout and presentation of your form', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="field-row">
                                <div class="form-group">
                                    <label for="form-layout" class="field-label"><?php _e('Layout Style', 'mobooking'); ?></label>
                                    <select id="form-layout" name="form_layout" class="form-control">
                                        <option value="modern" <?php selected($settings->form_layout, 'modern'); ?>><?php _e('Modern (Recommended)', 'mobooking'); ?></option>
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
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sharing Tab -->
            <div id="sharing" class="tab-content">
                <div class="content-grid">
                    <!-- Public URL -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('Share Your Form', 'mobooking'); ?></h3>
                            <p><?php _e('Use these options to share your booking form with customers', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="share-options">
                            <div class="share-option">
                                <div class="share-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                    </svg>
                                </div>
                                <div class="share-content">
                                    <h4><?php _e('Direct Link', 'mobooking'); ?></h4>
                                    <p><?php _e('Share this URL directly with customers', 'mobooking'); ?></p>
                                    <div class="url-input-group">
                                        <input type="text" class="form-control url-display" value="<?php echo esc_attr($booking_url); ?>" readonly>
                                        <button type="button" class="btn-secondary copy-url-btn" data-url="<?php echo esc_attr($booking_url); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                            </svg>
                                            <?php _e('Copy', 'mobooking'); ?>
                                        </button>
                                        <button type="button" class="btn-secondary open-url-btn" data-url="<?php echo esc_attr($booking_url); ?>">
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
                            
                            <div class="share-option">
                                <div class="share-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="16 18 22 12 16 6"/>
                                        <polyline points="8 6 2 12 8 18"/>
                                    </svg>
                                </div>
                                <div class="share-content">
                                    <h4><?php _e('Embed Code', 'mobooking'); ?></h4>
                                    <p><?php _e('Add this form to your website', 'mobooking'); ?></p>
                                    <div class="embed-controls">
                                        <div class="embed-settings">
                                            <div class="field-row">
                                                <div class="form-group">
                                                    <label for="embed-width"><?php _e('Width', 'mobooking'); ?></label>
                                                    <input type="text" id="embed-width" class="form-control" value="100%" placeholder="100%">
                                                </div>
                                                <div class="form-group">
                                                    <label for="embed-height"><?php _e('Height', 'mobooking'); ?></label>
                                                    <input type="text" id="embed-height" class="form-control" value="800" placeholder="800">
                                                </div>
                                            </div>
                                            <button type="button" id="generate-embed-btn" class="btn-secondary btn-small">
                                                <?php _e('Generate Code', 'mobooking'); ?>
                                            </button>
                                        </div>
                                        <div class="embed-code-section">
                                            <textarea id="embed-code-display" class="form-control code-textarea" rows="4" readonly 
                                                      placeholder="<?php _e('Click "Generate Code" to create your embed code', 'mobooking'); ?>"></textarea>
                                            <button type="button" id="copy-embed-btn" class="btn-secondary btn-small" style="display: none;">
                                                <?php _e('Copy Code', 'mobooking'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="share-option">
                                <div class="share-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect width="3" height="3" x="7" y="3"/>
                                        <rect width="3" height="3" x="14" y="3"/>
                                        <rect width="3" height="3" x="7" y="10"/>
                                        <rect width="3" height="3" x="14" y="10"/>
                                        <rect width="3" height="3" x="7" y="17"/>
                                        <rect width="3" height="3" x="14" y="17"/>
                                    </svg>
                                </div>
                                <div class="share-content">
                                    <h4><?php _e('QR Code', 'mobooking'); ?></h4>
                                    <p><?php _e('Generate a QR code for easy mobile access', 'mobooking'); ?></p>
                                    <div class="qr-section">
                                        <div class="qr-display" id="qr-code-container">
                                            <div class="qr-placeholder">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                                    <rect width="3" height="3" x="7" y="3"/>
                                                    <rect width="3" height="3" x="14" y="3"/>
                                                    <rect width="3" height="3" x="7" y="10"/>
                                                    <rect width="3" height="3" x="14" y="10"/>
                                                    <rect width="3" height="3" x="7" y="17"/>
                                                    <rect width="3" height="3" x="14" y="17"/>
                                                </svg>
                                                <span><?php _e('QR Code will appear here', 'mobooking'); ?></span>
                                            </div>
                                        </div>
                                        <div class="qr-actions">
                                            <button type="button" id="generate-qr-btn" class="btn-secondary btn-small">
                                                <?php _e('Generate QR', 'mobooking'); ?>
                                            </button>
                                            <button type="button" id="download-qr-btn" class="btn-secondary btn-small" style="display: none;">
                                                <?php _e('Download', 'mobooking'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Tab -->
            <div id="advanced" class="tab-content">
                <div class="content-grid">
                    <!-- SEO Settings -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('SEO Optimization', 'mobooking'); ?></h3>
                            <p><?php _e('Improve your form\'s search engine visibility', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="form-group">
                                <label for="seo-title" class="field-label"><?php _e('Page Title', 'mobooking'); ?></label>
                                <input type="text" id="seo-title" name="seo_title" class="form-control" 
                                       value="<?php echo esc_attr($settings->seo_title); ?>" 
                                       placeholder="<?php _e('Book Our Professional Services - Your Company Name', 'mobooking'); ?>">
                                <small class="field-note"><?php _e('Recommended: 50-60 characters', 'mobooking'); ?></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="seo-description" class="field-label"><?php _e('Meta Description', 'mobooking'); ?></label>
                                <textarea id="seo-description" name="seo_description" class="form-control" rows="3" 
                                          placeholder="<?php _e('Book our professional services online. Quick, easy, and secure booking process...', 'mobooking'); ?>"><?php echo esc_textarea($settings->seo_description); ?></textarea>
                                <small class="field-note"><?php _e('Recommended: 150-160 characters', 'mobooking'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Code -->
                    <div class="settings-group">
                        <div class="group-header">
                            <h3><?php _e('Custom Code', 'mobooking'); ?></h3>
                            <p><?php _e('Add custom styling and tracking codes', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="form-group">
                                <label for="analytics-code" class="field-label"><?php _e('Analytics & Tracking', 'mobooking'); ?></label>
                                <textarea id="analytics-code" name="analytics_code" class="form-control code-textarea" rows="6" 
                                          placeholder="<!-- Google Analytics, Facebook Pixel, or other tracking codes -->"><?php echo esc_textarea($settings->analytics_code); ?></textarea>
                                <small class="field-note"><?php _e('Add Google Analytics, Facebook Pixel, or other tracking codes', 'mobooking'); ?></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="custom-css" class="field-label"><?php _e('Custom CSS', 'mobooking'); ?></label>
                                <textarea id="custom-css" name="custom_css" class="form-control code-textarea" rows="8" 
                                          placeholder="/* Add your custom CSS here */
.mobooking-booking-form-container {
   /* Your custom styles */
}"><?php echo esc_textarea($settings->custom_css); ?></textarea>
                                <small class="field-note"><?php _e('Advanced users only. Custom CSS will override default styles.', 'mobooking'); ?></small>
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
                            
                            <div class="field-row">
                                <div class="form-group">
                                    <label for="contact-info" class="field-label"><?php _e('Contact Information', 'mobooking'); ?></label>
                                    <textarea id="contact-info" name="contact_info" class="form-control" rows="3" 
                                              placeholder="<?php _e('Phone: (555) 123-4567\nEmail: info@company.com\nAddress: 123 Main St', 'mobooking'); ?>"><?php echo esc_textarea($settings->contact_info); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="social-links" class="field-label"><?php _e('Social Media', 'mobooking'); ?></label>
                                    <textarea id="social-links" name="social_links" class="form-control" rows="3" 
                                              placeholder="<?php _e('Facebook: https://facebook.com/yourpage\nInstagram: https://instagram.com/yourprofile', 'mobooking'); ?>"><?php echo esc_textarea($settings->social_links); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Form Actions -->
        <div class="form-actions">
            <div class="actions-left">
                <button type="button" id="reset-settings-btn" class="btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    <?php _e('Reset to Defaults', 'mobooking'); ?>
                </button>
            </div>
            
            <div class="actions-right">
                <button type="submit" id="save-settings-btn" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17,21 17,13 7,13 7,21"/>
                        <polyline points="7,3 7,8 15,8"/>
                    </svg>
                    <span class="btn-text"><?php _e('Save Settings', 'mobooking'); ?></span>
                    <span class="btn-loading" style="display: none;">
                        <div class="spinner-modern"></div>
                        <?php _e('Saving...', 'mobooking'); ?>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="form-preview-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3><?php _e('Form Preview', 'mobooking'); ?></h3>
            <div class="modal-actions">
                <button type="button" id="refresh-preview-btn" class="btn-secondary btn-small">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    <?php _e('Refresh', 'mobooking'); ?>
                </button>
                <button type="button" id="open-in-new-tab-btn" class="btn-secondary btn-small" data-url="<?php echo esc_attr($booking_url); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15,3 21,3 21,9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    <?php _e('Open in New Tab', 'mobooking'); ?>
                </button>
            </div>
            <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="preview-container">
                <iframe id="form-preview-iframe" src="<?php echo esc_url($booking_url); ?>" 
                        style="width: 100%; height: 600px; border: none; border-radius: 8px;"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
/* Existing styles remain the same - just keeping the essential CSS */
.booking-form-section {
    animation: fadeIn 0.4s ease-out;
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.header-content {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
}

.header-main {
    flex: 1;
    display: flex;
    align-items: flex-start;
    gap: 3rem;
}

.title-group {
    flex: 1;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    line-height: 1.2;
}

.title-icon {
    width: 2rem;
    height: 2rem;
    color: hsl(var(--primary));
    flex-shrink: 0;
}

.page-subtitle {
    margin: 0;
    font-size: 1rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

.header-stats {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    min-width: 7rem;
}

.stat-icon {
    width: 2rem;
    height: 2rem;
    border-radius: calc(var(--radius) - 2px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon.success {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.stat-icon.warning {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.stat-icon.inactive {
    background: hsl(var(--muted) / 0.1);
    color: hsl(var(--muted-foreground));
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.125rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-shrink: 0;
}

.setup-alert {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: hsl(var(--warning) / 0.1);
    border: 1px solid hsl(var(--warning) / 0.3);
    border-radius: var(--radius);
    margin-bottom: 2rem;
}

.alert-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: hsl(var(--warning) / 0.2);
    color: hsl(var(--warning));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
}

.alert-content h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--warning));
}

.alert-content p {
    margin: 0 0 1rem 0;
    color: hsl(var(--foreground));
}

.requirements-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: hsl(var(--background));
    border-radius: calc(var(--radius) - 2px);
}

.requirement-item.completed {
    background: hsl(var(--success) / 0.1);
}

.check-icon {
    color: hsl(var(--success));
}

.cross-icon {
    color: hsl(var(--destructive));
}

.setup-link {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: auto;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--primary));
    text-decoration: none;
    transition: all 0.2s ease;
}

.setup-link:hover {
    color: hsl(var(--primary) / 0.8);
    gap: 0.5rem;
}

.form-container {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) + 4px);
    overflow: hidden;
}

.form-tabs {
    border-bottom: 1px solid hsl(var(--border));
}

.tab-list {
    display: flex;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border: none;
    background: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
    flex: 1;
    min-width: 0;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--muted-foreground));
}

.tab-button:hover {
    background: hsl(var(--accent) / 0.5);
    color: hsl(var(--foreground));
}

.tab-button.active {
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    box-shadow: var(--shadow-sm);
    border: 1px solid hsl(var(--border));
}

.tab-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: calc(var(--radius) - 2px);
    background: hsl(var(--muted) / 0.5);
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.tab-button.active .tab-icon {
    background: hsl(var(--primary));
    color: white;
}

.form-content {
    position: relative;
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

.content-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}
/* 
.settings-group {
    padding: 1.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--card));
} */

.group-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid hsl(var(--border));
}

.group-header h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.group-header p {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

.form-fields {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.field-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.field-label.required::after {
    content: " *";
    color: hsl(var(--destructive));
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--background));
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.field-note {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

.field-note.warning {
    color: hsl(var(--warning));
}

.color-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
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
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: 0.8125rem;
}

.input-with-action {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
}

.input-with-action .form-control {
    flex: 1;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.checkbox-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.checkbox-option:hover {
    background: hsl(var(--accent) / 0.5);
    border-color: hsl(var(--primary) / 0.3);
}

.checkbox-option input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    margin: 0;
    flex-shrink: 0;
}

.checkbox-content {
    flex: 1;
}

.checkbox-title {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.125rem;
}

.checkbox-desc {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

.share-options {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.share-option {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background: hsl(var(--card));
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
}

.share-content {
    flex: 1;
}

.share-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.share-content p {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.url-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: stretch;
}

.url-display {
    flex: 1;
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: 0.8125rem;
    background: hsl(var(--muted) / 0.5);
}

.embed-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.embed-settings {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.embed-code-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.code-textarea {
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: 0.8125rem;
    background: hsl(var(--muted) / 0.5);
    resize: vertical;
}

.qr-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.qr-display {
    width: 8rem;
    height: 8rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--muted) / 0.3);
    margin-bottom: 1rem;
}

.qr-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    text-align: center;
}

.qr-placeholder span {
    font-size: 0.75rem;
}

.qr-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-small {
    padding: 0.5rem 0.875rem;
    font-size: 0.8125rem;
}

.form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-top: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
    gap: 1rem;
}

.actions-left,
.actions-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-primary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.9));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px hsl(var(--primary) / 0.2);
}

.btn-primary:hover {
    background: linear-gradient(135deg, hsl(var(--primary) / 0.9), hsl(var(--primary) / 0.8));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.btn-secondary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: hsl(var(--accent));
}

.btn-secondary svg {
    width: 1rem;
    height: 1rem;
}

.btn-loading {
    display: none;
}

.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.spinner-modern {
    width: 1rem;
    height: 1rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.mobooking-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobooking-modal:not([style*="display: none"]) {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    width: 90vw;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

.modal-large {
    max-width: 90vw;
    width: 90vw;
    height: 90vh;
    max-height: 90vh;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(2rem) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
}

.modal-close {
    width: 2rem;
    height: 2rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: white;
}

.modal-close svg {
    width: 1rem;
    height: 1rem;
}

.modal-body {
    padding: 1.5rem 2rem;
    flex: 1;
    overflow-y: auto;
}

.preview-container {
    width: 100%;
    height: 100%;
    border-radius: var(--radius);
    overflow: hidden;
    border: 1px solid hsl(var(--border));
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }
    
    .header-main {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .header-stats {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .tab-list {
        flex-direction: column;
    }
    
    .field-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .color-fields {
        grid-template-columns: 1fr;
    }
    
    .checkbox-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column-reverse;
        gap: 1rem;
    }
    
    .actions-left,
    .actions-right {
        width: 100%;
        justify-content: center;
    }
    
    .actions-right .btn-primary,
    .actions-right .btn-secondary {
        flex: 1;
    }
    
    .share-option {
        flex-direction: column;
        text-align: center;
    }
    
    .url-input-group {
        flex-direction: column;
    }
    
    .modal-large {
        width: 95vw;
        height: 95vh;
    }
    
    .modal-header {
        padding: 1rem;
        flex-direction: column;
        gap: 1rem;
    }
    
    .modal-actions {
        width: 100%;
        justify-content: center;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Enhanced Booking Form Manager - REMOVED SAVE DRAFT FUNCTIONALITY
    const BookingFormManager = {
        // Tab switching
        initTabs: function() {
            $('.tab-button').on('click', function() {
                const target = $(this).data('tab');
                console.log('Tab clicked:', target);
                
                // Update active tab button
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                // Update active tab content
                $('.tab-content').removeClass('active');
                $('#' + target).addClass('active');
            });
        },
        
        // Color picker updates
        initColorPickers: function() {
            $('.color-picker').on('change', function() {
                const color = $(this).val();
                $(this).siblings('.color-text').val(color);
            });
        },
        
        // Copy functionality
        initCopyButtons: function() {
            $('.copy-url-btn, #copy-link-btn').on('click', function() {
                const url = $(this).data('url') || $(this).closest('.url-input-group').find('.url-display').val();
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(() => {
                        BookingFormManager.showNotification('URL copied to clipboard!', 'success');
                    });
                } else {
                    // Fallback for older browsers
                    const textarea = $('<textarea>').val(url).appendTo('body').select();
                    document.execCommand('copy');
                    textarea.remove();
                    BookingFormManager.showNotification('URL copied to clipboard!', 'success');
                }
            });
            
            $('#copy-embed-btn').on('click', function() {
                const embedCode = $('#embed-code-display').val();
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(embedCode).then(() => {
                        BookingFormManager.showNotification('Embed code copied to clipboard!', 'success');
                    });
                } else {
                    $('#embed-code-display').select();
                    document.execCommand('copy');
                    BookingFormManager.showNotification('Embed code copied to clipboard!', 'success');
                }
            });
        },
        
        // Open URL functionality
        initOpenButtons: function() {
            $('.open-url-btn, #open-in-new-tab-btn').on('click', function() {
                const url = $(this).data('url');
                window.open(url, '_blank');
            });
        },
        
        // Embed code generation
        initEmbedGeneration: function() {
            $('#generate-embed-btn').on('click', function() {
                const width = $('#embed-width').val() || '100%';
                const height = $('#embed-height').val() || '800';
                const embedUrl = $('input[name="embed_url"]').val() || $('.url-display').val();
                
                const embedCode = `<iframe src="${embedUrl}" width="${width}" height="${height}" frameborder="0" style="border: none; border-radius: 8px;"></iframe>`;
                
                $('#embed-code-display').val(embedCode);
                $('#copy-embed-btn').show();
            });
        },
        
        // QR Code generation
        initQRGeneration: function() {
            $('#generate-qr-btn').on('click', function() {
                const url = $('.url-display').val();
                const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;
                
                $('#qr-code-container').html(`<img src="${qrCodeUrl}" alt="QR Code" style="width: 100%; height: 100%; object-fit: contain;">`);
                $('#download-qr-btn').show().data('qr-url', qrCodeUrl);
            });
            
            $('#download-qr-btn').on('click', function() {
                const qrUrl = $(this).data('qr-url');
                const link = $('<a>').attr({
                    href: qrUrl,
                    download: 'booking-form-qr-code.png'
                }).appendTo('body');
                link[0].click();
                link.remove();
            });
        },
        
        // Form preview
        initPreview: function() {
            $('#preview-form-btn').on('click', function() {
                $('#form-preview-modal').show();
            });
            
            $('#refresh-preview-btn').on('click', function() {
                const iframe = $('#form-preview-iframe')[0];
                iframe.src = iframe.src;
            });
            
            // Modal close functionality
            $('.modal-close, .mobooking-modal').on('click', function(e) {
                if (e.target === this) {
                    $('.mobooking-modal').hide();
                }
            });
        },
        
        // FIXED: Form saving WITHOUT draft functionality
        initFormSaving: function() {
            console.log('Initializing form saving...');
            
            // Handle form submission properly
            $('#booking-form-settings').on('submit', function(e) {
                console.log('Form submit event triggered');
                e.preventDefault();
                e.stopPropagation();
                
                // Call save settings
                BookingFormManager.saveSettings();
                return false;
            });
            
            // Save Settings button click handler
            $('#save-settings-btn').on('click', function(e) {
                console.log('Save Settings button clicked');
                e.preventDefault();
                e.stopPropagation();
                
                // Trigger form submission
                $('#booking-form-settings').trigger('submit');
                return false;
            });
        },
        
        // Reset settings
        initResetSettings: function() {
            $('#reset-settings-btn').on('click', function() {
                if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                    BookingFormManager.resetSettings();
                }
            });
        },
        
        // ENHANCED: Save settings - REMOVED DRAFT FUNCTIONALITY
        saveSettings: function() {
            console.log('=== SAVE SETTINGS CALLED ===');
            
            const $form = $('#booking-form-settings');
            const $saveBtn = $('#save-settings-btn');
            
            console.log('Form element:', $form.length ? 'Found' : 'NOT FOUND');
            console.log('Button element:', $saveBtn.length ? 'Found' : 'NOT FOUND');
            
            if ($form.length === 0) {
                console.error('Form not found!');
                BookingFormManager.showNotification('Form not found!', 'error');
                return;
            }
            
            // Create FormData object
            const formData = new FormData($form[0]);
            
            // CRITICAL: Add the action
            formData.append('action', 'mobooking_save_booking_form_settings');
            console.log('Added action: mobooking_save_booking_form_settings');
            
            // Debug: Log all form data
            console.log('=== FORM DATA BEING SENT ===');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ':', pair[1]);
            }
            
            // Show loading state
            $saveBtn.addClass('loading').prop('disabled', true);
            $('.btn-text', $saveBtn).hide();
            $('.btn-loading', $saveBtn).show();
            
            console.log('Starting AJAX request...');
            
            $.ajax({
                url: mobookingDashboard.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                beforeSend: function(xhr) {
                    console.log('AJAX request starting...');
                },
                success: function(response) {
                    console.log('=== AJAX SUCCESS ===');
                    console.log('Response:', response);
                    
                    if (response && response.success) {
                        const message = response.data && response.data.message ? 
                            response.data.message : 
                            'Settings saved successfully!';
                            
                        BookingFormManager.showNotification(message, 'success');
                        
                        // Update URLs if they changed
                        if (response.data && response.data.booking_url) {
                            $('.url-display').val(response.data.booking_url);
                            $('.copy-url-btn, #copy-link-btn').data('url', response.data.booking_url);
                            $('#form-preview-iframe').attr('src', response.data.booking_url);
                            console.log('Updated URLs');
                        }
                        
                        // Update the status indicator
                        if (response.data && response.data.booking_url) {
                            $('.stat-icon.status').removeClass('inactive').addClass('success');
                            $('.stat-label:contains("Draft")').text('Published');
                        }
                        
                    } else {
                        console.error('AJAX Success but response.success is false:', response);
                        const errorMessage = response && response.data ? 
                            response.data : 
                            'Failed to save settings. Please try again.';
                        BookingFormManager.showNotification(errorMessage, 'error');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('=== AJAX ERROR ===');
                    console.error('Status:', jqXHR.status);
                    console.error('Status Text:', jqXHR.statusText);
                    console.error('Response Text:', jqXHR.responseText);
                    console.error('Text Status:', textStatus);
                    console.error('Error Thrown:', errorThrown);
                    
                    let errorMessage = 'An error occurred while saving settings.';
                    
                    if (jqXHR.status === 0) {
                        errorMessage = 'Network error. Please check your internet connection.';
                    } else if (jqXHR.status === 403) {
                        errorMessage = 'Permission denied. Please refresh the page and try again.';
                    } else if (jqXHR.status === 404) {
                        errorMessage = 'AJAX endpoint not found. Please contact support.';
                    } else if (jqXHR.status === 500) {
                        errorMessage = 'Server error. Please check the error logs.';
                    } else if (textStatus === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    }
                    
                    if (jqXHR.responseText) {
                        try {
                            const errorResponse = JSON.parse(jqXHR.responseText);
                            if (errorResponse.data) {
                                errorMessage = errorResponse.data;
                            }
                        } catch (e) {
                            if (jqXHR.responseText.includes('Fatal error') || jqXHR.responseText.includes('Parse error')) {
                                errorMessage = 'PHP Error occurred. Please check server logs.';
                            }
                        }
                    }
                    
                    BookingFormManager.showNotification(errorMessage, 'error');
                },
                complete: function() {
                    console.log('AJAX request completed');
                    
                    // Restore button state
                    $saveBtn.removeClass('loading').prop('disabled', false);
                    $('.btn-text', $saveBtn).show();
                    $('.btn-loading', $saveBtn).hide();
                }
            });
        },
        
        // Reset settings
        resetSettings: function() {
            $.ajax({
                url: mobookingDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mobooking_reset_booking_form_settings',
                    nonce: $('input[name="nonce"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        BookingFormManager.showNotification('Settings reset successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        BookingFormManager.showNotification(
                            response.data || 'Failed to reset settings.',
                            'error'
                        );
                    }
                },
                error: function() {
                    BookingFormManager.showNotification('An error occurred while resetting settings.', 'error');
                }
            });
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            console.log('Showing notification:', message, type);
            
            // Remove existing notifications
            $('.mobooking-notification').remove();
            
            const notification = $(`
                <div class="mobooking-notification ${type}">
                    <span>${message}</span>
                    <button class="notification-close" aria-label="Close">&times;</button>
                </div>
            `).appendTo('body');
            
            // Show notification with animation
            setTimeout(() => notification.addClass('show'), 100);
            
            // Auto hide after 5 seconds
            const autoHideTimer = setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
            
            // Manual close
            notification.find('.notification-close').on('click', function() {
                clearTimeout(autoHideTimer);
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            });
        },
        
        // Initialize all functionality
        init: function() {
            console.log('=== INITIALIZING BOOKING FORM MANAGER ===');
            
            // Check if required elements exist
            if ($('#booking-form-settings').length === 0) {
                console.error('Booking form not found!');
                return;
            }
            
            if ($('#save-settings-btn').length === 0) {
                console.error('Save settings button not found!');
                return;
            }
            
            console.log('Required elements found, proceeding with initialization...');
            
            this.initTabs();
            this.initColorPickers();
            this.initCopyButtons();
            this.initOpenButtons();
            this.initEmbedGeneration();
            this.initQRGeneration();
            this.initPreview();
            this.initFormSaving(); // This is the critical one for Save Settings
            this.initResetSettings();
            
            console.log('BookingFormManager initialization complete');
        }
    };
    
    // Initialize the booking form manager
    BookingFormManager.init();
    
    // Make it globally available for debugging
    window.BookingFormManager = BookingFormManager;
    
    console.log('jQuery ready function completed');
    console.log('mobookingDashboard object:', window.mobookingDashboard);
});
</script>

<?php
// ENHANCED AJAX handler for saving booking form settings - MAPPED ALL FIELDS CORRECTLY
add_action('wp_ajax_mobooking_save_booking_form_settings', function() {
    try {
        // Debug incoming data
        error_log('MoBooking - Received POST data: ' . print_r($_POST, true));
        
        // Check nonce
        if (!isset($_POST['nonce'])) {
            error_log('MoBooking - Nonce not set in POST data');
            wp_send_json_error(__('Security nonce is missing.', 'mobooking'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            error_log('MoBooking - Nonce verification failed. Received nonce: ' . $_POST['nonce']);
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        
        // FIXED: Map ALL form fields correctly to database columns
        $settings_data = array(
            // Basic Information
            'form_title' => sanitize_text_field($_POST['form_title'] ?? ''),
            'form_description' => sanitize_textarea_field($_POST['form_description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? absint($_POST['is_active']) : 0,
            
            // Form Features
            'show_form_header' => isset($_POST['show_form_header']) ? 1 : 0,
            'show_service_descriptions' => isset($_POST['show_service_descriptions']) ? 1 : 0,
            'show_price_breakdown' => isset($_POST['show_price_breakdown']) ? 1 : 0,
            'enable_zip_validation' => isset($_POST['enable_zip_validation']) ? 1 : 0,
            
            // Design & Branding
            'primary_color' => sanitize_hex_color($_POST['primary_color'] ?? '#3b82f6'),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color'] ?? '#1e40af'),
            'logo_url' => esc_url_raw($_POST['logo_url'] ?? ''),
            
            // Layout & Style
            'form_layout' => sanitize_text_field($_POST['form_layout'] ?? 'modern'),
            'form_width' => sanitize_text_field($_POST['form_width'] ?? 'standard'),
            
            // SEO Optimization
            'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize_textarea_field($_POST['seo_description'] ?? ''),
            
            // Custom Code
            'analytics_code' => wp_kses_post($_POST['analytics_code'] ?? ''),
            'custom_css' => wp_strip_all_tags($_POST['custom_css'] ?? ''),
            
            // Form Footer
            'custom_footer_text' => sanitize_textarea_field($_POST['custom_footer_text'] ?? ''),
            'contact_info' => sanitize_textarea_field($_POST['contact_info'] ?? ''),
            'social_links' => sanitize_textarea_field($_POST['social_links'] ?? ''),
            
            // Additional database columns that may exist
            'background_color' => sanitize_hex_color($_POST['background_color'] ?? '#ffffff'),
            'text_color' => sanitize_hex_color($_POST['text_color'] ?? '#1f2937'),
            'language' => sanitize_text_field($_POST['language'] ?? 'en'),
            'show_form_footer' => isset($_POST['show_form_footer']) ? 1 : 0,
            'custom_js' => wp_strip_all_tags($_POST['custom_js'] ?? ''),
            'step_indicator_style' => sanitize_text_field($_POST['step_indicator_style'] ?? 'progress'),
            'button_style' => sanitize_text_field($_POST['button_style'] ?? 'rounded'),
            'enable_testimonials' => isset($_POST['enable_testimonials']) ? 1 : 0,
            'testimonials_data' => wp_kses_post($_POST['testimonials_data'] ?? '')
        );
        
        // Debug settings data being saved
        error_log('MoBooking - Settings data to save: ' . print_r($settings_data, true));
        
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

// Add AJAX handler for resetting booking form settings
add_action('wp_ajax_mobooking_reset_booking_form_settings', function() {
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
        
        // Reset settings using BookingForm Manager
        if (class_exists('\MoBooking\BookingForm\Manager')) {
            $booking_form_manager = new \MoBooking\BookingForm\Manager();
            $result = $booking_form_manager->reset_settings($user_id);
        } else {
            // Fallback: Direct database reset
            global $wpdb;
            $result = $wpdb->delete(
                $wpdb->prefix . 'mobooking_booking_form_settings',
                array('user_id' => $user_id),
                array('%d')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Settings reset to defaults successfully.', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'mobooking'));
        }
        
    } catch (Exception $e) {
        error_log('MoBooking - Exception in reset booking form settings: ' . $e->getMessage());
        wp_send_json_error(__('An error occurred while resetting settings.', 'mobooking'));
    }
});

// Add notification styles
?>
<style>
/* Notification System */
.mobooking-notification {
    position: fixed;
    top: 2rem;
    right: 2rem;
    z-index: 9999;
    padding: 1rem 1.5rem;
    border-radius: var(--radius);
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    max-width: 24rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    transform: translateX(calc(100% + 2rem));
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
}

.mobooking-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.mobooking-notification.success {
    background: hsl(var(--success) / 0.1);
    border-color: hsl(var(--success) / 0.3);
    color: hsl(var(--success));
}

.mobooking-notification.error {
    background: hsl(var(--destructive) / 0.1);
    border-color: hsl(var(--destructive) / 0.3);
    color: hsl(var(--destructive));
}

.mobooking-notification.info {
    background: hsl(var(--info) / 0.1);
    border-color: hsl(var(--info) / 0.3);
    color: hsl(var(--info));
}

.mobooking-notification.warning {
    background: hsl(var(--warning) / 0.1);
    border-color: hsl(var(--warning) / 0.3);
    color: hsl(var(--warning));
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: inherit;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s ease;
    padding: 0;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    opacity: 1;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .mobooking-notification {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
    }
}

/* Loading animation improvements */
@keyframes spin {
    to { 
        transform: rotate(360deg); 
    }
}

.spinner-modern {
    animation: spin 1s linear infinite;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .mobooking-notification {
        transition: none;
    }
    
    .spinner-modern {
        animation: none;
    }
    
    .spinner-modern::before {
        content: '⟳';
        font-size: 1rem;
    }
}


/* Error state styles */
.form-control.error {
    border-color: hsl(var(--destructive));
    box-shadow: 0 0 0 2px hsl(var(--destructive) / 0.2);
}

.field-error {
    font-size: 0.75rem;
    color: hsl(var(--destructive));
    margin-top: 0.25rem;
}

/* Success state styles */
.form-control.success {
    border-color: hsl(var(--success));
    box-shadow: 0 0 0 2px hsl(var(--success) / 0.2);
}

/* Enhanced responsive design */
@media (max-width: 480px) {
    .mobooking-notification {
        top: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: none;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .stat-card {
        min-width: auto;
        flex: 1;
    }
    
    .setup-alert {
        padding: 1rem;
    }
    
    .share-option {
        padding: 1rem;
    }
    
    .modal-header {
        padding: 0.75rem;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .modal-body {
        padding: 1rem;
    }
}

/* Print styles for QR codes and embed codes */
@media print {
    .booking-form-section {
        background: white;
        color: black;
    }
    
    .header-actions,
    .form-actions,
    .modal-close {
        display: none;
    }
    
    .share-option {
        page-break-inside: avoid;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .btn-primary,
    .btn-secondary {
        border-width: 2px;
    }
    
    .form-control {
        border-width: 2px;
    }
    
    .mobooking-notification {
        border-width: 2px;
    }
}

/* Improved focus indicators for keyboard navigation */
.tab-button:focus-visible {
    outline: 2px solid hsl(var(--ring));
    outline-offset: -2px;
}

.checkbox-option:focus-within {
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

/* Status indicators improvements */
.stat-icon svg {
    transition: all 0.2s ease;
}

.stat-card:hover .stat-icon svg {
    transform: scale(1.1);
}

/* Enhanced button states */
.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Form validation improvements */
.form-group.required .field-label::after {
    content: " *";
    color: hsl(var(--destructive));
    font-weight: bold;
}

.form-group.has-error .form-control {
    border-color: hsl(var(--destructive));
    box-shadow: 0 0 0 2px hsl(var(--destructive) / 0.2);
}

.form-group.has-error .field-label {
    color: hsl(var(--destructive));
}

/* Enhanced color picker styles */
.color-input-group {
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    background: hsl(var(--background));
}

.color-picker {
    border: none;
    border-right: 1px solid hsl(var(--border));
}

.color-text {
    border: none;
    background: transparent;
}

/* Embed code enhancements */
.code-textarea {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
    line-height: 1.4;
    white-space: pre;
    word-wrap: break-word;
}

/* QR code enhancements */
.qr-display img {
    border-radius: calc(var(--radius) - 2px);
}

/* URL display enhancements */
.url-display {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
    word-break: break-all;
    user-select: all;
}

/* Modal enhancements */
.modal-large .modal-body {
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.preview-container {
    flex: 1;
    min-height: 0;
}

#form-preview-iframe {
    border-radius: var(--radius);
    transition: opacity 0.3s ease;
}

/* Loading state for iframe */
.preview-container.loading #form-preview-iframe {
    opacity: 0.5;
    pointer-events: none;
}

.preview-container.loading::after {
    content: '';
    position: absolute;
    inset: 0;
    background: hsl(var(--background) / 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    border-radius: var(--radius);
}

/* Smooth transitions for better UX */
.tab-content {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Settings group animations */
.settings-group {
    animation: slideInLeft 0.4s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Enhanced form field animations */
.form-control:focus {
    transform: scale(1.01);
}

.form-control:invalid {
    border-color: hsl(var(--destructive));
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-3px); }
    20%, 40%, 60%, 80% { transform: translateX(3px); }
}

/* Enhanced button hover effects */
.btn-primary:hover {
    box-shadow: 0 6px 20px hsl(var(--primary) / 0.4);
}

.btn-secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--shadow) / 0.15);
}

/* Improved tooltip-like help text */
.field-note {
    position: relative;
    transition: all 0.2s ease;
}

.field-note:hover {
    color: hsl(var(--foreground));
}

/* Enhanced requirements list */
.requirement-item {
    transition: all 0.2s ease;
}

.requirement-item:hover {
    background: hsl(var(--accent) / 0.3);
    transform: translateX(4px);
}

/* Better visual hierarchy */
.group-header h3 {
    position: relative;
}

.group-header h3::before {
    content: '';
    position: absolute;
    left: -1rem;
    top: 50%;
    width: 3px;
    height: 1.5rem;
    background: hsl(var(--primary));
    border-radius: 2px;
    transform: translateY(-50%);
}

/* Enhanced share options */
.share-option:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px hsl(var(--shadow) / 0.2);
}

.share-option:hover .share-icon {
    background: hsl(var(--primary));
    color: white;
    transform: scale(1.1);
}

/* Progress indicators for form saving */
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
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}
</style>

<script>
// Additional JavaScript enhancements
jQuery(document).ready(function($) {
    // Enhanced form validation
    const FormValidator = {
        validateField: function($field) {
            const value = $field.val().trim();
            const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
            const isRequired = $field.prop('required');
            
            let isValid = true;
            let errorMessage = '';
            
            // Required field validation
            if (isRequired && !value) {
                isValid = false;
                errorMessage = 'This field is required.';
            }
            
            // Email validation
            if (fieldType === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
            }
            
            // URL validation
            if (fieldType === 'url' && value) {
                try {
                    new URL(value);
                } catch {
                    isValid = false;
                    errorMessage = 'Please enter a valid URL.';
                }
            }
            
            // Color validation
            if (fieldType === 'color' && value) {
                const colorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
                if (!colorRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid color code.';
                }
            }
            
            this.showFieldValidation($field, isValid, errorMessage);
            return isValid;
        },
        
        showFieldValidation: function($field, isValid, errorMessage) {
            const $group = $field.closest('.form-group');
            const $error = $group.find('.field-error');
            
            $group.removeClass('has-error has-success');
            $field.removeClass('error success');
            $error.remove();
            
            if (!isValid) {
                $group.addClass('has-error');
                $field.addClass('error');
                $group.append(`<div class="field-error">${errorMessage}</div>`);
            } else if ($field.val().trim()) {
                $group.addClass('has-success');
                $field.addClass('success');
            }
        },
        
        validateForm: function() {
            let isValid = true;
            $('#booking-form-settings .form-control[required]').each(function() {
                if (!FormValidator.validateField($(this))) {
                    isValid = false;
                }
            });
            return isValid;
        }
    };
    
    // Real-time validation
    $('#booking-form-settings .form-control').on('blur', function() {
        FormValidator.validateField($(this));
    });
    
    // Enhanced auto-save functionality (optional)
    let autoSaveTimer;
    const AUTO_SAVE_DELAY = 30000; // 30 seconds
    
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            if ($('#booking-form-settings').hasClass('form-changed')) {
                console.log('Auto-saving form...');
                BookingFormManager.saveSettings(true); // true for auto-save
            }
        }, AUTO_SAVE_DELAY);
    }
    
    // Mark form as changed
    $('#booking-form-settings').on('input change', '.form-control', function() {
        $('#booking-form-settings').addClass('form-changed');
        scheduleAutoSave();
    });
    
    // Enhanced keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S or Cmd+S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#save-settings-btn').click();
        }
        
        // Escape key to close modals
        if (e.key === 'Escape') {
            $('.mobooking-modal:visible').hide();
        }
    });
    
    // Enhanced URL copying with different formats
    $('.copy-url-btn').on('contextmenu', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        const formats = {
            'Plain URL': url,
            'Markdown Link': `[Book Now](${url})`,
            'HTML Link': `<a href="${url}">Book Now</a>`,
            'QR Code URL': `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`
        };
        
        // Show format selection menu (simplified)
        const format = prompt('Choose format:\n1. Plain URL\n2. Markdown Link\n3. HTML Link\n4. QR Code URL\n\nEnter number (1-4):');
        const formatKeys = Object.keys(formats);
        const selectedFormat = formatKeys[parseInt(format) - 1];
        
        if (selectedFormat && formats[selectedFormat]) {
            navigator.clipboard.writeText(formats[selectedFormat]);
            BookingFormManager.showNotification(`${selectedFormat} copied to clipboard!`, 'success');
        }
    });
    
    // Enhanced iframe loading handling
    $('#form-preview-iframe').on('load', function() {
        $('.preview-container').removeClass('loading');
    });
    
    $('#refresh-preview-btn').on('click', function() {
        $('.preview-container').addClass('loading');
    });
    
    // Advanced QR code options
    $('#generate-qr-btn').on('contextmenu', function(e) {
        e.preventDefault();
        const url = $('.url-display').val();
        const size = prompt('QR Code size (default 200x200):', '200x200');
        const color = prompt('Foreground color (hex, default black):', '000000');
        const bgcolor = prompt('Background color (hex, default white):', 'ffffff');
        
        if (size) {
            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${size}&color=${color}&bgcolor=${bgcolor}&data=${encodeURIComponent(url)}`;
            $('#qr-code-container').html(`<img src="${qrCodeUrl}" alt="QR Code" style="width: 100%; height: 100%; object-fit: contain;">`);
            $('#download-qr-btn').show().data('qr-url', qrCodeUrl);
        }
    });
    
    // Form analytics tracking (if analytics is enabled)
    if (typeof gtag !== 'undefined') {
        $('#save-settings-btn').on('click', function() {
            gtag('event', 'form_save', {
                'event_category': 'booking_form',
                'event_label': 'settings_save'
            });
        });
        
        $('.copy-url-btn').on('click', function() {
            gtag('event', 'url_copy', {
                'event_category': 'booking_form',
                'event_label': 'url_share'
            });
        });
    }
    
    // Accessibility improvements
    $('.tab-button').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
        }
    });
    
    // Enhanced error handling for network issues
    $(document).ajaxError(function(event, jqXHR, ajaxSettings) {
        if (ajaxSettings.url.includes('mobooking_save_booking_form_settings')) {
            console.error('AJAX Error:', jqXHR);
            
            if (jqXHR.status === 0) {
                BookingFormManager.showNotification('Network error. Please check your connection and try again.', 'error');
            } else if (jqXHR.status >= 500) {
                BookingFormManager.showNotification('Server error. Please try again or contact support.', 'error');
            }
        }
    });
    
    console.log('Enhanced booking form functionality loaded');
});
</script>