<?php
// dashboard/sections/overview.php - Complete Dashboard Overview Section
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize managers and get data
try {
    $bookings_manager = new \MoBooking\Bookings\Manager();
    $services_manager = new \MoBooking\Services\ServicesManager();
    $geography_manager = new \MoBooking\Geography\Manager();
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Failed to initialize managers in overview: ' . $e->getMessage());
    }
    // Create fallback objects
    $bookings_manager = new stdClass();
    $services_manager = new stdClass();
    $geography_manager = new stdClass();
}

// Get dashboard stats - with error handling
$stats = array(
    'total_bookings' => 0,
    'pending_bookings' => 0,
    'confirmed_bookings' => 0,
    'completed_bookings' => 0,
    'total_revenue' => 0,
    'this_month_revenue' => 0,
    'this_week_revenue' => 0,
    'today_revenue' => 0,
    'most_popular_service' => null
);

try {
    if (method_exists($bookings_manager, 'count_user_bookings')) {
        $stats['total_bookings'] = $bookings_manager->count_user_bookings($user_id);
        $stats['pending_bookings'] = $bookings_manager->count_user_bookings($user_id, 'pending');
        $stats['confirmed_bookings'] = $bookings_manager->count_user_bookings($user_id, 'confirmed');
        $stats['completed_bookings'] = $bookings_manager->count_user_bookings($user_id, 'completed');
    }
    
    if (method_exists($bookings_manager, 'calculate_user_revenue')) {
        $stats['total_revenue'] = $bookings_manager->calculate_user_revenue($user_id);
        $stats['this_month_revenue'] = $bookings_manager->calculate_user_revenue($user_id, 'this_month');
        $stats['this_week_revenue'] = $bookings_manager->calculate_user_revenue($user_id, 'this_week');
        $stats['today_revenue'] = $bookings_manager->calculate_user_revenue($user_id, 'today');
    }
    
    if (method_exists($bookings_manager, 'get_most_popular_service')) {
        $stats['most_popular_service'] = $bookings_manager->get_most_popular_service($user_id);
    }
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Error getting stats: ' . $e->getMessage());
    }
}

// Get recent bookings
$recent_bookings = array();
try {
    if (method_exists($bookings_manager, 'get_user_bookings')) {
        $recent_bookings = $bookings_manager->get_user_bookings($user_id, array('limit' => 5, 'order' => 'DESC'));
    }
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Error getting recent bookings: ' . $e->getMessage());
    }
}

// Get user services
$user_services = array();
try {
    if (method_exists($services_manager, 'get_user_services')) {
        $user_services = $services_manager->get_user_services($user_id);
    }
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Error getting user services: ' . $e->getMessage());
    }
}

// Get user areas
$user_areas = array();
try {
    if (method_exists($geography_manager, 'get_user_areas')) {
        $user_areas = $geography_manager->get_user_areas($user_id);
    }
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Error getting user areas: ' . $e->getMessage());
    }
}

// Calculate growth percentages (mock data for now)
$growth_data = array(
    'bookings_growth' => rand(-15, 25),
    'revenue_growth' => rand(-10, 30),
    'customers_growth' => rand(-5, 35)
);

// Get current time
$current_time = current_time('timestamp');
$greeting_hour = date('H', $current_time);

// Determine greeting
if ($greeting_hour < 12) {
    $greeting = __('Good morning', 'mobooking');
} elseif ($greeting_hour < 18) {
    $greeting = __('Good afternoon', 'mobooking');
} else {
    $greeting = __('Good evening', 'mobooking');
}

// Check if this is their first time
$is_first_visit = get_user_meta($user_id, 'mobooking_first_visit', true);
if (!$is_first_visit) {
    update_user_meta($user_id, 'mobooking_first_visit', current_time('mysql'));
    $is_first_visit = true;
} else {
    $is_first_visit = false;
}
?>

<div class="dashboard-overview">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <div class="welcome-text">
                <h1 class="welcome-title">
                    <?php echo $greeting; ?>, <?php echo esc_html($current_user->display_name); ?>! 
                    <span class="wave-emoji">👋</span>
                </h1>
                <?php if ($is_first_visit) : ?>
                    <p class="welcome-subtitle">
                        <?php _e('Welcome to your MoBooking dashboard! Let\'s get started with setting up your services.', 'mobooking'); ?>
                    </p>
                    <div class="quick-setup-actions">
                        <a href="<?php echo esc_url(add_query_arg('section', 'services')); ?>" class="btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            <?php _e('Add Your First Service', 'mobooking'); ?>
                        </a>
                        <a href="<?php echo esc_url(add_query_arg('section', 'areas')); ?>" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <?php _e('Set Service Areas', 'mobooking'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <p class="welcome-subtitle">
                        <?php _e('Here\'s how your business is performing today.', 'mobooking'); ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="welcome-visual">
                <div class="dashboard-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 3H3V10H10V3Z"/>
                        <path d="M21 3H14V10H21V3Z"/>
                        <path d="M21 14H14V21H21V14Z"/>
                        <path d="M10 14H3V21H10V14Z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards Section -->
    <div class="kpi-section">
        <div class="kpi-cards-grid">
            <!-- Total Bookings -->
            <div class="kpi-card total-bookings" data-kpi="bookings">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                        </svg>
                    </div>
                    <div class="kpi-trend <?php echo $growth_data['bookings_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($growth_data['bookings_growth'] >= 0) : ?>
                                <path d="M7 14l5-5 5 5"/>
                            <?php else : ?>
                                <path d="M17 10l-5 5-5-5"/>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo abs($growth_data['bookings_growth']); ?>%</span>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value" data-target="<?php echo $stats['total_bookings']; ?>">
                        <?php echo $stats['total_bookings']; ?>
                    </div>
                    <div class="kpi-label"><?php _e('Total Bookings', 'mobooking'); ?></div>
                    <div class="kpi-breakdown">
                        <span class="pending"><?php echo $stats['pending_bookings']; ?> <?php _e('pending', 'mobooking'); ?></span>
                        <span class="confirmed"><?php echo $stats['confirmed_bookings']; ?> <?php _e('confirmed', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="kpi-card total-revenue" data-kpi="revenue">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="kpi-trend <?php echo $growth_data['revenue_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($growth_data['revenue_growth'] >= 0) : ?>
                                <path d="M7 14l5-5 5 5"/>
                            <?php else : ?>
                                <path d="M17 10l-5 5-5-5"/>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo abs($growth_data['revenue_growth']); ?>%</span>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value" data-target="<?php echo $stats['total_revenue']; ?>">
                        <?php echo function_exists('wc_price') ? wc_price($stats['total_revenue']) : '$' . number_format($stats['total_revenue'], 2); ?>
                    </div>
                    <div class="kpi-label"><?php _e('Total Revenue', 'mobooking'); ?></div>
                    <div class="kpi-breakdown">
                        <span class="this-month"><?php echo function_exists('wc_price') ? wc_price($stats['this_month_revenue']) : '$' . number_format($stats['this_month_revenue'], 2); ?> <?php _e('this month', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>

            <!-- This Month -->
            <div class="kpi-card this-month" data-kpi="month">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 2V6M16 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                            <path d="M8 14H16M8 18H12"/>
                        </svg>
                    </div>
                    <div class="kpi-period">
                        <?php echo date_i18n('M Y'); ?>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value" data-target="<?php echo $stats['this_month_revenue']; ?>">
                        <?php echo function_exists('wc_price') ? wc_price($stats['this_month_revenue']) : '$' . number_format($stats['this_month_revenue'], 2); ?>
                    </div>
                    <div class="kpi-label"><?php _e('This Month', 'mobooking'); ?></div>
                    <div class="kpi-breakdown">
                        <span class="this-week"><?php echo function_exists('wc_price') ? wc_price($stats['this_week_revenue']) : '$' . number_format($stats['this_week_revenue'], 2); ?> <?php _e('this week', 'mobooking'); ?></span>
                        <span class="today"><?php echo function_exists('wc_price') ? wc_price($stats['today_revenue']) : '$' . number_format($stats['today_revenue'], 2); ?> <?php _e('today', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Completion Rate -->
            <div class="kpi-card completion-rate" data-kpi="completion">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="kpi-status">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-content">
                    <?php 
                    $completion_rate = $stats['total_bookings'] > 0 ? round(($stats['completed_bookings'] / $stats['total_bookings']) * 100) : 0;
                    ?>
                    <div class="kpi-main-value" data-target="<?php echo $completion_rate; ?>">
                        <?php echo $completion_rate; ?>%
                    </div>
                    <div class="kpi-label"><?php _e('Completion Rate', 'mobooking'); ?></div>
                    <div class="kpi-breakdown">
                        <span class="completed"><?php echo $stats['completed_bookings']; ?> <?php _e('completed', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="dashboard-main-content">
        <div class="content-grid">
            <!-- Left Column -->
            <div class="content-left">
                <!-- Recent Bookings Widget -->
                <div class="dashboard-widget recent-bookings-widget">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                            </svg>
                            <?php _e('Recent Bookings', 'mobooking'); ?>
                        </h3>
                        <a href="<?php echo esc_url(add_query_arg('section', 'bookings')); ?>" class="widget-action">
                            <?php _e('View All', 'mobooking'); ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                    
                    <div class="widget-content">
                        <?php if (!empty($recent_bookings)) : ?>
                            <div class="bookings-list">
                                <?php foreach (array_slice($recent_bookings, 0, 5) as $booking) : ?>
                                    <div class="booking-item" data-booking-id="<?php echo $booking->id; ?>">
                                        <div class="booking-customer">
                                            <div class="customer-avatar">
                                                <?php echo strtoupper(substr($booking->customer_name, 0, 2)); ?>
                                            </div>
                                            <div class="customer-info">
                                                <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                                <div class="booking-date">
                                                    <?php echo date_i18n('M j, Y g:i A', strtotime($booking->service_date)); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="booking-details">
                                            <div class="booking-price">
                                                <?php echo function_exists('wc_price') ? wc_price($booking->total_price) : '$' . number_format($booking->total_price, 2); ?>
                                            </div>
                                            <div class="booking-status">
                                                <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                                    <?php 
                                                    switch ($booking->status) {
                                                        case 'pending':
                                                            _e('Pending', 'mobooking');
                                                            break;
                                                        case 'confirmed':
                                                            _e('Confirmed', 'mobooking');
                                                            break;
                                                        case 'completed':
                                                            _e('Completed', 'mobooking');
                                                            break;
                                                        case 'cancelled':
                                                            _e('Cancelled', 'mobooking');
                                                            break;
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="booking-actions">
                                            <a href="<?php echo esc_url(add_query_arg(array('section' => 'bookings', 'view' => 'single', 'booking_id' => $booking->id))); ?>" 
                                               class="action-btn view-btn" title="<?php _e('View Details', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                                    </svg>
                                </div>
                                <h4><?php _e('No bookings yet', 'mobooking'); ?></h4>
                                <p><?php _e('Your recent bookings will appear here once customers start booking your services.', 'mobooking'); ?></p>
                                <a href="<?php echo esc_url(add_query_arg('section', 'booking-form')); ?>" class="btn-primary">
                                    <?php _e('Share Your Booking Link', 'mobooking'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Widget -->
                <div class="dashboard-widget quick-actions-widget">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            <?php _e('Quick Actions', 'mobooking'); ?>
                        </h3>
                    </div>
                    
                    <div class="widget-content">
                        <div class="quick-actions-grid">
                            <a href="<?php echo esc_url(add_query_arg('section', 'services')); ?>" class="quick-action">
                                <div class="action-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <div class="action-title"><?php _e('Add Service', 'mobooking'); ?></div>
                                    <div class="action-desc"><?php _e('Create new service', 'mobooking'); ?></div>
                                </div>
                            </a>
                            
                            <a href="<?php echo esc_url(add_query_arg('section', 'discounts')); ?>" class="quick-action">
                                <div class="action-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <div class="action-title"><?php _e('Create Discount', 'mobooking'); ?></div>
                                    <div class="action-desc"><?php _e('Add promo codes', 'mobooking'); ?></div>
                                </div>
                            </a>
                            
                            <a href="<?php echo esc_url(add_query_arg('section', 'areas')); ?>" class="quick-action">
                                <div class="action-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <div class="action-title"><?php _e('Service Areas', 'mobooking'); ?></div>
                                    <div class="action-desc"><?php _e('Manage coverage', 'mobooking'); ?></div>
                                </div>
                            </a>
                            
                            <a href="<?php echo esc_url(add_query_arg('section', 'settings')); ?>" class="quick-action">
                                <div class="action-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                    </svg>
                                </div>
                                <div class="action-content">
                                    <div class="action-title"><?php _e('Settings', 'mobooking'); ?></div>
                                    <div class="action-desc"><?php _e('Configure options', 'mobooking'); ?></div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="content-right">
                <!-- Popular Service Widget -->
                <?php if ($stats['most_popular_service']) : ?>
                    <div class="dashboard-widget popular-service-widget">
                        <div class="widget-header">
                            <h3 class="widget-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <?php _e('Most Popular Service', 'mobooking'); ?>
                            </h3>
                        </div>
                        
                        <div class="widget-content">
                            <div class="popular-service-card">
                                <div class="service-header">
                                    <div class="service-icon">
                                        <?php if (!empty($stats['most_popular_service']->icon)) : ?>
                                            <i class="<?php echo esc_attr($stats['most_popular_service']->icon); ?>"></i>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-badge">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        <?php _e('Popular', 'mobooking'); ?>
                                    </div>
                                </div>
                                
                                <div class="service-info">
                                    <h4 class="service-name"><?php echo esc_html($stats['most_popular_service']->name); ?></h4>
                                    <?php if (!empty($stats['most_popular_service']->description)) : ?>
                                        <p class="service-description"><?php echo esc_html(wp_trim_words($stats['most_popular_service']->description, 15)); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="service-stats">
                                        <div class="stat-item">
<span class="stat-value">$<?php echo number_format($stats['most_popular_service']->price, 2); ?></span>                                            <span class="stat-label"><?php _e('Price', 'mobooking'); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-value"><?php echo $stats['most_popular_service']->duration; ?> <?php _e('min', 'mobooking'); ?></span>
                                            <span class="stat-label"><?php _e('Duration', 'mobooking'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="service-actions">
                                    <a href="<?php echo esc_url(add_query_arg(array('section' => 'services', 'view' => 'edit', 'service_id' => $stats['most_popular_service']->id))); ?>" class="btn-secondary">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"/>
                                        </svg>
                                        <?php _e('Edit Service', 'mobooking'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Business Setup Widget -->
                <div class="dashboard-widget setup-widget">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                            </svg>
                            <?php _e('Setup Progress', 'mobooking'); ?>
                        </h3>
                    </div>
                    
                    <div class="widget-content">
                        <?php
                        // Calculate setup progress
                        $setup_steps = array(
                            'services' => !empty($user_services),
                            'areas' => !empty($user_areas),
                            'settings' => !empty($settings->company_name),
                            'branding' => !empty($settings->logo_url) || !empty($settings->primary_color)
                        );
                        
                        $completed_steps = count(array_filter($setup_steps));
                        $total_steps = count($setup_steps);
                        $progress_percentage = ($completed_steps / $total_steps) * 100;
                        ?>
                        
                        <div class="setup-progress">
                            <div class="progress-header">
                                <span class="progress-text"><?php echo $completed_steps; ?> <?php _e('of', 'mobooking'); ?> <?php echo $total_steps; ?> <?php _e('completed', 'mobooking'); ?></span>
                                <span class="progress-percentage"><?php echo round($progress_percentage); ?>%</span>
                            </div>
                            
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="setup-checklist">
                            <div class="checklist-item <?php echo $setup_steps['services'] ? 'completed' : 'pending'; ?>">
                                <div class="item-status">
                                    <?php if ($setup_steps['services']) : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6L9 17l-5-5"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="item-content">
                                    <div class="item-title"><?php _e('Add Services', 'mobooking'); ?></div>
                                    <div class="item-description"><?php _e('Create your first service offering', 'mobooking'); ?></div>
                                </div>
                                <?php if (!$setup_steps['services']) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('section', 'services')); ?>" class="item-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="checklist-item <?php echo $setup_steps['areas'] ? 'completed' : 'pending'; ?>">
                                <div class="item-status">
                                    <?php if ($setup_steps['areas']) : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6L9 17l-5-5"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="item-content">
                                    <div class="item-title"><?php _e('Set Service Areas', 'mobooking'); ?></div>
                                    <div class="item-description"><?php _e('Define where you provide services', 'mobooking'); ?></div>
                                </div>
                                <?php if (!$setup_steps['areas']) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('section', 'areas')); ?>" class="item-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="checklist-item <?php echo $setup_steps['settings'] ? 'completed' : 'pending'; ?>">
                                <div class="item-status">
                                    <?php if ($setup_steps['settings']) : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6L9 17l-5-5"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="item-content">
                                    <div class="item-title"><?php _e('Business Info', 'mobooking'); ?></div>
                                    <div class="item-description"><?php _e('Add your company details', 'mobooking'); ?></div>
                                </div>
                                <?php if (!$setup_steps['settings']) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('section', 'settings')); ?>" class="item-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="checklist-item <?php echo $setup_steps['branding'] ? 'completed' : 'pending'; ?>">
                                <div class="item-status">
                                    <?php if ($setup_steps['branding']) : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 6L9 17l-5-5"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="item-content">
                                    <div class="item-title"><?php _e('Customize Branding', 'mobooking'); ?></div>
                                    <div class="item-description"><?php _e('Add logo and brand colors', 'mobooking'); ?></div>
                                </div>
                                <?php if (!$setup_steps['branding']) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('section', 'settings')); ?>" class="item-action">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($progress_percentage == 100) : ?>
                            <div class="setup-complete">
                                <div class="complete-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                    </svg>
                                </div>
                                <div class="complete-text">
                                    <h4><?php _e('Setup Complete!', 'mobooking'); ?></h4>
                                    <p><?php _e('Your business is ready to accept bookings.', 'mobooking'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tips & Resources Widget -->
                <div class="dashboard-widget tips-widget">
                    <div class="widget-header">
                        <h3 class="widget-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                                <path d="M12 17h.01"/>
                            </svg>
                            <?php _e('Tips & Resources', 'mobooking'); ?>
                        </h3>
                    </div>
                    
                    <div class="widget-content">
                        <div class="tips-list">
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 11H5a2 2 0 0 0-2 2v3c0 1.1.9 2 2 2h4m6-6h4a2 2 0 0 1 2 2v3c0 1.1-.9 2-2 2h-4m-6 0a2 2 0 0 0-2-2v-3c0-1.1.9-2 2-2m0 0V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/>
                                    </svg>
                                </div>
                                <div class="tip-content">
                                    <h5><?php _e('Share Your Booking Link', 'mobooking'); ?></h5>
                                    <p><?php _e('Add your booking form link to your website and social media profiles to start receiving bookings.', 'mobooking'); ?></p>
                                    <a href="<?php echo esc_url(add_query_arg('section', 'booking-form')); ?>" class="tip-action">
                                        <?php _e('Get Link', 'mobooking'); ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="tip-item">
                                <div class="tip-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                    </svg>
                                </div>
                                <div class="tip-content">
                                    <h5><?php _e('Create Promotional Codes', 'mobooking'); ?></h5>
                                    <p><?php _e('Attract new customers with discount codes and special offers for your services.', 'mobooking'); ?></p>
                                    <a href="<?php echo esc_url(add_query_arg('section', 'discounts')); ?>" class="tip-action">
                                        <?php _e('Create Discount', 'mobooking'); ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('📊 Dashboard Overview initializing...');
    
    // Animate counters on page load
    // function animateCounters() {
    //     $('.kpi-main-value').each(function() {
    //         const $this = $(this);
    //         const text = $this.text();
    //         const isPrice = text.includes(');
    //         const number = parseFloat(text.replace(/[^0-9.]/g, ''));
            
    //         if (!isNaN(number) && number > 0) {
    //             $this.prop('Counter', 0).animate({
    //                 Counter: number
    //             }, {
    //                 duration: 2000,
    //                 easing: 'easeOutCubic',
    //                 step: function (now) {
    //                     if (isPrice) {
    //                         $this.text(' + Math.ceil(now).toLocaleString());
    //                     } else {
    //                         $this.text(Math.ceil(now).toLocaleString());
    //                     }
    //                 },
    //                 complete: function() {
    //                     if (isPrice) {
    //                         $this.text(text); // Restore original formatting
    //                     } else {
    //                         $this.text(number.toLocaleString());
    //                     }
    //                 }
    //             });
    //         }
    //     });
    // }
    
    // Animate progress bars
    function animateProgressBars() {
        $('.progress-fill').each(function() {
            const $this = $(this);
            const width = $this.data('width') || $this.css('width');
            $this.css('width', '0%').animate({
                width: width
            }, 1500, 'easeOutCubic');
        });
    }
    
    // Add loading states to quick actions
    $('.quick-action').on('click', function() {
        $(this).addClass('loading');
    });
    
    
    // Initialize animations with delay for better UX
    setTimeout(() => {
        // animateCounters();
        animateProgressBars();
    }, 500);
    
    // Refresh stats periodically (every 5 minutes)
    setInterval(function() {
        refreshDashboardStats();
    }, 300000);
    
    function refreshDashboardStats() {
        $.ajax({
            url: mobookingDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_get_dashboard_stats',
                nonce: mobookingDashboard.nonces.service
            },
            success: function(response) {
                if (response.success && response.data.stats) {
                    updateStats(response.data.stats);
                }
            },
            error: function() {
                console.log('Failed to refresh dashboard stats');
            }
        });
    }
    
    function updateStats(stats) {
        // Update KPI values with animation
        Object.keys(stats).forEach(key => {
            const $element = $(`[data-kpi="${key}"] .kpi-main-value`);
            if ($element.length) {
                const newValue = stats[key];
                const currentValue = parseFloat($element.text().replace(/[^0-9.]/g, ''));
                
                if (newValue !== currentValue) {
                    $element.addClass('updating');
                    setTimeout(() => {
                        $element.text(newValue).removeClass('updating');
                    }, 300);
                }
            }
        });
    }
    
    // Add click-to-copy functionality for stats
    $('.kpi-main-value').on('click', function() {
        const text = $(this).text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Copied to clipboard!', 'success');
            });
        }
    });
    
    // Show notification function
    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="dashboard-notification ${type}">
                <span>${message}</span>
            </div>
        `);
        
        $('body').append(notification);
        
        setTimeout(() => {
            notification.addClass('show');
        }, 100);
        
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.which) {
                case 83: // Ctrl+S - Go to Services
                    e.preventDefault();
                    window.location.href = '<?php echo esc_js(add_query_arg('section', 'services')); ?>';
                    break;
                case 66: // Ctrl+B - Go to Bookings
                    e.preventDefault();
                    window.location.href = '<?php echo esc_js(add_query_arg('section', 'bookings')); ?>';
                    break;
                case 68: // Ctrl+D - Go to Discounts
                    e.preventDefault();
                    window.location.href = '<?php echo esc_js(add_query_arg('section', 'discounts')); ?>';
                    break;
            }
        }
    });
    
    // Performance monitoring
    const perfStart = performance.now();
    $(window).on('load', function() {
        const loadTime = performance.now() - perfStart;
        console.log(`📊 Dashboard loaded in ${Math.round(loadTime)}ms`);
    });
    
    console.log('✅ Dashboard Overview ready');
});
</script>

<?php
// Enqueue additional scripts for overview functionality
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-effects-core');

// Add custom easing
wp_add_inline_script('mobooking-dashboard', '
jQuery.easing.easeOutCubic = function (x, t, b, c, d) {
    return c*((t=t/d-1)*t*t + 1) + b;
};
');
?>