<?php
namespace MoBooking\Core;

/**
 * Main loader class that initializes all theme components - Fixed Version
 */
class Loader {
    /**
     * Initialize the theme components
     */
    public function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Register hooks
        $this->register_hooks();
        
        // Initialize database tables
        $this->init_database();
    }
    
    /**
     * Load all required dependencies - FIXED to prevent duplicate managers
     */
    private function load_dependencies() {
        // Initialize core modules
        new \MoBooking\Auth\Manager();
        new \MoBooking\Dashboard\Manager();
        
        // Initialize services manager (handles services only)
        new \MoBooking\Services\ServicesManager();
        
        // Initialize service options manager (handles options only)
        new \MoBooking\Services\ServiceOptionsManager();
        
        // Initialize other managers
        // new \MoBooking\Bookings\Manager();
        new \MoBooking\BookingForm\Manager();

        
        new \MoBooking\Discounts\Manager();
        new \MoBooking\Geography\Manager();
        new \MoBooking\Notifications\Manager();
        new \MoBooking\Payments\Manager();
        
        // Initialize admin components if in admin
        if (is_admin()) {
            new \MoBooking\Payments\AdminManager();
        }
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Theme setup
        add_action('after_setup_theme', array($this, 'theme_setup'));
        
        // Custom post types and taxonomies
        add_action('init', array($this, 'register_post_types'));
    }
    
    /**
     * Initialize database tables
     */
    private function init_database() {
        $db_manager = new \MoBooking\Database\Manager();
        $db_manager->create_tables();
    }
    
    /**
     * Theme setup function
     */
    public function theme_setup() {
        // Add theme support
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
        
        // Register navigation menus
        register_nav_menus(array(
            'primary' => esc_html__('Primary Menu', 'mobooking'),
            'footer' => esc_html__('Footer Menu', 'mobooking'),
        ));
    }
    
    /**
     * Register custom post types if needed
     */
    public function register_post_types() {
        // Register any custom post types here if needed
        // Currently using custom tables instead of post types
    }
}