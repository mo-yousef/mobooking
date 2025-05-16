<?php
namespace MoBooking\Notifications;

/**
 * Notifications Manager class
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here for now
    }

    /**
     * Send booking confirmation email to customer
     */
    public function send_booking_confirmation($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $to = $booking['customer_email'];
        $subject = sprintf(__('Booking Confirmation - %s', 'mobooking'), $booking['company_name']);
        $message = $this->get_booking_confirmation_template($booking);
        $headers = $this->get_email_headers($booking['user_id']);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send booking notification to business owner
     */
    public function send_admin_notification($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $user = get_userdata($booking['user_id']);
        if (!$user) {
            return false;
        }
        
        $to = $user->user_email;
        $subject = sprintf(__('New Booking - %s', 'mobooking'), $booking['customer_name']);
        $message = $this->get_admin_notification_template($booking);
        $headers = $this->get_email_headers($booking['user_id']);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send booking status update to customer
     */
    public function send_status_update($booking_id) {
        $booking = $this->get_booking_data($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $to = $booking['customer_email'];
        
        if ($booking['status'] == 'confirmed') {
            $subject = sprintf(__('Booking Confirmed - %s', 'mobooking'), $booking['company_name']);
            $message = $this->get_booking_confirmed_template($booking);
        } elseif ($booking['status'] == 'cancelled') {
            $subject = sprintf(__('Booking Cancelled - %s', 'mobooking'), $booking['company_name']);
            $message = $this->get_booking_cancelled_template($booking);
        } else {
            $subject = sprintf(__('Booking Update - %s', 'mobooking'), $booking['company_name']);
            $message = $this->get_booking_status_update_template($booking);
        }
        
        $headers = $this->get_email_headers($booking['user_id']);
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get booking data for email templates
     */
    private function get_booking_data($booking_id) {
        global $wpdb;
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';
        $settings_table = $wpdb->prefix . 'mobooking_settings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.company_name, s.primary_color, s.logo_url
            FROM $bookings_table b
            LEFT JOIN $settings_table s ON b.user_id = s.user_id
            WHERE b.id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return false;
        }
        
        // If no company name from settings, get from user
        if (empty($booking['company_name'])) {
            $user = get_userdata($booking['user_id']);
            $booking['company_name'] = $user ? $user->display_name : __('Cleaning Service', 'mobooking');
        }
        
        // Decode services
        $booking['services_array'] = json_decode($booking['services'], true);
        
        return $booking;
    }
    
    /**
     * Get email headers
     */
    private function get_email_headers($user_id) {
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($user_id);
        
        $from_name = $settings->company_name;
        $from_email = get_option('admin_email');
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
        
        return $headers;
    }
    
    /**
     * Get booking confirmation email template
     */
    private function get_booking_confirmation_template($booking) {
        ob_start();
        
        // Get email header
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking['user_id']);
        
        // Replace placeholders in header
        $email_header = $settings->email_header;
        $email_header = str_replace('{{company_name}}', $booking['company_name'], $email_header);
        
        // Replace placeholders in footer
        $email_footer = $settings->email_footer;
        $email_footer = str_replace('{{company_name}}', $booking['company_name'], $email_footer);
        $email_footer = str_replace('{{current_year}}', date('Y'), $email_footer);
        $email_footer = str_replace('{{phone}}', $settings->phone, $email_footer);
        $email_footer = str_replace('{{email}}', get_option('admin_email'), $email_footer);
        
        $primary_color = !empty($booking['primary_color']) ? $booking['primary_color'] : '#4CAF50';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Booking Confirmation', 'mobooking'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .booking-details { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
                .services-list { margin: 20px 0; }
                .service-item { margin-bottom: 10px; }
                .total { margin-top: 20px; font-weight: bold; }
                .button { display: inline-block; padding: 10px 20px; background-color: <?php echo esc_attr($primary_color); ?>; color: white; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php echo $email_header; ?>
                
                <h2><?php _e('Thank You for Your Booking!', 'mobooking'); ?></h2>
                
                <p><?php printf(__('Hello %s,', 'mobooking'), $booking['customer_name']); ?></p>
                
                <p><?php _e('Your booking has been received and is now pending confirmation. Below you will find the details of your booking.', 'mobooking'); ?></p>
                
                <div class="booking-details">
                    <h3><?php _e('Booking Information', 'mobooking'); ?></h3>
                    
                    <p><strong><?php _e('Booking Reference:', 'mobooking'); ?></strong> #<?php echo $booking['id']; ?></p>
                    <p><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['service_date'])); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php echo ucfirst($booking['status']); ?></p>
                    
                    <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                    <div class="services-list">
                        <?php 
                        $services = $booking['services_array'];
                        if (is_array($services)) {
                            foreach ($services as $service) {
                                ?>
                                <div class="service-item">
                                    <strong><?php echo esc_html($service['name']); ?></strong> - <?php echo wc_price($service['price']); ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($booking['discount_code'])) : ?>
                        <p><strong><?php _e('Discount Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['discount_code']); ?></p>
                        <p><strong><?php _e('Discount Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking['discount_amount']); ?></p>
                    <?php endif; ?>
                    
                    <div class="total">
                        <p><strong><?php _e('Total:', 'mobooking'); ?></strong> <?php echo wc_price($booking['total_price']); ?></p>
                    </div>
                </div>
                
                <h3><?php _e('Your Information', 'mobooking'); ?></h3>
                <p><strong><?php _e('Name:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_name']); ?></p>
                <p><strong><?php _e('Email:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_email']); ?></p>
                <?php if (!empty($booking['customer_phone'])) : ?>
                    <p><strong><?php _e('Phone:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_phone']); ?></p>
                <?php endif; ?>
                <p><strong><?php _e('Address:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_address']); ?></p>
                <p><strong><?php _e('ZIP Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['zip_code']); ?></p>
                
                <?php if (!empty($booking['notes'])) : ?>
                    <h3><?php _e('Additional Notes', 'mobooking'); ?></h3>
                    <p><?php echo nl2br(esc_html($booking['notes'])); ?></p>
                <?php endif; ?>
                
                <p><?php _e('We will contact you shortly to confirm your booking.', 'mobooking'); ?></p>
                
                <p><?php _e('Thank you for choosing us!', 'mobooking'); ?></p>
                
                <?php echo $email_footer; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get admin notification email template
     */
    private function get_admin_notification_template($booking) {
        ob_start();
        
        // Get email header
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking['user_id']);
        
        // Replace placeholders in header
        $email_header = $settings->email_header;
        $email_header = str_replace('{{company_name}}', $booking['company_name'], $email_header);
        
        // Replace placeholders in footer
        $email_footer = $settings->email_footer;
        $email_footer = str_replace('{{company_name}}', $booking['company_name'], $email_footer);
        $email_footer = str_replace('{{current_year}}', date('Y'), $email_footer);
        $email_footer = str_replace('{{phone}}', $settings->phone, $email_footer);
        $email_footer = str_replace('{{email}}', get_option('admin_email'), $email_footer);
        
        $primary_color = !empty($booking['primary_color']) ? $booking['primary_color'] : '#4CAF50';
        $dashboard_url = home_url('/dashboard/bookings/');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('New Booking Notification', 'mobooking'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .booking-details { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
                .services-list { margin: 20px 0; }
                .service-item { margin-bottom: 10px; }
                .total { margin-top: 20px; font-weight: bold; }
                .button { display: inline-block; padding: 10px 20px; background-color: <?php echo esc_attr($primary_color); ?>; color: white; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php echo $email_header; ?>
                
                <h2><?php _e('New Booking Received', 'mobooking'); ?></h2>
                
                <p><?php _e('You have received a new booking. Below you will find the details.', 'mobooking'); ?></p>
                
                <div class="booking-details">
                    <h3><?php _e('Booking Information', 'mobooking'); ?></h3>
                    
                    <p><strong><?php _e('Booking Reference:', 'mobooking'); ?></strong> #<?php echo $booking['id']; ?></p>
                    <p><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['service_date'])); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php echo ucfirst($booking['status']); ?></p>
                    
                    <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                    <div class="services-list">
                        <?php 
                        $services = $booking['services_array'];
                        if (is_array($services)) {
                            foreach ($services as $service) {
                                ?>
                                <div class="service-item">
                                    <strong><?php echo esc_html($service['name']); ?></strong> - <?php echo wc_price($service['price']); ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($booking['discount_code'])) : ?>
                        <p><strong><?php _e('Discount Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['discount_code']); ?></p>
                        <p><strong><?php _e('Discount Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking['discount_amount']); ?></p>
                    <?php endif; ?>
                    
                    <div class="total">
                        <p><strong><?php _e('Total:', 'mobooking'); ?></strong> <?php echo wc_price($booking['total_price']); ?></p>
                    </div>
                </div>
                
                <h3><?php _e('Customer Information', 'mobooking'); ?></h3>
                <p><strong><?php _e('Name:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_name']); ?></p>
                <p><strong><?php _e('Email:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_email']); ?></p>
                <?php if (!empty($booking['customer_phone'])) : ?>
                    <p><strong><?php _e('Phone:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_phone']); ?></p>
                <?php endif; ?>
                <p><strong><?php _e('Address:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_address']); ?></p>
                <p><strong><?php _e('ZIP Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['zip_code']); ?></p>
                
                <?php if (!empty($booking['notes'])) : ?>
                    <h3><?php _e('Additional Notes', 'mobooking'); ?></h3>
                    <p><?php echo nl2br(esc_html($booking['notes'])); ?></p>
                <?php endif; ?>
                
                <p>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="button"><?php _e('View Booking in Dashboard', 'mobooking'); ?></a>
                </p>
                
                <?php echo $email_footer; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get booking confirmed email template
     */
    private function get_booking_confirmed_template($booking) {
        ob_start();
        
        // Get email header
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking['user_id']);
        
        // Replace placeholders in header
        $email_header = $settings->email_header;
        $email_header = str_replace('{{company_name}}', $booking['company_name'], $email_header);
        
        // Replace placeholders in footer
        $email_footer = $settings->email_footer;
        $email_footer = str_replace('{{company_name}}', $booking['company_name'], $email_footer);
        $email_footer = str_replace('{{current_year}}', date('Y'), $email_footer);
        $email_footer = str_replace('{{phone}}', $settings->phone, $email_footer);
        $email_footer = str_replace('{{email}}', get_option('admin_email'), $email_footer);
        
        $primary_color = !empty($booking['primary_color']) ? $booking['primary_color'] : '#4CAF50';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Booking Confirmed', 'mobooking'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .booking-details { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
                .services-list { margin: 20px 0; }
                .service-item { margin-bottom: 10px; }
                .total { margin-top: 20px; font-weight: bold; }
                .button { display: inline-block; padding: 10px 20px; background-color: <?php echo esc_attr($primary_color); ?>; color: white; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php echo $email_header; ?>
                
                <h2><?php _e('Your Booking is Confirmed!', 'mobooking'); ?></h2>
                
                <p><?php printf(__('Hello %s,', 'mobooking'), $booking['customer_name']); ?></p>
                
                <p><?php _e('Great news! Your booking has been confirmed. We look forward to providing our services to you.', 'mobooking'); ?></p>
                
                <div class="booking-details">
                    <h3><?php _e('Booking Information', 'mobooking'); ?></h3>
                    
                    <p><strong><?php _e('Booking Reference:', 'mobooking'); ?></strong> #<?php echo $booking['id']; ?></p>
                    <p><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['service_date'])); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php _e('Confirmed', 'mobooking'); ?></p>
                    
                    <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                    <div class="services-list">
                        <?php 
                        $services = $booking['services_array'];
                        if (is_array($services)) {
                            foreach ($services as $service) {
                                ?>
                                <div class="service-item">
                                    <strong><?php echo esc_html($service['name']); ?></strong> - <?php echo wc_price($service['price']); ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($booking['discount_code'])) : ?>
                        <p><strong><?php _e('Discount Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['discount_code']); ?></p>
                        <p><strong><?php _e('Discount Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking['discount_amount']); ?></p>
                    <?php endif; ?>
                    
                    <div class="total">
                        <p><strong><?php _e('Total:', 'mobooking'); ?></strong> <?php echo wc_price($booking['total_price']); ?></p>
                    </div>
                </div>
                
                <h3><?php _e('Important Information', 'mobooking'); ?></h3>
                <p><?php _e('Our team will arrive at the scheduled date and time. Please ensure that someone is available to provide access.', 'mobooking'); ?></p>
                
                <p><?php _e('If you need to make any changes to your booking, please contact us as soon as possible.', 'mobooking'); ?></p>
                
                <p><?php _e('Thank you for choosing our services!', 'mobooking'); ?></p>
                
                <?php echo $email_footer; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get booking cancelled email template
     */
    private function get_booking_cancelled_template($booking) {
        ob_start();
        
        // Get email header
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking['user_id']);
        
        // Replace placeholders in header
        $email_header = $settings->email_header;
        $email_header = str_replace('{{company_name}}', $booking['company_name'], $email_header);
        
        // Replace placeholders in footer
        $email_footer = $settings->email_footer;
        $email_footer = str_replace('{{company_name}}', $booking['company_name'], $email_footer);
        $email_footer = str_replace('{{current_year}}', date('Y'), $email_footer);
        $email_footer = str_replace('{{phone}}', $settings->phone, $email_footer);
        $email_footer = str_replace('{{email}}', get_option('admin_email'), $email_footer);
        
        $primary_color = !empty($booking['primary_color']) ? $booking['primary_color'] : '#4CAF50';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Booking Cancelled', 'mobooking'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .booking-details { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
                .services-list { margin: 20px 0; }
                .service-item { margin-bottom: 10px; }
                .total { margin-top: 20px; font-weight: bold; }
                .button { display: inline-block; padding: 10px 20px; background-color: <?php echo esc_attr($primary_color); ?>; color: white; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php echo $email_header; ?>
                
                <h2><?php _e('Booking Cancelled', 'mobooking'); ?></h2>
                
                <p><?php printf(__('Hello %s,', 'mobooking'), $booking['customer_name']); ?></p>
                
                <p><?php _e('We regret to inform you that your booking has been cancelled. Below are the details of the cancelled booking.', 'mobooking'); ?></p>
                
                <div class="booking-details">
                    <h3><?php _e('Booking Information', 'mobooking'); ?></h3>
                    
                    <p><strong><?php _e('Booking Reference:', 'mobooking'); ?></strong> #<?php echo $booking['id']; ?></p>
                    <p><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['service_date'])); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php _e('Cancelled', 'mobooking'); ?></p>
                    
                    <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                    <div class="services-list">
                        <?php 
                        $services = $booking['services_array'];
                        if (is_array($services)) {
                            foreach ($services as $service) {
                                ?>
                                <div class="service-item">
                                    <strong><?php echo esc_html($service['name']); ?></strong> - <?php echo wc_price($service['price']); ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($booking['discount_code'])) : ?>
                        <p><strong><?php _e('Discount Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['discount_code']); ?></p>
                        <p><strong><?php _e('Discount Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking['discount_amount']); ?></p>
                    <?php endif; ?>
                    
                    <div class="total">
                        <p><strong><?php _e('Total:', 'mobooking'); ?></strong> <?php echo wc_price($booking['total_price']); ?></p>
                    </div>
                </div>
                
                <p><?php _e('If you would like to make a new booking, please visit our website.', 'mobooking'); ?></p>
                
                <p><?php _e('We apologize for any inconvenience this may have caused.', 'mobooking'); ?></p>
                
                <?php echo $email_footer; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get booking status update email template
     */
    private function get_booking_status_update_template($booking) {
        ob_start();
        
        // Get email header
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking['user_id']);
        
        // Replace placeholders in header
        $email_header = $settings->email_header;
        $email_header = str_replace('{{company_name}}', $booking['company_name'], $email_header);
        
        // Replace placeholders in footer
        $email_footer = $settings->email_footer;
        $email_footer = str_replace('{{company_name}}', $booking['company_name'], $email_footer);
        $email_footer = str_replace('{{current_year}}', date('Y'), $email_footer);
        $email_footer = str_replace('{{phone}}', $settings->phone, $email_footer);
        $email_footer = str_replace('{{email}}', get_option('admin_email'), $email_footer);
        
        $primary_color = !empty($booking['primary_color']) ? $booking['primary_color'] : '#4CAF50';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Booking Update', 'mobooking'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; }
                .booking-details { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
                .services-list { margin: 20px 0; }
                .service-item { margin-bottom: 10px; }
                .total { margin-top: 20px; font-weight: bold; }
                .button { display: inline-block; padding: 10px 20px; background-color: <?php echo esc_attr($primary_color); ?>; color: white; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <?php echo $email_header; ?>
                
                <h2><?php _e('Booking Update', 'mobooking'); ?></h2>
                
                <p><?php printf(__('Hello %s,', 'mobooking'), $booking['customer_name']); ?></p>
                
                <p><?php _e('There has been an update to your booking. The current status is now:', 'mobooking'); ?> <strong><?php echo ucfirst($booking['status']); ?></strong></p>
                
                <div class="booking-details">
                    <h3><?php _e('Booking Information', 'mobooking'); ?></h3>
                    
                    <p><strong><?php _e('Booking Reference:', 'mobooking'); ?></strong> #<?php echo $booking['id']; ?></p>
                    <p><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['service_date'])); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php echo ucfirst($booking['status']); ?></p>
                    
                    <h4><?php _e('Selected Services', 'mobooking'); ?></h4>
                    <div class="services-list">
                        <?php 
                        $services = $booking['services_array'];
                        if (is_array($services)) {
                            foreach ($services as $service) {
                                ?>
                                <div class="service-item">
                                    <strong><?php echo esc_html($service['name']); ?></strong> - <?php echo wc_price($service['price']); ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($booking['discount_code'])) : ?>
                        <p><strong><?php _e('Discount Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['discount_code']); ?></p>
                        <p><strong><?php _e('Discount Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking['discount_amount']); ?></p>
                    <?php endif; ?>
                    
                    <div class="total">
                        <p><strong><?php _e('Total:', 'mobooking'); ?></strong> <?php echo wc_price($booking['total_price']); ?></p>
                    </div>
                </div>
                
                <p><?php _e('If you have any questions about this update, please contact us.', 'mobooking'); ?></p>
                
                <p><?php _e('Thank you for choosing our services!', 'mobooking'); ?></p>
                
                <?php echo $email_footer; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}