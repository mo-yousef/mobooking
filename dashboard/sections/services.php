<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Get current view: list, add, edit
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Initialize the service manager - using the unified ServiceManager
$services_manager = new \MoBooking\Services\ServiceManager();

// Get services
$services = $services_manager->get_user_services(get_current_user_id());

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

// Get service data if editing
$service = null;
$options = array();

if ($current_view === 'edit' && $service_id > 0) {
    // Get the service with all its options in one call
    $service = $services_manager->get_service_with_options($service_id, get_current_user_id());
    
    // Redirect to list if service not found
    if (!$service) {
        wp_redirect(add_query_arg('view', 'list', remove_query_arg(array('service_id'))));
        exit;
    }
    
    // Set options from the service
    $options = isset($service->options) ? $service->options : array();
}

// Get active tab if specified in URL
$active_tab = isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info';

// Enqueue scripts and styles
wp_enqueue_style('mobooking-dashboard-style');
wp_enqueue_style('mobooking-service-options-style');
wp_enqueue_script('jquery-ui-sortable');
wp_enqueue_media();

// Register our unified service form handler script
wp_register_script('mobooking-service-form-handler', 
    MOBOOKING_URL . '/assets/js/service-form-handler.js', 
    array('jquery', 'jquery-ui-sortable'), 
    MOBOOKING_VERSION, 
    true
);

// Localize script with needed data
wp_localize_script('mobooking-service-form-handler', 'mobookingData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'serviceNonce' => wp_create_nonce('mobooking-service-nonce'),
    'userId' => get_current_user_id(),
    'currentServiceId' => $service_id,
    'messages' => array(
        'savingService' => __('Saving service...', 'mobooking'),
        'serviceSuccess' => __('Service saved successfully', 'mobooking'),
        'serviceError' => __('Error saving service', 'mobooking'),
        'deleteConfirm' => __('Are you sure you want to delete this? This action cannot be undone.', 'mobooking')
    )
));

wp_enqueue_script('mobooking-service-form-handler');
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
                    // Check if service has options
                    $has_options = property_exists($service, 'has_options') ? $service->has_options : false;
                ?>
                    <div class="service-card" data-id="<?php echo esc_attr($service->id); ?>" data-category="<?php echo esc_attr($service->category); ?>">
                        <div class="service-header">
                            <?php if (!empty($service->image_url)) : ?>
                                <div class="service-image" style="background-image: url('<?php echo esc_url($service->image_url); ?>')"></div>
                            <?php elseif (!empty($service->icon)) : ?>
                                <div class="service-icon">
                                    <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                </div>
                            <?php else: ?>
                                <div class="service-icon">
                                    <span class="dashicons dashicons-admin-tools"></span>
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
                                <?php 
                                    // Show a short excerpt of the description
                                    $desc = strip_tags($service->description);
                                    echo strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc;
                                ?>
                            </div>
                        </div>
                        
                        <div class="service-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service->id))); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'mobooking'); ?>
                            </a>
                            
                            <button type="button" class="button button-danger delete-service-btn" data-id="<?php echo esc_attr($service->id); ?>">
                                <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php elseif ($current_view === 'add' || $current_view === 'edit'): ?>
        <?php
        // Set page title based on view
        $page_title = $current_view === 'add' ? __('Add New Service', 'mobooking') : __('Edit Service', 'mobooking');
        ?>
        
        <!-- Add/Edit Service Form -->
        <div class="service-form-container">
            <div class="section-header">
                <h2 class="section-title"><?php echo esc_html($page_title); ?></h2>
                <a href="<?php echo esc_url(add_query_arg('view', 'list', remove_query_arg(array('service_id')))); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Back to Services', 'mobooking'); ?>
                </a>
            </div>
            
            <!-- Dynamic status/error notifications container -->
            <div id="notification-container"></div>
            
            <div class="service-form-wrapper">
                <div class="service-tabs">
                    <div class="tab-buttons">
                        <button type="button" class="tab-button <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" data-tab="basic-info">
                            <span class="dashicons dashicons-info-outline"></span>
                            <?php _e('Basic Info', 'mobooking'); ?>
                        </button>
                        <button type="button" class="tab-button <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" data-tab="presentation">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('Presentation', 'mobooking'); ?>
                        </button>
                        <?php if ($current_view === 'edit'): ?>
                            <button type="button" class="tab-button <?php echo $active_tab === 'options' ? 'active' : ''; ?>" data-tab="options">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Options & Add-ons', 'mobooking'); ?>
                                <?php if (!empty($options)): ?>
                                    <span class="count-badge"><?php echo count($options); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Unified service form that handles both service and options -->
                    <form id="unified-service-form" class="unified-form">
                        <input type="hidden" name="id" id="service-id" value="<?php echo esc_attr($service_id); ?>">
                        <input type="hidden" name="user_id" value="<?php echo get_current_user_id(); ?>">
                        <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
                        
                        <div class="tab-content">
                            <!-- Basic Info Tab -->
                            <div class="tab-pane <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" id="basic-info">
                                <div class="service-form-card">
                                    <div class="form-grid">
                                        <div class="form-column">
                                            <div class="form-group">
                                                <label for="service-name"><?php _e('Service Name', 'mobooking'); ?> <span class="required">*</span></label>
                                                <input type="text" id="service-name" name="name" value="<?php echo $service ? esc_attr($service->name) : ''; ?>" required>
                                                <div class="field-error" id="name-error"></div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                                                <textarea id="service-description" name="description" rows="4"><?php echo $service ? esc_textarea($service->description) : ''; ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="form-column">
                                            <div class="form-row">
                                                <div class="form-group half">
                                                    <label for="service-price"><?php _e('Base Price', 'mobooking'); ?> <span class="required">*</span></label>
                                                    <div class="input-prefix">
                                                        <span class="prefix">$</span>
                                                        <input type="number" id="service-price" name="price" min="0" step="0.01" value="<?php echo $service ? esc_attr($service->price) : '0.00'; ?>" required>
                                                    </div>
                                                    <div class="field-error" id="price-error"></div>
                                                </div>
                                                
                                                <div class="form-group half">
                                                    <label for="service-duration"><?php _e('Duration (min)', 'mobooking'); ?> <span class="required">*</span></label>
                                                    <div class="input-suffix">
                                                        <input type="number" id="service-duration" name="duration" min="15" step="15" value="<?php echo $service ? esc_attr($service->duration) : '60'; ?>" required>
                                                        <span class="suffix">min</span>
                                                    </div>
                                                    <div class="field-error" id="duration-error"></div>
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
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Presentation Tab -->
                            <div class="tab-pane <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" id="presentation">
                                <div class="service-form-card">
                                    <div class="form-grid">
                                        <div class="form-column">
                                            <div class="form-group">
                                                <label for="service-icon"><?php _e('Icon', 'mobooking'); ?></label>
                                                <div class="icon-selector">
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
                                                    
                                                    <div class="icon-preview">
                                                        <?php if ($service && !empty($service->icon)): ?>
                                                            <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                                        <?php else: ?>
                                                            <span class="icon-placeholder"></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="icon-grid">
                                                <div class="icon-item" data-icon="dashicons-admin-home">
                                                    <span class="dashicons dashicons-admin-home"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-admin-tools">
                                                    <span class="dashicons dashicons-admin-tools"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-bucket">
                                                    <span class="dashicons dashicons-bucket"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-hammer">
                                                    <span class="dashicons dashicons-hammer"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-art">
                                                    <span class="dashicons dashicons-art"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-building">
                                                    <span class="dashicons dashicons-building"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-businesswoman">
                                                    <span class="dashicons dashicons-businesswoman"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-car">
                                                    <span class="dashicons dashicons-car"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-pets">
                                                    <span class="dashicons dashicons-pets"></span>
                                                </div>
                                                <div class="icon-item" data-icon="dashicons-palmtree">
                                                    <span class="dashicons dashicons-palmtree"></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-column">
                                            <div class="form-group">
                                                <label for="service-image"><?php _e('Image', 'mobooking'); ?></label>
                                                <div class="image-upload-container">
                                                    <input type="text" id="service-image" name="image_url" value="<?php echo $service ? esc_attr($service->image_url) : ''; ?>" placeholder="https://...">
                                                    <button type="button" class="button select-image"><?php _e('Select', 'mobooking'); ?></button>
                                                </div>
                                                <div class="image-preview">
                                                    <?php if ($service && !empty($service->image_url)): ?>
                                                        <img src="<?php echo esc_url($service->image_url); ?>" alt="">
                                                    <?php else: ?>
                                                        <div class="no-image-placeholder">
                                                            <span class="dashicons dashicons-format-image"></span>
                                                            <span><?php _e('No image selected', 'mobooking'); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($current_view === 'edit'): ?>
                                <!-- Service Options Tab (Integrated with main form) -->
                                <div class="tab-pane <?php echo $active_tab === 'options' ? 'active' : ''; ?>" id="options">
                                    <div class="options-header-card">
                                        <div class="options-info">
                                            <h3><?php _e('Service Options & Add-ons', 'mobooking'); ?></h3>
                                            <p><?php _e('Options allow customers to customize their booking with add-ons, variations, or special requests.', 'mobooking'); ?></p>
                                        </div>
                                        
                                        <button type="button" class="button button-primary add-new-option-btn">
                                            <span class="dashicons dashicons-plus"></span>
                                            <?php _e('Add New Option', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    
                                    <!-- Integrated options section -->
                                    <div id="service-options-container" class="service-options-container">
                                        <!-- Options will be dynamically added here -->
                                        <?php foreach ($options as $index => $option): ?>
                                            <div class="option-card" data-option-index="<?php echo $index; ?>">
                                                <div class="option-card-header">
                                                    <div class="option-drag-handle">
                                                        <span class="dashicons dashicons-menu"></span>
                                                    </div>
                                                    <div class="option-title">
                                                        <span class="option-name"><?php echo esc_html($option->name); ?></span>
                                                        <span class="option-type"><?php echo esc_html($option_types[$option->type] ?? $option->type); ?></span>
                                                    </div>
                                                    <div class="option-actions">
                                                        <button type="button" class="button button-small edit-option-btn">
                                                            <span class="dashicons dashicons-edit"></span>
                                                        </button>
                                                        <button type="button" class="button button-small remove-option-btn">
                                                            <span class="dashicons dashicons-trash"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="option-card-details" style="display:none;">
                                                    <!-- Hidden fields to store option data -->
                                                    <input type="hidden" name="options[<?php echo $index; ?>][id]" value="<?php echo esc_attr($option->id); ?>">
                                                    <input type="hidden" name="options[<?php echo $index; ?>][service_id]" value="<?php echo esc_attr($service_id); ?>">
                                                    
                                                    <div class="option-form">
                                                        <div class="form-row">
                                                            <div class="form-group half">
                                                                <label><?php _e('Option Name', 'mobooking'); ?> <span class="required">*</span></label>
                                                                <input type="text" name="options[<?php echo $index; ?>][name]" value="<?php echo esc_attr($option->name); ?>" required>
                                                            </div>
                                                            <div class="form-group half">
                                                                <label><?php _e('Option Type', 'mobooking'); ?></label>
                                                                <select name="options[<?php echo $index; ?>][type]" class="option-type-select">
                                                                    <?php foreach ($option_types as $type => $label): ?>
                                                                    <option value="<?php echo esc_attr($type); ?>" <?php selected($option->type, $type); ?>><?php echo esc_html($label); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group half">
                                                                <label><?php _e('Required?', 'mobooking'); ?></label>
                                                                <select name="options[<?php echo $index; ?>][is_required]">
                                                                    <option value="0" <?php selected($option->is_required, 0); ?>><?php _e('Optional', 'mobooking'); ?></option>
                                                                    <option value="1" <?php selected($option->is_required, 1); ?>><?php _e('Required', 'mobooking'); ?></option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group half">
                                                                <label><?php _e('Description', 'mobooking'); ?></label>
                                                                <input type="text" name="options[<?php echo $index; ?>][description]" value="<?php echo esc_attr($option->description); ?>">
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Type-specific fields -->
                                                        <div class="option-type-fields">
                                                            <?php if ($option->type === 'checkbox'): ?>
                                                                <div class="form-row">
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Default Value', 'mobooking'); ?></label>
                                                                        <select name="options[<?php echo $index; ?>][default_value]">
                                                                            <option value="0" <?php selected($option->default_value, 0); ?>><?php _e('Unchecked', 'mobooking'); ?></option>
                                                                            <option value="1" <?php selected($option->default_value, 1); ?>><?php _e('Checked', 'mobooking'); ?></option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Option Label', 'mobooking'); ?></label>
                                                                        <input type="text" name="options[<?php echo $index; ?>][option_label]" value="<?php echo esc_attr($option->option_label); ?>">
                                                                    </div>
                                                                </div>
                                                            <?php elseif ($option->type === 'select' || $option->type === 'radio'): ?>
                                                                <!-- Choices container -->
                                                                <div class="form-group">
                                                                    <label><?php _e('Choices', 'mobooking'); ?></label>
                                                                    <div class="choices-container">
                                                                        <div class="choices-list">
                                                                            <?php 
                                                                            $choices = isset($option->choices) ? $option->choices : array();
                                                                            if (empty($choices) && !empty($option->options)) {
                                                                                // Parse options from string if not already parsed
                                                                                $choices = parseOptionChoices($option->options);
                                                                            }
                                                                            
                                                                            foreach ($choices as $choice_index => $choice): 
                                                                            ?>
                                                                                <div class="choice-row">
                                                                                    <div class="choice-value">
                                                                                        <input type="text" name="options[<?php echo $index; ?>][choices][<?php echo $choice_index; ?>][value]" 
                                                                                               value="<?php echo esc_attr($choice['value']); ?>" placeholder="Value">
                                                                                    </div>
                                                                                    <div class="choice-label">
                                                                                        <input type="text" name="options[<?php echo $index; ?>][choices][<?php echo $choice_index; ?>][label]" 
                                                                                               value="<?php echo esc_attr($choice['label']); ?>" placeholder="Label">
                                                                                    </div>
                                                                                    <div class="choice-price">
                                                                                        <input type="number" name="options[<?php echo $index; ?>][choices][<?php echo $choice_index; ?>][price]" 
                                                                                               value="<?php echo esc_attr($choice['price']); ?>" step="0.01" placeholder="0.00">
                                                                                    </div>
                                                                                    <div class="choice-actions">
                                                                                        <button type="button" class="remove-choice-btn">
                                                                                            <span class="dashicons dashicons-trash"></span>
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                        <button type="button" class="add-choice-btn button-secondary">
                                                                            <?php _e('Add Choice', 'mobooking'); ?>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label><?php _e('Default Value', 'mobooking'); ?></label>
                                                                    <input type="text" name="options[<?php echo $index; ?>][default_value]" 
                                                                           value="<?php echo esc_attr($option->default_value); ?>" 
                                                                           placeholder="<?php _e('Enter the value of the default choice', 'mobooking'); ?>">
                                                                </div>
                                                            <?php elseif ($option->type === 'number' || $option->type === 'quantity'): ?>
                                                                <div class="form-row">
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Minimum Value', 'mobooking'); ?></label>
                                                                        <input type="number" name="options[<?php echo $index; ?>][min_value]" 
                                                                               value="<?php echo $option->min_value !== null ? esc_attr($option->min_value) : ''; ?>" step="any">
                                                                    </div>
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Maximum Value', 'mobooking'); ?></label>
                                                                        <input type="number" name="options[<?php echo $index; ?>][max_value]" 
                                                                               value="<?php echo $option->max_value !== null ? esc_attr($option->max_value) : ''; ?>" step="any">
                                                                    </div>
                                                                </div>
                                                                <div class="form-row">
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Default Value', 'mobooking'); ?></label>
                                                                        <input type="number" name="options[<?php echo $index; ?>][default_value]" 
                                                                               value="<?php echo esc_attr($option->default_value); ?>" step="any">
                                                                    </div>
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Step', 'mobooking'); ?></label>
                                                                        <input type="number" name="options[<?php echo $index; ?>][step]" 
                                                                               value="<?php echo esc_attr($option->step ?: '1'); ?>" step="any">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label><?php _e('Unit Label', 'mobooking'); ?></label>
                                                                    <input type="text" name="options[<?php echo $index; ?>][unit]" 
                                                                           value="<?php echo esc_attr($option->unit); ?>" 
                                                                           placeholder="<?php _e('e.g., hours, sq ft', 'mobooking'); ?>">
                                                                </div>
                                                            <?php elseif ($option->type === 'text'): ?>
                                                                <div class="form-row">
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Default Value', 'mobooking'); ?></label>
                                                                        <input type="text" name="options[<?php echo $index; ?>][default_value]" 
                                                                               value="<?php echo esc_attr($option->default_value); ?>">
                                                                    </div>
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Placeholder', 'mobooking'); ?></label>
                                                                        <input type="text" name="options[<?php echo $index; ?>][placeholder]" 
                                                                               value="<?php echo esc_attr($option->placeholder); ?>">
                                                                    </div>
                                                                </div>
                                                            <?php elseif ($option->type === 'textarea'): ?>
                                                                <div class="form-group">
                                                                    <label><?php _e('Default Value', 'mobooking'); ?></label>
                                                                    <textarea name="options[<?php echo $index; ?>][default_value]" rows="3"><?php echo esc_textarea($option->default_value); ?></textarea>
                                                                </div>
                                                                <div class="form-row">
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Placeholder', 'mobooking'); ?></label>
                                                                        <input type="text" name="options[<?php echo $index; ?>][placeholder]" 
                                                                               value="<?php echo esc_attr($option->placeholder); ?>">
                                                                    </div>
                                                                    <div class="form-group half">
                                                                        <label><?php _e('Rows', 'mobooking'); ?></label>
                                                                        <input type="number" name="options[<?php echo $index; ?>][rows]" 
                                                                               value="<?php echo esc_attr($option->rows ?: '3'); ?>" min="2">
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <!-- Price impact section (common to all option types) -->
                                                        <div class="form-row price-impact-section">
                                                            <div class="form-group half">
                                                                <label><?php _e('Price Impact Type', 'mobooking'); ?></label>
                                                                <select name="options[<?php echo $index; ?>][price_type]" class="price-type-select">
                                                                    <?php foreach ($price_types as $type => $label): ?>
                                                                    <option value="<?php echo esc_attr($type); ?>" <?php selected($option->price_type, $type); ?>><?php echo esc_html($label); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group half price-impact-value">
                                                                <label><?php _e('Price Impact Value', 'mobooking'); ?></label>
                                                                <input type="number" name="options[<?php echo $index; ?>][price_impact]" 
                                                                       value="<?php echo esc_attr($option->price_impact); ?>" step="0.01">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Template for adding new options -->
                                        <div id="new-option-template" style="display:none;">
                                            <div class="option-card" data-option-index="{index}">
                                                <div class="option-card-header">
                                                    <div class="option-drag-handle">
                                                        <span class="dashicons dashicons-menu"></span>
                                                    </div>
                                                    <div class="option-title">
                                                        <span class="option-name"><?php _e('New Option', 'mobooking'); ?></span>
                                                        <span class="option-type"><?php _e('Checkbox', 'mobooking'); ?></span>
                                                    </div>
                                                    <div class="option-actions">
                                                        <button type="button" class="button button-small edit-option-btn">
                                                            <span class="dashicons dashicons-edit"></span>
                                                        </button>
                                                        <button type="button" class="button button-small remove-option-btn">
                                                            <span class="dashicons dashicons-trash"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="option-card-details" style="display:block;">
                                                    <!-- Hidden fields to store option data -->
                                                    <input type="hidden" name="options[{index}][id]" value="">
                                                    <input type="hidden" name="options[{index}][service_id]" value="<?php echo esc_attr($service_id); ?>">
                                                    
                                                    <div class="option-form">
                                                        <div class="form-row">
                                                            <div class="form-group half">
                                                                <label><?php _e('Option Name', 'mobooking'); ?> <span class="required">*</span></label>
                                                                <input type="text" name="options[{index}][name]" value="<?php _e('New Option', 'mobooking'); ?>" required>
                                                            </div>
                                                            <div class="form-group half">
                                                                <label><?php _e('Option Type', 'mobooking'); ?></label>
                                                                <select name="options[{index}][type]" class="option-type-select">
                                                                    <?php foreach ($option_types as $type => $label): ?>
                                                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group half">
                                                                <label><?php _e('Required?', 'mobooking'); ?></label>
                                                                <select name="options[{index}][is_required]">
                                                                    <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                                                                    <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                                                                </select>
                                                            </div>
                                                            <div class="form-group half">
                                                                <label><?php _e('Description', 'mobooking'); ?></label>
                                                                <input type="text" name="options[{index}][description]" value="">
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Type-specific fields will be loaded dynamically -->
                                                        <div class="option-type-fields">
                                                            <!-- Default checkbox fields -->
                                                            <div class="form-row">
                                                                <div class="form-group half">
                                                                    <label><?php _e('Default Value', 'mobooking'); ?></label>
                                                                    <select name="options[{index}][default_value]">
                                                                        <option value="0"><?php _e('Unchecked', 'mobooking'); ?></option>
                                                                        <option value="1"><?php _e('Checked', 'mobooking'); ?></option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group half">
                                                                    <label><?php _e('Option Label', 'mobooking'); ?></label>
                                                                    <input type="text" name="options[{index}][option_label]" value="">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Price impact section (common to all option types) -->
                                                        <div class="form-row price-impact-section">
                                                            <div class="form-group half">
                                                                <label><?php _e('Price Impact Type', 'mobooking'); ?></label>
                                                                <select name="options[{index}][price_type]" class="price-type-select">
                                                                    <?php foreach ($price_types as $type => $label): ?>
                                                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group half price-impact-value">
                                                                <label><?php _e('Price Impact Value', 'mobooking'); ?></label>
                                                                <input type="number" name="options[{index}][price_impact]" value="0" step="0.01">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <a href="<?php echo esc_url(add_query_arg('view', 'list', remove_query_arg(array('service_id')))); ?>" class="button button-secondary"><?php _e('Cancel', 'mobooking'); ?></a>
                            <button type="submit" class="button button-primary" id="save-service-button">
                                <span class="normal-state"><?php _e('Save Service', 'mobooking'); ?></span>
                                <span class="loading-state" style="display: none;">
                                    <span class="spinner-icon"></span>
                                    <?php _e('Saving...', 'mobooking'); ?>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Confirmation Modal for Delete Actions -->
<div id="confirmation-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3><?php _e('Confirm Deletion', 'mobooking'); ?></h3>
        <p id="confirmation-message"><?php _e('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'); ?></p>
        <div class="modal-actions">
            <button type="button" class="button button-secondary cancel-delete-btn"><?php _e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="button button-danger confirm-delete-btn"><?php _e('Delete', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<?php
// Helper function to parse option choices
function parseOptionChoices($options_string) {
    if (!$options_string) {
        return array();
    }
    
    $choices = array();
    $lines = explode("\n", $options_string);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = explode('|', $line);
        $value = trim($parts[0]);
        
        if (isset($parts[1])) {
            $label_price_parts = explode(':', trim($parts[1]));
            $label = trim($label_price_parts[0]);
            $price = isset($label_price_parts[1]) ? floatval(trim($label_price_parts[1])) : 0;
        } else {
            $label = $value;
            $price = 0;
        }
        
        $choices[] = array(
            'value' => $value,
            'label' => $label,
            'price' => $price
        );
    }
    
    return $choices;
}

// End output buffering and flush
ob_end_flush();
?>