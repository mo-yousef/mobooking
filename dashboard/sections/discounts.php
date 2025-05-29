<?php
// dashboard/sections/discounts.php - FIXED Discount Codes Management Section
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Initialize discounts manager
try {
    $discounts_manager = new \MoBooking\Discounts\Manager();
} catch (Exception $e) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MoBooking: Failed to initialize Discounts Manager: ' . $e->getMessage());
    }
    // Create a fallback to prevent fatal errors
    $discounts_manager = new stdClass();
    $discounts_manager->get_user_discounts = function() { return array(); };
}

// Get user's discount codes
$discounts = $discounts_manager->get_user_discounts($user_id);

// Get statistics
$total_discounts = count($discounts);
$active_discounts = count(array_filter($discounts, function($discount) { return $discount->active; }));
$expired_discounts = count(array_filter($discounts, function($discount) { 
    return $discount->expiry_date && strtotime($discount->expiry_date) < time(); 
}));
$total_usage = array_sum(array_column($discounts, 'usage_count'));
?>

<div class="discounts-section">
    <div class="discounts-header">
        <div class="discounts-header-content">
            <div class="discounts-title-group">
                <h1 class="discounts-main-title">
                    <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <p class="field-help"><?php _e('Leave empty for no expiry date', 'mobooking'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount-usage-limit"><?php _e('Usage Limit', 'mobooking'); ?></label>
                        <input type="number" id="discount-usage-limit" name="usage_limit" class="form-control" 
                               min="0" placeholder="<?php _e('Unlimited', 'mobooking'); ?>">
                        <p class="field-help"><?php _e('Maximum number of times this code can be used', 'mobooking'); ?></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="discount-active" name="active" value="1" checked>
                        <?php _e('Active (available for use)', 'mobooking'); ?>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="form-actions">
                    <button type="button" id="delete-discount-btn" class="btn-danger" style="display: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m3 6 3 18h12l3-18"></path>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                        </svg>
                        <?php _e('Delete Discount', 'mobooking'); ?>
                    </button>
                    
                    <div class="spacer"></div>
                    
                    <button type="button" id="cancel-discount-btn" class="btn-secondary">
                        <?php _e('Cancel', 'mobooking'); ?>
                    </button>
                    
                    <button type="submit" class="btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17,21 17,13 7,13 7,21"/>
                            <polyline points="7,3 7,8 15,8"/>
                        </svg>
                        <span class="btn-text"><?php _e('Save Discount', 'mobooking'); ?></span>
                        <span class="btn-loading"><?php _e('Saving...', 'mobooking'); ?></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Generator Modal -->
<div id="bulk-generator-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Bulk Generate Discount Codes', 'mobooking'); ?></h3>
            <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="bulk-generator-form">
            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk-count"><?php _e('Number of Codes', 'mobooking'); ?> *</label>
                    <input type="number" id="bulk-count" name="count" class="form-control" 
                           min="1" max="100" value="10" required>
                    <p class="field-help"><?php _e('Generate up to 100 codes at once', 'mobooking'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="bulk-prefix"><?php _e('Code Prefix', 'mobooking'); ?></label>
                    <input type="text" id="bulk-prefix" name="prefix" class="form-control" 
                           placeholder="<?php _e('e.g., SAVE', 'mobooking'); ?>" 
                           pattern="[A-Z]{2,10}" 
                           title="<?php _e('2-10 uppercase letters only', 'mobooking'); ?>">
                    <p class="field-help"><?php _e('Optional prefix for generated codes', 'mobooking'); ?></p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bulk-type"><?php _e('Discount Type', 'mobooking'); ?> *</label>
                        <select id="bulk-type" name="type" class="form-control" required>
                            <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                            <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk-amount"><?php _e('Discount Amount', 'mobooking'); ?> *</label>
                        <div class="amount-input-wrapper">
                            <span class="amount-prefix" id="bulk-amount-prefix">%</span>
                            <input type="number" id="bulk-amount" name="amount" class="form-control" 
                                   min="0" max="100" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bulk-expiry"><?php _e('Expiry Date', 'mobooking'); ?></label>
                        <input type="date" id="bulk-expiry" name="expiry_date" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk-usage-limit"><?php _e('Usage Limit (per code)', 'mobooking'); ?></label>
                        <input type="number" id="bulk-usage-limit" name="usage_limit" class="form-control" 
                               min="0" value="1">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="form-actions">
                    <button type="button" class="btn-secondary cancel-bulk-btn">
                        <?php _e('Cancel', 'mobooking'); ?>
                    </button>
                    
                    <button type="submit" class="btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span class="btn-text"><?php _e('Generate Codes', 'mobooking'); ?></span>
                        <span class="btn-loading"><?php _e('Generating...', 'mobooking'); ?></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmation-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Confirm Action', 'mobooking'); ?></h3>
        </div>
        <div class="modal-body">
            <p id="confirmation-message"><?php _e('Are you sure you want to perform this action?', 'mobooking'); ?></p>
        </div>
        <div class="modal-footer">
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
</div>

<script>
// COMPLETE WORKING JAVASCRIPT FOR DISCOUNTS SECTION
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 MoBooking Discounts Manager initializing...');
    
    const DiscountsManager = {
        // Configuration
        config: {
            ajaxUrl: mobookingDashboard?.ajaxUrl || '/wp-admin/admin-ajax.php',
            nonces: mobookingDashboard?.nonces || {},
            strings: mobookingDiscounts?.strings || {}
        },
        
        // State
        state: {
            currentDiscountId: null,
            isEditing: false,
            pendingAction: null
        },
        
        // Initialize
        init: function() {
            console.log('DiscountsManager: Initializing...'); // Debug log
            this.attachEventListeners();
            this.updateDiscountAmountInput();
            this.setMinDate();
            console.log('DiscountsManager: Initialized successfully'); // Debug log
        },
        
        // Attach event listeners
        attachEventListeners: function() {
            const self = this;
            
            // Add discount buttons
            $('#add-discount-btn, #add-first-discount-btn').on('click', function() {
                console.log('Add discount button clicked'); // Debug log
                self.openAddDiscountModal();
            });
            
            // Edit discount buttons
            $(document).on('click', '.edit-discount-btn', function() {
                console.log('Edit discount button clicked'); // Debug log
                const discountId = $(this).data('discount-id');
                self.openEditDiscountModal(discountId);
            });
            
            // Delete discount buttons
            $(document).on('click', '.delete-discount-btn', function() {
                console.log('Delete discount button clicked'); // Debug log
                const discountId = $(this).data('discount-id');
                self.confirmDeleteDiscount(discountId);
            });
            
            // Toggle discount status
            $(document).on('click', '.toggle-discount-btn', function() {
                console.log('Toggle discount button clicked'); // Debug log
                const discountId = $(this).data('discount-id');
                const isActive = $(this).data('active') === 1;
                self.toggleDiscountStatus(discountId, !isActive);
            });
            
            // Copy code buttons
            $(document).on('click', '.copy-code-btn', function() {
                console.log('Copy code button clicked'); // Debug log
                const code = $(this).data('code');
                self.copyToClipboard(code);
            });
            
            // Modal close buttons
            $('.modal-close, #cancel-discount-btn, .cancel-bulk-btn, .cancel-action-btn').on('click', function() {
                console.log('Modal close button clicked'); // Debug log
                self.closeModals();
            });
            
            // Modal backdrop clicks
            $('.modal-backdrop').on('click', function() {
                console.log('Modal backdrop clicked'); // Debug log
                self.closeModals();
            });
            
            // Discount type change
            $('#discount-type').on('change', function() {
                console.log('Discount type changed'); // Debug log
                self.updateDiscountAmountInput();
            });
            
            // Bulk type change
            $('#bulk-type').on('change', function() {
                console.log('Bulk type changed'); // Debug log
                self.updateBulkAmountInput();
            });
            
            // Generate code button
            $('#generate-code-btn').on('click', function() {
                console.log('Generate code button clicked'); // Debug log
                self.generateDiscountCode();
            });
            
            // Quick action buttons
            $('#generate-welcome-discount').on('click', function() {
                console.log('Generate welcome discount clicked'); // Debug log
                self.generateWelcomeDiscount();
            });
            
            $('#generate-holiday-discount').on('click', function() {
                console.log('Generate holiday discount clicked'); // Debug log
                self.generateHolidayDiscount();
            });
            
            $('#generate-bulk-discounts').on('click', function() {
                console.log('Generate bulk discounts clicked'); // Debug log
                self.openBulkGeneratorModal();
            });
            
            // Form submissions
            $('#discount-form').on('submit', function(e) {
                e.preventDefault();
                console.log('Discount form submitted'); // Debug log
                self.saveDiscount();
            });
            
            $('#bulk-generator-form').on('submit', function(e) {
                e.preventDefault();
                console.log('Bulk generator form submitted'); // Debug log
                self.generateBulkDiscounts();
            });
            
            // Confirmation action
            $('.confirm-action-btn').on('click', function() {
                console.log('Confirm action button clicked'); // Debug log
                self.executeConfirmedAction();
            });
            
            // Filters
            $('#discount-status-filter, #discount-type-filter').on('change', function() {
                console.log('Filter changed'); // Debug log
                self.filterDiscounts();
            });
            
            // Search
            $('#discounts-search-input').on('input', function() {
                console.log('Search input changed'); // Debug log
                self.searchDiscounts();
            });
            
            // Escape key to close modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    console.log('Escape key pressed'); // Debug log
                    self.closeModals();
                }
            });
            
            console.log('DiscountsManager: Event listeners attached');
        },
        
        // Open add discount modal
        openAddDiscountModal: function() {
            console.log('Opening add discount modal');
            this.state.isEditing = false;
            this.state.currentDiscountId = null;
            
            $('#discount-modal-title').text(this.config.strings.addDiscount || 'Add Discount Code');
            $('#discount-form')[0].reset();
            $('#discount-id').val('');
            $('#discount-active').prop('checked', true);
            $('#delete-discount-btn').hide();
            
            this.updateDiscountAmountInput();
            this.setMinDate();
            this.showModal('#discount-modal');
        },
        
        // Open edit discount modal
        openEditDiscountModal: function(discountId) {
            console.log('Opening edit discount modal for ID:', discountId);
            this.state.isEditing = true;
            this.state.currentDiscountId = discountId;
            
            // Get discount data from the DOM
            const $row = $(`.discount-row[data-discount-id="${discountId}"]`);
            if ($row.length === 0) {
                console.error('Discount row not found for ID:', discountId);
                this.showNotification('Discount not found', 'error');
                return;
            }
            
            const code = $row.find('.code-text').text().trim();
            const type = $row.data('type');
            const amountText = $row.find('.amount-display').text().trim();
            
            // Parse amount
            let amount = 0;
            if (type === 'percentage') {
                amount = parseFloat(amountText.replace('%', ''));
            } else {
                amount = parseFloat(amountText.replace(/[^0-9.]/g, ''));
            }
            
            // Get other data
            const expiryText = $row.find('.expiry-date-text').text().trim();
            const usageText = $row.find('.usage-limit').text().trim();
            const isActive = $row.find('.status-badge').hasClass('status-active');
            
            // Fill form
            $('#discount-modal-title').text(this.config.strings.editDiscount || 'Edit Discount Code');
            $('#discount-id').val(discountId);
            $('#discount-code').val(code);
            $('#discount-type').val(type);
            $('#discount-amount').val(amount);
            $('#discount-active').prop('checked', isActive);
            
            // Set usage limit if available
            const usageLimit = usageText && usageText !== 'unlimited' ? parseInt(usageText) : '';
            $('#discount-usage-limit').val(usageLimit);
            
            // Set expiry date if available
            if (expiryText && expiryText !== 'No expiry') {
                const date = new Date(expiryText);
                const dateStr = date.toISOString().split('T')[0];
                $('#discount-expiry').val(dateStr);
            } else {
                $('#discount-expiry').val('');
            }
            
            $('#delete-discount-btn').show();
            this.updateDiscountAmountInput();
            this.showModal('#discount-modal');
        },
        
        // Save discount
        saveDiscount: function() {
            console.log('Saving discount...');
            const $form = $('#discount-form');
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Validate form
            if (!this.validateDiscountForm()) {
                return;
            }
            
            // Disable form and show loading
            this.setLoading($submitBtn, true);
            $form.find('input, select, button').prop('disabled', true);
            
            const formData = new FormData($form[0]);
            formData.append('action', 'mobooking_save_discount');
            formData.append('nonce', this.config.nonces.discount || '');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    console.log('Save discount response:', response);
                    if (response.success) {
                        this.showNotification(response.data.message || 'Discount saved successfully', 'success');
                        this.closeModals();
                        // Reload the page to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotification(response.data.message || 'Failed to save discount', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Save discount error:', error);
                    this.showNotification('Error saving discount: ' + error, 'error');
                },
                complete: () => {
                    this.setLoading($submitBtn, false);
                    $form.find('input, select, button').prop('disabled', false);
                }
            });
        },
        
        // Confirm delete discount
        confirmDeleteDiscount: function(discountId) {
            console.log('Confirming delete discount:', discountId);
            this.state.pendingAction = {
                type: 'delete',
                discountId: discountId
            };
            
            const code = $(`.discount-row[data-discount-id="${discountId}"] .code-text`).text().trim();
            $('#confirmation-message').text(
                `Are you sure you want to delete the discount code "${code}"? This action cannot be undone.`
            );
            
            this.showModal('#confirmation-modal');
        },
        
        // Toggle discount status
        toggleDiscountStatus: function(discountId, newStatus) {
            console.log('Toggling discount status:', discountId, newStatus);
            const $btn = $(`.toggle-discount-btn[data-discount-id="${discountId}"]`);
            
            this.setLoading($btn, true);
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mobooking_toggle_discount',
                    discount_id: discountId,
                    active: newStatus ? 1 : 0,
                    nonce: this.config.nonces.discount || ''
                },
                success: (response) => {
                    console.log('Toggle discount response:', response);
                    if (response.success) {
                        this.showNotification(response.data.message || 'Discount status updated', 'success');
                        // Reload the page to show updated data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotification(response.data.message || 'Failed to update discount status', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Toggle discount error:', error);
                    this.showNotification('Error updating discount status: ' + error, 'error');
                },
                complete: () => {
                    this.setLoading($btn, false);
                }
            });
        },
        
        // Execute confirmed action
        executeConfirmedAction: function() {
            console.log('Executing confirmed action:', this.state.pendingAction);
            if (!this.state.pendingAction) {
                return;
            }
            
            const action = this.state.pendingAction;
            const $btn = $('.confirm-action-btn');
            
            this.setLoading($btn, true);
            
            if (action.type === 'delete') {
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mobooking_delete_discount',
                        discount_id: action.discountId,
                        nonce: this.config.nonces.discount || ''
                    },
                    success: (response) => {
                        console.log('Delete discount response:', response);
                        if (response.success) {
                            this.showNotification(response.data.message || 'Discount deleted successfully', 'success');
                            this.closeModals();
                            // Reload the page to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            this.showNotification(response.data.message || 'Failed to delete discount', 'error');
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Delete discount error:', error);
                        this.showNotification('Error deleting discount: ' + error, 'error');
                    },
                    complete: () => {
                        this.setLoading($btn, false);
                        this.state.pendingAction = null;
                    }
                });
            }
        },
        
        // Generate discount code
        generateDiscountCode: function() {
            console.log('Generating discount code');
            const prefixes = ['SAVE', 'DEAL', 'OFF', 'SPECIAL', 'PROMO'];
            const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
            const number = Math.floor(Math.random() * 90) + 10; // 10-99
            const code = prefix + number;
            
            $('#discount-code').val(code);
            this.showNotification('Code generated: ' + code, 'success');
        },
        
        // Generate welcome discount
        generateWelcomeDiscount: function() {
            console.log('Generating welcome discount');
            $('#discount-code').val('WELCOME10');
            $('#discount-type').val('percentage');
            $('#discount-amount').val('10');
            
            // Set expiry to 30 days from now
            const date = new Date();
            date.setDate(date.getDate() + 30);
            $('#discount-expiry').val(date.toISOString().split('T')[0]);
            
            this.updateDiscountAmountInput();
            this.openAddDiscountModal();
        },
        
        // Generate holiday discount
        generateHolidayDiscount: function() {
            console.log('Generating holiday discount');
            const holidays = ['HOLIDAY', 'XMAS', 'NEWYEAR', 'SUMMER', 'SPRING'];
            const holiday = holidays[Math.floor(Math.random() * holidays.length)];
            const amount = [15, 20, 25][Math.floor(Math.random() * 3)];
            
            $('#discount-code').val(holiday + amount);
            $('#discount-type').val('percentage');
            $('#discount-amount').val(amount.toString());
            
            this.updateDiscountAmountInput();
            this.openAddDiscountModal();
        },
        
        // Open bulk generator modal
        openBulkGeneratorModal: function() {
            console.log('Opening bulk generator modal');
            $('#bulk-generator-form')[0].reset();
            $('#bulk-count').val('10');
            $('#bulk-amount').val('');
            $('#bulk-usage-limit').val('1');
            this.updateBulkAmountInput();
            this.showModal('#bulk-generator-modal');
        },
        
        // Generate bulk discounts
        generateBulkDiscounts: function() {
            console.log('Generating bulk discounts');
            const $form = $('#bulk-generator-form');
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Validate form
            const count = parseInt($('#bulk-count').val());
            const amount = parseFloat($('#bulk-amount').val());
            
            if (!count || count < 1 || count > 100) {
                this.showNotification('Please enter a valid count (1-100)', 'error');
                return;
            }
            
            if (!amount || amount <= 0) {
                this.showNotification('Please enter a valid amount', 'error');
                return;
            }
            
            // Disable form and show loading
            this.setLoading($submitBtn, true);
            $form.find('input, select, button').prop('disabled', true);
            
            const formData = new FormData($form[0]);
            formData.append('action', 'mobooking_generate_bulk_discounts');
            formData.append('nonce', this.config.nonces.discount || '');
            
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    console.log('Bulk generate response:', response);
                    if (response.success) {
                        this.showNotification(response.data.message || 'Bulk discounts generated successfully', 'success');
                        this.closeModals();
                        // Reload the page to show new data
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        this.showNotification(response.data.message || 'Failed to generate bulk discounts', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Bulk generate error:', error);
                    this.showNotification('Error generating bulk discounts: ' + error, 'error');
                },
                complete: () => {
                    this.setLoading($submitBtn, false);
                    $form.find('input, select, button').prop('disabled', false);
                }
            });
        },
        
        // Copy to clipboard
        copyToClipboard: function(text) {
            console.log('Copying to clipboard:', text);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showNotification('Code copied to clipboard!', 'success');
                }).catch(() => {
                    this.fallbackCopy(text);
                });
            } else {
                this.fallbackCopy(text);
            }
        },
        
        // Fallback copy method
        fallbackCopy: function(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                this.showNotification('Code copied to clipboard!', 'success');
            } catch (err) {
                this.showNotification('Failed to copy code', 'error');
            }
            document.body.removeChild(textArea);
        },
        
        // Filter discounts
        filterDiscounts: function() {
            console.log('Filtering discounts');
            const statusFilter = $('#discount-status-filter').val();
            const typeFilter = $('#discount-type-filter').val();
            
            $('.discount-row').each(function() {
                const $row = $(this);
                const rowStatus = $row.data('status');
                const rowType = $row.data('type');
                
                let showRow = true;
                
                if (statusFilter && statusFilter !== rowStatus) {
                    showRow = false;
                }
                
                if (typeFilter && typeFilter !== rowType) {
                    showRow = false;
                }
                
                $row.toggle(showRow);
            });
        },
        
        // Search discounts
        searchDiscounts: function() {
            console.log('Searching discounts');
            const searchTerm = $('#discounts-search-input').val().toLowerCase();
            
            $('.discount-row').each(function() {
                const $row = $(this);
                const code = $row.find('.code-text').text().toLowerCase();
                const showRow = !searchTerm || code.includes(searchTerm);
                $row.toggle(showRow);
            });
        },
        
        // Update discount amount input
        updateDiscountAmountInput: function() {
            const type = $('#discount-type').val();
            const $prefix = $('#amount-prefix');
            const $input = $('#discount-amount');
            const $label = $('#discount-amount-label');
            
            if (type === 'percentage') {
                $prefix.text('%');
                $input.attr('max', '100').attr('step', '1');
                $label.text('Discount Percentage');
            } else {
                $prefix.text('ath d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                    <?php _e('Discount Codes', 'mobooking'); ?>
                </h1>
                <p class="discounts-subtitle"><?php _e('Create and manage discount codes for your services', 'mobooking'); ?></p>
            </div>
            
            <?php if (!empty($discounts)) : ?>
                <div class="discounts-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_discounts; ?></span>
                        <span class="stat-label"><?php _e('Total Codes', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $active_discounts; ?></span>
                        <span class="stat-label"><?php _e('Active', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_usage; ?></span>
                        <span class="stat-label"><?php _e('Total Uses', 'mobooking'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="discounts-header-actions">
            <button type="button" id="add-discount-btn" class="btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <?php _e('Create Discount Code', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <?php if (empty($discounts)) : ?>
        <!-- Empty State -->
        <div class="discounts-empty-state">
            <div class="empty-state-visual">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                </div>
                <div class="empty-state-sparkles">
                    <div class="sparkle sparkle-1"></div>
                    <div class="sparkle sparkle-2"></div>
                    <div class="sparkle sparkle-3"></div>
                </div>
            </div>
            <div class="empty-state-content">
                <h2><?php _e('Boost Sales with Discount Codes', 'mobooking'); ?></h2>
                <p><?php _e('Create promotional codes to attract new customers and reward loyal ones. Offer percentage or fixed amount discounts to increase bookings.', 'mobooking'); ?></p>
                <button type="button" id="add-first-discount-btn" class="btn-primary btn-large">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Create Your First Discount Code', 'mobooking'); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <!-- Discounts Management -->
        <div class="discounts-management">
            <!-- Quick Stats Cards -->
            <div class="discounts-stats-grid">
                <div class="stat-card total-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_discounts; ?></div>
                            <div class="stat-label"><?php _e('Total Codes', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card active-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $active_discounts; ?></div>
                            <div class="stat-label"><?php _e('Active Codes', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card expired-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $expired_discounts; ?></div>
                            <div class="stat-label"><?php _e('Expired', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card total-usage">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_usage; ?></div>
                            <div class="stat-label"><?php _e('Total Uses', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="discounts-toolbar">
                <div class="filters-section">
                    <select id="discount-status-filter" class="filter-select">
                        <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                        <option value="active"><?php _e('Active', 'mobooking'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'mobooking'); ?></option>
                        <option value="expired"><?php _e('Expired', 'mobooking'); ?></option>
                    </select>
                    
                    <select id="discount-type-filter" class="filter-select">
                        <option value=""><?php _e('All Types', 'mobooking'); ?></option>
                        <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                        <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                    </select>
                </div>
                
                <div class="search-section">
                    <input type="text" id="discounts-search-input" placeholder="<?php _e('Search discount codes...', 'mobooking'); ?>" class="search-input">
                </div>
            </div>
            
            <!-- Discounts Table -->
            <div class="discounts-container">
                <div class="discounts-grid-header">
                    <div class="grid-header-cell discount-code"><?php _e('Code', 'mobooking'); ?></div>
                    <div class="grid-header-cell discount-type"><?php _e('Type', 'mobooking'); ?></div>
                    <div class="grid-header-cell discount-amount"><?php _e('Discount', 'mobooking'); ?></div>
                    <div class="grid-header-cell usage-info"><?php _e('Usage', 'mobooking'); ?></div>
                    <div class="grid-header-cell expiry-date"><?php _e('Expires', 'mobooking'); ?></div>
                    <div class="grid-header-cell status"><?php _e('Status', 'mobooking'); ?></div>
                    <div class="grid-header-cell actions"><?php _e('Actions', 'mobooking'); ?></div>
                </div>
                
                <div class="discounts-grid-body" id="discounts-list">
                    <?php foreach ($discounts as $discount) : 
                        $is_expired = $discount->expiry_date && strtotime($discount->expiry_date) < time();
                        $is_limit_reached = $discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit;
                        $effective_status = !$discount->active ? 'inactive' : ($is_expired ? 'expired' : 'active');
                        $usage_percentage = $discount->usage_limit > 0 ? min(100, ($discount->usage_count / $discount->usage_limit) * 100) : 0;
                    ?>
                        <div class="discount-row" data-discount-id="<?php echo esc_attr($discount->id); ?>" data-status="<?php echo esc_attr($effective_status); ?>" data-type="<?php echo esc_attr($discount->type); ?>">
                            <div class="grid-cell discount-code">
                                <div class="code-display">
                                    <span class="code-text"><?php echo esc_html($discount->code); ?></span>
                                    <button class="copy-code-btn" data-code="<?php echo esc_attr($discount->code); ?>" title="<?php _e('Copy code', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="grid-cell discount-type">
                                <span class="type-badge type-<?php echo esc_attr($discount->type); ?>">
                                    <?php if ($discount->type === 'percentage') : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                        </svg>
                                        <?php _e('Percentage', 'mobooking'); ?>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                        <?php _e('Fixed Amount', 'mobooking'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="grid-cell discount-amount">
                                <span class="amount-display">
                                    <?php 
                                    if ($discount->type === 'percentage') {
                                        echo number_format($discount->amount, 0) . '%';
                                    } else {
                                        echo function_exists('wc_price') ? wc_price($discount->amount) : '$' . number_format($discount->amount, 2);
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="grid-cell usage-info">
                                <div class="usage-display">
                                    <div class="usage-numbers">
                                        <span class="usage-count"><?php echo $discount->usage_count; ?></span>
                                        <?php if ($discount->usage_limit > 0) : ?>
                                            <span class="usage-separator">/</span>
                                            <span class="usage-limit"><?php echo $discount->usage_limit; ?></span>
                                        <?php else : ?>
                                            <span class="usage-unlimited"><?php _e('unlimited', 'mobooking'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($discount->usage_limit > 0) : ?>
                                        <div class="usage-progress">
                                            <div class="usage-progress-bar">
                                                <div class="usage-progress-fill" style="width: <?php echo $usage_percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="grid-cell expiry-date">
                                <?php if ($discount->expiry_date) : ?>
                                    <div class="expiry-display">
                                        <span class="expiry-date-text"><?php echo date_i18n(get_option('date_format'), strtotime($discount->expiry_date)); ?></span>
                                        <?php if ($is_expired) : ?>
                                            <span class="expiry-status expired"><?php _e('Expired', 'mobooking'); ?></span>
                                        <?php else : ?>
                                            <?php 
                                            $days_until_expiry = ceil((strtotime($discount->expiry_date) - time()) / (60 * 60 * 24));
                                            if ($days_until_expiry <= 7) : ?>
                                                <span class="expiry-status warning"><?php echo $days_until_expiry . ' ' . _n('day left', 'days left', $days_until_expiry, 'mobooking'); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else : ?>
                                    <span class="no-expiry"><?php _e('No expiry', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid-cell status">
                                <span class="status-badge status-<?php echo esc_attr($effective_status); ?>">
                                    <?php 
                                    switch ($effective_status) {
                                        case 'active':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                            _e('Active', 'mobooking');
                                            break;
                                        case 'inactive':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12h8"/></svg>';
                                            _e('Inactive', 'mobooking');
                                            break;
                                        case 'expired':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
                                            _e('Expired', 'mobooking');
                                            break;
                                    }
                                    ?>
                                </span>
                                <?php if ($is_limit_reached) : ?>
                                    <span class="limit-reached"><?php _e('Limit reached', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid-cell actions">
                                <div class="action-buttons">
                                    <button type="button" class="btn-icon edit-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" title="<?php _e('Edit', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"></path>
                                        </svg>
                                    </button>
                                    
                                    <button type="button" class="btn-icon toggle-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" data-active="<?php echo $discount->active ? '1' : '0'; ?>" title="<?php echo $discount->active ? __('Deactivate', 'mobooking') : __('Activate', 'mobooking'); ?>">
                                        <?php if ($discount->active) : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <path d="M8 12h8"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <button type="button" class="btn-icon btn-danger delete-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" title="<?php _e('Delete', 'mobooking'); ?>">
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
            
            <!-- Discount Code Generator -->
            <div class="discount-generator">
                <h3><?php _e('Quick Actions', 'mobooking'); ?></h3>
                <div class="generator-actions">
                    <button type="button" id="generate-welcome-discount" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Generate Welcome Discount', 'mobooking'); ?>
                    </button>
                    
                    <button type="button" id="generate-holiday-discount" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php _e('Generate Holiday Discount', 'mobooking'); ?>
                    </button>
                    
                    <button type="button" id="generate-bulk-discounts" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Bulk Generate Codes', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Discount Modal (Add/Edit) -->
<div id="discount-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="discount-modal-title"><?php _e('Add Discount Code', 'mobooking'); ?></h3>
            <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="discount-form" method="post">
            <input type="hidden" id="discount-id" name="id">
            <?php wp_nonce_field('mobooking-discount-nonce', 'nonce'); ?>
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="discount-code"><?php _e('Discount Code', 'mobooking'); ?> *</label>
                    <div class="input-with-button">
                        <input type="text" id="discount-code" name="code" class="form-control" 
                               placeholder="<?php _e('e.g., SAVE20', 'mobooking'); ?>" 
                               pattern="[A-Z0-9]{3,20}" 
                               title="<?php _e('3-20 characters, letters and numbers only', 'mobooking'); ?>"
                               required>
                        <button type="button" id="generate-code-btn" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            <?php _e('Generate', 'mobooking'); ?>
                        </button>
                    </div>
                    <p class="field-help"><?php _e('Enter a unique code (3-20 characters, uppercase letters and numbers only)', 'mobooking'); ?></p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount-type"><?php _e('Discount Type', 'mobooking'); ?> *</label>
                        <select id="discount-type" name="type" class="form-control" required>
                            <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                            <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount-amount" id="discount-amount-label"><?php _e('Discount Amount', 'mobooking'); ?> *</label>
                        <div class="amount-input-wrapper">
                            <span class="amount-prefix" id="amount-prefix">%</span>
                            <input type="number" id="discount-amount" name="amount" class="form-control" 
                                   min="0" max="100" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount-expiry"><?php _e('Expiry Date', 'mobooking'); ?></label>
                        <input type="date" id="discount-expiry" name="expiry_date" class="form-control">
                        <p);
                $input.attr('max', '10000').attr('step', '0.01');
                $label.text('Discount Amount');
            }
        },
        
        // Update bulk amount input
        updateBulkAmountInput: function() {
            const type = $('#bulk-type').val();
            const $prefix = $('#bulk-amount-prefix');
            const $input = $('#bulk-amount');
            
            if (type === 'percentage') {
                $prefix.text('%');
                $input.attr('max', '100').attr('step', '1');
            } else {
                $prefix.text('ath d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                    <?php _e('Discount Codes', 'mobooking'); ?>
                </h1>
                <p class="discounts-subtitle"><?php _e('Create and manage discount codes for your services', 'mobooking'); ?></p>
            </div>
            
            <?php if (!empty($discounts)) : ?>
                <div class="discounts-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_discounts; ?></span>
                        <span class="stat-label"><?php _e('Total Codes', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $active_discounts; ?></span>
                        <span class="stat-label"><?php _e('Active', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_usage; ?></span>
                        <span class="stat-label"><?php _e('Total Uses', 'mobooking'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="discounts-header-actions">
            <button type="button" id="add-discount-btn" class="btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <?php _e('Create Discount Code', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <?php if (empty($discounts)) : ?>
        <!-- Empty State -->
        <div class="discounts-empty-state">
            <div class="empty-state-visual">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                </div>
                <div class="empty-state-sparkles">
                    <div class="sparkle sparkle-1"></div>
                    <div class="sparkle sparkle-2"></div>
                    <div class="sparkle sparkle-3"></div>
                </div>
            </div>
            <div class="empty-state-content">
                <h2><?php _e('Boost Sales with Discount Codes', 'mobooking'); ?></h2>
                <p><?php _e('Create promotional codes to attract new customers and reward loyal ones. Offer percentage or fixed amount discounts to increase bookings.', 'mobooking'); ?></p>
                <button type="button" id="add-first-discount-btn" class="btn-primary btn-large">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Create Your First Discount Code', 'mobooking'); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <!-- Discounts Management -->
        <div class="discounts-management">
            <!-- Quick Stats Cards -->
            <div class="discounts-stats-grid">
                <div class="stat-card total-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_discounts; ?></div>
                            <div class="stat-label"><?php _e('Total Codes', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card active-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $active_discounts; ?></div>
                            <div class="stat-label"><?php _e('Active Codes', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card expired-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $expired_discounts; ?></div>
                            <div class="stat-label"><?php _e('Expired', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card total-usage">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_usage; ?></div>
                            <div class="stat-label"><?php _e('Total Uses', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="discounts-toolbar">
                <div class="filters-section">
                    <select id="discount-status-filter" class="filter-select">
                        <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                        <option value="active"><?php _e('Active', 'mobooking'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'mobooking'); ?></option>
                        <option value="expired"><?php _e('Expired', 'mobooking'); ?></option>
                    </select>
                    
                    <select id="discount-type-filter" class="filter-select">
                        <option value=""><?php _e('All Types', 'mobooking'); ?></option>
                        <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                        <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                    </select>
                </div>
                
                <div class="search-section">
                    <input type="text" id="discounts-search-input" placeholder="<?php _e('Search discount codes...', 'mobooking'); ?>" class="search-input">
                </div>
            </div>
            
            <!-- Discounts Table -->
            <div class="discounts-container">
                <div class="discounts-grid-header">
                    <div class="grid-header-cell discount-code"><?php _e('Code', 'mobooking'); ?></div>
                    <div class="grid-header-cell discount-type"><?php _e('Type', 'mobooking'); ?></div>
                    <div class="grid-header-cell discount-amount"><?php _e('Discount', 'mobooking'); ?></div>
                    <div class="grid-header-cell usage-info"><?php _e('Usage', 'mobooking'); ?></div>
                    <div class="grid-header-cell expiry-date"><?php _e('Expires', 'mobooking'); ?></div>
                    <div class="grid-header-cell status"><?php _e('Status', 'mobooking'); ?></div>
                    <div class="grid-header-cell actions"><?php _e('Actions', 'mobooking'); ?></div>
                </div>
                
                <div class="discounts-grid-body" id="discounts-list">
                    <?php foreach ($discounts as $discount) : 
                        $is_expired = $discount->expiry_date && strtotime($discount->expiry_date) < time();
                        $is_limit_reached = $discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit;
                        $effective_status = !$discount->active ? 'inactive' : ($is_expired ? 'expired' : 'active');
                        $usage_percentage = $discount->usage_limit > 0 ? min(100, ($discount->usage_count / $discount->usage_limit) * 100) : 0;
                    ?>
                        <div class="discount-row" data-discount-id="<?php echo esc_attr($discount->id); ?>" data-status="<?php echo esc_attr($effective_status); ?>" data-type="<?php echo esc_attr($discount->type); ?>">
                            <div class="grid-cell discount-code">
                                <div class="code-display">
                                    <span class="code-text"><?php echo esc_html($discount->code); ?></span>
                                    <button class="copy-code-btn" data-code="<?php echo esc_attr($discount->code); ?>" title="<?php _e('Copy code', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="grid-cell discount-type">
                                <span class="type-badge type-<?php echo esc_attr($discount->type); ?>">
                                    <?php if ($discount->type === 'percentage') : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                        </svg>
                                        <?php _e('Percentage', 'mobooking'); ?>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                        <?php _e('Fixed Amount', 'mobooking'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="grid-cell discount-amount">
                                <span class="amount-display">
                                    <?php 
                                    if ($discount->type === 'percentage') {
                                        echo number_format($discount->amount, 0) . '%';
                                    } else {
                                        echo function_exists('wc_price') ? wc_price($discount->amount) : '$' . number_format($discount->amount, 2);
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="grid-cell usage-info">
                                <div class="usage-display">
                                    <div class="usage-numbers">
                                        <span class="usage-count"><?php echo $discount->usage_count; ?></span>
                                        <?php if ($discount->usage_limit > 0) : ?>
                                            <span class="usage-separator">/</span>
                                            <span class="usage-limit"><?php echo $discount->usage_limit; ?></span>
                                        <?php else : ?>
                                            <span class="usage-unlimited"><?php _e('unlimited', 'mobooking'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($discount->usage_limit > 0) : ?>
                                        <div class="usage-progress">
                                            <div class="usage-progress-bar">
                                                <div class="usage-progress-fill" style="width: <?php echo $usage_percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="grid-cell expiry-date">
                                <?php if ($discount->expiry_date) : ?>
                                    <div class="expiry-display">
                                        <span class="expiry-date-text"><?php echo date_i18n(get_option('date_format'), strtotime($discount->expiry_date)); ?></span>
                                        <?php if ($is_expired) : ?>
                                            <span class="expiry-status expired"><?php _e('Expired', 'mobooking'); ?></span>
                                        <?php else : ?>
                                            <?php 
                                            $days_until_expiry = ceil((strtotime($discount->expiry_date) - time()) / (60 * 60 * 24));
                                            if ($days_until_expiry <= 7) : ?>
                                                <span class="expiry-status warning"><?php echo $days_until_expiry . ' ' . _n('day left', 'days left', $days_until_expiry, 'mobooking'); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else : ?>
                                    <span class="no-expiry"><?php _e('No expiry', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid-cell status">
                                <span class="status-badge status-<?php echo esc_attr($effective_status); ?>">
                                    <?php 
                                    switch ($effective_status) {
                                        case 'active':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                            _e('Active', 'mobooking');
                                            break;
                                        case 'inactive':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12h8"/></svg>';
                                            _e('Inactive', 'mobooking');
                                            break;
                                        case 'expired':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
                                            _e('Expired', 'mobooking');
                                            break;
                                    }
                                    ?>
                                </span>
                                <?php if ($is_limit_reached) : ?>
                                    <span class="limit-reached"><?php _e('Limit reached', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid-cell actions">
                                <div class="action-buttons">
                                    <button type="button" class="btn-icon edit-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" title="<?php _e('Edit', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"></path>
                                        </svg>
                                    </button>
                                    
                                    <button type="button" class="btn-icon toggle-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" data-active="<?php echo $discount->active ? '1' : '0'; ?>" title="<?php echo $discount->active ? __('Deactivate', 'mobooking') : __('Activate', 'mobooking'); ?>">
                                        <?php if ($discount->active) : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <path d="M8 12h8"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <button type="button" class="btn-icon btn-danger delete-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" title="<?php _e('Delete', 'mobooking'); ?>">
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
            
            <!-- Discount Code Generator -->
            <div class="discount-generator">
                <h3><?php _e('Quick Actions', 'mobooking'); ?></h3>
                <div class="generator-actions">
                    <button type="button" id="generate-welcome-discount" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Generate Welcome Discount', 'mobooking'); ?>
                    </button>
                    
                    <button type="button" id="generate-holiday-discount" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php _e('Generate Holiday Discount', 'mobooking'); ?>
                    </button>
                    
                    <button type="button" id="generate-bulk-discounts" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Bulk Generate Codes', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Discount Modal (Add/Edit) -->
<div id="discount-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="discount-modal-title"><?php _e('Add Discount Code', 'mobooking'); ?></h3>
            <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="discount-form" method="post">
            <input type="hidden" id="discount-id" name="id">
            <?php wp_nonce_field('mobooking-discount-nonce', 'nonce'); ?>
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="discount-code"><?php _e('Discount Code', 'mobooking'); ?> *</label>
                    <div class="input-with-button">
                        <input type="text" id="discount-code" name="code" class="form-control" 
                               placeholder="<?php _e('e.g., SAVE20', 'mobooking'); ?>" 
                               pattern="[A-Z0-9]{3,20}" 
                               title="<?php _e('3-20 characters, letters and numbers only', 'mobooking'); ?>"
                               required>
                        <button type="button" id="generate-code-btn" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            <?php _e('Generate', 'mobooking'); ?>
                        </button>
                    </div>
                    <p class="field-help"><?php _e('Enter a unique code (3-20 characters, uppercase letters and numbers only)', 'mobooking'); ?></p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount-type"><?php _e('Discount Type', 'mobooking'); ?> *</label>
                        <select id="discount-type" name="type" class="form-control" required>
                            <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                            <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount-amount" id="discount-amount-label"><?php _e('Discount Amount', 'mobooking'); ?> *</label>
                        <div class="amount-input-wrapper">
                            <span class="amount-prefix" id="amount-prefix">%</span>
                            <input type="number" id="discount-amount" name="amount" class="form-control" 
                                   min="0" max="100" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount-expiry"><?php _e('Expiry Date', 'mobooking'); ?></label>
                        <input type="date" id="discount-expiry" name="expiry_date" class="form-control">
                        <p);
                $input.attr('max', '10000').attr('step', '0.01');
            }
        },
        
        // Set minimum date
        setMinDate: function() {
            const today = new Date().toISOString().split('T')[0];
            $('#discount-expiry, #bulk-expiry').attr('min', today);
        },
        
        // Validate discount form
        validateDiscountForm: function() {
            const code = $('#discount-code').val().trim();
            const amount = parseFloat($('#discount-amount').val());
            const type = $('#discount-type').val();
            
            if (!code) {
                this.showNotification('Please enter a discount code', 'error');
                $('#discount-code').focus();
                return false;
            }
            
            if (!/^[A-Z0-9]{3,20}$/.test(code)) {
                this.showNotification('Code must be 3-20 characters, uppercase letters and numbers only', 'error');
                $('#discount-code').focus();
                return false;
            }
            
            if (!amount || amount <= 0) {
                this.showNotification('Please enter a valid discount amount', 'error');
                $('#discount-amount').focus();
                return false;
            }
            
            if (type === 'percentage' && amount > 100) {
                this.showNotification('Percentage discount cannot exceed 100%', 'error');
                $('#discount-amount').focus();
                return false;
            }
            
            const expiryDate = $('#discount-expiry').val();
            if (expiryDate) {
                const today = new Date();
                const expiry = new Date(expiryDate);
                if (expiry <= today) {
                    this.showNotification('Expiry date must be in the future', 'error');
                    $('#discount-expiry').focus();
                    return false;
                }
            }
            
            return true;
        },
        
        // Show modal
        showModal: function(modalId) {
            console.log('Showing modal:', modalId);
            $(modalId).fadeIn(300);
            $('body').addClass('modal-open');
            
            // Focus first input
            setTimeout(() => {
                $(modalId).find('input:visible:first').focus();
            }, 300);
        },
        
        // Close modals
        closeModals: function() {
            console.log('Closing modals');
            $('.mobooking-modal').fadeOut(300);
            $('body').removeClass('modal-open');
            this.state.pendingAction = null;
        },
        
        // Set loading state
        setLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $button.find('.btn-text').hide();
                $button.find('.btn-loading').show();
            } else {
                $button.removeClass('loading').prop('disabled', false);
                $button.find('.btn-text').show();
                $button.find('.btn-loading').hide();
            }
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            console.log('Showing notification:', message, type);
            
            // Remove existing notifications
            $('.discount-notification').remove();
            
            const colors = {
                success: '#22c55e',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            
            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const $notification = $(`
                <div class="discount-notification ${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    min-width: 300px;
                    max-width: 500px;
                    padding: 16px 20px;
                    background: ${colors[type]};
                    color: white;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    font-weight: 500;
                ">
                    <span style="font-size: 18px; font-weight: bold; flex-shrink: 0;">${icons[type]}</span>
                    <span style="flex: 1; line-height: 1.4;">${message}</span>
                    <button style="
                        background: rgba(255, 255, 255, 0.2);
                        border: none;
                        color: white;
                        width: 24px;
                        height: 24px;
                        border-radius: 50%;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 18px;
                        flex-shrink: 0;
                    " onclick="$(this).parent().remove()">×</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Show notification with animation
            setTimeout(() => {
                $notification.css({
                    transform: 'translateX(0)',
                    opacity: 1
                });
            }, 100);
            
            // Auto-hide after 5 seconds (except for errors)
            if (type !== 'error') {
                setTimeout(() => {
                    $notification.css({
                        transform: 'translateX(100%)',
                        opacity: 0
                    });
                    setTimeout(() => $notification.remove(), 300);
                }, 5000);
            }
        }
    };
    
    // Initialize the discounts manager
    DiscountsManager.init();
    
    // Make it globally available for debugging
    window.MoBookingDiscountsManager = DiscountsManager;
    
    console.log('✅ MoBooking Discounts Manager ready');
});
</script>

<style>
/* Discounts Section Styling */
.discounts-section {
    animation: fadeIn 0.4s ease-out;
}

/* Modal improvements */
.mobooking-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s ease;
}

.mobooking-modal:not([style*="display: none"]) .modal-content {
    transform: scale(1);
    opacity: 1;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid hsl(var(--border));
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.modal-close {
    padding: 8px;
    border: none;
    background: transparent;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: hsl(var(--muted) / 0.5);
    color: hsl(var(--foreground));
}

.modal-close svg {
    width: 20px;
    height: 20px;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.3);
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
}

/* Form improvements */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: hsl(var(--foreground));
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: hsl(var(--primary));
    box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.input-with-button {
    display: flex;
    gap: 8px;
}

.input-with-button input {
    flex: 1;
}

.amount-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.amount-prefix {
    position: absolute;
    left: 14px;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    z-index: 1;
}

.amount-input-wrapper input {
    padding-left: 36px;
}

.field-help {
    font-size: 12px;
    color: hsl(var(--muted-foreground));
    margin-top: 4px;
    line-height: 1.4;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    padding: 12px;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    transition: all 0.2s ease;
}

.checkbox-label:hover {
    background: hsl(var(--muted) / 0.3);
    border-color: hsl(var(--primary));
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.spacer {
    flex: 1;
}

/* Button improvements */
.btn-primary, .btn-secondary, .btn-danger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: hsl(var(--primary));
    border-color: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.btn-primary:hover {
    background: hsl(var(--primary) / 0.9);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.btn-secondary {
    background: hsl(var(--secondary));
    border-color: hsl(var(--border));
    color: hsl(var(--secondary-foreground));
}

.btn-secondary:hover {
    background: hsl(var(--accent));
    border-color: hsl(var(--primary));
}

.btn-danger {
    background: hsl(var(--destructive));
    border-color: hsl(var(--destructive));
    color: hsl(var(--destructive-foreground));
}

.btn-danger:hover {
    background: hsl(var(--destructive) / 0.9);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px hsl(var(--destructive) / 0.3);
}

.btn-large {
    padding: 14px 24px;
    font-size: 16px;
    font-weight: 600;
}

.btn-icon {
    padding: 8px;
    border: 1px solid hsl(var(--border));
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: hsl(var(--muted) / 0.5);
    border-color: hsl(var(--primary));
    transform: translateY(-1px);
}

.btn-icon svg {
    width: 16px;
    height: 16px;
}

.btn-icon.btn-danger {
    border-color: hsl(var(--destructive) / 0.3);
    color: hsl(var(--destructive));
}

.btn-icon.btn-danger:hover {
    background: hsl(var(--destructive) / 0.1);
    border-color: hsl(var(--destructive));
}

/* Loading states */
.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.loading .btn-loading::before {
    content: "";
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
}

/* Header styling */
.discounts-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
}

.discounts-header-content {
    display: flex;
    align-items: flex-start;
    gap: 3rem;
    flex: 1;
}

.discounts-title-group {
    flex: 1;
}

.discounts-main-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: hsl(var(--foreground));
}

.title-icon {
    width: 28px;
    height: 28px;
    color: hsl(var(--primary));
}

.discounts-subtitle {
    margin: 0;
    color: hsl(var(--muted-foreground));
    font-size: 1rem;
}

.discounts-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: hsl(var(--primary));
}

.stat-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

/* Stats Cards */
.discounts-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px hsl(var(--primary) / 0.1);
}

.stat-card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    flex-shrink: 0;
}

.stat-icon svg {
    width: 1.5rem;
    height: 1.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: hsl(var(--foreground));
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

/* Toolbar */
.discounts-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: var(--radius);
}

.filters-section {
    display: flex;
    gap: 1rem;
}

.filter-select {
    min-width: 150px;
    padding: 8px 12px;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: white;
}

.search-section {
    flex: 1;
    max-width: 300px;
}

.search-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid hsl(var(--border));
    border-radius: 6px;
    background: white;
}

/* Discounts Grid */
.discounts-container {
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.discounts-grid-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: hsl(var(--muted) / 0.5);
    border-bottom: 1px solid hsl(var(--border));
    font-weight: 600;
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.discount-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
    transition: all 0.2s ease;
}

.discount-row:hover {
    background: hsl(var(--muted) / 0.3);
}

.discount-row:last-child {
    border-bottom: none;
}

.grid-cell {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

/* Code Display */
.code-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.code-text {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
    font-weight: 600;
    background: hsl(var(--muted) / 0.5);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: hsl(var(--primary));
    font-size: 0.8rem;
}

.copy-code-btn {
    padding: 4px;
    border: none;
    background: transparent;
    color: hsl(var(--muted-foreground));
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.copy-code-btn:hover {
    background: hsl(var(--muted) / 0.5);
    color: hsl(var(--foreground));
}

.copy-code-btn svg {
    width: 14px;
    height: 14px;
}

/* Type Badge */
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.type-badge.type-percentage {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.type-badge.type-fixed {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.type-badge svg {
    width: 12px;
    height: 12px;
}

/* Amount Display */
.amount-display {
    font-weight: 600;
    color: hsl(var(--foreground));
}

/* Usage Display */
.usage-display {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.usage-numbers {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
}

.usage-count {
    font-weight: 600;
    color: hsl(var(--primary));
}

.usage-separator {
    color: hsl(var(--muted-foreground));
}

.usage-limit {
    color: hsl(var(--muted-foreground));
}

.usage-unlimited {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    font-style: italic;
}

.usage-progress {
    width: 100%;
}

.usage-progress-bar {
    width: 100%;
    height: 4px;
    background: hsl(var(--muted));
    border-radius: 2px;
    overflow: hidden;
}

.usage-progress-fill {
    height: 100%;
    background: hsl(var(--primary));
    border-radius: 2px;
    transition: width 0.3s ease;
}

/* Expiry Display */
.expiry-display {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.expiry-date-text {
    font-size: 0.875rem;
    color: hsl(var(--foreground));
}

.expiry-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.125rem 0.375rem;
    border-radius: 12px;
}

.expiry-status.expired {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.expiry-status.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.no-expiry {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    font-style: italic;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.status-active {
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.status-badge.status-inactive {
    background: rgba(107, 114, 128, 0.1);
    color: #6b7280;
    border: 1px solid rgba(107, 114, 128, 0.2);
}

.status-badge.status-expired {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.status-badge svg {
    width: 12px;
    height: 12px;
}

.limit-reached {
    display: block;
    font-size: 0.65rem;
    color: #f59e0b;
    font-weight: 500;
    margin-top: 0.125rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

/* Empty State */
.discounts-empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-visual {
    position: relative;
    display: inline-block;
    margin-bottom: 2rem;
}

.empty-state-icon {
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.empty-state-icon svg {
    width: 2.5rem;
    height: 2.5rem;
    color: hsl(var(--primary));
}

.empty-state-sparkles {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.sparkle {
    position: absolute;
    width: 8px;
    height: 8px;
    background: hsl(var(--primary));
    border-radius: 50%;
    animation: sparkle 2s infinite;
}

.sparkle-1 {
    top: 10%;
    right: 15%;
    animation-delay: 0s;
}

.sparkle-2 {
    top: 60%;
    right: 10%;
    animation-delay: 0.7s;
}

.sparkle-3 {
    top: 20%;
    left: 10%;
    animation-delay: 1.4s;
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0);
    }
    50% {
        opacity: 1;
        transform: scale(1);
    }
}

.empty-state-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin-bottom: 0.5rem;
}

.empty-state-content p {
    color: hsl(var(--muted-foreground));
    max-width: 500px;
    margin: 0 auto 2rem;
    line-height: 1.6;
}

/* Discount Generator */
.discount-generator {
    margin-top: 2rem;
    padding: 1.5rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: var(--radius);
}

.discount-generator h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}

.generator-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.generator-actions .btn-secondary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.generator-actions svg {
    width: 16px;
    height: 16px;
}

/* Animations */
@keyframes fadeIn {
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
        transform: rotate(360deg);
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .discounts-grid-header,
    .discount-row {
        grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 0.8fr 0.8fr;
        font-size: 0.875rem;
    }
}

@media (max-width: 968px) {
    .discounts-header {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .discounts-header-content {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .discounts-stats {
        align-self: center;
    }
    
    .discounts-header-actions {
        align-self: stretch;
    }
    
    .discounts-toolbar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .filters-section {
        width: 100%;
        justify-content: space-between;
    }
    
    .search-section {
        max-width: none;
    }
    
    /* Mobile table view */
    .discounts-grid-header {
        display: none;
    }
    
    .discount-row {
        display: block;
        padding: 1rem;
        border-bottom: 1px solid hsl(var(--border));
        margin-bottom: 1rem;
        border-radius: 8px;
        background: hsl(var(--card));
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .discount-row .grid-cell {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid hsl(var(--border) / 0.3);
    }
    
    .discount-row .grid-cell:last-child {
        border-bottom: none;
        justify-content: flex-end;
        padding-top: 1rem;
    }
    
    .discount-row .grid-cell::before {
        content: attr(data-label);
        font-weight: 600;
        color: hsl(var(--muted-foreground));
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    /* Add data labels for mobile */
    .discount-row .discount-code::before { content: "Code"; }
    .discount-row .discount-type::before { content: "Type"; }
    .discount-row .discount-amount::before { content: "Amount"; }
    .discount-row .usage-info::before { content: "Usage"; }
    .discount-row .expiry-date::before { content: "Expires"; }
    .discount-row .status::before { content: "Status"; }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .generator-actions {
        flex-direction: column;
    }
    
    .action-buttons {
        justify-content: flex-end;
        margin-top: 0.5rem;
    }
}

@media (max-width: 480px) {
    .discounts-main-title {
        font-size: 1.5rem;
    }
    
    .discounts-stats {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
    }
    
    .stat-item {
        padding: 0.5rem;
        background: hsl(var(--muted) / 0.3);
        border-radius: var(--radius);
    }
    
    .empty-state-content {
        padding: 0 1rem;
    }
    
    .empty-state-content h2 {
        font-size: 1.25rem;
    }
    
    .btn-large {
        width: 100%;
        justify-content: center;
    }
    
    .modal-content {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
    }
    
    .discounts-stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 16px;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .type-badge,
    .status-badge,
    .expiry-status {
        border-width: 2px;
    }
    
    .code-text {
        border: 1px solid hsl(var(--primary));
    }
    
    .usage-progress-bar {
        border: 1px solid hsl(var(--border));
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .sparkle {
        animation: none;
        opacity: 0.3;
    }
}

/* Print styles */
@media print {
    .discounts-header-actions,
    .discounts-toolbar,
    .action-buttons,
    .generator-actions,
    .modal-close {
        display: none;
    }
    
    .discount-row {
        page-break-inside: avoid;
    }
    
    .discounts-section {
        background: white !important;
        color: black !important;
    }
    
    .mobooking-modal {
        display: none !important;
    }
}

/* Focus styles for accessibility */
.copy-code-btn:focus,
.btn-icon:focus,
.btn-primary:focus,
.btn-secondary:focus,
.btn-danger:focus {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

.filter-select:focus,
.search-input:focus,
.form-control:focus {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

/* Custom scrollbar for modal content */
.modal-content {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted-foreground)) transparent;
}

.modal-content::-webkit-scrollbar {
    width: 6px;
}

.modal-content::-webkit-scrollbar-track {
    background: transparent;
}

.modal-content::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground));
    border-radius: 3px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--foreground));
}

/* Body modal styles */
body.modal-open {
    overflow: hidden;
}

/* Additional utility classes */
.text-success {
    color: #16a34a !important;
}

.text-error {
    color: #dc2626 !important;
}

.text-warning {
    color: #d97706 !important;
}

.bg-success {
    background-color: rgba(34, 197, 94, 0.1) !important;
}

.bg-error {
    background-color: rgba(239, 68, 68, 0.1) !important;
}

.bg-warning {
    background-color: rgba(245, 158, 11, 0.1) !important;
}

/* Loading indicator for table rows */
.discount-row.loading {
    opacity: 0.6;
    pointer-events: none;
}

.discount-row.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 1rem;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border: 2px solid hsl(var(--muted-foreground));
    border-radius: 50%;
    border-top-color: hsl(var(--primary));
    animation: spin 1s linear infinite;
}

/* Success animations */
.discount-row.success {
    background: rgba(34, 197, 94, 0.1);
    animation: successPulse 0.6s ease-out;
}

@keyframes successPulse {
    0% {
        transform: scale(1);
        background: rgba(34, 197, 94, 0.2);
    }
    50% {
        transform: scale(1.02);
        background: rgba(34, 197, 94, 0.15);
    }
    100% {
        transform: scale(1);
        background: rgba(34, 197, 94, 0.1);
    }
}

/* Error states */
.form-control.error {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.field-error {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.field-error::before {
    content: '⚠';
    font-size: 0.875rem;
}
</style>

<?php
// Enqueue additional scripts and styles
wp_enqueue_script('wp-color-picker');
wp_enqueue_style('wp-color-picker');
wp_enqueue_media();

// Localize script for AJAX calls and translations
$localize_data = array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking-discount-nonce'),
    'userId' => $user_id,
    'strings' => array(
        'saving' => __('Saving...', 'mobooking'),
        'saved' => __('Discount saved successfully', 'mobooking'),
        'error' => __('An error occurred', 'mobooking'),
        'copied' => __('Copied to clipboard', 'mobooking'),
        'confirmDelete' => __('Are you sure you want to delete this discount code? This action cannot be undone.', 'mobooking'),
        'generating' => __('Generating codes...', 'mobooking'),
        'generated' => __('Discount codes generated successfully', 'mobooking'),
        'invalidCode' => __('Please enter a valid discount code (3-20 characters, letters and numbers only)', 'mobooking'),
        'duplicateCode' => __('This discount code already exists', 'mobooking'),
        'required' => __('This field is required', 'mobooking'),
        'maxAmount' => __('Amount cannot exceed maximum value', 'mobooking'),
        'invalidDate' => __('Please select a future date', 'mobooking'),
        'addDiscount' => __('Add Discount Code', 'mobooking'),
        'editDiscount' => __('Edit Discount Code', 'mobooking')
    ),
    'settings' => array(
        'totalDiscounts' => $total_discounts,
        'activeDiscounts' => $active_discounts,
        'expiredDiscounts' => $expired_discounts,
        'totalUsage' => $total_usage
    )
);

wp_localize_script('mobooking-dashboard', 'mobookingDiscounts', $localize_data);
?>ath d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                    <?php _e('Discount Codes', 'mobooking'); ?>
                </h1>
                <p class="discounts-subtitle"><?php _e('Create and manage discount codes for your services', 'mobooking'); ?></p>
            </div>
            
            <?php if (!empty($discounts)) : ?>
                <div class="discounts-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_discounts; ?></span>
                        <span class="stat-label"><?php _e('Total Codes', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $active_discounts; ?></span>
                        <span class="stat-label"><?php _e('Active', 'mobooking'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_usage; ?></span>
                        <span class="stat-label"><?php _e('Total Uses', 'mobooking'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="discounts-header-actions">
            <button type="button" id="add-discount-btn" class="btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <?php _e('Create Discount Code', 'mobooking'); ?>
            </button>
        </div>
    </div>
    
    <?php if (empty($discounts)) : ?>
        <!-- Empty State -->
        <div class="discounts-empty-state">
            <div class="empty-state-visual">
                <div class="empty-state-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                    </svg>
                </div>
                <div class="empty-state-sparkles">
                    <div class="sparkle sparkle-1"></div>
                    <div class="sparkle sparkle-2"></div>
                    <div class="sparkle sparkle-3"></div>
                </div>
            </div>
            <div class="empty-state-content">
                <h2><?php _e('Boost Sales with Discount Codes', 'mobooking'); ?></h2>
                <p><?php _e('Create promotional codes to attract new customers and reward loyal ones. Offer percentage or fixed amount discounts to increase bookings.', 'mobooking'); ?></p>
                <button type="button" id="add-first-discount-btn" class="btn-primary btn-large">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <?php _e('Create Your First Discount Code', 'mobooking'); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <!-- Discounts Management -->
        <div class="discounts-management">
            <!-- Quick Stats Cards -->
            <div class="discounts-stats-grid">
                <div class="stat-card total-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_discounts; ?></div>
                            <div class="stat-label"><?php _e('Total Codes', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card active-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $active_discounts; ?></div>
                            <div class="stat-label"><?php _e('Active Codes', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card expired-discounts">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 6v6l4 2"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $expired_discounts; ?></div>
                            <div class="stat-label"><?php _e('Expired', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card total-usage">
                    <div class="stat-card-header">
                        <div class="stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo $total_usage; ?></div>
                            <div class="stat-label"><?php _e('Total Uses', 'mobooking'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="discounts-toolbar">
                <div class="filters-section">
                    <select id="discount-status-filter" class="filter-select">
                        <option value=""><?php _e('All Statuses', 'mobooking'); ?></option>
                        <option value="active"><?php _e('Active', 'mobooking'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'mobooking'); ?></option>
                        <option value="expired"><?php _e('Expired', 'mobooking'); ?></option>
                    </select>
                    
                    <select id="discount-type-filter" class="filter-select">
                        <option value=""><?php _e('All Types', 'mobooking'); ?></option>
                        <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                        <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                    </select>
                </div>
                
                <div class="search-section">
                    <input type="text" id="discounts-search-input" placeholder="<?php _e('Search discount codes...', 'mobooking'); ?>" class="search-input">
                </div>
            </div>
            
            <!-- Discounts Table -->
            <div class="discounts-container">
                <div class="discounts-grid-header">
                    <div class="grid-header-cell discount-code"><?php _e('Code', 'mobooking'); ?></div>
                    <div class="grid-header-cell discount-type"><?php _e('Type', 'mobooking'); ?></div>
                    <div class="grid-header-cell discount-amount"><?php _e('Discount', 'mobooking'); ?></div>
                    <div class="grid-header-cell usage-info"><?php _e('Usage', 'mobooking'); ?></div>
                    <div class="grid-header-cell expiry-date"><?php _e('Expires', 'mobooking'); ?></div>
                    <div class="grid-header-cell status"><?php _e('Status', 'mobooking'); ?></div>
                    <div class="grid-header-cell actions"><?php _e('Actions', 'mobooking'); ?></div>
                </div>
                
                <div class="discounts-grid-body" id="discounts-list">
                    <?php foreach ($discounts as $discount) : 
                        $is_expired = $discount->expiry_date && strtotime($discount->expiry_date) < time();
                        $is_limit_reached = $discount->usage_limit > 0 && $discount->usage_count >= $discount->usage_limit;
                        $effective_status = !$discount->active ? 'inactive' : ($is_expired ? 'expired' : 'active');
                        $usage_percentage = $discount->usage_limit > 0 ? min(100, ($discount->usage_count / $discount->usage_limit) * 100) : 0;
                    ?>
                        <div class="discount-row" data-discount-id="<?php echo esc_attr($discount->id); ?>" data-status="<?php echo esc_attr($effective_status); ?>" data-type="<?php echo esc_attr($discount->type); ?>">
                            <div class="grid-cell discount-code">
                                <div class="code-display">
                                    <span class="code-text"><?php echo esc_html($discount->code); ?></span>
                                    <button class="copy-code-btn" data-code="<?php echo esc_attr($discount->code); ?>" title="<?php _e('Copy code', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="grid-cell discount-type">
                                <span class="type-badge type-<?php echo esc_attr($discount->type); ?>">
                                    <?php if ($discount->type === 'percentage') : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
                                        </svg>
                                        <?php _e('Percentage', 'mobooking'); ?>
                                    <?php else : ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                        <?php _e('Fixed Amount', 'mobooking'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="grid-cell discount-amount">
                                <span class="amount-display">
                                    <?php 
                                    if ($discount->type === 'percentage') {
                                        echo number_format($discount->amount, 0) . '%';
                                    } else {
                                        echo function_exists('wc_price') ? wc_price($discount->amount) : '$' . number_format($discount->amount, 2);
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <div class="grid-cell usage-info">
                                <div class="usage-display">
                                    <div class="usage-numbers">
                                        <span class="usage-count"><?php echo $discount->usage_count; ?></span>
                                        <?php if ($discount->usage_limit > 0) : ?>
                                            <span class="usage-separator">/</span>
                                            <span class="usage-limit"><?php echo $discount->usage_limit; ?></span>
                                        <?php else : ?>
                                            <span class="usage-unlimited"><?php _e('unlimited', 'mobooking'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($discount->usage_limit > 0) : ?>
                                        <div class="usage-progress">
                                            <div class="usage-progress-bar">
                                                <div class="usage-progress-fill" style="width: <?php echo $usage_percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="grid-cell expiry-date">
                                <?php if ($discount->expiry_date) : ?>
                                    <div class="expiry-display">
                                        <span class="expiry-date-text"><?php echo date_i18n(get_option('date_format'), strtotime($discount->expiry_date)); ?></span>
                                        <?php if ($is_expired) : ?>
                                            <span class="expiry-status expired"><?php _e('Expired', 'mobooking'); ?></span>
                                        <?php else : ?>
                                            <?php 
                                            $days_until_expiry = ceil((strtotime($discount->expiry_date) - time()) / (60 * 60 * 24));
                                            if ($days_until_expiry <= 7) : ?>
                                                <span class="expiry-status warning"><?php echo $days_until_expiry . ' ' . _n('day left', 'days left', $days_until_expiry, 'mobooking'); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else : ?>
                                    <span class="no-expiry"><?php _e('No expiry', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid-cell status">
                                <span class="status-badge status-<?php echo esc_attr($effective_status); ?>">
                                    <?php 
                                    switch ($effective_status) {
                                        case 'active':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/></svg>';
                                            _e('Active', 'mobooking');
                                            break;
                                        case 'inactive':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12h8"/></svg>';
                                            _e('Inactive', 'mobooking');
                                            break;
                                        case 'expired':
                                            echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>';
                                            _e('Expired', 'mobooking');
                                            break;
                                    }
                                    ?>
                                </span>
                                <?php if ($is_limit_reached) : ?>
                                    <span class="limit-reached"><?php _e('Limit reached', 'mobooking'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid-cell actions">
                                <div class="action-buttons">
                                    <button type="button" class="btn-icon edit-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" title="<?php _e('Edit', 'mobooking'); ?>">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="m18.5 2.5-9.5 9.5L4 15l1-4 9.5-9.5 3 3Z"></path>
                                        </svg>
                                    </button>
                                    
                                    <button type="button" class="btn-icon toggle-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" data-active="<?php echo $discount->active ? '1' : '0'; ?>" title="<?php echo $discount->active ? __('Deactivate', 'mobooking') : __('Activate', 'mobooking'); ?>">
                                        <?php if ($discount->active) : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <path d="M8 12h8"/>
                                            </svg>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2ZM8 12l2 2 4-4"/>
                                            </svg>
                                        <?php endif; ?>
                                    </button>
                                    
                                    <button type="button" class="btn-icon btn-danger delete-discount-btn" data-discount-id="<?php echo esc_attr($discount->id); ?>" title="<?php _e('Delete', 'mobooking'); ?>">
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
            
            <!-- Discount Code Generator -->
            <div class="discount-generator">
                <h3><?php _e('Quick Actions', 'mobooking'); ?></h3>
                <div class="generator-actions">
                    <button type="button" id="generate-welcome-discount" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Generate Welcome Discount', 'mobooking'); ?>
                    </button>
                    
                    <button type="button" id="generate-holiday-discount" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <?php _e('Generate Holiday Discount', 'mobooking'); ?>
                    </button>
                    
                    <button type="button" id="generate-bulk-discounts" class="btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Bulk Generate Codes', 'mobooking'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Discount Modal (Add/Edit) -->
<div id="discount-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="discount-modal-title"><?php _e('Add Discount Code', 'mobooking'); ?></h3>
            <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <form id="discount-form" method="post">
            <input type="hidden" id="discount-id" name="id">
            <?php wp_nonce_field('mobooking-discount-nonce', 'nonce'); ?>
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="discount-code"><?php _e('Discount Code', 'mobooking'); ?> *</label>
                    <div class="input-with-button">
                        <input type="text" id="discount-code" name="code" class="form-control" 
                               placeholder="<?php _e('e.g., SAVE20', 'mobooking'); ?>" 
                               pattern="[A-Z0-9]{3,20}" 
                               title="<?php _e('3-20 characters, letters and numbers only', 'mobooking'); ?>"
                               required>
                        <button type="button" id="generate-code-btn" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            <?php _e('Generate', 'mobooking'); ?>
                        </button>
                    </div>
                    <p class="field-help"><?php _e('Enter a unique code (3-20 characters, uppercase letters and numbers only)', 'mobooking'); ?></p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount-type"><?php _e('Discount Type', 'mobooking'); ?> *</label>
                        <select id="discount-type" name="type" class="form-control" required>
                            <option value="percentage"><?php _e('Percentage', 'mobooking'); ?></option>
                            <option value="fixed"><?php _e('Fixed Amount', 'mobooking'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount-amount" id="discount-amount-label"><?php _e('Discount Amount', 'mobooking'); ?> *</label>
                        <div class="amount-input-wrapper">
                            <span class="amount-prefix" id="amount-prefix">%</span>
                            <input type="number" id="discount-amount" name="amount" class="form-control" 
                                   min="0" max="100" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount-expiry"><?php _e('Expiry Date', 'mobooking'); ?></label>
                        <input type="date" id="discount-expiry" name="expiry_date" class="form-control">
                        <p