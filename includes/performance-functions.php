<?php
/**
 * Performance optimization functions.
 */

/**
 * Remove query strings from static resources
 */
function remove_query_strings_from_static_resources($src) {
    $parts = explode('?ver', $src);
    return $parts[0];
}

/**
 * Performance optimizations
 */
function mobooking_performance_optimizations() {
    // Remove query strings for better caching
    add_filter('script_loader_src', 'remove_query_strings_from_static_resources', 15);
    add_filter('style_loader_src', 'remove_query_strings_from_static_resources', 15);
    
    // Disable file editing
    if (!defined('DISALLOW_FILE_EDIT')) {
        define('DISALLOW_FILE_EDIT', true);
    }
    
    // Limit post revisions
    if (!defined('WP_POST_REVISIONS')) {
        define('WP_POST_REVISIONS', 3);
    }
}
add_action('init', 'mobooking_performance_optimizations');
