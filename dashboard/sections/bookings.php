<?php
// dashboard/sections/bookings.php - FIXED for Normalized Database Structure
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current view and booking ID
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;

// Initialize managers
$booking_manager = new \MoBooking\Bookings\Manager();

// Handle booking viewing/editing
$booking_data = null;
if ($current_view === 'view' && $booking_id) {
    $booking_data = $booking_manager->get_booking($booking_id, $user_id);
    if (!$booking_data) {
        $current_view = 'list';
    }
}

// Get user's bookings for list view
$bookings = array();
$total_bookings = 0;
$booking_stats = array();

if ($current_view === 'list') {
    // Get booking statistics using normalized database
    global $wpdb;
    
    // Get booking counts by status
    $status_counts = $wpdb->get_results($wpdb->prepare(
        "SELECT status, COUNT(*) as count 
         FROM {$wpdb->prefix}mobooking_bookings 
         WHERE user_id = %d 
         GROUP BY status",
        $user_id
    ));
    
    $booking_stats = array(
        'total' => 0,
        'pending' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'rescheduled' => 0
    );
    
    foreach ($status_counts as $stat) {
        $booking_stats['total'] += $stat->count;
        $booking_stats[$stat->status] = $stat->count;
    }
    
    // Get recent bookings with service information
    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            b.*,
            COUNT(bs.service_id) as service_count,
            GROUP_CONCAT(s.name SEPARATOR ', ') as service_names,
            SUM(bs.total_price) as services_total
         FROM {$wpdb->prefix}mobooking_bookings b
         LEFT JOIN {$wpdb->prefix}mobooking_booking_services bs ON b.id = bs.booking_id
         LEFT JOIN {$wpdb->prefix}mobooking_services s ON bs.service_id = s.id
         WHERE b.user_id = %d
         GROUP BY b.id
         ORDER BY b.created_at DESC
         LIMIT 20",
        $user_id
    ));
    
    $total_bookings = $booking_stats['total'];
}
?>

<div class="bookings-section modern-layout">
    <?php if ($current_view === 'list') : ?>
        <!-- ===== BOOKINGS LIST VIEW ===== -->
        <div class="bookings-header">
            <div class="header-main">
                <div class="title-section">
                    <h1 class="page-title">
                        <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                            <path d="M9 9h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <?php _e('Bookings', 'mobooking'); ?>
                    </h1>
                    <p class="page-subtitle"><?php _e('Manage your customer bookings and appointments', 'mobooking'); ?></p>
                </div>
                
                <div class="booking-stats-compact">
                    <div class="stat-pill total">
                        <span class="stat-number"><?php echo $booking_stats['total']; ?></span>
                        <span class="stat-label"><?php _e('Total', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-pill pending">
                        <span class="stat-number"><?php echo $booking_stats['pending']; ?></span>
                        <span class="stat-label"><?php _e('Pending', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-pill confirmed">
                        <span class="stat-number"><?php echo $booking_stats['confirmed']; ?></span>
                        <span class="stat-label"><?php _e('Confirmed', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-pill completed">
                        <span class="stat-number"><?php echo $booking_stats['completed']; ?></span>
                        <span class="stat-label"><?php _e('Completed', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="filter-controls">
                    <select id="status-filter" class="filter-select">
                        <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                        <option value="pending"><?php _e('Pending', 'mobooking'); ?></option>
                        <option value="confirmed"><?php _e('Confirmed', 'mobooking'); ?></option>
                        <option value="completed"><?php _e('Completed', 'mobooking'); ?></option>
                        <option value="cancelled"><?php _e('Cancelled', 'mobooking'); ?></option>
                        <option value="rescheduled"><?php _e('Rescheduled', 'mobooking'); ?></option>
                    </select>
                    
                    <input type="date" id="date-filter" class="filter-input" placeholder="<?php _e('Filter by date', 'mobooking'); ?>">
                </div>
                
                <button type="button" id="export-bookings-btn" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7,10 12,15 17,10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <?php _e('Export', 'mobooking'); ?>
                </button>
            </div>
        </div>
        
        <?php if (empty($bookings)) : ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-visual">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <path d="M9 9h6m-6 4h6"/>
                    </svg>
                </div>
                <div class="empty-content">
                    <h3><?php _e('No Bookings Yet', 'mobooking'); ?></h3>
                    <p><?php _e('When customers book your services, their appointments will appear here. Share your booking form to start receiving bookings.', 'mobooking'); ?></p>
                    <a href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>" class="btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                        </svg>
                        <?php _e('Setup Booking Form', 'mobooking'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Bookings Table -->
            <div class="bookings-table-container">
                <div class="table-header">
                    <h2><?php _e('Recent Bookings', 'mobooking'); ?></h2>
                    <div class="table-actions">
                        <button type="button" id="refresh-bookings-btn" class="btn-icon" title="<?php _e('Refresh', 'mobooking'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="1 4 1 10 7 10"/>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="bookings-table">
                    <table class="bookings-list">
                        <thead>
                            <tr>
                                <th class="col-id"><?php _e('ID', 'mobooking'); ?></th>
                                <th class="col-customer"><?php _e('Customer', 'mobooking'); ?></th>
                                <th class="col-services"><?php _e('Services', 'mobooking'); ?></th>
                                <th class="col-date"><?php _e('Date & Time', 'mobooking'); ?></th>
                                <th class="col-total"><?php _e('Total', 'mobooking'); ?></th>
                                <th class="col-status"><?php _e('Status', 'mobooking'); ?></th>
                                <th class="col-actions"><?php _e('Actions', 'mobooking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking) : ?>
                                <tr class="booking-row" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                                    <td class="col-id">
                                        <span class="booking-id">#<?php echo esc_html($booking->id); ?></span>
                                    </td>
                                    
                                    <td class="col-customer">
                                        <div class="customer-info">
                                            <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                            <div class="customer-contact">
                                                <a href="mailto:<?php echo esc_attr($booking->customer_email); ?>" class="email-link">
                                                    <?php echo esc_html($booking->customer_email); ?>
                                                </a>
                                                <?php if (!empty($booking->customer_phone)) : ?>
                                                    <span class="phone-number"><?php echo esc_html($booking->customer_phone); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="col-services">
                                        <div class="services-info">
                                            <?php if (!empty($booking->service_names)) : ?>
                                                <div class="service-names"><?php echo esc_html($booking->service_names); ?></div>
                                            <?php endif; ?>
                                            <div class="service-count">
                                                <?php echo sprintf(_n('%d service', '%d services', $booking->service_count, 'mobooking'), $booking->service_count); ?>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="col-date">
                                        <div class="date-info">
                                            <div class="service-date">
                                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking->service_date))); ?>
                                            </div>
                                            <div class="service-time">
                                                <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($booking->service_date))); ?>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="col-total">
                                        <div class="pricing-info">
                                            <div class="total-price"><?php echo wc_price($booking->total_price); ?></div>
                                            <?php if ($booking->discount_amount > 0) : ?>
                                                <div class="discount-info">
                                                    <span class="discount-label"><?php _e('Discount:', 'mobooking'); ?></span>
                                                    <span class="discount-amount">-<?php echo wc_price($booking->discount_amount); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="col-status">
                                        <div class="status-container">
                                            <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                                <?php echo esc_html(mobooking_get_booking_status_label($booking->status)); ?>
                                            </span>
                                            <div class="status-actions">
                                                <select class="status-select" data-booking-id="<?php echo esc_attr($booking->id); ?>" data-current-status="<?php echo esc_attr($booking->status); ?>">
                                                    <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending', 'mobooking'); ?></option>
                                                    <option value="confirmed" <?php selected($booking->status, 'confirmed'); ?>><?php _e('Confirmed', 'mobooking'); ?></option>
                                                    <option value="completed" <?php selected($booking->status, 'completed'); ?>><?php _e('Completed', 'mobooking'); ?></option>
                                                    <option value="cancelled" <?php selected($booking->status, 'cancelled'); ?>><?php _e('Cancelled', 'mobooking'); ?></option>
                                                    <option value="rescheduled" <?php selected($booking->status, 'rescheduled'); ?>><?php _e('Rescheduled', 'mobooking'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="col-actions">
                                        <div class="action-buttons">
                                            <button type="button" class="action-btn view-booking-btn" 
                                                    data-booking-id="<?php echo esc_attr($booking->id); ?>" 
                                                    title="<?php _e('View Details', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </button>
                                            
                                            <button type="button" class="action-btn edit-booking-btn" 
                                                    data-booking-id="<?php echo esc_attr($booking->id); ?>" 
                                                    title="<?php _e('Edit Booking', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                                                </svg>
                                            </button>
                                            
                                            <button type="button" class="action-btn delete-booking-btn" 
                                                    data-booking-id="<?php echo esc_attr($booking->id); ?>" 
                                                    title="<?php _e('Delete Booking', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="m3 6 3 18h12l3-18"/>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="table-pagination">
                    <div class="pagination-info">
                        <?php printf(__('Showing %d of %d bookings', 'mobooking'), count($bookings), $total_bookings); ?>
                    </div>
                    <div class="pagination-controls">
                        <button type="button" class="btn-pagination prev" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                            <?php _e('Previous', 'mobooking'); ?>
                        </button>
                        <button type="button" class="btn-pagination next">
                            <?php _e('Next', 'mobooking'); ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <!-- ===== BOOKING DETAIL VIEW ===== -->
        <div class="booking-detail-view">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="breadcrumb-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <path d="M9 9h6m-6 4h6"/>
                    </svg>
                    <?php _e('Bookings', 'mobooking'); ?>
                </a>
                <svg class="breadcrumb-separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                <span class="breadcrumb-current">
                    <?php printf(__('Booking #%d', 'mobooking'), $booking_data->id); ?>
                </span>
            </div>
            
            <!-- Booking Header -->
            <div class="booking-header">
                <div class="header-content">
                    <div class="booking-title">
                        <h1><?php printf(__('Booking #%d', 'mobooking'), $booking_data->id); ?></h1>
                        <span class="status-badge status-<?php echo esc_attr($booking_data->status); ?>">
                            <?php echo esc_html(mobooking_get_booking_status_label($booking_data->status)); ?>
                        </span>
                    </div>
                    <div class="booking-meta">
                        <span class="created-date">
                            <?php printf(__('Created: %s', 'mobooking'), date_i18n(get_option('date_format'), strtotime($booking_data->created_at))); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn-secondary" onclick="window.print();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 6,2 18,2 18,9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect width="12" height="8" x="6" y="14"/>
                        </svg>
                        <?php _e('Print', 'mobooking'); ?>
                    </button>
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m12 19-7-7 7-7M19 12H5"/>
                        </svg>
                        <?php _e('Back to List', 'mobooking'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Booking Details Grid -->
            <div class="booking-details-grid">
                <!-- Customer Information -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><?php _e('Customer Information', 'mobooking'); ?></h3>
                    </div>
                    <div class="card-content">
                        <div class="detail-row">
                            <label><?php _e('Name:', 'mobooking'); ?></label>
                            <span><?php echo esc_html($booking_data->customer_name); ?></span>
                        </div>
                        <div class="detail-row">
                            <label><?php _e('Email:', 'mobooking'); ?></label>
                            <span><a href="mailto:<?php echo esc_attr($booking_data->customer_email); ?>"><?php echo esc_html($booking_data->customer_email); ?></a></span>
                        </div>
                        <?php if (!empty($booking_data->customer_phone)) : ?>
                            <div class="detail-row">
                                <label><?php _e('Phone:', 'mobooking'); ?></label>
                                <span><a href="tel:<?php echo esc_attr($booking_data->customer_phone); ?>"><?php echo esc_html($booking_data->customer_phone); ?></a></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <label><?php _e('Address:', 'mobooking'); ?></label>
                            <span><?php echo esc_html($booking_data->customer_address); ?></span>
                        </div>
                        <div class="detail-row">
                            <label><?php _e('ZIP Code:', 'mobooking'); ?></label>
                            <span><?php echo esc_html($booking_data->zip_code); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Service Information -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><?php _e('Service Information', 'mobooking'); ?></h3>
                    </div>
                    <div class="card-content">
                        <div class="detail-row">
                            <label><?php _e('Service Date:', 'mobooking'); ?></label>
                            <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking_data->service_date))); ?></span>
                        </div>
                        
                        <!-- Services List -->
                        <?php if (!empty($booking_data->services)) : ?>
                            <div class="services-section">
                                <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                                <div class="services-list">
                                    <?php foreach ($booking_data->services as $service) : ?>
                                        <div class="service-item">
                                            <div class="service-details">
                                                <span class="service-name"><?php echo esc_html($service->service_name); ?></span>
                                                <?php if (!empty($service->service_description)) : ?>
                                                    <span class="service-description"><?php echo esc_html($service->service_description); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="service-pricing">
                                                <span class="quantity">x<?php echo esc_html($service->quantity); ?></span>
                                                <span class="unit-price"><?php echo wc_price($service->unit_price); ?></span>
                                                <span class="total-price"><?php echo wc_price($service->total_price); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Service Options -->
                        <?php if (!empty($booking_data->service_options)) : ?>
                            <div class="options-section">
                                <h4><?php _e('Additional Options', 'mobooking'); ?></h4>
                                <div class="options-list">
                                    <?php foreach ($booking_data->service_options as $option) : ?>
                                        <div class="option-item">
                                            <div class="option-details">
                                                <span class="option-name"><?php echo esc_html($option->option_name); ?></span>
                                                <span class="option-value"><?php echo esc_html($option->option_value); ?></span>
                                            </div>
                                            <?php if ($option->price_impact > 0) : ?>
                                                <div class="option-pricing">
                                                    <span class="price-impact">+<?php echo wc_price($option->price_impact); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pricing Information -->
                <div class="detail-card">
                    <div class="card-header">
                        <h3><?php _e('Pricing Details', 'mobooking'); ?></h3>
                    </div>
                    <div class="card-content">
                        <div class="pricing-breakdown">
                            <div class="pricing-row">
                                <label><?php _e('Subtotal:', 'mobooking'); ?></label>
                                <span><?php echo wc_price($booking_data->subtotal); ?></span>
                            </div>
                            
                            <?php if ($booking_data->discount_amount > 0) : ?>
                                <div class="pricing-row discount">
                                    <label>
                                        <?php _e('Discount:', 'mobooking'); ?>
                                        <?php if (!empty($booking_data->discount_code)) : ?>
                                            <span class="discount-code">(<?php echo esc_html($booking_data->discount_code); ?>)</span>
                                        <?php endif; ?>
                                    </label>
                                    <span class="discount-amount">-<?php echo wc_price($booking_data->discount_amount); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pricing-row total">
                                <label><?php _e('Total:', 'mobooking'); ?></label>
                                <span class="total-amount"><?php echo wc_price($booking_data->total_price); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <?php if (!empty($booking_data->notes)) : ?>
                    <div class="detail-card full-width">
                        <div class="card-header">
                            <h3><?php _e('Special Instructions', 'mobooking'); ?></h3>
                        </div>
                        <div class="card-content">
                            <div class="notes-content">
                                <?php echo wp_kses_post(nl2br($booking_data->notes)); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Status Management -->
                <div class="detail-card full-width">
                    <div class="card-header">
                        <h3><?php _e('Booking Management', 'mobooking'); ?></h3>
                    </div>
                    <div class="card-content">
                        <div class="status-management">
                            <div class="status-section">
                                <label for="booking-status"><?php _e('Update Status:', 'mobooking'); ?></label>
                                <select id="booking-status" class="status-select" data-booking-id="<?php echo esc_attr($booking_data->id); ?>">
                                    <option value="pending" <?php selected($booking_data->status, 'pending'); ?>><?php _e('Pending', 'mobooking'); ?></option>
                                    <option value="confirmed" <?php selected($booking_data->status, 'confirmed'); ?>><?php _e('Confirmed', 'mobooking'); ?></option>
                                    <option value="completed" <?php selected($booking_data->status, 'completed'); ?>><?php _e('Completed', 'mobooking'); ?></option>
                                    <option value="cancelled" <?php selected($booking_data->status, 'cancelled'); ?>><?php _e('Cancelled', 'mobooking'); ?></option>
                                    <option value="rescheduled" <?php selected($booking_data->status, 'rescheduled'); ?>><?php _e('Rescheduled', 'mobooking'); ?></option>
                                </select>
                                <button type="button" id="update-status-btn" class="btn-primary">
                                    <?php _e('Update Status', 'mobooking'); ?>
                                </button>
                            </div>
                            
                            <div class="danger-zone">
                                <h4><?php _e('Danger Zone', 'mobooking'); ?></h4>
                                <p><?php _e('These actions cannot be undone.', 'mobooking'); ?></p>
                                <button type="button" class="btn-danger delete-booking-btn" data-booking-id="<?php echo esc_attr($booking_data->id); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m3 6 3 18h12l3-18"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                    </svg>
                                    <?php _e('Delete Booking', 'mobooking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Booking Detail Modal -->
<div id="booking-detail-modal" class="modal" style="display:none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="modal-booking-title"><?php _e('Booking Details', 'mobooking'); ?></h3>
            <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="modal-body">
            <div id="modal-booking-content">
                <!-- Booking details will be loaded here -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn-secondary modal-close-btn"><?php _e('Close', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Confirm Action', 'mobooking'); ?></h3>
        </div>
        <div class="modal-body">
            <p id="confirmation-message"><?php _e('Are you sure you want to perform this action?', 'mobooking'); ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary cancel-action-btn"><?php _e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="btn-danger confirm-action-btn">
                <span class="btn-text"><?php _e('Confirm', 'mobooking'); ?></span>
                <span class="btn-loading" style="display: none;">
                    <div class="spinner"></div>
                    <?php _e('Processing...', 'mobooking'); ?>
                </span>
            </button>
        </div>
    </div>
</div>

<style>
/* Modern Bookings Section Styles */
.bookings-section.modern-layout {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

/* Header Styles */
.bookings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, hsl(var(--card)), hsl(var(--muted) / 0.3));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    box-shadow: 0 2px 8px hsl(var(--shadow) / 0.1);
}

.header-main {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex: 1;
}

.title-section {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.title-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: hsl(var(--primary));
}

.page-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.booking-stats-compact {
    display: flex;
    gap: 1rem;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
}

.stat-pill.total {
    background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
    border-color: hsl(var(--primary) / 0.3);
}

.stat-pill.pending {
    background: linear-gradient(135deg, hsl(var(--warning) / 0.1), hsl(var(--warning) / 0.05));
    border-color: hsl(var(--warning) / 0.3);
}

.stat-pill.confirmed {
    background: linear-gradient(135deg, hsl(var(--info) / 0.1), hsl(var(--info) / 0.05));
    border-color: hsl(var(--info) / 0.3);
}

.stat-pill.completed {
    background: linear-gradient(135deg, hsl(var(--success) / 0.1), hsl(var(--success) / 0.05));
    border-color: hsl(var(--success) / 0.3);
}

.stat-number {
    font-weight: 700;
    color: hsl(var(--foreground));
}

.stat-label {
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-controls {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.filter-select,
.filter-input {
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: hsl(var(--background));
    font-size: 0.875rem;
    min-width: 120px;
}

.btn-secondary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-secondary:hover {
    background: hsl(var(--accent));
}

.btn-secondary svg {
    width: 1rem;
    height: 1rem;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
    border: 2px dashed hsl(var(--border));
    border-radius: 12px;
}

.empty-visual {
    margin-bottom: 2rem;
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    color: hsl(var(--primary));
    opacity: 0.6;
}

.empty-content h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.empty-content p {
    margin: 0 0 2rem 0;
    color: hsl(var(--muted-foreground));
    max-width: 400px;
}

.btn-primary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.9));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.2);
    cursor: pointer;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px hsl(var(--primary) / 0.3);
}

.btn-primary svg {
    width: 1rem;
    height: 1rem;
}

/* Table Styles */
.bookings-table-container {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
}

.table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.table-header h2 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
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

.btn-icon:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary) / 0.3);
}

.btn-icon svg {
    width: 1rem;
    height: 1rem;
}

.bookings-table {
    overflow-x: auto;
}

.bookings-list {
    width: 100%;
    border-collapse: collapse;
}

.bookings-list th,
.bookings-list td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid hsl(var(--border));
}

.bookings-list th {
    background: hsl(var(--muted) / 0.3);
    font-weight: 600;
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.booking-row:hover {
    background: hsl(var(--accent) / 0.5);
}

/* Column Styles */
.col-id {
    width: 80px;
}

.col-customer {
    min-width: 200px;
}

.col-services {
    min-width: 180px;
}

.col-date {
    min-width: 140px;
}

.col-total {
    min-width: 120px;
}

.col-status {
    min-width: 140px;
}

.col-actions {
    width: 120px;
}

.booking-id {
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-weight: 600;
    color: hsl(var(--primary));
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.customer-name {
    font-weight: 600;
    color: hsl(var(--foreground));
}

.customer-contact {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
}

.email-link {
    color: hsl(var(--primary));
    text-decoration: none;
}

.email-link:hover {
    text-decoration: underline;
}

.services-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-names {
    font-weight: 500;
    color: hsl(var(--foreground));
}

.service-count {
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
}

.date-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-date {
    font-weight: 500;
    color: hsl(var(--foreground));
}

.service-time {
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
}

.pricing-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.total-price {
    font-weight: 600;
    color: hsl(var(--success));
}

.discount-info {
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
}

.discount-amount {
    color: hsl(var(--warning));
    font-weight: 500;
}

/* Status Badges */
.status-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-badge.status-pending {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
    border: 1px solid hsl(var(--warning) / 0.3);
}

.status-badge.status-confirmed {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
    border: 1px solid hsl(var(--info) / 0.3);
}

.status-badge.status-completed {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
    border: 1px solid hsl(var(--success) / 0.3);
}

.status-badge.status-cancelled {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border: 1px solid hsl(var(--destructive) / 0.3);
}

.status-badge.status-rescheduled {
    background: hsl(var(--secondary) / 0.1);
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--secondary) / 0.3);
}

.status-select {
    padding: 0.25rem 0.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: 4px;
    background: hsl(var(--background));
    font-size: 0.75rem;
    min-width: 100px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.action-btn {
    width: 1.75rem;
    height: 1.75rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary) / 0.3);
}

.action-btn.delete-booking-btn:hover {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: white;
}

.action-btn svg {
    width: 0.875rem;
    height: 0.875rem;
}

/* Pagination */
.table-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.2);
}

.pagination-info {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.pagination-controls {
    display: flex;
    gap: 0.5rem;
}

.btn-pagination {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-pagination:hover:not(:disabled) {
    background: hsl(var(--accent));
}

.btn-pagination:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-pagination svg {
    width: 1rem;
    height: 1rem;
}

/* Detail View Styles */
.booking-detail-view {
    max-width: 1000px;
    margin: 0 auto;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.breadcrumb-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb-link:hover {
    color: hsl(var(--primary));
}

.breadcrumb-link svg {
    width: 1rem;
    height: 1rem;
}

.breadcrumb-separator {
    width: 1rem;
    height: 1rem;
    color: hsl(var(--muted-foreground));
}

.breadcrumb-current {
    color: hsl(var(--foreground));
    font-weight: 500;
}

.booking-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.booking-title {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.booking-title h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.booking-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

/* Detail Cards */
.booking-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.detail-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
}

.detail-card.full-width {
    grid-column: 1 / -1;
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.card-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.card-content {
    padding: 1.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid hsl(var(--border));
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row label {
    font-weight: 500;
    color: hsl(var(--muted-foreground));
    min-width: 100px;
}

.detail-row span {
    flex: 1;
    text-align: right;
    color: hsl(var(--foreground));
}

.detail-row a {
    color: hsl(var(--primary));
    text-decoration: none;
}

.detail-row a:hover {
    text-decoration: underline;
}

/* Services and Options Lists */
.services-section,
.options-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid hsl(var(--border));
}

.services-section h4,
.options-section h4 {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.services-list,
.options-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-item,
.option-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: 6px;
}

.service-details,
.option-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-name,
.option-name {
    font-weight: 500;
    color: hsl(var(--foreground));
}

.service-description,
.option-value {
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
}

.service-pricing,
.option-pricing {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.quantity {
    color: hsl(var(--muted-foreground));
}

.unit-price,
.total-price,
.price-impact {
    font-weight: 600;
    color: hsl(var(--success));
}

/* Pricing Breakdown */
.pricing-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.pricing-row:not(:last-child) {
    border-bottom: 1px solid hsl(var(--border));
}

.pricing-row.total {
    padding-top: 1rem;
    border-top: 2px solid hsl(var(--border));
    font-weight: 700;
    font-size: 1.125rem;
}

.pricing-row.discount label {
    color: hsl(var(--warning));
}

.discount-code {
    font-size: 0.75rem;
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    margin-left: 0.5rem;
}

.discount-amount {
    color: hsl(var(--warning));
    font-weight: 600;
}

.total-amount {
    color: hsl(var(--success));
    font-weight: 700;
    font-size: 1.25rem;
}

/* Notes Content */
.notes-content {
    background: hsl(var(--muted) / 0.3);
    padding: 1rem;
    border-radius: 6px;
    white-space: pre-wrap;
    color: hsl(var(--foreground));
    line-height: 1.5;
}

/* Status Management */
.status-management {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.status-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-section label {
    font-weight: 500;
    color: hsl(var(--foreground));
    min-width: 120px;
}

.status-section .status-select {
    flex: 1;
    padding: 0.5rem;
    font-size: 0.875rem;
}

.danger-zone {
    padding: 1.5rem;
    background: hsl(var(--destructive) / 0.05);
    border: 1px solid hsl(var(--destructive) / 0.2);
    border-radius: 8px;
}

.danger-zone h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--destructive));
}

.danger-zone p {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.btn-danger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-danger:hover {
    background: hsl(var(--destructive) / 0.9);
    transform: translateY(-1px);
}

.btn-danger svg {
    width: 1rem;
    height: 1rem;
}

/* Modal Styles */
.modal {
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

.modal:not([style*="display: none"]) {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    width: 90vw;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

.modal-content.modal-large {
    max-width: 800px;
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
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 2rem;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.2);
}

.modal-close-btn {
    padding: 0.5rem 1rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close-btn:hover {
    background: hsl(var(--accent));
}

.cancel-action-btn {
    padding: 0.5rem 1rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-action-btn:hover {
    background: hsl(var(--accent));
}

.confirm-action-btn {
    position: relative;
    overflow: hidden;
}

.btn-loading {
    display: none;
}

.confirm-action-btn.loading .btn-text {
    opacity: 0;
}

.confirm-action-btn.loading .btn-loading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: absolute;
    inset: 0;
    justify-content: center;
}

.spinner {
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

/* Responsive Design */
@media (max-width: 1024px) {
    .bookings-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .header-main {
        flex-direction: column;
        gap: 1rem;
    }

    .booking-stats-compact {
        justify-content: center;
        flex-wrap: wrap;
    }

    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }

    .booking-details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .bookings-table {
        font-size: 0.875rem;
    }

    .bookings-list th,
    .bookings-list td {
        padding: 0.75rem 0.5rem;
    }

    .customer-contact {
        flex-direction: column;
    }

    .action-buttons {
        flex-direction: column;
        gap: 0.125rem;
    }

    .filter-controls {
        flex-direction: column;
        width: 100%;
    }

    .filter-select,
    .filter-input {
        width: 100%;
    }

    .table-pagination {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .modal-content {
        width: 95vw;
        margin: 1rem;
    }

    .modal-header,
    .modal-body,
    .modal-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

@media (max-width: 480px) {
    .booking-header {
        flex-direction: column;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
        justify-content: center;
    }

    .status-section {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }

    .status-section label {
        min-width: auto;
    }

    .table-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    /* Hide less important columns on mobile */
    .col-services,
    .col-date {
        display: none;
    }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .bookings-header,
    .table-header,
    .booking-header {
        background: linear-gradient(135deg, hsl(var(--card)), hsl(var(--muted) / 0.2));
    }
    
    .modal {
        background: rgba(0, 0, 0, 0.8);
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .detail-card,
    .modal-content,
    .bookings-table-container {
        border-width: 2px;
    }
    
    .btn-primary,
    .btn-danger {
        border: 2px solid currentColor;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Print styles */
@media print {
    .header-actions,
    .table-actions,
    .action-buttons,
    .danger-zone {
        display: none !important;
    }
    
    .booking-details-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .detail-card {
        break-inside: avoid;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Enhanced Bookings Manager with Normalized Database Support
    const BookingsManager = {
        currentPage: 1,
        itemsPerPage: 20,
        currentFilters: {
            status: '',
            date: ''
        },
        
        // Initialize all functionality
        init: function() {
            this.initEventHandlers();
            this.initModals();
            this.initFilters();
            this.loadBookings();
            
            console.log('BookingsManager initialized with normalized database support');
        },
        
        // Initialize event handlers
        initEventHandlers: function() {
            // Status change handlers
            $(document).on('change', '.status-select', this.handleStatusChange.bind(this));
            
            // View booking details
            $(document).on('click', '.view-booking-btn', this.handleViewBooking.bind(this));
            
            // Edit booking
            $(document).on('click', '.edit-booking-btn', this.handleEditBooking.bind(this));
            
            // Delete booking
            $(document).on('click', '.delete-booking-btn', this.handleDeleteBooking.bind(this));
            
            // Update status button
            $(document).on('click', '#update-status-btn', this.handleUpdateStatus.bind(this));
            
            // Export bookings
            $(document).on('click', '#export-bookings-btn', this.handleExportBookings.bind(this));
            
            // Refresh bookings
            $(document).on('click', '#refresh-bookings-btn', this.loadBookings.bind(this));
            
            // Pagination
            $(document).on('click', '.btn-pagination', this.handlePagination.bind(this));
        },
        
        // Initialize modals
        initModals: function() {
            // Close modal handlers
            $(document).on('click', '.modal-close, .modal-close-btn', function() {
                $('.modal').hide();
            });
            
            // Close modal on background click
            $(document).on('click', '.modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Cancel action handler
            $(document).on('click', '.cancel-action-btn', function() {
                $('.modal').hide();
            });
            
            // Confirm action handler
            $(document).on('click', '.confirm-action-btn', this.handleConfirmAction.bind(this));
        },
        
        // Initialize filters
        initFilters: function() {
            $('#status-filter').on('change', (e) => {
                this.currentFilters.status = e.target.value;
                this.currentPage = 1;
                this.loadBookings();
            });
            
            $('#date-filter').on('change', (e) => {
                this.currentFilters.date = e.target.value;
                this.currentPage = 1;
                this.loadBookings();
            });
        },
        
        // Load bookings from server
        loadBookings: function() {
            const data = {
                action: 'mobooking_get_user_bookings',
                nonce: mobookingDashboard.nonces.booking,
                page: this.currentPage,
                per_page: this.itemsPerPage,
                status: this.currentFilters.status,
                date: this.currentFilters.date
            };
            
            $.post(mobookingDashboard.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.renderBookings(response.data.bookings);
                        this.updatePagination(response.data.total, response.data.pages);
                    } else {
                        this.showNotification(response.data || 'Failed to load bookings', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Error loading bookings', 'error');
                });
        },
        
        // Render bookings in table
        renderBookings: function(bookings) {
            const tbody = $('.bookings-list tbody');
            tbody.empty();
            
            if (bookings.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">
                            <p>No bookings found matching your criteria.</p>
                        </td>
                    </tr>
                `);
                return;
            }
            
            bookings.forEach(booking => {
                const row = this.createBookingRow(booking);
                tbody.append(row);
            });
        },
        
        // Create booking row HTML
        createBookingRow: function(booking) {
            const serviceDate = new Date(booking.service_date);
            const createdDate = new Date(booking.created_at);
            
            return `
                <tr class="booking-row" data-booking-id="${booking.id}">
                    <td class="col-id">
                        <span class="booking-id">#${booking.id}</span>
                    </td>
                    <td class="col-customer">
                        <div class="customer-info">
                            <div class="customer-name">${this.escapeHtml(booking.customer_name)}</div>
                            <div class="customer-contact">
                                <a href="mailto:${booking.customer_email}" class="email-link">
                                    ${this.escapeHtml(booking.customer_email)}
                                </a>
                                ${booking.customer_phone ? `<span class="phone-number">${this.escapeHtml(booking.customer_phone)}</span>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="col-services">
                        <div class="services-info">
                            ${booking.service_names ? `<div class="service-names">${this.escapeHtml(booking.service_names)}</div>` : ''}
                            <div class="service-count">${booking.service_count || 0} service${(booking.service_count || 0) !== 1 ? 's' : ''}</div>
                        </div>
                    </td>
                    <td class="col-date">
                        <div class="date-info">
                            <div class="service-date">${serviceDate.toLocaleDateString()}</div>
                            <div class="service-time">${serviceDate.toLocaleTimeString()}</div>
                        </div>
                    </td>
                    <td class="col-total">
                        <div class="pricing-info">
                            <div class="total-price">${parseFloat(booking.total_price).toFixed(2)}</div>
                            ${booking.discount_amount > 0 ? `
                                <div class="discount-info">
                                    <span class="discount-label">Discount:</span>
                                    <span class="discount-amount">-${parseFloat(booking.discount_amount).toFixed(2)}</span>
                                </div>
                            ` : ''}
                        </div>
                    </td>
                    <td class="col-status">
                        <div class="status-container">
                            <span class="status-badge status-${booking.status}">
                                ${this.getStatusLabel(booking.status)}
                            </span>
                            <div class="status-actions">
                                <select class="status-select" data-booking-id="${booking.id}" data-current-status="${booking.status}">
                                    <option value="pending" ${booking.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="confirmed" ${booking.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                    <option value="completed" ${booking.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="cancelled" ${booking.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                    <option value="rescheduled" ${booking.status === 'rescheduled' ? 'selected' : ''}>Rescheduled</option>
                                </select>
                            </div>
                        </div>
                    </td>
                    <td class="col-actions">
                        <div class="action-buttons">
                            <button type="button" class="action-btn view-booking-btn" 
                                    data-booking-id="${booking.id}" 
                                    title="View Details">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                            <button type="button" class="action-btn edit-booking-btn" 
                                    data-booking-id="${booking.id}" 
                                    title="Edit Booking">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"/>
                                </svg>
                            </button>
                            <button type="button" class="action-btn delete-booking-btn" 
                                    data-booking-id="${booking.id}" 
                                    title="Delete Booking">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m3 6 3 18h12l3-18"/>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        },
        
        // Handle status change
        handleStatusChange: function(e) {
            const $select = $(e.target);
            const bookingId = $select.data('booking-id');
            const currentStatus = $select.data('current-status');
            const newStatus = $select.val();
            
            if (newStatus === currentStatus) {
                return;
            }
            
            this.updateBookingStatus(bookingId, newStatus, $select);
        },
        
        // Update booking status
        updateBookingStatus: function(bookingId, status, $select) {
            const data = {
                action: 'mobooking_update_booking_status',
                nonce: mobookingDashboard.nonces.booking,
                booking_id: bookingId,
                status: status
            };
            
            // Show loading state
            if ($select) {
                $select.prop('disabled', true);
            }
            
            $.post(mobookingDashboard.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || 'Status updated successfully', 'success');
                        
                        // Update the status badge
                        const $row = $(`.booking-row[data-booking-id="${bookingId}"]`);
                        const $badge = $row.find('.status-badge');
                        $badge.removeClass().addClass(`status-badge status-${status}`);
                        $badge.text(this.getStatusLabel(status));
                        
                        // Update current status data
                        if ($select) {
                            $select.data('current-status', status);
                        }
                        
                    } else {
                        this.showNotification(response.data || 'Failed to update status', 'error');
                        
                        // Reset select to previous value
                        if ($select) {
                            $select.val($select.data('current-status'));
                        }
                    }
                })
                .fail(() => {
                    this.showNotification('Error updating status', 'error');
                    
                    // Reset select to previous value
                    if ($select) {
                        $select.val($select.data('current-status'));
                    }
                })
                .always(() => {
                    // Remove loading state
                    if ($select) {
                        $select.prop('disabled', false);
                    }
                });
        },
        
        // Handle view booking
        handleViewBooking: function(e) {
            const bookingId = $(e.currentTarget).data('booking-id');
            this.loadBookingDetails(bookingId);
        },
        
        // Load booking details
        loadBookingDetails: function(bookingId) {
            const data = {
                action: 'mobooking_get_booking_details',
                nonce: mobookingDashboard.nonces.booking,
                booking_id: bookingId
            };
            
            $.post(mobookingDashboard.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {
                        this.showBookingDetails(response.data.booking);
                    } else {
                        this.showNotification(response.data || 'Failed to load booking details', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Error loading booking details', 'error');
                });
        },
        
        // Show booking details in modal
        showBookingDetails: function(booking) {
            const modal = $('#booking-detail-modal');
            const content = $('#modal-booking-content');
            
            // Set modal title
            $('#modal-booking-title').text(`Booking #${booking.id}`);
            
            // Generate booking details HTML
            const detailsHtml = this.generateBookingDetailsHtml(booking);
            content.html(detailsHtml);
            
            // Show modal
            modal.show();
        },
        
        // Generate booking details HTML
        generateBookingDetailsHtml: function(booking) {
            const serviceDate = new Date(booking.service_date);
            
            let html = `
                <div class="booking-details-modal">
                    <div class="detail-section">
                        <h4>Customer Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Name:</label>
                                <span>${this.escapeHtml(booking.customer_name)}</span>
                            </div>
                            <div class="detail-item">
                                <label>Email:</label>
                                <span><a href="mailto:${booking.customer_email}">${this.escapeHtml(booking.customer_email)}</a></span>
                            </div>
                            ${booking.customer_phone ? `
                                <div class="detail-item">
                                    <label>Phone:</label>
                                    <span><a href="tel:${booking.customer_phone}">${this.escapeHtml(booking.customer_phone)}</a></span>
                                </div>
                            ` : ''}
                            <div class="detail-item">
                                <label>Address:</label>
                                <span>${this.escapeHtml(booking.customer_address)}</span>
                            </div>
                            <div class="detail-item">
                                <label>ZIP Code:</label>
                                <span>${this.escapeHtml(booking.zip_code)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Service Information</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Service Date:</label>
                                <span>${serviceDate.toLocaleDateString()} ${serviceDate.toLocaleTimeString()}</span>
                            </div>
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="status-badge status-${booking.status}">${this.getStatusLabel(booking.status)}</span>
                            </div>
                        </div>
            `;
            
            // Add services if available
            if (booking.services && booking.services.length > 0) {
                html += `
                    <div class="services-list-modal">
                        <h5>Selected Services</h5>
                        <div class="services-items">
                `;
                
                booking.services.forEach(service => {
                    html += `
                        <div class="service-item-modal">
                            <div class="service-details">
                                <span class="service-name">${this.escapeHtml(service.service_name || 'Unknown Service')}</span>
                                ${service.service_description ? `<span class="service-description">${this.escapeHtml(service.service_description)}</span>` : ''}
                            </div>
                            <div class="service-pricing">
                                <span class="quantity">x${service.quantity || 1}</span>
                                <span class="unit-price">${parseFloat(service.unit_price || 0).toFixed(2)}</span>
                                <span class="total-price">${parseFloat(service.total_price || 0).toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Add service options if available
            if (booking.service_options && booking.service_options.length > 0) {
                html += `
                    <div class="options-list-modal">
                        <h5>Additional Options</h5>
                        <div class="options-items">
                `;
                
                booking.service_options.forEach(option => {
                    html += `
                        <div class="option-item-modal">
                            <div class="option-details">
                                <span class="option-name">${this.escapeHtml(option.option_name || 'Unknown Option')}</span>
                                <span class="option-value">${this.escapeHtml(option.option_value || '')}</span>
                            </div>
                            ${option.price_impact > 0 ? `
                                <div class="option-pricing">
                                    <span class="price-impact">+${parseFloat(option.price_impact).toFixed(2)}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Add pricing summary
            html += `
                    </div>
                    
                    <div class="detail-section">
                        <h4>Pricing Details</h4>
                        <div class="pricing-summary-modal">
                            <div class="pricing-row">
                                <label>Subtotal:</label>
                                <span>${parseFloat(booking.subtotal || booking.total_price).toFixed(2)}</span>
                            </div>
                            ${booking.discount_amount > 0 ? `
                                <div class="pricing-row discount">
                                    <label>Discount: ${booking.discount_code ? `(${this.escapeHtml(booking.discount_code)})` : ''}</label>
                                    <span class="discount-amount">-${parseFloat(booking.discount_amount).toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="pricing-row total">
                                <label>Total:</label>
                                <span class="total-amount">${parseFloat(booking.total_price).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
            `;
            
            // Add notes if available
            if (booking.notes && booking.notes.trim()) {
                html += `
                    <div class="detail-section">
                        <h4>Special Instructions</h4>
                        <div class="notes-content-modal">
                            ${this.escapeHtml(booking.notes).replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `;
            }
            
            html += `</div>`;
            
            return html;
        },
        
        // Handle edit booking
        handleEditBooking: function(e) {
            const bookingId = $(e.currentTarget).data('booking-id');
            // For now, redirect to view mode
            // In the future, this could open an edit modal
            window.location.href = `${mobookingDashboard.urls.bookings}?view=view&booking_id=${bookingId}`;
        },
        
        // Handle delete booking
        handleDeleteBooking: function(e) {
            const bookingId = $(e.currentTarget).data('booking-id');
            
            // Show confirmation modal
            $('#confirmation-message').text('Are you sure you want to delete this booking? This action cannot be undone.');
            $('#confirmation-modal').show();
            
            // Store booking ID for confirmation
            $('#confirmation-modal').data('booking-id', bookingId);
            $('#confirmation-modal').data('action', 'delete-booking');
        },
        
        // Handle update status button
        handleUpdateStatus: function(e) {
            const bookingId = $('#booking-status').data('booking-id');
            const newStatus = $('#booking-status').val();
            
            this.updateBookingStatus(bookingId, newStatus);
        },
        
        // Handle export bookings
        handleExportBookings: function(e) {
            const params = new URLSearchParams({
                action: 'mobooking_export_data',
                nonce: mobookingDashboard.nonces.settings,
                type: 'bookings',
                format: 'csv',
                status: this.currentFilters.status,
                date: this.currentFilters.date
            });
            
            window.open(`${mobookingDashboard.ajaxUrl}?${params.toString()}`, '_blank');
        },
        
        // Handle pagination
        handlePagination: function(e) {
            const $btn = $(e.currentTarget);
            
            if ($btn.hasClass('prev') && this.currentPage > 1) {
                this.currentPage--;
                this.loadBookings();
            } else if ($btn.hasClass('next')) {
                this.currentPage++;
                this.loadBookings();
            }
        },
        
        // Handle confirm action
        handleConfirmAction: function(e) {
            const $btn = $(e.currentTarget);
            const $modal = $('#confirmation-modal');
            const bookingId = $modal.data('booking-id');
            const action = $modal.data('action');
            
            if (action === 'delete-booking' && bookingId) {
                this.deleteBooking(bookingId, $btn);
            }
        },
        
        // Delete booking
        deleteBooking: function(bookingId, $btn) {
            const data = {
                action: 'mobooking_delete_booking',
                nonce: mobookingDashboard.nonces.booking,
                booking_id: bookingId
            };
            
            // Show loading state
            $btn.addClass('loading');
            
            $.post(mobookingDashboard.ajaxUrl, data)
                .done((response) => {
                    if (response.success) {