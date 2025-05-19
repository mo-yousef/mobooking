<?php
ob_end_flush(); 
?><?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Get current view: list, add, edit
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Initialize the service manager
$services_manager = new \MoBooking\Services\ServiceManager();

// Process form submissions first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect_url = '';
    
    // Handle service actions
    if (isset($_POST['service_action'])) {
        $nonce = isset($_POST['service_nonce']) ? $_POST['service_nonce'] : '';
        
        if (wp_verify_nonce($nonce, 'mobooking-service-nonce')) {
            // Save service
            if ($_POST['service_action'] === 'save') {
                // Build service data from POST
                $service_data = array(
                    'user_id' => $user_id,
                    'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
                    'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
                    'price' => isset($_POST['price']) ? floatval($_POST['price']) : 0,
                    'duration' => isset($_POST['duration']) ? intval($_POST['duration']) : 60,
                    'icon' => isset($_POST['icon']) ? sanitize_text_field($_POST['icon']) : '',
                    'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '',
                    'image_url' => isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '',
                    'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'active'
                );
                
                // Add ID if editing
                if ($service_id > 0) {
                    $service_data['id'] = $service_id;
                }
                
                // Validate required fields
                $errors = array();
                if (empty($service_data['name'])) {
                    $errors[] = __('Service name is required', 'mobooking');
                }
                if ($service_data['price'] <= 0) {
                    $errors[] = __('Price must be greater than zero', 'mobooking');
                }
                if ($service_data['duration'] < 15) {
                    $errors[] = __('Duration must be at least 15 minutes', 'mobooking');
                }
                
                if (empty($errors)) {
                    // Save service
                    $new_service_id = $services_manager->save_service($service_data);
                    
                    if ($new_service_id) {
                        // Set success message
                        $message = $service_id > 0 ? 
                            __('Service updated successfully', 'mobooking') : 
                            __('Service created successfully', 'mobooking');
                        
                        // Store message in transient
                        set_transient('mobooking_service_message', array(
                            'type' => 'success',
                            'text' => $message
                        ), 30);
                        
                        // Redirect to service list or edit page
                        $redirect_url = add_query_arg('view', 'list', remove_query_arg(array('service_id')));
                    } else {
                        // Set error message
                        $errors[] = __('Failed to save service. Please try again.', 'mobooking');
                    }
                }
                
                // Store errors if any
                if (!empty($errors)) {
                    set_transient('mobooking_service_errors', $errors, 30);
                    $redirect_url = add_query_arg(array('view' => $service_id > 0 ? 'edit' : 'add', 'service_id' => $service_id), remove_query_arg(array()));
                }
            }
            
            // Delete service
            else if ($_POST['service_action'] === 'delete' && $service_id > 0) {
                // Delete the service
                $result = $services_manager->delete_service($service_id, $user_id);
                
                if ($result) {
                    // Set success message
                    set_transient('mobooking_service_message', array(
                        'type' => 'success',
                        'text' => __('Service deleted successfully', 'mobooking')
                    ), 30);
                    
                    // Redirect to service list
                    $redirect_url = add_query_arg('view', 'list', remove_query_arg(array('service_id')));
                } else {
                    // Set error message
                    set_transient('mobooking_service_errors', array(__('Failed to delete service', 'mobooking')), 30);
                    $redirect_url = add_query_arg(array('view' => 'edit', 'service_id' => $service_id), remove_query_arg(array()));
                }
            }
        }
    }
    
    // Handle option actions
    elseif (isset($_POST['option_action'])) {
        $nonce = isset($_POST['option_nonce']) ? $_POST['option_nonce'] : '';
        
        if (wp_verify_nonce($nonce, 'mobooking-option-nonce')) {
            $option_service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            $option_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
            
            // Only process if we have a valid service ID
            if ($option_service_id > 0) {
                // Save option
                if ($_POST['option_action'] === 'save') {
                    // Build option data from POST
                    $option_data = array(
                        'service_id' => $option_service_id,
                        'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
                        'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'checkbox',
                        'is_required' => isset($_POST['is_required']) ? intval($_POST['is_required']) : 0,
                        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
                        'default_value' => isset($_POST['default_value']) ? sanitize_text_field($_POST['default_value']) : '',
                        'placeholder' => isset($_POST['placeholder']) ? sanitize_text_field($_POST['placeholder']) : '',
                        'min_value' => isset($_POST['min_value']) && $_POST['min_value'] !== '' ? floatval($_POST['min_value']) : null,
                        'max_value' => isset($_POST['max_value']) && $_POST['max_value'] !== '' ? floatval($_POST['max_value']) : null,
                        'price_impact' => isset($_POST['price_impact']) ? floatval($_POST['price_impact']) : 0,
                        'price_type' => isset($_POST['price_type']) ? sanitize_text_field($_POST['price_type']) : 'fixed',
                        'step' => isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '',
                        'unit' => isset($_POST['unit']) ? sanitize_text_field($_POST['unit']) : '',
                        'min_length' => isset($_POST['min_length']) && $_POST['min_length'] !== '' ? intval($_POST['min_length']) : null,
                        'max_length' => isset($_POST['max_length']) && $_POST['max_length'] !== '' ? intval($_POST['max_length']) : null,
                        'rows' => isset($_POST['rows']) && $_POST['rows'] !== '' ? intval($_POST['rows']) : null
                    );
                    
                    // Add ID if editing
                    if ($option_id > 0) {
                        $option_data['id'] = $option_id;
                    }
                    
                    // Handle choices for select/radio options
                    if (($option_data['type'] === 'select' || $option_data['type'] === 'radio') && isset($_POST['choice_value']) && is_array($_POST['choice_value'])) {
                        $choices = array();
                        $choice_values = $_POST['choice_value'];
                        $choice_labels = isset($_POST['choice_label']) ? $_POST['choice_label'] : array();
                        $choice_prices = isset($_POST['choice_price']) ? $_POST['choice_price'] : array();
                        
                        for ($i = 0; $i < count($choice_values); $i++) {
                            $value = trim($choice_values[$i]);
                            if (empty($value)) continue; // Skip empty values
                            
                            $label = isset($choice_labels[$i]) ? trim($choice_labels[$i]) : $value;
                            $price = isset($choice_prices[$i]) ? floatval($choice_prices[$i]) : 0;
                            
                            if ($price > 0) {
                                $choices[] = "$value|$label:$price";
                            } else {
                                $choices[] = "$value|$label";
                            }
                        }
                        
                        $option_data['options'] = implode("\n", $choices);
                    }
                    
                    // Validate required fields
                    $errors = array();
                    if (empty($option_data['name'])) {
                        $errors[] = __('Option name is required', 'mobooking');
                    }
                    if (empty($option_data['type'])) {
                        $errors[] = __('Option type is required', 'mobooking');
                    }
                    
                    if (empty($errors)) {
                        // Save option
                        $new_option_id = $services_manager->save_option($option_data);
                        
                        if ($new_option_id) {
                            // Set success message
                            $message = $option_id > 0 ? 
                                __('Option updated successfully', 'mobooking') : 
                                __('Option created successfully', 'mobooking');
                            
                            // Store message in transient
                            set_transient('mobooking_service_message', array(
                                'type' => 'success',
                                'text' => $message
                            ), 30);
                            
                            // Redirect to edit service page
                            $redirect_url = add_query_arg(array('view' => 'edit', 'service_id' => $option_service_id), remove_query_arg('option_id'));
                        } else {
                            // Set error message
                            $errors[] = __('Failed to save option. Please try again.', 'mobooking');
                        }
                    }
                    
                    // Store errors if any
                    if (!empty($errors)) {
                        set_transient('mobooking_service_errors', $errors, 30);
                        
                        // Redirect back to add/edit option page
                        $view = $option_id > 0 ? 'edit_option' : 'add_option';
                        $redirect_params = array('view' => $view, 'service_id' => $option_service_id);
                        if ($option_id > 0) {
                            $redirect_params['option_id'] = $option_id;
                        }
                        $redirect_url = add_query_arg($redirect_params);
                    }
                }
                
                // Delete option
                elseif ($_POST['option_action'] === 'delete' && $option_id > 0) {
                    // Delete the option
                    $result = $services_manager->delete_service_option($option_id);
                    
                    if ($result) {
                        // Set success message
                        set_transient('mobooking_service_message', array(
                            'type' => 'success',
                            'text' => __('Option deleted successfully', 'mobooking')
                        ), 30);
                    } else {
                        // Set error message
                        set_transient('mobooking_service_errors', array(__('Failed to delete option', 'mobooking')), 30);
                    }
                    
                    // Redirect to edit service page
                    $redirect_url = add_query_arg(array('view' => 'edit', 'service_id' => $option_service_id), remove_query_arg('option_id'));
                }
            }
        }
    }
    
    // If we have a redirect URL, perform the redirect
    // This is safe to do now because we've started output buffering
    if (!empty($redirect_url)) {
        // Clear any output that might have been captured
        ob_clean();
        
        // Redirect
        wp_safe_redirect($redirect_url);
        exit;
    }
}

// Get services
$services = $services_manager->get_user_services($user_id);

// Set categories for filtering
$categories = array(
    'residential' => __('Residential', 'mobooking'),
    'commercial' => __('Commercial', 'mobooking'),
    'special' => __('Special', 'mobooking')
);

// Option types for add-ons
$option_types = array(
    'checkbox' => __('Checkbox', 'mobooking'),
    'number' => __('Number Input', 'mobooking'),
    'select' => __('Dropdown Select', 'mobooking'),
    'text' => __('Text Input', 'mobooking'),
    'textarea' => __('Text Area', 'mobooking'),
    'radio' => __('Radio Buttons', 'mobooking'),
    'quantity' => __('Quantity Selector', 'mobooking')
);

// Price impact types
$price_types = array(
    'fixed' => __('Fixed Amount', 'mobooking'),
    'percentage' => __('Percentage of Base Price', 'mobooking'),
    'multiply' => __('Multiply by Value', 'mobooking'),
    'none' => __('No Price Impact', 'mobooking')
);

// Get total count
$total_services = count($services);

// Enqueue scripts and styles
wp_enqueue_style('mobooking-dashboard-style', MOBOOKING_URL . '/assets/css/dashboard.css', array(), MOBOOKING_VERSION);
wp_enqueue_style('mobooking-service-options-style', MOBOOKING_URL . '/assets/css/service-options.css', array(), MOBOOKING_VERSION);
wp_enqueue_media(); // Enable WordPress Media Uploader

// Display messages
$messages = get_transient('mobooking_service_message');
$errors = get_transient('mobooking_service_errors');

if ($messages) {
    delete_transient('mobooking_service_message');
}

if ($errors) {
    delete_transient('mobooking_service_errors');
}
?>

<div class="dashboard-section services-section">
    <?php if ($current_view === 'list'): ?>
        <!-- Services List View -->
        <div class="section-header">
            <h2 class="section-title"><?php _e('Your Services', 'mobooking'); ?></h2>
            
            <div class="top-actions">
                <a href="<?php echo esc_url(add_query_arg('view', 'add')); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus"></span> <?php _e('Add New Service', 'mobooking'); ?>
                </a>
                
                <div class="filter-controls">
                    <label for="service-filter"><?php _e('Filter by:', 'mobooking'); ?></label>
                    <select id="service-filter">
                        <option value=""><?php _e('All Services', 'mobooking'); ?></option>
                        <?php foreach ($categories as $slug => $name) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <?php if ($messages): ?>
            <div class="notification notification-<?php echo esc_attr($messages['type']); ?>">
                <?php echo esc_html($messages['text']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($services)) : ?>
            <div class="no-items">
                <span class="dashicons dashicons-admin-tools"></span>
                <p><?php _e('You haven\'t created any services yet.', 'mobooking'); ?></p>
                <p><?php _e('Add your first service to start receiving bookings.', 'mobooking'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('view', 'add')); ?>" class="button button-primary"><?php _e('Add Your First Service', 'mobooking'); ?></a>
            </div>
        <?php else : ?>
            <div class="services-grid">
                <?php foreach ($services as $service) : 
                    $has_options = $services_manager->has_service_options($service->id);
                ?>
                    <div class="service-card" data-id="<?php echo esc_attr($service->id); ?>" data-category="<?php echo esc_attr($service->category); ?>">
                        <div class="service-header">
                            <?php if (!empty($service->image_url)) : ?>
                                <div class="service-image" style="background-image: url('<?php echo esc_url($service->image_url); ?>')"></div>
                            <?php elseif (!empty($service->icon)) : ?>
                                <div class="service-icon">
                                    <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="service-title">
                                <h3><?php echo esc_html($service->name); ?></h3>
                                <?php if (!empty($service->category)) : ?>
                                    <span class="category-badge category-<?php echo esc_attr($service->category); ?>">
                                        <?php echo esc_html($categories[$service->category] ?? $service->category); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-status-price">
                                <div class="service-price">
                                    <?php echo wc_price($service->price); ?>
                                </div>
                                <div class="service-status service-status-<?php echo !empty($service->status) && $service->status == 'active' ? 'active' : 'inactive'; ?>">
                                    <?php echo !empty($service->status) && $service->status == 'active' ? __('Active', 'mobooking') : __('Inactive', 'mobooking'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-body">
                            <div class="service-meta">
                                <span class="service-duration">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo sprintf(_n('%d minute', '%d minutes', $service->duration, 'mobooking'), $service->duration); ?>
                                </span>
                                
                                <?php if ($has_options) : ?>
                                    <span class="service-options-badge">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php _e('Customizable', 'mobooking'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-description">
                                <?php echo wpautop(esc_html($service->description)); ?>
                            </div>
                        </div>
                        
                        <div class="service-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service->id))); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'mobooking'); ?>
                            </a>
                            
                            <form method="post" class="delete-service-form" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this service? This action cannot be undone.', 'mobooking'); ?>');">
                                <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
                                <input type="hidden" name="service_action" value="delete">
                                <button type="submit" class="button button-danger">
                                    <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'mobooking'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php elseif ($current_view === 'add' || $current_view === 'edit'): ?>
        <?php
        // Get service data if editing
        $service = null;
        if ($current_view === 'edit' && $service_id > 0) {
            $service = $services_manager->get_service($service_id, $user_id);
            
            // Redirect to list if service not found
            if (!$service) {
                wp_redirect(add_query_arg('view', 'list', remove_query_arg(array('service_id'))));
                exit;
            }
        }
        
        // Set page title based on view
        $page_title = $current_view === 'add' ? __('Add New Service', 'mobooking') : __('Edit Service', 'mobooking');
        $form_action = $current_view === 'add' ? add_query_arg('view', 'add') : add_query_arg(array('view' => 'edit', 'service_id' => $service_id));
        ?>
        
        <!-- Add/Edit Service Form -->
        <div class="service-form-container">
            <div class="section-header">
                <h2 class="section-title"><?php echo esc_html($page_title); ?></h2>
                <a href="<?php echo esc_url(add_query_arg('view', 'list', remove_query_arg(array('service_id')))); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Back to Services', 'mobooking'); ?>
                </a>
            </div>
            
            <?php if ($messages): ?>
                <div class="notification notification-<?php echo esc_attr($messages['type']); ?>">
                    <?php echo esc_html($messages['text']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="notification notification-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="service-form" method="post" action="<?php echo esc_url($form_action); ?>" class="card">
                <input type="hidden" name="service_action" value="save">
                <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
                
                <div class="form-tabs">
                    <div class="tabs-header">
                        <button type="button" class="tab-button active" data-tab="basic-info"><?php _e('Basic Info', 'mobooking'); ?></button>
                        <button type="button" class="tab-button" data-tab="presentation"><?php _e('Presentation', 'mobooking'); ?></button>
                        <?php if ($current_view === 'edit'): ?>
                            <button type="button" class="tab-button" data-tab="options"><?php _e('Service Options', 'mobooking'); ?></button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tabs-content">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane active" id="basic-info">
                            <div class="form-group">
                                <label for="service-name"><?php _e('Service Name', 'mobooking'); ?></label>
                                <input type="text" id="service-name" name="name" value="<?php echo $service ? esc_attr($service->name) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                                <textarea id="service-description" name="description" rows="4"><?php echo $service ? esc_textarea($service->description) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="service-price"><?php _e('Base Price', 'mobooking'); ?></label>
                                    <input type="number" id="service-price" name="price" min="0" step="0.01" value="<?php echo $service ? esc_attr($service->price) : '0.00'; ?>" required>
                                </div>
                                
                                <div class="form-group half">
                                    <label for="service-duration"><?php _e('Duration (minutes)', 'mobooking'); ?></label>
                                    <input type="number" id="service-duration" name="duration" min="15" step="15" value="<?php echo $service ? esc_attr($service->duration) : '60'; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="service-category"><?php _e('Category', 'mobooking'); ?></label>
                                    <select id="service-category" name="category">
                                        <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                        <?php foreach ($categories as $slug => $name) : ?>
                                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($service && $service->category === $slug); ?>>
                                                <?php echo esc_html($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group half">
                                    <label for="service-status"><?php _e('Status', 'mobooking'); ?></label>
                                    <select id="service-status" name="status">
                                        <option value="active" <?php selected($service && $service->status === 'active'); ?>><?php _e('Active', 'mobooking'); ?></option>
                                        <option value="inactive" <?php selected($service && $service->status === 'inactive'); ?>><?php _e('Inactive', 'mobooking'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Presentation Tab -->
                        <div class="tab-pane" id="presentation">
                            <div class="form-group">
                                <label for="service-image"><?php _e('Image URL', 'mobooking'); ?></label>
                                <div class="image-upload-container">
                                    <input type="text" id="service-image" name="image_url" value="<?php echo $service ? esc_attr($service->image_url) : ''; ?>" placeholder="https://...">
                                    <button type="button" class="button select-image"><?php _e('Select', 'mobooking'); ?></button>
                                </div>
                                <div class="image-preview">
                                    <?php if ($service && !empty($service->image_url)): ?>
                                        <img src="<?php echo esc_url($service->image_url); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-icon"><?php _e('Icon', 'mobooking'); ?></label>
                                <select id="service-icon" name="icon">
                                    <option value=""><?php _e('None', 'mobooking'); ?></option>
                                    <option value="dashicons-admin-home" <?php selected($service && $service->icon === 'dashicons-admin-home'); ?>><?php _e('Home', 'mobooking'); ?></option>
                                    <option value="dashicons-admin-tools" <?php selected($service && $service->icon === 'dashicons-admin-tools'); ?>><?php _e('Tools', 'mobooking'); ?></option>
                                    <option value="dashicons-bucket" <?php selected($service && $service->icon === 'dashicons-bucket'); ?>><?php _e('Bucket', 'mobooking'); ?></option>
                                    <option value="dashicons-hammer" <?php selected($service && $service->icon === 'dashicons-hammer'); ?>><?php _e('Hammer', 'mobooking'); ?></option>
                                    <option value="dashicons-art" <?php selected($service && $service->icon === 'dashicons-art'); ?>><?php _e('Paintbrush', 'mobooking'); ?></option>
                                    <option value="dashicons-building" <?php selected($service && $service->icon === 'dashicons-building'); ?>><?php _e('Building', 'mobooking'); ?></option>
                                    <option value="dashicons-businesswoman" <?php selected($service && $service->icon === 'dashicons-businesswoman'); ?>><?php _e('Person', 'mobooking'); ?></option>
                                    <option value="dashicons-car" <?php selected($service && $service->icon === 'dashicons-car'); ?>><?php _e('Car', 'mobooking'); ?></option>
                                    <option value="dashicons-pets" <?php selected($service && $service->icon === 'dashicons-pets'); ?>><?php _e('Pets', 'mobooking'); ?></option>
                                    <option value="dashicons-palmtree" <?php selected($service && $service->icon === 'dashicons-palmtree'); ?>><?php _e('Plant', 'mobooking'); ?></option>
                                </select>
                                <div class="icon-preview-container">
                                    <div class="icon-preview">
                                        <?php if ($service && !empty($service->icon)): ?>
                                            <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($current_view === 'edit'): ?>
                            <!-- Service Options Tab -->
                            <div class="tab-pane" id="options">
                                <div class="options-info-card">
                                    <h3><?php _e('Service Options', 'mobooking'); ?></h3>
                                    <p><?php _e('Service options allow your customers to customize their booking. Options can add features, adjust pricing, or provide special instructions.', 'mobooking'); ?></p>
                                    
                                    <div class="options-toolbar">
                                        <a href="<?php echo esc_url(add_query_arg(array('view' => 'add_option', 'service_id' => $service_id))); ?>" class="button button-primary">
                                            <span class="dashicons dashicons-plus"></span> <?php _e('Add New Option', 'mobooking'); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <?php 
                                // Get service options
                                $options = $services_manager->get_service_options($service_id);
                                
                                if (empty($options)): 
                                ?>
                                    <div class="no-options-message">
                                        <p><?php _e('No options have been created for this service yet.', 'mobooking'); ?></p>
                                        <p><?php _e('Options allow customers to customize their booking with add-ons, variations, or special requests.', 'mobooking'); ?></p>
                                        <a href="<?php echo esc_url(add_query_arg(array('view' => 'add_option', 'service_id' => $service_id))); ?>" class="button button-primary">
                                            <?php _e('Add Your First Option', 'mobooking'); ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="options-list">
                                        <table class="options-table">
                                            <thead>
                                                <tr>
                                                    <th><?php _e('Name', 'mobooking'); ?></th>
                                                    <th><?php _e('Type', 'mobooking'); ?></th>
                                                    <th><?php _e('Required', 'mobooking'); ?></th>
                                                    <th><?php _e('Price Impact', 'mobooking'); ?></th>
                                                    <th><?php _e('Actions', 'mobooking'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($options as $option): ?>
                                                    <tr>
                                                        <td><?php echo esc_html($option->name); ?></td>
                                                        <td><?php echo esc_html($option_types[$option->type] ?? $option->type); ?></td>
                                                        <td>
                                                            <?php if ($option->is_required): ?>
                                                                <span class="dashicons dashicons-yes-alt" style="color: var(--success-color);"></span>
                                                            <?php else: ?>
                                                                <span class="dashicons dashicons-no-alt" style="color: var(--text-light);"></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($option->price_impact > 0): ?>
                                                                <?php if ($option->price_type === 'percentage'): ?>
                                                                    +<?php echo esc_html($option->price_impact); ?>%
                                                                <?php elseif ($option->price_type === 'multiply'): ?>
                                                                    ×<?php echo esc_html($option->price_impact); ?>
                                                                <?php else: ?>
                                                                    +<?php echo wc_price($option->price_impact); ?>
                                                                <?php endif; ?>
                                                            <?php elseif ($option->price_impact < 0): ?>
                                                                <?php if ($option->price_type === 'percentage'): ?>
                                                                    <?php echo esc_html($option->price_impact); ?>%
                                                                <?php elseif ($option->price_type === 'multiply'): ?>
                                                                    ×<?php echo esc_html($option->price_impact); ?>
                                                                <?php else: ?>
                                                                    <?php echo wc_price($option->price_impact); ?>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                —
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="option-actions">
                                                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit_option', 'service_id' => $service_id, 'option_id' => $option->id))); ?>" class="button button-small">
                                                                <span class="dashicons dashicons-edit"></span>
                                                            </a>
                                                            <form method="post" class="delete-option-form" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this option?', 'mobooking'); ?>');">
                                                                <?php wp_nonce_field('mobooking-option-nonce', 'option_nonce'); ?>
                                                                <input type="hidden" name="option_action" value="delete">
                                                                <input type="hidden" name="option_id" value="<?php echo esc_attr($option->id); ?>">
                                                                <button type="submit" class="button button-small button-danger">
                                                                    <span class="dashicons dashicons-trash"></span>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo esc_url(add_query_arg('view', 'list', remove_query_arg(array('service_id')))); ?>" class="button button-secondary"><?php _e('Cancel', 'mobooking'); ?></a>
                    <button type="submit" class="button button-primary"><?php _e('Save Service', 'mobooking'); ?></button>
                </div>
            </form>
        </div>
        
    <?php elseif ($current_view === 'add_option' || $current_view === 'edit_option'): ?>
        <?php
        // Get service
        $service = null;
        if ($service_id > 0) {
            $service = $services_manager->get_service($service_id, $user_id);
            
            // Redirect to list if service not found
            if (!$service) {
                wp_redirect(add_query_arg('view', 'list', remove_query_arg(array('service_id', 'option_id'))));
                exit;
            }
        } else {
            // Redirect to list if no service ID
            wp_redirect(add_query_arg('view', 'list', remove_query_arg(array('service_id', 'option_id'))));
            exit;
        }
        
        // Get option if editing
        $option_id = isset($_GET['option_id']) ? intval($_GET['option_id']) : 0;
        $option = null;
        
        if ($current_view === 'edit_option' && $option_id > 0) {
            $option = $services_manager->get_service_option($option_id);
            
            // Redirect if option not found or doesn't belong to this service
            if (!$option || $option->parent_id != $service_id) {
                wp_redirect(add_query_arg(array('view' => 'edit', 'service_id' => $service_id), remove_query_arg('option_id')));
                exit;
            }
        }
        
        // Set page title based on view
        $page_title = $current_view === 'add_option' ? 
            sprintf(__('Add Option to %s', 'mobooking'), $service->name) : 
            sprintf(__('Edit Option for %s', 'mobooking'), $service->name);
        
        $form_action = $current_view === 'add_option' ? 
            add_query_arg(array('view' => 'add_option', 'service_id' => $service_id)) : 
            add_query_arg(array('view' => 'edit_option', 'service_id' => $service_id, 'option_id' => $option_id));
        ?>
        
        <!-- Add/Edit Option Form -->
        <div class="option-form-container">
            <div class="section-header">
                <h2 class="section-title"><?php echo esc_html($page_title); ?></h2>
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service_id), remove_query_arg('option_id'))); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Back to Service', 'mobooking'); ?>
                </a>
            </div>
            
            <?php if ($messages): ?>
                <div class="notification notification-<?php echo esc_attr($messages['type']); ?>">
                    <?php echo esc_html($messages['text']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="notification notification-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="option-form" method="post" action="<?php echo esc_url($form_action); ?>" class="card">
                <input type="hidden" name="option_action" value="save">
                <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
                <?php if ($option): ?>
                    <input type="hidden" name="option_id" value="<?php echo esc_attr($option->id); ?>">
                <?php endif; ?>
                <?php wp_nonce_field('mobooking-option-nonce', 'option_nonce'); ?>
                
                <div class="form-group">
                    <label for="option-name"><?php _e('Option Name', 'mobooking'); ?></label>
                    <input type="text" id="option-name" name="name" value="<?php echo $option ? esc_attr($option->name) : ''; ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
                        <select id="option-type" name="type" required>
                            <option value=""><?php _e('Select Type', 'mobooking'); ?></option>
                            <?php foreach ($option_types as $type => $label) : ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($option && $option->type === $type); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group half">
                        <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                        <select id="option-required" name="is_required">
                            <option value="0" <?php selected($option && !$option->is_required); ?>><?php _e('Optional', 'mobooking'); ?></option>
                            <option value="1" <?php selected($option && $option->is_required); ?>><?php _e('Required', 'mobooking'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                    <textarea id="option-description" name="description" rows="2"><?php echo $option ? esc_textarea($option->description) : ''; ?></textarea>
                </div>
                
                <!-- Dynamic fields based on option type will be inserted here via JavaScript -->
                <div id="dynamic-fields">
                    <!-- Placeholder for dynamic fields based on option type -->
                </div>
                
                <div class="form-row price-impact-section">
                    <div class="form-group half">
                        <label for="option-price-type"><?php _e('Price Impact Type', 'mobooking'); ?></label>
                        <select id="option-price-type" name="price_type">
                            <?php foreach ($price_types as $type => $label) : ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($option && $option->price_type === $type); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group half price-impact-value">
                        <label for="option-price-impact"><?php _e('Price Value', 'mobooking'); ?></label>
                        <input type="number" id="option-price-impact" name="price_impact" step="0.01" value="<?php echo $option ? esc_attr($option->price_impact) : '0'; ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service_id), remove_query_arg('option_id'))); ?>" class="button button-secondary"><?php _e('Cancel', 'mobooking'); ?></a>
                    <button type="submit" class="button button-primary"><?php _e('Save Option', 'mobooking'); ?></button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Toggle active class on buttons
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Show selected tab content
        $('.tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Service filter
    $('#service-filter').on('change', function() {
        var category = $(this).val();
        
        if (category === '') {
            $('.service-card').show();
        } else {
            $('.service-card').hide();
            $('.service-card[data-category="' + category + '"]').show();
        }
    });
    
    // Media uploader for service image
    var mediaUploader;
    
    $('.select-image').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create the media uploader
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: '<?php _e("Choose Image", "mobooking"); ?>',
            button: {
                text: '<?php _e("Select", "mobooking"); ?>'
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#service-image').val(attachment.url);
            $('.image-preview').html('<img src="' + attachment.url + '" alt="">');
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Preview icon when selected
    $('#service-icon').on('change', function() {
        var iconClass = $(this).val();
        if (iconClass) {
            $('.icon-preview').html('<span class="dashicons ' + iconClass + '"></span>');
        } else {
            $('.icon-preview').empty();
        }
    });
    
    // Option type change handler
    $('#option-type').on('change', function() {
        var optionType = $(this).val();
        generateDynamicFields(optionType);
    });
    
    // Price type change handler
    $('#option-price-type').on('change', function() {
        updatePriceFields($(this).val());
    });
    
    // Generate dynamic fields based on option type
    function generateDynamicFields(optionType) {
        const dynamicFields = $('#dynamic-fields');
        dynamicFields.empty();
        
        switch (optionType) {
            case 'checkbox':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e("Default Value", "mobooking"); ?></label>' +
                            '<select id="option-default-value" name="default_value">' +
                                '<option value="0"><?php _e("Unchecked", "mobooking"); ?></option>' +
                                '<option value="1"><?php _e("Checked", "mobooking"); ?></option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-label"><?php _e("Option Label", "mobooking"); ?></label>' +
                            '<input type="text" id="option-label" name="option_label" placeholder="<?php _e("Check this box to add...", "mobooking"); ?>">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'number':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-value"><?php _e("Minimum Value", "mobooking"); ?></label>' +
                            '<input type="number" id="option-min-value" name="min_value" value="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-value"><?php _e("Maximum Value", "mobooking"); ?></label>' +
                            '<input type="number" id="option-max-value" name="max_value">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e("Default Value", "mobooking"); ?></label>' +
                            '<input type="number" id="option-default-value" name="default_value">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-placeholder"><?php _e("Placeholder", "mobooking"); ?></label>' +
                            '<input type="text" id="option-placeholder" name="placeholder">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-step"><?php _e("Step", "mobooking"); ?></label>' +
                            '<input type="number" id="option-step" name="step" value="1" step="0.01">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-unit"><?php _e("Unit Label", "mobooking"); ?></label>' +
                            '<input type="text" id="option-unit" name="unit" placeholder="<?php _e("sq ft, hours, etc.", "mobooking"); ?>">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'select':
            case 'radio':
                dynamicFields.append(
                    '<div class="form-group">' +
                        '<label><?php _e("Choices", "mobooking"); ?></label>' +
                        '<div class="choices-container">' +
                            '<div class="choices-header">' +
                                '<div class="choice-value"><?php _e("Value", "mobooking"); ?></div>' +
                                '<div class="choice-label"><?php _e("Label", "mobooking"); ?></div>' +
                                '<div class="choice-price"><?php _e("Price Impact", "mobooking"); ?></div>' +
                                '<div class="choice-actions"></div>' +
                            '</div>' +
                            '<div class="choices-list">' +
                                '<div class="choice-row">' +
                                    '<div class="choice-value">' +
                                        '<input type="text" name="choice_value[]" placeholder="<?php _e("value", "mobooking"); ?>">' +
                                    '</div>' +
                                    '<div class="choice-label">' +
                                        '<input type="text" name="choice_label[]" placeholder="<?php _e("Display Label", "mobooking"); ?>">' +
                                    '</div>' +
                                    '<div class="choice-price">' +
                                        '<input type="number" name="choice_price[]" step="0.01" placeholder="0.00">' +
                                    '</div>' +
                                    '<div class="choice-actions">' +
                                        '<button type="button" class="button-link remove-choice">' +
                                            '<span class="dashicons dashicons-trash"></span>' +
                                        '</button>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="add-choice-container">' +
                                '<button type="button" class="button add-choice"><?php _e("Add Choice", "mobooking"); ?></button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="option-default-value"><?php _e("Default Value", "mobooking"); ?></label>' +
                        '<input type="text" id="option-default-value" name="default_value">' +
                        '<p class="field-hint"><?php _e("Enter the value (not the label) of the default choice", "mobooking"); ?></p>' +
                    '</div>'
                );
                break;
                
            case 'text':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e("Default Value", "mobooking"); ?></label>' +
                            '<input type="text" id="option-default-value" name="default_value">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-placeholder"><?php _e("Placeholder", "mobooking"); ?></label>' +
                            '<input type="text" id="option-placeholder" name="placeholder">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-length"><?php _e("Minimum Length", "mobooking"); ?></label>' +
                            '<input type="number" id="option-min-length" name="min_length" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-length"><?php _e("Maximum Length", "mobooking"); ?></label>' +
                            '<input type="number" id="option-max-length" name="max_length" min="0">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'textarea':
                dynamicFields.append(
                    '<div class="form-group">' +
                        '<label for="option-default-value"><?php _e("Default Value", "mobooking"); ?></label>' +
                        '<textarea id="option-default-value" name="default_value" rows="2"></textarea>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="option-placeholder"><?php _e("Placeholder", "mobooking"); ?></label>' +
                        '<input type="text" id="option-placeholder" name="placeholder">' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-rows"><?php _e("Rows", "mobooking"); ?></label>' +
                            '<input type="number" id="option-rows" name="rows" value="3" min="2">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-length"><?php _e("Maximum Length", "mobooking"); ?></label>' +
                            '<input type="number" id="option-max-length" name="max_length" min="0">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'quantity':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-value"><?php _e("Minimum Quantity", "mobooking"); ?></label>' +
                            '<input type="number" id="option-min-value" name="min_value" value="0" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-value"><?php _e("Maximum Quantity", "mobooking"); ?></label>' +
                            '<input type="number" id="option-max-value" name="max_value" min="0">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e("Default Quantity", "mobooking"); ?></label>' +
                            '<input type="number" id="option-default-value" name="default_value" value="0" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-step"><?php _e("Step", "mobooking"); ?></label>' +
                            '<input type="number" id="option-step" name="step" value="1" min="1">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-unit"><?php _e("Unit Label", "mobooking"); ?></label>' +
                            '<input type="text" id="option-unit" name="unit" placeholder="<?php _e("items, people, etc.", "mobooking"); ?>">' +
                        '</div>' +
                    '</div>'
                );
                break;
        }
    }
    
    // Update price fields based on selected price type
    function updatePriceFields(priceType) {
        const valueField = $('.price-impact-value');
        
        valueField.show();
        
        if (priceType === 'custom') {
            valueField.find('label').text('<?php _e("Formula", "mobooking"); ?>');
            valueField.find('input').attr('type', 'text').attr('placeholder', 'price + (value * 5)');
        } else if (priceType === 'none') {
            valueField.hide();
        } else if (priceType === 'percentage') {
            valueField.find('label').text('<?php _e("Percentage (%)", "mobooking"); ?>');
            valueField.find('input').attr('type', 'number').attr('placeholder', '10');
        } else if (priceType === 'multiply') {
            valueField.find('label').text('<?php _e("Multiplier", "mobooking"); ?>');
            valueField.find('input').attr('type', 'number').attr('step', '0.1').attr('placeholder', '1.5');
        } else {
            valueField.find('label').text('<?php _e("Amount ($)", "mobooking"); ?>');
            valueField.find('input').attr('type', 'number').attr('step', '0.01').attr('placeholder', '9.99');
        }
    }
    
    // Add choice button handler
    $(document).on('click', '.add-choice', function() {
        const choicesList = $(this).closest('.choices-container').find('.choices-list');
        const newRow = `
            <div class="choice-row">
                <div class="choice-value">
                    <input type="text" name="choice_value[]" placeholder="<?php _e("value", "mobooking"); ?>">
                </div>
                <div class="choice-label">
                    <input type="text" name="choice_label[]" placeholder="<?php _e("Display Label", "mobooking"); ?>">
                </div>
                <div class="choice-price">
                    <input type="number" name="choice_price[]" step="0.01" placeholder="0.00">
                </div>
                <div class="choice-actions">
                    <button type="button" class="button-link remove-choice">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        `;
        choicesList.append(newRow);
    });
    
    // Remove choice button handler
    $(document).on('click', '.remove-choice', function() {
        const choiceRow = $(this).closest('.choice-row');
        const choicesList = choiceRow.closest('.choices-list');
        
        // Don't remove if it's the only choice
        if (choicesList.find('.choice-row').length <= 1) {
            alert('<?php _e("You must have at least one choice", "mobooking"); ?>');
            return;
        }
        
        choiceRow.remove();
    });
    
    // Initialize dynamic fields if option type is already selected
    const selectedType = $('#option-type').val();
    if (selectedType) {
        generateDynamicFields(selectedType);
    }
    
    // Initialize price fields
    const selectedPriceType = $('#option-price-type').val();
    if (selectedPriceType) {
        updatePriceFields(selectedPriceType);
    }
    
    // Auto-hide notifications after 5 seconds
    setTimeout(function() {
        $('.notification').fadeOut('slow');
    }, 5000);
});
</script>

<style>
/* Enhanced styling for Services section */
.services-section {
    max-width: 1200px;
    margin: 0 auto;
}

/* Service Cards Styling */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.service-card {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e3e8f0;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.service-header {
    display: flex;
    padding: 1.25rem;
    border-bottom: 1px solid #e3e8f0;
    background-color: #f8fafd;
}

.service-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background-size: cover;
    background-position: center;
    margin-right: 1rem;
}

.service-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    background-color: #2863ec;
    color: white;
}

.service-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.service-title {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.service-title h3 {
    margin: 0 0 0.25rem;
    font-size: 1.125rem;
    color: #2863ec;
}

.category-badge {
    display: inline-block;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 30px;
    margin-top: 5px;
    background-color: rgba(40, 99, 236, 0.1);
    color: #2863ec;
}

.category-residential {
    background-color: rgba(33, 150, 243, 0.15);
    color: #1e88e5;
}

.category-commercial {
    background-color: rgba(156, 39, 176, 0.15);
    color: #9c27b0;
}

.category-special {
    background-color: rgba(255, 193, 7, 0.15);
    color: #ffc107;
}

.service-status-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    margin-left: 1rem;
}

.service-price {
    font-weight: 700;
    font-size: 1.125rem;
    color: #2863ec;
    margin-bottom: 0.25rem;
}

.service-status {
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 30px;
}

.service-status-active {
    background-color: rgba(76, 175, 80, 0.15);
    color: #43a047;
}

.service-status-inactive {
    background-color: rgba(158, 158, 158, 0.15);
    color: #64748b;
}

.service-body {
    padding: 1.25rem;
    flex-grow: 1;
}

.service-meta {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    color: #64748b;
    font-size: 0.875rem;
}

.service-meta .dashicons {
    margin-right: 0.25rem;
    color: #64748b;
}

.service-duration {
    margin-right: 1rem;
    display: flex;
    align-items: center;
}

.service-options-badge {
    display: flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 30px;
    background-color: rgba(33, 150, 243, 0.15);
    color: #1e88e5;
}

.service-options-badge .dashicons {
    margin-right: 0.25rem;
    color: #1e88e5;
}

.service-description {
    color: #020817;
    font-size: 0.9375rem;
    line-height: 1.6;
}

.service-actions {
    display: flex;
    justify-content: space-between;
    padding: 1.25rem;
    border-top: 1px solid #e3e8f0;
}

.service-actions .button {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
}

.service-actions .button .dashicons {
    margin-right: 0.25rem;
}

.delete-service-form {
    display: inline-block;
}

/* Form Styling */
.service-form-container, .option-form-container {
    max-width: 900px;
    margin: 0 auto;
}

.form-tabs {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.tabs-header {
    display: flex;
    background-color: #f8fafd;
    border-bottom: 1px solid #e3e8f0;
}

.tab-button {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    cursor: pointer;
    font-weight: 500;
    color: #64748b;
    position: relative;
    transition: all 0.3s ease;
}

.tab-button:hover {
    color: #2863ec;
    background-color: rgba(40, 99, 236, 0.05);
}

.tab-button.active {
    color: #2863ec;
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #2863ec;
}

.tabs-content {
    padding: 1.5rem;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-row {
    display: flex;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

.form-group.half {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #020817;
}

input[type="text"],
input[type="number"],
input[type="email"],
select,
textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #e3e8f0;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.3s ease;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #2863ec;
    box-shadow: 0 0 0 2px rgba(40, 99, 236, 0.15);
}

/* Image upload styling */
.image-upload-container {
    display: flex;
    gap: 0.5rem;
}

.image-preview {
    margin-top: 0.75rem;
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    border-radius: 8px;
    border: 1px solid #e3e8f0;
}

.icon-preview-container {
    margin-top: 0.75rem;
    text-align: center;
}

.icon-preview {
    display: inline-flex;
    width: 50px;
    height: 50px;
    background-color: #2863ec;
    color: white;
    border-radius: 8px;
    align-items: center;
    justify-content: center;
}

.icon-preview .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

/* Form actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1.5rem;
    margin-top: 2rem;
    border-top: 1px solid #e3e8f0;
}

/* Options styling */
.options-list {
    margin-top: 1.5rem;
}

.options-table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.options-table th {
    background-color: #f8fafd;
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    color: #020817;
    border-bottom: 1px solid #e3e8f0;
}

.options-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e3e8f0;
    background-color: white;
}

.options-table tr:last-child td {
    border-bottom: none;
}

.option-actions {
    white-space: nowrap;
}

.option-actions .button {
    padding: 0.25rem;
    line-height: 1;
}

.option-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.option-actions form {
    margin-left: 0.5rem;
}

.button-small {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.button-danger {
    background-color: #e53935;
    color: white;
}

.button-danger:hover {
    background-color: #d32f2f;
}

/* Options Info Card */
.options-info-card {
    background-color: #f8fafd;
    border-radius: 8px;
    padding: 1.25rem;
    border: 1px solid #e3e8f0;
    margin-bottom: 1.5rem;
}

.options-info-card h3 {
    margin-top: 0;
    margin-bottom: 0.75rem;
    color: #2863ec;
}

.options-info-card p {
    margin-bottom: 1rem;
    color: #64748b;
}

.options-toolbar {
    margin-top: 1rem;
}

/* Empty state styling */
.no-items, .no-options-message {
    text-align: center;
    padding: 3rem 2rem;
    background-color: #f8fafd;
    border-radius: 12px;
    border: 1px dashed #e3e8f0;
    color: #64748b;
    margin-top: 1.5rem;
}

.no-items .dashicons, .no-options-message .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* Notification styling */
.notification {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.notification-success {
    background-color: rgba(76, 175, 80, 0.15);
    color: #43a047;
    border: 1px solid rgba(76, 175, 80, 0.3);
}

.notification-error {
    background-color: rgba(244, 67, 54, 0.15);
    color: #e53935;
    border: 1px solid rgba(244, 67, 54, 0.3);
}

.notification-info {
    background-color: rgba(33, 150, 243, 0.15);
    color: #1e88e5;
    border: 1px solid rgba(33, 150, 243, 0.3);
}

.notification-warning {
    background-color: rgba(255, 152, 0, 0.15);
    color: #fb8c00;
    border: 1px solid rgba(255, 152, 0, 0.3);
}

.notification ul {
    margin: 0.5rem 0 0 1.5rem;
    padding: 0;
}

/* Choices container for select/radio options */
.choices-container {
    border: 1px solid #e3e8f0;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.choices-header {
    display: flex;
    background-color: #f8fafd;
    border-bottom: 1px solid #e3e8f0;
    padding: 0.75rem 1rem;
    font-weight: 500;
    font-size: 0.875rem;
    color: #64748b;
}

.choices-list {
    max-height: 250px;
    overflow-y: auto;
}

.choice-row {
    display: flex;
    border-bottom: 1px solid #e3e8f0;
    align-items: center;
    background-color: white;
}

.choice-row:last-child {
    border-bottom: none;
}

.choice-value {
    flex: 1;
    padding: 0.5rem;
}

.choice-label {
    flex: 2;
    padding: 0.5rem;
}

.choice-price {
    width: 100px;
    padding: 0.5rem;
}

.choice-actions {
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
}

.choice-row input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e3e8f0;
    border-radius: 6px;
    font-size: 0.875rem;
}

.add-choice-container {
    padding: 0.75rem 1rem;
    background-color: #f8fafd;
    border-top: 1px solid #e3e8f0;
}

.add-choice {
    width: 100%;
    border: 1px dashed #e3e8f0;
    background-color: white;
    color: #64748b;
    padding: 0.5rem;
    font-size: 0.875rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.add-choice:hover {
    background-color: #e6f0ff;
    color: #2863ec;
    border-color: #2863ec;
}

.remove-choice {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
}

.remove-choice:hover {
    color: #e53935;
}

.field-hint {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .service-actions {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .service-actions .button {
        width: 100%;
        justify-content: center;
    }
    
    .tabs-header {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
    }
    
    .options-table {
        display: block;
        overflow-x: auto;
    }
}
</style>