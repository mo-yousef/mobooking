<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get services
$services_manager = new \MoBooking\Services\ServiceManager();
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
wp_enqueue_script('mobooking-service-options-manager', MOBOOKING_URL . '/assets/js/service-options-manager.js', array('jquery', 'jquery-ui-sortable'), MOBOOKING_VERSION, true);
wp_enqueue_media(); // Enable WordPress Media Uploader

// Pass data to JavaScript
wp_localize_script('mobooking-service-options-manager', 'mobooking_data', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking-service-nonce'),
    'home_url' => home_url(),
    'dashboard_url' => home_url('/dashboard/services/'),
    'labels' => array(
        'add_new_service' => __('Add New Service', 'mobooking'),
        'edit_service' => __('Edit Service', 'mobooking'),
        'success_save' => __('Service saved successfully', 'mobooking'),
        'success_delete' => __('Service deleted successfully', 'mobooking'),
        'error_loading' => __('Error loading service data', 'mobooking'),
        'confirm_delete' => __('Are you sure you want to delete this service? This action cannot be undone.', 'mobooking'),
        'option_saved' => __('Option saved successfully', 'mobooking'),
        'option_deleted' => __('Option deleted successfully', 'mobooking'),
        'option_required' => __('Option name is required', 'mobooking'),
        'choice_required' => __('At least one choice with a value is required', 'mobooking'),
        'options_order' => __('Options order updated', 'mobooking'),
        'at_least_one' => __('You must have at least one choice', 'mobooking'),
    )
));
?>

<div class="dashboard-section services-section">
    <div class="section-header">
        <h2 class="section-title"><?php _e('Your Services', 'mobooking'); ?></h2>
        
        <div class="top-actions">
            <button type="button" class="button add-new-service">
                <span class="dashicons dashicons-plus"></span> <?php _e('Add New Service', 'mobooking'); ?>
            </button>
            
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
                        <button type="button" class="button button-secondary edit-service" data-id="<?php echo esc_attr($service->id); ?>">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'mobooking'); ?>
                        </button>
                        
                        <button type="button" class="button button-secondary delete-service" data-id="<?php echo esc_attr($service->id); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Unified Service Editor Modal -->
<div id="service-editor-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content unified-editor">
        <span class="modal-close">&times;</span>
        
        <h2 id="modal-title"><?php _e('Service Details', 'mobooking'); ?></h2>
        
        <div class="unified-tabs">
            <button type="button" class="tab-button active" data-tab="basic-info"><?php _e('Basic Info', 'mobooking'); ?></button>
            <button type="button" class="tab-button" data-tab="presentation"><?php _e('Presentation', 'mobooking'); ?></button>
            <button type="button" class="tab-button" data-tab="options"><?php _e('Service Options', 'mobooking'); ?></button>
        </div>
        
        <form id="unified-service-form">
            <input type="hidden" id="service-id" name="id" value="">
            
            <div class="tab-content">
                <!-- Basic Info Tab -->
                <div class="tab-pane active" id="basic-info">
                    <div class="form-group">
                        <label for="service-name"><?php _e('Service Name', 'mobooking'); ?></label>
                        <input type="text" id="service-name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                        <textarea id="service-description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="service-price"><?php _e('Base Price', 'mobooking'); ?></label>
                            <input type="number" id="service-price" name="price" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group half">
                            <label for="service-duration"><?php _e('Duration (minutes)', 'mobooking'); ?></label>
                            <input type="number" id="service-duration" name="duration" min="15" step="15" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="service-category"><?php _e('Category', 'mobooking'); ?></label>
                            <select id="service-category" name="category">
                                <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                <?php foreach ($categories as $slug => $name) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group half">
                            <label for="service-status"><?php _e('Status', 'mobooking'); ?></label>
                            <select id="service-status" name="status">
                                <option value="active"><?php _e('Active', 'mobooking'); ?></option>
                                <option value="inactive"><?php _e('Inactive', 'mobooking'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Presentation Tab -->
                <div class="tab-pane" id="presentation">
                    <div class="form-group">
                        <label for="service-image"><?php _e('Image URL', 'mobooking'); ?></label>
                        <div class="image-upload-container">
                            <input type="text" id="service-image" name="image_url" placeholder="https://...">
                            <button type="button" class="button select-image"><?php _e('Select', 'mobooking'); ?></button>
                        </div>
                        <div class="image-preview"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-icon"><?php _e('Icon', 'mobooking'); ?></label>
                        <select id="service-icon" name="icon">
                            <option value=""><?php _e('None', 'mobooking'); ?></option>
                            <option value="dashicons-admin-home"><?php _e('Home', 'mobooking'); ?></option>
                            <option value="dashicons-admin-tools"><?php _e('Tools', 'mobooking'); ?></option>
                            <option value="dashicons-bucket"><?php _e('Bucket', 'mobooking'); ?></option>
                            <option value="dashicons-hammer"><?php _e('Hammer', 'mobooking'); ?></option>
                            <option value="dashicons-art"><?php _e('Paintbrush', 'mobooking'); ?></option>
                            <option value="dashicons-building"><?php _e('Building', 'mobooking'); ?></option>
                            <option value="dashicons-businesswoman"><?php _e('Person', 'mobooking'); ?></option>
                            <option value="dashicons-car"><?php _e('Car', 'mobooking'); ?></option>
                            <option value="dashicons-pets"><?php _e('Pets', 'mobooking'); ?></option>
                            <option value="dashicons-palmtree"><?php _e('Plant', 'mobooking'); ?></option>
                        </select>
                        <div class="icon-preview-container">
                            <div class="icon-preview"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Options Tab -->
                <div class="tab-pane" id="options">
                    <div class="options-manager">
                        <div class="options-toolbar">
                            <button type="button" class="button add-new-option">
                                <span class="dashicons dashicons-plus"></span> <?php _e('Add Option', 'mobooking'); ?>
                            </button>
                            <div class="options-search">
                                <input type="text" placeholder="<?php _e('Search options...', 'mobooking'); ?>" id="options-search">
                            </div>
                        </div>
                        
                        <div class="options-list-container">
                            <div class="options-list">
                                <div class="options-list-empty"><?php _e('No options configured yet. Add your first option to customize this service.', 'mobooking'); ?></div>
                                <!-- Options will be listed here -->
                            </div>
                        </div>
                        
                        <div class="option-editor">
                            <div class="no-option-selected">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <p><?php _e('Select an option from the list or add a new one to customize your service.', 'mobooking'); ?></p>
                            </div>
                            
                            <div class="option-form-container" style="display: none;">
                                <div class="option-form-header">
                                    <h3><?php _e('Option Details', 'mobooking'); ?></h3>
                                </div>
                                
                                <div class="option-form-fields">
                                    <input type="hidden" id="option-id" name="option_id" value="">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="option-name"><?php _e('Option Name', 'mobooking'); ?></label>
                                            <input type="text" id="option-name" name="option_name" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group half">
                                            <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
                                            <select id="option-type" name="option_type" required>
                                                <option value=""><?php _e('Select Type', 'mobooking'); ?></option>
                                                <?php foreach ($option_types as $type => $label) : ?>
                                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group half">
                                            <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                                            <select id="option-required" name="option_required">
                                                <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                                                <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                                        <textarea id="option-description" name="option_description" rows="2"></textarea>
                                    </div>
                                    
                                    <!-- Dynamic fields based on option type -->
                                    <div class="dynamic-fields">
                                        <!-- Placeholder for dynamic fields -->
                                    </div>
                                    
                                    <div class="form-row price-impact-section">
                                        <div class="form-group half">
                                            <label for="option-price-type"><?php _e('Price Impact Type', 'mobooking'); ?></label>
                                            <select id="option-price-type" name="option_price_type">
                                                <?php foreach ($price_types as $type => $label) : ?>
                                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group half price-impact-value">
                                            <label for="option-price-impact"><?php _e('Price Value', 'mobooking'); ?></label>
                                            <input type="number" id="option-price-impact" name="option_price_impact" step="0.01" value="0">
                                        </div>
                                    </div>
                                    
                                    <div class="option-actions">
                                        <button type="button" class="button button-danger delete-option" style="display: none;"><?php _e('Delete Option', 'mobooking'); ?></button>
                                        <div class="spacer"></div>
                                        <button type="button" class="button button-secondary cancel-option"><?php _e('Cancel', 'mobooking'); ?></button>
                                        <button type="button" class="button button-primary save-option"><?php _e('Save Option', 'mobooking'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="button button-secondary cancel-service"><?php _e('Cancel', 'mobooking'); ?></button>
                <button type="submit" class="button button-primary save-service"><?php _e('Save Service', 'mobooking'); ?></button>
            </div>
            
            <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        
        <h2><?php _e('Delete Service', 'mobooking'); ?></h2>
        
        <p><?php _e('Are you sure you want to delete this service? This action cannot be undone.', 'mobooking'); ?></p>
        <p class="warning"><?php _e('Warning: Deleting this service will also remove all associated options and customizations.', 'mobooking'); ?></p>
        
        <div class="form-actions">
            <button type="button" class="button button-secondary cancel-delete"><?php _e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="button button-danger confirm-delete" data-id=""><?php _e('Delete Service', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<!-- Notification System -->
<div id="mobooking-notification" style="display: none;"></div>


<style>
/* Enhanced service-options.css */
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

  --shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
  --radius: 6px;
  --transition: all 0.2s ease-in-out;
}

/* Unified Editor Styling */
.unified-editor {
  max-width: 950px !important;
  padding: 0;
  overflow: hidden;
}

.unified-tabs {
  display: flex;
  background-color: #f8fafd;
  border-bottom: 1px solid var(--border-color);
}

.unified-tabs .tab-button {
  background: none;
  border: none;
  padding: 1rem 1.5rem;
  cursor: pointer;
  font-weight: 500;
  color: var(--text-light);
  transition: var(--transition);
  border-bottom: 2px solid transparent;
}

.unified-tabs .tab-button:hover {
  color: var(--primary-color);
  background-color: rgba(0, 0, 0, 0.02);
}

.unified-tabs .tab-button.active {
  color: var(--primary-color);
  border-bottom: 2px solid var(--primary-color);
}

.tab-content {
  padding: 1.5rem;
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
}

/* Options Manager */
.options-manager {
  display: grid;
  grid-template-columns: 280px 1fr;
  grid-template-rows: auto 1fr;
  gap: 1rem;
  height: 450px;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
}

.options-toolbar {
  grid-column: 1;
  grid-row: 1;
  padding: 0.75rem;
  border-bottom: 1px solid var(--border-color);
  background-color: #f8fafd;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.options-search {
  flex: 1;
}

.options-search input {
  width: 100%;
  padding: 0.4rem 0.75rem;
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
  font-size: 0.875rem;
  background-color: white;
}

.options-list-container {
  grid-column: 1;
  grid-row: 2;
  border-right: 1px solid var(--border-color);
  overflow-y: auto;
}

.options-list {
  padding: 0.75rem;
}

.option-editor {
  grid-column: 2;
  grid-row: 1 / span 2;
  padding: 1rem;
  overflow-y: auto;
  position: relative;
}

/* Option Items */
.option-item {
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.option-item:hover {
  border-color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: 0 3px 5px rgba(0, 0, 0, 0.05);
}

.option-item.active {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 1px var(--primary-color);
  background-color: var(--primary-light);
}

.option-item.sorting {
  opacity: 0.8;
  transform: scale(1.02);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.option-drag-handle {
  width: 24px;
  background-color: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  border-right: 1px solid var(--border-color);
}

.option-drag-handle:active {
  cursor: grabbing;
}

.option-drag-handle .dashicons {
  color: var(--text-light);
  font-size: 14px;
}

.option-content {
  padding: 0.75rem;
  flex: 1;
}

.option-name {
  font-weight: 600;
  display: block;
  margin-bottom: 0.25rem;
  color: var(--text-color);
}

.option-meta {
  font-size: 0.75rem;
  color: var(--text-light);
  display: flex;
  gap: 0.5rem;
}

.option-type {
  display: inline-block;
}

.option-required {
  display: inline-block;
  background-color: rgba(33, 150, 243, 0.1);
  color: var(--primary-color);
  padding: 0 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
}

.option-preview {
  border-left: 1px solid var(--border-color);
  width: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background-color: #f9fafc;
}

/* Option previews */
.preview-checkbox,
.preview-select,
.preview-radio,
.preview-number,
.preview-text,
.preview-textarea {
  font-size: 0.75rem;
  color: #999;
  text-align: center;
}

.preview-number {
  font-family: monospace;
}

.radio-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 1px solid #999;
  position: relative;
}

.radio-dot:after {
  content: '';
  position: absolute;
  width: 4px;
  height: 4px;
  background-color: #999;
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

/* Option Form Container */
.option-form-container {
  background-color: white;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  border: 1px solid var(--border-color);
}

.option-form-header {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border-color);
  background-color: #f8fafd;
}

.option-form-header h3 {
  margin: 0;
  font-size: 1rem;
}

.option-form-fields {
  padding: 1rem;
}

.option-actions {
  padding-top: 1rem;
  margin-top: 1rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.option-actions .spacer {
  flex: 1;
}

/* No Options Message */
.options-list-empty {
  color: var(--text-light);
  font-style: italic;
  text-align: center;
  padding: 1rem;
  background-color: rgba(0, 0, 0, 0.02);
  border-radius: var(--radius);
}

.no-option-selected {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--text-light);
  text-align: center;
}

.no-option-selected .dashicons {
  font-size: 2.5rem;
  opacity: 0.2;
  margin-bottom: 1rem;
}

/* Choices Container */
.choices-container {
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 1rem;
}

.choices-header {
  display: flex;
  background-color: #f5f7fa;
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

.choice-row.ui-sortable-helper {
  background-color: #f9fbfd;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.choice-row-placeholder {
  height: 38px;
  background-color: rgba(0, 0, 0, 0.02);
  border: 1px dashed var(--border-color);
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
  width: 100%;
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

/* Sortable placeholder */
.option-item-placeholder {
  border: 1px dashed var(--primary-color);
  background-color: rgba(76, 175, 80, 0.05);
  border-radius: var(--radius);
  margin-bottom: 0.75rem;
  height: 40px;
}

.sortable-enabled .option-item {
  cursor: move;
}

/* Field hint */
.field-hint {
  font-size: 0.75rem;
  color: var(--text-light);
  margin-top: 0.25rem;
}

/* Modal styling */
.mobooking-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease;
}

.mobooking-modal.loading {
  cursor: wait;
}

.mobooking-modal.loading::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 1s linear infinite;
  z-index: 100001;
}

.mobooking-modal:not([style*="display: none"]) {
  opacity: 1;
  visibility: visible;
}

.modal-content {
  background-color: var(--card-bg);
  border-radius: var(--radius);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  padding: 1.5rem;
  transform: translateY(20px);
  transition: transform 0.3s ease;
  animation: fadeInUp 0.3s ease forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes spin {
  to {
    transform: translate(-50%, -50%) rotate(360deg);
  }
}

.modal-close {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  font-size: 1.5rem;
  cursor: pointer;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: var(--text-light);
  transition: var(--transition);
  z-index: 2;
}

.modal-close:hover {
  color: var(--danger-color);
  background-color: rgba(0, 0, 0, 0.05);
}

/* Notification System */
#mobooking-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 12px 20px;
  border-radius: 6px;
  color: white;
  font-size: 14px;
  z-index: 100001;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
  animation: slide-in-right 0.3s ease-out forwards;
  max-width: 300px;
}

@keyframes slide-in-right {
  from {
    transform: translateX(50px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.notification-success {
  background-color: var(--success-color);
}

.notification-error {
  background-color: var(--danger-color);
}

.notification-info {
  background-color: var(--info-color);
}

.notification-warning {
  background-color: var(--warning-color);
}

/* Form specific styling */
.form-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group.half {
  flex: 1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

/* Image preview */
.image-preview {
  margin-top: 0.5rem;
}

.image-preview img {
  max-width: 100%;
  max-height: 150px;
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
}

.icon-preview-container {
  margin-top: 0.5rem;
}

.icon-preview {
  display: inline-flex;
  width: 40px;
  height: 40px;
  background-color: var(--primary-color);
  color: white;
  border-radius: 6px;
  align-items: center;
  justify-content: center;
}

/* Responsive styles */
@media (max-width: 768px) {
  .options-manager {
    grid-template-columns: 1fr;
    grid-template-rows: auto auto 1fr;
  }
  
  .options-list-container {
    grid-column: 1;
    grid-row: 2;
    border-right: none;
    border-bottom: 1px solid var(--border-color);
    max-height: 200px;
  }
  
  .option-editor {
    grid-column: 1;
    grid-row: 3;
  }
  
  .form-row {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-group.half {
    width: 100%;
  }
}
</style>