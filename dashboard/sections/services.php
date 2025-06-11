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
    'dashicons-heart' => 'Heart',
    'dashicons-shield' => 'Shield',
    'dashicons-lightbulb' => 'Lightbulb',
    'dashicons-tag' => 'Tag'
);
?>

<div class="services-section modern-compact">
    <?php if ($current_view === 'list') : ?>
        <!-- ===== MODERN SERVICES LIST VIEW ===== -->
        <div class="services-header-modern">
            <div class="header-main">
                <div class="title-section">
                    <h1 class="page-title">
                        <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12M21 16V8.00002C20.9996 7.6493 20.9071 7.00119 20.556 6.69754 20.3037 6.44539 20 6.27002L13 2.27002C12.696 2.09449 12.3511 2.00208 12 2.00208C11.6489 2.00208 11.304 2.09449 11 2.27002L4 6.27002C3.69626 6.44539 3.44398 6.69754 3.26846 7.00119C3.09294 7.30483 3.00036 7.6493 3 8.00002V16C3.00036 16.3508 3.09294 16.6952 3.26846 16.9989C3.44398 17.3025 3.69626 17.5547 4 17.73L11 21.73C11.304 21.9056 11.6489 21.998 12 21.998C12.3511 21.998 12.696 21.9056 13 21.73L20 17.73C20.3037 17.5547 20.556 17.3025 20.7315 16.9989C20.9071 16.6952 20.9996 16.3508 21 16Z"/>
                        </svg>
                        Services
                    </h1>
                    <p class="page-subtitle">Manage your service offerings</p>
                </div>
                
                <?php if (!empty($services)) : ?>
                    <div class="quick-stats">
                        <div class="stat-pill">
                            <span class="stat-number"><?php echo count($services); ?></span>
                            <span class="stat-label">Total</span>
                        </div>
                        <div class="stat-pill active">
                            <span class="stat-number"><?php echo count(array_filter($services, function($s) { return $s->status === 'active'; })); ?></span>
                            <span class="stat-label">Active</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="header-actions">
                <?php if (!empty($services)) : ?>
                    <div class="filter-compact">
                        <select id="category-filter" class="filter-select-modern">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="btn-add-modern">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add Service
                </a>
            </div>
        </div>
        
        <?php if (empty($services)) : ?>
            <!-- Modern Empty State -->
            <div class="empty-state-modern">
                <div class="empty-visual">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                    </div>
                </div>
                <div class="empty-content">
                    <h3>Create Your First Service</h3>
                    <p>Start building your service catalog with detailed descriptions, pricing, and customizable options.</p>
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="btn-create-first">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Create First Service
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Modern Services Grid -->
            <div class="services-grid-modern">
                <?php foreach ($services as $service) : 
                    $options_count = count($options_manager->get_service_options($service->id));
                ?>
                    <div class="service-card-modern" data-category="<?php echo esc_attr($service->category); ?>">
                        <div class="card-header">
                            <div class="service-visual">
                                <?php if (!empty($service->image_url)) : ?>
                                    <div class="service-image">
                                        <img src="<?php echo esc_url($service->image_url); ?>" alt="<?php echo esc_attr($service->name); ?>">
                                    </div>
                                <?php elseif (!empty($service->icon)) : ?>
                                    <div class="service-icon">
                                        <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                    </div>
                                <?php else : ?>
                                    <div class="service-icon default">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="status-indicator <?php echo esc_attr($service->status); ?>">
                                    <?php if ($service->status === 'active') : ?>
                                        <div class="status-dot active"></div>
                                    <?php else : ?>
                                        <div class="status-dot inactive"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <button type="button" class="action-btn edit" data-id="<?php echo esc_attr($service->id); ?>" title="Edit Service">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"></path>
                                    </svg>
                                </button>
                                <button type="button" class="action-btn delete delete-service-btn" data-id="<?php echo esc_attr($service->id); ?>" title="Delete Service">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m3 6 3 18h12l3-18"></path>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="service-title">
                                <h3><?php echo esc_html($service->name); ?></h3>
                                <?php if (!empty($service->category)) : ?>
                                    <span class="category-tag <?php echo esc_attr($service->category); ?>">
                                        <?php echo esc_html(ucfirst($service->category)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($service->description)) : ?>
                                <p class="service-description">
                                    <?php echo wp_trim_words(esc_html($service->description), 15); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="service-meta">
                                <div class="meta-item price">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <span><?php echo wc_price($service->price); ?></span>
                                </div>
                                
                                <div class="meta-item duration">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                    <span><?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?></span>
                                </div>
                                
                                <?php if ($options_count > 0) : ?>
                                    <div class="meta-item options">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                        <span><?php echo $options_count; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service->id), home_url('/dashboard/services/'))); ?>" 
                               class="btn-edit-modern">
                                Edit Service
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <!-- ===== MODERN SERVICE FORM VIEW ===== -->
        <div class="service-form-modern">
            <!-- Compact Breadcrumb -->
            <div class="breadcrumb-modern">
                <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="breadcrumb-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12"/>
                    </svg>
                    Services
                </a>
                <svg class="breadcrumb-separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                <span class="breadcrumb-current">
                    <?php echo $current_view === 'edit' ? __('Edit Service', 'mobooking') : __('New Service', 'mobooking'); ?>
                </span>
            </div>
            
            <!-- Compact Header -->
            <div class="form-header-modern">
                <div class="header-content">
                    <h1 class="form-title-modern">
                        <?php if ($current_view === 'edit') : ?>
                            <?php echo esc_html($service_data->name); ?>
                        <?php else : ?>
                            <?php _e('New Service', 'mobooking'); ?>
                        <?php endif; ?>
                    </h1>
                    <?php if ($current_view === 'edit') : ?>
                        <div class="service-status-badge <?php echo esc_attr($service_data->status); ?>">
                            <?php echo esc_html(ucfirst($service_data->status)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="btn-back-modern">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m12 19-7-7 7-7M19 12H5"/>
                    </svg>
                    Back
                </a>
            </div>
            
            <!-- Compact Tabs -->
            <div class="tabs-modern">
                <div class="tab-nav">
                    <button type="button" class="tab-btn <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" 
                            data-tab="basic-info">
                        <span class="tab-title">Details</span>
                    </button>
                    <button type="button" class="tab-btn <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" 
                            data-tab="presentation">
                        <span class="tab-title">Appearance</span>
                    </button>
                    <button type="button" class="tab-btn <?php echo $active_tab === 'options' ? 'active' : ''; ?>" 
                            data-tab="options">
                        <span class="tab-title">Options</span>
                        <?php if ($service_id) : ?>
                            <?php 
                            $options_count = count($options_manager->get_service_options($service_id));
                            if ($options_count > 0) : ?>
                                <span class="tab-badge"><?php echo $options_count; ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
            
            <!-- Service Form -->
            <form id="service-form" method="post" class="service-form-compact">
                <input type="hidden" id="service-id" name="id" value="<?php echo $service_data ? esc_attr($service_data->id) : ''; ?>">
                <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
                
                <!-- Basic Info Tab -->
                <div id="basic-info" class="tab-content <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>">
                    <div class="form-section-compact">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="service-name" class="field-label">Service Name <span class="required">*</span></label>
                                <input type="text" id="service-name" name="name" class="form-input" 
                                       value="<?php echo $service_data ? esc_attr($service_data->name) : ''; ?>" 
                                       placeholder="e.g., Deep House Cleaning" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-category" class="field-label">Category</label>
                                <select id="service-category" name="category" class="form-input">
                                    <option value="">Select Category</option>
                                    <option value="residential" <?php selected($service_data ? $service_data->category : '', 'residential'); ?>>Residential</option>
                                    <option value="commercial" <?php selected($service_data ? $service_data->category : '', 'commercial'); ?>>Commercial</option>
                                    <option value="special" <?php selected($service_data ? $service_data->category : '', 'special'); ?>>Special</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="service-price" class="field-label">Price <span class="required">*</span></label>
                                <div class="input-with-icon">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <input type="number" id="service-price" name="price" class="form-input" 
                                           value="<?php echo $service_data ? esc_attr($service_data->price) : ''; ?>" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-duration" class="field-label">Duration <span class="required">*</span></label>
                                <div class="input-with-suffix">
                                    <input type="number" id="service-duration" name="duration" class="form-input" 
                                           value="<?php echo $service_data ? esc_attr($service_data->duration) : '60'; ?>" 
                                           min="15" step="15" placeholder="60" required>
                                    <span class="input-suffix">min</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="service-description" class="field-label">Description</label>
                            <textarea id="service-description" name="description" class="form-input" rows="3" 
                                      placeholder="Describe what this service includes..."><?php echo $service_data ? esc_textarea($service_data->description) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="service-status" class="field-label">Status</label>
                            <select id="service-status" name="status" class="form-input">
                                <option value="active" <?php selected($service_data ? $service_data->status : 'active', 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected($service_data ? $service_data->status : '', 'inactive'); ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Appearance Tab -->
                <div id="presentation" class="tab-content <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>">
                    <div class="form-section-compact">
                        <div class="appearance-grid">
                            <div class="icon-section">
                                <label class="field-label">Service Icon</label>
                                <input type="hidden" id="service-icon" name="icon" value="<?php echo $service_data ? esc_attr($service_data->icon) : ''; ?>">
                                
                                <div class="icon-picker">
                                    <div class="current-icon">
                                        <?php if ($service_data && !empty($service_data->icon)) : ?>
                                            <span class="dashicons <?php echo esc_attr($service_data->icon); ?>"></span>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="icon-grid">
                                        <?php foreach ($available_icons as $icon_class => $icon_name) : ?>
                                            <div class="icon-option <?php echo ($service_data && $service_data->icon === $icon_class) ? 'selected' : ''; ?>" 
                                                 data-icon="<?php echo esc_attr($icon_class); ?>" title="<?php echo esc_attr($icon_name); ?>">
                                                <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="image-section">
                                <label for="service-image" class="field-label">Custom Image</label>
                                <div class="image-upload-compact">
                                    <div class="image-preview">
                                        <?php if ($service_data && !empty($service_data->image_url)) : ?>
                                            <img src="<?php echo esc_url($service_data->image_url); ?>" alt="Service image">
                                        <?php else : ?>
                                            <div class="image-placeholder">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                                    <circle cx="9" cy="9" r="2"/>
                                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                                </svg>
                                                <span>No image</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="image-controls">
                                        <input type="url" id="service-image" name="image_url" class="form-input" 
                                               value="<?php echo $service_data ? esc_attr($service_data->image_url) : ''; ?>" 
                                               placeholder="Image URL">
                                        <button type="button" class="btn-select-image">
                                            Select
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Options Tab -->
                <div id="options" class="tab-content <?php echo $active_tab === 'options' ? 'active' : ''; ?>">
                    <div class="form-section-compact">
                        <div class="options-header-compact">
                            <div>
                                <h3>Service Options</h3>
                                <p>Add customizable options for this service</p>
                            </div>
                            <button type="button" id="add-option-btn" class="btn-add-option" 
                                    <?php echo !$service_id ? 'disabled title="Save the service first"' : ''; ?>>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Add Option
                            </button>
                        </div>
                        
                        <div id="service-options-container" class="options-container-compact">
                            <!-- Options will be loaded via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Fixed Footer Actions -->
                <div class="form-footer-modern">
                    <div class="footer-left">
                        <?php if ($current_view === 'edit') : ?>
                            <button type="button" class="btn-delete delete-service-btn" data-id="<?php echo esc_attr($service_data->id); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m3 6 3 18h12l3-18"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                </svg>
                                Delete
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="footer-right">
                        <button type="submit" id="save-service-button" class="btn-save-modern">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21V13H7V21M7 3V8H15M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z"/>
                            </svg>
                            <span class="btn-text">
                                <?php echo $current_view === 'edit' ? __('Save Changes', 'mobooking') : __('Create Service', 'mobooking'); ?>
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <div class="spinner-modern"></div>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Modals (Keep existing modals but with modern styling) -->
<!-- Option Modal -->
<div id="option-modal" class="modal-modern" style="display:none;">
    <div class="modal-content-modern">
        <div class="modal-header-modern">
            <h3 id="option-modal-title">Add New Option</h3>
            <button class="modal-close-modern" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="modal-body-modern">
            <form id="option-form" method="post">
                <input type="hidden" id="option-id" name="id">
                <input type="hidden" id="option-service-id" name="service_id" value="<?php echo esc_attr($service_id); ?>">
                <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="option-name">Option Name <span class="required">*</span></label>
                        <input type="text" id="option-name" name="name" class="form-input" 
                               placeholder="e.g., Extra cleaning supplies" required>
                    </div>
                    <div class="form-group">
                        <label for="option-type">Option Type</label>
                        <select id="option-type" name="type" class="form-input">
                            <option value="checkbox">Checkbox</option>
                            <option value="text">Text Input</option>
                            <option value="number">Number Input</option>
                            <option value="select">Dropdown Select</option>
                            <option value="radio">Radio Buttons</option>
                            <option value="textarea">Text Area</option>
                            <option value="quantity">Quantity Selector</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="option-description">Description</label>
                    <textarea id="option-description" name="description" class="form-input" rows="2" 
                              placeholder="Optional description to help customers understand this option"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="option-required">Required</label>
                        <select id="option-required" name="is_required" class="form-input">
                            <option value="0">Optional</option>
                            <option value="1">Required</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="option-price-type">Price Impact</label>
                        <select id="option-price-type" name="price_type" class="form-input">
                            <option value="none">No Price Impact</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                            <option value="multiply">Multiply by Value</option>
                        </select>
                    </div>
                </div>
                
                <div id="price-impact-group" class="form-group">
                    <label for="option-price-impact">Price Impact Amount</label>
                    <input type="number" id="option-price-impact" name="price_impact" class="form-input" 
                           step="0.01" value="0" placeholder="0.00">
                </div>
                
                <!-- Dynamic fields will be inserted here -->
                <div id="option-dynamic-fields"></div>
            </form>
        </div>
        
        <div class="modal-footer-modern">
            <button type="button" id="delete-option-btn" class="btn-delete" style="display: none;">
                Delete Option
            </button>
            <div class="modal-actions">
                <button type="button" id="cancel-option-btn" class="btn-cancel">Cancel</button>
                <button type="submit" form="option-form" class="btn-save-modern">
                    <span class="btn-text">Save Option</span>
                    <span class="btn-loading" style="display: none;">
                        <div class="spinner-modern"></div>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="modal-modern" style="display:none;">
    <div class="modal-content-modern small">
        <div class="modal-header-modern">
            <h3>Confirm Delete</h3>
        </div>
        <div class="modal-body-modern">
            <p id="confirmation-message">Are you sure you want to delete this? This action cannot be undone.</p>
        </div>
        <div class="modal-footer-modern">
            <button type="button" class="btn-cancel cancel-delete-btn">Cancel</button>
            <button type="button" class="btn-delete confirm-delete-btn">
                <span class="btn-text">Delete</span>
                <span class="btn-loading" style="display: none;">
                    <div class="spinner-modern"></div>
                    Deleting...
                </span>
            </button>
        </div>
    </div>
</div>

<style>
/* Modern Compact Services Section Styles */
.services-section.modern-compact {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
}

/* ===== MODERN LIST VIEW ===== */
.services-header-modern {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, hsl(var(--card)), hsl(var(--muted) / 0.3));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    box-shadow: 0 2px 8px hsl(var(--shadow) / 0.1);
}

.header-main {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex: 1;
}

.title-section {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.title-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: hsl(var(--primary));
}

.page-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.quick-stats {
    display: flex;
    gap: 1rem;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
}

.stat-pill.active {
    background: linear-gradient(135deg, hsl(var(--success) / 0.1), hsl(var(--success) / 0.05));
    border-color: hsl(var(--success) / 0.3);
}

.stat-number {
    font-weight: 700;
    color: hsl(var(--foreground));
}

.stat-label {
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-select-modern {
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: hsl(var(--background));
    font-size: 0.875rem;
    min-width: 140px;
}

.btn-add-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.9));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px hsl(var(--primary) / 0.2);
}

.btn-add-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.btn-add-modern svg {
    width: 1rem;
    height: 1rem;
}

/* Empty State */
.empty-state-modern {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
    border: 2px dashed hsl(var(--border));
    border-radius: 12px;
    margin: 2rem 0;
}

.empty-visual {
    margin-bottom: 2rem;
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    color: hsl(var(--primary));
    opacity: 0.6;
}

.empty-content h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.empty-content p {
    margin: 0 0 2rem 0;
    color: hsl(var(--muted-foreground));
    max-width: 400px;
}

.btn-create-first {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.9));
    color: hsl(var(--primary-foreground));
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.2);
}

.btn-create-first:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px hsl(var(--primary) / 0.3);
}

/* Services Grid */
.services-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
}

.service-card-modern {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px hsl(var(--shadow) / 0.08);
}

.service-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px hsl(var(--shadow) / 0.15);
    border-color: hsl(var(--primary) / 0.3);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem;
    border-bottom: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.service-visual {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.service-icon,
.service-image {
    width: 3rem;
    height: 3rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.service-icon {
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
}

.service-icon.default {
    background: linear-gradient(135deg, hsl(var(--muted-foreground)), hsl(var(--muted-foreground) / 0.8));
}

.service-icon .dashicons {
    font-size: 1.25rem;
}

.service-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.status-indicator {
    position: absolute;
    top: -6px;
    right: -6px;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid hsl(var(--card));
}

.status-dot.active {
    background: hsl(var(--success));
}

.status-dot.inactive {
    background: hsl(var(--muted-foreground));
}

.card-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 2rem;
    height: 2rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary) / 0.3);
}

.action-btn.delete:hover {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: white;
}

.action-btn svg {
    width: 0.875rem;
    height: 0.875rem;
}

.card-content {
    padding: 1.25rem;
}

.service-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.service-title h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.category-tag {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
}

.category-tag.residential {
    background: hsl(var(--info) / 0.1);
    color: hsl(var(--info));
}

.category-tag.commercial {
    background: hsl(var(--warning) / 0.1);
    color: hsl(var(--warning));
}

.category-tag.special {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.service-description {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.4;
}

.service-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: hsl(var(--muted-foreground));
}

.meta-item svg {
    width: 0.875rem;
    height: 0.875rem;
}

.meta-item.price span {
    font-weight: 700;
    color: hsl(var(--success));
}

.card-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.2);
}

.btn-edit-modern {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.5rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-edit-modern:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
}

/* ===== MODERN FORM VIEW ===== */
.service-form-modern {
    margin: 0 auto;
}

.breadcrumb-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.breadcrumb-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb-link:hover {
    color: hsl(var(--primary));
}

.breadcrumb-link svg {
    width: 1rem;
    height: 1rem;
}

.breadcrumb-separator {
    width: 1rem;
    height: 1rem;
    color: hsl(var(--muted-foreground));
}

.breadcrumb-current {
    color: hsl(var(--foreground));
    font-weight: 500;
}

.form-header-modern {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.form-title-modern {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.service-status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.service-status-badge.active {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
}

.service-status-badge.inactive {
    background: hsl(var(--muted-foreground) / 0.1);
    color: hsl(var(--muted-foreground));
}

.btn-back-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.btn-back-modern:hover {
    background: hsl(var(--accent));
}

.btn-back-modern svg {
    width: 1rem;
    height: 1rem;
}

/* Compact Tabs */
.tabs-modern {
    margin-bottom: 1.5rem;
}

.tab-nav {
    display: flex;
    background: hsl(var(--muted));
    border-radius: 8px;
    padding: 0.25rem;
    gap: 0.25rem;
}

.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.tab-btn:hover {
    color: hsl(var(--foreground));
    background: hsl(var(--background) / 0.5);
}

.tab-btn.active {
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    box-shadow: 0 2px 4px hsl(var(--shadow) / 0.1);
}

.tab-badge {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-radius: 4px;
    padding: 0.125rem 0.375rem;
    font-size: 0.6875rem;
    font-weight: 600;
}

/* Form Styles */
.service-form-compact {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    padding-bottom: 5rem; /* Space for fixed footer */
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

.form-section-compact {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.field-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.required {
    color: hsl(var(--destructive));
}

.form-input {
    padding: 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: hsl(var(--background));
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

.input-with-icon {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: hsl(var(--muted-foreground));
    pointer-events: none;
}

.input-with-icon .form-input {
    padding-left: 2.5rem;
}

.input-with-suffix {
    position: relative;
}

.input-suffix {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    pointer-events: none;
}

/* Appearance Section */
.appearance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.icon-picker {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.current-icon {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.current-icon .dashicons {
    font-size: 1.5rem;
}

.current-icon svg {
    width: 1.5rem;
    height: 1.5rem;
}

.icon-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.5rem;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: hsl(var(--muted) / 0.2);
}

.icon-option {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: hsl(var(--background));
    border: 1px solid transparent;
}

.icon-option:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary) / 0.3);
}

.icon-option.selected {
    background: hsl(var(--primary));
    color: white;
}

.icon-option .dashicons {
    font-size: 1rem;
}

.image-upload-compact {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.image-preview {
    width: 120px;
    height: 80px;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--muted) / 0.2);
    margin: 0 auto;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: hsl(var(--muted-foreground));
    text-align: center;
}

.image-placeholder svg {
    width: 1.5rem;
    height: 1.5rem;
}

.image-placeholder span {
    font-size: 0.75rem;
}

.image-controls {
    display: flex;
    gap: 0.5rem;
}

.btn-select-image {
    padding: 0.5rem 1rem;
    background: hsl(var(--secondary));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-select-image:hover {
    background: hsl(var(--accent));
}

/* Options Section */
.options-header-compact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid hsl(var(--border));
}

.options-header-compact h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.options-header-compact p {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.btn-add-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add-option:hover:not(:disabled) {
    background: hsl(var(--primary) / 0.9);
}

.btn-add-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-add-option svg {
    width: 0.875rem;
    height: 0.875rem;
}

.options-container-compact {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Fixed Footer */
.form-footer-modern {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, hsl(var(--muted) / 0.5), hsl(var(--muted) / 0.3));
    border-top: 1px solid hsl(var(--border));
    backdrop-filter: blur(8px);
}

.footer-left,
.footer-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-delete {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-delete:hover {
    background: hsl(var(--destructive) / 0.9);
    transform: translateY(-1px);
}

.btn-delete svg {
    width: 0.875rem;
    height: 0.875rem;
}

.btn-save-modern {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.9));
    color: hsl(var(--primary-foreground));
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px hsl(var(--primary) / 0.2);
}

.btn-save-modern:hover {
    background: linear-gradient(135deg, hsl(var(--primary) / 0.9), hsl(var(--primary) / 0.8));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.btn-save-modern svg {
    width: 1rem;
    height: 1rem;
}

.btn-loading {
    display: none;
}

.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.spinner-modern {
    width: 1rem;
    height: 1rem;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* ===== MODERN MODALS ===== */
.modal-modern {
    position: fixed;
    inset: 0;
    z-index: 100;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-modern:not([style*="display: none"]) {
    opacity: 1;
    visibility: visible;
}

.modal-content-modern {
    background: hsl(var(--card));
    border: 1px solid hsl(var(--border));
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
    width: 90vw;
    max-width: 600px;
    max-height: 90vh;
    overflow: hidden;
    animation: modalSlideUp 0.3s ease;
}

.modal-content-modern.small {
    max-width: 400px;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(2rem) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header-modern {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid hsl(var(--border));
    background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.modal-header-modern h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.modal-close-modern {
    width: 2rem;
    height: 2rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--background));
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close-modern:hover {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: white;
}

.modal-close-modern svg {
    width: 1rem;
    height: 1rem;
}

.modal-body-modern {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer-modern {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.2);
}

.modal-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.btn-cancel {
    padding: 0.5rem 1rem;
    background: hsl(var(--secondary));
    color: hsl(var(--secondary-foreground));
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: hsl(var(--accent));
}

/* Responsive Design */
@media (max-width: 768px) {
    .services-header-modern {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .header-main {
        flex-direction: column;
        gap: 1rem;
    }

    .quick-stats {
        justify-content: center;
    }

    .header-actions {
        justify-content: center;
        flex-wrap: wrap;
    }

    .services-grid-modern {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .appearance-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .icon-grid {
        grid-template-columns: repeat(5, 1fr);
    }

    .tab-nav {
        flex-direction: column;
        gap: 0.25rem;
    }

    .form-footer-modern {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }

    .footer-left,
    .footer-right {
        width: 100%;
        justify-content: center;
    }

    .modal-content-modern {
        width: 95vw;
        margin: 1rem;
    }

    .modal-header-modern,
    .modal-body-modern,
    .modal-footer-modern {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .modal-actions {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1.25rem;
    }

    .title-icon {
        width: 1.25rem;
        height: 1.25rem;
    }

    .service-card-modern {
        margin: 0;
    }

    .card-header {
        padding: 1rem;
    }

    .card-content {
        padding: 1rem;
    }

    .service-meta {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .form-title-modern {
        font-size: 1.25rem;
    }

    .tab-content {
        padding: 1.5rem;
    }

    .icon-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
    .services-header-modern,
    .form-header-modern {
        background: linear-gradient(135deg, hsl(var(--card)), hsl(var(--muted) / 0.2));
    }
    
    .modal-modern {
        background: rgba(0, 0, 0, 0.8);
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .service-card-modern,
    .modal-content-modern {
        border-width: 2px;
    }
    
    .btn-add-modern,
    .btn-save-modern {
        border: 2px solid hsl(var(--primary));
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .service-card-modern:hover {
        transform: none;
    }
}
</style>

<script>
// Modern compact JavaScript enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced service card interactions
    const serviceCards = document.querySelectorAll('.service-card-modern');
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.setProperty('--hover-scale', '1.02');
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.removeProperty('--hover-scale');
        });
    });
    
    // Smooth tab transitions
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remove active states
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active states
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('active_tab', tabId);
            window.history.replaceState({}, '', url);
        });
    });
    
    // Enhanced icon selection
    const iconOptions = document.querySelectorAll('.icon-option');
    const currentIcon = document.querySelector('.current-icon');
    const iconInput = document.getElementById('service-icon');
    
    iconOptions.forEach(option => {
        option.addEventListener('click', function() {
            const iconClass = this.dataset.icon;
            
            // Update selection
            iconOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Update current icon display
            if (currentIcon) {
                currentIcon.innerHTML = `<span class="dashicons ${iconClass}"></span>`;
            }
            
            // Update hidden input
            if (iconInput) {
                iconInput.value = iconClass;
            }
            
            // Add selection feedback
            this.style.transform = 'scale(1.1)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
    });
    
    // Enhanced filter functionality
    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const selectedCategory = this.value;
            const serviceCards = document.querySelectorAll('.service-card-modern');
            
            serviceCards.forEach(card => {
                const cardCategory = card.dataset.category;
                
                if (!selectedCategory || cardCategory === selectedCategory) {
                    card.style.display = '';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // Edit button click handlers
    document.querySelectorAll('.action-btn.edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const serviceId = this.dataset.id;
            const editUrl = new URL(window.location.origin + window.location.pathname);
            editUrl.searchParams.set('view', 'edit');
            editUrl.searchParams.set('service_id', serviceId);
            window.location.href = editUrl.toString();
        });
    });
    
    // Form validation enhancements
    const serviceForm = document.getElementById('service-form');
    if (serviceForm) {
        const inputs = serviceForm.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = 'hsl(var(--destructive))';
                    this.style.boxShadow = '0 0 0 2px hsl(var(--destructive) / 0.2)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
        });
    }
    
    // Loading state for buttons
    function setButtonLoading(button, loading) {
        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (loading) {
            button.disabled = true;
            button.classList.add('loading');
            if (btnText) btnText.style.display = 'none';
            if (btnLoading) btnLoading.style.display = 'flex';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            if (btnText) btnText.style.display = '';
            if (btnLoading) btnLoading.style.display = 'none';
        }
    }
    
    // Make setButtonLoading globally available
    window.setButtonLoading = setButtonLoading;
});

// CSS animation for fade in
const fadeInKeyframes = `
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
`;

// Add keyframes to document
const styleSheet = document.createElement('style');
styleSheet.textContent = fadeInKeyframes;
document.head.appendChild(styleSheet);
</script>