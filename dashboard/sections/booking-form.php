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
                    <span class="stat-number">
                        <span class="status-indicator <?php echo $settings->is_active ? 'active' : 'inactive'; ?>"></span>
                    </span>
                    <span class="stat-label"><?php echo $settings->is_active ? __('Active', 'mobooking') : __('Inactive', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="booking-form-header-actions">
            <button type="button" id="preview-form-btn" class="btn-secondary">
                <svg viewBox="0 0 24 24" fill="none