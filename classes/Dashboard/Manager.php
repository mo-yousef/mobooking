<?php
namespace MoBooking\Dashboard;

/**
 * Dashboard Manager class - UPDATED for normalized database structure
 * Provides comprehensive analytics and dashboard functionality
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action('init', array($this, 'register_dashboard_endpoint'));
        add_filter('query_vars', array($this, 'add_dashboard_query_vars'));
        add_filter('template_include', array($this, 'load_dashboard_template'));
        
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_mobooking_get_dashboard_analytics', array($this, 'ajax_get_dashboard_analytics'));
        add_action('wp_ajax_mobooking_get_recent_bookings', array($this, 'ajax_get_recent_bookings'));
        add_action('wp_ajax_mobooking_get_revenue_chart', array($this, 'ajax_get_revenue_chart'));
        add_action('wp_ajax_mobooking_export_dashboard_data', array($this, 'ajax_export_dashboard_data'));
        
        // Add shortcodes
        add_shortcode('mobooking_dashboard', array($this, 'dashboard_shortcode'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking\Dashboard\Manager: Initialized with normalized database support');
        }
    }
    
    /**
     * Register dashboard endpoint
     */
    public function register_dashboard_endpoint() {
        add_rewrite_rule('^dashboard/?$', 'index.php?pagename=dashboard', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?pagename=dashboard&section=$matches[1]', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/([^/]+)/?$', 'index.php?pagename=dashboard&section=$matches[1]&subsection=$matches[2]', 'top');
    }
    
    /**
     * Add dashboard query vars
     */
    public function add_dashboard_query_vars($vars) {
        $vars[] = 'section';
        $vars[] = 'subsection';
        return $vars;
    }
    
    /**
     * Load dashboard template
     */
    public function load_dashboard_template($template) {
        global $wp_query;
        
        if (isset($wp_query->query['pagename']) && $wp_query->query['pagename'] === 'dashboard') {
            // Check if user is logged in and has the required role
            if (!is_user_logged_in()) {
                wp_redirect(home_url('/login/?redirect_to=' . urlencode(home_url('/dashboard/'))));
                exit;
            }
            
            // Check if user has the required role
            $user = wp_get_current_user();
            if (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles)) {
                wp_redirect(home_url('/?error=permission'));
                exit;
            }
            
            // Load the dashboard template
            $dashboard_template = MOBOOKING_PATH . '/dashboard/index.php';
            
            if (file_exists($dashboard_template)) {
                return $dashboard_template;
            }
        }
        
        return $template;
    }
    
    /**
     * UPDATED: Get dashboard stats using normalized database structure
     */
    public function get_dashboard_stats($user_id) {
        global $wpdb;
        
        // Get booking counts by status using normalized structure
        $booking_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count 
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d 
             GROUP BY status",
            $user_id
        ));
        
        // Convert to associative array
        $stats = array(
            'total_bookings' => 0,
            'pending_bookings' => 0,
            'confirmed_bookings' => 0,
            'completed_bookings' => 0,
            'cancelled_bookings' => 0,
            'rescheduled_bookings' => 0
        );
        
        foreach ($booking_stats as $stat) {
            $stats['total_bookings'] += $stat->count;
            $stats[$stat->status . '_bookings'] = $stat->count;
        }
        
        // Get revenue data
        $revenue_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status IN ('confirmed', 'completed') AND DATE(created_at) = CURDATE() THEN total_price ELSE 0 END) as today_revenue,
                SUM(CASE WHEN status IN ('confirmed', 'completed') AND YEARWEEK(created_at) = YEARWEEK(NOW()) THEN total_price ELSE 0 END) as this_week_revenue,
                SUM(CASE WHEN status IN ('confirmed', 'completed') AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW()) THEN total_price ELSE 0 END) as this_month_revenue,
                AVG(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE NULL END) as average_booking_value
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d",
            $user_id
        ));
        
        if ($revenue_data) {
            $stats['total_revenue'] = floatval($revenue_data->total_revenue);
            $stats['today_revenue'] = floatval($revenue_data->today_revenue);
            $stats['this_week_revenue'] = floatval($revenue_data->this_week_revenue);
            $stats['this_month_revenue'] = floatval($revenue_data->this_month_revenue);
            $stats['average_booking_value'] = floatval($revenue_data->average_booking_value);
        } else {
            $stats['total_revenue'] = 0;
            $stats['today_revenue'] = 0;
            $stats['this_week_revenue'] = 0;
            $stats['this_month_revenue'] = 0;
            $stats['average_booking_value'] = 0;
        }
        
        // Get most popular service using normalized structure
        $most_popular_service = $wpdb->get_row($wpdb->prepare(
            "SELECT s.id, s.name, s.price, s.duration, COUNT(bs.service_id) as booking_count
             FROM {$wpdb->prefix}mobooking_services s
             JOIN {$wpdb->prefix}mobooking_booking_services bs ON s.id = bs.service_id
             JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
             WHERE s.user_id = %d AND b.status IN ('confirmed', 'completed')
             GROUP BY s.id, s.name, s.price, s.duration
             ORDER BY booking_count DESC
             LIMIT 1",
            $user_id
        ));
        
        if ($most_popular_service) {
            $stats['most_popular_service'] = array(
                'id' => $most_popular_service->id,
                'name' => $most_popular_service->name,
                'price' => $most_popular_service->price,
                'duration' => $most_popular_service->duration,
                'booking_count' => $most_popular_service->booking_count
            );
        } else {
            $stats['most_popular_service'] = null;
        }
        
        // Get service counts
        $service_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_services WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        $stats['active_services'] = intval($service_count);
        
        // Get service area counts
        $area_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mobooking_areas WHERE user_id = %d AND active = 1",
            $user_id
        ));
        $stats['service_areas'] = intval($area_count);
        
        // Get recent activity
        $stats['recent_bookings'] = $this->get_recent_bookings($user_id, 5);
        
        return $stats;
    }
    
    /**
     * NEW: Get comprehensive dashboard analytics
     */
    public function get_dashboard_analytics($user_id, $period = 'month') {
        global $wpdb;
        
        // Date conditions based on period
        $date_condition = $this->get_date_condition($period);
        
        // Booking trends
        $booking_trends = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                status,
                COUNT(*) as count,
                SUM(total_price) as revenue
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d {$date_condition}
             GROUP BY DATE(created_at), status
             ORDER BY date DESC",
            $user_id
        ));
        
        // Service performance
        $service_performance = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.id,
                s.name,
                COUNT(bs.service_id) as booking_count,
                SUM(bs.total_price) as total_revenue,
                AVG(bs.unit_price) as average_price
             FROM {$wpdb->prefix}mobooking_services s
             LEFT JOIN {$wpdb->prefix}mobooking_booking_services bs ON s.id = bs.service_id
             LEFT JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
             WHERE s.user_id = %d AND (b.id IS NULL OR (b.status IN ('confirmed', 'completed') {$date_condition}))
             GROUP BY s.id, s.name
             ORDER BY booking_count DESC",
            $user_id
        ));
        
        // Customer analytics
        $customer_analytics = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT customer_email) as unique_customers,
                COUNT(*) as total_bookings,
                AVG(total_price) as average_order_value
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d AND status IN ('confirmed', 'completed') {$date_condition}",
            $user_id
        ));
        
        // Geographic distribution
        $geographic_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                zip_code,
                COUNT(*) as booking_count,
                SUM(total_price) as total_revenue
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d AND status IN ('confirmed', 'completed') {$date_condition}
             GROUP BY zip_code
             ORDER BY booking_count DESC
             LIMIT 10",
            $user_id
        ));
        
        // Revenue by time periods
        $revenue_trends = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                SUM(total_price) as daily_revenue,
                COUNT(*) as daily_bookings
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d AND status IN ('confirmed', 'completed') {$date_condition}
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $user_id
        ));
        
        return array(
            'booking_trends' => $booking_trends,
            'service_performance' => $service_performance,
            'customer_analytics' => $customer_analytics,
            'geographic_data' => $geographic_data,
            'revenue_trends' => $revenue_trends,
            'period' => $period
        );
    }
    
    /**
     * NEW: Get recent bookings with full details
     */
    public function get_recent_bookings($user_id, $limit = 10) {
        global $wpdb;
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                b.*,
                GROUP_CONCAT(s.name SEPARATOR ', ') as service_names,
                COUNT(bs.service_id) as service_count
             FROM {$wpdb->prefix}mobooking_bookings b
             LEFT JOIN {$wpdb->prefix}mobooking_booking_services bs ON b.id = bs.booking_id
             LEFT JOIN {$wpdb->prefix}mobooking_services s ON bs.service_id = s.id
             WHERE b.user_id = %d
             GROUP BY b.id
             ORDER BY b.created_at DESC
             LIMIT %d",
            $user_id, $limit
        ));
        
        // Enhance with service options
        foreach ($bookings as $booking) {
            $booking->service_options = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    so.name as option_name,
                    bso.option_value,
                    bso.price_impact
                 FROM {$wpdb->prefix}mobooking_booking_service_options bso
                 JOIN {$wpdb->prefix}mobooking_service_options so ON bso.service_option_id = so.id
                 WHERE bso.booking_id = %d",
                $booking->id
            ));
        }
        
        return $bookings;
    }
    
    /**
     * NEW: Get revenue chart data
     */
    public function get_revenue_chart_data($user_id, $period = 'month') {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $date_format = $this->get_date_format($period);
        
        $chart_data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '{$date_format}') as period_label,
                SUM(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE 0 END) as revenue,
                COUNT(CASE WHEN status IN ('confirmed', 'completed') THEN 1 END) as completed_bookings,
                COUNT(*) as total_bookings
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d {$date_condition}
             GROUP BY DATE_FORMAT(created_at, '{$date_format}')
             ORDER BY created_at ASC",
            $user_id
        ));
        
        return $chart_data;
    }
    
    /**
     * NEW: Get service analytics
     */
    public function get_service_analytics($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.id,
                s.name,
                s.price as base_price,
                s.duration,
                s.category,
                COUNT(bs.service_id) as total_bookings,
                SUM(bs.total_price) as total_revenue,
                AVG(bs.unit_price) as average_price,
                MAX(b.created_at) as last_booked,
                COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN b.status = 'cancelled' THEN 1 END) as cancelled_bookings
             FROM {$wpdb->prefix}mobooking_services s
             LEFT JOIN {$wpdb->prefix}mobooking_booking_services bs ON s.id = bs.service_id
             LEFT JOIN {$wpdb->prefix}mobooking_bookings b ON bs.booking_id = b.id
             WHERE s.user_id = %d
             GROUP BY s.id, s.name, s.price, s.duration, s.category
             ORDER BY total_bookings DESC",
            $user_id
        ));
    }
    
    /**
     * Helper: Get date condition for SQL queries
     */
    private function get_date_condition($period) {
        switch ($period) {
            case 'today':
                return " AND DATE(created_at) = CURDATE()";
            case 'week':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'month':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'quarter':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            case 'year':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
    }
    
    /**
     * Helper: Get date format for chart data
     */
    private function get_date_format($period) {
        switch ($period) {
            case 'today':
                return '%H:00'; // Hour format
            case 'week':
                return '%Y-%m-%d'; // Daily format
            case 'month':
                return '%Y-%m-%d'; // Daily format
            case 'quarter':
                return '%Y-%u'; // Weekly format
            case 'year':
                return '%Y-%m'; // Monthly format
            default:
                return '%Y-%m-%d';
        }
    }
    
    /**
     * AJAX handler to get dashboard stats
     */
    public function ajax_get_dashboard_stats() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-dashboard-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $stats = $this->get_dashboard_stats($user_id);
        
        wp_send_json_success(array(
            'stats' => $stats
        ));
    }
    
    /**
     * AJAX handler to get dashboard analytics
     */
    public function ajax_get_dashboard_analytics() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-dashboard-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
        
        $analytics = $this->get_dashboard_analytics($user_id, $period);
        
        wp_send_json_success(array(
            'analytics' => $analytics
        ));
    }
    
    /**
     * AJAX handler to get recent bookings
     */
    public function ajax_get_recent_bookings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-dashboard-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        
        $bookings = $this->get_recent_bookings($user_id, $limit);
        
        wp_send_json_success(array(
            'bookings' => $bookings
        ));
    }
    
    /**
     * AJAX handler to get revenue chart data
     */
    public function ajax_get_revenue_chart() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-dashboard-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
        
        $chart_data = $this->get_revenue_chart_data($user_id, $period);
        
        wp_send_json_success(array(
            'chart_data' => $chart_data,
            'period' => $period
        ));
    }
    
    /**
     * AJAX handler to export dashboard data
     */
    public function ajax_export_dashboard_data() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-dashboard-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'csv';
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';
        
        try {
            $export_url = $this->export_dashboard_data($user_id, $export_type, $period);
            
            wp_send_json_success(array(
                'export_url' => $export_url,
                'message' => __('Export generated successfully.', 'mobooking')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to generate export.', 'mobooking'));
        }
    }
    
    /**
     * Export dashboard data
     */
    public function export_dashboard_data($user_id, $format = 'csv', $period = 'month') {
        // Get comprehensive data
        $stats = $this->get_dashboard_stats($user_id);
        $analytics = $this->get_dashboard_analytics($user_id, $period);
        $service_analytics = $this->get_service_analytics($user_id);
        
        $filename = 'dashboard-export-' . $period . '-' . date('Y-m-d-H-i-s');
        
        if ($format === 'csv') {
            return $this->export_to_csv($stats, $analytics, $service_analytics, $filename);
        } elseif ($format === 'json') {
            return $this->export_to_json($stats, $analytics, $service_analytics, $filename);
        }
        
        throw new Exception('Unsupported export format');
    }
    
    /**
     * Export to CSV format
     */
    private function export_to_csv($stats, $analytics, $service_analytics, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Dashboard Stats
        fputcsv($output, array('Dashboard Statistics'));
        fputcsv($output, array('Metric', 'Value'));
        
        foreach ($stats as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            fputcsv($output, array(ucwords(str_replace('_', ' ', $key)), $value));
        }
        
        fputcsv($output, array('')); // Empty row
        
        // Service Analytics
        fputcsv($output, array('Service Performance'));
        fputcsv($output, array('Service', 'Bookings', 'Revenue', 'Average Price', 'Completion Rate'));
        
        foreach ($service_analytics as $service) {
            $completion_rate = $service->total_bookings > 0 
                ? round(($service->completed_bookings / $service->total_bookings) * 100, 2) . '%'
                : 'N/A';
                
            fputcsv($output, array(
                $service->name,
                $service->total_bookings,
                '$' . number_format($service->total_revenue, 2),
                '$' . number_format($service->average_price, 2),
                $completion_rate
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export to JSON format
     */
    private function export_to_json($stats, $analytics, $service_analytics, $filename) {
        $export_data = array(
            'generated_at' => current_time('mysql'),
            'stats' => $stats,
            'analytics' => $analytics,
            'service_analytics' => $service_analytics
        );
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Dashboard shortcode
     */
    public function dashboard_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'section' => 'overview'
        ), $atts);
        
        // Check if user is logged in and has the required role
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access your dashboard.', 'mobooking') . '</p>';
        }
        
        $user = wp_get_current_user();
        if (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<p>' . __('You do not have permission to access this dashboard.', 'mobooking') . '</p>';
        }
        
        // Get the current section from query var or attribute
        $section = get_query_var('section');
        if (empty($section)) {
            $section = $atts['section'];
        }
        
        // Include the dashboard template
        ob_start();
        include MOBOOKING_PATH . '/dashboard/index.php';
        return ob_get_clean();
    }
    
    /**
     * Get dashboard performance metrics
     */
    public function get_performance_metrics($user_id) {
        global $wpdb;
        
        // Conversion rates
        $conversion_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d",
            $user_id
        ));
        
        $completion_rate = $conversion_data->total_bookings > 0 
            ? round(($conversion_data->completed_bookings / $conversion_data->total_bookings) * 100, 2)
            : 0;
            
        $cancellation_rate = $conversion_data->total_bookings > 0 
            ? round(($conversion_data->cancelled_bookings / $conversion_data->total_bookings) * 100, 2)
            : 0;
        
        // Customer retention
        $repeat_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT customer_email 
                FROM {$wpdb->prefix}mobooking_bookings 
                WHERE user_id = %d AND status IN ('confirmed', 'completed')
                GROUP BY customer_email 
                HAVING COUNT(*) > 1
            ) as repeat_customers",
            $user_id
        ));
        
        $total_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT customer_email) 
             FROM {$wpdb->prefix}mobooking_bookings 
             WHERE user_id = %d AND status IN ('confirmed', 'completed')",
            $user_id
        ));
        
        $retention_rate = $total_customers > 0 
            ? round(($repeat_customers / $total_customers) * 100, 2)
            : 0;
        
        return array(
            'completion_rate' => $completion_rate,
            'cancellation_rate' => $cancellation_rate,
            'retention_rate' => $retention_rate,
            'total_customers' => intval($total_customers),
            'repeat_customers' => intval($repeat_customers)
        );
    }
}