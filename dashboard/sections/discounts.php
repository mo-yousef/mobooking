init: function() {
            console.log('DiscountsManager: Initializing...'); // Debug log
            this.attachEventListeners();
            this.updateDiscountAmountInput();
            this.setMinDate();
            console.log<?php
// dashboard/sections/discounts.php - Discount Codes Management Section
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
                        <path d="M19 5L5 19M9 6.5C9 7.88071 7.88071 9 6.5 9C5.11929 9 4 7.88071 4 6.5C4 5.11929 5.11929 4 6.5 4C7.88071 4 9 5.11929 9 6.5ZM20 17.5C20 18.8807 18.8807 20 17.5 20C16.1193 20 15 18.8807 15 17.5C15 16.1193 16.1193 15 17.5 15C18.8807 15 20 16.1193 20 17.5Z"/>
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
    <div class="modal-content">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3 id="discount-modal-title"><?php _e('Add Discount Code', 'mobooking'); ?></h3>
        
        <form id="discount-form" method="post">
            <input type="hidden" id="discount-id" name="id">
            <?php wp_nonce_field('mobooking-discount-nonce', 'nonce'); ?>
            
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
        </form>
    </div>
</div>

<!-- Bulk Generator Modal -->
<div id="bulk-generator-modal" class="mobooking-modal" style="display:none;">
    <div class="modal-content">
        <button class="modal-close" aria-label="<?php _e('Close', 'mobooking'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </button>
        
        <h3><?php _e('Bulk Generate Discount Codes', 'mobooking'); ?></h3>
        
        <form id="bulk-generator-form">
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

</script>

<style>
/* Discounts Section Styling */
.discounts-section {
    animation: fadeIn 0.4s ease-out;
}

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
}

.stat-label {
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.25rem;
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
}

.search-section {
    flex: 1;
    max-width: 300px;
}

.search-input {
    width: 100%;
}

/* Discounts Grid */
.discounts-container {
    background: white;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    overflow: hidden;
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
}

.discount-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr 1fr 1fr;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid hsl(var(--border));
    transition: background-color 0.2s ease;
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
}

/* Code Display */
.code-display {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.code-text {
    font-family: monospace;
    font-weight: 600;
    background: hsl(var(--muted) / 0.5);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: hsl(var(--primary));
}

.copy-code-btn {
    padding: 0.25rem;
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
    width: 1rem;
    height: 1rem;
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
}

.type-badge.type-fixed {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
}

.type-badge svg {
    width: 0.875rem;
    height: 0.875rem;
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
}

.status-badge.status-inactive {
    background: rgba(107, 114, 128, 0.1);
    color: #6b7280;
}

.status-badge.status-expired {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.status-badge svg {
    width: 0.75rem;
    height: 0.75rem;
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

.btn-icon {
    padding: 0.5rem;
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
}

.btn-icon svg {
    width: 1rem;
    height: 1rem;
}

.btn-icon.btn-danger {
    border-color: #fecaca;
    color: #dc2626;
}

.btn-icon.btn-danger:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: #dc2626;
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

.btn-large {
    padding: 0.875rem 2rem;
    font-size: 1rem;
    font-weight: 600;
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
    width: 1rem;
    height: 1rem;
}

/* Modal Enhancements */
.input-with-button {
    display: flex;
    gap: 0.5rem;
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
    left: 1rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    z-index: 1;
}

.amount-input-wrapper input {
    padding-left: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.field-help {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    margin-top: 0.25rem;
    line-height: 1.4;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.75rem;
    border: 1px solid hsl(var(--border));
    border-radius: var(--radius);
    transition: all 0.2s ease;
}

.checkbox-label:hover {
    background: hsl(var(--muted) / 0.3);
    border-color: hsl(var(--primary));
}

.checkbox-label input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid hsl(var(--border));
}

.spacer {
    flex: 1;
}

/* Loading States */
.loading .btn-text {
    display: none;
}

.loading .btn-loading {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.loading .btn-loading::before {
    content: "";
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
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

@media (max-width: 768px) {
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
    
    .discounts-grid-header {
        display: none;
    }
    
    .discount-row {
        display: block;
        padding: 1rem;
        border-bottom: 1px solid hsl(var(--border));
    }
    
    .grid-cell {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid hsl(var(--border) / 0.3);
    }
    
    .grid-cell:last-child {
        border-bottom: none;
        justify-content: flex-end;
    }
    
    .grid-cell::before {
        content: attr(data-label);
        font-weight: 600;
        color: hsl(var(--muted-foreground));
    }
    
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
}

/* Animation keyframes */
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

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .type-badge,
    .status-badge {
        border: 1px solid currentColor;
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
    .generator-actions {
        display: none;
    }
    
    .discount-row {
        page-break-inside: avoid;
    }
    
    .discounts-section {
        background: white !important;
        color: black !important;
    }
}

/* Dark mode support (if implemented) */
@media (prefers-color-scheme: dark) {
    .stat-card {
        background: hsl(var(--card));
    }
    
    .discounts-container {
        background: hsl(var(--card));
    }
    
    .btn-icon {
        background: hsl(var(--card));
    }
}

/* Focus styles for accessibility */
.copy-code-btn:focus,
.btn-icon:focus {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

.filter-select:focus,
.search-input:focus {
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
</style>

<?php
// Add any additional PHP logic or includes here if needed

// Enqueue additional scripts if required
wp_enqueue_script('wp-color-picker');
wp_enqueue_style('wp-color-picker');

// Add media uploader support
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
        'invalidDate' => __('Please select a future date', 'mobooking')
    ),
    'settings' => array(
        'totalDiscounts' => $total_discounts,
        'activeDiscounts' => $active_discounts,
        'expiredDiscounts' => $expired_discounts,
        'totalUsage' => $total_usage
    )
);

wp_localize_script('mobooking-dashboard', 'mobookingDiscounts', $localize_data);
?>