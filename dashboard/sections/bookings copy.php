<?php
// dashboard/sections/bookings.php - ENHANCED Bookings Management Section
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize bookings manager
$bookings_manager = new \MoBooking\Bookings\Manager();

// Get current view (list or individual booking)
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;

// Handle individual booking view
if ($current_view === 'booking' && $booking_id) {
    include(MOBOOKING_PATH . '/dashboard/sections/single-booking.php');
    return;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_filter = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '';
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Pagination
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query arguments
$args = array(
    'limit' => $per_page,
    'offset' => $offset,
    'orderby' => 'created_at',
    'order' => 'DESC'
);

if ($status_filter) {
    $args['status'] = $status_filter;
}

if ($date_filter) {
    switch($date_filter) {
        case 'today':
            $args['date_from'] = date('Y-m-d');
            $args['date_to'] = date('Y-m-d');
            break;
        case 'this_week':
            $args['date_from'] = date('Y-m-d', strtotime('monday this week'));
            $args['date_to'] = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'this_month':
            $args['date_from'] = date('Y-m-01');
            $args['date_to'] = date('Y-m-t');
            break;
        case 'last_30_days':
            $args['date_from'] = date('Y-m-d', strtotime('-30 days'));
            $args['date_to'] = date('Y-m-d');
            break;
    }
}

// Get bookings and statistics
$bookings = $bookings_manager->get_user_bookings($user_id, $args);
$total_bookings = $bookings_manager->count_user_bookings($user_id, $status_filter);

// Get statistics
$stats = array(
    'total' => $bookings_manager->count_user_bookings($user_id),
    'pending' => $bookings_manager->count_user_bookings($user_id, 'pending'),
    'confirmed' => $bookings_manager->count_user_bookings($user_id, 'confirmed'),
    'completed' => $bookings_manager->count_user_bookings($user_id, 'completed'),
    'cancelled' => $bookings_manager->count_user_bookings($user_id, 'cancelled'),
    'total_revenue' => $bookings_manager->calculate_user_revenue($user_id),
    'this_month_revenue' => $bookings_manager->calculate_user_revenue($user_id, 'this_month'),
    'today_revenue' => $bookings_manager->calculate_user_revenue($user_id, 'today')
);

// Calculate pagination
$total_pages = ceil($total_bookings / $per_page);
?>

<div class="bookings-section">
    <!-- Enhanced Header -->
    <div class="bookings-header">
        <div class="bookings-header-content">
            <div class="bookings-title-group">
                <h1 class="bookings-main-title">
                    <svg class="title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                    </svg>
                    <?php _e('Bookings Management', 'mobooking'); ?>
                </h1>
                <p class="bookings-subtitle"><?php _e('Manage and track your service bookings', 'mobooking'); ?></p>
            </div>
            
            <div class="bookings-actions">
                <button type="button" class="btn-secondary" id="export-bookings-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-15"/>
                        <path d="M7 10l5 5 5-5"/>
                        <path d="M12 15V3"/>
                    </svg>
                    <?php _e('Export', 'mobooking'); ?>
                </button>
                
                <button type="button" class="btn-secondary" id="refresh-bookings-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"/>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    <?php _e('Refresh', 'mobooking'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Stats Grid -->
    <div class="bookings-stats-grid">
        <div class="stat-card total-bookings">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 2V6M8 2V6M3 10H21"/>
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label"><?php _e('Total Bookings', 'mobooking'); ?></div>
                <div class="stat-trend">
                    <span class="trend-icon">📈</span>
                    <span class="trend-text"><?php _e('All time', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card pending-bookings<?php echo $stats['pending'] > 0 ? ' has-alert' : ''; ?>">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                <div class="stat-label"><?php _e('Pending Review', 'mobooking'); ?></div>
                <?php if ($stats['pending'] > 0) : ?>
                    <div class="stat-alert">
                        <span class="alert-pulse"></span>
                        <?php printf(_n('%d needs attention', '%d need attention', $stats['pending'], 'mobooking'), $stats['pending']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-card confirmed-bookings">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-value"><?php echo number_format($stats['confirmed']); ?></div>
                <div class="stat-label"><?php _e('Confirmed', 'mobooking'); ?></div>
                <div class="stat-trend success">
                    <span class="trend-icon">✅</span>
                    <span class="trend-text"><?php _e('Ready to serve', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card completed-bookings">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
                <div class="stat-label"><?php _e('Completed', 'mobooking'); ?></div>
                <div class="stat-trend success">
                    <span class="trend-icon">🎉</span>
                    <span class="trend-text"><?php _e('Job well done', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card revenue-card">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-value"><?php echo function_exists('wc_price') ? wc_price($stats['total_revenue']) : '$' . number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label"><?php _e('Total Revenue', 'mobooking'); ?></div>
                <div class="stat-subinfo">
                    <small><?php _e('This Month:', 'mobooking'); ?> <strong><?php echo function_exists('wc_price') ? wc_price($stats['this_month_revenue']) : '$' . number_format($stats['this_month_revenue'], 2); ?></strong></small>
                </div>
            </div>
        </div>
        
        <div class="stat-card cancelled-bookings">
            <div class="stat-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
            </div>
            <div class="stat-card-content">
                <div class="stat-value"><?php echo number_format($stats['cancelled']); ?></div>
                <div class="stat-label"><?php _e('Cancelled', 'mobooking'); ?></div>
                <div class="stat-trend neutral">
                    <span class="trend-text"><?php _e('Total cancelled', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Filters and Search -->
    <div class="bookings-controls">
        <div class="bookings-filters">
            <div class="filter-group">
                <label for="status-filter"><?php _e('Status', 'mobooking'); ?></label>
                <select id="status-filter" name="status" class="filter-select">
                    <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'mobooking'); ?></option>
                    <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Confirmed', 'mobooking'); ?></option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'mobooking'); ?></option>
                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'mobooking'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date-filter"><?php _e('Date Range', 'mobooking'); ?></label>
                <select id="date-filter" name="date_range" class="filter-select">
                    <option value=""><?php _e('All Dates', 'mobooking'); ?></option>
                    <option value="today" <?php selected($date_filter, 'today'); ?>><?php _e('Today', 'mobooking'); ?></option>
                    <option value="this_week" <?php selected($date_filter, 'this_week'); ?>><?php _e('This Week', 'mobooking'); ?></option>
                    <option value="this_month" <?php selected($date_filter, 'this_month'); ?>><?php _e('This Month', 'mobooking'); ?></option>
                    <option value="last_30_days" <?php selected($date_filter, 'last_30_days'); ?>><?php _e('Last 30 Days', 'mobooking'); ?></option>
                </select>
            </div>
            
            <button type="button" class="btn-secondary" id="clear-filters-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
                <?php _e('Clear', 'mobooking'); ?>
            </button>
        </div>
        
        <div class="bookings-search">
            <div class="search-input-group">
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="booking-search" name="search" 
                       placeholder="<?php _e('Search bookings...', 'mobooking'); ?>" 
                       value="<?php echo esc_attr($search_query); ?>" 
                       class="search-input">
                <?php if ($search_query) : ?>
                    <button type="button" id="clear-search" class="clear-search-btn" title="<?php _e('Clear search', 'mobooking'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bookings Content -->
    <div class="bookings-container">
        <?php if (empty($bookings)) : ?>
            <!-- Enhanced Empty State -->
            <div class="bookings-empty-state">
                <div class="empty-state-visual">
                    <div class="empty-state-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                        </svg>
                    </div>
                    <div class="empty-state-sparkles">
                        <div class="sparkle sparkle-1"></div>
                        <div class="sparkle sparkle-2"></div>
                        <div class="sparkle sparkle-3"></div>
                    </div>
                </div>
                <div class="empty-state-content">
                    <?php if ($search_query || $status_filter || $date_filter) : ?>
                        <h2><?php _e('No Bookings Found', 'mobooking'); ?></h2>
                        <p><?php _e('No bookings match your current filters. Try adjusting your search criteria or clearing the filters.', 'mobooking'); ?></p>
                        <div class="empty-state-actions">
                            <button type="button" class="btn-primary" id="clear-all-filters">
                                <?php _e('Clear All Filters', 'mobooking'); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <h2><?php _e('No Bookings Yet', 'mobooking'); ?></h2>
                        <p><?php _e('Your bookings will appear here once customers start booking your services. Make sure your booking form is published and your services are set up.', 'mobooking'); ?></p>
                        <div class="empty-state-actions">
                            <a href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>" class="btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                </svg>
                                <?php _e('Setup Booking Form', 'mobooking'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                                </svg>
                                <?php _e('Manage Services', 'mobooking'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <!-- Enhanced Bookings Grid -->
            <div class="bookings-grid" id="bookings-grid">
                <?php 
                $services_manager = new \MoBooking\Services\ServicesManager();
                foreach ($bookings as $booking) : 
                    $services_data = json_decode($booking->services, true);
                    $services_names = array();
                    
                    if (is_array($services_data)) {
                        foreach ($services_data as $service_id) {
                            $service = $services_manager->get_service($service_id);
                            if ($service) {
                                $services_names[] = $service->name;
                            }
                        }
                    }
                    
                    $service_date = new DateTime($booking->service_date);
                    $created_date = new DateTime($booking->created_at);
                    $now = new DateTime();
                    $days_until = $now->diff($service_date)->days;
                    
                    // Calculate urgency
                    $urgency_class = '';
                    $urgency_text = '';
                    if ($booking->status !== 'completed' && $booking->status !== 'cancelled') {
                        if ($service_date < $now) {
                            $urgency_class = 'overdue';
                            $urgency_text = __('Overdue', 'mobooking');
                        } elseif ($days_until == 0) {
                            $urgency_class = 'today';
                            $urgency_text = __('Today', 'mobooking');
                        } elseif ($days_until == 1) {
                            $urgency_class = 'tomorrow';
                            $urgency_text = __('Tomorrow', 'mobooking');
                        } elseif ($days_until <= 3) {
                            $urgency_class = 'soon';
                            $urgency_text = sprintf(__('In %d days', 'mobooking'), $days_until);
                        }
                    }
                ?>
                    <div class="booking-card status-<?php echo esc_attr($booking->status); ?> <?php echo $urgency_class; ?>" 
                         data-booking-id="<?php echo esc_attr($booking->id); ?>"
                         data-status="<?php echo esc_attr($booking->status); ?>"
                         data-customer="<?php echo esc_attr(strtolower($booking->customer_name . ' ' . $booking->customer_email)); ?>">
                        
                        <!-- Booking Card Header -->
                        <div class="booking-card-header">
                            <div class="booking-id-badge">
                                <span class="booking-id-number">#<?php echo $booking->id; ?></span>
                                <span class="booking-created-date"><?php echo $created_date->format('M j'); ?></span>
                            </div>
                            
                            <div class="booking-status-badge">
                                <span class="status-indicator status-<?php echo esc_attr($booking->status); ?>">
                                    <?php 
                                    switch ($booking->status) {
                                        case 'pending':
                                            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>';
                                            break;
                                        case 'confirmed':
                                            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>';
                                            break;
                                        case 'completed':
                                            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                            break;
                                        case 'cancelled':
                                            echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                                            break;
                                    }
                                    echo ucfirst($booking->status);
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Customer Info -->
                        <div class="booking-customer-info">
                            <div class="customer-avatar">
                                <?php echo strtoupper(substr($booking->customer_name, 0, 2)); ?>
                            </div>
                            <div class="customer-details">
                                <h3 class="customer-name"><?php echo esc_html($booking->customer_name); ?></h3>
                                <p class="customer-email"><?php echo esc_html($booking->customer_email); ?></p>
                                <?php if (!empty($booking->customer_phone)) : ?>
                                    <p class="customer-phone"><?php echo esc_html($booking->customer_phone); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Service Date & Time -->
                        <div class="booking-datetime">
                            <div class="datetime-main">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 2V6M8 2V6M3 10H21"/>
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                </svg>
                                <div class="datetime-text">
                                    <span class="service-date"><?php echo $service_date->format('M j, Y'); ?></span>
                                    <span class="service-time"><?php echo $service_date->format('g:i A'); ?></span>
                                </div>
                            </div>
                            <?php if ($urgency_text) : ?>
                                <div class="urgency-indicator <?php echo $urgency_class; ?>">
                                    <?php echo $urgency_text; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Services List -->
                        <div class="booking-services">
                            <div class="services-header">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                                </svg>
                                <span><?php _e('Services', 'mobooking'); ?></span>
                            </div>
                            <div class="services-list">
                                <?php if (!empty($services_names)) : ?>
                                    <?php foreach (array_slice($services_names, 0, 2) as $service_name) : ?>
                                        <span class="service-tag"><?php echo esc_html($service_name); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($services_names) > 2) : ?>
                                        <span class="service-tag more">+<?php echo count($services_names) - 2; ?> <?php _e('more', 'mobooking'); ?></span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="no-services"><?php _e('No services', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Address -->
                        <div class="booking-address">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span class="address-text"><?php echo esc_html(wp_trim_words($booking->customer_address, 8)); ?></span>
                        </div>
                        
                        <!-- Total Price -->
                        <div class="booking-total">
                            <div class="total-amount">
                                <?php echo function_exists('wc_price') ? wc_price($booking->total_price) : '$' . number_format($booking->total_price, 2); ?>
                            </div>
                            <?php if ($booking->discount_amount > 0) : ?>
                                <div class="discount-applied">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                    </svg>
                                    <?php _e('Discount Applied', 'mobooking'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="booking-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => $booking->id))); ?>" 
                               class="btn-view-booking" title="<?php _e('View Details', 'mobooking'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <?php _e('View', 'mobooking'); ?>
                            </a>
                            
                            <?php if ($booking->status === 'pending') : ?>
                                <button type="button" class="btn-action btn-confirm" 
                                        data-booking-id="<?php echo $booking->id; ?>" 
                                        title="<?php _e('Confirm Booking', 'mobooking'); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                    <?php _e('Confirm', 'mobooking'); ?>
                                </button>
                            <?php elseif ($booking->status === 'confirmed') : ?>
                                <button type="button" class="btn-action btn-complete" 
                                        data-booking-id="<?php echo $booking->id; ?>" 
                                        title="<?php _e('Mark as Completed', 'mobooking'); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                    </svg>
                                    <?php _e('Complete', 'mobooking'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($booking->status, ['pending', 'confirmed'])) : ?>
                                <button type="button" class="btn-action btn-cancel" 
                                        data-booking-id="<?php echo $booking->id; ?>" 
                                        title="<?php _e('Cancel Booking', 'mobooking'); ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="M15 9l-6 6M9 9l6 6"/>
                                    </svg>
                                    <?php _e('Cancel', 'mobooking'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Enhanced Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="bookings-pagination">
                    <div class="pagination-info">
                        <?php 
                        $start = ($page - 1) * $per_page + 1;
                        $end = min($page * $per_page, $total_bookings);
                        printf(__('Showing %d-%d of %d bookings', 'mobooking'), $start, $end, $total_bookings); 
                        ?>
                    </div>
                    
                    <div class="pagination-controls">
                        <?php if ($page > 1) : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $page - 1)); ?>" class="pagination-btn pagination-prev">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 18l-6-6 6-6"/>
                                </svg>
                                <?php _e('Previous', 'mobooking'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-numbers">
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<a href="' . esc_url(add_query_arg('paged', 1)) . '" class="pagination-number">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<span class="pagination-number current">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . esc_url(add_query_arg('paged', $i)) . '" class="pagination-number">' . $i . '</a>';
                                }
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="pagination-ellipsis">...</span>';
                                }
                                echo '<a href="' . esc_url(add_query_arg('paged', $total_pages)) . '" class="pagination-number">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>
                        
                        <?php if ($page < $total_pages) : ?>
                            <a href="<?php echo esc_url(add_query_arg('paged', $page + 1)); ?>" class="pagination-btn pagination-next">
                                <?php _e('Next', 'mobooking'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced JavaScript -->
<script>
jQuery(document).ready(function($) {
    const BookingsManager = {
        init: function() {
            this.attachEventListeners();
            this.initializeSearch();
            this.initializeFilters();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Status update buttons
            $('.btn-confirm, .btn-complete, .btn-cancel').on('click', function() {
                self.updateBookingStatus($(this));
            });
            
            // Search functionality
            $('#booking-search').on('input', function() {
                self.performSearch($(this).val());
            });
            
            // Clear search
            $('#clear-search').on('click', function() {
                $('#booking-search').val('');
                self.performSearch('');
            });
            
            // Filter changes
            $('#status-filter, #date-filter').on('change', function() {
                self.applyFilters();
            });
            
            // Clear filters
            $('#clear-filters-btn, #clear-all-filters').on('click', function() {
                self.clearAllFilters();
            });
            
            // Export bookings
            $('#export-bookings-btn').on('click', function() {
                self.exportBookings();
            });
            
            // Refresh
            $('#refresh-bookings-btn').on('click', function() {
                location.reload();
            });
            
            // Booking card clicks
            $('.booking-card').on('click', function(e) {
                if (!$(e.target).closest('.booking-actions').length) {
                    const bookingId = $(this).data('booking-id');
                    window.location.href = '<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => ''))); ?>' + bookingId;
                }
            });
        },
        
        updateBookingStatus: function($button) {
            const bookingId = $button.data('booking-id');
            let status = '';
            let confirmMessage = '';
            
            if ($button.hasClass('btn-confirm')) {
                status = 'confirmed';
                confirmMessage = '<?php _e('Confirm this booking?', 'mobooking'); ?>';
            } else if ($button.hasClass('btn-complete')) {
                status = 'completed';
                confirmMessage = '<?php _e('Mark this booking as completed?', 'mobooking'); ?>';
            } else if ($button.hasClass('btn-cancel')) {
                status = 'cancelled';
                confirmMessage = '<?php _e('Cancel this booking? This action cannot be undone.', 'mobooking'); ?>';
            }
            
            if (status && confirm(confirmMessage)) {
                $button.prop('disabled', true).addClass('loading');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mobooking_update_booking_status',
                        booking_id: bookingId,
                        status: status,
                        nonce: '<?php echo wp_create_nonce('mobooking-booking-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || '<?php _e('Error updating booking status', 'mobooking'); ?>');
                            $button.prop('disabled', false).removeClass('loading');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error updating booking status', 'mobooking'); ?>');
                        $button.prop('disabled', false).removeClass('loading');
                    }
                });
            }
        },
        
        performSearch: function(query) {
            const $cards = $('.booking-card');
            
            if (!query.trim()) {
                $cards.show();
                return;
            }
            
            const searchTerm = query.toLowerCase();
            
            $cards.each(function() {
                const $card = $(this);
                const searchData = [
                    $card.find('.customer-name').text(),
                    $card.find('.customer-email').text(),
                    $card.find('.booking-id-number').text(),
                    $card.data('customer')
                ].join(' ').toLowerCase();
                
                if (searchData.includes(searchTerm)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
        },
        
        applyFilters: function() {
            const currentUrl = new URL(window.location);
            
            const statusFilter = $('#status-filter').val();
            const dateFilter = $('#date-filter').val();
            
            if (statusFilter) {
                currentUrl.searchParams.set('status', statusFilter);
            } else {
                currentUrl.searchParams.delete('status');
            }
            
            if (dateFilter) {
                currentUrl.searchParams.set('date_range', dateFilter);
            } else {
                currentUrl.searchParams.delete('date_range');
            }
            
            // Reset to first page when applying filters
            currentUrl.searchParams.delete('paged');
            
            window.location.href = currentUrl.toString();
        },
        
        clearAllFilters: function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.delete('status');
            currentUrl.searchParams.delete('date_range');
            currentUrl.searchParams.delete('search');
            currentUrl.searchParams.delete('paged');
            
            window.location.href = currentUrl.toString();
        },
        
        exportBookings: function() {
            const data = [
                ['Booking ID', 'Customer Name', 'Email', 'Phone', 'Service Date', 'Services', 'Address', 'Status', 'Total']
            ];
            
            $('.booking-card:visible').each(function() {
                const $card = $(this);
                const services = $card.find('.service-tag').map(function() { 
                    return $(this).text(); 
                }).get().join(', ');
                
                data.push([
                    $card.find('.booking-id-number').text(),
                    $card.find('.customer-name').text(),
                    $card.find('.customer-email').text(),
                    $card.find('.customer-phone').text() || '',
                    $card.find('.service-date').text() + ' ' + $card.find('.service-time').text(),
                    services,
                    $card.find('.address-text').text(),
                    $card.find('.status-indicator').text().trim(),
                    $card.find('.total-amount').text()
                ]);
            });
            
            this.downloadCSV(data, 'bookings-export-' + new Date().toISOString().split('T')[0] + '.csv');
        },
        
        downloadCSV: function(data, filename) {
            const csvContent = data.map(row => 
                row.map(field => '"' + String(field).replace(/"/g, '""') + '"').join(',')
            ).join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        initializeSearch: function() {
            // Enable real-time search
            let searchTimeout;
            $('#booking-search').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                
                searchTimeout = setTimeout(() => {
                    if (query.length >= 2 || query.length === 0) {
                        // Update URL with search parameter
                        const currentUrl = new URL(window.location);
                        if (query) {
                            currentUrl.searchParams.set('search', query);
                        } else {
                            currentUrl.searchParams.delete('search');
                        }
                        
                        // For now, just filter visible cards
                        // In a full implementation, you might want to reload with server-side search
                        this.performSearch(query);
                    }
                }.bind(this), 300);
            }.bind(this));
        },
        
        initializeFilters: function() {
            // Update filter counts
            this.updateFilterCounts();
        },
        
        updateFilterCounts: function() {
            const statusCounts = {};
            $('.booking-card').each(function() {
                const status = $(this).data('status');
                statusCounts[status] = (statusCounts[status] || 0) + 1;
            });
            
            $('#status-filter option').each(function() {
                const $option = $(this);
                const value = $option.val();
                if (value && statusCounts[value]) {
                    const originalText = $option.text().split(' (')[0];
                    $option.text(originalText + ' (' + statusCounts[value] + ')');
                }
            });
        }
    };
    
    BookingsManager.init();
});
</script>

<style>
/* Enhanced Bookings Section Styles */
.bookings-section {
    animation: fadeIn 0.6s ease-out;
}

/* Header Styles */
.bookings-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.bookings-title-group h1 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.title-icon {
    color: hsl(var(--primary));
}

.bookings-subtitle {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 1rem;
}

.bookings-actions {
    display: flex;
    gap: 0.75rem;
}

/* Enhanced Stats Grid */
.bookings-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card.has-alert {
    animation: pulse 2s infinite;
}

.stat-card-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
    flex-shrink: 0;
}

.pending-bookings .stat-card-icon {
    background: linear-gradient(135deg, hsl(var(--warning)), hsl(var(--warning) / 0.8));
}

.confirmed-bookings .stat-card-icon {
    background: linear-gradient(135deg, hsl(var(--info)), hsl(var(--info) / 0.8));
}

.completed-bookings .stat-card-icon {
    background: linear-gradient(135deg, hsl(var(--success)), hsl(var(--success) / 0.8));
}

.revenue-card .stat-card-icon {
    background: linear-gradient(135deg, #10b981, #059669);
}

.cancelled-bookings .stat-card-icon {
    background: linear-gradient(135deg, hsl(var(--destructive)), hsl(var(--destructive) / 0.8));
}

.stat-card-content {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
    color: hsl(var(--foreground));
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.5rem;
}

.stat-trend, .stat-subinfo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.stat-alert {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: hsl(var(--warning));
    font-weight: 500;
}

.alert-pulse {
    width: 8px;
    height: 8px;
    background: hsl(var(--warning));
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

/* Controls */
.bookings-controls {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
}

.bookings-filters {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.75rem;
    font-weight: 500;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-select {
    padding: 0.625rem 0.875rem;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
    min-width: 140px;
}

.bookings-search {
    position: relative;
}

.search-input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    color: hsl(var(--muted-foreground));
    z-index: 1;
}

.search-input {
    padding: 0.625rem 0.875rem 0.625rem 2.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
    min-width: 250px;
    transition: all 0.2s ease;
}

.search-input:focus {
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.clear-search-btn {
    position: absolute;
    right: 0.5rem;
    background: none;
    border: none;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.clear-search-btn:hover {
    background: hsl(var(--accent));
    color: hsl(var(--foreground));
}

/* Bookings Grid */
.bookings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.booking-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.booking-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: hsl(var(--border));
    transition: all 0.3s ease;
}

.booking-card.status-pending::before {
    background: linear-gradient(90deg, hsl(var(--warning)), hsl(var(--warning) / 0.6));
}

.booking-card.status-confirmed::before {
    background: linear-gradient(90deg, hsl(var(--info)), hsl(var(--info) / 0.6));
}

.booking-card.status-completed::before {
    background: linear-gradient(90deg, hsl(var(--success)), hsl(var(--success) / 0.6));
}

.booking-card.status-cancelled::before {
    background: linear-gradient(90deg, hsl(var(--destructive)), hsl(var(--destructive) / 0.6));
}

.booking-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px hsl(var(--primary) / 0.1);
    border-color: hsl(var(--primary) / 0.3);
}

.booking-card.today::before,
.booking-card.tomorrow::before {
    background: linear-gradient(90deg, #f59e0b, #d97706);
    animation: pulse 2s infinite;
}

.booking-card.overdue::before {
    background: linear-gradient(90deg, hsl(var(--destructive)), #dc2626);
    animation: pulse 1.5s infinite;
}

/* Card Header */
.booking-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.booking-id-badge {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.booking-id-number {
    font-weight: 700;
    color: hsl(var(--primary));
    font-family: ui-monospace, 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    font-size: 0.875rem;
}

.booking-created-date {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.booking-status-badge .status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.status-pending {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
    border: 1px solid hsl(var(--warning) / 0.2);
}

.status-confirmed {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
    border: 1px solid hsl(var(--info) / 0.2);
}

.status-completed {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
    border: 1px solid hsl(var(--success) / 0.2);
}

.status-cancelled {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border: 1px solid hsl(var(--destructive) / 0.2);
}

/* Customer Info */
.booking-customer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.customer-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.customer-details h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.customer-email,
.customer-phone {
    margin: 0;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

/* DateTime */
.booking-datetime {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: 8px;
}

.datetime-main {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.datetime-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.service-date {
    font-weight: 500;
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.service-time {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.urgency-indicator {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.625rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.urgency-indicator.today {
    background: #fef3c7;
    color: #92400e;
}

.urgency-indicator.tomorrow {
    background: #dbeafe;
    color: #1e40af;
}

.urgency-indicator.overdue {
    background: #fee2e2;
    color: #991b1b;
}

.urgency-indicator.soon {
    background: #f3e8ff;
    color: #7c3aed;
}

/* Services */
.booking-services {
    margin-bottom: 1rem;
}

.services-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.services-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.service-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid hsl(var(--primary) / 0.2);
}

.service-tag.more {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    border-color: hsl(var(--border));
}

.no-services {
    color: hsl(var(--muted-foreground));
    font-style: italic;
    font-size: 0.75rem;
}

/* Address */
.booking-address {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: hsl(var(--muted) / 0.2);
    border-radius: 8px;
}

.address-text {
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    line-height: 1.4;
}

/* Total Price */
.booking-total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-bottom: 1rem;
}

.total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.discount-applied {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: hsl(var(--success));
}

/* Actions */
.booking-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    border-top: 1px solid hsl(var(--border));
    padding-top: 1rem;
}

.btn-view-booking {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.875rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.2s ease;
    flex: 1;
    justify-content: center;
}

.btn-view-booking:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.625rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-action:hover {
    border-color: hsl(var(--primary));
    color: hsl(var(--primary));
}

.btn-confirm {
    border-color: hsl(var(--success) / 0.3);
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.btn-confirm:hover {
    background: hsl(var(--success) / 0.2);
    border-color: hsl(var(--success));
}

.btn-complete {
    border-color: hsl(var(--info) / 0.3);
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
}

.btn-complete:hover {
    background: hsl(var(--info) / 0.2);
    border-color: hsl(var(--info));
}

.btn-cancel {
    border-color: hsl(var(--destructive) / 0.3);
    background: hsl(var(--destructive) / 0.1);
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
    width: 12px;
    height: 12px;
    border: 2px solid currentColor;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 0.25rem;
}

/* Pagination */
.bookings-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
}

.pagination-info {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-btn {
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

.pagination-btn:hover {
    background: hsl(var(--accent));
}

.pagination-numbers {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0 1rem;
}

.pagination-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.pagination-number:hover {
    background: hsl(var(--accent));
    color: hsl(var(--foreground));
}

.pagination-number.current {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.pagination-ellipsis {
    padding: 0 0.5rem;
    color: hsl(var(--muted-foreground));
}

/* Empty State */
.bookings-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: hsl(var(--card));
    border: 2px dashed hsl(var(--border));
    border-radius: 12px;
    position: relative;
    overflow: hidden;
}

.empty-state-visual {
    position: relative;
    margin-bottom: 2rem;
}

.empty-state-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 5rem;
    height: 5rem;
    background: hsl(var(--muted) / 0.5);
    border-radius: 50%;
    color: hsl(var(--muted-foreground));
    margin-bottom: 1rem;
}

.empty-state-sparkles {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 100px;
    pointer-events: none;
}

.sparkle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: hsl(var(--primary));
    border-radius: 50%;
    animation: sparkle 2s infinite;
}

.sparkle-1 {
    top: 20%;
    left: 20%;
    animation-delay: 0s;
}

.sparkle-2 {
    top: 40%;
    right: 20%;
    animation-delay: 0.7s;
}

.sparkle-3 {
    bottom: 30%;
    left: 30%;
    animation-delay: 1.4s;
}

.empty-state-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.75rem 0;
    color: hsl(var(--foreground));
}

.empty-state-content p {
    font-size: 1rem;
    color: hsl(var(--muted-foreground));
    margin: 0 0 2rem 0;
    max-width: 32rem;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.empty-state-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .bookings-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    }
    
    .bookings-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .bookings-filters {
        justify-content: center;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .bookings-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .bookings-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .bookings-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .booking-card {
        padding: 1rem;
    }
    
    .bookings-pagination {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .pagination-numbers {
        margin: 0;
    }
    
    .search-input {
        min-width: 200px;
    }
}

@media (max-width: 480px) {
    .bookings-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .booking-actions {
        flex-direction: column;
    }
    
    .btn-view-booking {
        flex: none;
    }
    
    .empty-state-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .filter-select,
    .search-input {
        min-width: auto;
        width: 100%;
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
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}

/* Print Styles */
@media print {
    .bookings-controls,
    .bookings-actions,
    .booking-actions,
    .bookings-pagination {
        display: none;
    }
    
    .bookings-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .booking-card::before {
        display: none;
    }
    
    .booking-card {
        border: 2px solid #333;
        break-inside: avoid;
        margin-bottom: 1rem;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .booking-card {
        border-width: 2px;
    }
    
    .status-indicator {
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
</style>