<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard stats
$dashboard_manager = new \MoBooking\Dashboard\Manager();
$stats = $dashboard_manager->get_dashboard_stats($user_id);

// Get recent bookings
$bookings_manager = new \MoBooking\Bookings\Manager();
$recent_bookings = $bookings_manager->get_user_bookings($user_id, array(
    'limit' => 5,
    'orderby' => 'created_at',
    'order' => 'DESC'
));
?>

<div class="dashboard-section overview-section">
    <h2 class="section-title"><?php _e('Dashboard Overview', 'mobooking'); ?></h2>
    
    <div class="stats-cards">
        <div class="stat-card total-bookings">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['total_bookings']); ?></div>
                <div class="stat-label"><?php _e('Total Bookings', 'mobooking'); ?></div>
            </div>
        </div>
        
        <div class="stat-card total-revenue">
            <div class="stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo wc_price($stats['total_revenue']); ?></div>
                <div class="stat-label"><?php _e('Total Revenue', 'mobooking'); ?></div>
            </div>
        </div>
        
        <div class="stat-card pending-bookings">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($stats['pending_bookings']); ?></div>
                <div class="stat-label"><?php _e('Pending Bookings', 'mobooking'); ?></div>
            </div>
        </div>
        
        <div class="stat-card this-month">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo wc_price($stats['this_month_revenue']); ?></div>
                <div class="stat-label"><?php _e('This Month', 'mobooking'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-widgets">
        <div class="widget recent-bookings">
            <h3 class="widget-title"><?php _e('Recent Bookings', 'mobooking'); ?></h3>
            
            <?php if (empty($recent_bookings)) : ?>
                <p class="no-bookings"><?php _e('No bookings yet.', 'mobooking'); ?></p>
            <?php else : ?>
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'mobooking'); ?></th>
                            <th><?php _e('Customer', 'mobooking'); ?></th>
                            <th><?php _e('Date', 'mobooking'); ?></th>
                            <th><?php _e('Total', 'mobooking'); ?></th>
                            <th><?php _e('Status', 'mobooking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking) : ?>
                            <tr class="booking-row status-<?php echo esc_attr($booking->status); ?>">
                                <td class="booking-id">#<?php echo esc_html($booking->id); ?></td>
                                <td class="booking-customer"><?php echo esc_html($booking->customer_name); ?></td>
                                <td class="booking-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($booking->service_date)); ?>
                                </td>
                                <td class="booking-total"><?php echo wc_price($booking->total_price); ?></td>
                                <td class="booking-status">
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
                                            default:
                                                echo esc_html($booking->status);
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" class="view-all"><?php _e('View All Bookings', 'mobooking'); ?></a>
            <?php endif; ?>
        </div>
        
        <div class="widget popular-service">
            <h3 class="widget-title"><?php _e('Most Popular Service', 'mobooking'); ?></h3>
            
            <?php if (empty($stats['most_popular_service'])) : ?>
                <p class="no-service"><?php _e('No service data available yet.', 'mobooking'); ?></p>
            <?php else : ?>
                <div class="popular-service-card">
                    <h4 class="service-name"><?php echo esc_html($stats['most_popular_service']['name']); ?></h4>
                    <div class="service-meta">
                        <span class="service-price"><?php echo wc_price($stats['most_popular_service']['price']); ?></span>
                        <span class="service-duration"><?php echo sprintf(_n('%d minute', '%d minutes', $stats['most_popular_service']['duration'], 'mobooking'), $stats['most_popular_service']['duration']); ?></span>
                    </div>
                </div>
                
                <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="view-all"><?php _e('Manage Services', 'mobooking'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>