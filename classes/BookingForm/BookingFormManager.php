<?php
namespace MoBooking\BookingForm;

/**
 * Booking Form Manager - Handles custom booking forms per user
 */
class BookingFormManager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action('init', array($this, 'register_booking_form_endpoints'));
        add_filter('query_vars', array($this, 'add_booking_form_query_vars'));
        add_filter('template_include', array($this, 'load_booking_form_template'));
        
        // Register AJAX handlers
        add_action('wp_ajax_mobooking_save_booking_form_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_mobooking_get_booking_form_settings', array($this, 'ajax_get_settings'));
        add_action('wp_ajax_mobooking_generate_embed_code', array($this, 'ajax_generate_embed_code'));
        add_action('wp_ajax_mobooking_preview_booking_form', array($this, 'ajax_preview_form'));
        
        // Add shortcodes
        add_shortcode('mobooking_booking_form_public', array($this, 'public_booking_form_shortcode'));
    }
    
    /**
     * Register booking form endpoints
     */
    public function register_booking_form_endpoints() {
        // Public booking form URLs: /booking/{username} or /booking/{user_id}
        add_rewrite_rule('^booking/([^/]+)/?$', 'index.php?mobooking_booking_form=1&booking_user=$matches[1]', 'top');
        
        // Embed form endpoint
        add_rewrite_rule('^booking-embed/([^/]+)/?$', 'index.php?mobooking_booking_embed=1&booking_user=$matches[1]', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_booking_form_query_vars($vars) {
        $vars[] = 'mobooking_booking_form';
        $vars[] = 'mobooking_booking_embed';
        $vars[] = 'booking_user';
        return $vars;
    }
    
    /**
     * Load booking form template
     */
    public function load_booking_form_template($template) {
        global $wp_query;
        
        if (get_query_var('mobooking_booking_form') || get_query_var('mobooking_booking_embed')) {
            $booking_user = get_query_var('booking_user');
            $is_embed = get_query_var('mobooking_booking_embed');
            
            if ($booking_user) {
                // Try to find user by username first, then by ID
                $user = get_user_by('login', $booking_user);
                if (!$user && is_numeric($booking_user)) {
                    $user = get_user_by('id', $booking_user);
                }
                
                if ($user && in_array('mobooking_business_owner', $user->roles)) {
                    // Set global variables for the template
                    global $mobooking_form_user, $mobooking_is_embed;
                    $mobooking_form_user = $user;
                    $mobooking_is_embed = $is_embed;
                    
                    // Load appropriate template
                    $template_file = $is_embed ? 'booking-form-embed.php' : 'booking-form-public.php';
                    $custom_template = MOBOOKING_PATH . '/templates/' . $template_file;
                    
                    if (file_exists($custom_template)) {
                        return $custom_template;
                    }
                } else {
                    // User not found or not a business owner
                    $wp_query->set_404();
                    status_header(404);
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Get booking form settings for a user
     */
    public function get_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
        
        // Create table if it doesn't exist
        $this->maybe_create_settings_table();
        
        $settings = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if (!$settings) {
            return $this->get_default_settings($user_id);
        }
        
        return $settings;
    }
    
    /**
     * Get default settings
     */
private function get_default_settings($user_id) {
    $user = get_userdata($user_id);
    
    return (object) array(
        'user_id' => $user_id,
        'form_title' => $user ? $user->display_name . "'s Booking" : __('Book a Service', 'mobooking'),
        'form_description' => __('Book our professional services quickly and easily.', 'mobooking'),
        'logo_url' => '',
        'primary_color' => '#3b82f6',
        'secondary_color' => '#1e40af',
        'background_color' => '#ffffff',
        'text_color' => '#1f2937',
        'language' => 'en',
        'show_service_descriptions' => 1,
        'show_price_breakdown' => 1,
        'show_form_header' => 1,
        'show_form_footer' => 1,
        'enable_zip_validation' => 1,
        'custom_css' => '',
        'custom_js' => '',  // NEW FIELD
        'form_layout' => 'modern',
        'step_indicator_style' => 'progress', // NEW FIELD
        'button_style' => 'rounded', // NEW FIELD
        'form_width' => 'standard',
        'contact_info' => '',
        'social_links' => '',
        'custom_footer_text' => '',
        'seo_title' => '',
        'seo_description' => '',
        'analytics_code' => '',
        'is_active' => 1
        // REMOVED: 'enable_testimonials' and 'testimonials_data'
    );
}
    
/**
 * Save booking form settings - FIXED checkbox handling
 */
// Update the save_settings method to remove testimonials and add new fields
public function save_settings($user_id, $data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
    
    // Create table if it doesn't exist
    $this->maybe_create_settings_table();
    
    // FIXED: Proper sanitization with NEW FIELDS and REMOVED testimonials
    $sanitized_data = array(
        'form_title' => sanitize_text_field($data['form_title'] ?? ''),
        'form_description' => sanitize_textarea_field($data['form_description'] ?? ''),
        'logo_url' => esc_url_raw($data['logo_url'] ?? ''),
        'primary_color' => sanitize_hex_color($data['primary_color'] ?? '#3b82f6'),
        'secondary_color' => sanitize_hex_color($data['secondary_color'] ?? '#1e40af'),
        'background_color' => sanitize_hex_color($data['background_color'] ?? '#ffffff'),
        'text_color' => sanitize_hex_color($data['text_color'] ?? '#1f2937'),
        'language' => sanitize_text_field($data['language'] ?? 'en'),
        
        // Checkbox fields - proper handling
        'show_service_descriptions' => (!empty($data['show_service_descriptions']) && $data['show_service_descriptions'] == '1') ? 1 : 0,
        'show_price_breakdown' => (!empty($data['show_price_breakdown']) && $data['show_price_breakdown'] == '1') ? 1 : 0,
        'show_form_header' => (!empty($data['show_form_header']) && $data['show_form_header'] == '1') ? 1 : 0,
        'show_form_footer' => (!empty($data['show_form_footer']) && $data['show_form_footer'] == '1') ? 1 : 0,
        'enable_zip_validation' => (!empty($data['enable_zip_validation']) && $data['enable_zip_validation'] == '1') ? 1 : 0,
        
        // Custom code and layout
        'custom_css' => wp_strip_all_tags($data['custom_css'] ?? ''),
        'custom_js' => wp_strip_all_tags($data['custom_js'] ?? ''),  // NEW FIELD
        'form_layout' => sanitize_text_field($data['form_layout'] ?? 'modern'),
        'step_indicator_style' => sanitize_text_field($data['step_indicator_style'] ?? 'progress'), // NEW FIELD
        'button_style' => sanitize_text_field($data['button_style'] ?? 'rounded'), // NEW FIELD
        'form_width' => sanitize_text_field($data['form_width'] ?? 'standard'),
        
        // Footer and contact info
        'contact_info' => sanitize_textarea_field($data['contact_info'] ?? ''),
        'social_links' => sanitize_textarea_field($data['social_links'] ?? ''),
        'custom_footer_text' => wp_kses_post($data['custom_footer_text'] ?? ''),
        
        // SEO and analytics
        'seo_title' => sanitize_text_field($data['seo_title'] ?? ''),
        'seo_description' => sanitize_textarea_field($data['seo_description'] ?? ''),
        'analytics_code' => wp_strip_all_tags($data['analytics_code'] ?? ''),
        'is_active' => (!empty($data['is_active']) && $data['is_active'] == '1') ? 1 : 0
        
        // REMOVED: 'enable_testimonials' and 'testimonials_data'
    );
    
    // Debug: Log what we're actually saving
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking - Saving settings without testimonials:');
        error_log('step_indicator_style: ' . $sanitized_data['step_indicator_style']);
        error_log('button_style: ' . $sanitized_data['button_style']);
        error_log('custom_js: ' . (strlen($sanitized_data['custom_js']) > 0 ? 'Present' : 'Empty'));
    }
    
    // Check if settings exist
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    if ($existing) {
        // Update existing settings
        $result = $wpdb->update(
            $table_name,
            $sanitized_data,
            array('user_id' => $user_id),
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%d', '%d', '%d', '%d', '%d', '%s', '%s', 
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%s', '%d'
            ),
            array('%d')
        );
    } else {
        // Insert new settings
        $result = $wpdb->insert(
            $table_name,
            array_merge($sanitized_data, array('user_id' => $user_id)),
            array(
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%d', '%d', '%d', '%d', '%d', '%s', '%s', 
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', 
                '%s', '%s', '%s', '%d'
            )
        );
    }
    
    return $result !== false;
}

// Add reset settings method
public function reset_settings($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
    
    // Delete existing settings
    $wpdb->delete($table_name, array('user_id' => $user_id), array('%d'));
    
    return true;
}

    /**
     * Generate unique booking form URL
     */
    public function get_booking_form_url($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Use username if available, otherwise use ID
        $identifier = $user->user_login ?: $user_id;
        return home_url('/booking/' . $identifier . '/');
    }
    
    /**
     * Generate embed code
     */
    public function generate_embed_code($user_id, $width = '100%', $height = '800') {
        $embed_url = $this->get_embed_url($user_id);
        if (!$embed_url) {
            return false;
        }
        
        return sprintf(
            '<iframe src="%s" width="%s" height="%s" frameborder="0" scrolling="auto" style="border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></iframe>',
            esc_url($embed_url),
            esc_attr($width),
            esc_attr($height)
        );
    }
    
    /**
     * Get embed URL
     */
    public function get_embed_url($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $identifier = $user->user_login ?: $user_id;
        return home_url('/booking-embed/' . $identifier . '/');
    }
    
    /**
     * Create settings table if it doesn't exist
     */
    private function maybe_create_settings_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mobooking_booking_form_settings';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                form_title varchar(255) DEFAULT '',
                form_description text DEFAULT '',
                logo_url varchar(500) DEFAULT '',
                primary_color varchar(20) DEFAULT '#3b82f6',
                secondary_color varchar(20) DEFAULT '#1e40af',
                background_color varchar(20) DEFAULT '#ffffff',
                text_color varchar(20) DEFAULT '#1f2937',
                language varchar(10) DEFAULT 'en',
                show_service_descriptions tinyint(1) DEFAULT 1,
                show_price_breakdown tinyint(1) DEFAULT 1,
                show_form_header tinyint(1) DEFAULT 1,
                show_form_footer tinyint(1) DEFAULT 1,
                enable_zip_validation tinyint(1) DEFAULT 1,
                custom_css longtext DEFAULT '',
                custom_js longtext DEFAULT '',
                form_layout varchar(50) DEFAULT 'modern',
                step_indicator_style varchar(50) DEFAULT 'progress',
                button_style varchar(50) DEFAULT 'rounded',
                form_width varchar(50) DEFAULT 'standard',
                contact_info text DEFAULT '',
                social_links text DEFAULT '',
                custom_footer_text text DEFAULT '',
                seo_title varchar(255) DEFAULT '',
                seo_description text DEFAULT '',
                analytics_code text DEFAULT '',
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * AJAX handler to save settings
     */
    public function ajax_save_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        
        // Save settings
        $result = $this->save_settings($user_id, $_POST);
        
        if ($result === false) {
            wp_send_json_error(__('Failed to save settings.', 'mobooking'));
        }
        
        // Return URLs
        wp_send_json_success(array(
            'message' => __('Settings saved successfully.', 'mobooking'),
            'booking_url' => $this->get_booking_form_url($user_id),
            'embed_url' => $this->get_embed_url($user_id)
        ));
    }
    
    /**
     * AJAX handler to get settings
     */
    public function ajax_get_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $settings = $this->get_settings($user_id);
        
        wp_send_json_success(array(
            'settings' => $settings,
            'booking_url' => $this->get_booking_form_url($user_id),
            'embed_url' => $this->get_embed_url($user_id)
        ));
    }
    
    /**
     * AJAX handler to generate embed code
     */
    public function ajax_generate_embed_code() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        $width = isset($_POST['width']) ? sanitize_text_field($_POST['width']) : '100%';
        $height = isset($_POST['height']) ? sanitize_text_field($_POST['height']) : '800';
        
        $embed_code = $this->generate_embed_code($user_id, $width, $height);
        
        if (!$embed_code) {
            wp_send_json_error(__('Failed to generate embed code.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'embed_code' => $embed_code,
            'embed_url' => $this->get_embed_url($user_id)
        ));
    }
    
    /**
     * AJAX handler for form preview
     */
    public function ajax_preview_form() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mobooking-booking-form-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check permissions
        if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
            wp_send_json_error(__('You do not have permission to do this.', 'mobooking'));
        }
        
        $user_id = get_current_user_id();
        
        // Generate preview HTML with current settings
        $preview_html = $this->generate_preview_html($user_id, $_POST);
        
        wp_send_json_success(array(
            'html' => $preview_html
        ));
    }
    
    /**
     * Generate preview HTML
     */
    private function generate_preview_html($user_id, $settings) {
        // This would generate a preview of the form with the current settings
        // For now, return a simple preview
        ob_start();
        ?>
        <div class="booking-form-preview" style="
            background-color: <?php echo esc_attr($settings['background_color'] ?? '#ffffff'); ?>;
            color: <?php echo esc_attr($settings['text_color'] ?? '#1f2937'); ?>;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 2rem;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
        ">
            <?php if (!empty($settings['show_form_header'])) : ?>
                <div class="form-header" style="text-align: center; margin-bottom: 2rem;">
                    <?php if (!empty($settings['logo_url'])) : ?>
                        <img src="<?php echo esc_url($settings['logo_url']); ?>" alt="Logo" style="max-height: 60px; margin-bottom: 1rem;">
                    <?php endif; ?>
                    <h1 style="color: <?php echo esc_attr($settings['primary_color'] ?? '#3b82f6'); ?>; margin: 0;">
                        <?php echo esc_html($settings['form_title'] ?? 'Book a Service'); ?>
                    </h1>
                    <?php if (!empty($settings['form_description'])) : ?>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.8;">
                            <?php echo esc_html($settings['form_description']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-preview-content">
                <div style="background: rgba(0,0,0,0.05); padding: 1.5rem; border-radius: 6px; text-align: center;">
                    <p style="margin: 0; font-style: italic;">📋 Form content will appear here</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; opacity: 0.7;">
                        This is a preview of your booking form layout and styling
                    </p>
                </div>
            </div>
            
            <?php if (!empty($settings['show_form_footer'])) : ?>
                <div class="form-footer" style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(0,0,0,0.1);">
                    <p style="margin: 0; font-size: 0.875rem; opacity: 0.6;">
                        <?php echo esc_html($settings['custom_footer_text'] ?? 'Powered by MoBooking'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Public booking form shortcode
     */
    public function public_booking_form_shortcode($atts) {
        global $mobooking_form_user;
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => $mobooking_form_user ? $mobooking_form_user->ID : 0,
        ), $atts);
        
        if (!$atts['user_id']) {
            return '<p>' . __('Invalid booking form.', 'mobooking') . '</p>';
        }
        
        // Get user's booking form settings
        $settings = $this->get_settings($atts['user_id']);
        
        if (!$settings->is_active) {
            return '<p>' . __('This booking form is currently unavailable.', 'mobooking') . '</p>';
        }
        
        // Generate the booking form with custom styling
        return $this->render_public_booking_form($atts['user_id'], $settings);
    }
    
    /**
     * Render public booking form
     */
    private function render_public_booking_form($user_id, $settings) {
        // Use the existing booking form shortcode but with custom styling
        ob_start();
        
        // Add custom CSS
        if (!empty($settings->custom_css)) {
            echo '<style>' . wp_strip_all_tags($settings->custom_css) . '</style>';
        }
        
        // Add dynamic CSS based on settings
        ?>
        <style>
        .mobooking-public-form {
            --primary-color: <?php echo esc_attr($settings->primary_color); ?>;
            --secondary-color: <?php echo esc_attr($settings->secondary_color); ?>;
            --background-color: <?php echo esc_attr($settings->background_color); ?>;
            --text-color: <?php echo esc_attr($settings->text_color); ?>;
        }
        .mobooking-public-form .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .mobooking-public-form .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        <?php if ($settings->form_width === 'narrow') : ?>
        .mobooking-booking-form-container {
            max-width: 600px;
        }
        <?php elseif ($settings->form_width === 'wide') : ?>
        .mobooking-booking-form-container {
            max-width: 1000px;
        }
        <?php endif; ?>
        </style>
        
        <div class="mobooking-public-form" style="background-color: <?php echo esc_attr($settings->background_color); ?>; color: <?php echo esc_attr($settings->text_color); ?>;">
            <?php if ($settings->show_form_header) : ?>
                <div class="form-header" style="text-align: center; padding: 2rem 1rem; margin-bottom: 1rem;">
                    <?php if (!empty($settings->logo_url)) : ?>
                        <img src="<?php echo esc_url($settings->logo_url); ?>" alt="Logo" style="max-height: 80px; margin-bottom: 1rem;">
                    <?php endif; ?>
                    <h1 style="color: <?php echo esc_attr($settings->primary_color); ?>; margin: 0 0 0.5rem 0;">
                        <?php echo esc_html($settings->form_title); ?>
                    </h1>
                    <?php if (!empty($settings->form_description)) : ?>
                        <p style="margin: 0; font-size: 1.125rem; opacity: 0.8;">
                            <?php echo esc_html($settings->form_description); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Render the actual booking form
            $bookings_manager = new \MoBooking\Bookings\Manager();
            echo $bookings_manager->booking_form_shortcode(array('user_id' => $user_id));
            ?>
            
            <?php if ($settings->show_form_footer && !empty($settings->custom_footer_text)) : ?>
                <div class="form-footer" style="text-align: center; padding: 2rem 1rem; margin-top: 2rem; border-top: 1px solid rgba(0,0,0,0.1);">
                    <?php echo wp_kses_post($settings->custom_footer_text); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
        // Add custom JavaScript
        if (!empty($settings->custom_js)) {
            echo '<script>' . wp_strip_all_tags($settings->custom_js) . '</script>';
        }
        
        // Add analytics code
        if (!empty($settings->analytics_code)) {
            echo wp_strip_all_tags($settings->analytics_code);
        }
        
        return ob_get_clean();
    }
}