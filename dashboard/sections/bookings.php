<?php
// dashboard/sections/bookings.php - Bookings Management Section
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize bookings manager
$bookings_manager = new \MoBooking\Bookings\Manager();

// Get bookings statistics
$total_bookings = $bookings_manager->count_user_bookings($user_id);
$pending_bookings = $bookings_manager->count_user_bookings($user_id, 'pending');
$confirmed_bookings = $bookings_manager->count_user_bookings($user_id, 'confirmed');
$completed_bookings = $bookings_manager->count_user_bookings($user_id, 'completed');
$cancelled_bookings = $bookings_manager->count_user_bookings($user_id, 'cancelled');

// Get revenue statistics
$total_revenue = $bookings_manager->calculate_user_revenue($user_id);
$this_month_revenue = $bookings_manager->calculate_user_revenue($user_id, 'this_month');

// Get recent bookings
$recent_bookings = $bookings_manager->get_user_bookings($user_id, array(
    'limit' => 10,
    'orderby' => 'created_at',
    'order' => 'DESC'
));
?>

<div class="bookings-section">
    <div class="bookings-header">
        <div class="bookings-header-content">
            <div class="bookings-title-group">
                <h1 class="bookings-main-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Bookings', 'mobooking'); ?>
                </h1>
                <p class="bookings-subtitle"><?php _e('Manage and track your service bookings', 'mobooking'); ?></p>
            </div>
            
            <div class="bookings-quick-stats">
                <div class="quick-stat">
                    <span class="stat-number"><?php echo $total_bookings; ?></span>
                    <span class="stat-label"><?php _e('Total', 'mobooking'); ?></span>
                </div>
                <div class="quick-stat pending">
                    <span class="stat-number"><?php echo $pending_bookings; ?></span>
                    <span class="stat-label"><?php _e('Pending', 'mobooking'); ?></span>
                </div>
                <div class="quick-stat revenue">
                    <span class="stat-number"><?php echo wc_price($this_month_revenue); ?></span>
                    <span class="stat-label"><?php _e('This Month', 'mobooking'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="bookings-header-actions">
            <button type="button" id="export-bookings-btn" class="btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 4v12"/>
                </svg>
                <?php _e('Export', 'mobooking'); ?>
            </button>
            
            <button type="button" id="refresh-bookings-btn" class="btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                <?php _e('Refresh', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <!-- Detailed Stats Cards -->
    <div class="bookings-stats-grid">
        <div class="stat-card total-bookings">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $total_bookings; ?></div>
                    <div class="stat-label"><?php _e('Total Bookings', 'mobooking'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="stat-card pending-bookings">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $pending_bookings; ?></div>
                    <div class="stat-label"><?php _e('Pending Review', 'mobooking'); ?></div>
                </div>
            </div>
            <?php if ($pending_bookings > 0) : ?>
                <div class="stat-alert">
                    <span class="alert-icon">⚠️</span>
                    <?php printf(_n('%d booking needs your attention', '%d bookings need your attention', $pending_bookings, 'mobooking'), $pending_bookings); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card confirmed-bookings">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $confirmed_bookings; ?></div>
                    <div class="stat-label"><?php _e('Confirmed', 'mobooking'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="stat-card completed-bookings">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $completed_bookings; ?></div>
                    <div class="stat-label"><?php _e('Completed', 'mobooking'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="stat-card revenue-card">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo wc_price($total_revenue); ?></div>
                    <div class="stat-label"><?php _e('Total Revenue', 'mobooking'); ?></div>
                </div>
            </div>
            <div class="stat-subinfo">
                <?php _e('This Month:', 'mobooking'); ?> <strong><?php echo wc_price($this_month_revenue); ?></strong>
            </div>
        </div>
        
        <div class="stat-card cancelled-bookings">
            <div class="stat-card-header">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M15 9l-6 6M9 9l6 6"></path>
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $cancelled_bookings; ?></div>
                    <div class="stat-label"><?php _e('Cancelled', 'mobooking'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="bookings-controls">
        <div class="bookings-filters">
            <select id="booking-status-filter" class="filter-select">
                <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                <option value="pending"><?php _e('Pending', 'mobooking'); ?></option>
                <option value="confirmed"><?php _e('Confirmed', 'mobooking'); ?></option>
                <option value="completed"><?php _e('Completed', 'mobooking'); ?></option>
                <option value="cancelled"><?php _e('Cancelled', 'mobooking'); ?></option>
            </select>
            
            <select id="booking-date-filter" class="filter-select">
                <option value=""><?php _e('All Dates', 'mobooking'); ?></option>
                <option value="today"><?php _e('Today', 'mobooking'); ?></option>
                <option value="tomorrow"><?php _e('Tomorrow', 'mobooking'); ?></option>
                <option value="this_week"><?php _e('This Week', 'mobooking'); ?></option>
                <option value="next_week"><?php _e('Next Week', 'mobooking'); ?></option>
                <option value="this_month"><?php _e('This Month', 'mobooking'); ?></option>
            </select>
        </div>
        
        <div class="bookings-search">
            <input type="text" id="booking-search" placeholder="<?php _e('Search customers, booking IDs...', 'mobooking'); ?>" class="search-input">
            <button type="button" id="clear-search" class="btn-icon" title="<?php _e('Clear search', 'mobooking'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="bookings-container">
        <?php if (empty($recent_bookings)) : ?>
            <!-- Empty State -->
            <div class="bookings-empty-state">
                <div class="empty-state-visual">
                    <div class="empty-state-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
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
                    <h2><?php _e('No Bookings Yet', 'mobooking'); ?></h2>
                    <p><?php _e('Your bookings will appear here once customers start booking your services. Make sure your booking form is published and your services are set up.', 'mobooking'); ?></p>
                    <div class="empty-state-actions">
                        <a href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>" class="btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                            </svg>
                            <?php _e('Setup Booking Form', 'mobooking'); ?>
                        </a>
                        <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                            </svg>
                            <?php _e('Manage Services', 'mobooking'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <!-- Bookings Table -->
            <div class="bookings-table-container">
                <table class="bookings-table" id="bookings-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="id">
                                <?php _e('Booking ID', 'mobooking'); ?>
                                <span class="sort-arrow"></span>
                            </th>
                            <th class="sortable" data-sort="customer">
                                <?php _e('Customer', 'mobooking'); ?>
                                <span class="sort-arrow"></span>
                            </th>
                            <th class="sortable" data-sort="date">
                                <?php _e('Service Date', 'mobooking'); ?>
                                <span class="sort-arrow"></span>
                            </th>
                            <th><?php _e('Services', 'mobooking'); ?></th>
                            <th class="sortable" data-sort="total">
                                <?php _e('Total', 'mobooking'); ?>
                                <span class="sort-arrow"></span>
                            </th>
                            <th class="sortable" data-sort="status">
                                <?php _e('Status', 'mobooking'); ?>
                                <span class="sort-arrow"></span>
                            </th>
                            <th><?php _e('Actions', 'mobooking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $services_manager = new \MoBooking\Services\ServicesManager();
                        foreach ($recent_bookings as $booking) : 
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
                        ?>
                            <tr class="booking-row status-<?php echo esc_attr($booking->status); ?>" 
                                data-booking-id="<?php echo esc_attr($booking->id); ?>"
                                data-customer="<?php echo esc_attr(strtolower($booking->customer_name . ' ' . $booking->customer_email)); ?>"
                                data-status="<?php echo esc_attr($booking->status); ?>"
                                data-service-date="<?php echo esc_attr($booking->service_date); ?>"
                                data-created="<?php echo esc_attr($booking->created_at); ?>">
                                
                                <td class="booking-id">
                                    <div class="booking-id-wrapper">
                                        <span class="id-number">#<?php echo $booking->id; ?></span>
                                        <span class="booking-date"><?php echo $created_date->format('M j, Y'); ?></span>
                                    </div>
                                </td>
                                
                                <td class="booking-customer">
                                    <div class="customer-info">
                                        <div class="customer-name"><?php echo esc_html($booking->customer_name); ?></div>
                                        <div class="customer-email"><?php echo esc_html($booking->customer_email); ?></div>
                                        <?php if (!empty($booking->customer_phone)) : ?>
                                            <div class="customer-phone"><?php echo esc_html($booking->customer_phone); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="booking-service-date">
                                    <div class="service-date-info">
                                        <div class="service-date"><?php echo $service_date->format('M j, Y'); ?></div>
                                        <div class="service-time"><?php echo $service_date->format('g:i A'); ?></div>
                                        <?php
                                        $now = new DateTime();
                                        $days_until = $now->diff($service_date)->days;
                                        if ($service_date > $now) {
                                            if ($days_until == 0) {
                                                echo '<div class="date-indicator today">' . __('Today', 'mobooking') . '</div>';
                                            } elseif ($days_until == 1) {
                                                echo '<div class="date-indicator tomorrow">' . __('Tomorrow', 'mobooking') . '</div>';
                                            } elseif ($days_until <= 7) {
                                                echo '<div class="date-indicator this-week">' . sprintf(__('In %d days', 'mobooking'), $days_until) . '</div>';
                                            }
                                        } elseif ($service_date < $now) {
                                            echo '<div class="date-indicator overdue">' . __('Past due', 'mobooking') . '</div>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                
                                <td class="booking-services">
                                    <div class="services-list">
                                        <?php if (!empty($services_names)) : ?>
                                            <?php foreach ($services_names as $index => $service_name) : ?>
                                                <span class="service-tag"><?php echo esc_html($service_name); ?></span>
                                                <?php if ($index < count($services_names) - 1) echo ' '; ?>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <span class="no-services"><?php _e('No services', 'mobooking'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="booking-total">
                                    <div class="total-info">
                                        <div class="total-amount"><?php echo wc_price($booking->total_price); ?></div>
                                        <?php if ($booking->discount_amount > 0) : ?>
                                            <div class="discount-info">
                                                <?php _e('Discount:', 'mobooking'); ?> -<?php echo wc_price($booking->discount_amount); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                
                                <td class="booking-status">
                                    <span class="status-badge status-<?php echo esc_attr($booking->status); ?>">
                                        <?php 
                                        switch ($booking->status) {
                                            case 'pending':
                                                echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
                                                _e('Pending', 'mobooking');
                                                break;
                                            case 'confirmed':
                                                echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                                _e('Confirmed', 'mobooking');
                                                break;
                                            case 'completed':
                                                echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                                _e('Completed', 'mobooking');
                                                break;
                                            case 'cancelled':
                                                echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M15 9l-6 6M9 9l6 6"></path></svg>';
                                                _e('Cancelled', 'mobooking');
                                                break;
                                            default:
                                                echo esc_html(ucfirst($booking->status));
                                        }
                                        ?>
                                    </span>
                                </td>
                                
                                <td class="booking-actions">
                                    <div class="action-buttons">
                                        <?php if ($booking->status === 'pending') : ?>
                                            <button type="button" class="btn-icon btn-success confirm-booking-btn" 
                                                    data-booking-id="<?php echo $booking->id; ?>" 
                                                    title="<?php _e('Confirm Booking', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($booking->status === 'confirmed') : ?>
                                            <button type="button" class="btn-icon btn-primary complete-booking-btn" 
                                                    data-booking-id="<?php echo $booking->id; ?>" 
                                                    title="<?php _e('Mark as Completed', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn-icon btn-secondary view-booking-btn" 
                                                data-booking-id="<?php echo $booking->id; ?>" 
                                                title="<?php _e('View Details', 'mobooking'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                        
                                        <?php if (in_array($booking->status, ['pending', 'confirmed'])) : ?>
                                            <button type="button" class="btn-icon btn-danger cancel-booking-btn" 
                                                    data-booking-id="<?php echo $booking->id; ?>" 
                                                    title="<?php _e('Cancel Booking', 'mobooking'); ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <path d="M15 9l-6 6M9 9l6 6"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination (if needed) -->
            <div class="bookings-pagination">
                <div class="pagination-info">
                    <?php printf(__('Showing %d of %d bookings', 'mobooking'), count($recent_bookings), $total_bookings); ?>
                </div>
                
                <?php if ($total_bookings > 10) : ?>
                    <div class="pagination-controls">
                        <button type="button" class="btn-secondary load-more-bookings" data-page="2">
                            <?php _e('Load More Bookings', 'mobooking'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Booking Details Modal -->
<div id="booking-details-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content modal-large">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <div class="modal-header">
            <h3 id="booking-modal-title"><?php _e('Booking Details', 'mobooking'); ?></h3>
            <div class="booking-modal-status"></div>
        </div>
        
        <div class="modal-body">
            <div id="booking-details-content">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
        
        <div class="modal-footer">
            <div class="modal-actions">
                <!-- Action buttons will be populated based on booking status -->
            </div>
        </div>
    </div>
</div>

<!-- Booking management JavaScript -->
<script>
jQuery(document).ready(function($) {
    const BookingsManager = {
        init: function() {
            this.attachEventListeners();
            this.initializeFilters();
            this.initializeSorting();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Status update buttons
            $('.confirm-booking-btn, .complete-booking-btn, .cancel-booking-btn').on('click', function() {
                self.updateBookingStatus($(this));
            });
            
            // View booking details
            $('.view-booking-btn').on('click', function() {
                self.viewBookingDetails($(this).data('booking-id'));
            });
            
            // Filters
            $('#booking-status-filter, #booking-date-filter').on('change', function() {
                self.applyFilters();
            });
            
            // Search
            $('#booking-search').on('input', function() {
                self.applySearch($(this).val());
            });
            
            $('#clear-search').on('click', function() {
                $('#booking-search').val('');
                self.applySearch('');
            });
            
            // Export
            $('#export-bookings-btn').on('click', function() {
                self.exportBookings();
            });
            
            // Refresh
            $('#refresh-bookings-btn').on('click', function() {
                location.reload();
            });
            
            // Modal close
            $('.modal-close').on('click', function() {
                self.hideModal();
            });
        },
        
        updateBookingStatus: function($button) {
            const bookingId = $button.data('booking-id');
            let status = '';
            let confirmMessage = '';
            
            if ($button.hasClass('confirm-booking-btn')) {
                status = 'confirmed';
                confirmMessage = '<?php _e('Confirm this booking?', 'mobooking'); ?>';
            } else if ($button.hasClass('complete-booking-btn')) {
                status = 'completed';
                confirmMessage = '<?php _e('Mark this booking as completed?', 'mobooking'); ?>';
            } else if ($button.hasClass('cancel-booking-btn')) {
                status = 'cancelled';
                confirmMessage = '<?php _e('Cancel this booking? This action cannot be undone.', 'mobooking'); ?>';
            }
            
            if (status && confirm(confirmMessage)) {
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
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error updating booking status', 'mobooking'); ?>');
                    }
                });
            }
        },
        
        viewBookingDetails: function(bookingId) {
            // Show modal with loading state
            this.showModal();
            $('#booking-details-content').html('<div class="loading-spinner">Loading...</div>');
            
            // Load booking details via AJAX
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mobooking_get_booking_details',
                    booking_id: bookingId,
                    nonce: '<?php echo wp_create_nonce('mobooking-booking-nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#booking-details-content').html(response.data.html);
                        $('#booking-modal-title').text('Booking #' + bookingId);
                    } else {
                        $('#booking-details-content').html('<p>Error loading booking details.</p>');
                    }
                },
                error: function() {
                    $('#booking-details-content').html('<p>Error loading booking details.</p>');
                }
            });
        },
        
        applyFilters: function() {
            const statusFilter = $('#booking-status-filter').val();
            const dateFilter = $('#booking-date-filter').val();
            
            $('.booking-row').each(function() {
                const $row = $(this);
                let showRow = true;
                
                // Status filter
                if (statusFilter && !$row.hasClass('status-' + statusFilter)) {
                    showRow = false;
                }
                
                // Date filter
                if (dateFilter && showRow) {
                    const serviceDate = new Date($row.data('service-date'));
                    const now = new Date();
                    
                    switch (dateFilter) {
                        case 'today':
                            showRow = serviceDate.toDateString() === now.toDateString();
                            break;
                        case 'tomorrow':
                            const tomorrow = new Date(now);
                            tomorrow.setDate(tomorrow.getDate() + 1);
                            showRow = serviceDate.toDateString() === tomorrow.toDateString();
                            break;
                        case 'this_week':
                            const weekStart = new Date(now);
                            weekStart.setDate(now.getDate() - now.getDay());
                            const weekEnd = new Date(weekStart);
                            weekEnd.setDate(weekStart.getDate() + 6);
                            showRow = serviceDate >= weekStart && serviceDate <= weekEnd;
                            break;
                        // Add more date filters as needed
                    }
                }
                
                if (showRow) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },
        
        applySearch: function(searchTerm) {
            const term = searchTerm.toLowerCase();
            
            $('.booking-row').each(function() {
                const $row = $(this);
                const searchData = $row.data('customer') + ' #' + $row.data('booking-id');
                
                if (!term || searchData.includes(term)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },
        
        initializeFilters: function() {
            // Initialize any filter-specific functionality
        },
        
        initializeSorting: function() {
            const self = this;
            
            $('.sortable').on('click', function() {
                const $header = $(this);
                const sortBy = $header.data('sort');
                const isAsc = !$header.hasClass('sort-asc');
                
                // Remove sort classes from all headers
                $('.sortable').removeClass('sort-asc sort-desc');
                
                // Add sort class to current header
                $header.addClass(isAsc ? 'sort-asc' : 'sort-desc');
                
                // Sort rows
                self.sortTable(sortBy, isAsc);
            });
        },
        
        sortTable: function(sortBy, isAsc) {
            const $tbody = $('#bookings-table tbody');
            const $rows = $tbody.find('tr').get();
            
            $rows.sort(function(a, b) {
                let aVal, bVal;
                
                switch (sortBy) {
                    case 'id':
                        aVal = parseInt($(a).data('booking-id'));
                        bVal = parseInt($(b).data('booking-id'));
                        break;
                    case 'customer':
                        aVal = $(a).find('.customer-name').text().toLowerCase();
                        bVal = $(b).find('.customer-name').text().toLowerCase();
                        break;
                    case 'date':
                        aVal = new Date($(a).data('service-date'));
                        bVal = new Date($(b).data('service-date'));
                        break;
                    case 'total':
                        aVal = parseFloat($(a).find('.total-amount').text().replace(/[^0-9.]/g, ''));
                        bVal = parseFloat($(b).find('.total-amount').text().replace(/[^0-9.]/g, ''));
                        break;
                    case 'status':
                        aVal = $(a).data('status');
                        bVal = $(b).data('status');
                        break;
                }
                
                if (aVal < bVal) return isAsc ? -1 : 1;
                if (aVal > bVal) return isAsc ? 1 : -1;
                return 0;
            });
            
            $.each($rows, function(index, row) {
                $tbody.append(row);
            });
        },
        
        exportBookings: function() {
            // Create CSV export
            const data = [];
            const headers = ['Booking ID', 'Customer Name', 'Email', 'Phone', 'Service Date', 'Services', 'Total', 'Status'];
            data.push(headers);
            
            $('.booking-row:visible').each(function() {
                const $row = $(this);
                const rowData = [
                    '#' + $row.data('booking-id'),
                    $row.find('.customer-name').text(),
                    $row.find('.customer-email').text(),
                    $row.find('.customer-phone').text() || '',
                    $row.find('.service-date').text() + ' ' + $row.find('.service-time').text(),
                    $row.find('.service-tag').map(function() { return $(this).text(); }).get().join(', '),
                    $row.find('.total-amount').text(),
                    $row.find('.status-badge').text().trim()
                ];
                data.push(rowData);
            });
            
            this.downloadCSV(data, 'bookings-export.csv');
        },
        
        downloadCSV: function(data, filename) {
            const csvContent = data.map(row => row.map(field => '"' + field + '"').join(',')).join('\n');
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
        
        showModal: function() {
            $('#booking-details-modal').fadeIn(300);
            $('body').addClass('modal-open');
        },
        
        hideModal: function() {
            $('#booking-details-modal').fadeOut(300);
            $('body').removeClass('modal-open');
        }
    };
    
    BookingsManager.init();
});
</script>

<style>
/* Additional CSS for bookings section */
.bookings-section {
    animation: fadeIn 0.4s ease-out;
}

.bookings-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.bookings-header-content {
    display: flex;
    align-items: flex-start;
    gap: 3rem;
    flex: 1;
}

.bookings-title-group {
    flex: 1;
}

.bookings-main-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.bookings-subtitle {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 1rem;
}

</style>