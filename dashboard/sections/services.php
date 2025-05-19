
<style>
/* Enhanced Services Styling */
:root {
    --primary-color: var(--mobooking-primary-color, #2863ec);
    --primary-dark: var(--mobooking-primary-color-dark, #1f4fbc);
    --primary-light: var(--mobooking-primary-color-light, #e6f0ff);
    --text-color: #020817;
    --text-light: #64748b;
    --bg-color: #f4f6f8;
    --card-bg: #ffffff;
    --border-color: #e3e8f0;
    --success-color: #43a047;
    --warning-color: #fb8c00;
    --danger-color: #e53935;
    --info-color: #1e88e5;
    --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --radius: 10px;
    --transition: all 0.25s ease-in-out;
}

/* Updated Services specific styling */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.service-card {
    background-color: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 1px solid var(--border-color);
}

.service-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: var(--primary-color);
}

.service-header {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    background-color: rgba(0, 0, 0, 0.02);
}

.service-image {
    width: 50px;
    height: 50px;
    background-size: cover;
    background-position: center;
    border-radius: 6px;
    margin-right: 0.75rem;
}

.service-icon {
    width: 50px;
    height: 50px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
}

.service-icon .dashicons {
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.service-title {
    flex: 1;
}

.service-title h3 {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.3;
}

.service-status-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    margin-left: 0.75rem;
}

.service-price {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.service-status {
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 50px;
}

.service-status-active {
    background-color: rgba(76, 175, 80, 0.15);
    color: var(--success-color);
}

.service-status-inactive {
    background-color: rgba(158, 158, 158, 0.15);
    color: var(--text-light);
}

.service-body {
    padding: 1rem;
    flex-grow: 1;
}

.service-meta {
    margin-bottom: 0.75rem;
    color: var(--text-light);
    font-size: 0.875rem;
    display: flex;
    align-items: center;
}

.service-meta .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
    vertical-align: middle;
    margin-right: 0.25rem;
}

.service-duration {
    margin-right: 1rem;
}

.service-options-badge {
    background-color: rgba(33, 150, 243, 0.15);
    color: var(--info-color);
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 0.75rem;
}

.service-description {
    font-size: 0.9375rem;
    line-height: 1.5;
    color: var(--text-color);
}

.service-actions {
    padding: 1rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
}

.service-actions button {
    padding: 0.5rem;
    font-size: 0.875rem;
}

.service-actions button .dashicons {
    margin-right: 0.25rem;
}

.top-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.filter-controls {
    display: flex;
    align-items: center;
}

.filter-controls label {
    margin-right: 0.5rem;
    margin-bottom: 0;
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

.modal-lg {
    max-width: 900px;
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    transition: var(--transition);
    line-height: 1;
    width: 30px;
    height: 30px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: var(--text-light);
    background-color: rgba(0, 0, 0, 0.05);
}

.modal-close:hover {
    color: var(--danger-color);
    background-color: rgba(0, 0, 0, 0.1);
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group.half {
    flex: 1;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.form-actions .spacer {
    flex-grow: 1;
}

/* Tab styling */
.modal-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

.tab-button {
    background: none;
    border: none;
    padding: 0.75rem 1.25rem;
    margin-right: 0.5rem;
    cursor: pointer;
    color: var(--text-light);
    position: relative;
    font-weight: 500;
}

.tab-button.active {
    color: var(--primary-color);
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

.tab-content {
    margin-bottom: 1rem;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Image preview styling */
.image-upload-container {
    display: flex;
    gap: 0.5rem;
}

.image-preview {
    margin-top: 0.5rem;
    max-width: 100%;
}

.image-preview img {
    max-width: 200px;
    max-height: 100px;
    border-radius: var(--radius);
    object-fit: cover;
}

.icon-preview-container {
    margin-top: 0.5rem;
    text-align: center;
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

.icon-preview .dashicons {
    font-size: 1.25rem;
    width: 1.25rem;
    height: 1.25rem;
}

/* Options modal styling */
.options-container {
    display: flex;
    min-height: 400px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    overflow: hidden;
    margin-top: 1.5rem;
}

.options-sidebar {
    width: 250px;
    border-right: 1px solid var(--border-color);
    padding: 1rem;
    background-color: #f8fafd;
    display: flex;
    flex-direction: column;
}

.add-new-option {
    width: 100%;
    margin-bottom: 1rem;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

.add-new-option:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.add-new-option .dashicons {
    margin-right: 0.5rem;
}

.options-list {
    flex: 1;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.options-list-empty {
    color: var(--text-light);
    font-style: italic;
    text-align: center;
    padding: 2rem 1rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: var(--radius);
}

.option-item {
    background-color: white;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    margin-bottom: 0.75rem;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    overflow: hidden;
}

.option-item:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
}

.option-item.active {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(40, 99, 236, 0.15);
    background-color: var(--primary-light);
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
    background-color: #f9fafc;
    position: relative;
}

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

.options-content {
    flex: 1;
    padding: 1.5rem;
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

.button-link {
    background: none;
    border: none;
    padding: 0;
    font: inherit;
    cursor: pointer;
    box-shadow: none;
}

/* Option sortable placeholder */
.option-item-placeholder {
    border: 1px dashed var(--primary-color);
    background-color: rgba(76, 175, 80, 0.05);
    border-radius: var(--radius);
    margin-bottom: 0.75rem;
    height: 56px;
}

.sortable-enabled .option-item {
    cursor: move;
}

/* Category badges */
.category-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 50px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.category-residential {
    background-color: rgba(33, 150, 243, 0.15);
    color: var(--info-color);
}

.category-commercial {
    background-color: rgba(156, 39, 176, 0.15);
    color: #9c27b0;
}

.category-special {
    background-color: rgba(255, 193, 7, 0.15);
    color: #ffc107;
}

/* No items state */
.no-items {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-light);
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: var(--radius);
    border: 1px dashed var(--border-color);
}

.no-items .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* Notification system */
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

/* Delete modal warning */
.warning {
    color: var(--danger-color);
    font-weight: 500;
}

/* Loading state */
.loading {
    position: relative;
}

.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.7);
    z-index: 1;
    border-radius: var(--radius);
}

.loading::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 30px;
    height: 30px;
    border: 3px solid var(--primary-color);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    z-index: 2;
}

@keyframes spin {
    to {
        transform: translate(-50%, -50%) rotate(360deg);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .top-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .service-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .service-actions button {
        width: 100%;
    }
    
    .options-container {
        flex-direction: column;
    }
    
    .options-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
        max-height: 200px;
    }
    
    .modal-content {
        width: 95%;
        padding: 1rem;
    }
}
</style> 
<?php
//Prevent direct access
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
    'custom' => __('Custom Formula', 'mobooking'),
    'none' => __('No Price Impact', 'mobooking')
);

// Get total count
$total_services = count($services);
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
                        
                        <button type="button" class="button button-secondary manage-options" data-id="<?php echo esc_attr($service->id); ?>" data-name="<?php echo esc_attr($service->name); ?>">
                            <span class="dashicons dashicons-admin-generic"></span> <?php _e('Options', 'mobooking'); ?>
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

<!-- Add/Edit Service Modal -->
<div id="service-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        
        <h2 id="modal-title"><?php _e('Add New Service', 'mobooking'); ?></h2>
        
        <form id="service-form">
            <div class="modal-tabs">
                <button type="button" class="tab-button active" data-tab="basic-info"><?php _e('Basic Info', 'mobooking'); ?></button>
                <button type="button" class="tab-button" data-tab="presentation"><?php _e('Presentation', 'mobooking'); ?></button>
            </div>
            
            <div class="tab-content">
                <!-- Basic Info Tab -->
                <div class="tab-pane active" id="basic-info">
                    <input type="hidden" id="service-id" name="id" value="">
                    
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
            </div>
            
            <div class="form-actions">
                <button type="button" class="button button-secondary cancel-service"><?php _e('Cancel', 'mobooking'); ?></button>
                <button type="submit" class="button button-primary save-service"><?php _e('Save Service', 'mobooking'); ?></button>
            </div>
            
            <?php wp_nonce_field('mobooking-service-nonce', 'service_nonce'); ?>
        </form>
    </div>
</div>

<!-- Service Options Modal -->
<div id="options-modal" class="mobooking-modal" style="display: none;">
    <div class="modal-content modal-lg">
        <span class="modal-close">&times;</span>
        
        <h2 id="options-modal-title">
            <?php _e('Manage Service Options', 'mobooking'); ?> - <span class="service-name"></span>
        </h2>
        
        <div class="options-container">
            <div class="options-sidebar">
                <button type="button" class="button add-new-option">
                    <span class="dashicons dashicons-plus"></span> <?php _e('Add Option', 'mobooking'); ?>
                </button>
                
                <div class="options-list">
                    <div class="options-list-empty"><?php _e('No options configured yet.', 'mobooking'); ?></div>
                    <!-- Options will be listed here -->
                </div>
            </div>
            
            <div class="options-content">
                <form id="option-form" style="display: none;">
                    <input type="hidden" id="option-id" name="id" value="">
                    <input type="hidden" id="option-service-id" name="service_id" value="">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="option-name"><?php _e('Option Name', 'mobooking'); ?></label>
                            <input type="text" id="option-name" name="name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
                            <select id="option-type" name="type" required>
                                <option value=""><?php _e('Select Type', 'mobooking'); ?></option>
                                <?php foreach ($option_types as $type => $label) : ?>
                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group half">
                            <label for="option-required"><?php _e('Required?', 'mobooking'); ?></label>
                            <select id="option-required" name="is_required">
                                <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                                <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="option-description"><?php _e('Description', 'mobooking'); ?></label>
                        <textarea id="option-description" name="description" rows="2"></textarea>
                    </div>
                    
                    <!-- Dynamic fields based on option type -->
                    <div class="dynamic-fields">
                        <!-- Placeholder for dynamic fields -->
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
                    
                    <div class="form-actions">
                        <button type="button" class="button button-danger delete-option" style="display: none;"><?php _e('Delete', 'mobooking'); ?></button>
                        <div class="spacer"></div>
                        <button type="button" class="button button-secondary cancel-option"><?php _e('Cancel', 'mobooking'); ?></button>
                        <button type="submit" class="button button-primary save-option"><?php _e('Save Option', 'mobooking'); ?></button>
                    </div>
                    
                    <?php 
                    // Add security nonces
                    $option_nonce = wp_create_nonce('mobooking-option-nonce');
                    echo '<input type="hidden" name="option_nonce" value="' . esc_attr($option_nonce) . '">';
                    echo '<input type="hidden" name="nonce" value="' . esc_attr(wp_create_nonce('mobooking-service-nonce')) . '">';
                    ?>
                </form>
                
                <div class="no-option-selected">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <p><?php _e('Select an option from the list or add a new one to customize your service.', 'mobooking'); ?></p>
                </div>
            </div>
        </div>
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

<script>
jQuery(document).ready(function($) {
    // Ensure mobooking_services is defined
    if (typeof mobooking_services === 'undefined') {
        window.mobooking_services = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-service-nonce'); ?>'
        };
    }
    
    // Notification system
    function showNotification(message, type = 'success') {
        const notification = $('#mobooking-notification');
        notification.attr('class', '').addClass('notification-' + type);
        notification.html(message);
        notification.fadeIn(300).delay(3000).fadeOut(300);
    }
    
    // Initialize the media uploader
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
            title: '<?php _e('Choose Image', 'mobooking'); ?>',
            button: {
                text: '<?php _e('Select', 'mobooking'); ?>'
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
    
    // Preview selected icon
    $('#service-icon').on('change', function() {
        var iconClass = $(this).val();
        if (iconClass) {
            $('.icon-preview').html('<span class="dashicons ' + iconClass + '"></span>');
        } else {
            $('.icon-preview').empty();
        }
    });
    
    // Tab switching
    $('.tab-button').on('click', function() {
        var targetTab = $(this).data('tab');
        
        // Update active tab button
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Show target tab content
        $('.tab-pane').removeClass('active');
        $('#' + targetTab).addClass('active');
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
    
    // Open add new service modal
    $('.add-new-service').on('click', function() {
        $('#modal-title').text('<?php _e('Add New Service', 'mobooking'); ?>');
        $('#service-form')[0].reset();
        $('#service-id').val('');
        $('.image-preview').empty();
        $('.icon-preview').empty();
        
        // Reset tabs
        $('.tab-button[data-tab="basic-info"]').click();
        
        $('#service-modal').fadeIn();
    });
    
    // Open edit service modal
    $(document).on('click', '.edit-service', function() {
        var serviceId = $(this).data('id');
        
        // Set modal title
        $('#modal-title').text('<?php _e('Edit Service', 'mobooking'); ?>');
        
        // Show loading indicator
        $('#service-modal').addClass('loading');
        $('#service-modal').fadeIn();
        
        // Get service data via AJAX
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_service',
                id: serviceId,
                nonce: mobooking_services.nonce
            },
            success: function(response) {
                if (response.success) {
                    var service = response.data.service;
                    
                    // Fill the form with service data
                    $('#service-id').val(service.id);
                    $('#service-name').val(service.name);
                    $('#service-description').val(service.description);
                    $('#service-price').val(service.price);
                    $('#service-duration').val(service.duration);
                    $('#service-category').val(service.category);
                    $('#service-icon').val(service.icon);
                    $('#service-image').val(service.image_url || '');
                    $('#service-status').val(service.status || 'active');
                    
                    // Preview image if available
                    if (service.image_url) {
                        $('.image-preview').html('<img src="' + service.image_url + '" alt="">');
                    } else {
                        $('.image-preview').empty();
                    }
                    
                    // Preview icon if available
                    if (service.icon) {
                        $('.icon-preview').html('<span class="dashicons ' + service.icon + '"></span>');
                    } else {
                        $('.icon-preview').empty();
                    }
                    
                    // Reset tabs
                    $('.tab-button[data-tab="basic-info"]').click();
                } else {
                    showNotification(response.data.message || 'Error loading service data', 'error');
                }
                
                // Remove loading indicator
                $('#service-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Error loading service data. Please try again.', 'error');
                $('#service-modal').removeClass('loading');
            }
        });
    });
    
    // Submit service form
    $('#service-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form data
        var serviceName = $('#service-name').val().trim();
        var servicePrice = parseFloat($('#service-price').val());
        var serviceDuration = parseInt($('#service-duration').val());
        
        if (!serviceName) {
            showNotification('Service name is required', 'error');
            $('#service-name').focus();
            return;
        }
        
        if (isNaN(servicePrice) || servicePrice <= 0) {
            showNotification('Service price must be greater than zero', 'error');
            $('#service-price').focus();
            return;
        }
        
        if (isNaN(serviceDuration) || serviceDuration < 15) {
            showNotification('Service duration must be at least 15 minutes', 'error');
            $('#service-duration').focus();
            return;
        }
        
        // Show loading indicator
        $('#service-modal').addClass('loading');
        
        // Prepare form data
        var formData = $(this).serialize();
        formData += '&action=mobooking_save_service&nonce=' + mobooking_services.nonce;
        
        // Submit via AJAX
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Service saved successfully', 'success');
                    
                    // Reload page to show updated services
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error saving service', 'error');
                    $('#service-modal').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Error saving service. Please try again.', 'error');
                $('#service-modal').removeClass('loading');
            }
        });
    });
    
    // Open delete confirmation modal
    $(document).on('click', '.delete-service', function() {
        var serviceId = $(this).data('id');
        $('.confirm-delete').data('id', serviceId);
        $('#delete-modal').fadeIn();
    });
    
    // Confirm delete service
    $('.confirm-delete').on('click', function() {
        var serviceId = $(this).data('id');
        
        // Show loading indicator
        $('#delete-modal').addClass('loading');
        
        // Submit delete request via AJAX
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_delete_service',
                id: serviceId,
                nonce: mobooking_services.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Service deleted successfully', 'success');
                    
                    // Reload page to show updated services
                    location.reload();
                } else {
                    showNotification(response.data.message || 'Error deleting service', 'error');
                    $('#delete-modal').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Error deleting service. Please try again.', 'error');
                $('#delete-modal').removeClass('loading');
            }
        });
    });
    
    // ================ SERVICE OPTIONS MANAGEMENT ================
    
    // Open manage options modal
    $(document).on('click', '.manage-options', function() {
        var serviceId = $(this).data('id');
        var serviceName = $(this).data('name');
        
        // Set modal title and service info
        $('#options-modal-title .service-name').text(serviceName);
        
        // Set service ID in the form
        $('#option-service-id').val(serviceId);
        
        // Reset option form
        $('#option-form').hide();
        $('#option-id').val('');
        $('#option-form')[0].reset();
        $('.no-option-selected').show();
        
        // Show loading indicator
        $('#options-modal').addClass('loading');
        $('#options-modal').fadeIn();
        
        // Load service options
        loadServiceOptions(serviceId);
    });
    
    // Function to load service options
    function loadServiceOptions(serviceId) {
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_service_options',
                service_id: serviceId,
                nonce: mobooking_services.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Clear options list
                    $('.options-list').empty();
                    
                    if (!response.data.options || response.data.options.length === 0) {
                        $('.options-list').html(
                            '<div class="options-list-empty"><?php _e('No options configured yet. Add your first option to customize this service.', 'mobooking'); ?></div>'
                        );
                    } else {
                        // Populate options list
                        $.each(response.data.options, function(index, option) {
                            var optionItem = $('<div class="option-item" data-id="' + option.id + '" data-order="' + (option.display_order || index) + '">' +
                                '<div class="option-drag-handle">' +
                                '<span class="dashicons dashicons-menu"></span>' +
                                '</div>' +
                                '<div class="option-content">' +
                                '<span class="option-name">' + option.name + '</span>' +
                                '<div class="option-meta">' +
                                '<span class="option-type">' + getOptionTypeLabel(option.type) + '</span>' +
                                (option.is_required == 1 ? '<span class="option-required">Required</span>' : '') +
                                '</div>' +
                                '</div>' +
                                '<div class="option-preview">' +
                                generateOptionPreview(option) +
                                '</div>' +
                                '</div>');
                            
                            $('.options-list').append(optionItem);
                        });
                        
                        // Initialize sortable functionality for options
                        if (!$('.options-list').hasClass('ui-sortable') && response.data.options.length > 1) {
                            initOptionsSortable(serviceId);
                        }
                    }
                } else {
                    showNotification(response.data.message || 'Error loading options', 'error');
                }
                
                // Remove loading indicator
                $('#options-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Error loading service options. Please try again.', 'error');
                $('#options-modal').removeClass('loading');
            }
        });
    }
    
    // Helper function to get option type label
    function getOptionTypeLabel(type) {
        const labels = {
            'checkbox': 'Checkbox',
            'number': 'Number Input',
            'select': 'Dropdown',
            'text': 'Text Input',
            'textarea': 'Text Area',
            'radio': 'Radio Buttons',
            'quantity': 'Quantity'
        };
        
        return labels[type] || type;
    }
    
    // Helper function to generate option preview
    function generateOptionPreview(option) {
        let preview = '';
        
        switch (option.type) {
            case 'checkbox':
                preview = '<div class="preview-checkbox"><input type="checkbox" ' +
                    (option.default_value == '1' ? 'checked' : '') + ' disabled /></div>';
                break;
                
            case 'select':
                preview = '<div class="preview-select"><select disabled><option>Options...</option></select></div>';
                break;
                
            case 'radio':
                preview = '<div class="preview-radio"><span class="radio-dot"></span></div>';
                break;
                
            case 'number':
            case 'quantity':
                preview = '<div class="preview-number">123</div>';
                break;
                
            case 'text':
                preview = '<div class="preview-text">Text</div>';
                break;
                
            case 'textarea':
                preview = '<div class="preview-textarea">Text Area</div>';
                break;
                
            default:
                preview = '';
        }
        
        // Add price info if applicable
        if (option.price_impact && option.price_impact != 0 && option.price_type !== 'none') {
            const sign = option.price_impact > 0 ? '+' : '';
            let priceDisplay = '';
            
            if (option.price_type === 'percentage') {
                priceDisplay = sign + option.price_impact + '%';
            } else if (option.price_type === 'fixed') {
                priceDisplay = sign + ' + Math.abs(parseFloat(option.price_impact)).toFixed(2);
            } else if (option.price_type === 'multiply') {
                priceDisplay = '×' + option.price_impact;
            }
            
            if (priceDisplay) {
                preview += '<div class="price-indicator">' + priceDisplay + '</div>';
            }
        }
        
        return preview;
    }
    
    // Initialize sortable functionality for options
    function initOptionsSortable(serviceId) {
        $('.options-list').sortable({
            handle: '.option-drag-handle',
            placeholder: 'option-item-placeholder',
            axis: 'y',
            opacity: 0.8,
            tolerance: 'pointer',
            start: function(event, ui) {
                ui.item.addClass('sorting');
                ui.placeholder.height(ui.item.outerHeight());
            },
            stop: function(event, ui) {
                ui.item.removeClass('sorting');
            },
            update: function(event, ui) {
                updateOptionsOrder(serviceId);
            }
        }).addClass('sortable-enabled');
    }
    
    // Function to update options order
    function updateOptionsOrder(serviceId) {
        const orderData = [];
        
        $('.options-list .option-item').each(function(index) {
            orderData.push({
                id: $(this).data('id'),
                order: index
            });
        });
        
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_update_options_order',
                service_id: serviceId,
                order_data: JSON.stringify(orderData),
                nonce: mobooking_services.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Options order updated', 'success');
                } else {
                    showNotification(response.data.message || 'Error updating order', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Error updating options order', 'error');
            }
        });
    }
    
    // Add new option button
    $('.add-new-option').on('click', function() {
        // Reset form
        $('#option-id').val('');
        $('#option-form')[0].reset();
        
        // Set default values
        $('#option-price-type').val('fixed');
        $('#option-price-impact').val('0');
        $('#option-type').val('checkbox');
        
        // Generate empty dynamic fields for default type
        generateDynamicFields('checkbox');
        
        // Hide delete button for new options
        $('.delete-option').hide();
        
        // Show the form
        $('.no-option-selected').hide();
        $('#option-form').show();
        
        // Deselect any selected option
        $('.option-item').removeClass('active');
    });
    
    // Handle clicking on an option in the list
    $(document).on('click', '.option-item', function(e) {
        // Don't react if clicking on the drag handle
        if ($(e.target).hasClass('option-drag-handle') || $(e.target).closest('.option-drag-handle').length) {
            return;
        }
        
        const optionId = $(this).data('id');
        
        // Show loading indicator
        $('#options-modal').addClass('loading');
        
        // Get option data
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_service_option',
                id: optionId,
                nonce: mobooking_services.nonce
            },
            success: function(response) {
                if (response.success) {
                    const option = response.data.option;
                    
                    // Fill the form with option data
                    $('#option-id').val(option.id);
                    $('#option-name').val(option.name);
                    $('#option-type').val(option.type);
                    $('#option-required').val(option.is_required);
                    $('#option-description').val(option.description);
                    $('#option-price-type').val(option.price_type || 'fixed');
                    $('#option-price-impact').val(option.price_impact || 0);
                    
                    // Generate type-specific fields
                    generateDynamicFields(option.type, option);
                    
                    // Show delete button
                    $('.delete-option').show();
                    
                    // Show the form
                    $('.no-option-selected').hide();
                    $('#option-form').show();
                    
                    // Highlight selected option
                    $('.option-item').removeClass('active');
                    $(`.option-item[data-id="${optionId}"]`).addClass('active');
                } else {
                    showNotification(response.data.message || 'Error loading option', 'error');
                }
                
                // Remove loading indicator
                $('#options-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Error loading option data. Please try again.', 'error');
                $('#options-modal').removeClass('loading');
            }
        });
    });
    
    // Handle option type change
    $('#option-type').on('change', function() {
        const optionType = $(this).val();
        if (optionType) {
            generateDynamicFields(optionType);
        }
    });
    
    // Handle price type change
    $('#option-price-type').on('change', function() {
        updatePriceFields($(this).val());
    });
    
    // Function to update price fields based on selected price type
    function updatePriceFields(priceType) {
        const valueField = $('.price-impact-value');
        
        valueField.show();
        
        if (priceType === 'custom') {
            valueField.find('label').text('<?php _e('Formula', 'mobooking'); ?>');
            valueField.find('input').attr('type', 'text').attr('placeholder', 'price + (value * 5)');
        } else if (priceType === 'none') {
            valueField.hide();
        } else if (priceType === 'percentage') {
            valueField.find('label').text('<?php _e('Percentage (%)', 'mobooking'); ?>');
            valueField.find('input').attr('type', 'number').attr('placeholder', '10');
        } else if (priceType === 'multiply') {
            valueField.find('label').text('<?php _e('Multiplier', 'mobooking'); ?>');
            valueField.find('input').attr('type', 'number').attr('step', '0.1').attr('placeholder', '1.5');
        } else {
            valueField.find('label').text('<?php _e('Amount ($)', 'mobooking'); ?>');
            valueField.find('input').attr('type', 'number').attr('step', '0.01').attr('placeholder', '9.99');
        }
    }
    
    // Function to generate dynamic fields based on option type
    function generateDynamicFields(optionType, optionData) {
        const dynamicFields = $('.dynamic-fields');
        dynamicFields.empty();
        
        optionData = optionData || {};
        
        switch (optionType) {
            case 'checkbox':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e('Default Value', 'mobooking'); ?></label>' +
                            '<select id="option-default-value" name="default_value">' +
                                '<option value="0" ' + (optionData.default_value == '0' ? 'selected' : '') + '><?php _e('Unchecked', 'mobooking'); ?></option>' +
                                '<option value="1" ' + (optionData.default_value == '1' ? 'selected' : '') + '><?php _e('Checked', 'mobooking'); ?></option>' +
                            '</select>' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-label"><?php _e('Option Label', 'mobooking'); ?></label>' +
                            '<input type="text" id="option-label" name="option_label" value="' + (optionData.option_label || '') + '" placeholder="<?php _e('Check this box to add...', 'mobooking'); ?>">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'number':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-value"><?php _e('Minimum Value', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-min-value" name="min_value" value="' + (optionData.min_value !== null ? optionData.min_value : 0) + '">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-value"><?php _e('Maximum Value', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-max-value" name="max_value" value="' + (optionData.max_value !== null ? optionData.max_value : '') + '">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e('Default Value', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-default-value" name="default_value" value="' + (optionData.default_value || '') + '">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-placeholder"><?php _e('Placeholder', 'mobooking'); ?></label>' +
                            '<input type="text" id="option-placeholder" name="placeholder" value="' + (optionData.placeholder || '') + '">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-step"><?php _e('Step', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-step" name="step" value="' + (optionData.step || '1') + '" step="0.01">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-unit"><?php _e('Unit Label', 'mobooking'); ?></label>' +
                            '<input type="text" id="option-unit" name="unit" value="' + (optionData.unit || '') + '" placeholder="<?php _e('sq ft, hours, etc.', 'mobooking'); ?>">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'select':
            case 'radio':
                dynamicFields.append(
                    '<div class="form-group">' +
                        '<label><?php _e('Choices', 'mobooking'); ?></label>' +
                        '<div class="choices-container">' +
                            '<div class="choices-header">' +
                                '<div class="choice-value"><?php _e('Value', 'mobooking'); ?></div>' +
                                '<div class="choice-label"><?php _e('Label', 'mobooking'); ?></div>' +
                                '<div class="choice-price"><?php _e('Price', 'mobooking'); ?></div>' +
                                '<div class="choice-actions"></div>' +
                            '</div>' +
                            '<div class="choices-list"></div>' +
                            '<div class="add-choice-container">' +
                                '<button type="button" class="button add-choice"><?php _e('Add Choice', 'mobooking'); ?></button>' +
                            '</div>' +
                        '</div>' +
                        '<input type="hidden" id="option-choices" name="options">' +
                        '<p class="field-hint"><?php _e('Each choice has a value (used internally) and a label (displayed to customers).', 'mobooking'); ?></p>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="option-default-value"><?php _e('Default Value', 'mobooking'); ?></label>' +
                        '<input type="text" id="option-default-value" name="default_value" value="' + (optionData.default_value || '') + '">' +
                        '<p class="field-hint"><?php _e('Enter the value (not the label) of the default choice', 'mobooking'); ?></p>' +
                    '</div>'
                );
                
                // Parse existing options and populate choices
                if (optionData.options) {
                    const choices = parseOptionsString(optionData.options);
                    const choicesList = dynamicFields.find('.choices-list');
                    
                    if (choices.length === 0) {
                        // Add a blank choice if none exist
                        addChoiceRow(choicesList);
                    } else {
                        // Add each choice
                        choices.forEach(choice => {
                            addChoiceRow(choicesList, choice.value, choice.label, choice.price);
                        });
                    }
                    
                    // Update the hidden field
                    $('#option-choices').val(optionData.options);
                } else {
                    // Add a blank choice for new options
                    addChoiceRow(dynamicFields.find('.choices-list'));
                }
                
                // Make choices sortable
                initChoicesSortable();
                break;
                
            case 'text':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e('Default Value', 'mobooking'); ?></label>' +
                            '<input type="text" id="option-default-value" name="default_value" value="' + (optionData.default_value || '') + '">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-placeholder"><?php _e('Placeholder', 'mobooking'); ?></label>' +
                            '<input type="text" id="option-placeholder" name="placeholder" value="' + (optionData.placeholder || '') + '">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-length"><?php _e('Minimum Length', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-min-length" name="min_length" value="' + (optionData.min_length !== null ? optionData.min_length : '') + '" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-length"><?php _e('Maximum Length', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-max-length" name="max_length" value="' + (optionData.max_length !== null ? optionData.max_length : '') + '" min="0">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'textarea':
                dynamicFields.append(
                    '<div class="form-group">' +
                        '<label for="option-default-value"><?php _e('Default Value', 'mobooking'); ?></label>' +
                        '<textarea id="option-default-value" name="default_value" rows="2">' + (optionData.default_value || '') + '</textarea>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="option-placeholder"><?php _e('Placeholder', 'mobooking'); ?></label>' +
                        '<input type="text" id="option-placeholder" name="placeholder" value="' + (optionData.placeholder || '') + '">' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-rows"><?php _e('Rows', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-rows" name="rows" value="' + (optionData.rows || '3') + '" min="2">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-length"><?php _e('Maximum Length', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-max-length" name="max_length" value="' + (optionData.max_length !== null ? optionData.max_length : '') + '" min="0">' +
                        '</div>' +
                    '</div>'
                );
                break;
                
            case 'quantity':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-value"><?php _e('Minimum Quantity', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-min-value" name="min_value" value="' + (optionData.min_value !== null ? optionData.min_value : 0) + '" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-value"><?php _e('Maximum Quantity', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-max-value" name="max_value" value="' + (optionData.max_value !== null ? optionData.max_value : '') + '" min="0">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e('Default Quantity', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-default-value" name="default_value" value="' + (optionData.default_value || 0) + '" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-step"><?php _e('Step', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-step" name="step" value="' + (optionData.step || '1') + '" min="1">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-unit"><?php _e('Unit Label', 'mobooking'); ?></label>' +
                            '<input type="text" id="option-unit" name="unit" value="' + (optionData.unit || '') + '" placeholder="<?php _e('items, people, etc.', 'mobooking'); ?>">' +
                        '</div>' +
                    '</div>'
                );
                break;
        }
        
        // Update price fields
        updatePriceFields(optionData.price_type || 'fixed');
    }
    
    // Function to parse options string into array of objects
    function parseOptionsString(optionsString) {
        const options = [];
        if (!optionsString) return options;
        
        const lines = optionsString.split('\n');
        
        lines.forEach(line => {
            if (!line.trim()) return;
            
            const parts = line.split('|');
            if (parts.length < 2) return;
            
            const value = parts[0]?.trim() || '';
            const labelPriceParts = parts[1]?.split(':') || [''];
            
            const label = labelPriceParts[0]?.trim() || '';
            const price = parseFloat(labelPriceParts[1] || 0) || 0;
            
            if (value) {
                options.push({
                    value: value,
                    label: label,
                    price: price
                });
            }
        });
        
        return options;
    }
    
    // Function to serialize choices to string format
    function serializeChoices() {
        const choices = [];
        
        $('.choices-list .choice-row').each(function() {
            const value = $(this).find('.choice-value-input').val().trim();
            const label = $(this).find('.choice-label-input').val().trim();
            const price = parseFloat($(this).find('.choice-price-input').val()) || 0;
            
            if (value) {
                choices.push(value + '|' + label + (price ? ':' + price : ''));
            }
        });
        
        return choices.join('\n');
    }
    
    // Function to add a new choice row
    function addChoiceRow(container, value = '', label = '', price = 0) {
        const row = $(
            '<div class="choice-row">' +
                '<div class="choice-drag-handle">' +
                    '<span class="dashicons dashicons-menu"></span>' +
                '</div>' +
                '<div class="choice-value">' +
                    '<input type="text" class="choice-value-input" value="' + value + '" placeholder="value">' +
                '</div>' +
                '<div class="choice-label">' +
                    '<input type="text" class="choice-label-input" value="' + label + '" placeholder="Display Label">' +
                '</div>' +
                '<div class="choice-price">' +
                    '<input type="number" class="choice-price-input" value="' + price + '" step="0.01" placeholder="0.00">' +
                '</div>' +
                '<div class="choice-actions">' +
                    '<button type="button" class="button-link remove-choice">' +
                        '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                '</div>' +
            '</div>'
        );
        
        container.append(row);
        
        // Focus on the value input for new rows
        if (!value) {
            row.find('.choice-value-input').focus();
        }
        
        // Update the hidden input
        updateOptionsField();
        
        return row;
    }
    
    // Function to initialize choices sortable
    function initChoicesSortable() {
        $('.choices-list').sortable({
            handle: '.choice-drag-handle',
            placeholder: 'choice-row-placeholder',
            axis: 'y',
            cursor: 'move',
            opacity: 0.8,
            tolerance: 'pointer',
            start: function(event, ui) {
                ui.placeholder.height(ui.item.outerHeight());
            },
            update: function() {
                // When order changes, update the hidden input
                updateOptionsField();
            }
        });
    }
    
    // Add new choice button handler
    $(document).on('click', '.add-choice', function() {
        const choicesList = $(this).closest('.choices-container').find('.choices-list');
        addChoiceRow(choicesList);
    });
    
    // Remove choice button handler
    $(document).on('click', '.remove-choice', function() {
        const choiceRow = $(this).closest('.choice-row');
        
        // Don't remove if it's the only choice
        if ($('.choice-row').length <= 1) {
            showNotification('You must have at least one choice', 'warning');
            return;
        }
        
        // Animate removal
        choiceRow.slideUp(200, function() {
            $(this).remove();
            updateOptionsField();
        });
    });
    
    // Update options field when choice inputs change
    $(document).on('input', '.choice-value-input, .choice-label-input, .choice-price-input', function() {
        updateOptionsField();
    });
    
    // Function to update the hidden options field
    function updateOptionsField() {
        const serialized = serializeChoices();
        $('#option-choices').val(serialized);
    }
    
    // Submit option form
    $('#option-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateOptionForm()) {
            return;
        }
        
        // Show loading indicator
        $('#options-modal').addClass('loading');
        
        // Prepare form data
        var formData = new FormData(this);
        formData.append('action', 'mobooking_save_service_option');
        
        // Send the AJAX request
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Reload options list
                    loadServiceOptions($('#option-service-id').val());
                    
                    // Hide the form
                    $('#option-form').hide();
                    $('.no-option-selected').show();
                    
                    // Show success notification
                    showNotification('Option ' + ($('#option-id').val() ? 'updated' : 'created') + ' successfully', 'success');
                } else {
                    showNotification(response.data.message || 'Error saving option', 'error');
                }
                
                $('#options-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Full response:', xhr.responseText);
                showNotification('Error saving option. Please try again.', 'error');
                $('#options-modal').removeClass('loading');
            }
        });
    });
    
    // Function to validate the option form
    function validateOptionForm() {
        // Check required fields
        const name = $('#option-name').val().trim();
        const type = $('#option-type').val();
        
        if (!name) {
            showNotification('Option name is required', 'error');
            $('#option-name').focus();
            return false;
        }
        
        if (!type) {
            showNotification('Option type is required', 'error');
            $('#option-type').focus();
            return false;
        }
        
        // Validate choices for select/radio options
        if (type === 'select' || type === 'radio') {
            const hasValidChoices = $('.choice-row').toArray().some(row => {
                return $(row).find('.choice-value-input').val().trim() !== '';
            });
            
            if (!hasValidChoices) {
                showNotification('At least one choice with a value is required', 'error');
                $('.choices-list .choice-value-input:first').focus();
                return false;
            }
        }
        
        return true;
    }
    
    //