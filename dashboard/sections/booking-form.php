<?php
/**
 * Enhanced Booking Form Settings Dashboard
 * Features: Analytics, Form Status, Popup Preview, Improved UI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user and settings
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$booking_form_manager = new \MoBooking\BookingForm\Manager();
$bookings_manager = new \MoBooking\Bookings\Manager();
$settings = $booking_form_manager->get_settings($user_id);

// Get analytics data
// $form_analytics = $booking_form_manager->get_form_analytics($user_id);
$form_stats = array(
    'total_views' => $form_analytics['total_views'] ?? 0,
    'total_submissions' => $form_analytics['total_submissions'] ?? 0,
    'conversion_rate' => $form_analytics['conversion_rate'] ?? 0,
    'avg_completion_time' => $form_analytics['avg_completion_time'] ?? 0
);

// Get recent bookings count
$recent_bookings = $bookings_manager->count_user_bookings($user_id, null, 7); // Last 7 days
$pending_bookings = $bookings_manager->count_user_bookings($user_id, 'pending');

// Check form requirements
$services_manager = new \MoBooking\Services\ServicesManager();
$geography_manager = new \MoBooking\Geography\Manager();
$services_count = count($services_manager->get_user_services($user_id));
$areas_count = count($geography_manager->get_user_areas($user_id));
$can_publish = ($services_count > 0 && $areas_count > 0);

// Form URLs
$booking_url = $booking_form_manager->get_booking_form_url($user_id);
$embed_url = $booking_form_manager->get_embed_url($user_id);
?>

<div class="booking-form-dashboard">
    <!-- Analytics Header -->
    <div class="analytics-header">
        <div class="analytics-overview">
            <div class="page-title-section">
                <div class="title-with-status">
                    <h1 class="page-title">
                        <svg class="title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <?php _e('Booking Form Settings', 'mobooking'); ?>
                    </h1>
                    <div class="form-status-badge <?php echo $settings->is_active ? 'status-active' : 'status-inactive'; ?>">
                        <span class="status-indicator"></span>
                        <?php echo $settings->is_active ? __('Live', 'mobooking') : __('Draft', 'mobooking'); ?>
                    </div>
                </div>
                <p class="page-subtitle"><?php _e('Customize your booking form and monitor its performance', 'mobooking'); ?></p>
            </div>

            <!-- Quick Stats Cards -->
            <div class="quick-stats-grid">
                <div class="stat-card views-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($form_stats['total_views']); ?></div>
                        <div class="stat-label"><?php _e('Form Views', 'mobooking'); ?></div>
                        <div class="stat-period"><?php _e('Last 30 days', 'mobooking'); ?></div>
                    </div>
                </div>

                <div class="stat-card conversions-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($form_stats['conversion_rate'], 1); ?>%</div>
                        <div class="stat-label"><?php _e('Conversion Rate', 'mobooking'); ?></div>
                        <div class="stat-period"><?php _e('Views to bookings', 'mobooking'); ?></div>
                    </div>
                </div>

                <div class="stat-card bookings-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 2v4M8 2v4m-5 4h18m-9 0v10"/>
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($recent_bookings); ?></div>
                        <div class="stat-label"><?php _e('New Bookings', 'mobooking'); ?></div>
                        <div class="stat-period"><?php _e('Last 7 days', 'mobooking'); ?></div>
                    </div>
                </div>

                <div class="stat-card pending-card <?php echo $pending_bookings > 0 ? 'has-alert' : ''; ?>">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($pending_bookings); ?></div>
                        <div class="stat-label"><?php _e('Pending Review', 'mobooking'); ?></div>
                        <?php if ($pending_bookings > 0): ?>
                            <div class="stat-alert">
                                <span class="alert-pulse"></span>
                                <?php _e('Needs attention', 'mobooking'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="header-actions">
            <button type="button" id="preview-form-btn" class="btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <?php _e('Preview Form', 'mobooking'); ?>
            </button>
            
            <button type="button" id="copy-form-url" class="btn-secondary" data-url="<?php echo esc_url($booking_url); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                <?php _e('Copy URL', 'mobooking'); ?>
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

    <!-- Requirements Check -->
    <?php if (!$can_publish): ?>
        <div class="requirements-notice">
            <div class="notice-content">
                <div class="notice-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div class="notice-text">
                    <h3><?php _e('Setup Required', 'mobooking'); ?></h3>
                    <p><?php _e('Complete these steps before publishing your form:', 'mobooking'); ?></p>
                    <ul class="requirements-list">
                        <?php if ($services_count === 0): ?>
                            <li class="requirement-item incomplete">
                                <span class="requirement-status">✗</span>
                                <span><?php _e('Add at least one service', 'mobooking'); ?></span>
                                <a href="?section=services" class="requirement-link"><?php _e('Add Services', 'mobooking'); ?></a>
                            </li>
                        <?php else: ?>
                            <li class="requirement-item complete">
                                <span class="requirement-status">✓</span>
                                <span><?php printf(__('%d services configured', 'mobooking'), $services_count); ?></span>
                            </li>
                        <?php endif; ?>

                        <?php if ($areas_count === 0): ?>
                            <li class="requirement-item incomplete">
                                <span class="requirement-status">✗</span>
                                <span><?php _e('Define service areas', 'mobooking'); ?></span>
                                <a href="?section=geography" class="requirement-link"><?php _e('Set Areas', 'mobooking'); ?></a>
                            </li>
                        <?php else: ?>
                            <li class="requirement-item complete">
                                <span class="requirement-status">✓</span>
                                <span><?php printf(__('%d service areas defined', 'mobooking'), $areas_count); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Settings Form -->
    <div class="settings-wrapper">
        <form id="booking-form-settings" method="post" class="booking-form-settings">
            <?php wp_nonce_field('mobooking-booking-form-nonce', 'booking_form_nonce'); ?>
            
            <div class="settings-grid">
                <!-- Left Column - Main Settings -->
                <div class="settings-main">
                    <div class="settings-tabs">
                        <button type="button" class="tab-button active" data-tab="general">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                            </svg>
                            <?php _e('General', 'mobooking'); ?>
                        </button>
                        <button type="button" class="tab-button" data-tab="design">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <palette />
                            </svg>
                            <?php _e('Design', 'mobooking'); ?>
                        </button>
                        <button type="button" class="tab-button" data-tab="advanced">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <settings />
                            </svg>
                            <?php _e('Advanced', 'mobooking'); ?>
                        </button>
                    </div>

                    <!-- General Tab -->
                    <div class="tab-content active" id="general-tab">
                        <div class="form-section">
                            <h3 class="section-title"><?php _e('Basic Information', 'mobooking'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="form-title" class="field-label required"><?php _e('Form Title', 'mobooking'); ?></label>
                                    <input type="text" id="form-title" name="form_title" class="form-control" 
                                           value="<?php echo esc_attr($settings->form_title); ?>" 
                                           placeholder="<?php _e('Book Our Services', 'mobooking'); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="form-description" class="field-label"><?php _e('Description', 'mobooking'); ?></label>
                                    <textarea id="form-description" name="form_description" class="form-control" rows="3"
                                              placeholder="<?php _e('Quick and easy booking for professional services...', 'mobooking'); ?>"><?php echo esc_textarea($settings->form_description); ?></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="language" class="field-label"><?php _e('Language', 'mobooking'); ?></label>
                                    <select id="language" name="language" class="form-control">
                                        <option value="en" <?php selected($settings->language ?? 'en', 'en'); ?>><?php _e('English', 'mobooking'); ?></option>
                                        <option value="es" <?php selected($settings->language ?? 'en', 'es'); ?>><?php _e('Spanish', 'mobooking'); ?></option>
                                        <option value="fr" <?php selected($settings->language ?? 'en', 'fr'); ?>><?php _e('French', 'mobooking'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="form-group half-width">
                                    <label class="field-label"><?php _e('Form Status', 'mobooking'); ?></label>
                                    <div class="form-status-toggle">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="is_active" value="1" <?php checked($settings->is_active, 1); ?> 
                                                   <?php echo !$can_publish ? 'disabled' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span class="toggle-label">
                                            <?php echo $settings->is_active ? __('Published', 'mobooking') : __('Draft', 'mobooking'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><?php _e('Form Features', 'mobooking'); ?></h3>
                            
                            <div class="features-grid">
                                <label class="feature-checkbox">
                                    <input type="checkbox" name="show_form_header" value="1" <?php checked($settings->show_form_header, 1); ?>>
                                    <span class="checkmark"></span>
                                    <div class="feature-info">
                                        <span class="feature-title"><?php _e('Show Header', 'mobooking'); ?></span>
                                        <span class="feature-desc"><?php _e('Display title and description', 'mobooking'); ?></span>
                                    </div>
                                </label>

                                <label class="feature-checkbox">
                                    <input type="checkbox" name="show_service_descriptions" value="1" <?php checked($settings->show_service_descriptions, 1); ?>>
                                    <span class="checkmark"></span>
                                    <div class="feature-info">
                                        <span class="feature-title"><?php _e('Service Descriptions', 'mobooking'); ?></span>
                                        <span class="feature-desc"><?php _e('Show detailed service info', 'mobooking'); ?></span>
                                    </div>
                                </label>

                                <label class="feature-checkbox">
                                    <input type="checkbox" name="show_price_breakdown" value="1" <?php checked($settings->show_price_breakdown, 1); ?>>
                                    <span class="checkmark"></span>
                                    <div class="feature-info">
                                        <span class="feature-title"><?php _e('Price Breakdown', 'mobooking'); ?></span>
                                        <span class="feature-desc"><?php _e('Display pricing details', 'mobooking'); ?></span>
                                    </div>
                                </label>

                                <label class="feature-checkbox">
                                    <input type="checkbox" name="enable_zip_validation" value="1" <?php checked($settings->enable_zip_validation, 1); ?>>
                                    <span class="checkmark"></span>
                                    <div class="feature-info">
                                        <span class="feature-title"><?php _e('ZIP Validation', 'mobooking'); ?></span>
                                        <span class="feature-desc"><?php _e('Validate service areas', 'mobooking'); ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Design Tab -->
                    <div class="tab-content" id="design-tab">
                        <div class="form-section">
                            <h3 class="section-title"><?php _e('Colors & Branding', 'mobooking'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="primary-color" class="field-label"><?php _e('Primary Color', 'mobooking'); ?></label>
                                    <div class="color-input-group">
                                        <input type="color" id="primary-color" name="primary_color" 
                                               value="<?php echo esc_attr($settings->primary_color ?? '#3b82f6'); ?>" class="color-picker">
                                        <input type="text" class="color-text" 
                                               value="<?php echo esc_attr($settings->primary_color ?? '#3b82f6'); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="form-group half-width">
                                    <label for="secondary-color" class="field-label"><?php _e('Secondary Color', 'mobooking'); ?></label>
                                    <div class="color-input-group">
                                        <input type="color" id="secondary-color" name="secondary_color" 
                                               value="<?php echo esc_attr($settings->secondary_color ?? '#1e40af'); ?>" class="color-picker">
                                        <input type="text" class="color-text" 
                                               value="<?php echo esc_attr($settings->secondary_color ?? '#1e40af'); ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="logo-url" class="field-label"><?php _e('Logo URL', 'mobooking'); ?></label>
                                    <div class="url-input-group">
                                        <input type="url" id="logo-url" name="logo_url" class="form-control" 
                                               value="<?php echo esc_url($settings->logo_url ?? ''); ?>" 
                                               placeholder="https://example.com/logo.png">
                                        <button type="button" class="btn-secondary upload-logo">
                                            <?php _e('Upload', 'mobooking'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><?php _e('Layout & Style', 'mobooking'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group half-width">
                                    <label for="form-layout" class="field-label"><?php _e('Layout Style', 'mobooking'); ?></label>
                                    <select id="form-layout" name="form_layout" class="form-control">
                                        <option value="modern" <?php selected($settings->form_layout ?? 'modern', 'modern'); ?>><?php _e('Modern', 'mobooking'); ?></option>
                                        <option value="classic" <?php selected($settings->form_layout ?? 'modern', 'classic'); ?>><?php _e('Classic', 'mobooking'); ?></option>
                                        <option value="minimal" <?php selected($settings->form_layout ?? 'modern', 'minimal'); ?>><?php _e('Minimal', 'mobooking'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="form-group half-width">
                                    <label for="form-width" class="field-label"><?php _e('Form Width', 'mobooking'); ?></label>
                                    <select id="form-width" name="form_width" class="form-control">
                                        <option value="narrow" <?php selected($settings->form_width ?? 'standard', 'narrow'); ?>><?php _e('Narrow', 'mobooking'); ?></option>
                                        <option value="standard" <?php selected($settings->form_width ?? 'standard', 'standard'); ?>><?php _e('Standard', 'mobooking'); ?></option>
                                        <option value="wide" <?php selected($settings->form_width ?? 'standard', 'wide'); ?>><?php _e('Wide', 'mobooking'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Tab -->
                    <div class="tab-content" id="advanced-tab">
                        <div class="form-section">
                            <h3 class="section-title"><?php _e('SEO & Analytics', 'mobooking'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="seo-title" class="field-label"><?php _e('SEO Title', 'mobooking'); ?></label>
                                    <input type="text" id="seo-title" name="seo_title" class="form-control" 
                                           value="<?php echo esc_attr($settings->seo_title ?? ''); ?>" 
                                           placeholder="<?php _e('Book Professional Services Online', 'mobooking'); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="seo-description" class="field-label"><?php _e('SEO Description', 'mobooking'); ?></label>
                                    <textarea id="seo-description" name="seo_description" class="form-control" rows="3"
                                              placeholder="<?php _e('Professional service booking made easy...', 'mobooking'); ?>"><?php echo esc_textarea($settings->seo_description ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="analytics-code" class="field-label"><?php _e('Analytics Code', 'mobooking'); ?></label>
                                    <textarea id="analytics-code" name="analytics_code" class="form-control code-input" rows="6" 
                                              placeholder="<!-- Google Analytics, Facebook Pixel, etc. -->"><?php echo esc_textarea($settings->analytics_code ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><?php _e('Custom Code', 'mobooking'); ?></h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="custom-css" class="field-label"><?php _e('Custom CSS', 'mobooking'); ?></label>
                                    <textarea id="custom-css" name="custom_css" class="form-control code-input" rows="8" 
                                              placeholder="/* Custom styles */
.mobooking-form {
    /* Your CSS here */
}"><?php echo esc_textarea($settings->custom_css ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Preview & Info -->
                <div class="settings-sidebar">
                    <div class="preview-card">
                        <div class="preview-header">
                            <h4><?php _e('Form URLs', 'mobooking'); ?></h4>
                        </div>
                        <div class="preview-content">
                            <div class="url-item">
                                <label><?php _e('Booking Page:', 'mobooking'); ?></label>
                                <div class="url-field">
                                    <input type="text" readonly value="<?php echo esc_url($booking_url); ?>" class="url-input">
                                    <button type="button" class="copy-btn" data-copy="<?php echo esc_url($booking_url); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="url-item">
                                <label><?php _e('Embed Code:', 'mobooking'); ?></label>
                                <div class="url-field">
                                    <textarea readonly class="embed-code">&lt;iframe src="<?php echo esc_url($embed_url); ?>" width="100%" height="600" frameborder="0"&gt;&lt;/iframe&gt;</textarea>
                                    <button type="button" class="copy-btn" data-copy='&lt;iframe src="<?php echo esc_url($embed_url); ?>" width="100%" height="600" frameborder="0"&gt;&lt;/iframe&gt;'>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="help-card">
                        <div class="help-header">
                            <h4><?php _e('Quick Tips', 'mobooking'); ?></h4>
                        </div>
                        <div class="help-content">
                            <div class="tip-item">
                                <div class="tip-icon">💡</div>
                                <div class="tip-text">
                                    <strong><?php _e('Boost Conversions', 'mobooking'); ?></strong>
                                    <p><?php _e('Keep your form description clear and mention key benefits', 'mobooking'); ?></p>
                                </div>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon">🎨</div>
                                <div class="tip-text">
                                    <strong><?php _e('Brand Consistency', 'mobooking'); ?></strong>
                                    <p><?php _e('Use colors that match your website for a professional look', 'mobooking'); ?></p>
                                </div>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon">📱</div>
                                <div class="tip-text">
                                    <strong><?php _e('Mobile Friendly', 'mobooking'); ?></strong>
                                    <p><?php _e('All layouts are responsive and work great on mobile devices', 'mobooking'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form Preview Modal -->
<div id="form-preview-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3><?php _e('Form Preview', 'mobooking'); ?></h3>
            <button type="button" class="modal-close" id="close-preview">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="preview-tabs">
                <button type="button" class="preview-tab active" data-device="desktop">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    <?php _e('Desktop', 'mobooking'); ?>
                </button>
                <button type="button" class="preview-tab" data-device="tablet">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                    <?php _e('Tablet', 'mobooking'); ?>
                </button>
                <button type="button" class="preview-tab" data-device="mobile">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                        <line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                    <?php _e('Mobile', 'mobooking'); ?>
                </button>
            </div>
            <div class="preview-frame-container">
                <div class="preview-frame desktop active">
                    <iframe id="preview-iframe-desktop" src="about:blank" frameborder="0"></iframe>
                </div>
                <div class="preview-frame tablet">
                    <iframe id="preview-iframe-tablet" src="about:blank" frameborder="0"></iframe>
                </div>
                <div class="preview-frame mobile">
                    <iframe id="preview-iframe-mobile" src="about:blank" frameborder="0"></iframe>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="close-preview-footer">
                <?php _e('Close', 'mobooking'); ?>
            </button>
            <a href="<?php echo esc_url($booking_url); ?>" target="_blank" class="btn-primary">
                <?php _e('Open Full Page', 'mobooking'); ?>
            </a>
        </div>
    </div>
</div>

<style>
/* Enhanced Booking Form Dashboard Styles */
.booking-form-dashboard {
    background: #f8fafc;
    margin: -20px -20px 0 -20px;
    padding: 0;
    min-height: 100vh;
}

/* Analytics Header */
.analytics-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    margin-bottom: 2rem;
}

.analytics-overview {
    max-width: 1200px;
    margin: 0 auto;
}

.title-with-status {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: white;
}

.title-icon {
    width: 32px;
    height: 32px;
    opacity: 0.9;
}

.form-status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-status-badge.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #dcfce7;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.form-status-badge.status-inactive {
    background: rgba(156, 163, 175, 0.2);
    color: #f3f4f6;
    border: 1px solid rgba(156, 163, 175, 0.3);
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse 2s infinite;
}

.page-subtitle {
    margin: 0;
    font-size: 1.125rem;
    opacity: 0.9;
    font-weight: 400;
}

/* Quick Stats Grid */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.15);
}

.stat-card .stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 0.75rem;
    margin-bottom: 1rem;
}

.stat-card .stat-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-weight: 600;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.stat-card .stat-period {
    font-size: 0.875rem;
    opacity: 0.7;
}

.stat-card.pending-card.has-alert .stat-alert {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.2);
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.alert-pulse {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ef4444;
    animation: pulse 1.5s infinite;
}

/* Header Actions */
.header-actions {
    position: absolute;
    top: 2rem;
    right: 2rem;
    display: flex;
    gap: 1rem;
}

.analytics-header {
    position: relative;
}

/* Requirements Notice */
.requirements-notice {
    background: #fef3c7;
    border: 1px solid #fbbf24;
    border-radius: 0.75rem;
    padding: 1.5rem;
    margin: 0 2rem 2rem;
    color: #92400e;
}

.notice-content {
    display: flex;
    gap: 1rem;
}

.notice-icon {
    flex-shrink: 0;
    color: #f59e0b;
}

.notice-text h3 {
    margin: 0 0 0.5rem;
    color: #92400e;
    font-size: 1.125rem;
    font-weight: 600;
}

.requirements-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.requirement-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.requirement-status {
    font-weight: 700;
    width: 20px;
    text-align: center;
}

.requirement-item.complete .requirement-status {
    color: #059669;
}

.requirement-item.incomplete .requirement-status {
    color: #dc2626;
}

.requirement-link {
    margin-left: auto;
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Settings Wrapper */
.settings-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem 2rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
}

/* Settings Main */
.settings-main {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.settings-tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.tab-button {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
}

.tab-button:hover {
    color: #374151;
    background: #f3f4f6;
}

.tab-button.active {
    color: #2563eb;
    background: white;
    border-bottom-color: #2563eb;
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

/* Form Sections */
.form-section {
    margin-bottom: 2rem;
}

.form-section:last-child {
    margin-bottom: 0;
}

.section-title {
    margin: 0 0 1rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    flex: 1;
}

.form-group.half-width {
    flex: 0 0 calc(50% - 0.5rem);
}

.field-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.field-label.required::after {
    content: "*";
    color: #ef4444;
    margin-left: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.code-input {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.8125rem;
    background: #f8fafc;
}

/* Color Input */
.color-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.color-picker {
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
}

.color-text {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: #f9fafb;
    font-family: monospace;
}

/* URL Input */
.url-input-group {
    display: flex;
    gap: 0.5rem;
}

.url-input-group .form-control {
    flex: 1;
}

.upload-logo {
    flex-shrink: 0;
}

/* Toggle Switch */
.form-status-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #d1d5db;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #2563eb;
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.toggle-label {
    font-weight: 500;
    color: #374151;
}

/* Features Grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.feature-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.feature-checkbox:hover {
    border-color: #2563eb;
    background: #f8fafc;
}

.feature-checkbox input {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 0.25rem;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.feature-checkbox input:checked + .checkmark {
    background: #2563eb;
    border-color: #2563eb;
}

.feature-checkbox input:checked + .checkmark::after {
    content: "";
    position: absolute;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.feature-info {
    flex: 1;
}

.feature-title {
    display: block;
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.25rem;
}

.feature-desc {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Sidebar */
.settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.preview-card,
.help-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.preview-header,
.help-header {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.preview-header h4,
.help-header h4 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}

.preview-content,
.help-content {
    padding: 1.5rem;
}

.url-item {
    margin-bottom: 1.5rem;
}

.url-item:last-child {
    margin-bottom: 0;
}

.url-item label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
    font-size: 0.875rem;
}

.url-field {
    display: flex;
    gap: 0.5rem;
}

.url-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #f9fafb;
    font-size: 0.875rem;
    color: #6b7280;
}

.embed-code {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #f9fafb;
    font-size: 0.75rem;
    color: #6b7280;
    height: 60px;
    resize: none;
    font-family: monospace;
}

.copy-btn {
    padding: 0.5rem;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.3s ease;
}

.copy-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

/* Help Tips */
.tip-item {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.tip-item:last-child {
    margin-bottom: 0;
}

.tip-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
}

.tip-text strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #111827;
    font-weight: 600;
}

.tip-text p {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

/* Buttons */
.btn-primary,
.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
    font-size: 0.875rem;
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.btn-secondary {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    backdrop-filter: blur(4px);
}

.modal-container {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 90vw;
    max-height: 90vh;
    width: 1000px;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
}

.modal-close {
    padding: 0.5rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    border-radius: 0.375rem;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    flex: 1;
    padding: 1.5rem;
    overflow: hidden;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

/* Preview Tabs */
.preview-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.preview-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.preview-tab:hover {
    color: #374151;
}

.preview-tab.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

/* Preview Frames */
.preview-frame-container {
    position: relative;
    height: 500px;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}

.preview-frame {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
}

.preview-frame.active {
    display: block;
}

.preview-frame.desktop iframe {
    width: 100%;
    height: 100%;
}

.preview-frame.tablet {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 1rem;
    background: #f3f4f6;
}

.preview-frame.tablet iframe {
    width: 600px;
    height: 450px;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.preview-frame.mobile {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 1rem;
    background: #f3f4f6;
}

.preview-frame.mobile iframe {
    width: 375px;
    height: 450px;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Animations */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

/* Responsive Design */
@media (max-width: 1024px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .settings-sidebar {
        order: -1;
    }
    
    .header-actions {
        position: static;
        margin-top: 1rem;
    }
    
    .title-with-status {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

@media (max-width: 768px) {
    .analytics-header {
        padding: 1.5rem;
    }
    
    .settings-wrapper {
        padding: 0 1.5rem 1.5rem;
    }
    
    .quick-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .form-group.half-width {
        flex: 1;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-container {
        width: 95vw;
        height: 90vh;
    }
}

@media (max-width: 480px) {
    .quick-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .settings-tabs {
        flex-direction: column;
    }
    
    .tab-content {
        padding: 1.5rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-button').on('click', function() {
        const tabId = $(this).data('tab');
        
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#' + tabId + '-tab').addClass('active');
    });
    
    // Color picker synchronization
    $('.color-picker').on('input', function() {
        $(this).siblings('.color-text').val($(this).val());
    });
    
    // Copy functionality
    $('.copy-btn').on('click', function() {
        const textToCopy = $(this).data('copy');
        navigator.clipboard.writeText(textToCopy).then(function() {
            // Show success feedback
            const $btn = $(this);
            const originalText = $btn.html();
            $btn.html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>');
            setTimeout(function() {
                $btn.html(originalText);
            }, 2000);
        }.bind(this));
    });
    
    // Form preview modal
    $('#preview-form-btn').on('click', function() {
        const formUrl = '<?php echo esc_js($booking_url);