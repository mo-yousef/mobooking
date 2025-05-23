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

<div class="services-section">
    <?php if ($current_view === 'list') : ?>
        <!-- ===== SERVICES LIST VIEW ===== -->
        <div class="services-header">
            <div class="services-header-content">
                <div class="services-title-group">
                    <h1 class="services-main-title">
                        <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12M21 16V8.00002C20.9996 7.6493 20.9071 7.30483 20.7315 7.00119C20.556 6.69754 20.3037 6.44539 20 6.27002L13 2.27002C12.696 2.09449 12.3511 2.00208 12 2.00208C11.6489 2.00208 11.304 2.09449 11 2.27002L4 6.27002C3.69626 6.44539 3.44398 6.69754 3.26846 7.00119C3.09294 7.30483 3.00036 7.6493 3 8.00002V16C3.00036 16.3508 3.09294 16.6952 3.26846 16.9989C3.44398 17.3025 3.69626 17.5547 4 17.73L11 21.73C11.304 21.9056 11.6489 21.998 12 21.998C12.3511 21.998 12.696 21.9056 13 21.73L20 17.73C20.3037 17.5547 20.556 17.3025 20.7315 16.9989C20.9071 16.6952 20.9996 16.3508 21 16Z"/>
                        </svg>
                        <?php _e('My Services', 'mobooking'); ?>
                    </h1>
                    <p class="services-subtitle"><?php _e('Manage and customize your service offerings', 'mobooking'); ?></p>
                </div>
                
                <?php if (!empty($services)) : ?>
                    <div class="services-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($services); ?></span>
                            <span class="stat-label"><?php _e('Services', 'mobooking'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count(array_filter($services, function($s) { return $s->status === 'active'; })); ?></span>
                            <span class="stat-label"><?php _e('Active', 'mobooking'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="services-header-actions">
                <?php if (!empty($services)) : ?>
                    <div class="filter-group">
                        <label for="category-filter" class="filter-label"><?php _e('Filter:', 'mobooking'); ?></label>
                        <select id="category-filter" class="filter-select">
                            <option value=""><?php _e('All Categories', 'mobooking'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Add Service', 'mobooking'); ?>
                </a>
            </div>
        </div>
        
        <?php if (empty($services)) : ?>
            <!-- Empty State -->
            <div class="services-empty-state">
                <div class="empty-state-visual">
                    <div class="empty-state-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                    </div>
                    <div class="empty-state-sparkles">
                        <div class="sparkle sparkle-1"></div>
                        <div class="sparkle sparkle-2"></div>
                        <div class="sparkle sparkle-3"></div>
                    </div>
                </div>
                <div class="empty-state-content">
                    <h2><?php _e('Create Your First Service', 'mobooking'); ?></h2>
                    <p><?php _e('Start building your service catalog. Add detailed descriptions, pricing, and customizable options to attract customers.', 'mobooking'); ?></p>
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="btn-primary btn-large">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <?php _e('Create Your First Service', 'mobooking'); ?>
                    </a>
                </div>
            </div>
        <?php else : ?>
            <!-- Services Grid -->
            <div class="services-grid">
                <?php foreach ($services as $service) : 
                    $options_count = count($options_manager->get_service_options($service->id));
                ?>
                    <div class="service-card" data-category="<?php echo esc_attr($service->category); ?>">
                        <div class="service-card-header">
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
                                    <div class="service-icon service-icon-default">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="service-status-indicator <?php echo esc_attr($service->status); ?>">
                                    <?php if ($service->status === 'active') : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M8 12h8"/>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="service-badges">
                                <?php if (!empty($service->category)) : ?>
                                    <span class="service-category-badge category-<?php echo esc_attr($service->category); ?>">
                                        <?php echo esc_html(ucfirst($service->category)); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($options_count > 0) : ?>
                                    <span class="service-options-badge">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3"/>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                                        </svg>
                                        <?php echo $options_count; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="service-card-content">
                            <h3 class="service-name"><?php echo esc_html($service->name); ?></h3>
                            
                            <?php if (!empty($service->description)) : ?>
                                <p class="service-description">
                                    <?php echo wp_trim_words(esc_html($service->description), 20); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="service-meta">
                                <div class="service-price">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    <span class="price-amount"><?php echo wc_price($service->price); ?></span>
                                </div>
                                
                                <div class="service-duration">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                    <span><?php echo sprintf(_n('%d min', '%d mins', $service->duration, 'mobooking'), $service->duration); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="service-card-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service->id), home_url('/dashboard/services/'))); ?>" 
                               class="btn-secondary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5Z"></path>
                                </svg>
                                <?php _e('Edit', 'mobooking'); ?>
                            </a>
                            
                            <button type="button" class="btn-icon btn-danger delete-service-btn" data-id="<?php echo esc_attr($service->id); ?>" title="<?php _e('Delete Service', 'mobooking'); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        <!-- ===== SERVICE EDIT/NEW VIEW ===== -->
        <div class="service-form-header">
            <div class="form-header-content">
                <nav class="form-breadcrumb">
                    <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="breadcrumb-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16.5 9.40002L7.5 4.21002M3.27 6.96002L12 12.01L20.73 6.96002M12 22.08V12M21 16V8.00002C20.9996 7.6493 20.9071 7.30483 20.7315 7.00119C20.556 6.69754 20.3037 6.44539 20 6.27002L13 2.27002C12.696 2.09449 12.3511 2.00208 12 2.00208C11.6489 2.00208 11.304 2.09449 11 2.27002L4 6.27002C3.69626 6.44539 3.44398 6.69754 3.26846 7.00119C3.09294 7.30483 3.00036 7.6493 3 8.00002V16C3.00036 16.3508 3.09294 16.6952 3.26846 16.9989C3.44398 17.3025 3.69626 17.5547 4 17.73L11 21.73C11.304 21.9056 11.6489 21.998 12 21.998C12.3511 21.998 12.696 21.9056 13 21.73L20 17.73C20.3037 17.5547 20.556 17.3025 20.7315 16.9989C20.9071 16.6952 20.9996 16.3508 21 16Z"/>
                        </svg>
                        <?php _e('Services', 'mobooking'); ?>
                    </a>
                    <svg class="breadcrumb-separator" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="breadcrumb-current">
                        <?php echo $current_view === 'edit' ? __('Edit Service', 'mobooking') : __('New Service', 'mobooking'); ?>
                    </span>
                </nav>
                
                <div class="form-header-title-group">
                    <h1 class="form-title">
                        <?php if ($current_view === 'edit') : ?>
                            <?php printf(__('Edit: %s', 'mobooking'), '<span class="service-name-highlight">' . esc_html($service_data->name) . '</span>'); ?>
                        <?php else : ?>
                            <?php _e('Create New Service', 'mobooking'); ?>
                        <?php endif; ?>
                    </h1>
                    <p class="form-subtitle">
                        <?php if ($current_view === 'edit') : ?>
                            <?php _e('Update your service details and manage customization options', 'mobooking'); ?>
                        <?php else : ?>
                            <?php _e('Set up a new service with pricing and customizable options', 'mobooking'); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <div class="form-header-actions">
                <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m12 19-7-7 7-7M19 12H5"/>
                    </svg>
                    <?php _e('Back to Services', 'mobooking'); ?>
                </a>
            </div>
        </div>
        
        <div class="service-form-container">
            <!-- Enhanced Tab Navigation -->
            <div class="service-tabs">
                <div class="tab-list" role="tablist">
                    <button type="button" class="tab-button <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" 
                            data-tab="basic-info" role="tab" aria-selected="<?php echo $active_tab === 'basic-info' ? 'true' : 'false'; ?>">
                        <div class="tab-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="tab-content">
                            <span class="tab-title"><?php _e('Basic Info', 'mobooking'); ?></span>
                            <span class="tab-description"><?php _e('Name, price & description', 'mobooking'); ?></span>
                        </div>
                    </button>
                    
                    <button type="button" class="tab-button <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" 
                            data-tab="presentation" role="tab" aria-selected="<?php echo $active_tab === 'presentation' ? 'true' : 'false'; ?>">
                        <div class="tab-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                <circle cx="9" cy="9" r="2"/>
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                            </svg>
                        </div>
                        <div class="tab-content">
                            <span class="tab-title"><?php _e('Presentation', 'mobooking'); ?></span>
                            <span class="tab-description"><?php _e('Visual appearance', 'mobooking'); ?></span>
                        </div>
                    </button>
                    
                    <button type="button" class="tab-button <?php echo $active_tab === 'options' ? 'active' : ''; ?>" 
                            data-tab="options" role="tab" aria-selected="<?php echo $active_tab === 'options' ? 'true' : 'false'; ?>">
                        <div class="tab-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                        </div>
                        <div class="tab-content">
                            <span class="tab-title"><?php _e('Options', 'mobooking'); ?></span>
                            <span class="tab-description">
                                <?php if ($service_id) : ?>
                                    <?php 
                                    $options_count = count($options_manager->get_service_options($service_id));
                                    echo sprintf(_n('%d option', '%d options', $options_count, 'mobooking'), $options_count);
                                    ?>
                                <?php else : ?>
                                    <?php _e('Customization options', 'mobooking'); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </button>
                </div>
            </div>
            
            <!-- Service Form -->
            <form id="service-form" method="post" class="service-form">
                <input type="hidden" id="service-id" name="id" value="<?php echo $service_data ? esc_attr($service_data->id) : ''; ?>">
                <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
                
                <!-- Basic Info Tab -->
                <div id="basic-info" class="tab-pane <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" role="tabpanel">
                    <div class="form-section">
                        <div class="section-header">
                            <h3 class="section-title"><?php _e('Service Information', 'mobooking'); ?></h3>
                            <p class="section-description"><?php _e('Enter the basic details about your service', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="field-group">
                                <label for="service-name" class="field-label required"><?php _e('Service Name', 'mobooking'); ?></label>
                                <div class="field-input">
                                    <input type="text" id="service-name" name="name" class="form-control" 
                                           value="<?php echo $service_data ? esc_attr($service_data->name) : ''; ?>" 
                                           placeholder="<?php _e('e.g., Deep House Cleaning', 'mobooking'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="field-row">
                                <div class="field-group">
                                    <label for="service-price" class="field-label required"><?php _e('Price', 'mobooking'); ?></label>
                                    <div class="field-input-with-icon">
                                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                        <input type="number" id="service-price" name="price" class="form-control" 
                                               value="<?php echo $service_data ? esc_attr($service_data->price) : ''; ?>" 
                                               step="0.01" min="0" placeholder="0.00" required>
                                    </div>
                                </div>
                                
                                <div class="field-group">
                                    <label for="service-duration" class="field-label required"><?php _e('Duration', 'mobooking'); ?></label>
                                    <div class="field-input-with-icon">
                                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        <input type="number" id="service-duration" name="duration" class="form-control" 
                                               value="<?php echo $service_data ? esc_attr($service_data->duration) : '60'; ?>" 
                                               min="15" step="15" placeholder="60" required>
                                        <span class="field-suffix"><?php _e('minutes', 'mobooking'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="field-row">
                                <div class="field-group">
                                    <label for="service-category" class="field-label"><?php _e('Category', 'mobooking'); ?></label>
                                    <div class="field-input">
                                        <select id="service-category" name="category" class="form-control">
                                            <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                            <option value="residential" <?php selected($service_data ? $service_data->category : '', 'residential'); ?>><?php _e('Residential', 'mobooking'); ?></option>
                                            <option value="commercial" <?php selected($service_data ? $service_data->category : '', 'commercial'); ?>><?php _e('Commercial', 'mobooking'); ?></option>
                                            <option value="special" <?php selected($service_data ? $service_data->category : '', 'special'); ?>><?php _e('Special', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="field-group">
                                    <label for="service-status" class="field-label"><?php _e('Status', 'mobooking'); ?></label>
                                    <div class="field-input">
                                        <select id="service-status" name="status" class="form-control">
                                            <option value="active" <?php selected($service_data ? $service_data->status : 'active', 'active'); ?>><?php _e('Active', 'mobooking'); ?></option>
                                            <option value="inactive" <?php selected($service_data ? $service_data->status : '', 'inactive'); ?>><?php _e('Inactive', 'mobooking'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="field-group">
                                <label for="service-description" class="field-label"><?php _e('Description', 'mobooking'); ?></label>
                                <div class="field-input">
                                    <textarea id="service-description" name="description" class="form-control" rows="4" 
                                              placeholder="<?php _e('Describe what this service includes, what makes it special, and what customers can expect...', 'mobooking'); ?>"><?php echo $service_data ? esc_textarea($service_data->description) : ''; ?></textarea>
                                </div>
                                <p class="field-help"><?php _e('A detailed description helps customers understand your service better', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Presentation Tab -->
                <div id="presentation" class="tab-pane <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" role="tabpanel">
                    <div class="form-section">
                        <div class="section-header">
                            <h3 class="section-title"><?php _e('Visual Presentation', 'mobooking'); ?></h3>
                            <p class="section-description"><?php _e('Choose how your service appears to customers', 'mobooking'); ?></p>
                        </div>
                        
                        <div class="form-fields">
                            <div class="field-row">
                                <div class="field-group">
                                    <label for="service-icon" class="field-label"><?php _e('Service Icon', 'mobooking'); ?></label>
                                    <input type="hidden" id="service-icon" name="icon" value="<?php echo $service_data ? esc_attr($service_data->icon) : ''; ?>">
                                    
                                    <div class="icon-selection">
                                        <div class="icon-preview-container">
                                            <div class="icon-preview">
                                                <?php if ($service_data && !empty($service_data->icon)) : ?>
                                                    <span class="dashicons <?php echo esc_attr($service_data->icon); ?>"></span>
                                                <?php else : ?>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <p class="icon-preview-label"><?php _e('Current Icon', 'mobooking'); ?></p>
                                        </div>
                                        
                                        <div class="icon-grid">
                                            <p class="icon-grid-label"><?php _e('Choose an icon:', 'mobooking'); ?></p>
                                            <div class="icon-options">
                                                <?php foreach ($available_icons as $icon_class => $icon_name) : ?>
                                                    <div class="icon-option <?php echo ($service_data && $service_data->icon === $icon_class) ? 'selected' : ''; ?>" 
                                                         data-icon="<?php echo esc_attr($icon_class); ?>" title="<?php echo esc_attr($icon_name); ?>">
                                                        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="field-group">
                                    <label for="service-image" class="field-label"><?php _e('Custom Image', 'mobooking'); ?></label>
                                    <div class="image-upload">
                                        <div class="image-preview-container">
                                            <div class="image-preview">
                                                <?php if ($service_data && !empty($service_data->image_url)) : ?>
                                                    <img src="<?php echo esc_url($service_data->image_url); ?>" alt="<?php echo esc_attr($service_data->name); ?>">
                                                <?php else : ?>
                                                    <div class="image-placeholder">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                                            <circle cx="9" cy="9" r="2"/>
                                                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                                        </svg>
                                                        <span><?php _e('No image selected', 'mobooking'); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="image-controls">
                                            <input type="url" id="service-image" name="image_url" class="form-control" 
                                                   value="<?php echo $service_data ? esc_attr($service_data->image_url) : ''; ?>" 
                                                   placeholder="<?php _e('Image URL or select from media library', 'mobooking'); ?>">
                                            <button type="button" class="btn-secondary select-image-btn">
                                                <?php _e('Select Image', 'mobooking'); ?>
                                            </button>
                                        </div>
                                        
                                        <p class="field-help"><?php _e('Recommended size: 400x300 pixels. Images will be optimized automatically.', 'mobooking'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Options Tab -->
                <div id="options" class="tab-pane <?php echo $active_tab === 'options' ? 'active' : ''; ?>" role="tabpanel">
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-header-content">
                                <h3 class="section-title"><?php _e('Service Options', 'mobooking'); ?></h3>
                                <p class="section-description"><?php _e('Add customizable options to let customers personalize this service', 'mobooking'); ?></p>
                            </div>
                            
                            <button type="button" id="add-option-btn" class="btn-primary" 
                                    <?php echo !$service_id ? 'disabled title="' . esc_attr__('Save the service first to add options', 'mobooking') . '"' : ''; ?>>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                <?php _e('Add Option', 'mobooking'); ?>
                            </button>
                        </div>
                        
                        <div id="service-options-container" class="service-options-container">
                            <!-- Options will be loaded via JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <div class="form-actions-left">
                        <?php if ($current_view === 'edit') : ?>
                            <button type="button" class="btn-danger delete-service-btn" data-id="<?php echo esc_attr($service_data->id); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m3 6 3 18h12l3-18"></path>
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                </svg>
                                <?php _e('Delete Service', 'mobooking'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions-right">
                        <button type="submit" id="save-service-button" class="btn-primary btn-large">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21V13H7V21M7 3V8H15M19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16L21 8V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21Z"/>
                            </svg>
                            <span class="btn-text">
                                <?php echo $current_view === 'edit' ? __('Update Service', 'mobooking') : __('Create Service', 'mobooking'); ?>
                            </span>
                            <span class="btn-loading">
                                <span class="spinner"></span>
                                <?php _e('Saving...', 'mobooking'); ?>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Keep existing modals -->
<!-- Option Modal -->
<div id="option-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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