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

// Enqueue scripts and styles
wp_enqueue_style('mobooking-dashboard-style', MOBOOKING_URL . '/assets/css/dashboard.css', array(), MOBOOKING_VERSION);
wp_enqueue_style('mobooking-service-options-style', MOBOOKING_URL . '/assets/css/service-options.css', array(), MOBOOKING_VERSION);
wp_enqueue_media(); // Enable WordPress Media Uploader

// Register our new unified script
wp_register_script('mobooking-unified-service-manager', 
    MOBOOKING_URL . '/assets/js/unified-service-manager.js', 
    array('jquery', 'jquery-ui-sortable'), 
    MOBOOKING_VERSION, 
    true
);

// Localize script with needed data
wp_localize_script('mobooking-unified-service-manager', 'mobookingData', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'serviceNonce' => wp_create_nonce('mobooking-service-nonce'),
    'userId' => get_current_user_id(),
    'currentServiceId' => $service_id,
    'messages' => array(
        'savingService' => __('Saving service...', 'mobooking'),
        'serviceSuccess' => __('Service saved successfully', 'mobooking'),
        'serviceError' => __('Error saving service', 'mobooking'),
        'optionSuccess' => __('Option saved successfully', 'mobooking'),
        'optionError' => __('Error saving option', 'mobooking'),
        'deleteConfirm' => __('Are you sure you want to delete this? This action cannot be undone.', 'mobooking')
    )
));

// Enqueue the script
wp_enqueue_script('mobooking-unified-service-manager');

// Get active tab if specified in URL
$active_tab = isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info';

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

// Display any stored transient messages if they exist
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
        
        <?php if ($errors): ?>
            <div class="notification notification-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
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
                    <form id="unified-service-form">
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
                                <!-- Service Options Tab -->
                                <div class="tab-pane <?php echo $active_tab === 'options' ? 'active' : ''; ?>" id="options">
                                    <div class="options-header-card">
                                        <div class="options-info">
                                            <h3><?php _e('Service Options & Add-ons', 'mobooking'); ?></h3>
                                            <p><?php _e('Options allow customers to customize their booking with add-ons, variations, or special requests.', 'mobooking'); ?></p>
                                        </div>
                                        
                                        <button type="button" class="button button-primary add-option-button">
                                            <span class="dashicons dashicons-plus"></span>
                                            <?php _e('Add New Option', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="options-container">
                                        <div class="options-sidebar">
                                            <div class="options-search-box">
                                                <input type="text" id="options-search" placeholder="<?php esc_attr_e('Search options...', 'mobooking'); ?>">
                                            </div>
                                            
                                            <div class="options-list">
                                                <?php if (empty($options)): ?>
                                                    <div class="options-list-empty">
                                                        <p><?php _e('No options configured yet.', 'mobooking'); ?></p>
                                                        <p><?php _e('Add your first option to customize this service.', 'mobooking'); ?></p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($options as $index => $option): ?>
                                                        <div class="option-item" data-id="<?php echo esc_attr($option->id); ?>" data-order="<?php echo esc_attr($option->display_order ?? $index); ?>">
                                                            <div class="option-drag-handle">
                                                                <span class="dashicons dashicons-menu"></span>
                                                            </div>
                                                            <div class="option-content">
                                                                <span class="option-name"><?php echo esc_html($option->name); ?></span>
                                                                <div class="option-meta">
                                                                    <span class="option-type"><?php echo esc_html($option_types[$option->type] ?? $option->type); ?></span>
                                                                    <?php if ($option->is_required): ?>
                                                                        <span class="option-required"><?php _e('Required', 'mobooking'); ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="option-preview">
                                                                <?php 
                                                                // Generate a small visual indicator based on option type
                                                                $preview_html = '';
                                                                switch ($option->type) {
                                                                    case 'checkbox':
                                                                        $preview_html = '<div class="preview-checkbox"><input type="checkbox" disabled ' . ($option->default_value == 1 ? 'checked' : '') . '></div>';
                                                                        break;
                                                                    case 'select':
                                                                        $preview_html = '<div class="preview-select"><select disabled><option>...</option></select></div>';
                                                                        break;
                                                                    case 'radio':
                                                                        $preview_html = '<div class="preview-radio"><span class="radio-dot"></span></div>';
                                                                        break;
                                                                    case 'number':
                                                                    case 'quantity':
                                                                        $preview_html = '<div class="preview-number">123</div>';
                                                                        break;
                                                                    case 'text':
                                                                        $preview_html = '<div class="preview-text">Abc</div>';
                                                                        break;
                                                                    case 'textarea':
                                                                        $preview_html = '<div class="preview-textarea">Text</div>';
                                                                        break;
                                                                }
                                                                
                                                                // Add price indicator if applicable
                                                                if ($option->price_impact != 0) {
                                                                    $price_indicator = '';
                                                                    if ($option->price_type === 'percentage') {
                                                                        $price_indicator = ($option->price_impact > 0 ? '+' : '') . $option->price_impact . '%';
                                                                    } elseif ($option->price_type === 'multiply') {
                                                                        $price_indicator = '×' . $option->price_impact;
                                                                    } else {
                                                                        $price_indicator = ($option->price_impact > 0 ? '+' : '') . wc_price($option->price_impact);
                                                                    }
                                                                    
                                                                    $preview_html .= '<div class="price-indicator">' . $price_indicator . '</div>';
                                                                }
                                                                
                                                                echo $preview_html;
                                                                ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="options-content">
                                            <div class="no-option-selected">
                                                <span class="dashicons dashicons-admin-generic"></span>
                                                <h3><?php _e('Service Options', 'mobooking'); ?></h3>
                                                <p><?php _e('Select an option from the list or add a new one to edit its details.', 'mobooking'); ?></p>
                                                <button type="button" class="button button-primary add-option-button">
                                                    <?php _e('Add New Option', 'mobooking'); ?>
                                                </button>
                                            </div>
                                            
                                            <div class="option-form-container" style="display: none;">
                                                <!-- Option form template will be loaded here -->
                                                <form id="option-form">
                                                    <input type="hidden" id="option-id" name="id" value="">
                                                    <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
                                                    
                                                    <h3 class="option-form-title"><?php _e('Add New Option', 'mobooking'); ?></h3>
                                                    
                                                    <div class="option-form-grid">
                                                        <div class="form-column">
                                                            <div class="form-group">
                                                                <label for="option-name"><?php _e('Option Name', 'mobooking'); ?> <span class="required">*</span></label>
                                                                <input type="text" id="option-name" name="name" required>
                                                                <div class="field-error" id="option-name-error"></div>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                                                                <textarea id="option-description" name="description" rows="2"></textarea>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-column">
                                                            <div class="form-row">
                                                                <div class="form-group half">
                                                                    <label for="option-type"><?php _e('Option Type', 'mobooking'); ?> <span class="required">*</span></label>
                                                                    <select id="option-type" name="type" required>
                                                                        <option value=""><?php _e('Select Type', 'mobooking'); ?></option>
                                                                        <?php foreach ($option_types as $type => $label) : ?>
                                                                            <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <div class="field-error" id="option-type-error"></div>
                                                                </div>
                                                                
                                                                <div class="form-group half">
                                                                    <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                                                                    <select id="option-required" name="is_required">
                                                                        <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                                                                        <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div id="dynamic-fields" class="dynamic-fields">
                                                        <!-- Dynamic fields will be loaded via JS based on option type -->
                                                    </div>
                                                    
                                                    <div class="form-row price-impact-section">
                                                        <div class="form-group half">
                                                            <label for="option-price-type"><?php _e('Price Impact Type', 'mobooking'); ?></label>
                                                            <select id="option-price-type" name="price_type">
                                                                <?php foreach ($price_types as $type => $label) : ?>
                                                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="form-group half price-impact-value">
                                                            <label for="option-price-impact"><?php _e('Price Value', 'mobooking'); ?></label>
                                                            <input type="number" id="option-price-impact" name="price_impact" step="0.01" value="0">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-actions option-form-actions">
                                                        <button type="button" class="button button-danger delete-option-btn" style="display: none;">
                                                            <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'mobooking'); ?>
                                                        </button>
                                                        <div class="spacer"></div>
                                                        <button type="button" class="button button-secondary cancel-option-btn">
                                                            <?php _e('Cancel', 'mobooking'); ?>
                                                        </button>
                                                        <button type="button" class="button button-primary save-option-btn">
                                                            <?php _e('Save Option', 'mobooking'); ?>
                                                        </button>
                                                    </div>
                                                </form>
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

<!-- Add the CSS for error fields and loading indicators -->
<style>
.field-error {
    color: var(--danger-color);
    font-size: 0.8rem;
    margin-top: 0.3rem;
    display: none;
}

.field-error.active {
    display: block;
}

.form-group.has-error input,
.form-group.has-error select,
.form-group.has-error textarea {
    border-color: var(--danger-color);
}

.loading-spinner {
    display: inline-block;
    width: 1em;
    height: 1em;
    border: 0.2em solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
    vertical-align: middle;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.mobooking-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background-color: white;
    border-radius: var(--radius);
    padding: 1.5rem;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    position: relative;
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}
</style>

<?php
// End output buffering and flush
ob_end_flush();
?>