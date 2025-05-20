<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering immediately to prevent "headers already sent" errors
ob_start();

// Get current view: list, add, edit
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

// Initialize the service manager
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

// Get active tab if specified in URL
$active_tab = isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info';

// Get service data if editing
$service = null;
if ($current_view === 'edit' && $service_id > 0) {
    $service = $services_manager->get_service($service_id, get_current_user_id());
    
    // Redirect to list if service not found
    if (!$service) {
        wp_redirect(add_query_arg('view', 'list', remove_query_arg(array('service_id'))));
        exit;
    }
    
    // Get service options
    if (method_exists($services_manager, 'get_service_options')) {
        $options = $services_manager->get_service_options($service_id);
    } else {
        $options = array();
    }
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
                    $has_options = method_exists($services_manager, 'has_service_options') ? 
                        $services_manager->has_service_options($service->id) : false;
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
                            
                            <form class="delete-service-form">
                                <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
                                <input type="hidden" name="service_id" value="<?php echo esc_attr($service->id); ?>">
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
                    
                    <form id="service-form" method="post">
                        <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
                        <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
                        
                        <div class="tab-content">
                            <!-- Basic Info Tab -->
                            <div class="tab-pane <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" id="basic-info">
                                <div class="service-form-card">
                                    <div class="form-grid">
                                        <div class="form-column">
                                            <div class="form-group">
                                                <label for="name"><?php _e('Service Name', 'mobooking'); ?> <span class="required">*</span></label>
                                                <input type="text" id="name" name="name" value="<?php echo $service ? esc_attr($service->name) : ''; ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="description"><?php _e('Description', 'mobooking'); ?></label>
                                                <textarea id="description" name="description" rows="4"><?php echo $service ? esc_textarea($service->description) : ''; ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="form-column">
                                            <div class="form-row">
                                                <div class="form-group half">
                                                    <label for="price"><?php _e('Base Price', 'mobooking'); ?> <span class="required">*</span></label>
                                                    <div class="input-prefix">
                                                        <span class="prefix">$</span>
                                                        <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo $service ? esc_attr($service->price) : '0.00'; ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group half">
                                                    <label for="duration"><?php _e('Duration (min)', 'mobooking'); ?> <span class="required">*</span></label>
                                                    <div class="input-suffix">
                                                        <input type="number" id="duration" name="duration" min="15" step="15" value="<?php echo $service ? esc_attr($service->duration) : '60'; ?>" required>
                                                        <span class="suffix">min</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-row">
                                                <div class="form-group half">
                                                    <label for="category"><?php _e('Category', 'mobooking'); ?></label>
                                                    <select id="category" name="category">
                                                        <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                                        <?php foreach ($categories as $slug => $name) : ?>
                                                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($service && $service->category === $slug); ?>>
                                                                <?php echo esc_html($name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group half">
                                                    <label for="status"><?php _e('Status', 'mobooking'); ?></label>
                                                    <select id="status" name="status">
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
                                                <label for="icon"><?php _e('Icon', 'mobooking'); ?></label>
                                                <div class="icon-selector">
                                                    <select id="icon" name="icon">
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
                                                <label for="image_url"><?php _e('Image', 'mobooking'); ?></label>
                                                <div class="image-upload-container">
                                                    <input type="text" id="image_url" name="image_url" value="<?php echo $service ? esc_attr($service->image_url) : ''; ?>" placeholder="https://...">
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
                                            
                                            <?php if (empty($options)): ?>
                                                <div class="options-list-empty">
                                                    <p><?php _e('No options configured yet.', 'mobooking'); ?></p>
                                                    <p><?php _e('Add your first option to customize this service.', 'mobooking'); ?></p>
                                                </div>
                                            <?php else: ?>
                                                <div class="options-list">
                                                    <?php foreach ($options as $index => $option): ?>
                                                        <div class="option-item" data-id="<?php echo esc_attr($option->id); ?>" data-order="<?php echo esc_attr($option->display_order); ?>">
                                                            <div class="option-drag-handle">
                                                                <span class="dashicons dashicons-menu"></span>
                                                            </div>
                                                            <div class="option-content">
                                                                <span class="option-name"><?php echo esc_html($option->name); ?></span>
                                                                <div class="option-meta">
                                                                    <span class="option-type"><?php echo esc_html($option_types[$option->type] ?? $option->type); ?></span>
                                                                    <?php if ($option->is_required): ?>
                                                                        <span class="option-required">Required</span>
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
                                                </div>
                                            <?php endif; ?>
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
                                                <!-- Option form will be loaded here via AJAX -->
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
        
        <!-- Option Form Template (for AJAX loading) -->
        <script type="text/template" id="option-form-template">
            <form id="option-form" method="post" action="">
                <input type="hidden" name="option_action" value="save">
                <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
                <input type="hidden" name="option_id" value="{id}">
                <?php wp_nonce_field('mobooking-option-nonce', 'option_nonce'); ?>
                
                <h3 class="option-form-title">{title}</h3>
                
                <div class="option-form-grid">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="option-name"><?php _e('Option Name', 'mobooking'); ?> <span class="required">*</span></label>
                            <input type="text" id="option-name" name="name" value="{name}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                            <textarea id="option-description" name="description" rows="2">{description}</textarea>
                        </div>
                    </div>
                    
                    <div class="form-column">
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="option-type"><?php _e('Option Type', 'mobooking'); ?> <span class="required">*</span></label>
                                <select id="option-type" name="type" required>
                                    <option value=""><?php _e('Select Type', 'mobooking'); ?></option>
                                    <?php foreach ($option_types as $type => $label) : ?>
                                        <option value="<?php echo esc_attr($type); ?>">{type_selected_<?php echo esc_attr($type); ?>}><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group half">
                                <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                                <select id="option-required" name="is_required">
                                    <option value="0" {required_selected_0}><?php _e('Optional', 'mobooking'); ?></option>
                                    <option value="1" {required_selected_1}><?php _e('Required', 'mobooking'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="dynamic-fields" class="dynamic-fields">
                    <!-- Placeholder for dynamic fields based on option type -->
                </div>
                
                <div class="form-row price-impact-section">
                    <div class="form-group half">
                        <label for="option-price-type"><?php _e('Price Impact Type', 'mobooking'); ?></label>
                        <select id="option-price-type" name="price_type">
                            <?php foreach ($price_types as $type => $label) : ?>
                                <option value="<?php echo esc_attr($type); ?>" {price_type_selected_<?php echo esc_attr($type); ?>}><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group half price-impact-value">
                        <label for="option-price-impact"><?php _e('Price Value', 'mobooking'); ?></label>
                        <input type="number" id="option-price-impact" name="price_impact" step="0.01" value="{price_impact}">
                    </div>
                </div>
                
                <div class="form-actions option-form-actions">
                    <button type="button" class="button button-danger delete-option" {delete_button_visibility}>
                        <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'mobooking'); ?>
                    </button>
                    <div class="spacer"></div>
                    <button type="button" class="button button-secondary cancel-option"><?php _e('Cancel', 'mobooking'); ?></button>
                    <button type="submit" class="button button-primary save-option"><?php _e('Save Option', 'mobooking'); ?></button>
                </div>
            </form>
        </script>
    <?php endif; ?>
</div>

<style>
/* AJAX Form Styling */
.spinner-icon {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

.loading-state {
    display: inline-flex;
    align-items: center;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Notification styling */
#mobooking-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    transition: opacity 0.3s ease;
    max-width: 400px;
}

#mobooking-notification.notification-success {
    background-color: #43a047;
    color: white;
}

#mobooking-notification.notification-error {
    background-color: #e53935;
    color: white;
}

#mobooking-notification.notification-info {
    background-color: #1e88e5;
    color: white;
}
</style>

<script>
// Add to functions.php to register AJAX endpoints
<?php 
// This code is for reference - should be in functions.php
/*
function mobooking_register_service_ajax_endpoints() {
    add_action('wp_ajax_mobooking_save_service_ajax', 'mobooking_save_service_ajax_handler');
    add_action('wp_ajax_mobooking_delete_service_ajax', 'mobooking_delete_service_ajax_handler');
}
add_action('init', 'mobooking_register_service_ajax_endpoints');

function mobooking_save_service_ajax_handler() {
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    
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
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
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
    
    if (!empty($errors)) {
        wp_send_json_error(['message' => implode('<br>', $errors)]);
        return;
    }
    
    // Initialize the service manager
    $services_manager = new \MoBooking\Services\ServiceManager();
    
    // Save service
    $new_service_id = $services_manager->save_service($service_data);
    
    if (!$new_service_id) {
        wp_send_json_error(['message' => __('Failed to save service. Please try again.', 'mobooking')]);
        return;
    }
    
    // Return success
    wp_send_json_success([
        'id' => $new_service_id,
        'message' => $service_id > 0 ? 
            __('Service updated successfully', 'mobooking') : 
            __('Service created successfully', 'mobooking')
    ]);
}

function mobooking_delete_service_ajax_handler() {
    // Check nonce
    if (!isset($_POST['service_nonce']) || !wp_verify_nonce($_POST['service_nonce'], 'mobooking-service-nonce')) {
        wp_send_json_error(['message' => __('Security verification failed.', 'mobooking')]);
    }
    
    // Check permissions
    if (!current_user_can('mobooking_business_owner') && !current_user_can('administrator')) {
        wp_send_json_error(['message' => __('You do not have permission to do this.', 'mobooking')]);
    }
    
    // Check service ID
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    if ($service_id <= 0) {
        wp_send_json_error(['message' => __('Invalid service ID.', 'mobooking')]);
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    
    // Initialize the service manager
    $services_manager = new \MoBooking\Services\ServiceManager();
    
    // Delete service
    $result = $services_manager->delete_service($service_id, $user_id);
    
    if (!$result) {
        wp_send_json_error(['message' => __('Failed to delete service.', 'mobooking')]);
        return;
    }
    
    // Return success
    wp_send_json_success([
        'message' => __('Service deleted successfully', 'mobooking')
    ]);
}
*/
?>

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Toggle active class on buttons
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show selected tab content
                const tabPanes = document.querySelectorAll('.tab-pane');
                tabPanes.forEach(pane => pane.classList.remove('active'));
                document.getElementById(tabId).classList.add('active');
            });
        });
    }
    
    // Service filter
    const serviceFilter = document.getElementById('service-filter');
    if (serviceFilter) {
        serviceFilter.addEventListener('change', function() {
            const category = this.value;
            const serviceCards = document.querySelectorAll('.service-card');
            
            if (category === '') {
                serviceCards.forEach(card => card.style.display = 'block');
            } else {
                serviceCards.forEach(card => {
                    if (card.getAttribute('data-category') === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });
    }
    
    // Service icon selection
    const iconItems = document.querySelectorAll('.icon-item');
    if (iconItems.length > 0) {
        iconItems.forEach(item => {
            item.addEventListener('click', function() {
                const iconClass = this.getAttribute('data-icon');
                const iconSelect = document.getElementById('icon');
                if (iconSelect) {
                    iconSelect.value = iconClass;
                    
                    // Update preview
                    const iconPreview = document.querySelector('.icon-preview');
                    if (iconPreview) {
                        iconPreview.innerHTML = `<span class="dashicons ${iconClass}"></span>`;
                    }
                }
            });
        });
    }
    
    // Media uploader for service image
    const selectImageBtn = document.querySelector('.select-image');
    if (selectImageBtn && typeof wp !== 'undefined' && wp.media) {
        let mediaUploader;
        
        selectImageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Create the media uploader
            mediaUploader = wp.media({
                title: 'Choose Image',
                button: {
                    text: 'Select'
                },
                multiple: false
            });
            
            // When an image is selected, run a callback
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                const imageUrlInput = document.getElementById('image_url');
                const imagePreview = document.querySelector('.image-preview');
                
                if (imageUrlInput) {
                    imageUrlInput.value = attachment.url;
                }
                
                if (imagePreview) {
                    imagePreview.innerHTML = `<img src="${attachment.url}" alt="">`;
                }
            });
            
            // Open the uploader dialog
            mediaUploader.open();
        });
    }
    
    // Preview icon when selected from dropdown
    const iconSelect = document.getElementById('icon');
    if (iconSelect) {
        iconSelect.addEventListener('change', function() {
            const iconClass = this.value;
            const iconPreview = document.querySelector('.icon-preview');
            
            if (iconPreview) {
                if (iconClass) {
                    iconPreview.innerHTML = `<span class="dashicons ${iconClass}"></span>`;
                } else {
                    iconPreview.innerHTML = '<span class="icon-placeholder"></span>';
                }
            }
        });
    }
    
    // Service Form Submission
    const serviceForm = document.getElementById('service-form');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const saveButton = document.getElementById('save-service-button');
            if (saveButton) {
                const normalState = saveButton.querySelector('.normal-state');
                const loadingState = saveButton.querySelector('.loading-state');
                
                if (normalState) normalState.style.display = 'none';
                if (loadingState) loadingState.style.display = 'inline-flex';
                saveButton.disabled = true;
            }
            
            // Get form data
            const formData = new FormData(serviceForm);
            formData.append('action', 'mobooking_save_service_ajax');
            
            // Submit form via fetch
            fetch(mobooking_data.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                if (saveButton) {
                    const normalState = saveButton.querySelector('.normal-state');
                    const loadingState = saveButton.querySelector('.loading-state');
                    
                    if (normalState) normalState.style.display = 'inline';
                    if (loadingState) loadingState.style.display = 'none';
                    saveButton.disabled = false;
                }
                
                if (data.success) {
                    // Show success message
                    showNotification(data.data.message, 'success');
                    
                    // If it's a new service, update the form with the new ID
                    const serviceIdField = serviceForm.querySelector('input[name="service_id"]');
                    const isNewService = !serviceIdField.value || serviceIdField.value === '0';
                    
                    if (isNewService && data.data.id) {
                        // Update service ID field
                        serviceIdField.value = data.data.id;
                        
                        // Update title and URL
                        const titleElem = document.querySelector('.section-title');
                        if (titleElem) {
                            titleElem.textContent = 'Edit Service';
                        }
                        
                        // Update URL without page reload
                        const newUrl = window.location.href.replace('view=add', 'view=edit') + '&service_id=' + data.data.id;
                        window.history.pushState({}, '', newUrl);
                        
                        // Show success message announcing the service was created
                        showNotification('Service created successfully! You can now add options.', 'success');
                        
                        // Switch to options tab if available
                        const optionsTab = document.querySelector('.tab-button[data-tab="options"]');
                        if (optionsTab) {
                            setTimeout(() => {
                                optionsTab.click();
                            }, 500);
                        }
                    } else {
                        // For existing services, just show the success message
                        showNotification('Service updated successfully', 'success');
                    }
                } else {
                    // Show error message
                    showNotification(data.data.message || 'Error saving service', 'error');
                }
            })
            .catch(error => {
                // Reset button state
                if (saveButton) {
                    const normalState = saveButton.querySelector('.normal-state');
                    const loadingState = saveButton.querySelector('.loading-state');
                    
                    if (normalState) normalState.style.display = 'inline';
                    if (loadingState) loadingState.style.display = 'none';
                    saveButton.disabled = false;
                }
                
                // Show error message
                console.error('Error submitting form:', error);
                showNotification('An error occurred while processing your request. Please try again.', 'error');
            });
        });
    }
    
    // Service Delete Button
    const deleteButtons = document.querySelectorAll('.delete-service-form');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Confirm deletion
                if (!confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
                    return;
                }
                
                // Show loading state
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonHTML = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="spinner-icon"></span> Deleting...';
                submitButton.disabled = true;
                
                // Get form data
                const formData = new FormData(this);
                formData.append('action', 'mobooking_delete_service_ajax');
                
                // Submit delete request via fetch
                fetch(mobooking_data.ajax_url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    submitButton.innerHTML = originalButtonHTML;
                    submitButton.disabled = false;
                    
                    if (data.success) {
                        // Show success message
                        showNotification(data.data.message, 'success');
                        
                        // Remove the service card from the UI with animation
                        const serviceCard = this.closest('.service-card');
                        if (serviceCard) {
                            serviceCard.style.opacity = '0';
                            serviceCard.style.transform = 'translateY(-10px)';
                            setTimeout(() => {
                                serviceCard.remove();
                                
                                // Check if there are no services left
                                const servicesGrid = document.querySelector('.services-grid');
                                if (servicesGrid && servicesGrid.children.length === 0) {
                                    const noItemsHTML = `
                                        <div class="no-items">
                                            <span class="dashicons dashicons-admin-tools"></span>
                                            <p>You haven't created any services yet.</p>
                                            <p>Add your first service to start receiving bookings.</p>
                                            <a href="?view=add" class="button button-primary">Add Your First Service</a>
                                        </div>
                                    `;
                                    servicesGrid.innerHTML = noItemsHTML;
                                }
                            }, 300);
                        }
                    } else {
                        // Show error message
                        showNotification(data.data.message || 'Error deleting service', 'error');
                    }
                })
                .catch(error => {
                    // Reset button state
                    submitButton.innerHTML = originalButtonHTML;
                    submitButton.disabled = false;
                    
                    // Show error message
                    console.error('Error deleting service:', error);
                    showNotification('An error occurred while deleting the service. Please try again.', 'error');
                });
            });
        });
    }
    
    // Options management - click on an option to edit
    const optionItems = document.querySelectorAll('.option-item');
    if (optionItems.length > 0) {
        optionItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't trigger if clicking on drag handle
                if (e.target.classList.contains('option-drag-handle') || 
                    e.target.closest('.option-drag-handle')) {
                    return;
                }
                
                const optionId = this.getAttribute('data-id');
                
                // Highlight selected option
                optionItems.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                // Load option data via AJAX
                loadOptionForm(optionId);
            });
        });
    }
    
    // Add new option button
    const addOptionButtons = document.querySelectorAll('.add-option-button');
    if (addOptionButtons.length > 0) {
        addOptionButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Deselect any selected option
                const optionItems = document.querySelectorAll('.option-item');
                optionItems.forEach(item => item.classList.remove('active'));
                
                // Show new option form
                loadOptionForm(0);
            });
        });
    }
    
    // Filter options with search
    const optionsSearch = document.getElementById('options-search');
    if (optionsSearch) {
        optionsSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const optionItems = document.querySelectorAll('.option-item');
            
            if (!searchTerm) {
                optionItems.forEach(item => item.style.display = 'flex');
                return;
            }
            
            optionItems.forEach(item => {
                const optionName = item.querySelector('.option-name').textContent.toLowerCase();
                const optionType = item.querySelector('.option-type').textContent.toLowerCase();
                
                if (optionName.includes(searchTerm) || optionType.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Auto-hide notifications after 5 seconds
    const notifications = document.querySelectorAll('.notification');
    if (notifications.length > 0) {
        setTimeout(function() {
            notifications.forEach(notification => {
                notification.style.display = 'none';
            });
        }, 5000);
    }
    
    // Notification function
    window.showNotification = function(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('mobooking-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'mobooking-notification';
            document.body.appendChild(notification);
        }
        
        // Set notification classes and content
        notification.className = 'notification-' + type;
        notification.innerHTML = message;
        notification.style.display = 'block';
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.style.display = 'none';
                notification.style.opacity = '1';
            }, 300);
        }, 3000);
    };
    
    // Helper function to load option form via AJAX
    function loadOptionForm(optionId) {
        const isNew = optionId === 0;
        const templateHtml = document.getElementById('option-form-template').innerHTML;
        const optionFormContainer = document.querySelector('.option-form-container');
        const noOptionSelected = document.querySelector('.no-option-selected');
        
        if (isNew) {
            // Create new option form
            let formHtml = templateHtml
                .replace('{id}', '')
                .replace('{title}', 'Add New Option')
                .replace('{name}', '')
                .replace('{description}', '')
                .replace(/{type_selected_[^}]+}/g, '')
                .replace('{type_selected_checkbox}', 'selected')
                .replace('{required_selected_0}', 'selected')
                .replace('{required_selected_1}', '')
                .replace(/{price_type_selected_[^}]+}/g, '')
                .replace('{price_type_selected_fixed}', 'selected')
                .replace('{price_impact}', '0')
                .replace('{delete_button_visibility}', 'style="display: none;"');
            
            if (optionFormContainer) optionFormContainer.innerHTML = formHtml;
            if (optionFormContainer) optionFormContainer.style.display = 'block';
            if (noOptionSelected) noOptionSelected.style.display = 'none';
            
            // Initialize dynamic fields for default type
            initOptionFormHandlers();
            generateDynamicFields('checkbox');
            updatePriceFields('fixed');
        } else {
            // Show loading state
            if (optionFormContainer) {
                optionFormContainer.innerHTML = '<div class="loading-spinner"></div>';
                optionFormContainer.style.display = 'block';
            }
            if (noOptionSelected) noOptionSelected.style.display = 'none';
            
            // Load option data via AJAX
            const formData = new FormData();
            formData.append('action', 'mobooking_get_service_option');
            formData.append('id', optionId);
            formData.append('nonce', mobooking_data.nonce);
            
            fetch(mobooking_data.ajax_url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.option) {
                    const option = data.data.option;
                    
                    let formHtml = templateHtml
                        .replace('{id}', option.id)
                        .replace('{title}', 'Edit Option')
                        .replace('{name}', option.name)
                        .replace('{description}', option.description || '')
                        .replace(/{type_selected_[^}]+}/g, '')
                        .replace('{type_selected_' + option.type + '}', 'selected')
                        .replace('{required_selected_0}', option.is_required == 0 ? 'selected' : '')
                        .replace('{required_selected_1}', option.is_required == 1 ? 'selected' : '')
                        .replace(/{price_type_selected_[^}]+}/g, '')
                        .replace('{price_type_selected_' + (option.price_type || 'fixed') + '}', 'selected')
                        .replace('{price_impact}', option.price_impact || '0')
                        .replace('{delete_button_visibility}', '');
                    
                    if (optionFormContainer) optionFormContainer.innerHTML = formHtml;
                    
                    // Initialize form handlers
                    initOptionFormHandlers();
                    
                    // Initialize dynamic fields based on option type
                    generateDynamicFields(option.type, option);
                    updatePriceFields(option.price_type || 'fixed');
                } else {
                    if (optionFormContainer) {
                        optionFormContainer.innerHTML = '<div class="error-message">Error loading option data</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading option:', error);
                if (optionFormContainer) {
                    optionFormContainer.innerHTML = '<div class="error-message">Error loading option data</div>';
                }
            });
        }
    }
    
    // Initialize event handlers for option form
    // Initialize event handlers for option form
    function initOptionFormHandlers() {
        // Option type change
        const optionType = document.getElementById('option-type');
        if (optionType) {
            optionType.addEventListener('change', function() {
                generateDynamicFields(this.value);
            });
        }
        
        // Price type change
        const priceType = document.getElementById('option-price-type');
        if (priceType) {
            priceType.addEventListener('change', function() {
                updatePriceFields(this.value);
            });
        }
        
        // Cancel button
        const cancelButton = document.querySelector('.cancel-option');
        if (cancelButton) {
            cancelButton.addEventListener('click', function() {
                const optionFormContainer = document.querySelector('.option-form-container');
                const noOptionSelected = document.querySelector('.no-option-selected');
                const optionItems = document.querySelectorAll('.option-item');
                
                if (optionFormContainer) optionFormContainer.style.display = 'none';
                if (noOptionSelected) noOptionSelected.style.display = 'flex';
                optionItems.forEach(item => item.classList.remove('active'));
            });
        }
        
        // Delete button
        const deleteButton = document.querySelector('.delete-option');
        if (deleteButton) {
            deleteButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this option? This action cannot be undone.')) {
                    const optionForm = document.getElementById('option-form');
                    if (optionForm) {
                        const formData = new FormData(optionForm);
                        formData.append('action', 'mobooking_delete_option_ajax');
                        
                        // Show loading state
                        this.innerHTML = '<span class="spinner-icon"></span> Deleting...';
                        this.disabled = true;
                        
                        fetch(mobooking_data.ajax_url, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Server returned ' + response.status + ' ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                showNotification(data.data.message, 'success');
                                
                                // Reload the options list
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                // Reset button state
                                this.innerHTML = '<span class="dashicons dashicons-trash"></span> Delete';
                                this.disabled = false;
                                
                                showNotification(data.data?.message || 'Error deleting option', 'error');
                            }
                        })
                        .catch(error => {
                            // Reset button state
                            this.innerHTML = '<span class="dashicons dashicons-trash"></span> Delete';
                            this.disabled = false;
                            
                            console.error('Error deleting option:', error);
                            showNotification('An error occurred while deleting the option', 'error');
                        });
                    }
                }
            });
        }
        
        // Save button
        const saveOptionButton = document.querySelector('.save-option');
        if (saveOptionButton) {
            saveOptionButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                const optionForm = document.getElementById('option-form');
                if (optionForm) {
                    // Basic validation
                    const nameField = optionForm.querySelector('input[name="name"]');
                    const typeField = optionForm.querySelector('select[name="type"]');
                    
                    if (!nameField || !nameField.value.trim()) {
                        showNotification('Option name is required', 'error');
                        if (nameField) nameField.focus();
                        return;
                    }
                    
                    if (!typeField || !typeField.value) {
                        showNotification('Option type is required', 'error');
                        if (typeField) typeField.focus();
                        return;
                    }
                    
                    // Special validation for select/radio - must have at least one choice
                    if ((typeField.value === 'select' || typeField.value === 'radio') && optionForm.querySelectorAll('.choice-row').length === 0) {
                        showNotification('At least one choice is required for ' + typeField.value + ' options', 'error');
                        return;
                    }
                    
                    // Show loading state
                    this.innerHTML = '<span class="spinner-icon"></span> Saving...';
                    this.disabled = true;
                    
                    // Submit form
                    const formData = new FormData(optionForm);
                    formData.append('action', 'mobooking_save_option_ajax');
                    
                    fetch(mobooking_data.ajax_url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Server returned ' + response.status + ' ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Reset button state
                        this.innerHTML = 'Save Option';
                        this.disabled = false;
                        
                        if (data.success) {
                            showNotification(data.data.message, 'success');
                            
                            // Reload the page to reflect changes
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification(data.data?.message || 'Error saving option', 'error');
                        }
                    })
                    .catch(error => {
                        // Reset button state
                        this.innerHTML = 'Save Option';
                        this.disabled = false;
                        
                        console.error('Error saving option:', error);
                        showNotification('An error occurred while saving the option', 'error');
                    });
                }
            });
        }
    }
    
    // Helper function to generate dynamic fields based on option type
    function generateDynamicFields(optionType, optionData) {
        const dynamicFields = document.getElementById('dynamic-fields');
        if (!dynamicFields) return;
        
        dynamicFields.innerHTML = '';
        optionData = optionData || {};
        
        switch (optionType) {
            case 'checkbox':
                dynamicFields.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="default_value">Default Value</label>
                            <select id="default_value" name="default_value">
                                <option value="0" ${optionData.default_value == 1 ? '' : 'selected'}>Unchecked</option>
                                <option value="1" ${optionData.default_value == 1 ? 'selected' : ''}>Checked</option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="option_label">Option Label</label>
                            <input type="text" id="option_label" name="option_label" value="${optionData.option_label || ''}" placeholder="Check this box to add...">
                        </div>
                    </div>
                `;
                break;
                
            case 'number':
                dynamicFields.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="min_value">Minimum Value</label>
                            <input type="number" id="min_value" name="min_value" value="${optionData.min_value !== null && optionData.min_value !== undefined ? optionData.min_value : '0'}">
                        </div>
                        <div class="form-group half">
                            <label for="max_value">Maximum Value</label>
                            <input type="number" id="max_value" name="max_value" value="${optionData.max_value !== null && optionData.max_value !== undefined ? optionData.max_value : ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="default_value">Default Value</label>
                            <input type="number" id="default_value" name="default_value" value="${optionData.default_value || ''}">
                        </div>
                        <div class="form-group half">
                            <label for="placeholder">Placeholder</label>
                            <input type="text" id="placeholder" name="placeholder" value="${optionData.placeholder || ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="step">Step</label>
                            <input type="number" id="step" name="step" value="${optionData.step || '1'}" step="0.01">
                        </div>
                        <div class="form-group half">
                            <label for="unit">Unit Label</label>
                            <input type="text" id="unit" name="unit" value="${optionData.unit || ''}" placeholder="sq ft, hours, etc.">
                        </div>
                    </div>
                `;
                break;
                
            case 'select':
            case 'radio':
                // Parse existing options
                let choicesArray = [];
                if (optionData.options) {
                    choicesArray = parseOptionsString(optionData.options);
                }
                
                let choicesHTML = `
                    <div class="form-group">
                        <label>Choices</label>
                        <div class="choices-container">
                            <div class="choices-header">
                                <div class="choice-value">Value</div>
                                <div class="choice-label">Label</div>
                                <div class="choice-price">Price Impact</div>
                                <div class="choice-actions"></div>
                            </div>
                            <div class="choices-list">
                `;
                
                // Add choices
                if (choicesArray.length === 0) {
                    choicesHTML += `
                        <div class="choice-row">
                            <div class="choice-drag-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </div>
                            <div class="choice-value">
                                <input type="text" name="choice_value[]" class="choice-value-input" placeholder="value">
                            </div>
                            <div class="choice-label">
                                <input type="text" name="choice_label[]" class="choice-label-input" placeholder="Display Label">
                            </div>
                            <div class="choice-price">
                                <input type="number" name="choice_price[]" class="choice-price-input" step="0.01" placeholder="0.00">
                            </div>
                            <div class="choice-actions">
                                <button type="button" class="button-link remove-choice">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    choicesArray.forEach(choice => {
                        choicesHTML += `
                            <div class="choice-row">
                                <div class="choice-drag-handle">
                                    <span class="dashicons dashicons-menu"></span>
                                </div>
                                <div class="choice-value">
                                    <input type="text" name="choice_value[]" class="choice-value-input" value="${choice.value}" placeholder="value">
                                </div>
                                <div class="choice-label">
                                    <input type="text" name="choice_label[]" class="choice-label-input" value="${choice.label}" placeholder="Display Label">
                                </div>
                                <div class="choice-price">
                                    <input type="number" name="choice_price[]" class="choice-price-input" value="${choice.price}" step="0.01" placeholder="0.00">
                                </div>
                                <div class="choice-actions">
                                    <button type="button" class="button-link remove-choice">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                
                choicesHTML += `
                            </div>
                            <div class="add-choice-container">
                                <button type="button" class="button add-choice">Add Choice</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="default_value">Default Value</label>
                        <input type="text" id="default_value" name="default_value" value="${optionData.default_value || ''}">
                        <p class="field-hint">Enter the value (not the label) of the default choice</p>
                    </div>
                `;
                
                dynamicFields.innerHTML = choicesHTML;
                
                // Add event listeners for choice buttons
                initializeChoiceControls();
                break;
                
            case 'text':
                dynamicFields.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="default_value">Default Value</label>
                            <input type="text" id="default_value" name="default_value" value="${optionData.default_value || ''}">
                        </div>
                        <div class="form-group half">
                            <label for="placeholder">Placeholder</label>
                            <input type="text" id="placeholder" name="placeholder" value="${optionData.placeholder || ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="min_length">Minimum Length</label>
                            <input type="number" id="min_length" name="min_length" value="${optionData.min_length !== null && optionData.min_length !== undefined ? optionData.min_length : ''}" min="0">
                        </div>
                        <div class="form-group half">
                            <label for="max_length">Maximum Length</label>
                            <input type="number" id="max_length" name="max_length" value="${optionData.max_length !== null && optionData.max_length !== undefined ? optionData.max_length : ''}" min="0">
                        </div>
                    </div>
                `;
                break;
                
            case 'textarea':
                dynamicFields.innerHTML = `
                    <div class="form-group">
                        <label for="default_value">Default Value</label>
                        <textarea id="default_value" name="default_value" rows="2">${optionData.default_value || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="placeholder">Placeholder</label>
                        <input type="text" id="placeholder" name="placeholder" value="${optionData.placeholder || ''}">
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="rows">Rows</label>
                            <input type="number" id="rows" name="rows" value="${optionData.rows || '3'}" min="2">
                        </div>
                        <div class="form-group half">
                            <label for="max_length">Maximum Length</label>
                            <input type="number" id="max_length" name="max_length" value="${optionData.max_length !== null && optionData.max_length !== undefined ? optionData.max_length : ''}" min="0">
                        </div>
                    </div>
                `;
                break;
                
            case 'quantity':
                dynamicFields.innerHTML = `
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="min_value">Minimum Quantity</label>
                            <input type="number" id="min_value" name="min_value" value="${optionData.min_value !== null && optionData.min_value !== undefined ? optionData.min_value : '0'}" min="0">
                        </div>
                        <div class="form-group half">
                            <label for="max_value">Maximum Quantity</label>
                            <input type="number" id="max_value" name="max_value" value="${optionData.max_value !== null && optionData.max_value !== undefined ? optionData.max_value : ''}" min="0">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="default_value">Default Quantity</label>
                            <input type="number" id="default_value" name="default_value" value="${optionData.default_value || '0'}" min="0">
                        </div>
                        <div class="form-group half">
                            <label for="step">Step</label>
                            <input type="number" id="step" name="step" value="${optionData.step || '1'}" min="1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="unit">Unit Label</label>
                            <input type="text" id="unit" name="unit" value="${optionData.unit || ''}" placeholder="items, people, etc.">
                        </div>
                    </div>
                `;
                break;
        }
    }
    
    // Initialize event handlers for choice controls
    function initializeChoiceControls() {
        // Add choice button
        const addChoiceButton = document.querySelector('.add-choice');
        if (addChoiceButton) {
            addChoiceButton.addEventListener('click', function() {
                const choicesList = document.querySelector('.choices-list');
                if (choicesList) {
                    const newRow = document.createElement('div');
                    newRow.className = 'choice-row';
                    newRow.innerHTML = `
                        <div class="choice-drag-handle">
                            <span class="dashicons dashicons-menu"></span>
                        </div>
                        <div class="choice-value">
                            <input type="text" name="choice_value[]" class="choice-value-input" placeholder="value">
                        </div>
                        <div class="choice-label">
                            <input type="text" name="choice_label[]" class="choice-label-input" placeholder="Display Label">
                        </div>
                        <div class="choice-price">
                            <input type="number" name="choice_price[]" class="choice-price-input" step="0.01" placeholder="0.00">
                        </div>
                        <div class="choice-actions">
                            <button type="button" class="button-link remove-choice">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    `;
                    choicesList.appendChild(newRow);
                    
                    // Focus new input
                    setTimeout(() => {
                        const newInput = newRow.querySelector('.choice-value-input');
                        if (newInput) newInput.focus();
                    }, 10);
                    
                    // Add event listener to the new remove button
                    const removeButton = newRow.querySelector('.remove-choice');
                    if (removeButton) {
                        removeButton.addEventListener('click', handleRemoveChoice);
                    }
                }
            });
        }
        
        // Remove choice buttons
        const removeButtons = document.querySelectorAll('.remove-choice');
        if (removeButtons.length > 0) {
            removeButtons.forEach(button => {
                button.addEventListener('click', handleRemoveChoice);
            });
        }
    }
    
    // Handler for remove choice button
    function handleRemoveChoice() {
        const choiceRow = this.closest('.choice-row');
        const choicesList = this.closest('.choices-list');
        
        if (choicesList && choicesList.children.length <= 1) {
            showNotification('You must have at least one choice', 'warning');
            return;
        }
        
        if (choiceRow) {
            choiceRow.remove();
        }
    }
    
    // Update price fields based on selected price type
    function updatePriceFields(priceType) {
        const valueField = document.querySelector('.price-impact-value');
        if (!valueField) return;
        
        valueField.style.display = 'block';
        const label = valueField.querySelector('label');
        const input = valueField.querySelector('input');
        
        if (priceType === 'custom') {
            if (label) label.textContent = 'Formula';
            if (input) {
                input.setAttribute('type', 'text');
                input.setAttribute('placeholder', 'price + (value * 5)');
            }
        } else if (priceType === 'none') {
            valueField.style.display = 'none';
        } else if (priceType === 'percentage') {
            if (label) label.textContent = 'Percentage (%)';
            if (input) {
                input.setAttribute('type', 'number');
                input.setAttribute('placeholder', '10');
            }
        } else if (priceType === 'multiply') {
            if (label) label.textContent = 'Multiplier';
            if (input) {
                input.setAttribute('type', 'number');
                input.setAttribute('step', '0.1');
                input.setAttribute('placeholder', '1.5');
            }
        } else {
            if (label) label.textContent = 'Amount ($)';
            if (input) {
                input.setAttribute('type', 'number');
                input.setAttribute('step', '0.01');
                input.setAttribute('placeholder', '9.99');
            }
        }
    }
    
    // Helper function to parse options string to array of objects
    function parseOptionsString(optionsString) {
        const options = [];
        if (!optionsString) return options;
        
        const lines = optionsString.split("\n");
        
        lines.forEach((line) => {
            if (!line.trim()) return;
            
            const parts = line.split("|");
            const value = parts[0]?.trim() || "";
            const labelPriceParts = parts[1]?.split(":") || [""];
            
            const label = labelPriceParts[0]?.trim() || "";
            const price = parseFloat(labelPriceParts[1] || 0) || 0;
            
            if (value) {
                options.push({
                    value,
                    label,
                    price
                });
            }
        });
        
        return options;
    }
});
</script>
<?php
// End output buffering and flush
ob_end_flush();
?>

<style>
/* Modern, compact styling for services section */
:root {
    --primary-color: #2863ec;
    --primary-dark: #1f4fbc;
    --primary-light: #e6f0ff;
    --danger-color: #e53935;
    --success-color: #43a047;
    --warning-color: #fb8c00;
    --info-color: #1e88e5;
    --text-color: #020817;
    --text-light: #64748b;
    --border-color: #e3e8f0;
    --bg-color: #f4f6f8;
    --card-bg: #ffffff;
    --shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    --radius: 10px;
    --transition: all 0.2s ease-in-out;
}

/* General Layout */
.services-section {
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

/* Service Cards */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.service-card {
    background-color: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 1px solid var(--border-color);
}

.service-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color);
}

.service-header {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background-color: #f9fafc;
}

.service-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    margin-right: 0.75rem;
}

.service-icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.service-image {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    margin-right: 0.75rem;
    background-size: cover;
    background-position: center;
}

.service-title {
    flex: 1;
}

.service-title h3 {
    margin: 0 0 0.25rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-color);
}

.category-badge {
    display: inline-block;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 20px;
    margin-top: 2px;
    background-color: rgba(40, 99, 236, 0.1);
    color: var(--primary-color);
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
    color: #f57c00;
}

.service-status-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-left: 0.5rem;
}

.service-price {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.service-status {
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 20px;
}

.service-status-active {
    background-color: rgba(76, 175, 80, 0.15);
    color: #43a047;
}

.service-status-inactive {
    background-color: rgba(158, 158, 158, 0.15);
    color: #9e9e9e;
}

.service-body {
    padding: 1rem;
    flex-grow: 1;
}

.service-meta {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    color: var(--text-light);
    font-size: 0.8rem;
}

.service-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 0.25rem;
}

.service-duration {
    margin-right: 0.75rem;
    display: flex;
    align-items: center;
}

.service-options-badge {
    display: flex;
    align-items: center;
    padding: 2px 6px;
    border-radius: 20px;
    background-color: rgba(33, 150, 243, 0.15);
    color: #1e88e5;
    font-size: 0.7rem;
}

.service-options-badge .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
    margin-right: 0.25rem;
}

.service-description {
    color: var(--text-color);
    font-size: 0.85rem;
    line-height: 1.4;
    max-height: 60px;
    overflow: hidden;
}

.service-actions {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
}

.service-actions .button {
    padding: 0.4rem 0.75rem;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    flex: 1;
    justify-content: center;
}

.service-actions .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 0.25rem;
}

.delete-service-form {
    flex: 1;
}

.button-danger {
    background-color: #e53935;
}

.button-danger:hover {
    background-color: #d32f2f;
}

/* Form Styling */
.service-form-wrapper {
    background-color: var(--bg-color);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.service-tabs {
    display: flex;
    flex-direction: column;
}

.tab-buttons {
    display: flex;
    background-color: white;
    border-radius: var(--radius) var(--radius) 0 0;
    border-bottom: 1px solid var(--border-color);
}

.tab-button {
    padding: 1rem 1.25rem;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-light);
    display: flex;
    align-items: center;
    transition: var(--transition);
    position: relative;
}

.tab-button .dashicons {
    margin-right: 0.5rem;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.tab-button:hover {
    color: var(--primary-color);
    background-color: var(--primary-light);
}

.tab-button.active {
    color: var(--primary-color);
    background-color: var(--primary-light);
}

.tab-button.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--primary-color);
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    font-size: 0.7rem;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    margin-left: 0.5rem;
    padding: 0 0.25rem;
}

.tab-content {
    padding: 1.5rem;
    background-color: var(--bg-color);
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.service-form-card {
    background-color: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
}

.form-grid {
    display: flex;
    gap: 1.5rem;
}

.form-column {
    flex: 1;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.form-group.half {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
    font-size: 0.9rem;
}

.required {
    color: var(--danger-color);
}

input[type="text"],
input[type="number"],
input[type="email"],
select,
textarea {
    width: 100%;
    padding: 0.75rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 0.9rem;
    transition: var(--transition);
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(40, 99, 236, 0.15);
}

.input-prefix,
.input-suffix {
    position: relative;
}

.prefix {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    pointer-events: none;
}

.input-prefix input {
    padding-left: 1.5rem;
}

.suffix {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    pointer-events: none;
}

.input-suffix input {
    padding-right: 2.5rem;
}

/* Icon Selector */
.icon-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.icon-preview {
    width: 44px;
    height: 44px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-preview .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.icon-placeholder {
    width: 24px;
    height: 24px;
    border: 2px dashed rgba(255, 255, 255, 0.5);
    border-radius: 4px;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(44px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.icon-item {
    width: 44px;
    height: 44px;
    background-color: var(--primary-light);
    color: var(--primary-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    border: 1px solid transparent;
}

.icon-item:hover {
    background-color: var(--primary-color);
    color: white;
}

.icon-item .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

/* Image upload */
.image-upload-container {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.image-upload-container input {
    flex: 1;
}

.image-preview {
    position: relative;
    min-height: 120px;
    background-color: #f9fafc;
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
}

.image-preview img {
    max-width: 100%;
    max-height: 200px;
    object-fit: contain;
}

.no-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    gap: 0.5rem;
}

.no-image-placeholder .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    opacity: 0.5;
}

/* Options Tab */
.options-header-card {
    background-color: white;
    border-radius: var(--radius);
    padding: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    box-shadow: var(--shadow);
}

.options-info h3 {
    margin: 0 0 0.5rem;
    font-size: 1.1rem;
    color: var(--text-color);
}

.options-info p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.9rem;
}

.add-option-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 1rem;
    font-size: 0.85rem;
}

.add-option-button .dashicons {
    margin-right: 0.5rem;
}

.options-container {
    display: flex;
    background-color: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    min-height: 400px;
}

.options-sidebar {
    width: 280px;
    border-right: 1px solid var(--border-color);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.options-search-box {
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}

.options-search-box input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    font-size: 0.85rem;
}

.options-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
}

.options-list-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--text-light);
    font-size: 0.9rem;
}

.option-item {
    background-color: #f9fafc;
    border-radius: var(--radius);
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.option-item:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
}

.option-item.active {
    border-color: var(--primary-color);
    background-color: var(--primary-light);
    box-shadow: 0 0 0 1px var(--primary-color);
}

.option-drag-handle {
    width: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
    background-color: rgba(0, 0, 0, 0.02);
    color: var(--text-light);
    border-right: 1px solid var(--border-color);
}

.option-drag-handle:active {
    cursor: grabbing;
}

.option-content {
    flex: 1;
    padding: 0.75rem;
    min-width: 0;
}

.option-name {
    font-weight: 500;
    display: block;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.option-meta {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--text-light);
    align-items: center;
}

.option-type {
    white-space: nowrap;
}

.option-required {
    background-color: rgba(33, 150, 243, 0.15);
    color: #1e88e5;
    padding: 0 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
}

.option-preview {
    width: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background-color: rgba(0, 0, 0, 0.02);
    border-left: 1px solid var(--border-color);
}

/* Option previews */
.preview-checkbox,
.preview-select,
.preview-radio,
.preview-number,
.preview-text,
.preview-textarea {
    font-size: 0.75rem;
    color: var(--text-light);
    text-align: center;
}

.preview-checkbox input {
    pointer-events: none;
}

.radio-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 1px solid var(--text-light);
    position: relative;
}

.radio-dot:after {
    content: '';
    position: absolute;
    width: 4px;
    height: 4px;
    background-color: var(--text-light);
    border-radius: 50%;
    top: 2px;
    left: 2px;
}

.price-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    font-size: 0.7rem;
    padding: 2px 0;
    text-align: center;
    background-color: rgba(0, 0, 0, 0.05);
}

.options-content {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
    position: relative;
}

.no-option-selected {
    display: flex;
    flex-direction: column;
    height: 100%;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
    text-align: center;
    padding: 2rem;
}

.no-option-selected .dashicons {
    font-size: 3rem;
    opacity: 0.2;
    margin-bottom: 1rem;
}

/* Option Form */
.option-form-container {
    height: 100%;
}

#option-form {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.option-form-title {
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
    color: var(--text-color);
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.option-form-grid {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.option-form-actions {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.spacer {
    flex-grow: 1;
}

/* Choices Container for select/radio options */
.choices-container {
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 1rem;
}

.choices-header {
    display: flex;
    background-color: #f9fafc;
    border-bottom: 1px solid var(--border-color);
    padding: 0.5rem;
    font-weight: 500;
    font-size: 0.75rem;
    color: var(--text-light);
}

.choices-list {
    max-height: 200px;
    overflow-y: auto;
}

.choice-row {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    align-items: center;
    background-color: white;
    transition: var(--transition);
}

.choice-row:last-child {
    border-bottom: none;
}

.choice-drag-handle {
    width: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
    padding: 0 0.25rem;
    color: var(--text-light);
}

.choice-drag-handle:active {
    cursor: grabbing;
}

.choice-value {
    flex: 1;
    padding: 0.25rem;
}

.choice-label {
    flex: 2;
    padding: 0.25rem;
}

.choice-price {
    width: 80px;
    padding: 0.25rem;
}

.choice-actions {
    width: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.choice-row input {
    border: 1px solid transparent;
    background-color: transparent;
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}

.choice-row input:focus {
    border-color: var(--primary-color);
    background-color: white;
}

.add-choice-container {
    padding: 0.5rem;
    background-color: #f9fafc;
    border-top: 1px solid var(--border-color);
}

.add-choice {
    background-color: transparent;
    border: 1px dashed var(--border-color);
    color: var(--text-light);
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
    width: 100%;
    box-shadow: none;
}

.add-choice:hover {
    background-color: var(--primary-light);
    color: var(--primary-color);
    border-color: var(--primary-color);
}

.remove-choice {
    color: var(--text-light);
    opacity: 0.5;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: var(--transition);
}

.remove-choice:hover {
    color: var(--danger-color);
    opacity: 1;
}

.button-link {
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    cursor: pointer;
    box-shadow: none;
}

/* Sortable placeholders */
.option-item-placeholder {
    height: 50px;
    background-color: rgba(40, 99, 236, 0.05);
    border: 1px dashed var(--primary-color);
    border-radius: var(--radius);
    margin-bottom: 0.5rem;
}

.choice-row-placeholder {
    height: 38px;
    background-color: rgba(40, 99, 236, 0.05);
    border: 1px dashed var(--primary-color);
}

/* Form actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem;
    background-color: white;
    border-radius: 0 0 var(--radius) var(--radius);
    border-top: 1px solid var(--border-color);
}

/* Notifications */
.notification {
    background-color: white;
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: var(--shadow);
    border-left: 4px solid;
}

.notification-success {
    border-color: var(--success-color);
    background-color: rgba(76, 175, 80, 0.05);
}

.notification-error {
    border-color: var(--danger-color);
    background-color: rgba(244, 67, 54, 0.05);
}

.notification-info {
    border-color: var(--info-color);
    background-color: rgba(33, 150, 243, 0.05);
}

.notification-warning {
    border-color: var(--warning-color);
    background-color: rgba(255, 152, 0, 0.05);
}

.notification ul {
    margin-top: 0.5rem;
    margin-bottom: 0;
    padding-left: 1.5rem;
}

/* Loading spinner */
.loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 3px solid rgba(40, 99, 236, 0.2);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    animation: spin 1s linear infinite;
    position: absolute;
    top: 50%;
    left: 50%;
    margin: -20px 0 0 -20px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Notification popup */
#mobooking-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background-color: var(--primary-color);
    color: white;
    padding: 1rem 1.25rem;
    border-radius: var(--radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    max-width: 300px;
    animation: slideIn 0.3s ease-out;
    display: none;
}

#mobooking-notification.notification-success {
    background-color: var(--success-color);
}

#mobooking-notification.notification-error {
    background-color: var(--danger-color);
}

#mobooking-notification.notification-warning {
    background-color: var(--warning-color);
}

@keyframes slideIn {
    from {
        transform: translateX(30px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .form-grid {
        flex-direction: column;
        gap: 0;
    }
    
    .options-container {
        flex-direction: column;
        min-height: 600px;
    }
    
    .options-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
        max-height: 300px;
    }
    
    .option-form-grid {
        flex-direction: column;
        gap: 0;
    }
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .top-actions {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .filter-controls {
        width: 100%;
    }
    
    .tab-buttons {
        overflow-x: auto;
        white-space: nowrap;
    }
    
    .service-actions {
        flex-direction: column;
    }
    
    .service-actions .button,
    .delete-service-form {
        width: 100%;
    }
}

    </style>