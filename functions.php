<?php
/**
 * MoBooking Theme Functions - Organized & Clean
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('MOBOOKING_VERSION', '1.0.2');
define('MOBOOKING_PATH', get_template_directory());
define('MOBOOKING_URL', get_template_directory_uri());

// Load organized include files
require_once MOBOOKING_PATH . '/includes/autoloader.php';
require_once MOBOOKING_PATH . '/includes/theme-setup.php';
require_once MOBOOKING_PATH . '/includes/enqueue-scripts.php';
require_once MOBOOKING_PATH . '/includes/ajax-handlers.php';
require_once MOBOOKING_PATH . '/includes/helper-functions.php';
require_once MOBOOKING_PATH . '/includes/database-functions.php';
require_once MOBOOKING_PATH . '/includes/debug-functions.php';
require_once MOBOOKING_PATH . '/includes/performance-functions.php';
require_once MOBOOKING_PATH . '/includes/admin-functions.php';
require_once MOBOOKING_PATH . '/includes/auth-functions.php';
require_once MOBOOKING_PATH . '/includes/rewrite-rules.php';
require_once MOBOOKING_PATH . '/includes/dashboard-functions.php';
require_once MOBOOKING_PATH . '/includes/hooks.php';
