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
$service_manager = new \MoBooking\Services\ServiceManager();
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

// Enqueue necessary scripts
wp_enqueue_script('jquery-ui-sortable');
wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_media();
?>

<div class="dashboard-section services-section">
    <?php if ($current_view === 'list') : ?>
        <!-- Services List View -->
        <div class="section-header">
            <h2 class="section-title"><?php _e('Services', 'mobooking'); ?></h2>
            
            <div class="top-actions">
                <div class="filter-controls">
                    <label for="category-filter"><?php _e('Filter by category:', 'mobooking'); ?></label>
                    <select id="category-filter">
                        <option value=""><?php _e('All Categories', 'mobooking'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html(ucfirst($category)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add New Service', 'mobooking'); ?>
                </a>
            </div>
        </div>
        
        <?php if (empty($services)) : ?>
            <div class="no-items">
                <span class="dashicons dashicons-admin-tools"></span>
                <h3><?php _e('No Services Yet', 'mobooking'); ?></h3>
                <p><?php _e('Create your first service to start accepting bookings from customers.', 'mobooking'); ?></p>
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'new'), home_url('/dashboard/services/'))); ?>" class="button button-primary">
                    <?php _e('Create Your First Service', 'mobooking'); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="services-grid">
                <?php foreach ($services as $service) : 
                    $options_count = count($options_manager->get_service_options($service->id));
                ?>
                    <div class="service-card" data-category="<?php echo esc_attr($service->category); ?>">
                        <div class="service-header">
                            <div class="service-visual">
                                <?php if (!empty($service->image_url)) : ?>
                                    <div class="service-image" style="background-image: url('<?php echo esc_url($service->image_url); ?>')"></div>
                                <?php elseif (!empty($service->icon)) : ?>
                                    <div class="service-icon">
                                        <span class="dashicons <?php echo esc_attr($service->icon); ?>"></span>
                                    </div>
                                <?php else : ?>
                                    <div class="service-icon">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-title">
                                <h3><?php echo esc_html($service->name); ?></h3>
                                <?php if (!empty($service->category)) : ?>
                                    <span class="category-badge category-<?php echo esc_attr($service->category); ?>">
                                        <?php echo esc_html(ucfirst($service->category)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-status-price">
                                <div class="service-price"><?php echo wc_price($service->price); ?></div>
                                <span class="service-status service-status-<?php echo esc_attr($service->status); ?>">
                                    <?php echo esc_html(ucfirst($service->status)); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="service-body">
                            <div class="service-meta">
                                <span class="service-duration">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo sprintf(_n('%d minute', '%d minutes', $service->duration, 'mobooking'), $service->duration); ?>
                                </span>
                                
                                <?php if ($options_count > 0) : ?>
                                    <span class="service-options-badge">
                                        <?php echo sprintf(_n('%d option', '%d options', $options_count, 'mobooking'), $options_count); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($service->description)) : ?>
                                <div class="service-description">
                                    <?php echo wp_trim_words(esc_html($service->description), 20); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="service-actions">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'edit', 'service_id' => $service->id), home_url('/dashboard/services/'))); ?>" 
                               class="button button-secondary">
                                <span class="dashicons dashicons-edit"></span>
                                <?php _e('Edit', 'mobooking'); ?>
                            </a>
                            
                            <button type="button" class="button button-danger delete-service-btn" data-id="<?php echo esc_attr($service->id); ?>">
                                <span class="dashicons dashicons-trash"></span>
                                <?php _e('Delete', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <!-- Service Edit/New View -->
        <div class="section-header">
            <h2 class="section-title">
                <?php if ($current_view === 'edit') : ?>
                    <?php printf(__('Edit Service: %s', 'mobooking'), esc_html($service_data->name)); ?>
                <?php else : ?>
                    <?php _e('Add New Service', 'mobooking'); ?>
                <?php endif; ?>
            </h2>
            
            <a href="<?php echo esc_url(home_url('/dashboard/services/')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to Services', 'mobooking'); ?>
            </a>
        </div>
        
        <div class="service-form-container">
            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button type="button" class="tab-button <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>" data-tab="basic-info">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Basic Info', 'mobooking'); ?>
                </button>
                <button type="button" class="tab-button <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>" data-tab="presentation">
                    <span class="dashicons dashicons-format-image"></span>
                    <?php _e('Presentation', 'mobooking'); ?>
                </button>
                <button type="button" class="tab-button <?php echo $active_tab === 'options' ? 'active' : ''; ?>" data-tab="options">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Options', 'mobooking'); ?>
                    <?php if ($service_id) : ?>
                        <span class="options-count">(<?php echo count($options_manager->get_service_options($service_id)); ?>)</span>
                    <?php endif; ?>
                </button>
            </div>
            
            <!-- Service Form -->
            <form id="service-form" method="post">
                <input type="hidden" id="service-id" name="id" value="<?php echo $service_data ? esc_attr($service_data->id) : ''; ?>">
                <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
                
                <!-- Basic Info Tab -->
                <div id="basic-info" class="tab-pane <?php echo $active_tab === 'basic-info' ? 'active' : ''; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="service-name"><?php _e('Service Name', 'mobooking'); ?> *</label>
                            <input type="text" id="service-name" name="name" value="<?php echo $service_data ? esc_attr($service_data->name) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="service-price"><?php _e('Price', 'mobooking'); ?> *</label>
                            <input type="number" id="service-price" name="price" value="<?php echo $service_data ? esc_attr($service_data->price) : ''; ?>" step="0.01" min="0" required>
                        </div>
                        <div class="form-group half">
                            <label for="service-duration"><?php _e('Duration (minutes)', 'mobooking'); ?> *</label>
                            <input type="number" id="service-duration" name="duration" value="<?php echo $service_data ? esc_attr($service_data->duration) : '60'; ?>" min="15" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="service-category"><?php _e('Category', 'mobooking'); ?></label>
                            <select id="service-category" name="category">
                                <option value=""><?php _e('Select Category', 'mobooking'); ?></option>
                                <option value="residential" <?php selected($service_data ? $service_data->category : '', 'residential'); ?>><?php _e('Residential', 'mobooking'); ?></option>
                                <option value="commercial" <?php selected($service_data ? $service_data->category : '', 'commercial'); ?>><?php _e('Commercial', 'mobooking'); ?></option>
                                <option value="special" <?php selected($service_data ? $service_data->category : '', 'special'); ?>><?php _e('Special', 'mobooking'); ?></option>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label for="service-status"><?php _e('Status', 'mobooking'); ?></label>
                            <select id="service-status" name="status">
                                <option value="active" <?php selected($service_data ? $service_data->status : 'active', 'active'); ?>><?php _e('Active', 'mobooking'); ?></option>
                                <option value="inactive" <?php selected($service_data ? $service_data->status : '', 'inactive'); ?>><?php _e('Inactive', 'mobooking'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                        <textarea id="service-description" name="description" rows="4"><?php echo $service_data ? esc_textarea($service_data->description) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Presentation Tab -->
                <div id="presentation" class="tab-pane <?php echo $active_tab === 'presentation' ? 'active' : ''; ?>">
                    <div class="form-row">
                        <div class="form-group half">
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
                                    <div class="icon-item" data-icon="<?php echo esc_attr($icon_class); ?>" title="<?php echo esc_attr($icon_name); ?>">
                                        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="form-group half">
                            <label for="service-image"><?php _e('Image URL', 'mobooking'); ?></label>
                            <div class="image-upload-container">
                                <input type="url" id="service-image" name="image_url" value="<?php echo $service_data ? esc_attr($service_data->image_url) : ''; ?>">
                                <button type="button" class="button select-image"><?php _e('Select Image', 'mobooking'); ?></button>
                            </div>
                            
                            <div class="image-preview">
                                <?php if ($service_data && !empty($service_data->image_url)) : ?>
                                    <img src="<?php echo esc_url($service_data->image_url); ?>" alt="">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Options Tab -->
                <div id="options" class="tab-pane <?php echo $active_tab === 'options' ? 'active' : ''; ?>">
                    <div class="options-header">
                        <h3><?php _e('Service Options', 'mobooking'); ?></h3>
                        <p><?php _e('Add customizable options to let customers personalize this service and adjust pricing accordingly.', 'mobooking'); ?></p>
                        
                        <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
                            <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
                                <strong>Debug Tools:</strong>
                                <button type="button" onclick="testAjax()" style="margin: 5px;">Test Basic AJAX</button>
                                <button type="button" onclick="debugServices()" style="margin: 5px;">Debug Services</button>
                                <button type="button" onclick="checkDatabase()" style="margin: 5px;">Check Database</button>
                                <button type="button" onclick="createTables()" style="margin: 5px;">Create Tables</button>
                                <button type="button" onclick="fixTables()" style="margin: 5px; background: #e74c3c; color: white;">🔧 Fix Missing Table</button>
                                <script>
                                function testAjax() {
                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                        action: 'mobooking_test'
                                    }, function(response) {
                                        console.log('Test AJAX Response:', response);
                                        alert('Test AJAX: ' + (response.success ? 'SUCCESS' : 'FAILED'));
                                    }).fail(function(xhr) {
                                        console.error('Test AJAX Failed:', xhr);
                                        alert('Test AJAX Failed: ' + xhr.status + ' - ' + xhr.responseText);
                                    });
                                }
                                
                                function debugServices() {
                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                        action: 'mobooking_debug_services'
                                    }, function(response) {
                                        console.log('Debug Services Response:', response);
                                        alert('Debug complete - check console');
                                    }).fail(function(xhr) {
                                        console.error('Debug Services Failed:', xhr);
                                        alert('Debug Failed: ' + xhr.status + ' - ' + xhr.responseText);
                                    });
                                }
                                
                                function checkDatabase() {
                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                        action: 'mobooking_db_diagnostic'
                                    }, function(response) {
                                        console.log('🗄️ Database Diagnostic:', response);
                                        if (response.success) {
                                            const data = response.data;
                                            let message = 'DATABASE STATUS:\n\n';
                                            message += 'Services Table: ' + (data.tables.services.exists ? '✅ EXISTS' : '❌ MISSING') + '\n';
                                            message += 'Options Table: ' + (data.tables.options.exists ? '✅ EXISTS' : '❌ MISSING') + '\n';
                                            message += 'User Can Manage: ' + (data.user.can_manage ? '✅ YES' : '❌ NO') + '\n';
                                            message += '\nSee console for full details';
                                            alert(message);
                                        }
                                    }).fail(function(xhr) {
                                        console.error('Database check failed:', xhr);
                                        alert('Database check failed: ' + xhr.responseText);
                                    });
                                }
                                
                                function createTables() {
                                    if (!confirm('Create missing database tables? This is safe to run multiple times.')) return;
                                    
                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                        action: 'mobooking_create_tables'
                                    }, function(response) {
                                        console.log('🔧 Create Tables Response:', response);
                                        if (response.success) {
                                            alert('✅ Tables created successfully!\n\nRefresh the page and try adding an option again.');
                                        } else {
                                            alert('❌ Failed to create tables: ' + response.data);
                                        }
                                    }).fail(function(xhr) {
                                        console.error('Create tables failed:', xhr);
                                        alert('Create tables failed: ' + xhr.responseText);
                                    });
                                }
                                
                                function fixTables() {
                                    if (!confirm('Force create the missing wp_mobooking_service_options table?')) return;
                                    
                                    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                        action: 'mobooking_fix_tables'
                                    }, function(response) {
                                        console.log('🔧 Fix Tables Response:', response);
                                        if (response.success && response.data.table_exists) {
                                            alert('✅ SUCCESS!\n\nService options table created!\nRefresh the page and try adding an option again.');
                                            location.reload();
                                        } else {
                                            alert('❌ Failed to create service options table.\n\nTry the manual SQL approach.');
                                        }
                                    }).fail(function(xhr) {
                                        console.error('Fix tables failed:', xhr);
                                        alert('Fix tables failed: ' + xhr.responseText);
                                    });
                                }
                                </script>
                            </div>
                        <?php endif; ?>
                        
                        <button type="button" id="add-option-btn" class="button button-primary" <?php echo !$service_id ? 'disabled title="' . esc_attr__('Save the service first to add options', 'mobooking') . '"' : ''; ?>>
                            <span class="dashicons dashicons-plus-alt"></span>
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
                            <?php _e('Delete Service', 'mobooking'); ?>
                        </button>
                    <?php endif; ?>
                    
                    <div class="spacer"></div>
                    
                    <button type="submit" id="save-service-button" class="button button-primary">
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
<div id="option-modal" class="mobooking-modal">
    <div class="modal-content modal-lg">
        <span class="modal-close">&times;</span>
        <h3 id="option-modal-title"><?php _e('Add New Option', 'mobooking'); ?></h3>
        
        <form id="option-form" method="post">
            <input type="hidden" id="option-id" name="id">
            <input type="hidden" id="option-service-id" name="service_id" value="<?php echo esc_attr($service_id); ?>">
            <?php wp_nonce_field('mobooking-service-nonce', 'nonce'); ?>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="option-name"><?php _e('Option Name', 'mobooking'); ?> *</label>
                    <input type="text" id="option-name" name="name" required>
                </div>
                <div class="form-group half">
                    <label for="option-type"><?php _e('Option Type', 'mobooking'); ?></label>
                    <select id="option-type" name="type">
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
                <textarea id="option-description" name="description" rows="2"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="option-required"><?php _e('Required', 'mobooking'); ?></label>
                    <select id="option-required" name="is_required">
                        <option value="0"><?php _e('Optional', 'mobooking'); ?></option>
                        <option value="1"><?php _e('Required', 'mobooking'); ?></option>
                    </select>
                </div>
                <div class="form-group half">
                    <label for="option-price-type"><?php _e('Price Impact', 'mobooking'); ?></label>
                    <select id="option-price-type" name="price_type">
                        <option value="none"><?php _e('No Price Impact', 'mobooking'); ?></option>
                        <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                        <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                        <option value="multiply"><?php _e('Multiply by Value', 'mobooking'); ?></option>
                    </select>
                </div>
            </div>
            
            <div id="price-impact-group" class="form-group">
                <label for="option-price-impact"><?php _e('Price Impact Amount', 'mobooking'); ?></label>
                <input type="number" id="option-price-impact" name="price_impact" step="0.01" value="0">
            </div>
            
            <!-- Dynamic fields will be inserted here -->
            <div id="option-dynamic-fields"></div>
            
            <div class="form-actions">
                <button type="button" id="delete-option-btn" class="button button-danger" style="display: none;">
                    <?php _e('Delete Option', 'mobooking'); ?>
                </button>
                
                <div class="spacer"></div>
                
                <button type="button" id="cancel-option-btn" class="button button-secondary">
                    <?php _e('Cancel', 'mobooking'); ?>
                </button>
                
                <button type="submit" class="button button-primary">
                    <span class="normal-state"><?php _e('Save Option', 'mobooking'); ?></span>
                    <span class="loading-state" style="display: none;"><?php _e('Saving...', 'mobooking'); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="mobooking-modal">
    <div class="modal-content">
        <h3><?php _e('Confirm Delete', 'mobooking'); ?></h3>
        <p id="confirmation-message"><?php _e('Are you sure you want to delete this? This action cannot be undone.', 'mobooking'); ?></p>
        
        <div class="form-actions">
            <button type="button" class="button button-secondary cancel-delete-btn">
                <?php _e('Cancel', 'mobooking'); ?>
            </button>
            <button type="button" class="button button-danger confirm-delete-btn">
                <span class="normal-state"><?php _e('Delete', 'mobooking'); ?></span>
                <span class="loading-state" style="display: none;"><?php _e('Deleting...', 'mobooking'); ?></span>
            </button>
        </div>
    </div>
</div>

<style>
/* Additional styles for services section */
.icon-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 0.5rem;
    margin-top: 0.5rem;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    padding: 0.5rem;
    border-radius: var(--radius);
}

.icon-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
}

.icon-item:hover {
    border-color: var(--primary-color);
    background-color: var(--primary-light);
}

.icon-item.selected {
    border-color: var(--primary-color);
    background-color: var(--primary-color);
    color: white;
}

.tab-navigation {
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
    display: flex;
    align-items: center;
    gap: 0.5rem;
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

.options-count {
    background-color: var(--primary-color);
    color: white;
    padding: 2px 6px;
    border-radius: 50px;
    font-size: 0.75rem;
    margin-left: 0.25rem;
}

.options-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.options-header h3 {
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.options-header p {
    color: var(--text-light);
    margin-bottom: 1rem;
}
</style>

<script>
// Emergency debug script
jQuery(document).ready(function($) {
    console.log("🚨 EMERGENCY DEBUG SCRIPT LOADED");
    
    // Check current service ID from multiple sources
    const urlParams = new URLSearchParams(window.location.search);
    const serviceIdFromUrl = urlParams.get('service_id');
    const serviceIdFromForm = $('#service-id').val();
    
    console.log("🔍 Emergency Debug:");
    console.log("- URL service_id:", serviceIdFromUrl);
    console.log("- Form service_id:", serviceIdFromForm);
    console.log("- Dashboard config:", typeof window.MoBookingDashboard !== 'undefined' ? window.MoBookingDashboard.config : 'not loaded');
    
    // Force update the service ID if we have it
    if (serviceIdFromUrl || serviceIdFromForm) {
        const serviceId = serviceIdFromUrl || serviceIdFromForm;
        console.log("🔧 EMERGENCY: Setting service ID to", serviceId);
        
        // Update form field
        $('#option-service-id').val(serviceId);
        
        // Update dashboard config if available
        if (typeof window.MoBookingDashboard !== 'undefined') {
            window.MoBookingDashboard.config.currentServiceId = parseInt(serviceId);
            console.log("✅ Updated dashboard config with service ID:", serviceId);
        }
    }
    
    // EMERGENCY FIX: Override the showAddOptionModal function
    setTimeout(function() {
        if (typeof window.MoBookingDashboard !== 'undefined') {
            console.log("🔧 EMERGENCY: Overriding showAddOptionModal");
            
            window.MoBookingDashboard.showAddOptionModal = function() {
                const serviceId = this.config.currentServiceId || 
                                new URLSearchParams(window.location.search).get('service_id') ||
                                $('#service-id').val();
                
                console.log("🚨 EMERGENCY showAddOptionModal with service ID:", serviceId);
                
                if (!serviceId) {
                    this.showNotification("No service ID found", "error");
                    return;
                }
                
                // Force update the config
                this.config.currentServiceId = parseInt(serviceId);
                
                // Reset and show modal
                this.state.currentOptionId = null;
                $('#option-form')[0].reset();
                $('#option-id').val('');
                $('#option-service-id').val(serviceId);
                
                console.log("🔧 Set option-service-id field to:", serviceId);
                
                $('#option-modal-title').text('Add New Option');
                $('#delete-option-btn').hide();
                $('#option-dynamic-fields').empty();
                
                // Show modal
                $('#option-modal').fadeIn(300);
                $('body').addClass('modal-open');
                
                console.log("✅ EMERGENCY modal opened with service ID:", $('#option-service-id').val());
            };
            
            // Also override handleOptionSubmit
            window.MoBookingDashboard.handleOptionSubmit = function() {
                console.log("🚨 EMERGENCY handleOptionSubmit");
                
                const formData = new FormData($('#option-form')[0]);
                formData.append('action', 'mobooking_save_service_option');
                
                // Force add service ID
                const serviceId = this.config.currentServiceId || 
                                new URLSearchParams(window.location.search).get('service_id');
                                
                if (serviceId) {
                    formData.set('service_id', serviceId);
                }
                
                console.log("🔧 EMERGENCY: Form data:");
                for (let [key, value] of formData.entries()) {
                    console.log(`- ${key}: ${value}`);
                }
                
                // Make request
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function(response) {
                    console.log("🚨 EMERGENCY response:", response);
                    if (response.success) {
                        alert("Option saved successfully!");
                        $('#option-modal').fadeOut(300);
                        location.reload(); // Quick fix
                    } else {
                        alert("Error: " + (response.data || "Failed to save"));
                    }
                }).fail(function(xhr) {
                    console.error("🚨 EMERGENCY AJAX failed:", xhr);
                    alert("AJAX Error: " + xhr.responseText);
                });
            };
        }
    }, 1000);
});
</script>