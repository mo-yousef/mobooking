<?php
namespace MoBooking\Services;

/**
 * Services Manager class
 * Handles services and their options
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register basic AJAX handlers
        add_action('wp_ajax_mobooking_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_mobooking_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_mobooking_get_service', array($this, 'ajax_get_service'));
        add_action('wp_ajax_mobooking_get_services', array($this, 'ajax_get_services'));
    }

    /**
     * Get services for a user
     */
    public function get_user_services($user_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'mobooking_services';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $services_table WHERE user_id = %d ORDER BY name ASC",
            $user_id
        ));
    }

    /**
     * Check if a service has any options
     */
    public function has_service_options($service_id) {
        // Basic implementation
        return false;
    }

    /**
     * Placeholder for AJAX save service handler
     */
    public function ajax_save_service() {
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }

    /**
     * Placeholder for AJAX delete service handler
     */
    public function ajax_delete_service() {
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }

    /**
     * Placeholder for AJAX get service handler
     */
    public function ajax_get_service() {
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }

    /**
     * Placeholder for AJAX get services handler
     */
    public function ajax_get_services() {
        wp_send_json_error(array('message' => 'Not implemented yet'));
    }
}