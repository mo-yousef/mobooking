<?php
// dashboard/sections/overview.php - Professional Business Overview Dashboard
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current date info
$today = new DateTime();
$current_month = $today->format('Y-m');
$current_year = $today->format('Y');
$last_month = $today->modify('-1 month')->format('Y-m');
$today = new DateTime(); // Reset

// Initialize managers
$bookings_manager = new \MoBooking\Bookings\Manager();
$services_manager = new \MoBooking\Services\ServicesManager();
$geography_manager = new \MoBooking\Geography\Manager();
$discounts_manager = new \MoBooking\Discounts\Manager();

// Get comprehensive stats
$stats = array(
    // Current month stats
    'current_month' => array(
        'bookings' => $bookings_manager->count_user_bookings($user_id, null, $current_month . '-01', $current_month . '-31'),
        'revenue' => $bookings_manager->calculate_user_revenue($user_id, 'this_month'),
        'new_customers' => $bookings_manager->count_new_customers($user_id, $current_month . '-01', $current_month . '-31'),
    ),
    
    // Last month stats for comparison
    'last_month' => array(
        'bookings' => $bookings_manager->count_user_bookings($user_id, null, $last_month . '-01', $last_month . '-31'),
        'revenue' => $bookings_manager->calculate_user_revenue($user_id, 'last_month'),
        'new_customers' => $bookings_manager->count_new_customers($user_id, $last_month . '-01', $last_month . '-31'),
    ),
    
    // Today's stats
    'today' => array(
        'bookings' => $bookings_manager->count_user_bookings($user_id, null, $today->format('Y-m-d'), $today->format('Y-m-d')),
        'revenue' => $bookings_manager->calculate_user_revenue($user_id, 'today'),
        'scheduled' => $bookings_manager->count_user_bookings($user_id, 'confirmed', $today->format('Y-m-d'), $today->format('Y-m-d')),
    ),
    
    // Overall stats
    'total' => array(
        'bookings' => $bookings_manager->count_user_bookings($user_id),
        'revenue' => $bookings_manager->calculate_user_revenue($user_id),
        'customers' => $bookings_manager->count_unique_customers($user_id),
        'avg_booking_value' => $bookings_manager->get_average_booking_value($user_id),
    ),
    
    // Status breakdowns
    'pending' => $bookings_manager->count_user_bookings($user_id, 'pending'),
    'confirmed' => $bookings_manager->count_user_bookings($user_id, 'confirmed'),
    'completed' => $bookings_manager->count_user_bookings($user_id, 'completed'),
    'cancelled' => $bookings_manager->count_user_bookings($user_id, 'cancelled'),
);

// Calculate growth percentages
$booking_growth = $stats['last_month']['bookings'] > 0 ? 
    (($stats['current_month']['bookings'] - $stats['last_month']['bookings']) / $stats['last_month']['bookings']) * 100 : 
    ($stats['current_month']['bookings'] > 0 ? 100 : 0);

$revenue_growth = $stats['last_month']['revenue'] > 0 ? 
    (($stats['current_month']['revenue'] - $stats['last_month']['revenue']) / $stats['last_month']['revenue']) * 100 : 
    ($stats['current_month']['revenue'] > 0 ? 100 : 0);

$customer_growth = $stats['last_month']['new_customers'] > 0 ? 
    (($stats['current_month']['new_customers'] - $stats['last_month']['new_customers']) / $stats['last_month']['new_customers']) * 100 : 
    ($stats['current_month']['new_customers'] > 0 ? 100 : 0);

// Get additional business data
$services = $services_manager->get_user_services($user_id);
$active_services = array_filter($services, function($service) { return $service->status === 'active'; });
$service_areas = $geography_manager->get_user_areas($user_id);
$discounts = $discounts_manager->get_user_discounts($user_id);
$active_discounts = array_filter($discounts, function($discount) { return $discount->active; });

// Get upcoming bookings
$upcoming_bookings = $bookings_manager->get_upcoming_bookings($user_id, 7);
$overdue_bookings = $bookings_manager->get_overdue_bookings($user_id);

// Get recent activity
$recent_bookings = $bookings_manager->get_user_bookings($user_id, array('limit' => 10, 'status' => null));

// Get popular services
$popular_services = $bookings_manager->get_popular_services($user_id, 5);

// Get business performance metrics
$completion_rate = $stats['total']['bookings'] > 0 ? ($stats['completed'] / $stats['total']['bookings']) * 100 : 0;
$cancellation_rate = $stats['total']['bookings'] > 0 ? ($stats['cancelled'] / $stats['total']['bookings']) * 100 : 0;
$booking_conversion = 100 - $cancellation_rate; // Simple conversion rate
?>

<div class="overview-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-main">
                <h1 class="dashboard-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <?php _e('Business Dashboard', 'mobooking'); ?>
                </h1>
                <p class="dashboard-subtitle">
                    <?php 
                    printf(
                        __('Welcome back! Here\'s your business overview for %s', 'mobooking'), 
                        $today->format('F Y')
                    ); 
                    ?>
                </p>
            </div>
            <div class="header-actions">
                <div class="quick-stats">
                    <div class="quick-stat">
                        <span class="stat-value"><?php echo $stats['today']['bookings']; ?></span>
                        <span class="stat-label"><?php _e('Today', 'mobooking'); ?></span>
                    </div>
                    <div class="quick-stat">
                        <span class="stat-value"><?php echo $stats['pending']; ?></span>
                        <span class="stat-label"><?php _e('Pending', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="kpi-section">
        <div class="kpi-grid">
            <!-- Monthly Revenue -->
            <div class="kpi-card revenue-card">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="m17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="kpi-trend <?php echo $revenue_growth >= 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($revenue_growth >= 0) : ?>
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            <?php else : ?>
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                                <polyline points="17 18 23 18 23 12"/>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo number_format(abs($revenue_growth), 1); ?>%</span>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value">
                        <?php echo function_exists('wc_price') ? wc_price($stats['current_month']['revenue']) : '$' . number_format($stats['current_month']['revenue'], 2); ?>
                    </div>
                    <div class="kpi-label"><?php _e('Monthly Revenue', 'mobooking'); ?></div>
                    <div class="kpi-comparison">
                        <?php 
                        printf(
                            __('%s vs last month', 'mobooking'), 
                            ($revenue_growth >= 0 ? '+' : '') . number_format($revenue_growth, 1) . '%'
                        ); 
                        ?>
                    </div>
                </div>
            </div>

            <!-- Monthly Bookings -->
            <div class="kpi-card bookings-card">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="kpi-trend <?php echo $booking_growth >= 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($booking_growth >= 0) : ?>
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            <?php else : ?>
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                                <polyline points="17 18 23 18 23 12"/>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo number_format(abs($booking_growth), 1); ?>%</span>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value"><?php echo $stats['current_month']['bookings']; ?></div>
                    <div class="kpi-label"><?php _e('Monthly Bookings', 'mobooking'); ?></div>
                    <div class="kpi-comparison">
                        <?php 
                        printf(
                            __('%s vs last month', 'mobooking'), 
                            ($booking_growth >= 0 ? '+' : '') . number_format($booking_growth, 1) . '%'
                        ); 
                        ?>
                    </div>
                </div>
            </div>

            <!-- Average Booking Value -->
            <div class="kpi-card avg-value-card">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="kpi-badge">
                        <span><?php _e('AVG', 'mobooking'); ?></span>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value">
                        <?php echo function_exists('wc_price') ? wc_price($stats['total']['avg_booking_value']) : '$' . number_format($stats['total']['avg_booking_value'], 2); ?>
                    </div>
                    <div class="kpi-label"><?php _e('Avg. Booking Value', 'mobooking'); ?></div>
                    <div class="kpi-details">
                        <?php printf(__('From %d total bookings', 'mobooking'), $stats['total']['bookings']); ?>
                    </div>
                </div>
            </div>

            <!-- Customer Acquisition -->
            <div class="kpi-card customers-card">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="m22 21-3-3m0 0a2 2 0 0 0 3-3 2 2 0 0 0-3 3"/>
                        </svg>
                    </div>
                    <div class="kpi-trend <?php echo $customer_growth >= 0 ? 'positive' : 'negative'; ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <?php if ($customer_growth >= 0) : ?>
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            <?php else : ?>
                                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                                <polyline points="17 18 23 18 23 12"/>
                            <?php endif; ?>
                        </svg>
                        <span><?php echo number_format(abs($customer_growth), 1); ?>%</span>
                    </div>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main-value"><?php echo $stats['current_month']['new_customers']; ?></div>
                    <div class="kpi-label"><?php _e('New Customers', 'mobooking'); ?></div>
                    <div class="kpi-comparison">
                        <?php printf(__('%d total customers', 'mobooking'), $stats['total']['customers']); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Business Health Metrics -->
    <div class="health-metrics-section">
        <h2 class="section-title">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
            <?php _e('Business Health', 'mobooking'); ?>
        </h2>
        
        <div class="health-grid">
            <!-- Booking Status Distribution -->
            <div class="health-card">
                <div class="health-header">
                    <h3><?php _e('Booking Status Distribution', 'mobooking'); ?></h3>
                    <div class="health-period"><?php _e('All Time', 'mobooking'); ?></div>
                </div>
                <div class="health-content">
                    <div class="status-distribution">
                        <div class="status-item completed">
                            <div class="status-bar" style="width: <?php echo $stats['total']['bookings'] > 0 ? ($stats['completed'] / $stats['total']['bookings'] * 100) : 0; ?>%"></div>
                            <div class="status-info">
                                <span class="status-label"><?php _e('Completed', 'mobooking'); ?></span>
                                <span class="status-value"><?php echo $stats['completed']; ?></span>
                                <span class="status-percent"><?php echo $stats['total']['bookings'] > 0 ? number_format(($stats['completed'] / $stats['total']['bookings'] * 100), 1) : 0; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="status-item confirmed">
                            <div class="status-bar" style="width: <?php echo $stats['total']['bookings'] > 0 ? ($stats['confirmed'] / $stats['total']['bookings'] * 100) : 0; ?>%"></div>
                            <div class="status-info">
                                <span class="status-label"><?php _e('Confirmed', 'mobooking'); ?></span>
                                <span class="status-value"><?php echo $stats['confirmed']; ?></span>
                                <span class="status-percent"><?php echo $stats['total']['bookings'] > 0 ? number_format(($stats['confirmed'] / $stats['total']['bookings'] * 100), 1) : 0; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="status-item pending">
                            <div class="status-bar" style="width: <?php echo $stats['total']['bookings'] > 0 ? ($stats['pending'] / $stats['total']['bookings'] * 100) : 0; ?>%"></div>
                            <div class="status-info">
                                <span class="status-label"><?php _e('Pending', 'mobooking'); ?></span>
                                <span class="status-value"><?php echo $stats['pending']; ?></span>
                                <span class="status-percent"><?php echo $stats['total']['bookings'] > 0 ? number_format(($stats['pending'] / $stats['total']['bookings'] * 100), 1) : 0; ?>%</span>
                            </div>
                        </div>
                        
                        <div class="status-item cancelled">
                            <div class="status-bar" style="width: <?php echo $stats['total']['bookings'] > 0 ? ($stats['cancelled'] / $stats['total']['bookings'] * 100) : 0; ?>%"></div>
                            <div class="status-info">
                                <span class="status-label"><?php _e('Cancelled', 'mobooking'); ?></span>
                                <span class="status-value"><?php echo $stats['cancelled']; ?></span>
                                <span class="status-percent"><?php echo $stats['total']['bookings'] > 0 ? number_format(($stats['cancelled'] / $stats['total']['bookings'] * 100), 1) : 0; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="health-card">
                <div class="health-header">
                    <h3><?php _e('Performance Metrics', 'mobooking'); ?></h3>
                    <div class="health-period"><?php _e('Overall', 'mobooking'); ?></div>
                </div>
                <div class="health-content">
                    <div class="performance-metrics">
                        <div class="metric-item">
                            <div class="metric-circle" data-percentage="<?php echo number_format($completion_rate, 1); ?>">
                                <svg viewBox="0 0 36 36" class="circular-chart green">
                                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <path class="circle" stroke-dasharray="<?php echo $completion_rate; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <text x="18" y="20.35" class="percentage"><?php echo number_format($completion_rate, 0); ?>%</text>
                                </svg>
                            </div>
                            <div class="metric-info">
                                <span class="metric-label"><?php _e('Completion Rate', 'mobooking'); ?></span>
                                <span class="metric-description"><?php _e('Bookings completed successfully', 'mobooking'); ?></span>
                            </div>
                        </div>
                        
                        <div class="metric-item">
                            <div class="metric-circle" data-percentage="<?php echo number_format($booking_conversion, 1); ?>">
                                <svg viewBox="0 0 36 36" class="circular-chart blue">
                                    <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <path class="circle" stroke-dasharray="<?php echo $booking_conversion; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                    <text x="18" y="20.35" class="percentage"><?php echo number_format($booking_conversion, 0); ?>%</text>
                                </svg>
                            </div>
                            <div class="metric-info">
                                <span class="metric-label"><?php _e('Booking Success', 'mobooking'); ?></span>
                                <span class="metric-description"><?php _e('Non-cancelled bookings', 'mobooking'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Setup Status -->
            <div class="health-card">
                <div class="health-header">
                    <h3><?php _e('Business Setup', 'mobooking'); ?></h3>
                    <div class="setup-completion">
                        <?php 
                        $setup_items = array(
                            'services' => count($active_services) > 0,
                            'areas' => count($service_areas) > 0,
                            'settings' => !empty($settings->company_name),
                            'discounts' => count($active_discounts) > 0,
                        );
                        $completed_items = array_sum($setup_items);
                        $completion_percentage = ($completed_items / count($setup_items)) * 100;
                        ?>
                        <span class="completion-text"><?php echo $completed_items; ?>/<?php echo count($setup_items); ?> <?php _e('Complete', 'mobooking'); ?></span>
                    </div>
                </div>
                <div class="health-content">
                    <div class="setup-checklist">
                        <div class="checklist-item <?php echo $setup_items['services'] ? 'completed' : 'pending'; ?>">
                            <div class="checklist-icon">
                                <?php if ($setup_items['services']) : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                <?php else : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="checklist-content">
                                <span class="checklist-label"><?php _e('Services Created', 'mobooking'); ?></span>
                                <span class="checklist-status"><?php echo count($active_services); ?> <?php _e('active', 'mobooking'); ?></span>
                            </div>
                        </div>
                        
                        <div class="checklist-item <?php echo $setup_items['areas'] ? 'completed' : 'pending'; ?>">
                            <div class="checklist-icon">
                                <?php if ($setup_items['areas']) : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                <?php else : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="checklist-content">
                                <span class="checklist-label"><?php _e('Service Areas', 'mobooking'); ?></span>
                                <span class="checklist-status"><?php echo count($service_areas); ?> <?php _e('areas', 'mobooking'); ?></span>
                            </div>
                        </div>
                        
                        <div class="checklist-item <?php echo $setup_items['settings'] ? 'completed' : 'pending'; ?>">
                            <div class="checklist-icon">
                                <?php if ($setup_items['settings']) : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                <?php else : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="checklist-content">
                                <span class="checklist-label"><?php _e('Business Settings', 'mobooking'); ?></span>
                                <span class="checklist-status"><?php echo $setup_items['settings'] ? __('Configured', 'mobooking') : __('Pending', 'mobooking'); ?></span>
                            </div>
                        </div>
                        
                        <div class="checklist-item <?php echo $setup_items['discounts'] ? 'completed' : 'optional'; ?>">
                            <div class="checklist-icon">
                                <?php if ($setup_items['discounts']) : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                <?php else : ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="checklist-content">
                                <span class="checklist-label"><?php _e('Discount Codes', 'mobooking'); ?></span>
                                <span class="checklist-status"><?php echo count($active_discounts); ?> <?php _e('active', 'mobooking'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Items & Alerts -->
    <?php if (!empty($overdue_bookings) || $stats['pending'] > 5 || !$setup_items['services'] || !$setup_items['areas']) : ?>
    <div class="alerts-section">
        <h2 class="section-title">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m21 16-4 4-4-4"/>
                <path d="M17 20V4"/>
                <path d="m3 8 4-4 4 4"/>
                <path d="M7 4v16"/>
            </svg>
            <?php _e('Action Items', 'mobooking'); ?>
        </h2>
        
        <div class="alerts-grid">
            <?php if (!empty($overdue_bookings)) : ?>
            <div class="alert-card urgent">
                <div class="alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div class="alert-content">
                    <h3><?php _e('Overdue Bookings', 'mobooking'); ?></h3>
                    <p><?php printf(_n('%d booking is overdue', '%d bookings are overdue', count($overdue_bookings), 'mobooking'), count($overdue_bookings)); ?></p>
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/?status=overdue')); ?>" class="alert-action">
                        <?php _e('Review Overdue', 'mobooking'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($stats['pending'] > 5) : ?>
            <div class="alert-card warning">
                <div class="alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/>
                        <path d="M12 9v4"/>
                        <path d="M12 17h.01"/>
                    </svg>
                </div>
                <div class="alert-content">
                    <h3><?php _e('Pending Bookings', 'mobooking'); ?></h3>
                    <p><?php printf(__('%d bookings are waiting for confirmation', 'mobooking'), $stats['pending']); ?></p>
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/?status=pending')); ?>" class="alert-action">
                        <?php _e('Review Pending', 'mobooking'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$setup_items['services']) : ?>
            <div class="alert-card info">
                <div class="alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                </div>
                <div class="alert-content">
                    <h3><?php _e('Setup Required', 'mobooking'); ?></h3>
                    <p><?php _e('Add your first service to start accepting bookings', 'mobooking'); ?></p>
                    <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="alert-action">
                        <?php _e('Add Services', 'mobooking'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$setup_items['areas']) : ?>
            <div class="alert-card info">
                <div class="alert-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
                <div class="alert-content">
                    <h3><?php _e('Service Areas Needed', 'mobooking'); ?></h3>
                    <p><?php _e('Define your service areas to enable ZIP code validation', 'mobooking'); ?></p>
                    <a href="<?php echo esc_url(home_url('/dashboard/areas/')); ?>" class="alert-action">
                        <?php _e('Add Areas', 'mobooking'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity & Insights -->
    <div class="activity-section">
        <div class="activity-grid">
            <!-- Recent Bookings -->
            <div class="activity-card">
                <div class="activity-header">
                    <h3>
                        <svg class="activity-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <?php _e('Recent Bookings', 'mobooking'); ?>
                    </h3>
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="view-all">
                        <?php _e('View All', 'mobooking'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </div>
                <div class="activity-content">
                    <?php if (!empty($recent_bookings)) : ?>
                        <div class="bookings-list">
                            <?php foreach (array_slice($recent_bookings, 0, 5) as $booking) : 
                                $booking_date = new DateTime($booking->service_date);
                                $created_date = new DateTime($booking->created_at);
                            ?>
                                <div class="booking-item">
                                    <div class="booking-avatar">
                                        <?php echo strtoupper(substr($booking->customer_name, 0, 2)); ?>
                                    </div>
                                    <div class="booking-info">
                                        <div class="booking-customer"><?php echo esc_html($booking->customer_name); ?></div>
                                        <div class="booking-details">
                                            <?php echo $booking_date->format('M j, Y g:i A'); ?> • 
                                            <?php echo function_exists('wc_price') ? wc_price($booking->total_price) : ' . number_format($booking->total_price, 2); ?>
                                        </div>
                                    </div>
                                    <div class="booking-status">
                                        <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                            <?php echo ucfirst($booking->status); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <p><?php _e('No bookings yet', 'mobooking'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Schedule -->
            <div class="activity-card">
                <div class="activity-header">
                    <h3>
                        <svg class="activity-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                        <?php _e('Upcoming Schedule', 'mobooking'); ?>
                    </h3>
                    <span class="schedule-count"><?php echo count($upcoming_bookings); ?> <?php _e('this week', 'mobooking'); ?></span>
                </div>
                <div class="activity-content">
                    <?php if (!empty($upcoming_bookings)) : ?>
                        <div class="schedule-list">
                            <?php foreach (array_slice($upcoming_bookings, 0, 5) as $booking) : 
                                $booking_date = new DateTime($booking->service_date);
                                $now = new DateTime();
                                $is_today = $booking_date->format('Y-m-d') === $now->format('Y-m-d');
                                $is_tomorrow = $booking_date->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d');
                                $now = new DateTime(); // Reset
                            ?>
                                <div class="schedule-item <?php echo $is_today ? 'today' : ($is_tomorrow ? 'tomorrow' : ''); ?>">
                                    <div class="schedule-time">
                                        <div class="time-display">
                                            <?php echo $booking_date->format('g:i A'); ?>
                                        </div>
                                        <div class="date-display">
                                            <?php 
                                            if ($is_today) {
                                                echo __('Today', 'mobooking');
                                            } elseif ($is_tomorrow) {
                                                echo __('Tomorrow', 'mobooking');
                                            } else {
                                                echo $booking_date->format('M j');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="schedule-details">
                                        <div class="schedule-customer"><?php echo esc_html($booking->customer_name); ?></div>
                                        <div class="schedule-service">
                                            <?php 
                                            $services_data = json_decode($booking->services, true);
                                            if (is_array($services_data) && !empty($services_data)) {
                                                $service_id = $services_data[0];
                                                $service = $services_manager->get_service($service_id);
                                                echo $service ? esc_html($service->name) : __('Service', 'mobooking');
                                            } else {
                                                echo __('Service', 'mobooking');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="schedule-actions">
                                        <a href="<?php echo esc_url(home_url('/dashboard/bookings/?view=single&booking_id=' . $booking->id)); ?>" class="schedule-link">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m9 18 6-6-6-6"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12,6 12,12 16,14"/>
                            </svg>
                            <p><?php _e('No upcoming bookings', 'mobooking'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Popular Services -->
            <div class="activity-card">
                <div class="activity-header">
                    <h3>
                        <svg class="activity-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <?php _e('Popular Services', 'mobooking'); ?>
                    </h3>
                    <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="view-all">
                        <?php _e('Manage', 'mobooking'); ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 18 6-6-6-6"/>
                        </svg>
                    </a>
                </div>
                <div class="activity-content">
                    <?php if (!empty($popular_services)) : ?>
                        <div class="services-list">
                            <?php foreach (array_slice($popular_services, 0, 5) as $service) : ?>
                                <div class="service-item">
                                    <div class="service-icon">
                                        <?php if (!empty($service->icon)) : ?>
                                            <i class="<?php echo esc_attr($service->icon); ?>"></i>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-info">
                                        <div class="service-name"><?php echo esc_html($service->name); ?></div>
                                        <div class="service-stats">
                                            <?php echo $service->booking_count ?? 0; ?> <?php _e('bookings', 'mobooking'); ?> • 
                                            <?php echo function_exists('wc_price') ? wc_price($service->price) : ' . number_format($service->price, 2); ?>
                                        </div>
                                    </div>
                                    <div class="service-trend">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                        </svg>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                            </svg>
                            <p><?php _e('No services yet', 'mobooking'); ?></p>
                            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="btn-link">
                                <?php _e('Add Your First Service', 'mobooking'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <h2 class="section-title">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            <?php _e('Quick Actions', 'mobooking'); ?>
        </h2>
        
        <div class="quick-actions-grid">
            <a href="<?php echo esc_url(home_url('/dashboard/bookings/?status=pending')); ?>" class="quick-action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                </div>
                <div class="action-content">
                    <h3><?php _e('Review Pending', 'mobooking'); ?></h3>
                    <p><?php printf(_n('%d booking needs attention', '%d bookings need attention', $stats['pending'], 'mobooking'), $stats['pending']); ?></p>
                </div>
                <div class="action-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </div>
            </a>
            
            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="quick-action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </div>
                <div class="action-content">
                    <h3><?php _e('Add Service', 'mobooking'); ?></h3>
                    <p><?php _e('Expand your service offerings', 'mobooking'); ?></p>
                </div>
                <div class="action-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </div>
            </a>
            
            <a href="<?php echo esc_url(home_url('/dashboard/discounts/')); ?>" class="quick-action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                </div>
                <div class="action-content">
                    <h3><?php _e('Create Promotion', 'mobooking'); ?></h3>
                    <p><?php _e('Boost bookings with discounts', 'mobooking'); ?></p>
                </div>
                <div class="action-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </div>
            </a>
            
            <a href="<?php echo esc_url(home_url('/dashboard/settings/')); ?>" class="quick-action-card">
                <div class="action-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </div>
                <div class="action-content">
                    <h3><?php _e('Settings', 'mobooking'); ?></h3>
                    <p><?php _e('Customize your business profile', 'mobooking'); ?></p>
                </div>
                <div class="action-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
/* Professional Overview Dashboard Styles */
.overview-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    animation: fadeIn 0.6s ease-out;
}

/* Header Section */
.dashboard-header {
    background: linear-gradient(135deg, hsl(var(--primary)) 0%, hsl(var(--primary) / 0.8) 100%);
    color: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
    transform: translate(50%, -50%);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    position: relative;
    z-index: 1;
}

.dashboard-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.title-icon {
    width: 32px;
    height: 32px;
}

.dashboard-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0;
    font-weight: 400;
}

.quick-stats {
    display: flex;
    gap: 2rem;
}

.quick-stat {
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.quick-stat .stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.quick-stat .stat-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

/* KPI Section */
.kpi-section {
    margin-bottom: 3rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.kpi-card {
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, hsl(var(--primary)), hsl(var(--primary) / 0.6));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.kpi-card:hover::before {
    transform: scaleX(1);
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px -5px rgba(0, 0, 0, 0.1);
}

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
    color: hsl(var(--primary));
}

.kpi-icon svg {
    width: 24px;
    height: 24px;
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.kpi-trend.positive {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.kpi-trend.negative {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.kpi-trend svg {
    width: 14px;
    height: 14px;
}

.kpi-badge {
    padding: 0.25rem 0.5rem;
    background: hsl(var(--muted));
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
}

.kpi-main-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: hsl(var(--foreground));
    margin-bottom: 0.5rem;
    line-height: 1;
}

.kpi-label {
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.kpi-comparison {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.kpi-details {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

/* Health Metrics Section */
.health-metrics-section {
    margin-bottom: 3rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    margin-bottom: 1.5rem;
}

.section-icon {
    width: 24px;
    height: 24px;
    color: hsl(var(--primary));
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.health-card {
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.health-header {
    padding: 1.5rem 1.5rem 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.health-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: hsl(var(--foreground));
}

.health-period {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    background: hsl(var(--muted));
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.setup-completion {
    font-size: 0.875rem;
    color: hsl(var(--primary));
    font-weight: 600;
}

.health-content {
    padding: 1.5rem;
}

/* Status Distribution */
.status-distribution {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item {
    position: relative;
}

.status-bar {
    height: 8px;
    border-radius: 4px;
    margin-bottom: 0.5rem;
    transition: width 0.6s ease;
}

.status-item.completed .status-bar {
    background: linear-gradient(90deg, #22c55e, #16a34a);
}

.status-item.confirmed .status-bar {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.status-item.pending .status-bar {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

.status-item.cancelled .status-bar {
    background: linear-gradient(90deg, #ef4444, #dc2626);
}

.status-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.status-label {
    font-weight: 500;
    color: hsl(var(--foreground));
}

.status-value {
    font-weight: 600;
    color: hsl(var(--foreground));
}

.status-percent {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

/* Performance Metrics */
.performance-metrics {
    display: flex;
    gap: 2rem;
    justify-content: space-around;
}

.metric-item {
    text-align: center;
}

.metric-circle {
    margin-bottom: 1rem;
}

.circular-chart {
    display: block;
    margin: 0 auto;
    max-width: 80px;
    max-height: 80px;
}

.circle-bg {
    fill: none;
    stroke: #eee;
    stroke-width: 2.8;
}

.circle {
    fill: none;
    stroke-width: 2.8;
    stroke-linecap: round;
    animation: progress 1.5s ease-in-out;
    transform: rotate(-90deg);
    transform-origin: 50% 50%;
}

.circular-chart.green .circle {
    stroke: #22c55e;
}

.circular-chart.blue .circle {
    stroke: #3b82f6;
}

.percentage {
    fill: hsl(var(--foreground));
    font-family: sans-serif;
    font-size: 0.5em;
    font-weight: 600;
    text-anchor: middle;
}

.metric-label {
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.metric-description {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

/* Setup Checklist */
.setup-checklist {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.checklist-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.checklist-item.completed {
    background: rgba(34, 197, 94, 0.05);
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.checklist-item.pending {
    background: rgba(245, 158, 11, 0.05);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.checklist-item.optional {
    background: rgba(107, 114, 128, 0.05);
    border: 1px solid rgba(107, 114, 128, 0.2);
}

.checklist-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.checklist-item.completed .checklist-icon {
    background: #22c55e;
    color: white;
}

.checklist-item.pending .checklist-icon {
    background: #f59e0b;
    color: white;
}

.checklist-item.optional .checklist-icon {
    background: #6b7280;
    color: white;
}

.checklist-icon svg {
    width: 14px;
    height: 14px;
}

.checklist-content {
    flex: 1;
}

.checklist-label {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.125rem;
}

.checklist-status {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

/* Alerts Section */
.alerts-section {
    margin-bottom: 3rem;
}

.alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.alert-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid;
    transition: all 0.3s ease;
}

.alert-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.alert-card.urgent {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
}

.alert-card.warning {
    background: rgba(245, 158, 11, 0.05);
    border-color: rgba(245, 158, 11, 0.2);
}

.alert-card.info {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.2);
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.alert-card.urgent .alert-icon {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.alert-card.warning .alert-icon {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.alert-card.info .alert-icon {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
}

.alert-icon svg {
    width: 20px;
    height: 20px;
}

.alert-content h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: hsl(var(--foreground));
}

.alert-content p {
    margin: 0 0 1rem 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
}

.alert-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--primary));
    text-decoration: none;
    transition: color 0.2s ease;
}

.alert-action:hover {
    color: hsl(var(--primary) / 0.8);
}

.alert-action svg {
    width: 14px;
    height: 14px;
}

/* Activity Section */
.activity-section {
    margin-bottom: 3rem;
}

.activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.activity-card {
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.activity-header {
    padding: 1.5rem 1.5rem 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid hsl(var(--border));
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.activity-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: hsl(var(--foreground));
}

.activity-icon {
    width: 18px;
    height: 18px;
    color: hsl(var(--primary));
}

.view-all {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: hsl(var(--primary));
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.view-all:hover {
    color: hsl(var(--primary) / 0.8);
}

.view-all svg {
    width: 14px;
    height: 14px;
}

.schedule-count {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    background: hsl(var(--muted));
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.activity-content {
    padding: 0 1.5rem 1.5rem 1.5rem;
}

/* Bookings List */
.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.booking-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    background: hsl(var(--muted) / 0.3);
    transition: background-color 0.2s ease;
}

.booking-item:hover {
    background: hsl(var(--muted) / 0.5);
}

.booking-avatar {
    width: 40px;
    height: 40px;
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

.booking-info {
    flex: 1;
}

.booking-customer {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.booking-details {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.booking-status {
    flex-shrink: 0;
}

/* Schedule List */
.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.schedule-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    background: hsl(var(--muted) / 0.3);
    transition: all 0.2s ease;
}

.schedule-item:hover {
    background: hsl(var(--muted) / 0.5);
    transform: translateX(4px);
}

.schedule-item.today {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.schedule-item.tomorrow {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.schedule-time {
    text-align: center;
    flex-shrink: 0;
    min-width: 80px;
}

.time-display {
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.date-display {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.schedule-details {
    flex: 1;
}

.schedule-customer {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.schedule-service {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.schedule-actions {
    flex-shrink: 0;
}

.schedule-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    text-decoration: none;
    transition: all 0.2s ease;
}

.schedule-link:hover {
    background: hsl(var(--primary));
    color: white;
}

.schedule-link svg {
    width: 16px;
    height: 16px;
}

/* Services List */
.services-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.service-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    background: hsl(var(--muted) / 0.3);
    transition: background-color 0.2s ease;
}

.service-item:hover {
    background: hsl(var(--muted) / 0.5);
}

.service-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
    color: hsl(var(--primary));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.service-icon svg {
    width: 20px;
    height: 20px;
}

.service-info {
    flex: 1;
}

.service-name {
    font-weight: 500;
    color: hsl(var(--foreground));
    margin-bottom: 0.25rem;
}

.service-stats {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.service-trend {
    flex-shrink: 0;
    color: #22c55e;
}

.service-trend svg {
    width: 16px;
    height: 16px;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: hsl(var(--muted-foreground));
}

.empty-state svg {
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
}

.btn-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--primary));
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: color 0.2s ease;
}

.btn-link:hover {
    color: hsl(var(--primary) / 0.8);
}

/* Quick Actions Section */
.quick-actions-section {
    margin-bottom: 2rem;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.quick-action-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.quick-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
    border-color: hsl(var(--primary));
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
    color: hsl(var(--primary));
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.action-icon svg {
    width: 24px;
    height: 24px;
}

.action-content {
    flex: 1;
}

.action-content h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: hsl(var(--foreground));
}

.action-content p {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    margin: 0;
}

.action-arrow {
    flex-shrink: 0;
    color: hsl(var(--muted-foreground));
    transition: color 0.2s ease;
}

.quick-action-card:hover .action-arrow {
    color: hsl(var(--primary));
}

.action-arrow svg {
    width: 16px;
    height: 16px;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.status-badge.status-pending {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-badge.status-confirmed {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.status-badge.status-completed {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.status-badge.status-cancelled {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
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

@keyframes progress {
    0% {
        stroke-dasharray: 0 100;
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .health-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .dashboard-header {
        padding: 1.5rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .quick-stats {
        align-self: stretch;
        justify-content: space-around;
    }
    
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .kpi-main-value {
        font-size: 2rem;
    }
    
    .performance-metrics {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .alerts-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .booking-item,
    .schedule-item,
    .service-item {
        padding: 0.75rem;
    }
    
    .booking-avatar,
    .service-icon {
        width: 32px;
        height: 32px;
    }
    
    .schedule-time {
        min-width: 60px;
    }
    
    .action-icon {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 480px) {
    .dashboard-header {
        padding: 1rem;
        text-align: center;
    }
    
    .dashboard-title {
        font-size: 1.25rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .kpi-card {
        padding: 1rem;
    }
    
    .kpi-main-value {
        font-size: 1.75rem;
    }
    
    .health-card,
    .activity-card {
        margin: 0 -0.5rem;
        border-radius: 8px;
    }
    
    .activity-header {
        padding: 1rem;
    }
    
    .activity-content {
        padding: 0 1rem 1rem 1rem;
    }
    
    .alert-card {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .quick-action-card {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .kpi-card,
    .health-card,
    .activity-card,
    .alert-card,
    .quick-action-card {
        border-width: 2px;
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

/* Print Styles */
@media print {
    .dashboard-header {
        background: white !important;
        color: black !important;
        border: 2px solid #333;
    }
    
    .alert-card,
    .quick-action-card {
        display: none;
    }
    
    .kpi-card,
    .health-card,
    .activity-card {
        page-break-inside: avoid;
        border: 1px solid #333;
    }
}
</style>

<script>
// Enhanced Dashboard Interactivity
jQuery(document).ready(function($) {
    'use strict';
    
    // Animate counters on page load
    function animateCounters() {
        $('.kpi-main-value').each(function() {
            const $this = $(this);
            const text = $this.text();
            const isPrice = text.includes(');
            const number = parseFloat(text.replace(/[^0-9.]/g, ''));
            
            if (!isNaN(number)) {
                $this.text(isPrice ? '$0.00' : '0');
                
                $({ counter: 0 }).animate({ counter: number }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function() {
                        const value = Math.ceil(this.counter);
                        $this.text(isPrice ? ' + value.toLocaleString() + '.00' : value.toLocaleString());
                    },
                    complete: function() {
                        $this.text(text);
                    }
                });
            }
        });