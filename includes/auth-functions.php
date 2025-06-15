<?php
/**
 * Authentication and role management functions.
 */

function mobooking_force_role_registration() {
    // Remove existing role first (in case it exists with issues)
    remove_role('mobooking_business_owner');

    // Add the role with proper capabilities
    $result = add_role(
        'mobooking_business_owner',
        __('MoBooking Business Owner', 'mobooking'),
        array(
            'read' => true,
            'upload_files' => true,
            'publish_posts' => false,
            'edit_posts' => false,
            'delete_posts' => false,
            'edit_others_posts' => false,
            'publish_pages' => false,
            'edit_pages' => false,
            'edit_others_pages' => false,
            'delete_pages' => false,
            'delete_others_pages' => false,
            'read_private_pages' => false,
            'read_private_posts' => false,
            'edit_published_posts' => false,
            'edit_published_pages' => false,
            'edit_private_posts' => false,
            'edit_private_pages' => false,
            'delete_private_posts' => false,
            'delete_private_pages' => false,
            'delete_published_posts' => false,
            'delete_published_pages' => false,
            'delete_others_posts' => false,
            'delete_others_pages' => false,
            'manage_categories' => false,
            'manage_links' => false,
            'moderate_comments' => false,
            'manage_options' => false,
            'import' => false,
            'unfiltered_html' => false,
            'edit_themes' => false,
            'install_plugins' => false,
            'update_core' => false,
            'list_users' => false,
            'remove_users' => false,
            'add_users' => false,
            'create_users' => false,
            'edit_users' => false,
            'delete_users' => false,
            'promote_users' => false,
        )
    );

    if ($result !== null) {
        error_log('MoBooking: Business Owner role successfully created/updated via force registration');
        return true;
    } else {
        error_log('MoBooking: Failed to create Business Owner role via force registration');
        return false;
    }
}

add_action('after_setup_theme', function() {
    if (!is_admin()) {
        return;
    }

    $last_check = get_option('mobooking_role_check_daily', 0); // Changed option name slightly for clarity
    if (time() - $last_check < DAY_IN_SECONDS) {
        return;
    }

    $role = get_role('mobooking_business_owner');
    if (!$role) {
        mobooking_force_role_registration();
    }

    update_option('mobooking_role_check_daily', time());
}, 15);

/**
 * Admin Debug Utilities for MoBooking Role
 */

// 1. Dashboard Widget for Role Status
function mobooking_debug_role_status() {
    if (!current_user_can('administrator')) {
        echo '<p>' . esc_html__('You do not have permission to view this information.', 'mobooking') . '</p>';
        return;
    }

    echo '<h4>' . esc_html__('MoBooking Business Owner Role Status', 'mobooking') . '</h4>';
    $role_name = 'mobooking_business_owner';
    $role = get_role($role_name);

    if ($role) {
        echo '<p style="color: green;">' . sprintf(esc_html__('Role "%s" exists.', 'mobooking'), $role_name) . '</p>';
        echo '<h5>' . esc_html__('Capabilities:', 'mobooking') . '</h5>';
        echo '<ul>';
        foreach ($role->capabilities as $cap => $value) {
            if ($value) {
                echo '<li>' . esc_html($cap) . '</li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<p style="color: red;">' . sprintf(esc_html__('Role "%s" does NOT exist.', 'mobooking'), $role_name) . '</p>';
        echo '<button id="mobooking-recreate-role-btn" class="button button-primary">' . esc_html__('Recreate Role', 'mobooking') . '</button>';
        echo '<div id="mobooking-recreate-role-message" style="margin-top:10px;"></div>';
    }

    echo '<hr>';
    echo '<h4>' . esc_html__('Auth Manager Class Status', 'mobooking') . '</h4>';
    if (class_exists('\MoBooking\Auth\Manager')) {
        echo '<p style="color: green;">' . esc_html__('\MoBooking\Auth\Manager class exists.', 'mobooking') . '</p>';
    } else {
        echo '<p style="color: red;">' . esc_html__('\MoBooking\Auth\Manager class does NOT exist.', 'mobooking') . '</p>';
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#mobooking-recreate-role-btn').on('click', function() {
                var $button = $(this);
                var $messageDiv = $('#mobooking-recreate-role-message');
                $messageDiv.html('<?php echo esc_js(__('Processing...', 'mobooking')); ?>');
                $button.prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mobooking_recreate_role',
                        nonce: '<?php echo esc_js(wp_create_nonce('mobooking_admin_actions')); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $messageDiv.html('<p style="color: green;">' + response.data + '</p>');
                        } else {
                            $messageDiv.html('<p style="color: red;">' + response.data + '</p>');
                        }
                        $button.prop('disabled', false);
                    },
                    error: function() {
                        $messageDiv.html('<p style="color: red;"><?php echo esc_js(__('An error occurred.', 'mobooking')); ?></p>');
                        $button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('wp_dashboard_setup', function() {
    if (current_user_can('administrator')) {
        wp_add_dashboard_widget(
            'mobooking_role_debug_widget', // Changed ID to be more specific
            __('MoBooking Role Status', 'mobooking'),
            'mobooking_debug_role_status'
        );
    }
});

// 2. AJAX Action for Manual Role Recreation
add_action('wp_ajax_mobooking_recreate_role', function() {
    // Check permissions
    if (!current_user_can('administrator')) {
        wp_send_json_error(__('Insufficient permissions', 'mobooking'), 403); // Added HTTP status code
    }

    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'mobooking_admin_actions')) {
        wp_send_json_error(__('Security check failed', 'mobooking'), 403); // Added HTTP status code
    }

    $result = mobooking_force_role_registration();

    if ($result) {
        wp_send_json_success(__('Role recreated successfully. Please refresh the page to see updated status.', 'mobooking'));
    } else {
        wp_send_json_error(__('Failed to recreate role. Check error logs for details.', 'mobooking'));
    }
});

?>
