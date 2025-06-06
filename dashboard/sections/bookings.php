<?php
// dashboard/sections/bookings.php - ENHANCED Responsive Bookings Management Section
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
$view_mode = isset($_GET['view_mode']) ? sanitize_text_field($_GET['view_mode']) : 'cards';

// Pagination
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = ($view_mode === 'compact') ? 50 : 20;
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

// Get upcoming bookings (next 7 days)
$upcoming_args = array(
    'limit' => 5,
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d', strtotime('+7 days')),
    'status' => 'confirmed'
);
$upcoming_bookings = $bookings_manager->get_user_bookings($user_id, $upcoming_args);
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
                    <?php if ($total_bookings > 0) : ?>
                        <span class="bookings-count"><?php echo number_format($total_bookings); ?></span>
                    <?php endif; ?>
                </h1>
                <p class="page-subtitle">Manage and track your service bookings</p>
            </div>
            
            <div class="header-actions">
                <button type="button" class="btn-secondary" id="bulk-actions-btn" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c1.94 0 3.73.62 5.18 1.67"/>
                    </svg>
                    <span class="selected-count">0</span> Selected
                </button>
                
                <div class="header-action-group">
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
        
        <!-- Quick Insights -->
        <?php if (!empty($upcoming_bookings)) : ?>
            <div class="quick-insights">
                <div class="insight-card">
                    <div class="insight-header">
                        <h3>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            Upcoming This Week
                        </h3>
                        <span class="insight-count"><?php echo count($upcoming_bookings); ?></span>
                    </div>
                    <div class="upcoming-list">
                        <?php foreach (array_slice($upcoming_bookings, 0, 3) as $upcoming) : 
                            $upcoming_date = new DateTime($upcoming->service_date);
                        ?>
                            <div class="upcoming-item">
                                <div class="upcoming-date">
                                    <div class="date-day"><?php echo $upcoming_date->format('d'); ?></div>
                                    <div class="date-month"><?php echo $upcoming_date->format('M'); ?></div>
                                </div>
                                <div class="upcoming-details">
                                    <div class="upcoming-customer"><?php echo esc_html($upcoming->customer_name); ?></div>
                                    <div class="upcoming-time"><?php echo $upcoming_date->format('g:i A'); ?></div>
                                </div>
                                <div class="upcoming-actions">
                                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => $upcoming->id))); ?>" 
                                       class="upcoming-view-btn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 18l6-6-6-6"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Controls Section -->
    <div class="controls-section">
        <div class="controls-left">
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
        </div>
        
        <div class="controls-right">
            <div class="view-toggle">
                <button type="button" class="view-btn <?php echo $view_mode === 'cards' ? 'active' : ''; ?>" data-view="cards">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </button>
                <button type="button" class="view-btn <?php echo $view_mode === 'compact' ? 'active' : ''; ?>" data-view="compact">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/>
                        <line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
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
    </div>
    
    <!-- Bookings Content -->
    <div class="bookings-content">
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
            <!-- Bookings List -->
            <div class="bookings-container">
                <div class="bookings-list <?php echo esc_attr($view_mode); ?>-view">
                    <?php 
                    $services_manager = new \MoBooking\Services\ServicesManager();
                    foreach ($bookings as $booking) : 
                        $services_data = $booking->services;
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
                        $is_past = $service_date < $now;
                        
                        // Calculate urgency
                        $urgency_class = '';
                        if ($booking->status !== 'completed' && $booking->status !== 'cancelled') {
                            if ($is_past) {
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
                        <div class="booking-card <?php echo $urgency_class; ?>" data-booking-id="<?php echo $booking->id; ?>">
                            <div class="booking-card-header">
                                <div class="booking-select">
                                    <input type="checkbox" class="booking-checkbox" data-booking-id="<?php echo $booking->id; ?>">
                                </div>
                                
                                <div class="booking-id-section">
                                    <span class="booking-id">#<?php echo $booking->id; ?></span>
                                    <span class="booking-created"><?php echo $created_date->format('M j'); ?></span>
                                </div>
                                
                                <div class="booking-status">
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
                                    
                                    <?php if ($urgency_class) : ?>
                                        <div class="urgency-indicator <?php echo $urgency_class; ?>">
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
                            </div>
                            
                            <div class="booking-card-body">
                                <div class="booking-main-info">
                                    <div class="customer-section">
                                        <a href="<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => $booking->id))); ?>" 
                                           class="customer-link">
                                            <div class="customer-avatar">
                                                <?php echo strtoupper(substr($booking->customer_name, 0, 2)); ?>
                                            </div>
                                            <div class="customer-details">
                                                <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                                <div class="customer-contact">
                                                    <span class="customer-email"><?php echo esc_html($booking->customer_email); ?></span>
                                                    <?php if (!empty($booking->customer_phone)) : ?>
                                                        <span class="customer-phone"><?php echo esc_html($booking->customer_phone); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    
                                    <div class="service-date-section">
                                        <div class="date-info">
                                            <div class="service-date"><?php echo $service_date->format('M j, Y'); ?></div>
                                            <div class="service-time"><?php echo $service_date->format('g:i A'); ?></div>
                                            <div class="service-day"><?php echo $service_date->format('l'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="booking-secondary-info">
                                    <div class="services-section">
                                        <div class="services-label">Services:</div>
                                        <div class="services-list">
                                            <?php if (!empty($services_names)) : ?>
                                                <?php foreach (array_slice($services_names, 0, 3) as $service_name) : ?>
                                                    <span class="service-tag"><?php echo esc_html($service_name); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($services_names) > 3) : ?>
                                                    <span class="service-tag more">+<?php echo count($services_names) - 3; ?> more</span>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span class="no-services">No services</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="price-section">
                                        <div class="total-amount">
                                            <?php echo $booking->total_price; ?>
                                        </div>
                                        <?php if ($booking->discount_amount > 0) : ?>
                                            <div class="discount-indicator">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                                </svg>
                                                Discount Applied
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($booking->notes)) : ?>
                                    <div class="booking-notes">
                                        <div class="notes-label">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14,2 14,8 20,8"/>
                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                            </svg>
                                            Notes:
                                        </div>
                                        <div class="notes-content"><?php echo esc_html(wp_trim_words($booking->notes, 15)); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="booking-card-footer">
                                <div class="booking-actions">
                                    <button type="button" class="action-btn view-btn" 
                                            onclick="window.location.href='<?php echo esc_url(add_query_arg(array('view' => 'booking', 'booking_id' => $booking->id))); ?>'"
                                            title="<?php _e('View Details', 'mobooking'); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        View
                                    </button>
                                    
                                    <?php if ($booking->status === 'pending') : ?>
                                        <button type="button" class="action-btn confirm-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('Confirm Booking', 'mobooking'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                <polyline points="22,4 12,14.01 9,11.01"/>
                                            </svg>
                                            Confirm
                                        </button>
                                    <?php elseif ($booking->status === 'confirmed') : ?>
                                        <button type="button" class="action-btn complete-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('Mark as Completed', 'mobooking'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                            Complete
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($booking->status, ['pending', 'confirmed'])) : ?>
                                        <button type="button" class="action-btn cancel-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('Cancel Booking', 'mobooking'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M15 9l-6 6M9 9l6 6"/>
                                            </svg>
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                    
                                    <div class="more-actions">
                                        <button type="button" class="action-btn more-btn" data-booking-id="<?php echo $booking->id; ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="1"/>
                                                <circle cx="12" cy="5" r="1"/>
                                                <circle cx="12" cy="19" r="1"/>
                                            </svg>
                                        </button>
                                        <div class="more-actions-menu" style="display: none;">
                                            <button type="button" class="menu-action" data-action="duplicate">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                </svg>
                                                Duplicate
                                            </button>
                                            <button type="button" class="menu-action" data-action="send-reminder">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                                    <polyline points="22,6 12,13 2,6"/>
                                                </svg>
                                                Send Reminder
                                            </button>
                                            <button type="button" class="menu-action" data-action="export-pdf">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                    <polyline points="14,2 14,8 20,8"/>
                                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                                    <polyline points="10,9 9,9 8,9"/>
                                                </svg>
                                                Export PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="booking-meta">
                                    <span class="time-info">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12,6 12,12 16,14"/>
                                        </svg>
                                        <?php 
                                        if ($is_past) {
                                            printf(__('%s ago', 'mobooking'), human_time_diff(strtotime($booking->service_date)));
                                        } else {
                                            printf(__('In %s', 'mobooking'), human_time_diff(time(), strtotime($booking->service_date)));
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
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

<!-- Bulk Actions Modal -->
<div id="bulk-actions-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Bulk Actions', 'mobooking'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p><span class="bulk-count">0</span> <?php _e('bookings selected. Choose an action:', 'mobooking'); ?></p>
            <div class="bulk-actions-grid">
                <button type="button" class="bulk-action-btn confirm-bulk" data-action="confirmed">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                    <span><?php _e('Confirm All', 'mobooking'); ?></span>
                </button>
                
                <button type="button" class="bulk-action-btn complete-bulk" data-action="completed">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                    </svg>
                    <span><?php _e('Mark Completed', 'mobooking'); ?></span>
                </button>
                
                <button type="button" class="bulk-action-btn cancel-bulk" data-action="cancelled">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M15 9l-6 6M9 9l6 6"/>
                    </svg>
                    <span><?php _e('Cancel All', 'mobooking'); ?></span>
                </button>
                
                <button type="button" class="bulk-action-btn export-bulk" data-action="export">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-15"/>
                        <path d="M7 10l5 5 5-5"/>
                        <path d="M12 15V3"/>
                    </svg>
                    <span><?php _e('Export Selected', 'mobooking'); ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript -->
<script>
jQuery(document).ready(function($) {
    const BookingsManager = {
        selectedBookings: new Set(),
        
        init: function() {
            this.attachEventListeners();
            this.initializeSearch();
            this.initializeFilters();
            this.initializeSelections();
            this.initializeMoreActions();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Status update buttons
            $('.confirm-btn, .complete-btn, .cancel-btn').on('click', function(e) {
                e.stopPropagation();
                self.updateBookingStatus($(this));
            });
            
            // View toggle
            $('.view-btn').on('click', function() {
                const view = $(this).data('view');
                self.changeView(view);
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
            
            // Bulk actions
            $('#bulk-actions-btn').on('click', function() {
                self.showBulkActionsModal();
            });
            
            // More actions menu
            $('.more-btn').on('click', function(e) {
                e.stopPropagation();
                self.toggleMoreActions($(this));
            });
            
            // Click outside to close menus
            $(document).on('click', function() {
                $('.more-actions-menu').hide();
            });
            
            // Prevent menu close when clicking inside
            $('.more-actions-menu').on('click', function(e) {
                e.stopPropagation();
            });
        },
        
        initializeSelections: function() {
            const self = this;
            
            // Individual checkbox selection
            $('.booking-checkbox').on('change', function() {
                const bookingId = $(this).data('booking-id');
                if ($(this).is(':checked')) {
                    self.selectedBookings.add(bookingId);
                    $(this).closest('.booking-card').addClass('selected');
                } else {
                    self.selectedBookings.delete(bookingId);
                    $(this).closest('.booking-card').removeClass('selected');
                }
                self.updateSelectionUI();
            });
            
            // Select all functionality (you can add a select all checkbox)
            $('#select-all-bookings').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.booking-checkbox').prop('checked', isChecked).trigger('change');
            });
        },
        
        updateSelectionUI: function() {
            const count = this.selectedBookings.size;
            $('.selected-count').text(count);
            $('.bulk-count').text(count);
            
            if (count > 0) {
                $('#bulk-actions-btn').show();
            } else {
                $('#bulk-actions-btn').hide();
            }
        },
        
        showBulkActionsModal: function() {
            if (this.selectedBookings.size === 0) return;
            
            $('#bulk-actions-modal').show();
            $('.bulk-count').text(this.selectedBookings.size);
            
            // Attach bulk action handlers
            $('.bulk-action-btn').off('click').on('click', (e) => {
                const action = $(e.currentTarget).data('action');
                this.performBulkAction(action);
            });
        },
        
        performBulkAction: function(action) {
            if (this.selectedBookings.size === 0) return;
            
            const bookingIds = Array.from(this.selectedBookings);
            let confirmMessage = '';
            
            switch(action) {
                case 'confirmed':
                    confirmMessage = `Confirm ${bookingIds.length} booking(s)?`;
                    break;
                case 'completed':
                    confirmMessage = `Mark ${bookingIds.length} booking(s) as completed?`;
                    break;
                case 'cancelled':
                    confirmMessage = `Cancel ${bookingIds.length} booking(s)? This cannot be undone.`;
                    break;
                case 'export':
                    this.exportSelectedBookings();
                    $('#bulk-actions-modal').hide();
                    return;
            }
            
            if (confirm(confirmMessage)) {
                this.processBulkStatusUpdate(bookingIds, action);
            }
        },
        
        processBulkStatusUpdate: function(bookingIds, status) {
            const self = this;
            let completed = 0;
            const total = bookingIds.length;
            
            // Update each booking
            bookingIds.forEach(bookingId => {
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
                        completed++;
                        if (completed === total) {
                            location.reload();
                        }
                    },
                    error: function() {
                        completed++;
                        if (completed === total) {
                            alert('Some updates failed. Please refresh the page.');
                            location.reload();
                        }
                    }
                });
            });
            
            $('#bulk-actions-modal').hide();
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
                            // Update the booking card in place
                            self.updateBookingCardStatus(bookingId, status);
                            self.showNotification('Booking status updated successfully', 'success');
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
        
        updateBookingCardStatus: function(bookingId, newStatus) {
            const $card = $(`.booking-card[data-booking-id="${bookingId}"]`);
            
            // Update status badge
            const $statusBadge = $card.find('.status-badge');
            $statusBadge.removeClass().addClass('status-badge status-' + newStatus);
            
            let statusIcon = '';
            let statusText = '';
            
            switch(newStatus) {
                case 'confirmed':
                    statusIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>';
                    statusText = 'Confirmed';
                    break;
                case 'completed':
                    statusIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                    statusText = 'Completed';
                    break;
                case 'cancelled':
                    statusIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                    statusText = 'Cancelled';
                    break;
            }
            
            $statusBadge.html(statusIcon + statusText);
            
            // Update action buttons
            const $actions = $card.find('.booking-actions');
            $actions.find('.confirm-btn, .complete-btn, .cancel-btn').remove();
            
            if (newStatus === 'confirmed') {
                $actions.prepend(`
                    <button type="button" class="action-btn complete-btn" data-booking-id="${bookingId}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                        </svg>
                        Complete
                    </button>
                    <button type="button" class="action-btn cancel-btn" data-booking-id="${bookingId}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M15 9l-6 6M9 9l6 6"/>
                        </svg>
                        Cancel
                    </button>
                `);
            }
            
            // Re-attach event listeners for new buttons
            this.attachActionListeners($card);
        },
        
        attachActionListeners: function($container) {
            const self = this;
            $container.find('.confirm-btn, .complete-btn, .cancel-btn').off('click').on('click', function(e) {
                e.stopPropagation();
                self.updateBookingStatus($(this));
            });
        },
        
        changeView: function(view) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('view_mode', view);
            window.location.href = currentUrl.toString();
        },
        
        performSearch: function(query) {
            const $cards = $('.booking-card');
            
            if (!query.trim()) {
                $cards.show();
                $('#clear-search').hide();
                return;
            }
            
            $('#clear-search').show();
            const searchTerm = query.toLowerCase();
            
            $cards.each(function() {
                const $card = $(this);
                const searchData = [
                    $card.find('.customer-name').text(),
                    $card.find('.customer-email').text(),
                    $card.find('.booking-id').text(),
                    $card.find('.service-tag').map(function() { return $(this).text(); }).get().join(' ')
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
            
            currentUrl.searchParams.delete('paged'); // Reset pagination
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
        
        initializeSearch: function() {
            // Debounce search input
            let searchTimeout;
            $('#booking-search').on('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performLiveSearch($(e.target).val());
                }, 300);
            });
        },
        
        performLiveSearch: function(query) {
            // For now, we'll do client-side filtering
            // In production, you might want to implement server-side search
            this.performSearch(query);
        },
        
        initializeFilters: function() {
            // Add smooth transitions when filters change
            $('.filter-select').on('change', () => {
                $('.bookings-list').addClass('loading');
                setTimeout(() => this.applyFilters(), 150);
            });
        },
        
        initializeMoreActions: function() {
            const self = this;
            
            // More actions menu handlers
            $('.menu-action').on('click', function() {
                const action = $(this).data('action');
                const bookingId = $(this).closest('.more-actions').find('.more-btn').data('booking-id');
                self.handleMoreAction(action, bookingId);
            });
        },
        
        toggleMoreActions: function($button) {
            const $menu = $button.siblings('.more-actions-menu');
            
            // Close all other menus
            $('.more-actions-menu').not($menu).hide();
            
            // Toggle current menu
            $menu.toggle();
            
            // Position menu if needed
            this.positionMenu($menu);
        },
        
        positionMenu: function($menu) {
            const rect = $menu[0].getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // If menu goes below viewport, show it above the button
            if (rect.bottom > windowHeight) {
                $menu.addClass('menu-up');
            } else {
                $menu.removeClass('menu-up');
            }
        },
        
        handleMoreAction: function(action, bookingId) {
            switch(action) {
                case 'duplicate':
                    this.duplicateBooking(bookingId);
                    break;
                case 'send-reminder':
                    this.sendReminder(bookingId);
                    break;
                case 'export-pdf':
                    this.exportBookingPDF(bookingId);
                    break;
            }
            
            // Close menu
            $('.more-actions-menu').hide();
        },
        
        duplicateBooking: function(bookingId) {
            if (confirm('Create a duplicate of this booking?')) {
                // In a real implementation, you'd make an AJAX call to duplicate
                this.showNotification('Booking duplication feature coming soon!', 'info');
            }
        },
        
        sendReminder: function(bookingId) {
            const $button = $(`.more-btn[data-booking-id="${bookingId}"]`);
            $button.addClass('loading');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mobooking_send_reminder',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce('mobooking-booking-nonce'); ?>'
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Reminder sent successfully!', 'success');
                    } else {
                        this.showNotification('Failed to send reminder', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error sending reminder', 'error');
                },
                complete: () => {
                    $button.removeClass('loading');
                }
            });
        },
        
        exportBookingPDF: function(bookingId) {
            // Open PDF export in new window
            const exportUrl = `<?php echo admin_url('admin-ajax.php'); ?>?action=mobooking_export_booking_pdf&booking_id=${bookingId}&nonce=<?php echo wp_create_nonce('mobooking-export-nonce'); ?>`;
            window.open(exportUrl, '_blank');
        },
        
        exportBookings: function() {
            const exportUrl = `<?php echo admin_url('admin-ajax.php'); ?>?action=mobooking_export_bookings&nonce=<?php echo wp_create_nonce('mobooking-export-nonce'); ?>`;
            window.open(exportUrl, '_blank');
        },
        
        exportSelectedBookings: function() {
            if (this.selectedBookings.size === 0) return;
            
            const bookingIds = Array.from(this.selectedBookings).join(',');
            const exportUrl = `<?php echo admin_url('admin-ajax.php'); ?>?action=mobooking_export_bookings&booking_ids=${bookingIds}&nonce=<?php echo wp_create_nonce('mobooking-export-nonce'); ?>`;
            window.open(exportUrl, '_blank');
        },
        
        showNotification: function(message, type = 'info') {
            // Remove existing notifications
            $('.notification').remove();
            
            const notification = $(`
                <div class="notification notification-${type}">
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            $('body').append(notification);
            
            // Show notification
            setTimeout(() => notification.addClass('show'), 10);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
            
            // Manual close
            notification.find('.notification-close').on('click', () => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            });
        },
        
        // Real-time updates (WebSocket or polling)
        initializeRealTimeUpdates: function() {
            // Poll for updates every 30 seconds
            setInterval(() => {
                this.checkForUpdates();
            }, 30000);
        },
        
        checkForUpdates: function() {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mobooking_check_booking_updates',
                    last_check: localStorage.getItem('last_booking_check') || 0,
                    nonce: '<?php echo wp_create_nonce('mobooking-updates-nonce'); ?>'
                },
                success: (response) => {
                    if (response.success && response.data.has_updates) {
                        this.showUpdateNotification(response.data.updates_count);
                    }
                    localStorage.setItem('last_booking_check', Date.now());
                }
            });
        },
        
        showUpdateNotification: function(count) {
            const notification = $(`
                <div class="update-notification">
                    <span>${count} new booking update(s) available</span>
                    <button class="refresh-btn">Refresh</button>
                </div>
            `);
            
            $('.page-header').after(notification);
            
            notification.find('.refresh-btn').on('click', () => {
                location.reload();
            });
            
            // Auto-hide after 10 seconds
            setTimeout(() => notification.fadeOut(), 10000);
        },
        
        // Keyboard shortcuts
        initializeKeyboardShortcuts: function() {
            $(document).on('keydown', (e) => {
                // Only if not typing in an input
                if (e.target.tagName.toLowerCase() === 'input') return;
                
                switch(e.key) {
                    case 'r':
                        if (e.ctrlKey || e.metaKey) {
                            e.preventDefault();
                            location.reload();
                        }
                        break;
                    case 'f':
                        if (e.ctrlKey || e.metaKey) {
                            e.preventDefault();
                            $('#booking-search').focus();
                        }
                        break;
                    case 'Escape':
                        $('.more-actions-menu').hide();
                        $('#bulk-actions-modal').hide();
                        break;
                }
            });
        }
    };
    
    // Initialize the BookingsManager
    BookingsManager.init();
    BookingsManager.initializeRealTimeUpdates();
    BookingsManager.initializeKeyboardShortcuts();
    
    // Customer name click handler
    $('.customer-link').on('click', function(e) {
        e.preventDefault();
        const bookingId = $(this).closest('.booking-card').data('booking-id');
        
        // Add smooth transition
        $(this).closest('.booking-card').addClass('navigating');
        
        setTimeout(() => {
            window.location.href = $(this).attr('href');
        }, 150);
    });
    
    // Enhanced card interactions
    $('.booking-card').on('mouseenter', function() {
        $(this).addClass('hovered');
    }).on('mouseleave', function() {
        $(this).removeClass('hovered');
    });
    
    // Smooth scrolling for pagination
    $('.pagination-btn, .pagination-number').on('click', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        
        $('html, body').animate({
            scrollTop: $('.bookings-content').offset().top - 100
        }, 500, () => {
            window.location.href = href;
        });
    });
    
    // Touch/swipe support for mobile
    if (window.innerWidth <= 768) {
        let startX = 0;
        let startY = 0;
        
        $('.booking-card').on('touchstart', function(e) {
            startX = e.originalEvent.touches[0].clientX;
            startY = e.originalEvent.touches[0].clientY;
        });
        
        $('.booking-card').on('touchend', function(e) {
            const endX = e.originalEvent.changedTouches[0].clientX;
            const endY = e.originalEvent.changedTouches[0].clientY;
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            
            // Swipe left to show actions
            if (Math.abs(deltaX) > Math.abs(deltaY) && deltaX < -50) {
                $(this).addClass('actions-visible');
            }
            // Swipe right to hide actions
            else if (Math.abs(deltaX) > Math.abs(deltaY) && deltaX > 50) {
                $(this).removeClass('actions-visible');
            }
        });
    }
});

</script>


<style>
/* Enhanced Responsive Bookings Section CSS */

/* ==================================================
   LAYOUT & CONTAINERS
   ================================================== */

.bookings-page {
    animation: fadeInUp 0.6s ease-out;
}

/* Page Header */
.page-header {
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
    color: hsl(var(--foreground));
    line-height: 1.2;
}

.title-icon {
    width: 2rem;
    height: 2rem;
    color: hsl(var(--primary));
    flex-shrink: 0;
}

.bookings-count {
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.page-subtitle {
    margin: 0;
    font-size: 1rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.header-action-group {
    display: flex;
    gap: 0.75rem;
}

/* ==================================================
   ANALYTICS SECTION
   ================================================== */

.analytics-section {
    margin-bottom: 2rem;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, hsl(var(--primary)), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px hsl(var(--primary) / 0.1);
}

.metric-card:hover::before {
    transform: translateX(100%);
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.metric-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
}

.metric-total .metric-icon {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
}

.metric-pending .metric-icon {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.metric-confirmed .metric-icon {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.metric-revenue .metric-icon {
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
}

.metric-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

.metric-trend.positive {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.metric-trend.negative {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
}

.metric-alert-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--warning));
    background: hsl(var(--warning) / 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    position: relative;
}

.alert-pulse {
    width: 0.5rem;
    height: 0.5rem;
    background: hsl(var(--warning));
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.2);
    }
}

.metric-content {
    text-align: left;
}

.metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    line-height: 1;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.metric-subtitle {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

/* Quick Insights */
.quick-insights {
    margin-top: 1.5rem;
}

.insight-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    padding: 1.5rem;
}

.insight-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.insight-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.insight-count {
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.upcoming-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.upcoming-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: 8px;
    transition: all 0.2s ease;
}

.upcoming-item:hover {
    background: hsl(var(--muted) / 0.3);
    border-color: hsl(var(--primary) / 0.3);
}

.upcoming-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    background: hsl(var(--primary) / 0.1);
    border-radius: 8px;
    padding: 0.5rem;
    min-width: 3rem;
}

.date-day {
    font-size: 1.125rem;
    font-weight: 700;
    color: hsl(var(--primary));
    line-height: 1;
}

.date-month {
    font-size: 0.75rem;
    color: hsl(var(--primary));
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.upcoming-details {
    flex: 1;
}

.upcoming-customer {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.125rem;
}

.upcoming-time {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.upcoming-view-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 6px;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    transition: all 0.2s ease;
}

.upcoming-view-btn:hover {
    background: hsl(var(--primary));
    border-color: hsl(var(--primary));
    color: white;
}

/* ==================================================
   CONTROLS SECTION
   ================================================== */

.controls-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
}

.controls-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.controls-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filters-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-select {
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background-color: hsl(var(--background));
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    min-width: 10rem;
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.view-toggle {
    display: flex;
    gap: 0.25rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: calc(var(--radius) + 2px);
    padding: 0.25rem;
}

.view-btn {
    padding: 0.5rem;
    border: none;
    background: none;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.2s ease;
    color: hsl(var(--muted-foreground));
}

.view-btn:hover {
    background: hsl(var(--accent));
    color: hsl(var(--foreground));
}

.view-btn.active {
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    box-shadow: var(--shadow-sm);
}

.search-container {
    position: relative;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    color: hsl(var(--muted-foreground));
    pointer-events: none;
    z-index: 1;
}

.search-input {
    padding: 0.75rem 0.75rem 0.75rem 2.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    background-color: hsl(var(--background));
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    min-width: 16rem;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
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
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
}

/* ==================================================
   BOOKING CARDS
   ================================================== */

.bookings-container {
    position: relative;
}

.bookings-list {
    display: grid;
    gap: 1.5rem;
    transition: opacity 0.3s ease;
}

.bookings-list.cards-view {
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
}

.bookings-list.compact-view {
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

.bookings-list.loading {
    opacity: 0.6;
    pointer-events: none;
}

.booking-card {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
}

.booking-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: transparent;
    transition: all 0.3s ease;
}

.booking-card.overdue::before {
    background: hsl(var(--destructive));
}

.booking-card.today::before {
    background: hsl(var(--warning));
}

.booking-card.tomorrow::before {
    background: hsl(var(--info));
}

.booking-card.soon::before {
    background: hsl(var(--primary));
}

.booking-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px hsl(var(--border) / 0.4);
    border-color: hsl(var(--primary) / 0.3);
}

.booking-card.hovered {
    transform: translateY(-2px);
}

.booking-card.selected {
    border-color: hsl(var(--primary));
    box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2);
}

.booking-card.navigating {
    transform: scale(0.98);
    opacity: 0.8;
}

/* Compact view styling */
.compact-view .booking-card {
    display: flex;
    padding: 1rem;
    align-items: center;
    gap: 1rem;
}

.compact-view .booking-card-header,
.compact-view .booking-card-body,
.compact-view .booking-card-footer {
    padding: 0;
}

.compact-view .booking-card-body {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 2rem;
}

/* Card Components */
.booking-card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.booking-select {
    display: flex;
    align-items: center;
}

.booking-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 4px;
    cursor: pointer;
}

.booking-id-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.booking-id {
    font-weight: 700;
    color: hsl(var(--foreground));
    font-family: ui-monospace, 'SF Mono', 'Monaco', monospace;
}

.booking-created {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.booking-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    border: 1px solid transparent;
}

.status-pending {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
    border-color: hsl(var(--warning) / 0.2);
}

.status-confirmed {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
    border-color: hsl(var(--info) / 0.2);
}

.status-completed {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
    border-color: hsl(var(--success) / 0.2);
}

.status-cancelled {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
    border-color: hsl(var(--destructive) / 0.2);
}

.urgency-indicator {
    font-size: 0.6875rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.urgency-indicator.overdue {
    background: hsl(var(--destructive) / 0.15);
    color: hsl(var(--destructive));
    animation: urgentPulse 2s infinite;
}

.urgency-indicator.today {
    background: hsl(var(--warning) / 0.15);
    color: hsl(var(--warning));
}

.urgency-indicator.tomorrow {
    background: hsl(var(--info) / 0.15);
    color: hsl(var(--info));
}

.urgency-indicator.soon {
    background: hsl(var(--primary) / 0.15);
    color: hsl(var(--primary));
}

@keyframes urgentPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

.booking-card-body {
    padding: 1.5rem;
}

.booking-main-info {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.customer-section {
    flex: 1;
}

.customer-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    padding: 0.5rem;
    border-radius: 8px;
    margin: -0.5rem;
}

.customer-link:hover {
    background: hsl(var(--primary) / 0.05);
    transform: translateX(4px);
}

.customer-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

.customer-avatar::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transform: rotate(45deg);
    transition: all 0.6s ease;
    opacity: 0;
}

.customer-link:hover .customer-avatar::before {
    opacity: 1;
    animation: shine 0.6s ease;
}

@keyframes shine {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}

.customer-details {
    flex: 1;
    min-width: 0;
}

.customer-name {
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
    font-size: 1rem;
    line-height: 1.3;
    transition: color 0.2s ease;
}

.customer-link:hover .customer-name {
    color: hsl(var(--primary));
}

.customer-contact {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.customer-email,
.customer-phone {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.3;
}

.service-date-section {
    flex-shrink: 0;
    text-align: right;
}

.date-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.service-date {
    font-weight: 600;
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.service-time {
    font-weight: 700;
    color: hsl(var(--primary));
    font-size: 1rem;
}

.service-day {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.booking-secondary-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.services-section {
    flex: 1;
}

.services-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.services-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.service-tag {
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid hsl(var(--border));
    transition: all 0.2s ease;
}

.service-tag:hover {
    background: hsl(var(--accent));
    transform: translateY(-1px);
}

.service-tag.more {
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border-color: hsl(var(--primary) / 0.2);
}

.no-services {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    font-style: italic;
}

.price-section {
    text-align: right;
    flex-shrink: 0;
}

.total-amount {
    font-size: 1.25rem;
    font-weight: 700;
    color: hsl(var(--success));
    margin-bottom: 0.25rem;
}

.discount-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: hsl(var(--success));
    background: hsl(var(--success) / 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    border: 1px solid hsl(var(--success) / 0.2);
}

.booking-notes {
    background: hsl(var(--muted) / 0.3);
    border: 1px solid hsl(var(--border));
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 0.75rem;
}

.notes-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.notes-content {
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    line-height: 1.4;
}

.booking-card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.2), hsl(var(--muted) / 0.1));
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.booking-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    color: hsl(var(--muted-foreground));
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    white-space: nowrap;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

.view-btn:hover {
    background: hsl(var(--primary));
    border-color: hsl(var(--primary));
    color: white;
}

.confirm-btn:hover {
    background: hsl(var(--info));
    border-color: hsl(var(--info));
    color: white;
}

.complete-btn:hover {
    background: hsl(var(--success));
    border-color: hsl(var(--success));
    color: white;
}

.cancel-btn:hover {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: white;
}

.more-actions {
    position: relative;
}

.more-btn {
    padding: 0.5rem;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.more-actions-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    z-index: 10;
    min-width: 12rem;
    overflow: hidden;
}

.more-actions-menu.menu-up {
    top: auto;
    bottom: 100%;
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.menu-action {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem 1rem;
    border: none;
    background: none;
    text-align: left;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid hsl(var(--border));
}

.menu-action:last-child {
    border-bottom: none;
}

.menu-action:hover {
    background: hsl(var(--accent));
}

.booking-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.time-info {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

/* ==================================================
   EMPTY STATE
   ================================================== */

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4rem 2rem;
    margin: 2rem 0;
    border-radius: 16px;
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
    border: 2px dashed hsl(var(--border));
}

.empty-state-icon {
    margin-bottom: 2rem;
    opacity: 0.6;
    color: hsl(var(--muted-foreground));
}

.empty-state-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    color: hsl(var(--foreground));
}

.empty-state-content p {
    font-size: 1rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.6;
    margin: 0 0 2rem 0;
    max-width: 32rem;
}

.empty-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

/* ==================================================
   PAGINATION
   ================================================== */

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid hsl(var(--border));
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

.pagination-btn,
.pagination-number {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    border-radius: 6px;
    font-size: 0.875rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pagination-btn:hover,
.pagination-number:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary) / 0.3);
}

.pagination-number.current {
    background: hsl(var(--primary));
    border-color: hsl(var(--primary));
    color: white;
}

.pagination-ellipsis {
    padding: 0.5rem 0.75rem;
    color: hsl(var(--muted-foreground));
}

/* ==================================================
   MODALS
   ================================================== */

.mobooking-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    background-color: rgb(0 0 0 / 0.8);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobooking-modal:not([style*="display: none"]) {
    opacity: 1;
    visibility: visible;
}

.modal-content {
    background-color: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    box-shadow: var(--shadow-xl);
    width: 90vw;
    max-width: 32rem;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    margin: 1rem;
    animation: modalSlideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
    padding: 1.5rem 1.5rem 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    padding: 0.25rem;
    line-height: 1;
    transition: color 0.2s ease;
}

.modal-close:hover {
    color: hsl(var(--destructive));
}

.modal-body {
    padding: 1.5rem;
}

.bulk-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.bulk-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: hsl(var(--muted-foreground));
}

.bulk-action-btn:hover {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary) / 0.05);
    color: hsl(var(--primary));
    transform: translateY(-2px);
}

.confirm-bulk:hover {
    border-color: hsl(var(--info));
    background: hsl(var(--info) / 0.05);
    color: hsl(var(--info));
}

.complete-bulk:hover {
    border-color: hsl(var(--success));
    background: hsl(var(--success) / 0.05);
    color: hsl(var(--success));
}

.cancel-bulk:hover {
    border-color: hsl(var(--destructive));
    background: hsl(var(--destructive) / 0.05);
    color: hsl(var(--destructive));
}

/* ==================================================
   NOTIFICATIONS
   ================================================== */

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    max-width: 400px;
    min-width: 300px;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification.show {
    transform: translateX(0);
    opacity: 1;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
}

.notification-message {
    flex: 1;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s ease;
}

.notification-close:hover {
    color: hsl(var(--destructive));
}

.notification-success {
    border-left: 4px solid hsl(var(--success));
}

.notification-error {
    border-left: 4px solid hsl(var(--destructive));
}

.notification-info {
    border-left: 4px solid hsl(var(--info));
}

.update-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 999;
    background: hsl(var(--info));
    color: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.875rem;
}

.refresh-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.refresh-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* ==================================================
   RESPONSIVE DESIGN
   ================================================== */

@media (max-width: 1200px) {
    .analytics-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .bookings-list.cards-view {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }
    
    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .page-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .metric-value {
        font-size: 1.75rem;
    }
    
    .controls-section {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }
    
    .controls-left,
    .controls-right {
        justify-content: center;
    }
    
    .filters-container {
        justify-content: center;
    }
    
    .filter-select,
    .search-input {
        min-width: auto;
        width: 100%;
    }
    
    .bookings-list.cards-view {
        grid-template-columns: 1fr;
    }
    
    .booking-main-info {
        flex-direction: column;
        gap: 1rem;
    }
    
    .service-date-section {
        text-align: left;
    }
    
    .date-info {
        align-items: flex-start;
    }
    
    .booking-secondary-info {
        flex-direction: column;
        gap: 1rem;
    }
    
    .price-section {
        text-align: left;
    }
    
    .booking-card-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .booking-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .pagination-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .bulk-actions-grid {
        grid-template-columns: 1fr;
    }
    
    /* Mobile swipe actions */
    .booking-card.actions-visible .booking-actions {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 480px) {
    .title-icon {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .page-title {
        font-size: 1.25rem;
    }
    
    .controls-section {
        padding: 1rem;
    }
    
    .booking-card {
        margin: 0 -0.5rem;
    }
    
    .booking-card-header,
    .booking-card-body,
    .booking-card-footer {
        padding: 1rem;
    }
    
    .customer-avatar {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 0.75rem;
    }
    
    .action-btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.6875rem;
    }
    
    .modal-content {
        margin: 0.5rem;
        max-height: 95vh;
    }
    
    .notification {
        left: 0.5rem;
        right: 0.5rem;
        min-width: auto;
        max-width: none;
    }
}

/* ==================================================
   ACCESSIBILITY & ANIMATIONS
   ================================================== */

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

@media (prefers-contrast: high) {
    .booking-card,
    .metric-card,
    .action-btn,
    .filter-select,
    .search-input {
        border-width: 2px;
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Focus styles for keyboard navigation */
.booking-card:focus-within,
.action-btn:focus,
.filter-select:focus,
.search-input:focus,
.pagination-btn:focus,
.pagination-number:focus {
    outline: 2px solid hsl(var(--ring));
    outline-offset: 2px;
}

/* Loading states */
.loading {
    position: relative;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 1rem;
    height: 1rem;
    border: 2px solid hsl(var(--primary) / 0.3);
    border-top-color: hsl(var(--primary));
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: translate(-50%, -50%) rotate(360deg);
    }
}

/* Print styles */
@media print {
    .header-actions,
    .controls-section,
    .booking-actions,
    .pagination-container,
    .notification,
    .modal-content {
        display: none !important;
    }
    
    .booking-card {
        break-inside: avoid;
        border: 2px solid #333;
        margin-bottom: 1rem;
    }
    
    .bookings-list {
        grid-template-columns: 1fr !important;
        gap: 1rem;
    }
}
</style>