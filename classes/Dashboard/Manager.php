<?php
namespace MoBooking\Dashboard;

/**
 * Dashboard Manager class
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
        
        // Add shortcodes
        add_shortcode('mobooking_dashboard', array($this, 'dashboard_shortcode'));
    }
    
    /**
     * Register dashboard endpoint
     */
    public function register_dashboard_endpoint() {
        add_rewrite_rule('^dashboard/?$', 'index.php?pagename=dashboard', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?pagename=dashboard&section=$matches[1]', 'top');
    }
    
    /**
     * Add dashboard query vars
     */
    public function add_dashboard_query_vars($vars) {
        $vars[] = 'section';
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
                // Redirect to login page
                wp_redirect(home_url('/login/?redirect_to=' . urlencode(home_url('/dashboard/'))));
                exit;
            }
            
            // Check if user has the required role
            $user = wp_get_current_user();
            if (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles)) {
                // Redirect to home page with error message
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
     * Get dashboard stats
     */
    public function get_dashboard_stats($user_id) {
        $bookings_manager = new \MoBooking\Bookings\Manager();
        
        $stats = array(
            'total_bookings' => $bookings_manager->count_user_bookings($user_id),
            'pending_bookings' => $bookings_manager->count_user_bookings($user_id, 'pending'),
            'confirmed_bookings' => $bookings_manager->count_user_bookings($user_id, 'confirmed'),
            'completed_bookings' => $bookings_manager->count_user_bookings($user_id, 'completed'),
            'cancelled_bookings' => $bookings_manager->count_user_bookings($user_id, 'cancelled'),
            'total_revenue' => $bookings_manager->calculate_user_revenue($user_id),
            'today_revenue' => $bookings_manager->calculate_user_revenue($user_id, 'today'),
            'this_week_revenue' => $bookings_manager->calculate_user_revenue($user_id, 'this_week'),
            'this_month_revenue' => $bookings_manager->calculate_user_revenue($user_id, 'this_month'),
            'most_popular_service' => null
        );
        
        // Get most popular service
        $most_popular_service = $bookings_manager->get_most_popular_service($user_id);
        if ($most_popular_service) {
            $stats['most_popular_service'] = array(
                'id' => $most_popular_service->id,
                'name' => $most_popular_service->name,
                'price' => $most_popular_service->price,
                'duration' => $most_popular_service->duration
            );
        }
        
        return $stats;
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

    // Add this to classes/Database/Manager.php
    public function force_recreate_services_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_services';
        
        // Drop the table if it exists
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Recreate the table
        $this->create_services_table();
        
        // Run the migration
        $migration = new ServicesTableMigration();
        $migration->run();
    }
}