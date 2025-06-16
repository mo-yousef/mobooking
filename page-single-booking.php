<?php /** Template Name: Page Single Booking */ ?>
<?php
// dashboard/sections/single-booking.php - Individual Booking Detail Page
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get booking details
$booking = $bookings_manager->get_booking($booking_id, $user_id);

if (!$booking) {
    ?>
    <div class="booking-not-found">
        <div class="not-found-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M15 9l-6 6M9 9l6 6"/>
            </svg>
        </div>
        <h2><?php _e('Booking Not Found', 'mobooking'); ?></h2>
        <p><?php _e('The booking you\'re looking for doesn\'t exist or you don\'t have permission to view it.', 'mobooking'); ?></p>
        <a href="<?php echo esc_url(remove_query_arg(array('view', 'booking_id'))); ?>" class="btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
            <?php _e('Back to Bookings', 'mobooking'); ?>
        </a>
    </div>
    <?php
    return;
}

// Get services data
$services_manager = new \MoBooking\Services\ServicesManager();
$services_data     = $booking->services;
$services_list     = array();

if (is_array($services_data)) {
    foreach ($services_data as $service_id) {
        $service = $services_manager->get_service($service_id);
        if ($service) {
            $services_list[] = $service;
        }
    }
}

// Parse service options
$service_options_data = $booking->service_options;

// Date calculations
$service_date = new DateTime($booking->service_date);
$created_date = new DateTime($booking->created_at);
$updated_date = new DateTime($booking->updated_at);
$now          = new DateTime();

// Calculate time until service
$interval   = $now->diff($service_date);
$days_until = $interval->days;
$is_future  = $service_date > $now;
$is_today   = $service_date->format('Y-m-d') === $now->format('Y-m-d');
$is_overdue = $service_date < $now && !in_array($booking->status, ['completed', 'cancelled']);

// Determine urgency
$urgency_class = '';
$urgency_text  = '';
if ($booking->status !== 'completed' && $booking->status !== 'cancelled') {
    if ($is_overdue) {
        $urgency_class = 'overdue';
        $urgency_text  = __('Overdue', 'mobooking');
    } elseif ($is_today) {
        $urgency_class = 'today';
        $urgency_text  = __('Today', 'mobooking');
    } elseif ($days_until == 1) {
        $urgency_class = 'tomorrow';
        $urgency_text  = __('Tomorrow', 'mobooking');
    } elseif ($days_until <= 3 && $is_future) {
        $urgency_class = 'soon';
        $urgency_text  = sprintf(__('In %d days', 'mobooking'), $days_until);
    }
}
?>

<div class="single-booking-page">
    <!-- Header -->
    <div class="single-booking-header">
        <div class="header-navigation">
            <a href="<?php echo esc_url(remove_query_arg(array('view', 'booking_id'))); ?>" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                <?php _e('Back to Bookings', 'mobooking'); ?>
            </a>
        </div>

        <div class="header-content">
            <div class="header-main">
                <div class="booking-id-section">
                    <h1 class="booking-title">
                        <?php _e('Booking', 'mobooking'); ?> <span class="booking-id-highlight">#<?php echo $booking->id; ?></span>
                    </h1>
                    <div class="booking-meta">
                        <span class="created-date">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            <?php printf(__('Created %s', 'mobooking'), $created_date->format('M j, Y \a\t g:i A')); ?>
                        </span>
                        <?php if ($updated_date != $created_date) : ?>
                            <span class="updated-date">
                                <?php printf(__('Updated %s', 'mobooking'), $updated_date->format('M j, Y \a\t g:i A')); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="status-section">
                    <div class="current-status">
                        <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                            <?php
                            switch ($booking->status) {
                                case 'pending':
                                    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>';
                                    _e('Pending Review', 'mobooking');
                                    break;
                                case 'confirmed':
                                    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>';
                                    _e('Confirmed', 'mobooking');
                                    break;
                                case 'completed':
                                    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                    _e('Completed', 'mobooking');
                                    break;
                                case 'cancelled':
                                    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                                    _e('Cancelled', 'mobooking');
                                    break;
                            }
                            ?>
                        </span>
                    </div>

                    <?php if ($urgency_text) : ?>
                        <div class="urgency-badge <?php echo $urgency_class; ?>">
                            <?php echo $urgency_text; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="header-actions">
                <div class="status-update-dropdown" style="margin-right: 0.75rem;">
                    <label for="booking-status-changer" class="sr-only"><?php _e('Change Booking Status', 'mobooking'); ?></label>
                    <select id="booking-status-changer" class="form-control" data-booking-id="<?php echo esc_attr($booking->id); ?>" data-current-status="<?php echo esc_attr($booking->status); ?>" style="padding: 0.75rem 1.25rem; border: 1px solid hsl(var(--border)); border-radius: 8px; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                        <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending Review', 'mobooking'); ?></option>
                        <option value="confirmed" <?php selected($booking->status, 'confirmed'); ?>><?php _e('Confirmed', 'mobooking'); ?></option>
                        <option value="completed" <?php selected($booking->status, 'completed'); ?>><?php _e('Completed', 'mobooking'); ?></option>
                        <option value="cancelled" <?php selected($booking->status, 'cancelled'); ?>><?php _e('Cancelled', 'mobooking'); ?></option>
                    </select>
                </div>
                <button type="button" class="btn-secondary" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6,9 6,2 18,2 18,9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    <?php _e('Print', 'mobooking'); ?>
                </button>
                <a href="download-booking-pdf.php?booking_id=<?php echo $booking->id; ?>" class="btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <?php _e('Download PDF', 'mobooking'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="single-booking-content">
        <div class="booking-details-grid">
            <!-- Customer Information -->
            <div class="detail-card customer-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php _e('Customer Information', 'mobooking'); ?>
                    </h2>
                </div>
                <div class="card-content">
                    <div class="customer-profile">
                        <div class="customer-avatar-large">
                            <?php echo strtoupper(substr($booking->customer_name, 0, 2)); ?>
                        </div>
                        <div class="customer-info">
                            <h3 class="customer-name"><?php echo esc_html($booking->customer_name); ?></h3>
                            <div class="contact-details">
                                <div class="contact-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                    <a href="mailto:<?php echo esc_attr($booking->customer_email); ?>" class="contact-link">
                                        <?php echo esc_html($booking->customer_email); ?>
                                    </a>
                                </div>
                                <?php if (!empty($booking->customer_phone)) : ?>
                                    <div class="contact-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        <a href="tel:<?php echo esc_attr($booking->customer_phone); ?>" class="contact-link">
                                            <?php echo esc_html($booking->customer_phone); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Date & Time -->
            <div class="detail-card datetime-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 2V6M8 2V6M3 10H21"/>
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        </svg>
                        <?php _e('Service Schedule', 'mobooking'); ?>
                    </h2>
                </div>
                <div class="card-content">
                    <div class="datetime-display">
                        <div class="date-section">
                            <div class="date-main">
                                <?php echo $service_date->format('l'); ?>
                            </div>
                            <div class="date-full">
                                <?php echo $service_date->format('F j, Y'); ?>
                            </div>
                        </div>
                        <div class="time-section">
                            <div class="time-main">
                                <?php echo $service_date->format('g:i A'); ?>
                            </div>
                            <div class="timezone">
                                <?php echo $service_date->format('T'); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_future) : ?>
                        <div class="countdown">
                            <div class="countdown-label"><?php _e('Time until service:', 'mobooking'); ?></div>
                            <div class="countdown-value">
                                <?php
                                if ($days_until > 0) {
                                    printf(_n('%d day', '%d days', $days_until, 'mobooking'), $days_until);
                                    if ($interval->h > 0) {
                                        echo ', ' . sprintf(_n('%d hour', '%d hours', $interval->h, 'mobooking'), $interval->h);
                                    }
                                } elseif ($interval->h > 0) {
                                    printf(_n('%d hour', '%d hours', $interval->h, 'mobooking'), $interval->h);
                                    if ($interval->i > 0) {
                                        echo ', ' . sprintf(_n('%d minute', '%d minutes', $interval->i, 'mobooking'), $interval->i);
                                    }
                                } else {
                                    printf(_n('%d minute', '%d minutes', $interval->i, 'mobooking'), $interval->i);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Service Address -->
            <div class="detail-card address-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php _e('Service Address', 'mobooking'); ?>
                    </h2>
                </div>
                <div class="card-content">
                    <div class="address-display">
                        <div class="address-text">
                            <?php echo nl2br(esc_html($booking->customer_address)); ?>
                        </div>
                        <div class="zip-code">
                            <span class="zip-label"><?php _e('ZIP Code:', 'mobooking'); ?></span>
                            <span class="zip-value"><?php echo esc_html($booking->zip_code); ?></span>
                        </div>
                    </div>

                    <div class="address-actions">
                        <a href="https://maps.google.com/?q=<?php echo urlencode($booking->customer_address . ', ' . $booking->zip_code); ?>"
                           target="_blank" rel="noopener" class="map-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <?php _e('View on Map', 'mobooking'); ?>
                        </a>

                        <a href="https://www.google.com/maps/dir/Current+Location/<?php echo urlencode($booking->customer_address . ', ' . $booking->zip_code); ?>"
                           target="_blank" rel="noopener" class="directions-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                            </svg>
                            <?php _e('Get Directions', 'mobooking'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Services & Pricing -->
            <div class="detail-card services-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                        </svg>
                        <?php _e('Services & Pricing', 'mobooking'); ?>
                    </h2>
                </div>
                <div class="card-content">
                    <div class="services-list">
                        <?php if (!empty($services_list)) : ?>
                            <?php foreach ($services_list as $service) : ?>
                                <div class="service-item">
                                    <div class="service-info">
                                        <div class="service-main">
                                            <h4 class="service-name"><?php echo esc_html($service->name); ?></h4>
                                            <?php if (!empty($service->description)) : ?>
                                                <p class="service-description"><?php echo esc_html($service->description); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="service-meta">
                                            <span class="service-duration">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <polyline points="12,6 12,12 16,14"/>
                                                </svg>
                                                <?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="service-price">
                                        <?php echo function_exists('wc_price') ? wc_price($service->price) : number_format($service->price, 2); ?>
                                    </div>
                                </div>

                                <?php if (!empty($service_options_data[$service->id])) : ?>
                                    <div class="service-options">
                                        <h5 class="options-title"><?php _e('Selected Options:', 'mobooking'); ?></h5>
                                        <?php foreach ($service_options_data[$service->id] as $option_id => $option_value) : ?>
                                            <?php if (!empty($option_value) && $option_value !== '0') : ?>
                                                <?php
                                                // Get option details (you might want to create a method to get option by ID)
                                                $option_name   = sprintf(__('Option %d', 'mobooking'), $option_id);
                                                $display_value = $option_value;

                                                // Format display value based on option type
                                                if ($option_value === '1') {
                                                    $display_value = __('Yes', 'mobooking');
                                                }
                                                ?>
                                                <div class="option-item">
                                                    <span class="option-name"><?php echo esc_html($option_name); ?>:</span>
                                                    <span class="option-value"><?php echo esc_html($display_value); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="no-services">
                                <p><?php _e('No services specified for this booking.', 'mobooking'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pricing Summary -->
                    <div class="pricing-summary">
                        <div class="pricing-line">
                            <span class="label"><?php _e('Subtotal:', 'mobooking'); ?></span>
                            <span class="amount">
                                <?php
                                $subtotal = $booking->total_price + $booking->discount_amount;
                                echo function_exists('wc_price') ? wc_price($subtotal) : number_format($subtotal, 2);
                                ?>
                            </span>
                        </div>

                        <?php if ($booking->discount_amount > 0) : ?>
                            <div class="pricing-line discount">
                                <span class="label">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                    </svg>
                                    <?php _e('Discount:', 'mobooking'); ?>
                                    <?php if (!empty($booking->discount_code)) : ?>
                                        <span class="discount-code">(<?php echo esc_html($booking->discount_code); ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="amount discount-amount">
                                    -<?php echo function_exists('wc_price') ? wc_price($booking->discount_amount) : number_format($booking->discount_amount, 2); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="pricing-line total">
                            <span class="label"><?php _e('Total:', 'mobooking'); ?></span>
                            <span class="amount total-amount">
                                <?php echo function_exists('wc_price') ? wc_price($booking->total_price) : number_format($booking->total_price, 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Special Instructions -->
            <?php if (!empty($booking->notes)) : ?>
                <div class="detail-card notes-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                            <?php _e('Special Instructions', 'mobooking'); ?>
                        </h2>
                    </div>
                    <div class="card-content">
                        <div class="notes-content">
                            <?php echo nl2br(esc_html($booking->notes)); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Booking Timeline -->
            <div class="detail-card timeline-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                        <?php _e('Booking Timeline', 'mobooking'); ?>
                    </h2>
                </div>
                <div class="card-content">
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-marker">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                </svg>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title"><?php _e('Booking Created', 'mobooking'); ?></div>
                                <div class="timeline-time"><?php echo $created_date->format('M j, Y \a\t g:i A'); ?></div>
                                <div class="timeline-description"><?php _e('Customer submitted booking request', 'mobooking'); ?></div>
                            </div>
                        </div>

                        <div class="timeline-item <?php echo in_array($booking->status, ['confirmed', 'completed']) ? 'completed' : 'pending'; ?>">
                            <div class="timeline-marker">
                                <?php if (in_array($booking->status, ['confirmed', 'completed'])) : ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                    </svg>
                                <?php else : ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    <?php echo $booking->status === 'confirmed' || $booking->status === 'completed' ?
                                        __('Booking Confirmed', 'mobooking') : __('Awaiting Confirmation', 'mobooking'); ?>
                                </div>
                                <?php if ($booking->status === 'confirmed' || $booking->status === 'completed') : ?>
                                    <div class="timeline-time"><?php echo $updated_date->format('M j, Y \a\t g:i A'); ?></div>
                                    <div class="timeline-description"><?php _e('Booking has been confirmed and scheduled', 'mobooking'); ?></div>
                                <?php else : ?>
                                    <div class="timeline-description pending-text"><?php _e('Waiting for your confirmation', 'mobooking'); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="timeline-item <?php echo $booking->status === 'completed' ? 'completed' : 'future'; ?>">
                            <div class="timeline-marker">
                                <?php if ($booking->status === 'completed') : ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                    </svg>
                                <?php else : ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    <?php echo $booking->status === 'completed' ?
                                        __('Service Completed', 'mobooking') : __('Service Scheduled', 'mobooking'); ?>
                                </div>
                                <div class="timeline-time"><?php echo $service_date->format('M j, Y \a\t g:i A'); ?></div>
                                <div class="timeline-description">
                                    <?php echo $booking->status === 'completed' ?
                                        __('Service has been completed successfully', 'mobooking') :
                                        __('Scheduled service date and time', 'mobooking'); ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($booking->status === 'cancelled') : ?>
                            <div class="timeline-item cancelled">
                                <div class="timeline-marker">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M15 9l-6 6M9 9l6 6"/>
                                    </svg>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title"><?php _e('Booking Cancelled', 'mobooking'); ?></div>
                                    <div class="timeline-time"><?php echo $updated_date->format('M j, Y \a\t g:i A'); ?></div>
                                    <div class="timeline-description"><?php _e('This booking has been cancelled', 'mobooking'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // New handler for status dropdown change
    $('#booking-status-changer').on('change', function() {
        const $dropdown = $(this);
        const bookingId = $dropdown.data('booking-id');
        const newStatus = $dropdown.val();
        const currentStatus = $dropdown.data('current-status'); // Store initial status to revert

        if (newStatus === currentStatus) {
            return; // No change
        }

        let confirmMessage = '<?php echo esc_js(__('Are you sure you want to change the booking status to ', 'mobooking')); ?>' + newStatus.charAt(0).toUpperCase() + newStatus.slice(1) + '?';

        if (newStatus === 'cancelled') {
            confirmMessage = '<?php echo esc_js(__('Cancel this booking? This action cannot be undone.', 'mobooking')); ?>';
        } else if (newStatus === 'completed' && currentStatus !== 'completed') {
            confirmMessage = '<?php echo esc_js(__('Mark this booking as completed?', 'mobooking')); ?>';
        } else if (newStatus === 'confirmed' && currentStatus === 'pending') {
            confirmMessage = '<?php echo esc_js(__('Confirm this booking?', 'mobooking')); ?>';
        }

        // Use a general confirmation, or only for specific transitions like 'cancelled'
        if (confirm(confirmMessage)) {
            // Add a visual loading state if possible
            $dropdown.prop('disabled', true).css('opacity', 0.7);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mobooking_update_booking_status',
                    booking_id: bookingId,
                    status: newStatus,
                    nonce: '<?php echo wp_create_nonce('mobooking-booking-nonce'); // Confirm this nonce is appropriate for the AJAX handler ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Update current status data attribute and reload for full consistency
                        $dropdown.data('current-status', newStatus);
                        location.reload();
                    } else {
                        alert(response.data.message || response.data || '<?php echo esc_js(__('Error updating booking status', 'mobooking')); ?>');
                        $dropdown.prop('disabled', false).css('opacity', 1);
                        $dropdown.val(currentStatus); // Revert to original status on failure
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('<?php echo esc_js(__('AJAX error updating booking status:', 'mobooking')); ?> ' + textStatus + ' - ' + errorThrown);
                    $dropdown.prop('disabled', false).css('opacity', 1);
                    $dropdown.val(currentStatus); // Revert to original status on failure
                }
            });
        } else {
            $dropdown.val(currentStatus); // Revert if confirmation is cancelled
        }
    });

    // Old button handlers are removed by replacing the HTML for header-actions.
    // If any other JS depends on them, that might need adjustment, but assumed not for now.
});
</script>

<style>
/* (styles unchanged for brevity) */
/* New Two-Column Layout Styles */
.single-booking-content.new-layout {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}
.booking-main-details {
    flex: 2; /* Approx 66-70% */
    min-width: 0; /* Prevent overflow */
}
.booking-sidebar {
    flex: 1; /* Approx 30-33% */
    min-width: 320px; /* Minimum width for sidebar */
}
.booking-main-details .booking-details-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (max-width: 1024px) {
    .single-booking-content.new-layout {
        flex-direction: column;
    }
    .booking-main-details,
    .booking-sidebar {
        flex: 1 1 100%;
    }
}
</style>


<style>
/* Single Booking Page Styles */
.single-booking-page {
    max-width: 1400px;
    margin: 0 auto;
    animation: fadeIn 0.6s ease-out;
}

/* Header */
.single-booking-header {
    margin-bottom: 2rem;
}

.header-navigation {
    margin-bottom: 1rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem 0;
    transition: color 0.2s ease;
}

.back-link:hover {
    color: hsl(var(--primary));
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    padding: 2rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
}

.header-main {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.booking-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: hsl(var(--foreground));
}

.booking-id-highlight {
    color: hsl(var(--primary));
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
}

.booking-meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.created-date,
.updated-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.status-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid;
}

.status-pending {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
    border-color: hsl(var(--warning) / 0.3);
}

.status-confirmed {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
    border-color: hsl(var(--info) / 0.3);
}

.status-completed {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
    border-color: hsl(var(--success) / 0.3);
}

.status-cancelled {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border-color: hsl(var(--destructive) / 0.3);
}

.urgency-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.urgency-badge.today {
    background: #fef3c7;
    color: #92400e;
}

.urgency-badge.tomorrow {
    background: #dbeafe;
    color: #1e40af;
}

.urgency-badge.overdue {
    background: #fee2e2;
    color: #991b1b;
    animation: pulse 1.5s infinite;
}

.urgency-badge.soon {
    background: #f3e8ff;
    color: #7c3aed;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-confirm {
    background: hsl(var(--success) / 0.1);
    border-color: hsl(var(--success) / 0.3);
    color: hsl(var(--success));
}

.btn-confirm:hover {
    background: hsl(var(--success) / 0.2);
    border-color: hsl(var(--success));
}

.btn-complete {
    background: hsl(var(--info) / 0.1);
    border-color: hsl(var(--info) / 0.3);
    color: hsl(var(--info));
}

.btn-complete:hover {
    background: hsl(var(--info) / 0.2);
    border-color: hsl(var(--info));
}

.btn-cancel {
    background: hsl(var(--destructive) / 0.1);
    border-color: hsl(var(--destructive) / 0.3);
    color: hsl(var(--destructive));
}

.btn-cancel:hover {
    background: hsl(var(--destructive) / 0.2);
    border-color: hsl(var(--destructive));
}

.btn-action.loading {
    opacity: 0.7;
    pointer-events: none;
}

.btn-action.loading::after {
    content: '';
    width: 14px;
    height: 14px;
    border: 2px solid currentColor;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 0.5rem;
}

/* Main Content Grid */
.booking-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

/* Detail Cards */
.detail-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.detail-card:hover {
    box-shadow: 0 8px 20px hsl(var(--primary) / 0.1);
    transform: translateY(-2px);
}

.card-header {
    padding: 1.5rem 1.5rem 0 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: hsl(var(--foreground));
}

.card-content {
    padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Customer Card */
.customer-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.customer-avatar-large {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.customer-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.75rem 0;
    color: hsl(var(--foreground));
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.contact-link {
    color: hsl(var(--foreground));
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.contact-link:hover {
    color: hsl(var(--primary));
}

/* DateTime Card */
.datetime-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.date-section,
.time-section {
    text-align: center;
}

.date-main,
.time-main {
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.date-full,
.timezone {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.countdown {
    text-align: center;
    padding: 1rem;
    background: hsl(var(--primary) / 0.1);
    border-radius: 8px;
    border: 1px solid hsl(var(--primary) / 0.2);
}

.countdown-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.5rem;
}

.countdown-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--primary));
}

/* Address Card */
.address-display {
    margin-bottom: 1rem;
}

.address-text {
    font-size: 1rem;
    line-height: 1.5;
    color: hsl(var(--foreground));
    margin-bottom: 0.75rem;
}

.zip-code {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.zip-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.zip-value {
    font-weight: 600;
    color: hsl(var(--foreground));
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
}

.address-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.map-link,
.directions-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.map-link:hover,
.directions-link:hover {
    background: hsl(var(--accent));
    transform: translateY(-1px);
}

/* Services Card */
.services-list {
    margin-bottom: 1.5rem;
}

.service-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    border-radius: 8px;
    margin-bottom: 1rem;
    background: hsl(var(--muted) / 0.2);
}

.service-item:last-child {
    margin-bottom: 0;
}

.service-info {
    flex: 1;
}

.service-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: hsl(var(--foreground));
}

.service-description {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
}

.service-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.service-duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.service-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.service-options {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid hsl(var(--border));
}

.options-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    margin: 0 0 0.5rem 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.option-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    font-size: 0.875rem;
}

.option-name {
    color: hsl(var(--muted-foreground));
}

.option-value {
    font-weight: 500;
    color: hsl(var(--foreground));
}

.no-services {
    text-align: center;
    padding: 2rem;
    color: hsl(var(--muted-foreground));
}

/* Pricing Summary */
.pricing-summary {
    border-top: 1px solid hsl(var(--border));
    padding-top: 1rem;
}

.pricing-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

.pricing-line.total {
    border-top: 1px solid hsl(var(--border));
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
}

.pricing-line.discount .label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--success));
}

.discount-code {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    font-weight: normal;
}

.discount-amount {
    color: hsl(var(--success));
}

.total-amount {
    color: hsl(var(--foreground));
    font-size: 1.25rem;
}

/* Notes Card */
.notes-content {
    padding: 1rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: 8px;
    border-left: 4px solid hsl(var(--primary));
    font-size: 0.875rem;
    line-height: 1.6;
    color: hsl(var(--foreground));
}

/* Timeline Card */
.timeline {
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: hsl(var(--border));
}

.timeline-item {
    position: relative;
    display: flex;
    gap: 1rem;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: relative;
    z-index: 1;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: hsl(var(--card));
    border: 2px solid hsl(var(--border));
}

.timeline-item.completed .timeline-marker {
    background: hsl(var(--success));
    border-color: hsl(var(--success));
    color: white;
}

.timeline-item.pending .timeline-marker {
    background: hsl(var(--warning));
    border-color: hsl(var(--warning));
    color: white;
    animation: pulse 2s infinite;
}

.timeline-item.cancelled .timeline-marker {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: white;
}

.timeline-item.future .timeline-marker {
    background: hsl(var(--muted));
    border-color: hsl(var(--muted-foreground));
    color: hsl(var(--muted-foreground));
}

.timeline-content {
    flex: 1;
    padding-top: 0.125rem;
}

.timeline-title {
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.timeline-time {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.25rem;
}

.timeline-description {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

.pending-text {
    color: hsl(var(--warning));
    font-weight: 500;
}

/* Not Found State */
.booking-not-found {
    text-align: center;
    padding: 4rem 2rem;
    background: hsl(var(--card));
    border: 2px dashed hsl(var(--border));
    border-radius: 12px;
}

.not-found-icon {
    margin-bottom: 1.5rem;
    color: hsl(var(--muted-foreground));
    opacity: 0.6;
}

.booking-not-found h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: hsl(var(--foreground));
}

.booking-not-found p {
    font-size: 1rem;
    color: hsl(var(--muted-foreground));
    margin: 0 0 2rem 0;
    max-width: 24rem;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .booking-details-grid {
        grid-template-columns: 1fr;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }

    .header-main {
        flex-direction: column;
        gap: 1rem;
    }

    .status-section {
        align-items: flex-start;
    }
}

@media (max-width: 768px) {
    .single-booking-page {
        padding: 0 0.5rem;
    }

    .header-content {
        padding: 1.5rem;
    }

    .booking-title {
        font-size: 1.5rem;
    }

    .header-actions {
        flex-direction: column;
    }

    .btn-action {
        justify-content: center;
        width: 100%;
    }

    .datetime-display {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .service-item {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .service-price {
        text-align: right;
    }

    .address-actions {
        flex-direction: column;
    }

    .map-link,
    .directions-link {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .detail-card {
        border-radius: 8px;
    }

    .card-header,
    .card-content {
        padding: 1rem;
    }

    .customer-profile {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }

    .contact-details {
        align-items: center;
    }

    .timeline::before {
        left: 0.75rem;
    }

    .timeline-marker {
        width: 1.5rem;
        height: 1.5rem;
    }
}

/* Print Styles */
@media print {
    .single-booking-header .header-actions,
    .address-actions,
    .back-link {
        display: none;
    }

    .single-booking-page {
        max-width: none;
    }

    .booking-details-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .detail-card {
        break-inside: avoid;
        border: 2px solid #333;
        margin-bottom: 1rem;
    }

    .header-content {
        border: 2px solid #333;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .detail-card {
        border-width: 2px;
    }

    .timeline-marker {
        border-width: 3px;
    }

    .status-badge {
        border-width: 2px;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Animations */
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

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
/* New Two-Column Layout Styles */
.single-booking-content.new-layout {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}
.booking-main-details {
    flex: 2; /* Approx 66-70% */
    min-width: 0; /* Prevent overflow */
}
.booking-sidebar {
    flex: 1; /* Approx 30-33% */
    min-width: 320px; /* Minimum width for sidebar */
}
.booking-main-details .booking-details-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (max-width: 1024px) {
    .single-booking-content.new-layout {
        flex-direction: column;
    }
    .booking-main-details,
    .booking-sidebar {
        flex: 1 1 100%;
    }
}
</style>

[end of page-single-booking.php]
