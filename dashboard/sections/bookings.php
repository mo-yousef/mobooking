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

<div class="bookings-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <h1 class="page-title">
                    <svg class="title-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                    </svg>
                    Bookings
                </h1>
                <p class="page-subtitle">Manage and track your service bookings</p>
            </div>
            
            <div class="header-actions">
                <button type="button" class="btn-secondary" id="export-bookings-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-15"/>
                        <path d="M7 10l5 5 5-5"/>
                        <path d="M12 15V3"/>
                    </svg>
                    Export
                </button>
                
                <button type="button" class="btn-primary" id="new-booking-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    New Booking
                </button>
            </div>
        </div>
    </div>
    
    <!-- Analytics Section -->
    <div class="analytics-section">
        <div class="analytics-grid">
            <div class="metric-card metric-total">
                <div class="metric-header">
                    <div class="metric-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 2V6M8 2V6M3 10H21"/>
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        </svg>
                    </div>
                    <span class="metric-trend positive">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 14l5-5 5 5"/>
                        </svg>
                        12%
                    </span>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo number_format($stats['total']); ?></div>
                    <div class="metric-label">Total Bookings</div>
                    <div class="metric-subtitle">All time</div>
                </div>
            </div>
            
            <div class="metric-card metric-pending<?php echo $stats['pending'] > 0 ? ' metric-alert' : ''; ?>">
                <div class="metric-header">
                    <div class="metric-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                    </div>
                    <?php if ($stats['pending'] > 0) : ?>
                        <span class="metric-alert-badge">
                            <div class="alert-pulse"></div>
                            Action needed
                        </span>
                    <?php endif; ?>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo number_format($stats['pending']); ?></div>
                    <div class="metric-label">Pending Review</div>
                    <div class="metric-subtitle">Awaiting confirmation</div>
                </div>
            </div>
            
            <div class="metric-card metric-confirmed">
                <div class="metric-header">
                    <div class="metric-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                    </div>
                    <span class="metric-trend positive">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 14l5-5 5 5"/>
                        </svg>
                        5%
                    </span>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo number_format($stats['confirmed']); ?></div>
                    <div class="metric-label">Confirmed</div>
                    <div class="metric-subtitle">Ready to serve</div>
                </div>
            </div>
            
            <div class="metric-card metric-revenue">
                <div class="metric-header">
                    <div class="metric-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <span class="metric-trend positive">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 14l5-5 5 5"/>
                        </svg>
                        23%
                    </span>
                </div>
                <div class="metric-content">
                    <div class="metric-value"><?php echo function_exists('wc_price') ? wc_price($stats['total_revenue']) : '$' . number_format($stats['total_revenue'], 2); ?></div>
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-subtitle">This month: <?php echo function_exists('wc_price') ? wc_price($stats['this_month_revenue']) : '$' . number_format($stats['this_month_revenue'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="controls-section">
        <div class="filters-container">
            <div class="filter-group">
                <label for="status-filter" class="filter-label">Status</label>
                <select id="status-filter" name="status" class="filter-select">
                    <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'mobooking'); ?></option>
                    <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>><?php _e('Confirmed', 'mobooking'); ?></option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'mobooking'); ?></option>
                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>><?php _e('Cancelled', 'mobooking'); ?></option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date-filter" class="filter-label">Date Range</label>
                <select id="date-filter" name="date_range" class="filter-select">
                    <option value=""><?php _e('All Dates', 'mobooking'); ?></option>
                    <option value="today" <?php selected($date_filter, 'today'); ?>><?php _e('Today', 'mobooking'); ?></option>
                    <option value="this_week" <?php selected($date_filter, 'this_week'); ?>><?php _e('This Week', 'mobooking'); ?></option>
                    <option value="this_month" <?php selected($date_filter, 'this_month'); ?>><?php _e('This Month', 'mobooking'); ?></option>
                    <option value="last_30_days" <?php selected($date_filter, 'last_30_days'); ?>><?php _e('Last 30 Days', 'mobooking'); ?></option>
                </select>
            </div>
            
            <button type="button" class="btn-secondary clear-filters" id="clear-filters-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
                Clear
            </button>
        </div>
        
        <div class="search-container">
            <div class="search-input-wrapper">
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="booking-search" name="search" 
                       placeholder="<?php _e('Search bookings...', 'mobooking'); ?>" 
                       value="<?php echo esc_attr($search_query); ?>" 
                       class="search-input">
                <?php if ($search_query) : ?>
                    <button type="button" id="clear-search" class="clear-search-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="table-container">
        <?php if (empty($bookings)) : ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                    </svg>
                </div>
                <div class="empty-state-content">
                    <?php if ($search_query || $status_filter || $date_filter) : ?>
                        <h3><?php _e('No Bookings Found', 'mobooking'); ?></h3>
                        <p><?php _e('No bookings match your current filters. Try adjusting your search criteria.', 'mobooking'); ?></p>
                        <button type="button" class="btn-primary" id="clear-all-filters">
                            <?php _e('Clear All Filters', 'mobooking'); ?>
                        </button>
                    <?php else : ?>
                        <h3><?php _e('No Bookings Yet', 'mobooking'); ?></h3>
                        <p><?php _e('Your bookings will appear here once customers start booking your services.', 'mobooking'); ?></p>
                        <div class="empty-actions">
                            <a href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>" class="btn-primary">
                                <?php _e('Setup Booking Form', 'mobooking'); ?>
                            </a>
                            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="btn-secondary">
                                <?php _e('Manage Services', 'mobooking'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="id">
                            <div class="th-content">
                                ID
                                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
                                </svg>
                            </div>
                        </th>
                        <th class="sortable" data-sort="customer">
                            <div class="th-content">
                                Customer
                                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
                                </svg>
                            </div>
                        </th>
                        <th class="sortable" data-sort="service_date">
                            <div class="th-content">
                                Service Date
                                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
                                </svg>
                            </div>
                        </th>
                        <th>Services</th>
                        <th>Address</th>
                        <th class="sortable" data-sort="status">
                            <div class="th-content">
                                Status
                                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
                                </svg>
                            </div>
                        </th>
                        <th class="sortable" data-sort="total_price">
                            <div class="th-content">
                                Total
                                <svg class="sort-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
                                </svg>
                            </div>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                        if ($booking->status !== 'completed' && $booking->status !== 'cancelled') {
                            if ($service_date < $now) {
                                $urgency_class = 'overdue';
                            } elseif ($days_until == 0) {
                                $urgency_class = 'today';
                            } elseif ($days_until == 1) {
                                $urgency_class = 'tomorrow';
                            } elseif ($days_until <= 3) {
                                $urgency_class = 'soon';
                            }
                        }
                    ?>
                        <tr class="booking-row <?php echo $urgency_class; ?>" data-booking-id="<?php echo $booking->id; ?>">
                            <td class="booking-id-cell">
                                <div class="booking-id-wrapper">
                                    <span class="booking-id">#<?php echo $booking->id; ?></span>
                                    <span class="booking-created"><?php echo $created_date->format('M j'); ?></span>
                                </div>
                            </td>
                            
                            <td class="customer-cell">
                                <div class="customer-info">
                                    <div class="customer-avatar">
                                        <?php echo strtoupper(substr($booking->customer_name, 0, 2)); ?>
                                    </div>
                                    <div class="customer-details">
                                        <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                        <div class="customer-email"><?php echo esc_html($booking->customer_email); ?></div>
                                        <?php if (!empty($booking->customer_phone)) : ?>
                                            <div class="customer-phone"><?php echo esc_html($booking->customer_phone); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="service-date-cell">
                                <div class="date-info">
                                    <div class="service-date"><?php echo $service_date->format('M j, Y'); ?></div>
                                    <div class="service-time"><?php echo $service_date->format('g:i A'); ?></div>
                                    <?php if ($urgency_class) : ?>
                                        <div class="urgency-badge <?php echo $urgency_class; ?>">
                                            <?php 
                                            switch($urgency_class) {
                                                case 'overdue': _e('Overdue', 'mobooking'); break;
                                                case 'today': _e('Today', 'mobooking'); break;
                                                case 'tomorrow': _e('Tomorrow', 'mobooking'); break;
                                                case 'soon': printf(__('In %d days', 'mobooking'), $days_until); break;
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="services-cell">
                                <div class="services-list">
                                    <?php if (!empty($services_names)) : ?>
                                        <?php foreach (array_slice($services_names, 0, 2) as $service_name) : ?>
                                            <span class="service-tag"><?php echo esc_html($service_name); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($services_names) > 2) : ?>
                                            <span class="service-tag more">+<?php echo count($services_names) - 2; ?></span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="no-services">No services</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="address-cell">
                                <div class="address-info">
                                    <span class="address-text"><?php echo esc_html(wp_trim_words($booking->customer_address, 6)); ?></span>
                                    <span class="zip-code"><?php echo esc_html($booking->zip_code); ?></span>
                                </div>
                            </td>
                            
                            <td class="status-cell">
                                <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                    <?php 
                                    switch ($booking->status) {
                                        case 'pending':
                                            echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>';
                                            _e('Pending', 'mobooking');
                                            break;
                                        case 'confirmed':
                                            echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>';
                                            _e('Confirmed', 'mobooking');
                                            break;
                                        case 'completed':
                                            echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                            _e('Completed', 'mobooking');
                                            break;
                                        case 'cancelled':
                                            echo '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                                            _e('Cancelled', 'mobooking');
                                            break;
                                    }
                                    ?>
                                </span>
                            </td>
                            
                            <td class="total-cell">
                                <div class="total-amount">
                                    <?php echo function_exists('wc_price') ? wc_price($booking->total_price) : '$' . number_format($booking->total_price, 2); ?>
                                </div>
                                <?php if ($booking->discount_amount > 0) : ?>
                                    <div class="discount-indicator">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                        </svg>
                                        Discount
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <td class="actions-cell">
                                <div class="action-buttons">
                                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => $booking->id))); ?>" 
                                       class="action-btn view-btn" title="<?php _e('View Details', 'mobooking'); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    
                                    <?php if ($booking->status === 'pending') : ?>
                                        <button type="button" class="action-btn confirm-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('Confirm Booking', 'mobooking'); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                <polyline points="22,4 12,14.01 9,11.01"/>
                                            </svg>
                                        </button>
                                    <?php elseif ($booking->status === 'confirmed') : ?>
                                        <button type="button" class="action-btn complete-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('Mark as Completed', 'mobooking'); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($booking->status, ['pending', 'confirmed'])) : ?>
                                        <button type="button" class="action-btn cancel-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('Cancel Booking', 'mobooking'); ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M15 9l-6 6M9 9l6 6"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="pagination-container">
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
                                Previous
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
                                Next
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
            this.initializeSorting();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Status update buttons
            $('.confirm-btn, .complete-btn, .cancel-btn').on('click', function() {
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
            
            // Row clicks (view booking)
            $('.booking-row').on('click', function(e) {
                if (!$(e.target).closest('.action-buttons').length) {
                    const bookingId = $(this).data('booking-id');
                    window.location.href = '<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => ''))); ?>' + bookingId;
                }
            });
        },
        
        updateBookingStatus: function($button) {
            const bookingId = $button.data('booking-id');
            let status = '';
            let confirmMessage = '';
            
            if ($button.hasClass('confirm-btn')) {
                status = 'confirmed';
                confirmMessage = '<?php _e('Confirm this booking?', 'mobooking'); ?>';
            } else if ($button.hasClass('complete-btn')) {
                status = 'completed';
                confirmMessage = '<?php _e('Mark this booking as completed?', 'mobooking'); ?>';
            } else if ($button.hasClass('cancel-btn')) {
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
            const $rows = $('.booking-row');
            
            if (!query.trim()) {
                $rows.show();
                return;
            }
            
            const searchTerm = query.toLowerCase();
            
            $rows.each(function() {
                const $row = $(this);
                const searchData = [
                    $row.find('.customer-name').text(),
                    $row.find('.customer-email').text(),
                    $row.find('.booking-id').text(),
                    $row.find('.service-tag').map(function() { return $(this).text(); }).get().join(' ')
                ].join(' ').toLowerCase();
                
                if (searchData.includes(searchTerm)) {
                    $row.show();
                } else {
                    $row.hide();
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
        
        initializeSorting: function() {
            $('.sortable').on('click', function() {
                const $this = $(this);
                const sortBy = $this.data('sort');
                
                // Toggle sort direction
                let direction = $this.hasClass('sort-asc') ? 'desc' : 'asc';
                
                // Remove sort classes from all headers
                $('.sortable').removeClass('sort-asc sort-desc');
                
                // Add appropriate class to current header
                $this.addClass('sort-' + direction);
                
                // Perform sort (simplified client-side sort)
                this.sortTable(sortBy, direction);
            });
        },
        
        sortTable: function(column, direction) {
            const $tbody = $('.bookings-table tbody');
            const $rows = $tbody.find('tr').get();
            
            $rows.sort(function(a, b) {
                const aVal = $(a).find('[data-sort="' + column + '"]').text() || $(a).find('.' + column + '-cell').text();
                const bVal = $(b).find('[data-sort="' + column + '"]').text() || $(b).find('.' + column + '-cell').text();
                
                if (direction === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });
            
            $.each($rows, function(index, row) {
                $tbody.append(row);
            });
        },
        
        exportBookings: function() {
            const data = [
                ['ID', 'Customer Name', 'Email', 'Phone', 'Service Date', 'Services', 'Address', 'Status', 'Total']
            ];
            
            $('.booking-row:visible').each(function() {
                const $row = $(this);
                const services = $row.find('.service-tag').map(function() { 
                    return $(this).text(); 
                }).get().join(', ');
                
                data.push([
                    $row.find('.booking-id').text(),
                    $row.find('.customer-name').text(),
                    $row.find('.customer-email').text(),
                    $row.find('.customer-phone').text() || '',
                    $row.find('.service-date').text() + ' ' + $row.find('.service-time').text(),
                    services,
                    $row.find('.address-text').text(),
                    $row.find('.status-badge').text().trim(),
                    $row.find('.total-amount').text()
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
            let searchTimeout;
            $('#booking-search').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val();
                
                searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }.bind(this), 300);
            }.bind(this));
        },
        
        initializeFilters: function() {
            // Add any filter-specific initialization here
        }
    };
    
    BookingsManager.init();
});
</script>

<style>
/* Enhanced Bookings Page Styles */
.bookings-page {
    background-color: #fdfdfd;
    min-height: 100vh;
    padding: 0;
}

/* Page Header */
.page-header {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 2rem;
    margin-bottom: 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1400px;
    margin: 0 auto;
}

.header-info {
    flex: 1;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
}

.title-icon {
    color: #3b82f6;
    flex-shrink: 0;
}

.page-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 1rem;
    line-height: 1.5;
}

.header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

/* Analytics Section */
.analytics-section {
    background: white;
    padding: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.metric-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.metric-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.metric-card.metric-alert {
    border-color: #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.02), rgba(245, 158, 11, 0.05));
}

.metric-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.metric-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    flex-shrink: 0;
}

.metric-total .metric-icon {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.metric-pending .metric-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.metric-confirmed .metric-icon {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.metric-revenue .metric-icon {
    background: linear-gradient(135deg, #10b981, #059669);
}

.metric-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 600;
}

.metric-trend.positive {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.metric-alert-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border-radius: 16px;
    font-size: 0.75rem;
    font-weight: 500;
}

.alert-pulse {
    width: 6px;
    height: 6px;
    background: #f59e0b;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

.metric-content {
    text-align: left;
}

.metric-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: #111827;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.25rem;
}

.metric-subtitle {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Controls Section */
.controls-section {
    background: white;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.filters-container {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-select {
    padding: 0.625rem 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    min-width: 140px;
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-container {
    flex-shrink: 0;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 0.875rem;
    color: #9ca3af;
    z-index: 1;
}

.search-input {
    padding: 0.625rem 0.875rem 0.625rem 2.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    min-width: 300px;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-search-btn {
    position: absolute;
    right: 0.5rem;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.clear-search-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Table Container */
.table-container {
    background: white;
    margin: 0;
    overflow: hidden;
}

/* Bookings Table */
.bookings-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    background: white;
}

.bookings-table thead {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.bookings-table th {
    text-align: left;
    padding: 1rem;
    font-weight: 600;
    color: #374151;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    background: #f9fafb;
    z-index: 10;
}

.sortable {
    cursor: pointer;
    user-select: none;
    transition: all 0.2s ease;
}

.sortable:hover {
    background: #f3f4f6;
}

.th-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sort-icon {
    opacity: 0.5;
    transition: all 0.2s ease;
}

.sortable.sort-asc .sort-icon,
.sortable.sort-desc .sort-icon {
    opacity: 1;
    color: #3b82f6;
}

.sortable.sort-desc .sort-icon {
    transform: rotate(180deg);
}

.bookings-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.booking-row {
    transition: all 0.2s ease;
    cursor: pointer;
}

.booking-row:hover {
    background: #f9fafb;
}

.booking-row.today {
    background: linear-gradient(90deg, rgba(245, 158, 11, 0.02), transparent);
    border-left: 3px solid #f59e0b;
}

.booking-row.overdue {
    background: linear-gradient(90deg, rgba(239, 68, 68, 0.02), transparent);
    border-left: 3px solid #ef4444;
}

/* Table Cell Styles */
.booking-id-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.booking-id {
    font-weight: 600;
    color: #3b82f6;
    font-family: ui-monospace, 'SF Mono', 'Monaco', monospace;
    font-size: 0.875rem;
}

.booking-created {
    font-size: 0.75rem;
    color: #9ca3af;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.customer-details {
    flex: 1;
    min-width: 0;
}

.customer-name {
    font-weight: 600;
    color: #111827;
    margin-bottom: 0.125rem;
    font-size: 0.875rem;
}

.customer-email,
.customer-phone {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.125rem;
}

.date-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.service-date {
    font-weight: 500;
    color: #111827;
    font-size: 0.875rem;
}

.service-time {
    font-size: 0.75rem;
    color: #6b7280;
}

.urgency-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.625rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.25rem;
    display: inline-block;
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
}

.urgency-badge.soon {
    background: #f3e8ff;
    color: #7c3aed;
}

.services-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.service-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.service-tag.more {
    background: #f3f4f6;
    color: #6b7280;
    border-color: #e5e7eb;
}

.no-services {
    color: #9ca3af;
    font-style: italic;
    font-size: 0.75rem;
}

.address-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.address-text {
    font-size: 0.875rem;
    color: #374151;
    line-height: 1.4;
}

.zip-code {
    font-size: 0.75rem;
    color: #6b7280;
    font-family: ui-monospace, 'SF Mono', 'Monaco', monospace;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
    border: 1px solid;
}

.status-pending {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border-color: rgba(245, 158, 11, 0.3);
}

.status-confirmed {
    background: rgba(6, 182, 212, 0.1);
    color: #0891b2;
    border-color: rgba(6, 182, 212, 0.3);
}

.status-completed {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.3);
}

.total-amount {
    font-weight: 700;
    color: #111827;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.discount-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.625rem;
    color: #059669;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 0.375rem;
    align-items: center;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.action-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}
</style>