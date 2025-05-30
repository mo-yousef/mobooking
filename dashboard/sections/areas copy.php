<?php
// dashboard/sections/areas.php - Service Areas Management
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize managers
$geography_manager = new \MoBooking\Geography\Manager();
$areas = $geography_manager->get_user_areas($user_id);

// Get coverage statistics
$total_areas = count($areas);
$active_areas = count(array_filter($areas, function($area) { return $area->active; }));
?>

<div class="areas-section">
    <div class="areas-header">
        <div class="areas-header-content">
            <div class="areas-title-group">
                <h1 class="areas-main-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 18L1 22V6L8 2M8 18L16 22M8 18V2M16 22L23 18V2L16 6M16 22V6M16 6L8 2"/>
                    </svg>
                    <?php _e('Service Areas', 'mobooking'); ?>
                </h1>
                <p class="areas-subtitle"><?php _e('Manage ZIP codes where you provide services', 'mobooking'); ?></p>
            </div>
            
            <?php if (!empty($areas)) : ?>
                <div class="areas-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_areas; ?></span>
                        <span class="stat-label"><?php _e('Total Areas', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $active_areas; ?></span>
                        <span class="stat-label"><?php _e('Active', 'mobooking'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="areas-header-actions">
            <button type="button" id="add-area-btn" class="btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <?php _e('Add ZIP Code', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <?php if (empty($areas)) : ?>
        <!-- Empty State -->
        <div class="areas-empty-state">
            <div class="empty-state-visual">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M8 18L1 22V6L8 2M8 18L16 22M8 18V2M16 22L23 18V2L16 6M16 22V6M16 6L8 2"/>
                    </svg>
                </div>
                <div class="empty-state-sparkles">
                    <div class="sparkle sparkle-1"></div>
                    <div class="sparkle sparkle-2"></div>
                    <div class="sparkle sparkle-3"></div>
                </div>
            </div>
            <div class="empty-state-content">
                <h2><?php _e('Define Your Service Areas', 'mobooking'); ?></h2>
                <p><?php _e('Add ZIP codes to specify where you provide services. Customers will only be able to book if they\'re in your service area.', 'mobooking'); ?></p>
                <button type="button" id="add-first-area-btn" class="btn-primary btn-large">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Add Your First Service Area', 'mobooking'); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <!-- Areas Management -->
        <div class="areas-management">
            <!-- Bulk Actions -->
            <div class="areas-toolbar">
                <div class="bulk-actions">
                    <select id="bulk-action-select">
                        <option value=""><?php _e('Bulk Actions', 'mobooking'); ?></option>
                        <option value="activate"><?php _e('Activate', 'mobooking'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'mobooking'); ?></option>
                        <option value="delete"><?php _e('Delete', 'mobooking'); ?></option>
                    </select>
                    <button type="button" id="apply-bulk-action" class="btn-secondary"><?php _e('Apply', 'mobooking'); ?></button>
                </div>
                
                <div class="areas-search">
                    <input type="text" id="areas-search-input" placeholder="<?php _e('Search ZIP codes...', 'mobooking'); ?>" class="search-input">
                </div>
            </div>
            
            <!-- Areas Grid/List -->
            <div class="areas-container">
                <div class="areas-grid-header">
                    <div class="grid-header-cell select-all">
                        <input type="checkbox" id="select-all-areas" class="select-checkbox">
                    </div>
                    <div class="grid-header-cell zip-code"><?php _e('ZIP Code', 'mobooking'); ?></div>
                    <div class="grid-header-cell label"><?php _e('Label', 'mobooking'); ?></div>
                    <div class="grid-header-cell status"><?php _e('Status', 'mobooking'); ?></div>
                    <div class="grid-header-cell actions"><?php _e('Actions', 'mobooking'); ?></div>
                </div>
                
                <div class="areas-grid-body" id="areas-list">
                    <?php foreach ($areas as $area) : ?>
                        <div class="area-row" data-area-id="<?php echo esc_attr($area->id); ?>" data-zip="<?php echo esc_attr($area->zip_code); ?>">
                            <div class="grid-cell select">
                                <input type="checkbox" class="area-checkbox" value="<?php echo esc_attr($area->id); ?>">
                            </div>
                            <div class="grid-cell zip-code">
                                <span class="zip-display"><?php echo esc_html($area->zip_code); ?></span>
                            </div>
                            <div class="grid-cell label">
                                <span class="label-display"><?php echo esc_html($area->label ?: '—'); ?></span>
                            </div>
                            <div class="grid-cell status">
                                <span class="status-badge <?php echo $area->active ? 'active' : 'inactive'; ?>">
                                    <?php echo $area->active ? __('Active', 'mobooking') : __('Inactive', 'mobooking'); ?>
                                </span>
                            </div>
                            <div class="grid-cell actions">
                                <div class="action-buttons">
                                    <button type="button" class="btn-icon edit-area-btn" data-area-id="<?php echo esc_attr($area->id); ?>" title="<?php _e('Edit', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-icon toggle-area-btn" data-area-id="<?php echo esc_attr($area->id); ?>" data-active="<?php echo $area->active ? '1' : '0'; ?>" title="<?php echo $area->active ? __('Deactivate', 'mobooking') : __('Activate', 'mobooking'); ?>">
                                        <?php if ($area->active) : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M8 12h8"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                    <button type="button" class="btn-icon btn-danger delete-area-btn" data-area-id="<?php echo esc_attr($area->id); ?>" title="<?php _e('Delete', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m3 6 3 18h12l3-18"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Import/Export -->
            <div class="areas-import-export">
                <h3><?php _e('Import/Export ZIP Codes', 'mobooking'); ?></h3>
                <div class="import-export-actions">
                    <div class="import-section">
                        <label for="zip-codes-import" class="import-label">
                            <?php _e('Import ZIP codes (comma or line separated):', 'mobooking'); ?>
                        </label>
                        <textarea id="zip-codes-import" rows="4" placeholder="<?php _e('12345, 12346, 12347 or one per line...', 'mobooking'); ?>"></textarea>
                        <button type="button" id="import-zip-codes" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                            <?php _e('Import ZIP Codes', 'mobooking'); ?>
                        </button>
                    </div>
                    
                    <div class="export-section">
                        <button type="button" id="export-zip-codes" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 4v12"/>
                            </svg>
                            <?php _e('Export ZIP Codes', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Area Modal (Add/Edit) -->
<div id="area-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3 id="area-modal-title"><?php _e('Add Service Area', 'mobooking'); ?></h3>
        
        <form id="area-form" method="post">
            <input type="hidden" id="area-id" name="id">
            <?php wp_nonce_field('mobooking-area-nonce', 'nonce'); ?>
            
            <div class="form-group">
                <label for="area-zip-code"><?php _e('ZIP Code', 'mobooking'); ?> *</label>
                <input type="text" id="area-zip-code" name="zip_code" class="form-control" 
                       pattern="[0-9]{5}(-[0-9]{4})?" 
                       placeholder="<?php _e('e.g., 12345 or 12345-6789', 'mobooking'); ?>" 
                       required>
                <p class="field-help"><?php _e('Enter a 5-digit ZIP code or ZIP+4 format', 'mobooking'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="area-label"><?php _e('Label/Description', 'mobooking'); ?></label>
                <input type="text" id="area-label" name="label" class="form-control" 
                       placeholder="<?php _e('e.g., Downtown, North Side, etc.', 'mobooking'); ?>">
                <p class="field-help"><?php _e('Optional friendly name for this area', 'mobooking'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="area-active" name="active" value="1" checked>
                    <?php _e('Active (available for booking)', 'mobooking'); ?>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" id="delete-area-btn" class="btn-danger" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 6 3 18h12l3-18"></path>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                    </svg>
                    <?php _e('Delete Area', 'mobooking'); ?>
                </button>
                
                <div class="spacer"></div>
                
                <button type="button" id="cancel-area-btn" class="btn-secondary">
                    <?php _e('Cancel', 'mobooking'); ?>
                </button>
                
                <button type="submit" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17,21 17,13 7,13 7,21"/>
                        <polyline points="7,3 7,8 15,8"/>
                    </svg>
                    <span class="btn-text"><?php _e('Save Area', 'mobooking'); ?></span>
                    <span class="btn-loading"><?php _e('Saving...', 'mobooking'); ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <h3><?php _e('Confirm Action', 'mobooking'); ?></h3>
        <p id="confirmation-message"><?php _e('Are you sure you want to perform this action?', 'mobooking'); ?></p>
        
        <div class="form-actions">
            <button type="button" class="btn-secondary cancel-action-btn">
                <?php _e('Cancel', 'mobooking'); ?>
            </button>
            <button type="button" class="btn-danger confirm-action-btn">
                <span class="btn-text"><?php _e('Confirm', 'mobooking'); ?></span>
                <span class="btn-loading"><?php _e('Processing...', 'mobooking'); ?></span>
            </button>
        </div>
    </div>
</div>

<script>
// Areas Management JavaScript
jQuery(document).ready(function($) {
    const AreasManager = {
        config: {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('mobooking-area-nonce'); ?>',
            userId: <?php echo $user_id; ?>
        },
        
        state: {
            isProcessing: false,
            currentAreaId: null,
            selectedAreas: []
        },
        
        init: function() {
            this.attachEventListeners();
        },
        
        attachEventListeners: function() {
            const self = this;
            
            // Add area buttons
            $('#add-area-btn, #add-first-area-btn').on('click', function() {
                self.showAddAreaModal();
            });
            
            // Edit area
            $(document).on('click', '.edit-area-btn', function() {
                const areaId = $(this).data('area-id');
                self.editArea(areaId);
            });
            
            // Toggle area status
            $(document).on('click', '.toggle-area-btn', function() {
                const areaId = $(this).data('area-id');
                const isActive = $(this).data('active') === 1;
                self.toggleAreaStatus(areaId, !isActive);
            });
            
            // Delete area
            $(document).on('click', '.delete-area-btn', function() {
                const areaId = $(this).data('area-id');
                self.deleteArea(areaId);
            });
            
            // Area form submission
            $('#area-form').on('submit', function(e) {
                e.preventDefault();
                self.saveArea();
            });
            
            // Modal controls
            $('.modal-close, #cancel-area-btn, .cancel-action-btn').on('click', function() {
                self.hideModals();
            });
            
            // Bulk actions
            $('#apply-bulk-action').on('click', function() {
                self.applyBulkAction();
            });
            
            // Select all checkbox
            $('#select-all-areas').on('change', function() {
                $('.area-checkbox').prop('checked', $(this).is(':checked'));
            });
            
            // Search
            $('#areas-search-input').on('input', function() {
                self.filterAreas($(this).val());
            });
            
            // Import/Export
            $('#import-zip-codes').on('click', function() {
                self.importZipCodes();
            });
            
            $('#export-zip-codes').on('click', function() {
                self.exportZipCodes();
            });
            
            // Confirmation modal
            $('.confirm-action-btn').on('click', function() {
                self.executeConfirmedAction();
            });
        },
        
        showAddAreaModal: function() {
            this.state.currentAreaId = null;
            
            // Reset form
            $('#area-form')[0].reset();
            $('#area-id').val('');
            $('#area-active').prop('checked', true);
            
            // Update modal
            $('#area-modal-title').text('<?php _e('Add Service Area', 'mobooking'); ?>');
            $('#delete-area-btn').hide();
            
            this.showModal('#area-modal');
        },
        
        editArea: function(areaId) {
            this.state.currentAreaId = areaId;
            
            const $row = $(`[data-area-id="${areaId}"]`);
            const zipCode = $row.find('.zip-display').text();
            const label = $row.find('.label-display').text();
            const isActive = $row.find('.status-badge').hasClass('active');
            
            // Populate form
            $('#area-id').val(areaId);
            $('#area-zip-code').val(zipCode);
            $('#area-label').val(label === '—' ? '' : label);
            $('#area-active').prop('checked', isActive);
            
            // Update modal
            $('#area-modal-title').text('<?php _e('Edit Service Area', 'mobooking'); ?>');
            $('#delete-area-btn').show();
            
            this.showModal('#area-modal');
        },
        
        saveArea: function() {
            if (this.state.isProcessing) return;
            
            this.state.isProcessing = true;
            const $btn = $('#area-form button[type="submit"]');
            this.setLoading($btn, true);
            
            const formData = new FormData($('#area-form')[0]);
            formData.append('action', 'mobooking_save_area');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.hideModals();
                        location.reload(); // Refresh to show updated data
                    } else {
                        this.showNotification(response.data?.message || 'Failed to save area', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Error saving area', 'error');
                },
                complete: () => {
                    this.state.isProcessing = false;
                    this.setLoading($btn, false);
                }
            });
        },
        
        toggleAreaStatus: function(areaId, isActive) {
            const data = {
                action: 'mobooking_toggle_area_status',
                id: areaId,
                active: isActive ? 1 : 0,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        location.reload();
                    } else {
                        this.showNotification('Failed to update area status', 'error');
                    }
                }
            });
        },
        
        deleteArea: function(areaId) {
            this.state.currentAreaId = areaId;
            $('#confirmation-message').text('<?php _e('Are you sure you want to delete this service area? This action cannot be undone.', 'mobooking'); ?>');
            this.showModal('#confirmation-modal');
        },
        
        executeConfirmedAction: function() {
            if (!this.state.currentAreaId) return;
            
            const $btn = $('.confirm-action-btn');
            this.setLoading($btn, true);
            
            const data = {
                action: 'mobooking_delete_area',
                id: this.state.currentAreaId,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        this.hideModals();
                        location.reload();
                    } else {
                        this.showNotification('Failed to delete area', 'error');
                    }
                },
                complete: () => {
                    this.setLoading($btn, false);
                }
            });
        },
        
        applyBulkAction: function() {
            const action = $('#bulk-action-select').val();
            const selectedIds = $('.area-checkbox:checked').map((i, el) => $(el).val()).get();
            
            if (!action || selectedIds.length === 0) {
                this.showNotification('Please select an action and at least one area', 'warning');
                return;
            }
            
            // Confirm bulk delete
            if (action === 'delete') {
                this.state.selectedAreas = selectedIds;
                $('#confirmation-message').html(`<?php _e('Are you sure you want to delete', 'mobooking'); ?> <strong>${selectedIds.length}</strong> <?php _e('service areas? This action cannot be undone.', 'mobooking'); ?>`);
                this.showModal('#confirmation-modal');
                return;
            }
            
            // Execute bulk action
            this.executeBulkAction(action, selectedIds);
        },
        
        executeBulkAction: function(action, ids) {
            const data = {
                action: 'mobooking_bulk_area_action',
                bulk_action: action,
                area_ids: ids,
                nonce: this.config.nonce
            };
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        location.reload();
                    } else {
                        this.showNotification('Bulk action failed', 'error');
                    }
                }
            });
        },
        
        filterAreas: function(searchTerm) {
            const $rows = $('.area-row');
            
            if (!searchTerm) {
                $rows.show();
                return;
            }
            
            $rows.each(function() {
                const zipCode = $(this).data('zip');
                const label = $(this).find('.label-display').text();
                
                if (zipCode.includes(searchTerm) || label.toLowerCase().includes(searchTerm.toLowerCase())) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        importZipCodes: function() {
            const zipCodes = $('#zip-codes-import').val().trim();
            
            if (!zipCodes) {
                this.showNotification('Please enter ZIP codes to import', 'warning');
                return;
            }
            
            const data = {
                action: 'mobooking_import_zip_codes',
                zip_codes: zipCodes,
                nonce: this.config.nonce
            };
            
            $('#import-zip-codes').addClass('loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        $('#zip-codes-import').val('');
                        location.reload();
                    } else {
                        this.showNotification('Import failed', 'error');
                    }
                },
                complete: () => {
                    $('#import-zip-codes').removeClass('loading');
                }
            });
        },
        
        exportZipCodes: function() {
            window.location.href = `${this.config.ajaxUrl}?action=mobooking_export_zip_codes&nonce=${this.config.nonce}`;
        },
        
        // Utility methods
        showModal: function(selector) {
            $(selector).fadeIn(300);
            $('body').addClass('modal-open');
        },
        
        hideModals: function() {
            $('.mobooking-modal').fadeOut(300);
            $('body').removeClass('modal-open');
            this.state.currentAreaId = null;
            this.state.selectedAreas = [];
        },
        
        setLoading: function($btn, loading) {
            if (loading) {
                $btn.addClass('loading').prop('disabled', true);
            } else {
                $btn.removeClass('loading').prop('disabled', false);
            }
        },
        
        showNotification: function(message, type = 'info') {
            // Use the same notification system as the booking form
            const $notification = $(`<div class="notification ${type}">${message}</div>`);
            $('body').append($notification);
            
            setTimeout(() => $notification.addClass('show'), 100);
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 4000);
        }
    };
    
    AreasManager.init();
});
</script>