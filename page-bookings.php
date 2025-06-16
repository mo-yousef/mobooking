<?php /** Template Name: Page Bookings */ ?>
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
    include(MOBOOKING_PATH . '/page-single-booking.php');
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
        <div class="controls-grid">
            <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status-filter" class="filter-label">Status</label>
                <select id="status-filter" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    </select>
                </div>

            <!-- Date Range Filter -->
                <div class="filter-group">
                    <label for="date-filter" class="filter-label">Date Range</label>
                <select id="date-filter" class="filter-select">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    </select>
                </div>

            <!-- Search Input -->
            <div class="filter-group search-group">
                <label for="search-input" class="filter-label">Search</label>
                <div class="search-input-wrapper">
                    <input type="text" id="search-input" class="search-input" placeholder="Search bookings...">
                    <button class="clear-search-btn" aria-label="Clear search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

            <!-- Reset Button -->
            <div class="filter-group">
                <button id="reset-filters" class="btn-secondary">Reset Filters</button>
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
                <div class="bookings-list-container">
                    <!-- List Header -->
                    <div class="bookings-list-header">
                        <div class="header-cell">Booking ID</div>
                        <div class="header-cell">Client</div>
                        <div class="header-cell">Service</div>
                        <div class="header-cell">Date</div>
                        <div class="header-cell">Status</div>
                        <div class="header-cell">Actions</div>
                    </div>

                    <!-- Bookings List -->
                    <div class="bookings-list">
                    <?php
                    $services_manager = new \MoBooking\Services\ServicesManager();
                    foreach ($bookings as $booking) :
                            $services_data = is_array($booking->services) ? $booking->services : json_decode($booking->services, true);
                            $service_name = '';

                            if (is_array($services_data) && !empty($services_data)) {
                                $service = $services_manager->get_service($services_data[0]);
                                if ($service) {
                                    $service_name = $service->name;
                            }
                        }

                        $service_date = new DateTime($booking->service_date);
                            $status_class = 'status-' . $booking->status;
                        ?>
                        <div class="booking-row" data-booking-id="<?php echo esc_attr($booking->id); ?>">
                            <div class="booking-cell">#<?php echo esc_html($booking->id); ?></div>
                            <div class="booking-cell">
                                <div class="client-info">
                                    <div class="client-name"><?php echo esc_html($booking->customer_name); ?></div>
                                    <div class="client-email"><?php echo esc_html($booking->customer_email); ?></div>
                                </div>
                                </div>
                            <div class="booking-cell"><?php echo esc_html($service_name); ?></div>
                            <div class="booking-cell"><?php echo esc_html($service_date->format('M d, Y')); ?></div>
                            <div class="booking-cell">
                                <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html(ucfirst($booking->status)); ?>
                                    </span>
                                        </div>
                            <div class="booking-cell">
                                <a href="<?php echo esc_url(add_query_arg(['view' => 'booking', 'booking_id' => $booking->id])); ?>" class="btn-view-details">
                                    View Details
                                        </a>
                                    </div>
                                        </div>
                                                <?php endforeach; ?>
                                        </div>
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
    // Initialize filters
    const BookingsManager = {
        init: function() {
            this.bindEvents();
            this.initializeFilters();
        },

        bindEvents: function() {
            // Status filter
            $('#status-filter').on('change', () => this.filterBookings());

            // Date filter
            $('#date-filter').on('change', () => this.filterBookings());

            // Search input
            $('#search-input').on('input', () => this.filterBookings());

            // Clear search
            $('.clear-search-btn').on('click', () => {
                $('#search-input').val('');
                this.filterBookings();
            });

            // Reset filters
            $('#reset-filters').on('click', () => {
                $('#status-filter').val('');
                $('#date-filter').val('');
                $('#search-input').val('');
                this.filterBookings();
                // Remove parameters from URL
                window.history.replaceState({}, '', window.location.pathname);
            });
        },

        initializeFilters: function() {
            // Set initial filter values from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            $('#status-filter').val(urlParams.get('status') || '');
            $('#date-filter').val(urlParams.get('date_range') || '');
            $('#search-input').val(urlParams.get('search') || '');

            // Apply initial filters
            this.filterBookings();
        },

        filterBookings: function() {
            const status = $('#status-filter').val();
            const dateRange = $('#date-filter').val();
            const searchQuery = ($('#search-input').val() || '').toLowerCase();

            let visibleCount = 0;

            $('.booking-row').each(function() {
                const $row = $(this);
                const safeText = (selector) => ($row.find(selector).text() || '').toLowerCase();
                const bookingId = safeText('.booking-cell:first');
                const clientName = safeText('.client-name');
                const clientEmail = safeText('.client-email');
                const serviceName = safeText('.booking-cell:nth-child(3)');
                const rowStatus = safeText('.status-badge');

                let show = true;

                // Status filter
                if ($('#status-filter').val() && rowStatus !== $('#status-filter').val().toLowerCase()) {
                    show = false;
                }

                // Search filter
                if (searchQuery) {
                    const matchesSearch =
                        bookingId.includes(searchQuery) ||
                        clientName.includes(searchQuery) ||
                        clientEmail.includes(searchQuery) ||
                        serviceName.includes(searchQuery);

                    if (!matchesSearch) {
                        show = false;
                    }
                }

                // Date filter
                if (dateRange) {
                    const bookingDate = new Date($row.find('.booking-cell:nth-child(4)').text());
                    const today = new Date();
                    const startOfWeek = new Date(today.getFullYear(), today.getMonth(), today.getDate() - today.getDay());
                    const endOfWeek = new Date(today.getFullYear(), today.getMonth(), today.getDate() - today.getDay() + 6);
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);

                    switch(dateRange) {
                        case 'today':
                            if (bookingDate.toDateString() !== (new Date()).toDateString()) {
                                show = false;
                            }
                            break;
                        case 'this_week':
                            if (bookingDate < startOfWeek || bookingDate > endOfWeek) {
                                show = false;
                            }
                            break;
                        case 'this_month':
                            if (bookingDate < startOfMonth || bookingDate > endOfMonth) {
                                show = false;
                            }
                    break;
            }
                }

                $row.toggle(show);
                if (show) visibleCount++;
            });

            // Show/hide no-result-found empty state
            if (visibleCount === 0) {
                $('.empty-state-content').show();
                } else {
                $('.empty-state-content').hide();
            }

            // Update URL with current filters
            const params = new URLSearchParams(window.location.search);
            if (status) params.set('status', status);
            if (dateRange) params.set('date_range', dateRange);
            if (searchQuery) params.set('search', searchQuery);

            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    };

    BookingsManager.init();
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
    margin-bottom: 2rem;
}

.controls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--foreground));
}

.filter-select {
    padding: 0.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: 4px;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
}

.search-group {
    grid-column: span 2;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: 4px;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
}

.clear-search-btn {
    position: absolute;
    right: 0.5rem;
    background: none;
    border: none;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
}

/* ==================================================
   BOOKING CARDS
   ================================================== */

.bookings-container {
    position: relative;
}

.bookings-list-container {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow-x: auto;
    margin-bottom: 2rem;
    width: 100%;
    box-sizing: border-box;
}

.bookings-list-header,
.booking-row {
    display: grid;
    grid-template-columns: 80px 2fr 2fr 1fr 1fr 1fr;
    gap: 1rem;
    align-items: center;
}

.bookings-list {
    display: flex;
    flex-direction: column;
}

.booking-row {
    display: grid;
    grid-template-columns: 80px 2fr 2fr 1fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
    align-items: center;
    transition: background-color 0.2s ease;
}

.booking-row:last-child {
    border-bottom: none;
}

.booking-row:hover {
    background: hsl(var(--muted) / 0.1);
}

.booking-cell {
    font-size: 0.875rem;
    color: hsl(var(--foreground));
}

.client-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.client-name {
    font-weight: 500;
}

.client-email {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

@media (max-width: 1024px) {
    .bookings-list-header,
    .booking-row {
        grid-template-columns: 80px 2fr 1.5fr 1fr 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .bookings-list-container {
        overflow-x: auto;
        padding-bottom: 1rem;
    }
    .bookings-list-header,
    .booking-row {
        min-width: 700px;
        font-size: 0.95em;
    }
    .client-info {
        min-width: 120px;
    }
}

@media (max-width: 600px) {
    .bookings-list-header,
    .booking-row {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-width: unset;
        gap: 0.5rem;
        padding: 0.75rem 0.5rem;
    }
    .bookings-list-header {
        font-size: 1em;
        background: hsl(var(--muted) / 0.2);
        border-bottom: 1px solid hsl(var(--border));
    }
    .booking-row {
        border-bottom: 1px solid hsl(var(--border));
        background: hsl(var(--background));
        margin-bottom: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 1px 2px hsl(var(--border) / 0.1);
    }
    .booking-cell {
        width: 100%;
        font-size: 1em;
        padding: 0.25rem 0;
    }
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

/* Table Layout Styles */
.bookings-table-container {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.bookings-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.bookings-table th,
.bookings-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid hsl(var(--border));
}

.bookings-table th {
    background: hsl(var(--muted) / 0.3);
    font-weight: 600;
    color: hsl(var(--foreground));
}

.bookings-table tr:last-child td {
    border-bottom: none;
}

.bookings-table tr:hover {
    background: hsl(var(--muted) / 0.1);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.status-confirmed {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.status-completed {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
}

.status-cancelled {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
}

.btn-view-details {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-view-details:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .bookings-table-container {
        overflow-x: auto;
    }

    .bookings-table {
        min-width: 600px;
    }
}

/* Div-based Table Layout Styles */
.bookings-list-container {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.bookings-list-header {
    display: grid;
    grid-template-columns: 80px 2fr 2fr 1fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem;
    background: hsl(var(--muted) / 0.3);
    border-bottom: 1px solid hsl(var(--border));
    font-weight: 600;
    color: hsl(var(--foreground));
}

.bookings-list {
    display: flex;
    flex-direction: column;
}

.booking-row {
    display: grid;
    grid-template-columns: 80px 2fr 2fr 1fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid hsl(var(--border));
    align-items: center;
    transition: background-color 0.2s ease;
}

.booking-row:last-child {
    border-bottom: none;
}

.booking-row:hover {
    background: hsl(var(--muted) / 0.1);
}

.booking-cell {
    font-size: 0.875rem;
    color: hsl(var(--foreground));
}

.client-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.client-name {
    font-weight: 500;
}

.client-email {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.status-confirmed {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.status-completed {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
}

.status-cancelled {
    background: hsl(var(--destructive) / 0.1);
    color: hsl(var(--destructive));
}

.btn-view-details {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-view-details:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
}

@media (max-width: 1024px) {
    .bookings-list-header,
    .booking-row {
        grid-template-columns: 80px 2fr 1.5fr 1fr 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .bookings-list-container {
        overflow-x: auto;
    }

    .bookings-list-header,
    .booking-row {
        min-width: 800px;
    }

    .client-info {
        min-width: 200px;
    }
}
</style>
