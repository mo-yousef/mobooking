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
$discounts = is_callable(array($discounts_manager, 'get_user_discounts')) 
    ? $discounts_manager->get_user_discounts($user_id) 
    : array();

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
?>








<script>
// FIXED DISCOUNTS JAVASCRIPT - ADDRESSING ALL POTENTIAL ISSUES
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 MoBooking Discounts Manager initializing...');
    
    const DiscountsManager = {
        // Configuration - Fixed potential undefined issues
        config: {
            ajaxUrl: (typeof mobookingDashboard !== 'undefined' && mobookingDashboard?.ajaxUrl) || '/wp-admin/admin-ajax.php',
            nonces: (typeof mobookingDashboard !== 'undefined' && mobookingDashboard?.nonces) || {},
            strings: (typeof mobookingDiscounts !== 'undefined' && mobookingDiscounts?.strings) || {}
        },
        
        // State
        state: {
            currentDiscountId: null,
            isEditing: false,
            pendingAction: null,
            initialized: false
        },
        
        // Initialize with better error handling
        init: function() {
            console.log('DiscountsManager: Starting initialization...');
            
            try {
                // Check if required elements exist
                if (!this.checkRequiredElements()) {
                    console.error('DiscountsManager: Required elements not found, retrying in 500ms...');
                    setTimeout(() => this.init(), 500);
                    return;
                }
                
                this.attachEventListeners();
                this.updateDiscountAmountInput();
                this.setMinDate();
                this.state.initialized = true;
                
                console.log('✅ DiscountsManager: Initialized successfully');
            } catch (error) {
                console.error('DiscountsManager initialization error:', error);
                setTimeout(() => this.init(), 1000); // Retry after 1 second
            }
        },
        
        // Check if required DOM elements exist
        checkRequiredElements: function() {
            const requiredElements = [
                '#add-discount-btn, #add-first-discount-btn',
                '#discount-modal',
                '#discount-form'
            ];
            
            for (const selector of requiredElements) {
                if ($(selector).length === 0) {
                    console.warn(`Required element not found: ${selector}`);
                    return false;
                }
            }
            return true;
        },
        
        // Attach event listeners with better error handling
        attachEventListeners: function() {
            const self = this;
            
            try {
                // Add discount buttons - Fixed selector issue
                $(document).on('click', '#add-discount-btn, #add-first-discount-btn', function(e) {
                    e.preventDefault();
                    console.log('Add discount button clicked');
                    self.openAddDiscountModal();
                });
                
                // Edit discount buttons - Using event delegation
                $(document).on('click', '.edit-discount-btn', function(e) {
                    e.preventDefault();
                    console.log('Edit discount button clicked');
                    const discountId = $(this).data('discount-id');
                    if (discountId) {
                        self.openEditDiscountModal(discountId);
                    } else {
                        console.error('No discount ID found');
                    }
                });
                
                // Delete discount buttons
                $(document).on('click', '.delete-discount-btn', function(e) {
                    e.preventDefault();
                    console.log('Delete discount button clicked');
                    const discountId = $(this).data('discount-id');
                    if (discountId) {
                        self.confirmDeleteDiscount(discountId);
                    }
                });
                
                // Toggle discount status
                $(document).on('click', '.toggle-discount-btn', function(e) {
                    e.preventDefault();
                    console.log('Toggle discount button clicked');
                    const discountId = $(this).data('discount-id');
                    const isActive = $(this).data('active') == 1; // Fixed comparison
                    if (discountId) {
                        self.toggleDiscountStatus(discountId, !isActive);
                    }
                });
                
                // Copy code buttons
                $(document).on('click', '.copy-code-btn', function(e) {
                    e.preventDefault();
                    console.log('Copy code button clicked');
                    const code = $(this).data('code');
                    if (code) {
                        self.copyToClipboard(code);
                    }
                });
                
                // Modal close buttons - Fixed selector
                $(document).on('click', '.modal-close, #cancel-discount-btn, .cancel-bulk-btn, .cancel-action-btn', function(e) {
                    e.preventDefault();
                    console.log('Modal close button clicked');
                    self.closeModals();
                });
                
                // Modal backdrop clicks
                $(document).on('click', '.modal-backdrop', function(e) {
                    console.log('Modal backdrop clicked');
                    self.closeModals();
                });
                
                // Prevent modal close when clicking inside modal content
                $(document).on('click', '.modal-content', function(e) {
                    e.stopPropagation();
                });
                
                // Discount type change
                $(document).on('change', '#discount-type', function() {
                    console.log('Discount type changed');
                    self.updateDiscountAmountInput();
                });
                
                // Bulk type change
                $(document).on('change', '#bulk-type', function() {
                    console.log('Bulk type changed');
                    self.updateBulkAmountInput();
                });
                
                // Generate code button
                $(document).on('click', '#generate-code-btn', function(e) {
                    e.preventDefault();
                    console.log('Generate code button clicked');
                    self.generateDiscountCode();
                });
                
                // Quick action buttons
                $(document).on('click', '#generate-welcome-discount', function(e) {
                    e.preventDefault();
                    console.log('Generate welcome discount clicked');
                    self.generateWelcomeDiscount();
                });
                
                $(document).on('click', '#generate-holiday-discount', function(e) {
                    e.preventDefault();
                    console.log('Generate holiday discount clicked');
                    self.generateHolidayDiscount();
                });
                
                $(document).on('click', '#generate-bulk-discounts', function(e) {
                    e.preventDefault();
                    console.log('Generate bulk discounts clicked');
                    self.openBulkGeneratorModal();
                });
                
                // Form submissions
                $(document).on('submit', '#discount-form', function(e) {
                    e.preventDefault();
                    console.log('Discount form submitted');
                    self.saveDiscount();
                });
                
                $(document).on('submit', '#bulk-generator-form', function(e) {
                    e.preventDefault();
                    console.log('Bulk generator form submitted');
                    self.generateBulkDiscounts();
                });
                
                // Confirmation action
                $(document).on('click', '.confirm-action-btn', function(e) {
                    e.preventDefault();
                    console.log('Confirm action button clicked');
                    self.executeConfirmedAction();
                });
                
                // Filters
                $(document).on('change', '#discount-status-filter, #discount-type-filter', function() {
                    console.log('Filter changed');
                    self.filterDiscounts();
                });
                
                // Search - Using input event for real-time search
                $(document).on('input', '#discounts-search-input', function() {
                    console.log('Search input changed');
                    self.searchDiscounts();
                });
                
                // Escape key to close modals
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' || e.keyCode === 27) {
                        console.log('Escape key pressed');
                        self.closeModals();
                    }
                });
                
                console.log('✅ DiscountsManager: Event listeners attached');
                
            } catch (error) {
                console.error('Error attaching event listeners:', error);
            }
        },
        
        // Open add discount modal
        openAddDiscountModal: function() {
            try {
                console.log('Opening add discount modal');
                this.state.isEditing = false;
                this.state.currentDiscountId = null;
                
                const $modal = $('#discount-modal');
                if ($modal.length === 0) {
                    console.error('Discount modal not found');
                    this.showNotification('Modal not found', 'error');
                    return;
                }
                
                $('#discount-modal-title').text(this.config.strings.addDiscount || 'Add Discount Code');
                $('#discount-form')[0].reset();
                $('#discount-id').val('');
                $('#discount-active').prop('checked', true);
                $('#delete-discount-btn').hide();
                
                this.updateDiscountAmountInput();
                this.setMinDate();
                this.showModal('#discount-modal');
                
            } catch (error) {
                console.error('Error opening add discount modal:', error);
                this.showNotification('Error opening modal', 'error');
            }
        },
        
        // Open edit discount modal - Fixed data extraction
        openEditDiscountModal: function(discountId) {
            try {
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
                
                // Extract data more safely
                const code = $row.find('.code-text').text().trim();
                const type = $row.data('type') || 'percentage';
                const amountText = $row.find('.amount-display').text().trim();
                
                // Parse amount more robustly
                let amount = 0;
                if (type === 'percentage') {
                    const match = amountText.match(/(\d+(?:\.\d+)?)/);
                    amount = match ? parseFloat(match[1]) : 0;
                } else {
                    const match = amountText.match(/(\d+(?:\.\d+)?)/);
                    amount = match ? parseFloat(match[1]) : 0;
                }
                
                // Get other data safely
                const expiryElement = $row.find('.expiry-date-text');
                const expiryText = expiryElement.length ? expiryElement.text().trim() : '';
                
                const usageElement = $row.find('.usage-limit');
                const usageText = usageElement.length ? usageElement.text().trim() : '';
                
                const isActive = $row.find('.status-badge').hasClass('status-active');
                
                // Fill form
                $('#discount-modal-title').text(this.config.strings.editDiscount || 'Edit Discount Code');
                $('#discount-id').val(discountId);
                $('#discount-code').val(code);
                $('#discount-type').val(type);
                $('#discount-amount').val(amount);
                $('#discount-active').prop('checked', isActive);
                
                // Set usage limit if available
                if (usageText && usageText !== 'unlimited') {
                    const usageLimit = parseInt(usageText);
                    if (!isNaN(usageLimit)) {
                        $('#discount-usage-limit').val(usageLimit);
                    }
                } else {
                    $('#discount-usage-limit').val('');
                }
                
                // Set expiry date if available
                if (expiryText && expiryText !== 'No expiry') {
                    try {
                        const date = new Date(expiryText);
                        if (!isNaN(date.getTime())) {
                            const dateStr = date.toISOString().split('T')[0];
                            $('#discount-expiry').val(dateStr);
                        }
                    } catch (e) {
                        console.warn('Could not parse expiry date:', expiryText);
                        $('#discount-expiry').val('');
                    }
                } else {
                    $('#discount-expiry').val('');
                }
                
                $('#delete-discount-btn').show();
                this.updateDiscountAmountInput();
                this.showModal('#discount-modal');
                
            } catch (error) {
                console.error('Error opening edit discount modal:', error);
                this.showNotification('Error opening edit modal', 'error');
            }
        },
        
        // Save discount with better error handling
        saveDiscount: function() {
            try {
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
                
                // Prepare form data
                const discountId = $('#discount-id').val();
                const postData = {
                    action: 'mobooking_save_discount',
                    nonce: this.config.nonces.discount || '',
                    code: $('#discount-code').val().trim().toUpperCase(),
                    type: $('#discount-type').val(),
                    amount: parseFloat($('#discount-amount').val()),
                    expiry_date: $('#discount-expiry').val(),
                    usage_limit: $('#discount-usage-limit').val() || 0,
                    active: $('#discount-active').is(':checked') ? 1 : 0
                };
                
                // Add ID for editing
                if (discountId) {
                    postData.id = discountId;
                    postData.discount_id = discountId; // Send both formats
                }
                
                console.log('Save request data:', postData);
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: postData,
                    timeout: 30000, // 30 second timeout
                    beforeSend: function(xhr) {
                        console.log('Sending save request...');
                    },
                    success: (response) => {
                        console.log('Save discount response:', response);
                        try {
                            if (response && response.success) {
                                this.showNotification(response.data?.message || 'Discount saved successfully', 'success');
                                this.closeModals();
                                // Reload the page to show updated data
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                console.error('Save failed:', response);
                                this.showNotification(response?.data?.message || response?.data || 'Failed to save discount', 'error');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            this.showNotification('Invalid response from server', 'error');
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Save discount error:', {xhr, status, error});
                        console.error('Response text:', xhr.responseText);
                        
                        let errorMessage = 'Error saving discount';
                        
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please try again.';
                        } else {
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.data) {
                                    errorMessage = errorResponse.data;
                                }
                            } catch (e) {
                                if (error) {
                                    errorMessage += ': ' + error;
                                }
                            }
                        }
                        
                        this.showNotification(errorMessage, 'error');
                    },
                    complete: () => {
                        this.setLoading($submitBtn, false);
                        $form.find('input, select, button').prop('disabled', false);
                    }
                });
                
            } catch (error) {
                console.error('Error in saveDiscount:', error);
                this.showNotification('Error preparing save request', 'error');
            }
        },
        
        // Rest of the methods remain the same but with better error handling...
        confirmDeleteDiscount: function(discountId) {
            try {
                console.log('Confirming delete discount:', discountId);
                this.state.pendingAction = {
                    type: 'delete',
                    discountId: discountId
                };
                
                const $codeElement = $(`.discount-row[data-discount-id="${discountId}"] .code-text`);
                const code = $codeElement.length ? $codeElement.text().trim() : 'this discount';
                
                $('#confirmation-message').text(
                    `Are you sure you want to delete the discount code "${code}"? This action cannot be undone.`
                );
                
                this.showModal('#confirmation-modal');
            } catch (error) {
                console.error('Error in confirmDeleteDiscount:', error);
                this.showNotification('Error preparing delete confirmation', 'error');
            }
        },
        
        // Toggle discount status with better error handling
        toggleDiscountStatus: function(discountId, newStatus) {
            try {
                console.log('Toggling discount status:', discountId, newStatus);
                const $btn = $(`.toggle-discount-btn[data-discount-id="${discountId}"]`);
                
                if ($btn.length === 0) {
                    console.error('Toggle button not found for discount:', discountId);
                    return;
                }
                
                this.setLoading($btn, true);
                
                const postData = {
                    action: 'mobooking_toggle_discount',
                    id: discountId,
                    discount_id: discountId, // Send both formats
                    active: newStatus ? 1 : 0,
                    nonce: this.config.nonces.discount || ''
                };
                
                console.log('Toggle request data:', postData);
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: postData,
                    timeout: 15000,
                    beforeSend: function(xhr) {
                        console.log('Sending toggle request...');
                    },
                    success: (response) => {
                        console.log('Toggle discount response:', response);
                        if (response && response.success) {
                            this.showNotification(response.data?.message || 'Discount status updated', 'success');
                            // Reload the page to show updated data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            console.error('Toggle failed:', response);
                            this.showNotification(response?.data?.message || response?.data || 'Failed to update discount status', 'error');
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Toggle discount error:', {xhr, status, error});
                        console.error('Response text:', xhr.responseText);
                        
                        let errorMessage = 'Error updating discount status';
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.data) {
                                errorMessage = errorResponse.data;
                            }
                        } catch (e) {
                            // Keep default error message
                        }
                        
                        this.showNotification(errorMessage, 'error');
                    },
                    complete: () => {
                        this.setLoading($btn, false);
                    }
                });
                
            } catch (error) {
                console.error('Error in toggleDiscountStatus:', error);
                this.showNotification('Error toggling discount status', 'error');
            }
        },
        
        // Execute confirmed action
        executeConfirmedAction: function() {
            try {
                console.log('Executing confirmed action:', this.state.pendingAction);
                if (!this.state.pendingAction) {
                    console.error('No pending action found');
                    return;
                }
                
                const action = this.state.pendingAction;
                const $btn = $('.confirm-action-btn');
                
                this.setLoading($btn, true);
                
                if (action.type === 'delete' && action.discountId) {
                    console.log('Sending delete request for discount ID:', action.discountId);
                    
                    // Prepare the data object
                    const postData = {
                        action: 'mobooking_delete_discount',
                        id: action.discountId, // Try both 'id' and 'discount_id'
                        discount_id: action.discountId,
                        nonce: this.config.nonces.discount || ''
                    };
                    
                    console.log('Delete request data:', postData);
                    
                    $.ajax({
                        url: this.config.ajaxUrl,
                        type: 'POST',
                        data: postData,
                        timeout: 15000,
                        beforeSend: function(xhr) {
                            console.log('Sending delete request...');
                        },
                        success: (response) => {
                            console.log('Delete discount response:', response);
                            if (response && response.success) {
                                this.showNotification(response.data?.message || 'Discount deleted successfully', 'success');
                                this.closeModals();
                                // Reload the page to show updated data
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                console.error('Delete failed:', response);
                                this.showNotification(response?.data?.message || response?.data || 'Failed to delete discount', 'error');
                            }
                        },
                        error: (xhr, status, error) => {
                            console.error('Delete discount error:', {xhr, status, error});
                            console.error('Response text:', xhr.responseText);
                            
                            let errorMessage = 'Error deleting discount';
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.data) {
                                    errorMessage = errorResponse.data;
                                }
                            } catch (e) {
                                // Keep default error message
                            }
                            
                            this.showNotification(errorMessage, 'error');
                        },
                        complete: () => {
                            this.setLoading($btn, false);
                            this.state.pendingAction = null;
                        }
                    });
                } else {
                    console.error('Invalid delete action or missing discount ID:', action);
                    this.showNotification('Invalid delete request', 'error');
                    this.setLoading($btn, false);
                    this.state.pendingAction = null;
                }
            } catch (error) {
                console.error('Error executing confirmed action:', error);
                this.showNotification('Error executing action', 'error');
                this.setLoading($('.confirm-action-btn'), false);
                this.state.pendingAction = null;
            }
        },
        
        // Generate discount code
        generateDiscountCode: function() {
            try {
                console.log('Generating discount code');
                const prefixes = ['SAVE', 'DEAL', 'OFF', 'SPECIAL', 'PROMO'];
                const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
                const number = Math.floor(Math.random() * 90) + 10; // 10-99
                const code = prefix + number;
                
                $('#discount-code').val(code);
                this.showNotification('Code generated: ' + code, 'success');
            } catch (error) {
                console.error('Error generating discount code:', error);
                this.showNotification('Error generating code', 'error');
            }
        },
        
        // Generate welcome discount
        generateWelcomeDiscount: function() {
            try {
                console.log('Generating welcome discount');
                this.openAddDiscountModal();
                
                // Set values after modal is open
                setTimeout(() => {
                    $('#discount-code').val('WELCOME10');
                    $('#discount-type').val('percentage');
                    $('#discount-amount').val('10');
                    
                    // Set expiry to 30 days from now
                    const date = new Date();
                    date.setDate(date.getDate() + 30);
                    $('#discount-expiry').val(date.toISOString().split('T')[0]);
                    
                    this.updateDiscountAmountInput();
                }, 100);
                
            } catch (error) {
                console.error('Error generating welcome discount:', error);
                this.showNotification('Error generating welcome discount', 'error');
            }
        },
        
        // Generate holiday discount
        generateHolidayDiscount: function() {
            try {
                console.log('Generating holiday discount');
                const holidays = ['HOLIDAY', 'XMAS', 'NEWYEAR', 'SUMMER', 'SPRING'];
                const holiday = holidays[Math.floor(Math.random() * holidays.length)];
                const amount = [15, 20, 25][Math.floor(Math.random() * 3)];
                
                this.openAddDiscountModal();
                
                // Set values after modal is open
                setTimeout(() => {
                    $('#discount-code').val(holiday + amount);
                    $('#discount-type').val('percentage');
                    $('#discount-amount').val(amount.toString());
                    this.updateDiscountAmountInput();
                }, 100);
                
            } catch (error) {
                console.error('Error generating holiday discount:', error);
                this.showNotification('Error generating holiday discount', 'error');
            }
        },
        
        // Copy to clipboard with fallback
        copyToClipboard: function(text) {
            try {
                console.log('Copying to clipboard:', text);
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.showNotification('Code copied to clipboard!', 'success');
                    }).catch(() => {
                        this.fallbackCopy(text);
                    });
                } else {
                    this.fallbackCopy(text);
                }
            } catch (error) {
                console.error('Error copying to clipboard:', error);
                this.fallbackCopy(text);
            }
        },
        
        // Fallback copy method
        fallbackCopy: function(text) {
            try {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    this.showNotification('Code copied to clipboard!', 'success');
                } else {
                    this.showNotification('Please manually copy: ' + text, 'info');
                }
            } catch (err) {
                console.error('Fallback copy failed:', err);
                this.showNotification('Copy failed. Code: ' + text, 'error');
            }
        },
        
        // Filter discounts
        filterDiscounts: function() {
            try {
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
            } catch (error) {
                console.error('Error filtering discounts:', error);
            }
        },
        
        // Search discounts
        searchDiscounts: function() {
            try {
                console.log('Searching discounts');
                const searchTerm = $('#discounts-search-input').val().toLowerCase();
                
                $('.discount-row').each(function() {
                    const $row = $(this);
                    const code = $row.find('.code-text').text().toLowerCase();
                    const showRow = !searchTerm || code.includes(searchTerm);
                    $row.toggle(showRow);
                });
            } catch (error) {
                console.error('Error searching discounts:', error);
            }
        },
        
        // Update discount amount input
        updateDiscountAmountInput: function() {
            try {
                const type = $('#discount-type').val();
                const $prefix = $('#amount-prefix');
                const $input = $('#discount-amount');
                const $label = $('#discount-amount-label');
                
                if (type === 'percentage') {
                    $prefix.text('%');
                    $input.attr('max', '100').attr('step', '1');
                    if ($label.length) $label.text('Discount Percentage');
                } else {
                    $prefix.text('$');
                    $input.attr('max', '10000').attr('step', '0.01');
                    if ($label.length) $label.text('Discount Amount');
                }
            } catch (error) {
                console.error('Error updating discount amount input:', error);
            }
        },
        
        // Update bulk amount input
        updateBulkAmountInput: function() {
            try {
                const type = $('#bulk-type').val();
                const $prefix = $('#bulk-amount-prefix');
                const $input = $('#bulk-amount');
                
                if (type === 'percentage') {
                    $prefix.text('%');
                    $input.attr('max', '100').attr('step', '1');
                } else {
                    $prefix.text('$');
                    $input.attr('max', '10000').attr('step', '0.01');
                }
            } catch (error) {
                console.error('Error updating bulk amount input:', error);
            }
        },
        
        // Set minimum date
        setMinDate: function() {
            try {
                const today = new Date().toISOString().split('T')[0];
                $('#discount-expiry, #bulk-expiry').attr('min', today);
            } catch (error) {
                console.error('Error setting minimum date:', error);
            }
        },
        
        // Validate discount form
        validateDiscountForm: function() {
            try {
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
            } catch (error) {
                console.error('Error validating form:', error);
                this.showNotification('Error validating form', 'error');
                return false;
            }
        },
        
        // Show modal
        showModal: function(modalId) {
            try {
                console.log('Showing modal:', modalId);
                const $modal = $(modalId);
                if ($modal.length === 0) {
                    console.error('Modal not found:', modalId);
                    return;
                }
                
                $modal.show().css('display', 'flex');
                $('body').addClass('modal-open');
                
                // Focus first input
                setTimeout(() => {
                    $modal.find('input:visible:first').focus();
                }, 100);
            } catch (error) {
                console.error('Error showing modal:', error);
            }
        },
        
        // Close modals
        closeModals: function() {
            try {
                console.log('Closing modals');
                $('.mobooking-modal').hide();
                $('body').removeClass('modal-open');
                this.state.pendingAction = null;
            } catch (error) {
                console.error('Error closing modals:', error);
            }
        },
        
        // Set loading state
        setLoading: function($button, loading) {
            try {
                if (!$button || $button.length === 0) {
                    console.warn('Button not found for loading state');
                    return;
                }
                
                if (loading) {
                    $button.addClass('loading').prop('disabled', true);
                    const $btnText = $button.find('.btn-text');
                    const $btnLoading = $button.find('.btn-loading');
                    
                    if ($btnText.length) $btnText.hide();
                    if ($btnLoading.length) $btnLoading.show();
                } else {
                    $button.removeClass('loading').prop('disabled', false);
                    const $btnText = $button.find('.btn-text');
                    const $btnLoading = $button.find('.btn-loading');
                    
                    if ($btnText.length) $btnText.show();
                    if ($btnLoading.length) $btnLoading.hide();
                }
            } catch (error) {
                console.error('Error setting loading state:', error);
            }
        },
        
        // Open bulk generator modal
        openBulkGeneratorModal: function() {
            try {
                console.log('Opening bulk generator modal');
                const $modal = $('#bulk-generator-modal');
                if ($modal.length === 0) {
                    console.error('Bulk generator modal not found');
                    this.showNotification('Bulk generator not available', 'error');
                    return;
                }
                
                $('#bulk-generator-form')[0].reset();
                $('#bulk-count').val('10');
                $('#bulk-amount').val('');
                $('#bulk-usage-limit').val('1');
                this.updateBulkAmountInput();
                this.showModal('#bulk-generator-modal');
            } catch (error) {
                console.error('Error opening bulk generator modal:', error);
                this.showNotification('Error opening bulk generator', 'error');
            }
        },
        
        // Generate bulk discounts
        generateBulkDiscounts: function() {
            try {
                console.log('Generating bulk discounts');
                const $form = $('#bulk-generator-form');
                const $submitBtn = $form.find('button[type="submit"]');
                
                // Validate form
                const count = parseInt($('#bulk-count').val());
                const amount = parseFloat($('#bulk-amount').val());
                
                if (!count || count < 1 || count > 100) {
                    this.showNotification('Please enter a valid count (1-100)', 'error');
                    $('#bulk-count').focus();
                    return;
                }
                
                if (!amount || amount <= 0) {
                    this.showNotification('Please enter a valid amount', 'error');
                    $('#bulk-amount').focus();
                    return;
                }
                
                // Disable form and show loading
                this.setLoading($submitBtn, true);
                $form.find('input, select, button').prop('disabled', true);
                
                const formData = {
                    action: 'mobooking_generate_bulk_discounts',
                    nonce: this.config.nonces.discount || '',
                    count: count,
                    prefix: $('#bulk-prefix').val().trim().toUpperCase(),
                    type: $('#bulk-type').val(),
                    amount: amount,
                    expiry_date: $('#bulk-expiry').val(),
                    usage_limit: parseInt($('#bulk-usage-limit').val()) || 1
                };
                
                $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    timeout: 60000, // 60 second timeout for bulk operations
                    success: (response) => {
                        console.log('Bulk generate response:', response);
                        if (response && response.success) {
                            this.showNotification(response.data?.message || 'Bulk discounts generated successfully', 'success');
                            this.closeModals();
                            // Reload the page to show new data
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            this.showNotification(response?.data?.message || 'Failed to generate bulk discounts', 'error');
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Bulk generate error:', {xhr, status, error});
                        let errorMessage = 'Error generating bulk discounts';
                        
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please try with fewer codes.';
                        } else if (xhr.responseJSON?.data?.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        }
                        
                        this.showNotification(errorMessage, 'error');
                    },
                    complete: () => {
                        this.setLoading($submitBtn, false);
                        $form.find('input, select, button').prop('disabled', false);
                    }
                });
                
            } catch (error) {
                console.error('Error in generateBulkDiscounts:', error);
                this.showNotification('Error preparing bulk generation', 'error');
            }
        },
        
        // Show notification with better error handling
        showNotification: function(message, type = 'info') {
            try {
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
                        background: ${colors[type] || colors.info};
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
                        font-size: 14px;
                        line-height: 1.4;
                    ">
                        <span style="font-size: 18px; font-weight: bold; flex-shrink: 0;">${icons[type] || icons.info}</span>
                        <span style="flex: 1;">${message}</span>
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
                            font-weight: bold;
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
                
                // Auto-hide after delay (except for errors)
                const hideDelay = type === 'error' ? 8000 : 5000;
                setTimeout(() => {
                    if ($notification.length) {
                        $notification.css({
                            transform: 'translateX(100%)',
                            opacity: 0
                        });
                        setTimeout(() => $notification.remove(), 300);
                    }
                }, hideDelay);
                
            } catch (error) {
                console.error('Error showing notification:', error);
                // Fallback to alert
                alert(message);
            }
        }
    };
    
    // Initialize with delay to ensure DOM is ready
    setTimeout(() => {
        DiscountsManager.init();
    }, 100);
    
    // Fallback initialization if first attempt fails
    setTimeout(() => {
        if (!DiscountsManager.state.initialized) {
            console.warn('🔄 Retrying DiscountsManager initialization...');
            DiscountsManager.init();
        }
    }, 1000);
    
    // Make it globally available for debugging
    window.MoBookingDiscountsManager = DiscountsManager;
    
    console.log('✅ MoBooking Discounts Manager script loaded');
});
</script>



<style>
/* FIXED DISCOUNTS SECTION CSS - COMPREHENSIVE STYLING */

/* CSS Variables for better theming and consistency */
:root {
  --primary: 220 70% 50%;
  --primary-foreground: 210 40% 98%;
  --secondary: 210 40% 96%;
  --secondary-foreground: 222.2 84% 4.9%;
  --muted: 210 40% 96%;
  --muted-foreground: 215.4 16.3% 46.9%;
  --accent: 210 40% 94%;
  --accent-foreground: 222.2 84% 4.9%;
  --destructive: 0 84.2% 60.2%;
  --destructive-foreground: 210 40% 98%;
  --border: 214.3 31.8% 91.4%;
  --input: 214.3 31.8% 91.4%;
  --ring: 222.2 84% 4.9%;
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  --card: 0 0% 100%;
  --card-foreground: 222.2 84% 4.9%;
  --popover: 0 0% 100%;
  --popover-foreground: 222.2 84% 4.9%;
  --radius: 8px;
  
  /* Success colors */
  --success: 142 76% 36%;
  --success-foreground: 355.7 100% 97.3%;
  
  /* Warning colors */
  --warning: 32 95% 44%;
  --warning-foreground: 48 96% 89%;
  
  /* Info colors */
  --info: 217 91% 60%;
  --info-foreground: 213 31% 91%;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  :root {
    --primary: 217.2 91.2% 59.8%;
    --primary-foreground: 222.2 84% 4.9%;
    --secondary: 217.2 32.6% 17.5%;
    --secondary-foreground: 210 40% 98%;
    --muted: 217.2 32.6% 17.5%;
    --muted-foreground: 215 20.2% 65.1%;
    --accent: 217.2 32.6% 17.5%;
    --accent-foreground: 210 40% 98%;
    --destructive: 0 62.8% 30.6%;
    --destructive-foreground: 210 40% 98%;
    --border: 217.2 32.6% 17.5%;
    --input: 217.2 32.6% 17.5%;
    --background: 222.2 84% 4.9%;
    --foreground: 210 40% 98%;
    --card: 222.2 84% 4.9%;
    --card-foreground: 210 40% 98%;
    --popover: 222.2 84% 4.9%;
    --popover-foreground: 210 40% 98%;
  }
}

/* Base styles and reset */
.discounts-section {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  line-height: 1.6;
  color: hsl(var(--foreground));
  background: hsl(var(--background));
  animation: fadeInUp 0.5s ease-out;
  max-width: 100%;
  overflow-x: hidden;
}

.discounts-section * {
  box-sizing: border-box;
}

/* Animation keyframes */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
    transform: translate3d(0,0,0);
  }
  40%, 43% {
    animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
    transform: translate3d(0, -30px, 0);
  }
  70% {
    animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
    transform: translate3d(0, -15px, 0);
  }
  90% {
    transform: translate3d(0,-4px,0);
  }
}

@keyframes sparkle {
  0%, 100% {
    opacity: 0;
    transform: scale(0) rotate(0deg);
  }
  50% {
    opacity: 1;
    transform: scale(1) rotate(180deg);
  }
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

/* Header Section */
.discounts-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 2rem;
  margin-bottom: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, hsl(var(--card)) 0%, hsl(var(--muted)) 100%);
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
  font-size: 2rem;
  font-weight: 700;
  color: hsl(var(--foreground));
  line-height: 1.2;
}

.title-icon {
  width: 32px;
  height: 32px;
  color: hsl(var(--primary));
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.discounts-subtitle {
  margin: 0;
  color: hsl(var(--muted-foreground));
  font-size: 1.1rem;
  font-weight: 400;
}

.discounts-stats {
  display: flex;
  gap: 2rem;
}

.stat-item {
  text-align: center;
  padding: 0.5rem;
}

.stat-number {
  display: block;
  font-size: 1.75rem;
  font-weight: 700;
  color: hsl(var(--primary));
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: hsl(var(--muted-foreground));
  font-weight: 500;
}

.discounts-header-actions {
  align-self: flex-start;
}

/* Stats Grid */
.discounts-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 1.5rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px hsl(var(--primary) / 0.15);
  border-color: hsl(var(--primary) / 0.3);
}

.stat-card:hover::before {
  transform: scaleX(1);
}

.stat-card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
  color: hsl(var(--primary));
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
  background: linear-gradient(135deg, hsl(var(--primary) / 0.2), hsl(var(--primary) / 0.1));
  transform: scale(1.1);
}

.stat-icon svg {
  width: 1.75rem;
  height: 1.75rem;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 2.25rem;
  font-weight: 700;
  color: hsl(var(--foreground));
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: hsl(var(--muted-foreground));
  font-weight: 500;
}

/* Toolbar */
.discounts-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1.25rem;
  background: hsl(var(--muted) / 0.5);
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  backdrop-filter: blur(8px);
}

.filters-section {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.filter-select,
.search-input {
  padding: 0.625rem 0.875rem;
  border: 1px solid hsl(var(--border));
  border-radius: calc(var(--radius) - 2px);
  background: hsl(var(--background));
  color: hsl(var(--foreground));
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.filter-select {
  min-width: 160px;
}

.filter-select:focus,
.search-input:focus {
  outline: none;
  border-color: hsl(var(--primary));
  box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
  transform: translateY(-1px);
}

.search-section {
  flex: 1;
  max-width: 320px;
  position: relative;
}

.search-input {
  width: 100%;
  padding-left: 2.5rem;
}

.search-section::before {
  content: '🔍';
  position: absolute;
  left: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
  z-index: 1;
}

/* Discounts Container */
.discounts-container {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.discounts-grid-header {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, hsl(var(--muted)), hsl(var(--muted) / 0.8));
  border-bottom: 1px solid hsl(var(--border));
  font-weight: 600;
  color: hsl(var(--foreground));
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.discounts-grid-body {
  max-height: 600px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
}

.discounts-grid-body::-webkit-scrollbar {
  width: 8px;
}

.discounts-grid-body::-webkit-scrollbar-track {
  background: transparent;
}

.discounts-grid-body::-webkit-scrollbar-thumb {
  background: hsl(var(--muted-foreground) / 0.3);
  border-radius: 4px;
}

.discounts-grid-body::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground) / 0.5);
}

.discount-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr 1fr 1fr;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid hsl(var(--border) / 0.5);
  transition: all 0.2s ease;
  position: relative;
}

.discount-row:hover {
  background: hsl(var(--muted) / 0.3);
  transform: translateX(4px);
}

.discount-row:last-child {
  border-bottom: none;
}

.discount-row.loading {
  opacity: 0.6;
  pointer-events: none;
  position: relative;
}

.discount-row.loading::after {
  content: '';
  position: absolute;
  top: 50%;
  right: 1rem;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border: 2px solid hsl(var(--muted-foreground) / 0.3);
  border-radius: 50%;
  border-top-color: hsl(var(--primary));
  animation: spin 1s linear infinite;
}

.discount-row.success {
  background: rgba(34, 197, 94, 0.1);
  animation: successPulse 0.6s ease-out;
}

.grid-cell {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  min-height: 2.5rem;
}

/* Code Display */
.code-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.code-text {
  font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-weight: 600;
  background: linear-gradient(135deg, hsl(var(--muted)), hsl(var(--muted) / 0.5));
  padding: 0.375rem 0.75rem;
  border-radius: calc(var(--radius) - 2px);
  color: hsl(var(--primary));
  font-size: 0.8rem;
  border: 1px solid hsl(var(--border));
  transition: all 0.2s ease;
}

.code-display:hover .code-text {
  background: linear-gradient(135deg, hsl(var(--primary) / 0.1), hsl(var(--primary) / 0.05));
  border-color: hsl(var(--primary) / 0.3);
}

.copy-code-btn {
  padding: 0.375rem;
  border: none;
  background: transparent;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  border-radius: calc(var(--radius) - 2px);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.copy-code-btn:hover {
  background: hsl(var(--muted));
  color: hsl(var(--foreground));
  transform: scale(1.1);
}

.copy-code-btn:active {
  transform: scale(0.95);
}

.copy-code-btn svg {
  width: 14px;
  height: 14px;
}

/* Badges */
.type-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid;
  transition: all 0.2s ease;
}

.type-badge svg,
.status-badge svg {
  width: 12px;
  height: 12px;
}

/* Type badges */
.type-badge.type-percentage {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
  color: #16a34a;
  border-color: rgba(34, 197, 94, 0.2);
}

.type-badge.type-fixed {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.05));
  color: #2563eb;
  border-color: rgba(59, 130, 246, 0.2);
}

/* Status badges */
.status-badge.status-active {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(34, 197, 94, 0.05));
  color: #16a34a;
  border-color: rgba(34, 197, 94, 0.2);
}

.status-badge.status-inactive {
  background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(107, 114, 128, 0.05));
  color: #6b7280;
  border-color: rgba(107, 114, 128, 0.2);
}

.status-badge.status-expired {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
  color: #dc2626;
  border-color: rgba(239, 68, 68, 0.2);
}

/* Amount Display */
.amount-display {
  font-weight: 600;
  color: hsl(var(--foreground));
  font-size: 0.9rem;
}

/* Usage Display */
.usage-display {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
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
  max-width: 80px;
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
  background: linear-gradient(90deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
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
  font-weight: 500;
}

.expiry-status {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.125rem 0.5rem;
  border-radius: 12px;
  display: inline-block;
  width: fit-content;
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

.limit-reached {
  display: block;
  font-size: 0.65rem;
  color: #f59e0b;
  font-weight: 500;
  margin-top: 0.125rem;
  padding: 0.125rem 0.375rem;
  background: rgba(245, 158, 11, 0.1);
  border-radius: 8px;
  width: fit-content;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn-icon {
  padding: 0.5rem;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--background));
  border-radius: calc(var(--radius) - 2px);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: hsl(var(--muted-foreground));
  position: relative;
  overflow: hidden;
}

.btn-icon::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: hsl(var(--primary) / 0.1);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: all 0.3s ease;
}

.btn-icon:hover::before {
  width: 100%;
  height: 100%;
}

.btn-icon:hover {
  border-color: hsl(var(--primary) / 0.5);
  color: hsl(var(--primary));
  transform: translateY(-2px);
  box-shadow: 0 4px 12px hsl(var(--primary) / 0.2);
}

.btn-icon:active {
  transform: translateY(0);
}

.btn-icon svg {
  width: 16px;
  height: 16px;
  position: relative;
  z-index: 1;
}

.btn-icon.btn-danger {
  border-color: hsl(var(--destructive) / 0.3);
  color: hsl(var(--destructive));
}

.btn-icon.btn-danger::before {
  background: hsl(var(--destructive) / 0.1);
}

.btn-icon.btn-danger:hover {
  border-color: hsl(var(--destructive));
  color: hsl(var(--destructive));
  box-shadow: 0 4px 12px hsl(var(--destructive) / 0.2);
}

/* Buttons */
.btn-primary,
.btn-secondary,
.btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border: 1px solid;
  border-radius: var(--radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  position: relative;
  overflow: hidden;
  user-select: none;
}

.btn-primary {
  background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.9));
  border-color: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  box-shadow: 0 2px 4px hsl(var(--primary) / 0.2);
}

.btn-primary:hover {
  background: linear-gradient(135deg, hsl(var(--primary) / 0.9), hsl(var(--primary) / 0.8));
  transform: translateY(-2px);
  box-shadow: 0 6px 20px hsl(var(--primary) / 0.3);
}

.btn-primary:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px hsl(var(--primary) / 0.2);
}

.btn-secondary {
  background: hsl(var(--secondary));
  border-color: hsl(var(--border));
  color: hsl(var(--secondary-foreground));
}

.btn-secondary:hover {
  background: hsl(var(--accent));
  border-color: hsl(var(--primary) / 0.5);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-danger {
  background: linear-gradient(135deg, hsl(var(--destructive)), hsl(var(--destructive) / 0.9));
  border-color: hsl(var(--destructive));
  color: hsl(var(--destructive-foreground));
  box-shadow: 0 2px 4px hsl(var(--destructive) / 0.2);
}

.btn-danger:hover {
  background: linear-gradient(135deg, hsl(var(--destructive) / 0.9), hsl(var(--destructive) / 0.8));
  transform: translateY(-2px);
  box-shadow: 0 6px 20px hsl(var(--destructive) / 0.3);
}

.btn-large {
  padding: 1rem 2rem;
  font-size: 1rem;
  font-weight: 600;
}

.btn-primary svg,
.btn-secondary svg,
.btn-danger svg {
  width: 16px;
  height: 16px;
}

/* BUTTON LOADING STATES CSS */

/* Base loading class for buttons */
.loading {
  pointer-events: none;
  opacity: 0.8;
  position: relative;
}

/* Hide normal button text when loading */
.loading .btn-text {
  opacity: 0;
  visibility: hidden;
}

/* Show loading content */
.btn-loading {
  display: none;
  align-items: center;
  gap: 0.5rem;
  font-size: inherit;
  font-weight: inherit;
}

.loading .btn-loading {
  display: inline-flex;
}

/* Loading spinner - Method 1: CSS-only spinner */
.btn-loading::before {
  content: "";
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: rgba(255, 255, 255, 0.8);
  animation: btn-spin 1s linear infinite;
  flex-shrink: 0;
}

/* For secondary buttons - darker spinner */
.btn-secondary.loading .btn-loading::before {
  border-color: rgba(0, 0, 0, 0.2);
  border-top-color: rgba(0, 0, 0, 0.6);
}

/* For danger buttons - maintain white spinner */
.btn-danger.loading .btn-loading::before {
  border-color: rgba(255, 255, 255, 0.3);
  border-top-color: rgba(255, 255, 255, 0.8);
}

/* Spin animation */
@keyframes btn-spin {
  to {
    transform: rotate(360deg);
  }
}

/* Alternative Method 2: Loading with text and spinner */
.btn-loading-alt {
  display: none;
  align-items: center;
  gap: 0.5rem;
}

.loading .btn-loading-alt {
  display: inline-flex;
}

.btn-loading-alt .spinner {
  width: 14px;
  height: 14px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: btn-spin 1s linear infinite;
}

/* Method 3: Dots loading animation */
.btn-loading-dots {
  display: none;
  align-items: center;
  gap: 0.25rem;
}

.loading .btn-loading-dots {
  display: inline-flex;
}

.btn-loading-dots .dot {
  width: 4px;
  height: 4px;
  background: currentColor;
  border-radius: 50%;
  animation: btn-dot-bounce 1.4s infinite ease-in-out both;
}

.btn-loading-dots .dot:nth-child(1) { animation-delay: -0.32s; }
.btn-loading-dots .dot:nth-child(2) { animation-delay: -0.16s; }
.btn-loading-dots .dot:nth-child(3) { animation-delay: 0s; }

@keyframes btn-dot-bounce {
  0%, 80%, 100% {
    transform: scale(0);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Method 4: Pulse loading */
.btn-loading-pulse {
  display: none;
  align-items: center;
  gap: 0.5rem;
}

.loading .btn-loading-pulse {
  display: inline-flex;
}

.btn-loading-pulse .pulse-circle {
  width: 8px;
  height: 8px;
  background: currentColor;
  border-radius: 50%;
  animation: btn-pulse 1.5s infinite;
}

@keyframes btn-pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.5);
    opacity: 0.3;
  }
}

/* Enhanced loading states for different button sizes */

/* Large buttons */
.btn-large.loading .btn-loading::before {
  width: 20px;
  height: 20px;
  border-width: 3px;
}

/* Small buttons */
.btn-sm.loading .btn-loading::before {
  width: 12px;
  height: 12px;
  border-width: 1.5px;
}

/* Icon buttons loading state */
.btn-icon.loading {
  opacity: 0.7;
}

.btn-icon.loading::after {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 16px;
  height: 16px;
  border: 2px solid rgba(0, 0, 0, 0.2);
  border-radius: 50%;
  border-top-color: rgba(0, 0, 0, 0.6);
  animation: btn-spin 1s linear infinite;
}

.btn-icon.btn-danger.loading::after {
  border-color: rgba(239, 68, 68, 0.3);
  border-top-color: rgba(239, 68, 68, 0.8);
}

/* Disable hover effects when loading */
.loading:hover {
  transform: none !important;
  box-shadow: none !important;
}

/* Loading overlay for buttons */
.btn-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.1);
  border-radius: inherit;
  display: none;
  align-items: center;
  justify-content: center;
}

.loading .btn-loading-overlay {
  display: flex;
}

.btn-loading-overlay .spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: rgba(255, 255, 255, 0.8);
  animation: btn-spin 1s linear infinite;
}

/* Form submit button specific loading */
.form-submit-loading {
  position: relative;
  pointer-events: none;
}

.form-submit-loading .btn-text {
  opacity: 0.3;
}

.form-submit-loading::after {
  content: "";
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: rgba(255, 255, 255, 0.8);
  animation: btn-spin 1s linear infinite;
}

/* Smooth transitions for loading states */
.btn-text,
.btn-loading {
  transition: opacity 0.2s ease, visibility 0.2s ease;
}

/* Loading state for different themes */

/* Dark mode loading adjustments */
@media (prefers-color-scheme: dark) {
  .btn-secondary.loading .btn-loading::before {
    border-color: rgba(255, 255, 255, 0.2);
    border-top-color: rgba(255, 255, 255, 0.6);
  }
  
  .btn-icon.loading::after {
    border-color: rgba(255, 255, 255, 0.2);
    border-top-color: rgba(255, 255, 255, 0.6);
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .loading .btn-loading::before,
  .btn-icon.loading::after {
    border-width: 3px;
  }
}

/* Reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
  .btn-loading::before,
  .btn-loading-alt .spinner,
  .btn-icon.loading::after,
  .btn-loading-overlay .spinner {
    animation: none;
  }
  
  /* Show static loading indicator instead */
  .loading .btn-loading::before {
    content: "⏳";
    border: none;
    width: auto;
    height: auto;
    font-size: 16px;
  }
  
  .btn-loading-dots .dot {
    animation: none;
    opacity: 0.6;
  }
  
  .btn-loading-pulse .pulse-circle {
    animation: none;
    opacity: 0.6;
  }
}

/* Specific loading states for common actions */
.saving .btn-loading::after {
  content: " Saving...";
}

.deleting .btn-loading::after {
  content: " Deleting...";
}

.generating .btn-loading::after {
  content: " Generating...";
}

.processing .btn-loading::after {
  content: " Processing...";
}

/* Button loading with progress indication */
.btn-progress {
  position: relative;
  overflow: hidden;
}

.btn-progress::before {
  content: "";
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  animation: btn-progress-shine 2s infinite;
}

@keyframes btn-progress-shine {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

/* Loading button with percentage */
.btn-loading-percent {
  position: relative;
}

.btn-loading-percent::after {
  content: attr(data-progress) "%";
  position: absolute;
  right: 0.75rem;
  font-size: 0.75rem;
  opacity: 0.8;
}

/* Disabled state during loading */
.loading,
.loading:hover,
.loading:focus,
.loading:active {
  cursor: not-allowed;
  user-select: none;
}

/* Loading button group styles */
.btn-group .loading {
  z-index: 1;
}

.btn-group .loading:not(:first-child) {
  border-left-color: transparent;
}

.btn-group .loading:not(:last-child) {
  border-right-color: transparent;
}
/* Modal Styles */
.mobooking-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  animation: fadeIn 0.3s ease-out;
}

.modal-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  position: relative;
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  transform: scale(0.95);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.mobooking-modal[style*="flex"] .modal-content {
  transform: scale(1);
  opacity: 1;
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid hsl(var(--border));
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, hsl(var(--muted) / 0.5), hsl(var(--muted) / 0.3));
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: hsl(var(--foreground));
}

.modal-close {
  padding: 0.5rem;
  border: none;
  background: transparent;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  border-radius: calc(var(--radius) - 2px);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: hsl(var(--muted));
  color: hsl(var(--foreground));
  transform: scale(1.1);
}

.modal-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid hsl(var(--border));
  background: hsl(var(--muted) / 0.3);
  border-bottom-left-radius: var(--radius);
  border-bottom-right-radius: var(--radius);
}

/* Form Styles */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: hsl(var(--foreground));
  font-size: 0.875rem;
}

.form-control {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid hsl(var(--border));
  border-radius: calc(var(--radius) - 2px);
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background: hsl(var(--background));
  color: hsl(var(--foreground));
}

.form-control:focus {
  outline: none;
  border-color: hsl(var(--primary));
  box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
  transform: translateY(-1px);
}

.form-control:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: hsl(var(--muted) / 0.5);
}

.form-control.error {
  border-color: hsl(var(--destructive));
  box-shadow: 0 0 0 3px hsl(var(--destructive) / 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

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
  font-size: 0.875rem;
}

.amount-input-wrapper input {
  padding-left: 2.5rem;
}

.field-help {
  font-size: 0.75rem;
  color: hsl(var(--muted-foreground));
  margin-top: 0.375rem;
  line-height: 1.4;
}

.field-error {
  color: hsl(var(--destructive));
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

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 1rem;
  border: 1px solid hsl(var(--border));
  border-radius: calc(var(--radius) - 2px);
  transition: all 0.2s ease;
  background: hsl(var(--background));
}

.checkbox-label:hover {
  background: hsl(var(--muted) / 0.5);
  border-color: hsl(var(--primary) / 0.5);
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
  accent-color: hsl(var(--primary));
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.spacer {
  flex: 1;
}

/* Empty State */
.discounts-empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: linear-gradient(135deg, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
  border-radius: var(--radius);
  border: 2px dashed hsl(var(--border));
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
  background: linear-gradient(135deg, hsl(var(--primary) / 0.15), hsl(var(--primary) / 0.05));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  position: relative;
  overflow: hidden;
}

.empty-state-icon::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, hsl(var(--primary) / 0.1), transparent);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
  100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.empty-state-icon svg {
  width: 2.5rem;
  height: 2.5rem;
  color: hsl(var(--primary));
  position: relative;
  z-index: 1;
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
  animation: sparkle 2s infinite ease-in-out;
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

.empty-state-content h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin-bottom: 0.75rem;
}

.empty-state-content p {
  color: hsl(var(--muted-foreground));
  max-width: 500px;
  margin: 0 auto 2rem;
  line-height: 1.6;
  font-size: 1rem;
}

/* Discount Generator */
.discount-generator {
  margin-top: 2rem;
  padding: 2rem;
  background: linear-gradient(135deg, hsl(var(--muted) / 0.5), hsl(var(--muted) / 0.3));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
}

.discount-generator h3 {
  margin: 0 0 1.5rem 0;
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

/* Notification Styles */
.discount-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;
  min-width: 320px;
  max-width: 500px;
  padding: 1rem 1.25rem;
  border-radius: var(--radius);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  transform: translateX(100%);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
  font-size: 0.875rem;
  line-height: 1.4;
  color: white;
  backdrop-filter: blur(8px);
}

.discount-notification.success {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.discount-notification.error {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.discount-notification.warning {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.discount-notification.info {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.discount-notification button {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  font-weight: bold;
  transition: all 0.2s ease;
}

.discount-notification button:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

/* Utility Classes */
.text-success { color: hsl(var(--success)) !important; }
.text-error { color: hsl(var(--destructive)) !important; }
.text-warning { color: hsl(var(--warning)) !important; }
.text-info { color: hsl(var(--info)) !important; }

.bg-success { background-color: hsl(var(--success) / 0.1) !important; }
.bg-error { background-color: hsl(var(--destructive) / 0.1) !important; }
.bg-warning { background-color: hsl(var(--warning) / 0.1) !important; }
.bg-info { background-color: hsl(var(--info) / 0.1) !important; }

/* Body Modal Open State */
body.modal-open {
  overflow: hidden;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .discounts-grid-header,
  .discount-row {
    grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 0.8fr 0.8fr;
    font-size: 0.8rem;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
  }
  
  .discounts-main-title {
    font-size: 1.75rem;
  }
  
  .stat-value {
    font-size: 1.875rem;
  }
}

@media (max-width: 968px) {
  .discounts-header {
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
  }
  
  .discounts-header-content {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .discounts-stats {
    align-self: center;
    justify-content: center;
  }
  
  .discounts-header-actions {
    align-self: stretch;
  }
  
  .discounts-toolbar {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }
  
  .filters-section {
    width: 100%;
    justify-content: space-between;
  }
  
  .search-section {
    max-width: none;
  }
  
  .discounts-stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .generator-actions {
    flex-direction: column;
  }
}

@media (max-width: 768px) {
  /* Mobile table transformation */
  .discounts-grid-header {
    display: none;
  }
  
  .discount-row {
    display: block;
    padding: 1.25rem;
    border-bottom: 1px solid hsl(var(--border));
    margin-bottom: 1rem;
    border-radius: var(--radius);
    background: hsl(var(--card));
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid hsl(var(--border));
  }
  
  .discount-row:hover {
    transform: none;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
  }
  
  .discount-row .grid-cell {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid hsl(var(--border) / 0.3);
    margin: 0;
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
    min-width: 80px;
  }
  
  /* Add data labels for mobile */
  .discount-row .discount-code { --label: "Code"; }
  .discount-row .discount-type { --label: "Type"; }
  .discount-row .discount-amount { --label: "Amount"; }
  .discount-row .usage-info { --label: "Usage"; }
  .discount-row .expiry-date { --label: "Expires"; }
  .discount-row .status { --label: "Status"; }
  
  .discount-row .discount-code::before { content: "Code"; }
  .discount-row .discount-type::before { content: "Type"; }
  .discount-row .discount-amount::before { content: "Amount"; }
  .discount-row .usage-info::before { content: "Usage"; }
  .discount-row .expiry-date::before { content: "Expires"; }
  .discount-row .status::before { content: "Status"; }
  
  .action-buttons {
    justify-content: flex-end;
    margin-top: 0.5rem;
  }
  
  .modal-content {
    margin: 0.5rem;
    max-height: calc(100vh - 1rem);
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem 1.25rem;
  }
}

@media (max-width: 480px) {
  .discounts-main-title {
    font-size: 1.5rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .title-icon {
    width: 24px;
    height: 24px;
  }
  
  .discounts-stats {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
    width: 100%;
  }
  
  .stat-item {
    padding: 0.75rem;
    background: hsl(var(--muted) / 0.3);
    border-radius: calc(var(--radius) - 2px);
    border: 1px solid hsl(var(--border));
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
  
  .discounts-stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .stat-card {
    padding: 1.25rem;
  }
  
  .stat-value {
    font-size: 1.75rem;
  }
  
  .filters-section {
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
  }
  
  .filter-select {
    width: 100%;
  }
  
  .discount-notification {
    left: 1rem;
    right: 1rem;
    min-width: auto;
    max-width: none;
  }
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
  .type-badge,
  .status-badge,
  .expiry-status {
    border-width: 2px;
    font-weight: 600;
  }
  
  .code-text {
    border: 2px solid hsl(var(--primary));
  }
  
  .usage-progress-bar {
    border: 1px solid hsl(var(--border));
  }
  
  .btn-primary,
  .btn-danger {
    border-width: 2px;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
  
  .sparkle {
    animation: none;
    opacity: 0.5;
  }
  
  .empty-state-icon::before {
    animation: none;
  }
}

/* Print Styles */
@media print {
  .discounts-header-actions,
  .discounts-toolbar,
  .action-buttons,
  .generator-actions,
  .modal-close,
  .discount-notification {
    display: none !important;
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
  
  .discount-row:hover {
    background: transparent !important;
    transform: none !important;
  }
}

/* Focus Styles for Accessibility */
.copy-code-btn:focus,
.btn-icon:focus,
.btn-primary:focus,
.btn-secondary:focus,
.btn-danger:focus,
.modal-close:focus {
  outline: 2px solid hsl(var(--primary));
  outline-offset: 2px;
}

.filter-select:focus,
.search-input:focus,
.form-control:focus {
  outline: 2px solid hsl(var(--primary));
  outline-offset: 2px;
}

/* Custom Scrollbar Styles */
.discounts-container {
  scrollbar-width: thin;
  scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
}

.discounts-container::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.discounts-container::-webkit-scrollbar-track {
  background: transparent;
}

.discounts-container::-webkit-scrollbar-thumb {
  background: hsl(var(--muted-foreground) / 0.3);
  border-radius: 4px;
}

.discounts-container::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground) / 0.5);
}

/* Loading Overlay */
.discounts-section.loading {
  position: relative;
  pointer-events: none;
}

.discounts-section.loading::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.discounts-section.loading::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 40px;
  height: 40px;
  border: 4px solid hsl(var(--muted));
  border-radius: 50%;
  border-top-color: hsl(var(--primary));
  animation: spin 1s linear infinite;
  z-index: 101;
}

/* Smooth transitions for dynamic content */
.discount-row {
  opacity: 1;
  transition: opacity 0.3s ease, transform 0.2s ease;
}

.discount-row.hidden {
  opacity: 0;
  transform: translateX(-20px);
}

.discount-row.removing {
  opacity: 0;
  transform: translateX(100%);
  transition: all 0.3s ease;
}

/* Enhanced focus indicators */
.btn-primary:focus-visible,
.btn-secondary:focus-visible,
.btn-danger:focus-visible {
  outline: 2px solid hsl(var(--primary));
  outline-offset: 2px;
  box-shadow: 0 0 0 4px hsl(var(--primary) / 0.2);
}

/* Improved mobile touch targets */
@media (max-width: 768px) {
  .btn-icon {
    min-width: 44px;
    min-height: 44px;
    padding: 0.75rem;
  }
  
  .copy-code-btn {
    min-width: 44px;
    min-height: 44px;
    padding: 0.75rem;
  }
  
  .modal-close {
    min-width: 44px;
    min-height: 44px;
    padding: 0.75rem;
  }
}

/* Enhanced empty state animation */
.empty-state-visual {
  animation: bounce 2s infinite;
}

@media (prefers-reduced-motion: reduce) {
  .empty-state-visual {
    animation: none;
  }
}
</style>