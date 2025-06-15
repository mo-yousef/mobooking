<?php
/**
 * Custom rewrite rules.
 */

/**
 * Add custom rewrite rules for the dashboard sections.
 */
function mobooking_custom_rewrite_rules() {
    add_rewrite_tag('%section%', '([^&]+)');
    add_rewrite_rule('^dashboard/([^/]*)/?', 'index.php?pagename=dashboard&section=\$matches[1]', 'top');
}
add_action('init', 'mobooking_custom_rewrite_rules');

/**
 * Flush rewrite rules on theme activation
 */
function mobooking_flush_rewrite_rules() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'mobooking_flush_rewrite_rules');
