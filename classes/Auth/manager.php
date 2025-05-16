<?php
namespace MoBooking\Auth;

/**
 * Authentication Manager
 */
class Manager {
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action('init', array($this, 'register_custom_role'));
        add_action('wp_ajax_nopriv_mobooking_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_mobooking_register', array($this, 'handle_registration'));
        add_action('wp_ajax_mobooking_logout', array($this, 'handle_logout'));
        
        // Add shortcodes for forms
        add_shortcode('mobooking_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('mobooking_registration_form', array($this, 'registration_form_shortcode'));
    }
    
    /**
     * Register custom role
     */
    public function register_custom_role() {
        add_role(
            'mobooking_business_owner',
            __('MoBooking Business Owner', 'mobooking'),
            array(
                'read' => true,
                'upload_files' => true,
                'publish_posts' => false,
                'edit_posts' => false,
            )
        );
    }
    
    /**
     * Login form shortcode
     */
    public function login_form_shortcode() {
        // If user is already logged in, show a welcome message
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            ob_start();
            ?>
            <div class="mobooking-login-welcome">
                <p><?php printf(__('Welcome back, %s!', 'mobooking'), $current_user->display_name); ?></p>
                <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="button"><?php _e('Go to Dashboard', 'mobooking'); ?></a>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Display login form
        ob_start();
        ?>
        <div class="mobooking-login-form">
            <form id="mobooking-login" method="post">
                <div class="form-group">
                    <label for="username"><?php _e('Username or Email', 'mobooking'); ?></label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php _e('Password', 'mobooking'); ?></label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        <?php _e('Remember Me', 'mobooking'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php _e('Log In', 'mobooking'); ?></button>
                </div>
                
                <div class="mobooking-message"></div>
                
                <?php wp_nonce_field('mobooking-login-nonce', 'mobooking_login_nonce'); ?>
                <input type="hidden" name="action" value="mobooking_login">
            </form>
            
            <div class="mobooking-login-links">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php _e('Forgot Password?', 'mobooking'); ?></a>
                <a href="<?php echo esc_url(home_url('/register/')); ?>"><?php _e('Create an Account', 'mobooking'); ?></a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Registration form shortcode
     */
    public function registration_form_shortcode() {
        // If user is already logged in, show a welcome message
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            ob_start();
            ?>
            <div class="mobooking-register-welcome">
                <p><?php printf(__('You are already logged in as %s.', 'mobooking'), $current_user->display_name); ?></p>
                <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="button"><?php _e('Go to Dashboard', 'mobooking'); ?></a>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Display registration form
        ob_start();
        ?>
        <div class="mobooking-register-form">
            <form id="mobooking-register" method="post">
                <div class="form-group">
                    <label for="first_name"><?php _e('First Name', 'mobooking'); ?></label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name"><?php _e('Last Name', 'mobooking'); ?></label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><?php _e('Email', 'mobooking'); ?></label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="company_name"><?php _e('Company Name', 'mobooking'); ?></label>
                    <input type="text" name="company_name" id="company_name" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php _e('Password', 'mobooking'); ?></label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm"><?php _e('Confirm Password', 'mobooking'); ?></label>
                    <input type="password" name="password_confirm" id="password_confirm" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="terms" value="1" required>
                        <?php _e('I agree to the Terms and Conditions', 'mobooking'); ?>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php _e('Create Account', 'mobooking'); ?></button>
                </div>
                
                <div class="mobooking-message"></div>
                
                <?php wp_nonce_field('mobooking-register-nonce', 'mobooking_register_nonce'); ?>
                <input type="hidden" name="action" value="mobooking_register">
            </form>
            
            <div class="mobooking-register-links">
                <a href="<?php echo esc_url(home_url('/login/')); ?>"><?php _e('Already have an account? Log In', 'mobooking'); ?></a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle login AJAX request
     */
    public function handle_login() {
        // Check nonce
        if (!isset($_POST['mobooking_login_nonce']) || !wp_verify_nonce($_POST['mobooking_login_nonce'], 'mobooking-login-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Check username and password
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($username) || empty($password)) {
            wp_send_json_error(__('Username and password are required.', 'mobooking'));
        }
        
        // Try to log in
        $credentials = array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        );
        
        $user = wp_signon($credentials, false);
        
        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        }
        
        // Check if user has the required role
        if (!in_array('mobooking_business_owner', $user->roles) && !in_array('administrator', $user->roles)) {
            wp_logout();
            wp_send_json_error(__('You do not have permission to access the dashboard.', 'mobooking'));
        }
        
        wp_send_json_success(array(
            'redirect' => home_url('/dashboard/'),
            'message' => __('Login successful. Redirecting...', 'mobooking')
        ));
    }
    
    /**
     * Handle registration AJAX request
     */
    public function handle_registration() {
        // Check nonce
        if (!isset($_POST['mobooking_register_nonce']) || !wp_verify_nonce($_POST['mobooking_register_nonce'], 'mobooking-register-nonce')) {
            wp_send_json_error(__('Security verification failed.', 'mobooking'));
        }
        
        // Validate form data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $company_name = sanitize_text_field($_POST['company_name']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $terms = isset($_POST['terms']) ? true : false;
        
        // Check required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($company_name) || empty($password)) {
            wp_send_json_error(__('All fields are required.', 'mobooking'));
        }
        
        // Validate email
        if (!is_email($email)) {
            wp_send_json_error(__('Invalid email address.', 'mobooking'));
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(__('Email already in use. Please choose another one.', 'mobooking'));
        }
        
        // Check passwords match
        if ($password !== $password_confirm) {
            wp_send_json_error(__('Passwords do not match.', 'mobooking'));
        }
        
        // Check password strength
        if (strlen($password) < 8) {
            wp_send_json_error(__('Password must be at least 8 characters long.', 'mobooking'));
        }
        
        // Check terms
        if (!$terms) {
            wp_send_json_error(__('You must agree to the Terms and Conditions.', 'mobooking'));
        }
        
        // Create user
        $username = $this->generate_username($first_name, $last_name);
        
        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'mobooking_business_owner'
        ));
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Add additional user meta
        update_user_meta($user_id, 'mobooking_company_name', $company_name);
        
        // Create initial settings
        $settings_manager = new \MoBooking\Database\SettingsManager();
        $settings_manager->create_default_settings($user_id, $company_name);
        
        // Log the user in
        wp_set_auth_cookie($user_id, true);
        
        // Send success response
        wp_send_json_success(array(
            'redirect' => home_url('/dashboard/'),
            'message' => __('Registration successful. Redirecting to dashboard...', 'mobooking')
        ));
    }
    
    /**
     * Generate a unique username
     */
    private function generate_username($first_name, $last_name) {
        $username = strtolower($first_name . '.' . $last_name);
        $username = preg_replace('/[^a-z0-9\.-]/', '', $username);
        
        // Check if username exists, if so, add a number
        $base_username = $username;
        $i = 1;
        
        while (username_exists($username)) {
            $username = $base_username . $i;
            $i++;
        }
        
        return $username;
    }
}