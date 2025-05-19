<?php
namespace MoBooking\Core;

/**
 * Main loader class that initializes all theme components
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
        $db_manager = new \MoBooking\Database\Manager();
        $db_manager->create_tables();
    }
    
    /**
     * Load all required dependencies
     */
    private function load_dependencies() {
        // Initialize core modules
        new \MoBooking\Auth\Manager();
        new \MoBooking\Dashboard\Manager();
        new \MoBooking\Services\ServiceManager(); // Updated to use ServiceManager directly
        new \MoBooking\Bookings\Manager();
        new \MoBooking\Discounts\Manager();
        new \MoBooking\Geography\Manager();
        new \MoBooking\Notifications\Manager();
        new \MoBooking\Payments\Manager();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Theme setup
        add_action('after_setup_theme', array($this, 'theme_setup'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
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
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue main stylesheet
        wp_enqueue_style('mobooking-style', get_stylesheet_uri(), array(), MOBOOKING_VERSION);
        
        // Enqueue main JavaScript
        wp_enqueue_script('mobooking-main', MOBOOKING_URL . '/assets/js/main.js', array('jquery'), MOBOOKING_VERSION, true);
        
        // Localize script
        wp_localize_script('mobooking-main', 'mobooking_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking-nonce'),
        ));
    }
}