
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

// Get total count
$total_services = count($services);
?>

<div class="dashboard-section services-section">
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
    
    <?php if (empty($services)) : ?>
        <div class="no-items">
            <span class="dashicons dashicons-admin-tools"></span>
            <p><?php _e('You haven\'t created any services yet.', 'mobooking'); ?></p>
            <p><?php _e('Add your first service to start receiving bookings.', 'mobooking'); ?></p>
        </div>
    <?php else : ?>
        <div class="services-grid">
            <?php foreach ($services as $service) : ?>
                <div class="service-card" data-id="<?php echo esc_attr($service->id); ?>" data-category="<?php echo esc_attr($service->category); ?>">
                    <div class="service-header">
                        <?php if (!empty($service->icon)) : ?>
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
                        
                        <div class="service-price">
                            <?php echo wc_price($service->price); ?>
                        </div>
                    </div>
                    
                    <div class="service-body">
                        <div class="service-meta">
                            <span class="service-duration">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo sprintf(_n('%d minute', '%d minutes', $service->duration, 'mobooking'), $service->duration); ?>
                            </span>
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
                            <span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'mobooking'); ?>
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
            <input type="hidden" id="service-id" name="id" value="">
            
            <div class="form-group">
                <label for="service-name"><?php _e('Service Name', 'mobooking'); ?></label>
                <input type="text" id="service-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="service-description"><?php _e('Description', 'mobooking'); ?></label>
                <textarea id="service-description" name="description" rows="4"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="service-price"><?php _e('Price', 'mobooking'); ?></label>
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
        
        <div class="form-actions">
            <button type="button" class="button button-secondary cancel-delete"><?php _e('Cancel', 'mobooking'); ?></button>
            <button type="button" class="button button-danger confirm-delete" data-id=""><?php _e('Delete Service', 'mobooking'); ?></button>
        </div>
    </div>
</div>

<?php
// Localize the script with the AJAX URL and nonce
wp_enqueue_script('mobooking-services-script', '', array('jquery'), MOBOOKING_VERSION, true);
wp_add_inline_script('mobooking-services-script', '
var mobooking_services = {
    "ajax_url": "' . admin_url('admin-ajax.php') . '",
    "nonce": "' . wp_create_nonce('mobooking-service-nonce') . '"
};
');
?>
<script>
jQuery(document).ready(function($) {
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
    
    // Close modals
    $('.modal-close, .cancel-service, .cancel-delete').on('click', function() {
        $('.mobooking-modal').fadeOut();
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
    /* Services specific styling */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .service-card {
        background-color: var(--card-bg);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        transition: var(--transition);
        overflow: hidden;
    }
    
    .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }
    
    .service-header {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .service-icon {
        width: 40px;
        height: 40px;
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
    }
    
    .service-icon .dashicons {
        font-size: 1.25rem;
        width: 1.25rem;
        height: 1.25rem;
    }
    
    .service-title {
        flex: 1;
    }
    
    .service-title h3 {
        margin: 0 0 0.25rem;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .service-price {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--primary-color);
    }
    
    .service-body {
        padding: 1rem;
    }
    
    .service-meta {
        margin-bottom: 0.75rem;
        color: var(--text-light);
        font-size: 0.875rem;
    }
    
    .service-meta .dashicons {
        font-size: 1rem;
        width: 1rem;
        height: 1rem;
        vertical-align: middle;
        margin-right: 0.25rem;
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
    
    .top-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
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
    
    .modal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 1.5rem;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .modal-close:hover {
        color: var(--primary-color);
    }
    
    .form-row {
        display: flex;
        gap: 1rem;
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
    @media (max-width: 600px) {
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
    }
</style>
