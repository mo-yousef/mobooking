<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering to prevent "headers already sent" errors
ob_start();
// Debug panel
echo mobooking_debug_panel();
echo '<h2>' . __('Debug Information', 'mobooking') . '</h2>';

// Get current view: list, add, edit
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Initialize the managers using the new separate tables architecture
$services_manager = new \MoBooking\Services\ServiceManager();
$options_manager = new \MoBooking\Services\ServiceOptionsManager();

// Get current user ID
$current_user_id = get_current_user_id();

// Get services for the current user
$services = $services_manager->get_user_services($current_user_id);

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
    // Get the service with all its options using the new method
    $service = $services_manager->get_service_with_options($service_id, $current_user_id);
    
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

// Register our service form handler script (using the new separate tables handler)
wp_register_script('mobooking-services-handler', 
    MOBOOKING_URL . '/assets/js/service-form-handler.js', 
    array('jquery', 'jquery-ui-sortable'), 
    MOBOOKING_VERSION, 
    true
);

// Localize script with needed data for new architecture
wp_localize_script('mobooking-services-handler', 'mobookingServices', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'serviceNonce' => wp_create_nonce('mobooking-service-nonce'),
    'userId' => $current_user_id,
    'currentServiceId' => $service_id,
    'currentView' => $current_view,
    'activeTab' => $active_tab,
    'messages' => array(
        'savingService' => __('Saving service...', 'mobooking'),
        'serviceSuccess' => __('Service saved successfully', 'mobooking'),
        'serviceError' => __('Error saving service', 'mobooking'),
        'savingOption' => __('Saving option...', 'mobooking'),
        'optionSuccess' => __('Option saved successfully', 'mobooking'),
        'optionError' => __('Error saving option', 'mobooking'),
        'deleteConfirm' => __('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'),
        'loadingOptions' => __('Loading options...', 'mobooking'),
        'noOptionsFound' => __('No options found for this service.', 'mobooking')
    ),
    'endpoints' => array(
        'saveService' => 'mobooking_save_service',
        'deleteService' => 'mobooking_delete_service',
        'getService' => 'mobooking_get_service',
        'saveOption' => 'mobooking_save_service_option',
        'getOption' => 'mobooking_get_service_option',
        'getOptions' => 'mobooking_get_service_options',
        'deleteOption' => 'mobooking_delete_service_option',
        'updateOptionsOrder' => 'mobooking_update_options_order'
    )
));

wp_enqueue_script('mobooking-services-handler');
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
            <?php     mobooking_diagnose_services(); ?>
            <div class="no-items">
                <span class="dashicons dashicons-admin-tools"></span>
                <p><?php _e('You haven\'t created any services yet.', 'mobooking'); ?></p>
                <p><?php _e('Add your first service to start receiving bookings.', 'mobooking'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('view', 'add')); ?>" class="button button-primary"><?php _e('Add Your First Service', 'mobooking'); ?></a>
            </div>
        <?php else : ?>
            <div class="services-grid">
                <?php foreach ($services as $service) : ?>
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
                                
                                <?php if ($service->has_options) : ?>
                                    <span class="service-options-badge">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php printf(_n('%d option', '%d options', $service->options_count, 'mobooking'), $service->options_count); ?>
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
                    
                    <!-- Service form -->
                    <form id="service-form" class="service-form">
                        <input type="hidden" name="id" id="service-id" value="<?php echo esc_attr($service_id); ?>">
                        <input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>">
                        <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
                        
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
                                <!-- Service Options Tab -->
                                <div class="tab-pane <?php echo $active_tab === 'options' ? 'active' : ''; ?>" id="options">
                                    <div class="options-header-card">
                                        <div class="options-info">
                                            <h3><?php _e('Service Options & Add-ons', 'mobooking'); ?></h3>
                                            <p><?php _e('Options allow customers to customize their booking with add-ons, variations, or special requests.', 'mobooking'); ?></p>
                                        </div>
                                        
                                        <button type="button" class="button button-primary" id="add-option-btn" <?php echo !$service_id ? 'disabled title="' . __('Save the service first to add options', 'mobooking') . '"' : ''; ?>>
                                            <span class="dashicons dashicons-plus"></span>
                                            <?php _e('Add New Option', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    
                                    <!-- Options List -->
                                    <div id="service-options-container" class="service-options-container">
                                        <?php if (empty($options)): ?>
                                            <div class="no-options-message">
                                                <span class="dashicons dashicons-admin-generic"></span>
                                                <p><?php _e('No options configured yet. Add your first option to customize this service.', 'mobooking'); ?></p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($options as $index => $option): ?>
                                                <div class="option-card" data-option-id="<?php echo esc_attr($option->id); ?>">
                                                    <div class="option-card-header">
                                                        <div class="option-drag-handle">
                                                            <span class="dashicons dashicons-menu"></span>
                                                        </div>
                                                        <div class="option-title">
                                                            <span class="option-name"><?php echo esc_html($option->name); ?></span>
                                                            <span class="option-type"><?php echo esc_html($option_types[$option->type] ?? $option->type); ?></span>
                                                            <?php if ($option->is_required): ?>
                                                                <span class="option-required"><?php _e('Required', 'mobooking'); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="option-actions">
                                                            <button type="button" class="button button-small edit-option-btn">
                                                                <span class="dashicons dashicons-edit"></span>
                                                            </button>
                                                            <button type="button" class="button button-small delete-option-btn" data-option-id="<?php echo esc_attr($option->id); ?>">
                                                                <span class="dashicons dashicons-trash"></span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <a href="<?php echo esc_url(add_query_arg('view', 'list', remove_query_arg(array('service_id')))); ?>" class="button button-secondary"><?php _e('Cancel', 'mobooking'); ?></a>
                            
                            <?php if ($current_view === 'edit' && $service_id): ?>
                                <button type="button" class="button button-danger delete-service-btn" data-id="<?php echo esc_attr($service_id); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Delete Service', 'mobooking'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button type="submit" class="button button-primary" id="save-service-button">
                                <span class="normal-state">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Save Service', 'mobooking'); ?>
                                </span>
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

<!-- Option Form Modal -->
<div id="option-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3 id="option-modal-title"><?php _e('Add Option', 'mobooking'); ?></h3>
        
        <form id="option-form">
            <input type="hidden" id="option-id" name="id" value="">
            <input type="hidden" id="option-service-id" name="service_id" value="<?php echo esc_attr($service_id); ?>">
            <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="option-name"><?php _e('Option Name', 'mobooking'); ?> <span class="required">*</span></label>
                    <input type="text" id="option-name" name="name" required>
                    <div class="field-error" id="option-name-error"></div>
                </div>
                <div class="form-group half">
                    <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
                    <select id="option-type" name="type">
                        <?php foreach ($option_types as $type => $label): ?>
                            <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                <input type="text" id="option-description" name="description" placeholder="<?php _e('Optional description for customers', 'mobooking'); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                    <select id="option-required" name="is_required">
                        <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                        <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                    </select>
                </div>
                <div class="form-group half">
                    <label for="option-price-type"><?php _e('Price Impact Type', 'mobooking'); ?></label>
                    <select id="option-price-type" name="price_type">
                        <?php foreach ($price_types as $type => $label): ?>
                            <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group" id="price-impact-group">
                <label for="option-price-impact"><?php _e('Price Impact Value', 'mobooking'); ?></label>
                <input type="number" id="option-price-impact" name="price_impact" step="0.01" value="0" placeholder="0.00">
                <p class="field-hint"><?php _e('Enter the amount or percentage based on the type selected above', 'mobooking'); ?></p>
            </div>
            
            <!-- Dynamic fields will be loaded here based on option type -->
            <div id="option-dynamic-fields"></div>
            
            <div class="form-actions">
                <button type="button" id="delete-option-btn" class="button button-danger" style="display: none;">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Delete Option', 'mobooking'); ?>
                </button>
                <div class="spacer"></div>
                <button type="button" class="button button-secondary" id="cancel-option-btn">
                    <?php _e('Cancel', 'mobooking'); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <span class="normal-state">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Save Option', 'mobooking'); ?>
                    </span>
                    <span class="loading-state" style="display: none;">
                        <span class="spinner-icon"></span>
                        <?php _e('Saving...', 'mobooking'); ?>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal for Delete Actions -->
<div id="confirmation-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3><?php _e('Confirm Deletion', 'mobooking'); ?></h3>
        <p id="confirmation-message"><?php _e('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'); ?></p>
        <div class="modal-actions">
            <button type="button" class="button button-secondary cancel-delete-btn"><?php _e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="button button-danger confirm-delete-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Delete', 'mobooking'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="mobooking-notification" class="mobooking-notification" style="display: none;"></div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p><?php _e('Loading...', 'mobooking'); ?></p>
    </div>
</div>

<style>
/* Additional CSS for loading states and notifications */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-spinner {
    text-align: center;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.mobooking-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    max-width: 350px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideInRight 0.3s ease;
}

.mobooking-notification.success {
    background-color: var(--success-color);
}

.mobooking-notification.error {
    background-color: var(--danger-color);
}

.mobooking-notification.warning {
    background-color: var(--warning-color);
}

.mobooking-notification.info {
    background-color: var(--info-color);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.form-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    padding-top: 1.5rem;
    margin-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.form-actions .spacer {
    flex: 1;
}

.input-prefix,
.input-suffix {
    display: flex;
    align-items: center;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow: hidden;
}

.input-prefix input,
.input-suffix input {
    border: none !important;
    flex: 1;
}

.prefix,
.suffix {
    background-color: #f5f7fa;
    padding: 0.75rem;
    color: var(--text-light);
    font-weight: 500;
}

.options-header-card {
    background-color: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.options-info h3 {
    margin: 0 0 0.5rem 0;
    color: var(--text-color);
}

.options-info p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

.count-badge {
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

#add-option-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.no-options-message {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-light);
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: var(--radius);
    border: 1px dashed var(--border-color);
}

.no-options-message .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}
</style>
<?php
// End output buffering and flush
ob_end_flush();
?>












