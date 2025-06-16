<?php
// dashboard/sections/booking-form.php - Enhanced Booking Form Management
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize booking form manager
$booking_form_manager = new \MoBooking\BookingForm\BookingFormManager();
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

// Get metrics (you can implement these functions in your Manager classes)
$total_bookings = 247; // Replace with actual data: $booking_form_manager->get_total_bookings($user_id);
$form_views = 1432; // Replace with actual data: $booking_form_manager->get_form_views($user_id);
$conversion_rate = 17.2; // Replace with actual data: $booking_form_manager->get_conversion_rate($user_id);
$monthly_change = 12; // Replace with actual data

// Calculate setup progress
$setup_steps = [
    'services' => $services_count > 0,
    'areas' => $areas_count > 0,
    'design' => !empty($settings->primary_color) && !empty($settings->form_title),
    'seo' => !empty($settings->seo_title) && !empty($settings->seo_description)
];
$completed_steps = count(array_filter($setup_steps));
$total_steps = count($setup_steps);
$progress_percentage = round(($completed_steps / $total_steps) * 100);
?>

<style>
/* Enhanced CSS Variables & Base Styles */
:root {
    --mo-primary: #3b82f6;
    --mo-primary-dark: #1e40af;
    --mo-secondary: #f1f5f9;
    --mo-success: #10b981;
    --mo-warning: #f59e0b;
    --mo-danger: #ef4444;
    --mo-info: #06b6d4;
    --mo-border: #e2e8f0;
    --mo-text: #1e293b;
    --mo-text-muted: #64748b;
    --mo-background: #ffffff;
    --mo-card-bg: #ffffff;
    --mo-hover-bg: #f8fafc;
    --mo-radius: 8px;
    --mo-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    --mo-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    --mo-transition: all 0.2s ease;
}

.mobooking-dashboard * {
    box-sizing: border-box;
}

/* Dashboard Header with Quick Actions */
.mobooking-dashboard .dashboard-header {
    background: var(--mo-card-bg);
    border-radius: var(--mo-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--mo-shadow);
    border: 1px solid var(--mo-border);
}

.mobooking-dashboard .header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    margin-bottom: 2rem;
}

.mobooking-dashboard .header-title-section {
    flex: 1;
}

.mobooking-dashboard .header-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--mo-text);
}

.mobooking-dashboard .title-icon {
    width: 2rem;
    height: 2rem;
    color: var(--mo-primary);
}

.mobooking-dashboard .header-subtitle {
    margin: 0;
    color: var(--mo-text-muted);
    font-size: 1.1rem;
}

.mobooking-dashboard .header-actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.mobooking-dashboard .btn-open-form {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, var(--mo-primary), var(--mo-primary-dark));
    color: white;
    border: none;
    border-radius: var(--mo-radius);
    font-weight: 600;
    text-decoration: none;
    transition: var(--mo-transition);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    font-size: 1rem;
    cursor: pointer;
}

.mobooking-dashboard .btn-open-form:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.4);
    color: white;
    text-decoration: none;
}

.mobooking-dashboard .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.25rem;
    background: var(--mo-secondary);
    color: var(--mo-text);
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    font-weight: 500;
    text-decoration: none;
    transition: var(--mo-transition);
    cursor: pointer;
}

.mobooking-dashboard .btn-secondary:hover {
    background: var(--mo-hover-bg);
    border-color: var(--mo-primary);
    color: var(--mo-text);
    text-decoration: none;
}

/* Setup Progress */
.mobooking-dashboard .setup-progress {
    background: var(--mo-card-bg);
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--mo-shadow);
}

.mobooking-dashboard .progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.mobooking-dashboard .progress-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--mo-text);
}

.mobooking-dashboard .progress-percentage {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--mo-primary);
}

.mobooking-dashboard .progress-bar-container {
    background: var(--mo-secondary);
    border-radius: 999px;
    height: 8px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.mobooking-dashboard .progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--mo-primary), var(--mo-primary-dark));
    border-radius: 999px;
    transition: width 0.5s ease;
}

.mobooking-dashboard .setup-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.mobooking-dashboard .setup-step {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    background: var(--mo-background);
    transition: var(--mo-transition);
}

.mobooking-dashboard .setup-step:hover {
    background: var(--mo-hover-bg);
}

.mobooking-dashboard .setup-step.completed {
    background: rgba(16, 185, 129, 0.05);
    border-color: var(--mo-success);
}

.mobooking-dashboard .setup-step.incomplete {
    background: rgba(245, 158, 11, 0.05);
    border-color: var(--mo-warning);
}

.mobooking-dashboard .step-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.mobooking-dashboard .step-icon.completed {
    background: var(--mo-success);
}

.mobooking-dashboard .step-icon.incomplete {
    background: var(--mo-warning);
}

.mobooking-dashboard .step-content {
    flex: 1;
}

.mobooking-dashboard .step-title {
    margin: 0 0 0.25rem 0;
    font-weight: 600;
    color: var(--mo-text);
}

.mobooking-dashboard .step-description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--mo-text-muted);
}

/* Settings Container */
.mobooking-dashboard .settings-container {
    background: var(--mo-card-bg);
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    box-shadow: var(--mo-shadow);
    overflow: hidden;
}

/* Enhanced Tabs */
.mobooking-dashboard .settings-tabs {
    display: flex;
    background: var(--mo-secondary);
    border-bottom: 1px solid var(--mo-border);
    overflow-x: auto;
}

.mobooking-dashboard .tab-button {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border: none;
    background: transparent;
    color: var(--mo-text-muted);
    font-weight: 500;
    cursor: pointer;
    transition: var(--mo-transition);
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    min-width: fit-content;
}

.mobooking-dashboard .tab-button:hover {
    background: var(--mo-hover-bg);
    color: var(--mo-text);
}

.mobooking-dashboard .tab-button.active {
    background: var(--mo-card-bg);
    color: var(--mo-primary);
    border-bottom-color: var(--mo-primary);
}

.mobooking-dashboard .tab-icon {
    width: 1.25rem;
    height: 1.25rem;
}

/* Form Content */
.mobooking-dashboard .form-content {
    padding: 2rem;
}

.mobooking-dashboard .tab-content {
    display: none;
}

.mobooking-dashboard .tab-content.active {
    display: block;
    animation: moFadeIn 0.3s ease;
}

@keyframes moFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.mobooking-dashboard .content-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Settings Groups */
.mobooking-dashboard .settings-group {
    background: var(--mo-background);
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    padding: 1.5rem;
    box-shadow: var(--mo-shadow);
}

.mobooking-dashboard .group-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--mo-border);
}

.mobooking-dashboard .group-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--mo-text);
}

.mobooking-dashboard .group-description {
    margin: 0;
    color: var(--mo-text-muted);
}

/* Form Fields */
.mobooking-dashboard .form-fields {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.mobooking-dashboard .field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.mobooking-dashboard .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mobooking-dashboard .field-label {
    font-weight: 600;
    color: var(--mo-text);
    font-size: 0.875rem;
}

.mobooking-dashboard .field-label.required::after {
    content: " *";
    color: var(--mo-danger);
}

.mobooking-dashboard .form-control {
    padding: 0.75rem 1rem;
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    font-size: 0.875rem;
    background: var(--mo-background);
    color: var(--mo-text);
    transition: var(--mo-transition);
    width: 100%;
}

.mobooking-dashboard .form-control:focus {
    outline: none;
    border-color: var(--mo-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.mobooking-dashboard .field-note {
    font-size: 0.75rem;
    color: var(--mo-text-muted);
}

/* Color Picker */
.mobooking-dashboard .color-input-group {
    display: flex;
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    overflow: hidden;
    background: var(--mo-background);
}

.mobooking-dashboard .color-picker {
    width: 3rem;
    height: 2.5rem;
    border: none;
    cursor: pointer;
}

.mobooking-dashboard .color-text {
    flex: 1;
    border: none;
    padding: 0.5rem;
    font-family: monospace;
    background: transparent;
}

/* Checkbox Options */
.mobooking-dashboard .checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.mobooking-dashboard .checkbox-option {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--mo-border);
    border-radius: var(--mo-radius);
    background: var(--mo-background);
    cursor: pointer;
    transition: var(--mo-transition);
}

.mobooking-dashboard .checkbox-option:hover {
    background: var(--mo-hover-bg);
    border-color: var(--mo-primary);
}

.mobooking-dashboard .checkbox-option input[type="checkbox"] {
    margin: 0;
    width: 1.25rem;
    height: 1.25rem;
    accent-color: var(--mo-primary);
}

.mobooking-dashboard .checkbox-content {
    flex: 1;
}

.mobooking-dashboard .checkbox-title {
    font-weight: 600;
    color: var(--mo-text);
    margin-bottom: 0.25rem;
}

.mobooking-dashboard .checkbox-desc {
    font-size: 0.875rem;
    color: var(--mo-text-muted);
}

/* Action Buttons */
.mobooking-dashboard .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: var(--mo-secondary);
    border-top: 1px solid var(--mo-border);
}

.mobooking-dashboard .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: var(--mo-primary);
    color: white;
    border: none;
    border-radius: var(--mo-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--mo-transition);
    text-decoration: none;
}

.mobooking-dashboard .btn-primary:hover {
    background: var(--mo-primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

/* Notification */
.mobooking-dashboard .notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: var(--mo-radius);
    box-shadow: var(--mo-shadow-lg);
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
    animation: moSlideIn 0.3s ease;
}

.mobooking-dashboard .notification.success {
    background: var(--mo-success);
    color: white;
}

.mobooking-dashboard .notification.error {
    background: var(--mo-danger);
    color: white;
}

.mobooking-dashboard .notification-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.25rem;
    cursor: pointer;
    opacity: 0.8;
}

.mobooking-dashboard .notification-close:hover {
    opacity: 1;
}

@keyframes moSlideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .mobooking-dashboard .header-top {
        flex-direction: column;
        gap: 1rem;
    }

    .mobooking-dashboard .header-actions {
        width: 100%;
        justify-content: stretch;
    }

    .mobooking-dashboard .header-actions a,
    .mobooking-dashboard .header-actions button {
        flex: 1;
        justify-content: center;
    }

    .mobooking-dashboard .metrics-grid {
        grid-template-columns: 1fr;
    }

    .mobooking-dashboard .setup-steps {
        grid-template-columns: 1fr;
    }

    .mobooking-dashboard .settings-tabs {
        flex-direction: column;
    }

    .mobooking-dashboard .tab-button {
        justify-content: flex-start;
        border-bottom: none;
        border-left: 3px solid transparent;
    }

    .mobooking-dashboard .tab-button.active {
        border-bottom: none;
        border-left-color: var(--mo-primary);
    }

    .mobooking-dashboard .field-row {
        grid-template-columns: 1fr;
    }

    .mobooking-dashboard .form-actions {
        flex-direction: column;
    }
}

/* Loading States */
.mobooking-dashboard .loading {
    opacity: 0.6;
    pointer-events: none;
}

.mobooking-dashboard .spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: moSpin 1s linear infinite;
}

@keyframes moSpin {
    to { transform: rotate(360deg); }
}
</style>

<div class="mobooking-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-top">
            <div class="header-title-section">
                <h1 class="header-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                    </svg>
                    <?php _e('Booking Form Dashboard', 'mobooking'); ?>
                </h1>
                <p class="header-subtitle"><?php _e('Create and customize your customer booking experience', 'mobooking'); ?></p>
            </div>

            <div class="header-actions">
                <a href="<?php echo esc_url($booking_url); ?>" target="_blank" class="btn-open-form" id="open-form-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15,3 21,3 21,9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    <?php _e('Open Form', 'mobooking'); ?>
                </a>
                <button class="btn-secondary" id="preview-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <?php _e('Preview', 'mobooking'); ?>
                </button>
            </div>
        </div>

    </div>

    <!-- Setup Progress -->
    <div class="setup-progress">
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
        </div>

        <div class="setup-steps">
            <div class="setup-step <?php echo $setup_steps['services'] ? 'completed' : 'incomplete'; ?>">
                <div class="step-icon <?php echo $setup_steps['services'] ? 'completed' : 'incomplete'; ?>">
                    <?php if ($setup_steps['services']): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="step-content">
                    <h4 class="step-title"><?php _e('Services Added', 'mobooking'); ?></h4>
                    <p class="step-description">
                        <?php
                        if ($setup_steps['services']) {
                            printf(__('%d services configured and active', 'mobooking'), $services_count);
                        } else {
                            _e('Add your first service to get started', 'mobooking');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="setup-step <?php echo $setup_steps['areas'] ? 'completed' : 'incomplete'; ?>">
                <div class="step-icon <?php echo $setup_steps['areas'] ? 'completed' : 'incomplete'; ?>">
                    <?php if ($setup_steps['areas']): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="step-content">
                    <h4 class="step-title"><?php _e('Service Areas', 'mobooking'); ?></h4>
                    <p class="step-description">
                        <?php
                        if ($setup_steps['areas']) {
                            printf(__('%d geographic areas defined', 'mobooking'), $areas_count);
                        } else {
                            _e('Define where you provide services', 'mobooking');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="setup-step <?php echo $setup_steps['design'] ? 'completed' : 'incomplete'; ?>">
                <div class="step-icon <?php echo $setup_steps['design'] ? 'completed' : 'incomplete'; ?>">
                    <?php if ($setup_steps['design']): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="step-content">
                    <h4 class="step-title"><?php _e('Form Design', 'mobooking'); ?></h4>
                    <p class="step-description">
                        <?php
                        if ($setup_steps['design']) {
                            _e('Branding and colors customized', 'mobooking');
                        } else {
                            _e('Customize your form appearance', 'mobooking');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="setup-step <?php echo $setup_steps['seo'] ? 'completed' : 'incomplete'; ?>">
                <div class="step-icon <?php echo $setup_steps['seo'] ? 'completed' : 'incomplete'; ?>">
                    <?php if ($setup_steps['seo']): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"/>
                        </svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="step-content">
                    <h4 class="step-title"><?php _e('SEO Optimization', 'mobooking'); ?></h4>
                    <p class="step-description">
                        <?php
                        if ($setup_steps['seo']) {
                            _e('SEO title and description added', 'mobooking');
                        } else {
                            _e('Add meta description and title', 'mobooking');
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Container -->
    <div class="settings-container">
        <div class="settings-tabs">
            <button type="button" class="tab-button active" data-tab="general">
                <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
                </svg>
                <?php _e('General', 'mobooking'); ?>
            </button>
            <button type="button" class="tab-button" data-tab="design">
                <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                <?php _e('Design', 'mobooking'); ?>
            </button>
            <button type="button" class="tab-button" data-tab="advanced">
                <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <?php _e('Advanced', 'mobooking'); ?>
            </button>
            <button type="button" class="tab-button" data-tab="share">
                <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                    <polyline points="16,6 12,2 8,6"/>
                    <line x1="12" y1="2" x2="12" y2="15"/>
                </svg>
                <?php _e('Share & Embed', 'mobooking'); ?>
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
                                <h3 class="group-title"><?php _e('Basic Information', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Configure the basic details of your booking form', 'mobooking'); ?></p>
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
                                <h3 class="group-title"><?php _e('Form Features', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Enable or disable specific features in your booking form', 'mobooking'); ?></p>
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
                                <h3 class="group-title"><?php _e('Branding & Colors', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Customize the visual appearance of your booking form', 'mobooking'); ?></p>
                            </div>

                            <div class="form-fields">
                                <div class="field-row">
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
                                </div>

                                <div class="field-row">
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
                                <h3 class="group-title"><?php _e('Layout & Style', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Adjust the layout and presentation of your form', 'mobooking'); ?></p>
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
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div class="tab-content" data-tab="advanced">
                    <div class="content-grid">
                        <!-- SEO Settings -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3 class="group-title"><?php _e('SEO Optimization', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Optimize your booking form for search engines', 'mobooking'); ?></p>
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

                        <!-- Custom Code -->
                        <div class="settings-group">
                            <div class="group-header">
                                <h3 class="group-title"><?php _e('Custom Code', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Add custom CSS and JavaScript to enhance your form', 'mobooking'); ?></p>
                            </div>

                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="custom-css" class="field-label"><?php _e('Custom CSS', 'mobooking'); ?></label>
                                    <textarea id="custom-css" name="custom_css" class="form-control" rows="8"
                                              style="font-family: monospace;"
                                              placeholder="<?php _e('/* Custom CSS styles */\n.booking-form {\n    /* Your styles here */\n}', 'mobooking'); ?>"><?php echo esc_textarea($settings->custom_css); ?></textarea>
                                    <small class="field-note"><?php _e('Custom CSS will override default styles.', 'mobooking'); ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="analytics-code" class="field-label"><?php _e('Analytics Code', 'mobooking'); ?></label>
                                    <textarea id="analytics-code" name="analytics_code" class="form-control" rows="6"
                                              style="font-family: monospace;"
                                              placeholder="<?php _e('<!-- Google Analytics, Facebook Pixel, or other tracking codes -->', 'mobooking'); ?>"><?php echo esc_textarea($settings->analytics_code); ?></textarea>
                                    <small class="field-note"><?php _e('Add Google Analytics, Facebook Pixel, or other tracking codes', 'mobooking'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Share & Embed Tab -->
                <div class="tab-content" data-tab="share">
                    <div class="content-grid">
                        <div class="settings-group">
                            <div class="group-header">
                                <h3 class="group-title"><?php _e('Share Your Form', 'mobooking'); ?></h3>
                                <p class="group-description"><?php _e('Get your booking form URL and embed code', 'mobooking'); ?></p>
                            </div>

                            <div class="form-fields">
                                <div class="form-group">
                                    <label for="booking-url" class="field-label"><?php _e('Direct Link', 'mobooking'); ?></label>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <input type="text" id="booking-url" class="form-control" readonly
                                               value="<?php echo esc_url($booking_url); ?>" style="flex: 1;">
                                        <button type="button" class="btn-secondary" onclick="copyToClipboard('#booking-url')">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                            </svg>
                                            <?php _e('Copy', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    <small class="field-note"><?php _e('Share this URL to let customers access your booking form directly', 'mobooking'); ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="embed-code" class="field-label"><?php _e('Embed Code', 'mobooking'); ?></label>
                                    <textarea id="embed-code" class="form-control" rows="4" readonly
                                              style="font-family: monospace;"><?php echo esc_textarea('<iframe src="' . esc_url($embed_url) . '" width="100%" height="800" frameborder="0"></iframe>'); ?></textarea>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                        <button type="button" class="btn-secondary" onclick="copyToClipboard('#embed-code')">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                            </svg>
                                            <?php _e('Copy Code', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    <small class="field-note"><?php _e('Embed the booking form directly into your website', 'mobooking'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" id="reset-settings-btn" class="btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                    <?php _e('Reset to Defaults', 'mobooking'); ?>
                </button>
                <button type="submit" id="save-settings-btn" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17,21 17,13 7,13 7,21"/>
                        <polyline points="7,3 7,8 15,8"/>
                    </svg>
                    <?php _e('Save Settings', 'mobooking'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('MoBooking Dashboard JavaScript loaded');

    // Enhanced booking form manager
    const MoBookingDashboard = {

        init: function() {
            this.initTabs();
            this.initColorPickers();
            this.initFormSaving();
            this.initResetSettings();
            this.initPreview();
            this.updateProgress();
        },

        // Tab functionality
        initTabs: function() {
            $('.mobooking-dashboard .tab-button').on('click', function() {
                const tabId = $(this).data('tab');

                // Update active tab button
                $('.mobooking-dashboard .tab-button').removeClass('active');
                $(this).addClass('active');

                // Update active tab content
                $('.mobooking-dashboard .tab-content').removeClass('active');
                $(`.mobooking-dashboard .tab-content[data-tab="${tabId}"]`).addClass('active');
            });
        },

        // Color picker functionality
        initColorPickers: function() {
            $('.mobooking-dashboard .color-picker').on('input change', function() {
                const $colorPicker = $(this);
                const $textInput = $colorPicker.siblings('.color-text');
                $textInput.val($colorPicker.val());
            });
        },

        // Form saving with enhanced validation
        initFormSaving: function() {
            $('#booking-form-settings').on('submit', function(e) {
                e.preventDefault();

                // Validate required fields
                const formTitle = $('#form-title').val().trim();
                if (!formTitle) {
                    MoBookingDashboard.showNotification('<?php _e('Form title is required.', 'mobooking'); ?>', 'error');
                    $('#form-title').focus();
                    return;
                }

                // Show loading state
                const $saveBtn = $('#save-settings-btn');
                const originalHtml = $saveBtn.html();
                $saveBtn.prop('disabled', true).html('<div class="spinner"></div> <?php _e('Saving...', 'mobooking'); ?>');

                // Serialize form data
                const formData = $(this).serialize() + '&action=mobooking_save_booking_form_settings';

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            MoBookingDashboard.showNotification('<?php _e('Settings saved successfully!', 'mobooking'); ?>', 'success');
                            MoBookingDashboard.updateProgress();

                            // Update URLs if they changed
                            if (response.data && response.data.booking_url) {
                                $('#booking-url').val(response.data.booking_url);
                            }
                        } else {
                            MoBookingDashboard.showNotification(
                                response.data || '<?php _e('Failed to save settings.', 'mobooking'); ?>',
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {xhr, status, error});
                        let errorMessage = '<?php _e('An error occurred while saving settings.', 'mobooking'); ?>';

                        if (xhr.status === 0) {
                            errorMessage = '<?php _e('Network error. Please check your connection.', 'mobooking'); ?>';
                        } else if (xhr.status === 403) {
                            errorMessage = '<?php _e('Permission denied. Please refresh the page and try again.', 'mobooking'); ?>';
                        } else if (xhr.status === 500) {
                            errorMessage = '<?php _e('Server error. Please check the error logs.', 'mobooking'); ?>';
                        }

                        MoBookingDashboard.showNotification(errorMessage, 'error');
                    },
                    complete: function() {
                        // Restore button state
                        $saveBtn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        },

        // Reset settings
        initResetSettings: function() {
            $('#reset-settings-btn').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to reset all settings to defaults? This action cannot be undone.', 'mobooking'); ?>')) {
                    const $resetBtn = $(this);
                    const originalHtml = $resetBtn.html();
                    $resetBtn.prop('disabled', true).html('<div class="spinner"></div> <?php _e('Resetting...', 'mobooking'); ?>');

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'mobooking_reset_booking_form_settings',
                            nonce: $('input[name="nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                MoBookingDashboard.showNotification('<?php _e('Settings reset successfully!', 'mobooking'); ?>', 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                MoBookingDashboard.showNotification(
                                    response.data || '<?php _e('Failed to reset settings.', 'mobooking'); ?>',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            MoBookingDashboard.showNotification('<?php _e('An error occurred while resetting settings.', 'mobooking'); ?>', 'error');
                        },
                        complete: function() {
                            $resetBtn.prop('disabled', false).html(originalHtml);
                        }
                    });
                }
            });
        },

        // Preview functionality
        initPreview: function() {
            $('#preview-btn').on('click', function() {
                const bookingUrl = $('#booking-url').val();
                if (bookingUrl) {
                    window.open(bookingUrl, '_blank');
                } else {
                    MoBookingDashboard.showNotification('<?php _e('Booking URL not available. Please save settings first.', 'mobooking'); ?>', 'warning');
                }
            });
        },

        // Update setup progress
        updateProgress: function() {
            // This could be enhanced to dynamically check completion status
            const servicesComplete = <?php echo $setup_steps['services'] ? 'true' : 'false'; ?>;
            const areasComplete = <?php echo $setup_steps['areas'] ? 'true' : 'false'; ?>;
            const designComplete = $('#form-title').val().trim() !== '' && $('#primary-color').val() !== '';
            const seoComplete = $('#seo-title').val().trim() !== '' && $('#seo-description').val().trim() !== '';

            const completedSteps = [servicesComplete, areasComplete, designComplete, seoComplete].filter(Boolean).length;
            const totalSteps = 4;
            const percentage = Math.round((completedSteps / totalSteps) * 100);

            $('.progress-percentage').text(percentage + '%');
            $('.progress-bar').css('width', percentage + '%');
        },

        // Notification system
        showNotification: function(message, type = 'info') {
            // Remove existing notifications
            $('.mobooking-dashboard .notification').remove();

            const notification = $(`
                <div class="notification ${type}">
                    <span>${message}</span>
                    <button type="button" class="notification-close">&times;</button>
                </div>
            `);

            $('body').append(notification);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut();
            }, 5000);

            // Close button handler
            notification.find('.notification-close').on('click', function() {
                notification.fadeOut();
            });
        }
    };

    // Copy to clipboard function
    window.copyToClipboard = function(selector) {
        const $element = $(selector);
        if ($element.length) {
            $element.select();
            document.execCommand('copy');

            // Show success feedback
            const $button = $element.siblings('button').last();
            const originalHtml = $button.html();
            $button.html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg> <?php _e('Copied!', 'mobooking'); ?>');

            setTimeout(() => {
                $button.html(originalHtml);
            }, 2000);
        }
    };

    // Initialize the dashboard
    MoBookingDashboard.init();

    // Make globally available for debugging
    window.MoBookingDashboard = MoBookingDashboard;
});
</script>

<?php
// Enhanced AJAX handler for saving booking form settings
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

        // Map form fields correctly
        $settings_data = array(
            // Basic Information
            'form_title' => sanitize_text_field($_POST['form_title'] ?? ''),
            'form_description' => sanitize_textarea_field($_POST['form_description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? absint($_POST['is_active']) : 0,
            'language' => sanitize_text_field($_POST['language'] ?? 'en'),

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

            // Layout & Style
            'form_layout' => sanitize_text_field($_POST['form_layout'] ?? 'modern'),
            'form_width' => sanitize_text_field($_POST['form_width'] ?? 'standard'),
            'step_indicator_style' => sanitize_text_field($_POST['step_indicator_style'] ?? 'progress'),
            'button_style' => sanitize_text_field($_POST['button_style'] ?? 'rounded'),

            // SEO Optimization
            'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize_textarea_field($_POST['seo_description'] ?? ''),

            // Custom Code
            'analytics_code' => wp_kses_post($_POST['analytics_code'] ?? ''),
            'custom_css' => wp_strip_all_tags($_POST['custom_css'] ?? '')
        );

        // Save settings using BookingForm Manager
        $booking_form_manager = new \MoBooking\BookingForm\BookingFormManager();
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

// AJAX handler for resetting booking form settings
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

        // Reset to default settings
        $default_settings = array(
            'form_title' => __('Book Our Services', 'mobooking'),
            'form_description' => __('Select your service and schedule an appointment', 'mobooking'),
            'is_active' => 1,
            'language' => 'en',
            'show_form_header' => 1,
            'show_service_descriptions' => 1,
            'show_price_breakdown' => 1,
            'enable_zip_validation' => 0,
            'show_form_footer' => 1,
            'primary_color' => '#3b82f6',
            'secondary_color' => '#1e40af',
            'background_color' => '#ffffff',
            'text_color' => '#1f2937',
            'form_layout' => 'modern',
            'form_width' => 'standard',
            'step_indicator_style' => 'progress',
            'button_style' => 'rounded',
            'seo_title' => '',
            'seo_description' => '',
            'analytics_code' => '',
            'custom_css' => ''
        );

        $booking_form_manager = new \MoBooking\BookingForm\BookingFormManager();
        $result = $booking_form_manager->save_settings($user_id, $default_settings);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Settings reset successfully!', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'mobooking'));
        }

    } catch (Exception $e) {
        error_log('MoBooking - Exception in reset booking form settings: ' . $e->getMessage());
        wp_send_json_error(__('An error occurred while resetting settings.', 'mobooking'));
    }
});
?>
