<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get services
$services_manager = new \MoBooking\Services\Manager();
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
    'custom' => __('Custom Formula', 'mobooking')
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
        
        <h2 id="options-modal-title"><?php _e('Manage Service Options', 'mobooking'); ?> - <span class="service-name"></span></h2>
        
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
                            <input type="number" id="option-price-impact" name="price_impact" step="0.01">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="button button-danger delete-option" style="display: none;"><?php _e('Delete', 'mobooking'); ?></button>
                        <div class="spacer"></div>
                        <button type="button" class="button button-secondary cancel-option"><?php _e('Cancel', 'mobooking'); ?></button>
                        <button type="submit" class="button button-primary save-option"><?php _e('Save Option', 'mobooking'); ?></button>
                    </div>
                    
                    <?php wp_nonce_field('mobooking-option-nonce', 'option_nonce'); ?>
                </form>
                
                <div class="no-option-selected">
                    <p><?php _e('Select an option from the list or add a new one.', 'mobooking'); ?></p>
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
        
        <div class="form-actions">
            <button type="button" class="button button-secondary cancel-delete"><?php _e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="button button-danger confirm-delete" data-id=""><?php _e('Delete Service', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // First, check if mobooking_services is defined, and if not, define it
    if (typeof mobooking_services === 'undefined') {
        console.log('mobooking_services not defined, creating a default object');
        window.mobooking_services = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-service-nonce'); ?>'
        };
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
    $('.edit-service').on('click', function() {
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
                    alert(response.data.message || response.data);
                    $('#service-modal').fadeOut();
                }
                
                // Remove loading indicator
                $('#service-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error loading service data. Please try again.', 'mobooking'); ?>');
                $('#service-modal').fadeOut();
                $('#service-modal').removeClass('loading');
            }
        });
    });
    
    // Open manage options modal
    $('.manage-options').on('click', function() {
        var serviceId = $(this).data('id');
        var serviceName = $(this).data('name');
        
        // Set modal title
        $('.options-modal-title .service-name').text(serviceName);
        
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
                    
                    if (response.data.options.length === 0) {
                        $('.options-list').html('<div class="options-list-empty"><?php _e('No options configured yet.', 'mobooking'); ?></div>');
                    } else {
                        // Populate options list
                        $.each(response.data.options, function(index, option) {
                            var optionItem = $('<div class="option-item" data-id="' + option.id + '">' +
                                '<span class="option-name">' + option.name + '</span>' +
                                '<span class="option-type">' + option.type + '</span>' +
                                '</div>');
                            $('.options-list').append(optionItem);
                        });
                    }
                } else {
                    alert(response.data.message || response.data);
                }
                
                // Remove loading indicator
                $('#options-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error loading service options. Please try again.', 'mobooking'); ?>');
                $('#options-modal').removeClass('loading');
            }
        });
    }
    
    // Handle clicking on an option in the list
    $(document).on('click', '.option-item', function() {
        var optionId = $(this).data('id');
        
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
                    var option = response.data.option;
                    
                    // Fill the form with option data
                    $('#option-id').val(option.id);
                    $('#option-name').val(option.name);
                    $('#option-type').val(option.type);
                    $('#option-required').val(option.is_required);
                    $('#option-description').val(option.description);
                    $('#option-price-type').val(option.price_type);
                    $('#option-price-impact').val(option.price_impact);
                    
                    // Generate type-specific fields
                    generateDynamicFields(option.type, option);
                    
                    // Show delete button
                    $('.delete-option').show();
                    
                    // Show the form
                    $('.no-option-selected').hide();
                    $('#option-form').show();
                    
                    // Highlight selected option
                    $('.option-item').removeClass('active');
                    $('.option-item[data-id="' + optionId + '"]').addClass('active');
                } else {
                    alert(response.data.message || response.data);
                }
                
                // Remove loading indicator
                $('#options-modal').removeClass('loading');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error loading option data. Please try again.', 'mobooking'); ?>');
                $('#options-modal').removeClass('loading');
            }
        });
    });
    
    // Add new option button
    $('.add-new-option').on('click', function() {
        // Reset form
        $('#option-id').val('');
        $('#option-form')[0].reset();
        
        // Set service ID from the modal
        // (Already set when opening the options modal)
        
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
    
    // Handle option type change
    $('#option-type').on('change', function() {
        var optionType = $(this).val();
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
        var valueField = $('.price-impact-value');
        
        if (priceType === 'custom') {
            valueField.find('label').text('<?php _e('Formula', 'mobooking'); ?>');
            valueField.find('input').attr('type', 'text').attr('placeholder', 'price + (value * 5)');
        } else {
            valueField.find('label').text('<?php _e('Price Value', 'mobooking'); ?>');
            valueField.find('input').attr('type', 'number').attr('placeholder', '');
        }
    }
    
    // Function to generate dynamic fields based on option type
    function generateDynamicFields(optionType, optionData) {
        var dynamicFields = $('.dynamic-fields');
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
                    '</div>'
                );
                break;
                
            case 'number':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-value"><?php _e('Minimum Value', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-min-value" name="min_value" value="' + (optionData.min_value || 0) + '">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-value"><?php _e('Maximum Value', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-max-value" name="max_value" value="' + (optionData.max_value || '') + '">' +
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
                    '</div>'
                );
                break;
                
            case 'select':
            case 'radio':
                dynamicFields.append(
                    '<div class="form-group">' +
                        '<label for="option-choices"><?php _e('Choices (one per line, format: value|label)', 'mobooking'); ?></label>' +
                        '<textarea id="option-choices" name="options" rows="4">' + (optionData.options || '') + '</textarea>' +
                        '<p class="field-hint"><?php _e('Example: small|Small Size (10-20 sq ft)', 'mobooking'); ?></p>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label for="option-default-value"><?php _e('Default Value', 'mobooking'); ?></label>' +
                        '<input type="text" id="option-default-value" name="default_value" value="' + (optionData.default_value || '') + '">' +
                        '<p class="field-hint"><?php _e('Enter the value (not the label) of the default choice', 'mobooking'); ?></p>' +
                    '</div>'
                );
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
                    '</div>'
                );
                break;
                
            case 'quantity':
                dynamicFields.append(
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-min-value"><?php _e('Minimum Quantity', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-min-value" name="min_value" value="' + (optionData.min_value || 0) + '" min="0">' +
                        '</div>' +
                        '<div class="form-group half">' +
                            '<label for="option-max-value"><?php _e('Maximum Quantity', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-max-value" name="max_value" value="' + (optionData.max_value || '') + '" min="0">' +
                        '</div>' +
                    '</div>' +
                    '<div class="form-row">' +
                        '<div class="form-group half">' +
                            '<label for="option-default-value"><?php _e('Default Quantity', 'mobooking'); ?></label>' +
                            '<input type="number" id="option-default-value" name="default_value" value="' + (optionData.default_value || 0) + '" min="0">' +
                        '</div>' +
                    '</div>'
                );
                break;
        }
        
        // Update price fields
        updatePriceFields(optionData.price_type || 'fixed');
    }
    
    // Close modals
    $('.modal-close, .cancel-service, .cancel-option, .cancel-delete').on('click', function() {
        $('.mobooking-modal').fadeOut('fast');
    });
    
    // Cancel option editing
    $('.cancel-option').on('click', function() {
        $('#option-form').hide();
        $('.no-option-selected').show();
        $('.option-item').removeClass('active');
    });
    
    // Submit service form
    $('#service-form').on('submit', function(e) {
        e.preventDefault();
        
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
                    // Reload page to show updated services
                    location.reload();
                } else {
                    alert(response.data.message || response.data);
                    $('#service-modal').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error saving service. Please try again.', 'mobooking'); ?>');
                $('#service-modal').removeClass('loading');
            }
        });
    });
    
    // Submit option form
    $('#option-form').on('submit', function(e) {
        e.preventDefault();
        
        // Show loading indicator
        $('#options-modal').addClass('loading');
        
        // Prepare form data
        var formData = $(this).serialize();
        formData += '&action=mobooking_save_service_option&nonce=' + mobooking_services.nonce;
        
        // Submit via AJAX
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Reload options list
                    loadServiceOptions($('#option-service-id').val());
                    
                    // Hide the form
                    $('#option-form').hide();
                    $('.no-option-selected').show();
                } else {
                    alert(response.data.message || response.data);
                    $('#options-modal').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error saving option. Please try again.', 'mobooking'); ?>');
                $('#options-modal').removeClass('loading');
            }
        });
    });
    
    // Delete option button click
    $('.delete-option').on('click', function() {
        var optionId = $('#option-id').val();
        
        if (!optionId) {
            return;
        }
        
        if (!confirm('<?php _e('Are you sure you want to delete this option? This action cannot be undone.', 'mobooking'); ?>')) {
            return;
        }
        
        // Show loading indicator
        $('#options-modal').addClass('loading');
        
        // Submit delete request via AJAX
        $.ajax({
            url: mobooking_services.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_delete_service_option',
                id: optionId,
                nonce: mobooking_services.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload options list
                    loadServiceOptions($('#option-service-id').val());
                    
                    // Hide the form
                    $('#option-form').hide();
                    $('.no-option-selected').show();
                } else {
                    alert(response.data.message || response.data);
                    $('#options-modal').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error deleting option. Please try again.', 'mobooking'); ?>');
                $('#options-modal').removeClass('loading');
            }
        });
    });
    
    // Open delete confirmation modal
    $('.delete-service').on('click', function() {
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
                    // Reload page to show updated services
                    location.reload();
                } else {
                    alert(response.data.message || response.data);
                    $('#delete-modal').removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php _e('Error deleting service. Please try again.', 'mobooking'); ?>');
                $('#delete-modal').removeClass('loading');
            }
        });
    });
});
</script>

<style>
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
    border: 1px solid var(--border-color);    }
    
    .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
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
    
    /* Modal styling enhancements */
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
        z-index: 1000;
    }
    
    .modal-content {
        background-color: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        padding: 1.5rem;
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
        width: 24px;
        height: 24px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .modal-close:hover {
        color: var(--primary-color);
        background-color: rgba(0, 0, 0, 0.05);
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
        margin-top: 1.5rem;
        min-height: 300px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
    }
    
    .options-sidebar {
        width: 240px;
        border-right: 1px solid var(--border-color);
        padding: 1rem;
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .options-content {
        flex: 1;
        padding: 1.5rem;
        position: relative;
    }
    
    .options-list {
        margin-top: 1rem;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .options-list-empty {
        color: var(--text-light);
        font-style: italic;
        margin-top: 1rem;
        text-align: center;
    }
    
    .option-item {
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius);
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        flex-direction: column;
    }
    
    .option-item:hover {
        border-color: var(--primary-color);
        background-color: rgba(0, 0, 0, 0.01);
    }
    
    .option-item.active {
        border-color: var(--primary-color);
        background-color: rgba(76, 175, 80, 0.05);
    }
    
    .option-name {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .option-type {
        font-size: 0.75rem;
        color: var(--text-light);
        text-transform: capitalize;
    }
    
    .no-option-selected {
        display: flex;
        height: 100%;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        text-align: center;
        padding: 2rem;
    }
    
    .field-hint {
        font-size: 0.75rem;
        color: var(--text-light);
        margin-top: 0.25rem;
        margin-bottom: 0;
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
        display: flex;
        align-items: center;
        justify-content: center;
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
            gap: 0;
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
        }
    }
</style>