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
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
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
               <button type="button" id="save-draft-btn" class="btn-secondary">
                   <?php _e('Save as Draft', 'mobooking'); ?>
               </button>
               <button type="submit" id="save-settings-btn" class="btn-primary">
                   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                       <polyline points="17,21 17,13 7,13 7,21"/>
                       <polyline points="7,3 7,8 15,8"/>
                   </svg>
                   <span class="btn-text"><?php _e('Save Settings', 'mobooking'); ?></span>
                   <span class="btn-loading" style="display: none;"><?php _e('Saving...', 'mobooking'); ?></span>
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
/* Booking Form Section Styles */
.booking-form-section {
   animation: fadeIn 0.4s ease-out;
}

/* Section Header */
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

/* Setup Alert */
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

/* Form Container */
.form-container {
   background: hsl(var(--card));
   border: 1px solid hsl(var(--border));
   border-radius: calc(var(--radius) + 4px);
   overflow: hidden;
}

/* Form Tabs */
.form-tabs {
   border-bottom: 1px solid hsl(var(--border));
}

.tab-list {
   display: flex;
   padding: 0.5rem;
   background: hsl(var(--muted) / 0.3);
   gap: 0.25rem;
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

/* Form Content */
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

/* Settings Groups */
.settings-group {
   padding: 1.5rem;
   border: 1px solid hsl(var(--border));
   border-radius: var(--radius);
   background: hsl(var(--card));
}

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

/* Form Fields */
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

/* Color Fields */
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

/* Input with Action */
.input-with-action {
   display: flex;
   gap: 0.75rem;
   align-items: stretch;
}

.input-with-action .form-control {
   flex: 1;
}

/* Checkbox Options */
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

/* Share Options */
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

/* URL Input Group */
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

/* Embed Controls */
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

/* QR Section */
.qr-section {