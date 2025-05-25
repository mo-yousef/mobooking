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
        // Register hooks for notifications
        add_action('wp_ajax_mobooking_send_test_email', array($this, 'ajax_send_test_email'));
    }
    
    /**
     * Send booking confirmation email to customer
     */
    public function send_booking_confirmation($booking_id) {
        $bookings_manager = new \MoBooking\Bookings\Manager();
        $booking = $bookings_manager->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Get business owner settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking->user_id);
        
        // Email details
        $to = $booking->customer_email;
        $subject = sprintf(__('Booking Confirmation - %s', 'mobooking'), $settings->company_name);
        
        // Build email content
        $message = $this->build_confirmation_email($booking, $settings);
        
        // Send email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings->company_name . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send admin notification about new booking
     */
    public function send_admin_notification($booking_id) {
        $bookings_manager = new \MoBooking\Bookings\Manager();
        $booking = $bookings_manager->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Get business owner info
        $user = get_userdata($booking->user_id);
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking->user_id);
        
        // Email details
        $to = $user->user_email;
        $subject = sprintf(__('New Booking Received - #%d', 'mobooking'), $booking->id);
        
        // Build email content
        $message = $this->build_admin_notification_email($booking, $settings);
        
        // Send email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: MoBooking <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send status update notification
     */
    public function send_status_update($booking_id) {
        $bookings_manager = new \MoBooking\Bookings\Manager();
        $booking = $bookings_manager->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Get business owner settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($booking->user_id);
        
        // Email details
        $to = $booking->customer_email;
        $subject = sprintf(__('Booking Update - #%d', 'mobooking'), $booking->id);
        
        // Build email content
        $message = $this->build_status_update_email($booking, $settings);
        
        // Send email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings->company_name . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Build booking confirmation email
     */
    private function build_confirmation_email($booking, $settings) {
        $services = json_decode($booking->services, true);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Booking Confirmation', 'mobooking'); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <?php if (!empty($settings->email_header)) : ?>
                <?php echo $this->process_email_template($settings->email_header, $booking, $settings); ?>
            <?php endif; ?>
            
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin-top: 0;">
                    <?php _e('Booking Confirmation', 'mobooking'); ?>
                </h2>
                
                <p><?php _e('Dear', 'mobooking'); ?> <?php echo esc_html($booking->customer_name); ?>,</p>
                
                <p><?php echo esc_html($settings->booking_confirmation_message); ?></p>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3><?php _e('Booking Details', 'mobooking'); ?></h3>
                    <p><strong><?php _e('Booking ID:', 'mobooking'); ?></strong> #<?php echo esc_html($booking->id); ?></p>
                    <p><strong><?php _e('Service Date:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)); ?></p>
                    <p><strong><?php _e('Service Address:', 'mobooking'); ?></strong><br><?php echo nl2br(esc_html($booking->customer_address)); ?></p>
                    
                    <h4><?php _e('Services Booked:', 'mobooking'); ?></h4>
                    <?php if (is_array($services)) : ?>
                        <ul>
                            <?php foreach ($services as $service) : ?>
                                <li><?php echo esc_html($service['name']); ?> - <?php echo wc_price($service['price']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <p><strong><?php _e('Total Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking->total_price); ?></p>
                    
                    <?php if (!empty($booking->notes)) : ?>
                        <p><strong><?php _e('Special Instructions:', 'mobooking'); ?></strong><br><?php echo nl2br(esc_html($booking->notes)); ?></p>
                    <?php endif; ?>
                </div>
                
                <p><?php _e('We will contact you to confirm the appointment details. If you have any questions, please don\'t hesitate to reach out.', 'mobooking'); ?></p>
                
                <p><?php _e('Thank you for choosing our services!', 'mobooking'); ?></p>
            </div>
            
            <?php if (!empty($settings->email_footer)) : ?>
                <?php echo $this->process_email_template($settings->email_footer, $booking, $settings); ?>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build admin notification email
     */
    private function build_admin_notification_email($booking, $settings) {
        $services = json_decode($booking->services, true);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('New Booking Received', 'mobooking'); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid <?php echo esc_attr($settings->primary_color); ?>;">
                <h2 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin-top: 0;">
                    <?php _e('New Booking Received!', 'mobooking'); ?>
                </h2>
                
                <p><?php _e('You have received a new booking. Here are the details:', 'mobooking'); ?></p>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3><?php _e('Customer Information', 'mobooking'); ?></h3>
                    <p><strong><?php _e('Name:', 'mobooking'); ?></strong> <?php echo esc_html($booking->customer_name); ?></p>
                    <p><strong><?php _e('Email:', 'mobooking'); ?></strong> <?php echo esc_html($booking->customer_email); ?></p>
                    <?php if (!empty($booking->customer_phone)) : ?>
                        <p><strong><?php _e('Phone:', 'mobooking'); ?></strong> <?php echo esc_html($booking->customer_phone); ?></p>
                    <?php endif; ?>
                    <p><strong><?php _e('Address:', 'mobooking'); ?></strong><br><?php echo nl2br(esc_html($booking->customer_address)); ?></p>
                    
                    <h3><?php _e('Booking Details', 'mobooking'); ?></h3>
                    <p><strong><?php _e('Booking ID:', 'mobooking'); ?></strong> #<?php echo esc_html($booking->id); ?></p>
                    <p><strong><?php _e('Service Date:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php echo esc_html(ucfirst($booking->status)); ?></p>
                    
                    <h4><?php _e('Services Requested:', 'mobooking'); ?></h4>
                    <?php if (is_array($services)) : ?>
                        <ul>
                            <?php foreach ($services as $service) : ?>
                                <li><?php echo esc_html($service['name']); ?> - <?php echo wc_price($service['price']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <p><strong><?php _e('Total Amount:', 'mobooking'); ?></strong> <?php echo wc_price($booking->total_price); ?></p>
                    
                    <?php if (!empty($booking->notes)) : ?>
                        <p><strong><?php _e('Customer Notes:', 'mobooking'); ?></strong><br><?php echo nl2br(esc_html($booking->notes)); ?></p>
                    <?php endif; ?>
                </div>
                
                <p style="text-align: center;">
                    <a href="<?php echo esc_url(home_url('/dashboard/bookings/')); ?>" 
                       style="background: <?php echo esc_attr($settings->primary_color); ?>; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                        <?php _e('View in Dashboard', 'mobooking'); ?>
                    </a>
                </p>
                
                <p><?php _e('Please contact the customer to confirm the appointment details.', 'mobooking'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build status update email
     */
    private function build_status_update_email($booking, $settings) {
        $status_messages = array(
            'confirmed' => __('Your booking has been confirmed! We will see you at the scheduled time.', 'mobooking'),
            'cancelled' => __('Your booking has been cancelled. If you have any questions, please contact us.', 'mobooking'),
            'completed' => __('Your service has been completed. Thank you for choosing our services!', 'mobooking')
        );
        
        $message = isset($status_messages[$booking->status]) ? $status_messages[$booking->status] : __('Your booking status has been updated.', 'mobooking');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Booking Update', 'mobooking'); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            <?php if (!empty($settings->email_header)) : ?>
                <?php echo $this->process_email_template($settings->email_header, $booking, $settings); ?>
            <?php endif; ?>
            
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin-top: 0;">
                    <?php _e('Booking Update', 'mobooking'); ?>
                </h2>
                
                <p><?php _e('Dear', 'mobooking'); ?> <?php echo esc_html($booking->customer_name); ?>,</p>
                
                <p><?php echo esc_html($message); ?></p>
                
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <p><strong><?php _e('Booking ID:', 'mobooking'); ?></strong> #<?php echo esc_html($booking->id); ?></p>
                    <p><strong><?php _e('Status:', 'mobooking'); ?></strong> <?php echo esc_html(ucfirst($booking->status)); ?></p>
                    <p><strong><?php _e('Service Date:', 'mobooking'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->service_date)); ?></p>
                </div>
                
                <p><?php _e('If you have any questions, please don\'t hesitate to contact us.', 'mobooking'); ?></p>
            </div>
            
            <?php if (!empty($settings->email_footer)) : ?>
                <?php echo $this->process_email_template($settings->email_footer, $booking, $settings); ?>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Process email template variables
     */
    private function process_email_template($template, $booking, $settings) {
        $replacements = array(
            '{{company_name}}' => $settings->company_name,
            '{{customer_name}}' => $booking->customer_name,
            '{{booking_id}}' => $booking->id,
            '{{current_year}}' => date('Y'),
            '{{phone}}' => $settings->phone,
            '{{email}}' => get_userdata($booking->user_id)->user_email
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * AJAX handler to send test email
     */
    public function ajax_send_test_email() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-test-email-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : $user->user_email;
        
        if (!is_email($email)) {
            wp_send_json_error(__('Invalid email address.', 'mobooking'));
        }
        
        // Get settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings = $settings_manager->get_settings($user_id);
        
        // Send test email
        $subject = sprintf(__('Test Email from %s', 'mobooking'), $settings->company_name);
        $message = $settings->email_header . '<p>' . __('This is a test email from your MoBooking setup.', 'mobooking') . '</p>' . $settings->email_footer;
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $settings->company_name . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        );
        
        $result = wp_mail($email, $subject, $message, $headers);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Test email sent successfully.', 'mobooking')
            ));
        } else {
            wp_send_json_error(__('Failed to send test email.', 'mobooking'));
        }
    }
}