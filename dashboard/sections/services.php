<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current view and service ID
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
$service_id = isset($_GET['service_id']) ? absint($_GET['service_id']) : 0;
$active_tab = isset($_GET['active_tab']) ? sanitize_text_field($_GET['active_tab']) : 'basic-info';

// Initialize managers
$service_manager = new \MoBooking\Services\ServicesManager();
$options_manager = new \MoBooking\Services\ServiceOptionsManager();

// Handle service editing
$service_data = null;
if ($current_view === 'edit' && $service_id) {
    $service_data = $service_manager->get_service($service_id, $user_id);
    if (!$service_data) {
        $current_view = 'list';
    }
}

// Get user's services for list view
$services = array();
$categories = array();
if ($current_view === 'list') {
    $services = $service_manager->get_user_services($user_id);
    $categories = $service_manager->get_user_categories($user_id);
}

// Available icons for services
$available_icons = array(
    'dashicons-admin-home' => 'Home',
    'dashicons-building' => 'Building',
    'dashicons-admin-tools' => 'Tools',
    'dashicons-hammer' => 'Hammer',
    'dashicons-admin-appearance' => 'Brush',
    'dashicons-car' => 'Car',
    'dashicons-products' => 'Products',
    'dashicons-money-alt' => 'Money',
    'dashicons-chart-line' => 'Chart',
    'dashicons-calendar-alt' => 'Calendar',
    'dashicons-clock' => 'Clock',
    'dashicons-location-alt' => 'Location',
    'dashicons-email-alt' => 'Email',
    'dashicons-phone' => 'Phone',
    'dashicons-star-filled' => 'Star',
    'dashicons-heart' => 'Heart'
);
?>

<div class="dashboard-section services-section">
    <?php if ($current_view === 'list') : ?>
        <!-- Services List View -->
        <div class="section-header">
            <div class="section-title-group">
                <h2 class="section-title"><?php _e('Services', 'mobooking'); ?></h2>
                <p class="section-description"><?php _e('Manage your services and customize options for each one.', 'mobooking'); ?></p>
            </div>
            
            <div class="section-actions">
                <div class="filter-controls">
                    <label for="category-filter" class="sr-only"><?php _e('Filter by category', 'mobooking'); ?></label>
                    <select id="category-filter" class="form-control">
                        <option value=""><?php _e('All Categories', 'mobooking'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="button button-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Add Service', 'mobooking'); ?>
                </a>
            </div>
        </div>
        
        <?php if (empty($services)) : ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <h3><?php _e('No services yet', 'mobooking'); ?></h3>
                <p><?php _e('Create your first service to start accepting bookings from customers.', 'mobooking'); ?></p>
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="button button-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Create Your First Service', 'mobooking'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="services-grid">
                <?php foreach ($services as $service) : 
                    $options_count = count($options_manager->get_service_options($service->id));
                ?>
                    <div class="service-card" data-category="<?php echo esc_attr($service->category); ?>">
                        <div class="service-card-header">
                            <div class="service-visual">
                                <?php if (!empty($service->image_url)) : ?>
                                    <div class="service-image" style="background-image: url('<?php echo esc_url($service->image_url); ?>')"></div>
                                <?php elseif (!empty($service->icon)) : ?>
                                    <div class="service-icon">
                                        <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                    </div>
                                <?php else : ?>
                                    <div class="service-icon default">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-content">
                                <div class="service-header">
                                    <h3 class="service-name"><?php echo esc_html($service->name); ?></h3>
                                    <div class="service-badges">
                                        <?php if (!empty($service->category)) : ?>
                                            <span class="service-category-badge category-<?php echo esc_attr($service->category); ?>">
                                                <?php echo esc_html(ucfirst($service->category)); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="service-status-badge status-<?php echo esc_attr($service->status); ?>">
                                            <?php echo esc_html(ucfirst($service->status)); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($service->description)) : ?>
                                    <p class="service-description">
                                        <?php echo wp_trim_words(esc_html($service->description), 15); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="service-meta">
                                    <div class="service-price">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                        <?php echo wc_price($service->price); ?>
                                    </div>
                                    
                                    <div class="service-duration">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        <?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?>
                                    </div>
                                    
                                    <?php if ($options_count > 0) : ?>
                                        <div class="service-options">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                            </svg>
                                            <?php echo sprintf(_n('%d option', '%d options', $options_count, 'mobooking'), $options_count); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-card-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service->id), home_url('/dashboard/services/'))); ?>" 
                               class="button button-secondary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"></path>
                                </svg>
                                <?php _e('Edit', 'mobooking'); ?>
                            </a>
                            
                            <button type="button" class="btn-icon delete-service-btn" data-id="<?php echo esc_attr($service->id); ?>" title="<?php _e('Delete Service', 'mobooking'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m3 6 3 18h12l3-18"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <!-- Service Edit/New View -->
        <div class="section-header">
            <div class="section-title-group">
                <h2 class="section-title">
                    <?php if ($current_view === 'edit') : ?>
                        <?php printf(__('Edit Service: %s', 'mobooking'), esc_html($service_data->name)); ?>
                    <?php else : ?>
                        <?php _e('Add New Service', 'mobooking'); ?>
                    <?php endif; ?>
                </h2>
                <p class="section-description">
                    <?php if ($current_view === 'edit') : ?>
                        <?php _e('Update your service details and manage customization options.', 'mobooking'); ?>
                    <?php else : ?>
                        <?php _e('Create a new service that customers can book with customizable options.', 'mobooking'); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="button button-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m12 19-7-7 7-7M19 12H5"/>
                </svg>
                <?php _e('Back to Services', 'mobooking'); ?>
            </a>
        </div>
        
        <div class="service-form-container">
            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button type="button" class="tab-button <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" data-tab="basic-info">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                    </svg>
                    <?php _e('Basic Info', 'mobooking'); ?>
                </button>
                <button type="button" class="tab-button <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" data-tab="presentation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <circle cx="9" cy="9" r="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                    <?php _e('Presentation', 'mobooking'); ?>
                </button>
                <button type="button" class="tab-button <?php echo $active_tab === 'options' ? 'active' : ''; ?>" data-tab="options">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <?php _e('Options', 'mobooking'); ?>
                    <?php if ($service_id) : ?>
                        <span class="options-count"><?php echo count($options_manager->get_service_options($service_id)); ?></span>
                    <?php endif; ?>
                </button>
            </div>
            
            <!-- Service Form -->
            <form id="service-form" method="post">
                <input type="hidden" id="service-id" name="id" value="<?php echo $service_data ? esc_attr($service_data->id) : ''; ?>">
                <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
                
                <!-- Basic Info Tab -->
                <div id="basic-info" class="tab-pane <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>">
                    <div class="form-section">
                        <h3 class="form-section-title"><?php _e('Service Details', 'mobooking'); ?></h3>
                        
                        <div class="form-group">
                            <label for="service-name"><?php _e('Service Name', 'mobooking'); ?> *</label>
                            <input type="text" id="service-name" name="name" class="form-control" 
                                   value="<?php echo $service_data ? esc_attr($service_data->name) : ''; ?>" 
                                   placeholder="<?php _e('e.g., Deep House Cleaning', 'mobooking'); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="service-price"><?php _e('Price', 'mobooking'); ?> *</label>
                                <div class="input-with-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <input type="number" id="service-price" name="price" class="form-control" 
                                           value="<?php echo $service_data ? esc_attr($service_data->price) : ''; ?>" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="service-duration"><?php _e('Duration (minutes)', 'mobooking'); ?> *</label>
                                <div class="input-with-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                    <input type="number" id="service-duration" name="duration" class="form-control" 
                                           value="<?php echo $service_data ? esc_attr($service_data->duration) : '60'; ?>" 
                                           min="15" step="15" placeholder="60" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="service-category"><?php _e('Category', 'mobooking'); ?></label>
                                <select id="service-category" name="category" class="form-control">
                                    <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                    <option value="residential" <?php selected($service_data ? $service_data->category : '', 'residential'); ?>><?php _e('Residential', 'mobooking'); ?></option>
                                    <option value="commercial" <?php selected($service_data ? $service_data->category : '', 'commercial'); ?>><?php _e('Commercial', 'mobooking'); ?></option>
                                    <option value="special" <?php selected($service_data ? $service_data->category : '', 'special'); ?>><?php _e('Special', 'mobooking'); ?></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="service-status"><?php _e('Status', 'mobooking'); ?></label>
                                <select id="service-status" name="status" class="form-control">
                                    <option value="active" <?php selected($service_data ? $service_data->status : 'active', 'active'); ?>><?php _e('Active', 'mobooking'); ?></option>
                                    <option value="inactive" <?php selected($service_data ? $service_data->status : '', 'inactive'); ?>><?php _e('Inactive', 'mobooking'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                            <textarea id="service-description" name="description" class="form-control" rows="4" 
                                      placeholder="<?php _e('Describe what this service includes...', 'mobooking'); ?>"><?php echo $service_data ? esc_textarea($service_data->description) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Presentation Tab -->
                <div id="presentation" class="tab-pane <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>">
                    <div class="form-section">
                        <h3 class="form-section-title"><?php _e('Visual Presentation', 'mobooking'); ?></h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="service-icon"><?php _e('Icon', 'mobooking'); ?></label>
                                <input type="hidden" id="service-icon" name="icon" value="<?php echo $service_data ? esc_attr($service_data->icon) : ''; ?>">
                                
                                <div class="icon-preview-container">
                                    <div class="icon-preview">
                                        <?php if ($service_data && !empty($service_data->icon)) : ?>
                                            <span class="dashicons <?php echo esc_attr($service_data->icon); ?>"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="icon-grid">
                                    <?php foreach ($available_icons as $icon_class => $icon_name) : ?>
                                        <div class="icon-item <?php echo ($service_data && $service_data->icon === $icon_class) ? 'selected' : ''; ?>" 
                                             data-icon="<?php echo esc_attr($icon_class); ?>" title="<?php echo esc_attr($icon_name); ?>">
                                            <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-image"><?php _e('Custom Image', 'mobooking'); ?></label>
                                <div class="image-upload-container">
                                    <input type="url" id="service-image" name="image_url" class="form-control" 
                                           value="<?php echo $service_data ? esc_attr($service_data->image_url) : ''; ?>" 
                                           placeholder="<?php _e('Image URL or select from media library', 'mobooking'); ?>">
                                    <button type="button" class="button select-image"><?php _e('Select Image', 'mobooking'); ?></button>
                                </div>
                                
                                <div class="image-preview">
                                    <?php if ($service_data && !empty($service_data->image_url)) : ?>
                                        <img src="<?php echo esc_url($service_data->image_url); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                
                                <p class="form-help"><?php _e('Images will be displayed at 60x60 pixels. Recommended size: 120x120px.', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Options Tab -->
                <div id="options" class="tab-pane <?php echo $active_tab === 'options' ? 'active' : ''; ?>">
                    <div class="options-header">
                        <div class="options-header-content">
                            <h3><?php _e('Service Options', 'mobooking'); ?></h3>
                            <p><?php _e('Add customizable options to let customers personalize this service. Options can affect pricing and provide additional value.', 'mobooking'); ?></p>
                        </div>
                        
                        <button type="button" id="add-option-btn" <?php echo !$service_id ? 'disabled title="' . esc_attr__('Save the service first to add options', 'mobooking') . '"' : ''; ?>>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            <?php _e('Add Option', 'mobooking'); ?>
                        </button>
                    </div>
                    
                    <div id="service-options-container" class="service-options-container">
                        <!-- Options will be loaded via JavaScript -->
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <?php if ($current_view === 'edit') : ?>
                        <button type="button" class="button button-danger delete-service-btn" data-id="<?php echo esc_attr($service_data->id); ?>">
                            
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 6H5M5 6H21M5 6V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V6H5ZM8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M10 11V17M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>

                            <?php _e('Delete Service', 'mobooking'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <div class="spacer"></div>
                    
                    <button type="submit" id="save-service-button" class="button button-primary">
                                                
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21V13H7V21M7 3V8H15M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <span class="normal-state">
                            <?php echo $current_view === 'edit' ? __('Update Service', 'mobooking') : __('Create Service', 'mobooking'); ?>
                        </span>
                        <span class="loading-state" style="display: none;">
                            <?php _e('Saving...', 'mobooking'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Option Modal -->
<div id="option-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3 id="option-modal-title"><?php _e('Add New Option', 'mobooking'); ?></h3>
        
        <form id="option-form" method="post">
            <input type="hidden" id="option-id" name="id">
            <input type="hidden" id="option-service-id" name="service_id" value="<?php echo esc_attr($service_id); ?>">
            <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="option-name"><?php _e('Option Name', 'mobooking'); ?> *</label>
                    <input type="text" id="option-name" name="name" class="form-control" 
                           placeholder="<?php _e('e.g., Extra cleaning supplies', 'mobooking'); ?>" required>
                </div>


<?php
// Replace the existing option type dropdown in dashboard/sections/services.php
// Find the section around line 400-500 in the option modal form

// Replace this existing code:
/*
<div class="form-group">
    <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
    <select id="option-type" name="type" class="form-control">
        <option value="checkbox"><?php _e('Checkbox', 'mobooking'); ?></option>
        <option value="text"><?php _e('Text Input', 'mobooking'); ?></option>
        <option value="number"><?php _e('Number Input', 'mobooking'); ?></option>
        <option value="select"><?php _e('Dropdown Select', 'mobooking'); ?></option>
        <option value="radio"><?php _e('Radio Buttons', 'mobooking'); ?></option>
        <option value="textarea"><?php _e('Text Area', 'mobooking'); ?></option>
        <option value="quantity"><?php _e('Quantity Selector', 'mobooking'); ?></option>
    </select>
</div>
*/

// With this enhanced version:
?>

<div class="form-group">
    <label class="option-type-label"><?php _e('Option Type', 'mobooking'); ?></label>
    <p class="option-type-description"><?php _e('Choose how customers will interact with this option', 'mobooking'); ?></p>
    
    <!-- Hidden input to store the selected type -->
    <input type="hidden" id="option-type" name="type" value="checkbox">
    
    <!-- Visual option type selection cards -->
    <div class="option-type-selector">
        
        <!-- Checkbox Option -->
        <div class="option-type-card active" data-type="checkbox">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Checkbox', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Yes/No choice - great for add-ons', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <label class="example-checkbox">
                        <input type="checkbox" disabled> <?php _e('Add eco-friendly products', 'mobooking'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Text Input Option -->
        <div class="option-type-card" data-type="text">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10,9 9,9 8,9"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Text Input', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Single line text - names, addresses', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <input type="text" class="example-input" placeholder="<?php _e('Enter special instructions', 'mobooking'); ?>" disabled>
                </div>
            </div>
        </div>

        <!-- Number Input Option -->
        <div class="option-type-card" data-type="number">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <path d="M9 9h6v6H9z"/>
                    <path d="M9 1v4M15 1v4M9 19v4M15 19v4M1 9h4M1 15h4M19 9h4M19 15h4"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Number Input', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Numeric values with min/max limits', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <input type="number" class="example-input" placeholder="0" min="1" max="10" disabled>
                </div>
            </div>
        </div>

        <!-- Dropdown Select Option -->
        <div class="option-type-card" data-type="select">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                    <rect x="3" y="4" width="18" height="2" rx="1"/>
                    <rect x="3" y="18" width="18" height="2" rx="1"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Dropdown Select', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Choose one from multiple options', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <select class="example-select" disabled>
                        <option><?php _e('Select frequency...', 'mobooking'); ?></option>
                        <option><?php _e('Weekly', 'mobooking'); ?></option>
                        <option><?php _e('Bi-weekly', 'mobooking'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Radio Buttons Option -->
        <div class="option-type-card" data-type="radio">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Radio Buttons', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Choose one option, clearly visible', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <label class="example-radio">
                        <input type="radio" name="example" disabled> <?php _e('Morning (8-12)', 'mobooking'); ?>
                    </label>
                    <label class="example-radio">
                        <input type="radio" name="example" disabled> <?php _e('Afternoon (1-5)', 'mobooking'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Text Area Option -->
        <div class="option-type-card" data-type="textarea">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <line x1="10" y1="9" x2="8" y2="9"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Text Area', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Multi-line text for longer responses', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <textarea class="example-textarea" placeholder="<?php _e('Describe your cleaning needs...', 'mobooking'); ?>" rows="2" disabled></textarea>
                </div>
            </div>
        </div>

        <!-- Quantity Selector Option -->
        <div class="option-type-card" data-type="quantity">
            <div class="option-type-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
            </div>
            <div class="option-type-content">
                <h4 class="option-type-title"><?php _e('Quantity Selector', 'mobooking'); ?></h4>
                <p class="option-type-desc"><?php _e('Number input with +/- buttons', 'mobooking'); ?></p>
                <div class="option-type-example">
                    <div class="example-quantity">
                        <button type="button" class="qty-btn" disabled>-</button>
                        <input type="number" value="1" class="qty-input" disabled>
                        <button type="button" class="qty-btn" disabled>+</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Enhanced Option Type Selector Styles */
.option-type-label {
    font-weight: 600;
    font-size: 1rem;
    color: hsl(var(--foreground));
    margin-bottom: 0.5rem;
    display: block;
}

.option-type-description {
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.option-type-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.option-type-card {
    border: 2px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: hsl(var(--card));
    position: relative;
    overflow: hidden;
}

.option-type-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.6));
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease-in-out;
}

.option-type-card:hover {
    border-color: hsl(var(--primary) / 0.5);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.option-type-card:hover::before {
    transform: scaleX(1);
}

.option-type-card.active {
    border-color: hsl(var(--primary));
    background-color: hsl(var(--primary) / 0.05);
    box-shadow: var(--shadow-md);
}

.option-type-card.active::before {
    transform: scaleX(1);
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
}

.option-type-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: calc(var(--radius) + 2px);
    background: linear-gradient(135deg, hsl(var(--muted)), hsl(var(--muted) / 0.8));
    color: hsl(var(--muted-foreground));
    margin-bottom: 1rem;
    transition: all 0.3s ease-in-out;
}

.option-type-card.active .option-type-icon {
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: hsl(var(--primary-foreground));
    transform: scale(1.05);
}

.option-type-content {
    flex: 1;
}

.option-type-title {
    font-size: 1rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin: 0 0 0.5rem 0;
    line-height: 1.25;
}

.option-type-desc {
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
    margin: 0 0 1rem 0;
    line-height: 1.4;
}

.option-type-example {
    margin-top: 1rem;
    padding: 0.75rem;
    background-color: hsl(var(--muted) / 0.3);
    border-radius: calc(var(--radius) - 2px);
    border: 1px solid hsl(var(--border));
}

/* Example input styles */
.example-input,
.example-select,
.example-textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) - 2px);
    font-size: 0.8125rem;
    background-color: hsl(var(--background));
    color: hsl(var(--muted-foreground));
    opacity: 0.8;
}

.example-checkbox,
.example-radio {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
    margin-bottom: 0.25rem;
    cursor: default;
}

.example-checkbox input,
.example-radio input {
    opacity: 0.6;
}

.example-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
}

.qty-btn {
    width: 2rem;
    height: 2rem;
    border: 1px solid hsl(var(--border));
    background-color: hsl(var(--background));
    color: hsl(var(--muted-foreground));
    border-radius: calc(var(--radius) - 2px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    opacity: 0.6;
}

.qty-input {
    width: 3rem;
    text-align: center;
    padding: 0.375rem;
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) - 2px);
    font-size: 0.8125rem;
    background-color: hsl(var(--background));
    color: hsl(var(--muted-foreground));
    opacity: 0.8;
}

/* Responsive design */
@media (max-width: 768px) {
    .option-type-selector {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .option-type-card {
        padding: 1rem;
    }
    
    .option-type-icon {
        width: 2.5rem;
        height: 2.5rem;
        margin-bottom: 0.75rem;
    }
}

/* Animation for selection */
@keyframes selectOption {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.option-type-card.selecting {
    animation: selectOption 0.3s ease-in-out;
}
</style>

<script>
// JavaScript to handle option type selection
jQuery(document).ready(function($) {
    // Handle option type card selection
    $(document).on('click', '.option-type-card', function() {
        const $card = $(this);
        const type = $card.data('type');
        
        // Remove active class from all cards
        $('.option-type-card').removeClass('active');
        
        // Add active class to clicked card with animation
        $card.addClass('active selecting');
        setTimeout(() => {
            $card.removeClass('selecting');
        }, 300);
        
        // Update hidden input
        $('#option-type').val(type);
        
        // Trigger change event to update dynamic fields
        $('#option-type').trigger('change');
        
        // Optional: Add haptic feedback on mobile
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    });
    
    // Set initial selection when modal opens
    $(document).on('mobooking:option-modal-opened', function(e, optionData) {
        const currentType = optionData ? optionData.type : 'checkbox';
        
        // Update visual selection
        $('.option-type-card').removeClass('active');
        $(`.option-type-card[data-type="${currentType}"]`).addClass('active');
        
        // Update hidden input
        $('#option-type').val(currentType);
    });
});
</script>




            </div>
            
            <div class="form-group">
                <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                <textarea id="option-description" name="description" class="form-control" rows="2" 
                          placeholder="<?php _e('Optional description to help customers understand this option', 'mobooking'); ?>"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="option-required"><?php _e('Required', 'mobooking'); ?></label>
                    <select id="option-required" name="is_required" class="form-control">
                        <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                        <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="option-price-type"><?php _e('Price Impact', 'mobooking'); ?></label>
                    <select id="option-price-type" name="price_type" class="form-control">
                        <option value="none"><?php _e('No Price Impact', 'mobooking'); ?></option>
                        <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                        <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                        <option value="multiply"><?php _e('Multiply by Value', 'mobooking'); ?></option>
                    </select>
                </div>
            </div>
            
            <div id="price-impact-group" class="form-group">
                <label for="option-price-impact"><?php _e('Price Impact Amount', 'mobooking'); ?></label>
                <input type="number" id="option-price-impact" name="price_impact" class="form-control" 
                       step="0.01" value="0" placeholder="0.00">
                <p class="form-help"><?php _e('Enter the amount to add to the base price. Use negative values to offer discounts.', 'mobooking'); ?></p>
            </div>
            
            <!-- Dynamic fields will be inserted here -->
            <div id="option-dynamic-fields"></div>
            
            <div class="form-actions">
                <button type="button" id="delete-option-btn" class="button button-danger" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 6 3 18h12l3-18"></path>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                    </svg>
                    <?php _e('Delete Option', 'mobooking'); ?>
                </button>
                
                <div class="spacer"></div>
                
                <button type="button" id="cancel-option-btn" class="button button-secondary">
                    <?php _e('Cancel', 'mobooking'); ?>
                </button>
                
                <button type="submit" class="button button-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17,21 17,13 7,13 7,21"/>
                        <polyline points="7,3 7,8 15,8"/>
                    </svg>
                    <span class="normal-state"><?php _e('Save Option', 'mobooking'); ?></span>
                    <span class="loading-state" style="display: none;"><?php _e('Saving...', 'mobooking'); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <h3><?php _e('Confirm Delete', 'mobooking'); ?></h3>
        <p id="confirmation-message"><?php _e('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'); ?></p>
        
        <div class="form-actions">
            <button type="button" class="button button-secondary cancel-delete-btn">
                <?php _e('Cancel', 'mobooking'); ?>
            </button>
            <button type="button" class="button button-danger confirm-delete-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m3 6 3 18h12l3-18"></path>
                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                </svg>
                <span class="normal-state"><?php _e('Delete', 'mobooking'); ?></span>
                <span class="loading-state" style="display: none;"><?php _e('Deleting...', 'mobooking'); ?></span>
            </button>
        </div>
    </div>
</div>

<style>
/* Additional modern styling */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.section-title-group {
    flex: 1;
}

.section-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.section-description {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.9375rem;
    line-height: 1.5;
}

.section-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.5rem;
}

.service-card {
    background-color: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: calc(var(--radius) + 2px);
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.service-card:hover {
    border-color: hsl(var(--primary));
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    transform: translateY(-2px);
}

.service-card-header {
    padding: 1.25rem;
    display: flex;
    gap: 1rem;
}

.service-visual {
    flex-shrink: 0;
}

.service-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
    border-radius: calc(var(--radius) + 2px);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}

.service-icon.default {
    background: linear-gradient(135deg, hsl(var(--muted-foreground)), hsl(var(--muted-foreground) / 0.8));
}

.service-image {
    width: 3rem;
    height: 3rem;
    background-size: cover;
    background-position: center;
    border-radius: calc(var(--radius) + 2px);
    border: 2px solid hsl(var(--border));
}

.service-content {
    flex: 1;
    min-width: 0;
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.service-name {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    line-height: 1.25;
}

.service-badges {
    display: flex;
    gap: 0.375rem;
    flex-wrap: wrap;
}

.service-category-badge,
.service-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    border-radius: calc(var(--radius) - 2px);
    font-size: 0.6875rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.service-category-badge.category-residential {
    background-color: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
}

.service-category-badge.category-commercial {
    background-color: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.service-category-badge.category-special {
    background-color: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.service-status-badge.status-active {
    background-color: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.service-status-badge.status-inactive {
    background-color: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
}

.service-description {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

.service-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.service-price,
.service-duration,
.service-options {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
}

.service-price svg,
.service-duration svg,
.service-options svg {
    color: hsl(var(--muted-foreground));
}

.service-card-actions {
    padding: 1rem 1.25rem;
    border-top: 1px solid hsl(var(--border));
    background-color: hsl(var(--muted) / 0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    border: 2px dashed hsl(var(--border));
    border-radius: calc(var(--radius) + 4px);
    background-color: hsl(var(--muted) / 0.3);
}

.empty-state-icon {
    margin-bottom: 1.5rem;
    color: hsl(var(--muted-foreground));
    opacity: 0.6;
}

.empty-state h3 {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.empty-state p {
    margin: 0 0 1.5rem 0;
    color: hsl(var(--muted-foreground));
    max-width: 28rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    padding-bottom: 0.75rem;
    border-bottom: 1px solid hsl(var(--border));
}

.input-with-icon {
    position: relative;
}

.input-with-icon svg {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: hsl(var(--muted-foreground));
    pointer-events: none;
}

.input-with-icon input {
    padding-left: 2.5rem !important;
}

.form-help {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.options-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background-color: hsl(var(--muted) / 0.3);
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    gap: 2rem;
}

.options-header-content {
    flex: 1;
}

.options-header h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.options-header p {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }
    
    .section-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .service-card-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .service-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.5rem;
    }
    
    .service-meta {
        justify-content: center;
    }
    
    .service-card-actions {
        flex-direction: column;
    }
    
    .options-header {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>