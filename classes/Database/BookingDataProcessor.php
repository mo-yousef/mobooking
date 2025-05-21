<?php
namespace MoBooking\Database;

/**
 * Class to process and format booking data with related services and options
 */
class BookingDataProcessor {
    /**
     * Process booking data and include detailed service and option info
     * 
     * @param object $booking The booking record
     * @return object The processed booking with detailed service info
     */
    public function process_booking($booking) {
        if (!$booking) {
            return null;
        }
        
        // Parse services JSON
        if (!empty($booking->services)) {
            $booking->services_array = json_decode($booking->services, true);
        } else {
            $booking->services_array = array();
        }
        
        // Parse options JSON if present
        if (!empty($booking->service_options)) {
            $booking->options_array = json_decode($booking->service_options, true);
        } else {
            $booking->options_array = array();
        }
        
        // Calculate total duration
        $booking->total_duration = $this->calculate_total_duration($booking->services_array);
        
        // Format date and time
        if (!empty($booking->service_date)) {
            $booking->formatted_date = date_i18n(get_option('date_format'), strtotime($booking->service_date));
            $booking->formatted_time = date_i18n(get_option('time_format'), strtotime($booking->service_date));
        }
        
        return $booking;
    }
    
    /**
     * Calculate total duration for all services in a booking
     * 
     * @param array $services Array of services from booking
     * @return int Total duration in minutes
     */
    private function calculate_total_duration($services) {
        $total_duration = 0;
        
        if (is_array($services)) {
            foreach ($services as $service) {
                if (isset($service['duration'])) {
                    $total_duration += intval($service['duration']);
                }
            }
        }
        
        return $total_duration;
    }
    
    /**
     * Process multiple bookings
     * 
     * @param array $bookings Array of booking records
     * @return array Processed bookings
     */
    public function process_bookings($bookings) {
        $processed_bookings = array();
        
        foreach ($bookings as $booking) {
            $processed_bookings[] = $this->process_booking($booking);
        }
        
        return $processed_bookings;
    }
    
    /**
     * Calculate booking statistics for a user
     * 
     * @param int $user_id User ID
     * @param string $period Period for stats calculation (today, week, month, year, all)
     * @return array Booking statistics
     */
    public function calculate_booking_stats($user_id, $period = 'all') {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';
        
        // Base query to get total bookings
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(total_price) as revenue
                FROM $bookings_table 
                WHERE user_id = %d";
        
        // Add time period filter if needed
        $query_params = array($user_id);
        
        switch ($period) {
            case 'today':
                $query .= " AND DATE(service_date) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(service_date, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $query .= " AND MONTH(service_date) = MONTH(CURDATE()) AND YEAR(service_date) = YEAR(CURDATE())";
                break;
            case 'year':
                $query .= " AND YEAR(service_date) = YEAR(CURDATE())";
                break;
        }
        
        // Execute query
        $result = $wpdb->get_row($wpdb->prepare($query, $query_params));
        
        // Format results
        $stats = array(
            'total' => intval($result->total),
            'pending' => intval($result->pending),
            'confirmed' => intval($result->confirmed),
            'completed' => intval($result->completed),
            'cancelled' => intval($result->cancelled),
            'revenue' => floatval($result->revenue),
        );
        
        return $stats;
    }
}