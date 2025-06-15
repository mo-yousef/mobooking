<?php
namespace MoBooking\Core;

/**
 * Main loader class that initializes all theme components - FIXED VERSION
 * Properly handles dependencies and only loads existing managers
 */
class Loader {
    /**
     * Available managers and their dependencies
     */
    private $managers = array();
    
    /**
     * Loaded managers tracking
     */
    private $loaded_managers = array();
    
    /**
     * Initialize the theme components
     */
    public function init() {
        try {
            // Define manager dependencies
            $this->define_manager_dependencies();
            
            // Load dependencies first
            $this->load_dependencies();
            
            // Register WordPress hooks
            $this->register_hooks();
            
            // Initialize database tables
            $this->init_database();
            
            // Load managers in proper order
            $this->load_managers();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking\Core\Loader: Successfully initialized all components');
                error_log('MoBooking: Loaded managers: ' . implode(', ', array_keys($this->loaded_managers)));
            }
            
        } catch (\Exception $e) { // FIXED: Use \Exception instead of Exception
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking\Core\Loader: Initialization failed: ' . $e->getMessage());
            }
            
            // Show admin notice for critical errors
            add_action('admin_notices', function() use ($e) {
                if (current_user_can('administrator')) {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>MoBooking Error:</strong> ' . esc_html($e->getMessage());
                    echo '</p></div>';
                }
            });
        }
    }
    
    /**
     * Define manager dependencies and availability
     */
    private function define_manager_dependencies() {
        $this->managers = array(
            // Core managers (always load first)
            'Database\Manager' => array(
                'class' => '\MoBooking\Database\Manager',
                'file' => '/classes/Database/Manager.php',
                'dependencies' => array(),
                'required' => true,
                'description' => 'Database table management'
            ),
            
            'Auth\Manager' => array(
                'class' => '\MoBooking\Auth\Manager',
                'file' => '/classes/Auth/Manager.php',
                'dependencies' => array('Database\Manager'),
                'required' => true,
                'description' => 'User authentication and registration'
            ),
            
            'Database\SettingsManager' => array(
                'class' => '\MoBooking\Database\SettingsManager',
                'file' => '/classes/Database/SettingsManager.php',
                'dependencies' => array('Database\Manager'),
                'required' => true,
                'description' => 'User settings management'
            ),
            
            // Service managers
            'Services\ServicesManager' => array(
                'class' => '\MoBooking\Services\ServicesManager',
                'file' => '/classes/Services/ServicesManager.php',
                'dependencies' => array('Database\Manager'),
                'required' => true,
                'description' => 'Service management'
            ),
            
            'Services\ServiceOptionsManager' => array(
                'class' => '\MoBooking\Services\ServiceOptionsManager',
                'file' => '/classes/Services/ServiceOptionsManager.php',
                'dependencies' => array('Database\Manager'),
                'required' => true,
                'description' => 'Service options management'
            ),
            
            // Booking managers
            'Bookings\Manager' => array(
                'class' => '\MoBooking\Bookings\Manager',
                'file' => '/classes/Bookings/Manager.php',
                'dependencies' => array('Database\Manager', 'Services\ServicesManager'),
                'required' => true,
                'description' => 'Booking management'
            ),
            
            // Dashboard
            'Dashboard\Manager' => array(
                'class' => '\MoBooking\Dashboard\Manager',
                'file' => '/classes/Dashboard/Manager.php',
                'dependencies' => array('Bookings\Manager', 'Services\ServicesManager'),
                'required' => true,
                'description' => 'Dashboard analytics and management'
            ),
            
            // Optional managers (load if available)
            'Notifications\Manager' => array(
                'class' => '\MoBooking\Notifications\Manager',
                'file' => '/classes/Notifications/Manager.php',
                'dependencies' => array('Bookings\Manager', 'Database\SettingsManager'),
                'required' => false,
                'description' => 'Email and SMS notifications'
            ),
            
            'Payments\Manager' => array(
                'class' => '\MoBooking\Payments\Manager',
                'file' => '/classes/Payments/Manager.php',
                'dependencies' => array('Bookings\Manager'),
                'required' => false,
                'description' => 'Payment processing integration'
            ),
            
            'Payments\AdminManager' => array(
                'class' => '\MoBooking\Payments\AdminManager',
                'file' => '/classes/Payments/AdminManager.php',
                'dependencies' => array('Payments\Manager'),
                'required' => false,
                'condition' => 'is_admin',
                'description' => 'Admin payment management'
            )
        );
    }
    
    /**
     * Load base dependencies
     */
    private function load_dependencies() {
        // Ensure autoloader is working
        if (!class_exists('\MoBooking\Database\Manager')) {
            require_once MOBOOKING_PATH . '/includes/autoloader.php';
        }
        
        // Load helper functions if not already loaded
        if (!function_exists('mobooking_log')) {
            require_once MOBOOKING_PATH . '/includes/helper-functions.php';
        }
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Theme setup
        add_action('after_setup_theme', array($this, 'theme_setup'));
        
        // Custom post types and taxonomies (if needed)
        add_action('init', array($this, 'register_post_types'));
        
        // Admin initialization
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }
    
    /**
     * Initialize database tables
     */
    private function init_database() {
        try {
            if (class_exists('\MoBooking\Database\Manager')) {
                $db_manager = new \MoBooking\Database\Manager();
                $db_manager->create_tables();
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('MoBooking: Database tables initialized');
                }
            }
        } catch (\Exception $e) { // FIXED: Use \Exception
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MoBooking: Database initialization failed: ' . $e->getMessage());
            }
            throw new \Exception('Database initialization failed: ' . $e->getMessage()); // FIXED: Use \Exception
        }
    }
    
    /**
     * Load managers in dependency order
     */
    private function load_managers() {
        $load_order = $this->resolve_dependencies();
        
        foreach ($load_order as $manager_key) {
            $this->load_manager($manager_key);
        }
    }
    
    /**
     * Resolve dependency order
     */
    private function resolve_dependencies() {
        $resolved = array();
        $unresolved = array();
        
        foreach (array_keys($this->managers) as $manager_key) {
            $this->resolve_dependency($manager_key, $resolved, $unresolved);
        }
        
        return $resolved;
    }
    
    /**
     * Recursive dependency resolution
     */
    private function resolve_dependency($manager_key, &$resolved, &$unresolved) {
        if (in_array($manager_key, $resolved)) {
            return;
        }
        
        if (in_array($manager_key, $unresolved)) {
            throw new \Exception("Circular dependency detected for manager: {$manager_key}"); // FIXED: Use \Exception
        }
        
        $unresolved[] = $manager_key;
        
        if (isset($this->managers[$manager_key])) {
            foreach ($this->managers[$manager_key]['dependencies'] as $dependency) {
                $this->resolve_dependency($dependency, $resolved, $unresolved);
            }
        }
        
        $resolved[] = $manager_key;
        $unresolved = array_diff($unresolved, array($manager_key));
    }
    
    /**
     * Load a single manager
     */
    private function load_manager($manager_key) {
        if (isset($this->loaded_managers[$manager_key])) {
            return true; // Already loaded
        }
        
        if (!isset($this->managers[$manager_key])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MoBooking: Unknown manager requested: {$manager_key}");
            }
            return false;
        }
        
        $manager_info = $this->managers[$manager_key];
        
        // Check if file exists
        $file_path = MOBOOKING_PATH . $manager_info['file'];
        if (!file_exists($file_path)) {
            if ($manager_info['required']) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Required manager file not found: {$file_path}");
                }
                throw new \Exception("Required manager file not found: {$manager_info['file']}"); // FIXED: Use \Exception
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Optional manager file not found, skipping: {$file_path}");
                }
                return false;
            }
        }
        
        // Check conditions (e.g., is_admin)
        if (isset($manager_info['condition'])) {
            if ($manager_info['condition'] === 'is_admin' && !is_admin()) {
                return false;
            }
        }
        
        // Check dependencies are loaded
        foreach ($manager_info['dependencies'] as $dependency) {
            if (!isset($this->loaded_managers[$dependency])) {
                if ($manager_info['required']) {
                    throw new \Exception("Manager {$manager_key} requires {$dependency} which is not loaded"); // FIXED: Use \Exception
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MoBooking: Skipping optional manager {$manager_key} due to missing dependency {$dependency}");
                    }
                    return false;
                }
            }
        }
        
        // Load the manager
        try {
            if (!class_exists($manager_info['class'])) {
                require_once $file_path;
            }
            
            if (class_exists($manager_info['class'])) {
                $instance = new $manager_info['class']();
                $this->loaded_managers[$manager_key] = $instance;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Successfully loaded {$manager_key} - {$manager_info['description']}");
                }
                
                return true;
            } else {
                throw new \Exception("Class {$manager_info['class']} not found after including file"); // FIXED: Use \Exception
            }
            
        } catch (\Exception $e) { // FIXED: Use \Exception
            if ($manager_info['required']) {
                throw new \Exception("Failed to load required manager {$manager_key}: " . $e->getMessage()); // FIXED: Use \Exception
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MoBooking: Failed to load optional manager {$manager_key}: " . $e->getMessage());
                }
                return false;
            }
        }
    }
    
    /**
     * Get loaded managers status
     */
    public function get_status() {
        $status = array();
        
        foreach ($this->managers as $key => $info) {
            $status[$key] = array(
                'loaded' => isset($this->loaded_managers[$key]),
                'required' => $info['required'],
                'file_exists' => file_exists(MOBOOKING_PATH . $info['file']),
                'class_exists' => class_exists($info['class'])
            );
        }
        
        return $status;
    }
    
    /**
     * Theme setup function
     */
    public function theme_setup() {
        // Add theme support
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
        add_theme_support('responsive-embeds');
        add_theme_support('editor-styles');
        add_theme_support('align-wide');
        
        // Register navigation menus
        register_nav_menus(array(
            'primary' => esc_html__('Primary Menu', 'mobooking'),
            'footer' => esc_html__('Footer Menu', 'mobooking'),
            'dashboard' => esc_html__('Dashboard Menu', 'mobooking'),
        ));
        
        // Set content width
        global $content_width;
        if (!isset($content_width)) {
            $content_width = 1200;
        }
        
        // Load textdomain
        load_theme_textdomain('mobooking', MOBOOKING_PATH . '/languages');
        
        // Add custom image sizes
        add_image_size('mobooking-service-thumbnail', 300, 200, true);
        add_image_size('mobooking-service-large', 600, 400, true);
        add_image_size('mobooking-avatar', 100, 100, true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Theme setup completed');
        }
    }
    
    /**
     * Register custom post types if needed
     */
    public function register_post_types() {
        // Currently using custom database tables instead of post types
        // This function is available for future expansion
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Admin-specific initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MoBooking: Admin initialization completed');
        }
    }
}
